<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Mail\PurchaseReceiptMail;
use App\Mail\SaleNotificationMail;
use App\Models\Order;
use App\Models\Product;
use App\Notifications\NewSaleNotification;
use App\Services\AifoPaymentService;
use App\Services\PromoCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class CheckoutController extends Controller
{
    public function store(Request $request, Product $product, AifoPaymentService $payments, PromoCodeService $promoService)
    {
        abort_unless($product->status === 'published', 404);

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
            $check = $promoService->validate((string) $stashed['code'], auth()->user(), $subtotal);
            if ($check) {
                $discount = (float) $check['discount'];
                $promoApplied = $check['promo'];
            }
        }

        $total = max(0.0, round($subtotal - $discount, 2));

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
            'price' => $total,
            'currency' => $product->currency,
            'license_type' => $licenseType,
            'license_snapshot' => $licenseSnapshot,
        ]);

        if ($promoApplied) {
            $promoService->redeem($promoApplied, auth()->user(), $order, $discount);
        }

        $payment = $payments->createPayment($order);

        if ($payment->status === 'paid') {
            Mail::to($order->user)->queue(new PurchaseReceiptMail($order));
            Mail::to($product->author)->queue(new SaleNotificationMail($order, $product->author));

            foreach ($order->items as $item) {
                $item->author?->notify(new NewSaleNotification($item, $order));
            }

            return redirect()->route('checkout.success', $order);
        }

        return view('marketplace.checkout', compact('order', 'payment'));
    }

    public function success(Order $order)
    {
        abort_unless($order->user_id === auth()->id(), 403);

        return view('marketplace.checkout-success', compact('order'));
    }

    public function demoConfirm(Order $order, AifoPaymentService $payments)
    {
        abort_unless($order->user_id === auth()->id(), 403);
        abort_unless($order->status === 'pending' && $order->payment, 404);

        $payments->markPaid($order->payment, ['demo_confirmed_by_user' => true]);

        Mail::to($order->user)->queue(new PurchaseReceiptMail($order));
        foreach ($order->items as $item) {
            Mail::to($item->author)->queue(new SaleNotificationMail($order, $item->author));
            $item->author?->notify(new NewSaleNotification($item, $order));
        }

        return redirect()->route('checkout.success', $order);
    }
}
