<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ProductBundle;
use App\Services\AifoPaymentService;
use App\Services\AuditLogger;
use App\Services\MarketplaceAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\PurchaseReceiptMail;
use App\Mail\SaleNotificationMail;
use App\Notifications\NewSaleNotification;

class BundleController extends Controller
{
    public function show(ProductBundle $bundle)
    {
        abort_unless($bundle->is_active, 404);
        $bundle->loadMissing(['items.author', 'items.files', 'author']);

        $owned = [];
        if (auth()->check()) {
            $access = app(MarketplaceAccess::class);
            foreach ($bundle->items as $product) {
                $owned[$product->id] = $access->canDownload(auth()->user(), $product);
            }
        }

        return view('marketplace.bundle', compact('bundle', 'owned'));
    }

    public function checkout(Request $request, ProductBundle $bundle, AifoPaymentService $payments, AuditLogger $audit)
    {
        abort_unless($bundle->is_active, 404);
        $bundle->loadMissing(['items.author']);

        $order = Order::create([
            'number'   => 'ORD-'.now()->format('YmdHis').'-'.strtoupper(str()->random(5)),
            'user_id'  => auth()->id(),
            'status'   => 'pending',
            'subtotal' => $bundle->price,
            'total'    => $bundle->price,
            'currency' => $bundle->currency,
        ]);

        foreach ($bundle->items as $product) {
            $order->items()->create([
                'product_id'  => $product->id,
                'author_id'   => $product->user_id,
                'price'       => 0, // price is at bundle level
                'currency'    => $bundle->currency,
                'license_type' => 'personal',
            ]);
        }

        $audit->record('checkout.bundle_order_created', $order, [
            'bundle_id' => $bundle->id,
            'total'     => $bundle->price,
        ]);

        $payment = $payments->createPayment($order);

        if ($payment->status === 'paid') {
            Mail::to($order->user)->queue(new PurchaseReceiptMail($order));
            foreach ($order->items as $item) {
                if ($item->author) {
                    Mail::to($item->author)->queue(new SaleNotificationMail($order, $item->author));
                    $item->author->notify(new NewSaleNotification($item, $order));
                }
            }
            return redirect()->route('checkout.success', $order);
        }

        return view('marketplace.checkout', compact('order', 'payment'));
    }
}
