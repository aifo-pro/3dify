<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Jobs\SendAbandonedCartEmail;
use App\Mail\PurchaseReceiptMail;
use App\Mail\SaleNotificationMail;
use App\Models\Order;
use App\Models\Product;
use App\Services\AccountBalanceService;
use App\Notifications\NewSaleNotification;
use App\Services\AifoPaymentService;
use App\Services\AuditLogger;
use App\Services\PromoCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class CheckoutController extends Controller
{
    public function store(Request $request, Product $product, AifoPaymentService $payments, PromoCodeService $promoService, AccountBalanceService $balances, AuditLogger $audit)
    {
        abort_unless($product->status === 'published', 404);

        $data = $request->validate([
            'balance_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        // Resolve license type. Falls back to "personal" if commercial isn't offered.
        $requestedType = (string) $request->input('license_type', 'personal');
        $licenseType = in_array($requestedType, Product::LICENSE_TYPES, true) ? $requestedType : 'personal';
        if ($licenseType === 'commercial' && ! $product->commercial_license_enabled) {
            $licenseType = 'personal';
        }

        $licenseModel = $product->licenseFor($licenseType);
        $licenseSnapshot = $licenseModel ? $licenseModel->toSnapshot() : null;
        if ($licenseSnapshot && $licenseType === 'commercial' && is_array($product->commercial_license_description ?? null)) {
            // Capture author's per-product commercial wording so it survives later edits.
            $licenseSnapshot['description'] = $product->commercial_license_description;
        }
        $licenseSnapshot = $licenseSnapshot ? array_merge($licenseSnapshot, ['type' => $licenseType]) : ['type' => $licenseType];

        $subtotal = $product->priceFor($licenseType);
        $discount = 0.0;
        $promoApplied = null;

        $stashed = session()->pull('promo.'.$product->id);
        if ($stashed && ($stashed['product_id'] ?? null) === $product->id) {
            $check = $promoService->validate((string) $stashed['code'], auth()->user(), $subtotal, $product);
            if ($check) {
                $discount = (float) $check['discount'];
                $promoApplied = $check['promo'];
            }
        }

        $itemTotal = max(0.0, round($subtotal - $discount, 2));

        // Author earning base: a SYSTEM promo (no author_id) is funded by the
        // platform, so the author still earns on the full price. An AUTHOR promo
        // is funded by the author, so the base is the discounted price.
        $isSystemPromo = $promoApplied && $promoApplied->author_id === null;
        $authorEarningBase = $isSystemPromo ? round($subtotal, 2) : $itemTotal;
        $availableBalance = $balances->availableBalance($request->user(), $product->currency ?: AccountBalanceService::DEFAULT_CURRENCY);
        $requestedBalance = $request->boolean('use_balance')
            ? (float) ($data['balance_amount'] ?? $availableBalance)
            : 0.0;
        $balanceAmount = round(min(max(0, $requestedBalance), $availableBalance, $itemTotal), 2);
        $total = max(0.0, round($itemTotal - $balanceAmount, 2));

        $order = Order::create([
            'number' => 'ORD-'.now()->format('YmdHis').'-'.strtoupper(str()->random(5)),
            'user_id' => auth()->id(),
            'status' => 'pending',
            'subtotal' => $subtotal,
            'total' => $total,
            'currency' => $product->currency,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'author_id' => $product->user_id,
            'price' => $itemTotal,
            'author_earning_base' => $authorEarningBase,
            'currency' => $product->currency,
            'license_type' => $licenseType,
            'license_snapshot' => $licenseSnapshot,
        ]);

        $audit->record('checkout.order_created', $order, [
            'product_id' => $product->id,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'balance_amount' => $balanceAmount,
            'total' => $total,
            'currency' => $product->currency,
        ]);

        $balanceHold = $balances->reserveForOrder($request->user(), $order, $balanceAmount, $product->currency);

        if ($promoApplied) {
            $promoService->redeem($promoApplied, auth()->user(), $order, $discount);
        }

        $payment = $payments->createPayment($order);
        if ($balanceHold) {
            $payment->update([
                'payload' => array_merge($payment->payload ?? [], [
                    'balance_applied' => (float) $balanceHold->amount,
                    'balance_transaction_id' => $balanceHold->id,
                    'order_item_total' => $itemTotal,
                ]),
            ]);
        }

        // Schedule abandoned cart email if order stays pending after 1 hour
        if ($payment->status !== 'paid') {
            SendAbandonedCartEmail::dispatch($order->id)->delay(now()->addHour());
        }

        if ($payment->status === 'paid') {
            Mail::to($order->user)->queue(new PurchaseReceiptMail($order));
            Mail::to($product->author)->queue(new SaleNotificationMail($order, $product->author));

            foreach ($order->items as $item) {
                $item->author?->notify(new NewSaleNotification($item, $order));
            }

            return redirect()->route('checkout.success', $order);
        }

        $order->load(['items.product.author', 'items.author']);

        return view('marketplace.checkout', compact('order', 'payment'));
    }

    public function success(Order $order)
    {
        abort_unless($order->user_id === auth()->id(), 403);

        $order->load(['payment', 'items.product.author', 'items.product.files', 'items.author']);

        return view('marketplace.checkout-success', compact('order'));
    }

    public function failed(Order $order)
    {
        abort_unless($order->user_id === auth()->id(), 403);

        $order->load(['payment', 'items.product.author', 'items.author']);

        return view('marketplace.checkout-failed', compact('order'));
    }

    public function demoConfirm(Order $order, AifoPaymentService $payments, AuditLogger $audit)
    {
        abort_unless($order->user_id === auth()->id(), 403);
        abort_unless($order->status === 'pending' && $order->payment, 404);

        $payments->markPaid($order->payment, ['demo_confirmed_by_user' => true]);
        $audit->record('payment.demo_confirmed', $order, ['order_id' => $order->id]);

        Mail::to($order->user)->queue(new PurchaseReceiptMail($order));
        foreach ($order->items as $item) {
            Mail::to($item->author)->queue(new SaleNotificationMail($order, $item->author));
            $item->author?->notify(new NewSaleNotification($item, $order));
        }

        return redirect()->route('checkout.success', $order);
    }
}
