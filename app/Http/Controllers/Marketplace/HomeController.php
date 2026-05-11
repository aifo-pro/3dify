<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Mail\PurchaseReceiptMail;
use App\Mail\SaleNotificationMail;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\TipPayment;
use App\Models\User;
use App\Notifications\NewTipNotification;
use App\Services\AccountBalanceService;
use App\Services\AifoPaymentService;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class HomeController extends Controller
{
    public function __invoke(Request $request)
    {
        if ($redirect = $this->redirectAifoTipReturn($request)) {
            return $redirect;
        }

        if ($redirect = $this->redirectAifoOrderReturn($request)) {
            return $redirect;
        }

        $emptyStats = [
            'products' => 0,
            'free_products' => 0,
            'authors' => 0,
            'categories' => 0,
            'paid_orders' => 0,
            'downloads' => 0,
            'views' => 0,
        ];

        if (! Schema::hasTable('products')) {
            return view('marketplace.home', [
                'featuredProducts' => collect(),
                'popularProducts' => collect(),
                'latestProducts' => collect(),
                'freeProducts' => collect(),
                'categories' => collect(),
                'stats' => $emptyStats,
            ]);
        }

        $stats = [
            'products' => Product::query()->published()->count(),
            'free_products' => Product::query()->published()->where('is_free', true)->count(),
            'authors' => Schema::hasTable('users')
                ? User::query()->whereHas('products', fn ($q) => $q->where('status', 'published'))->count()
                : 0,
            'categories' => Schema::hasTable('categories')
                ? Category::query()->where('is_active', true)->count()
                : 0,
            'paid_orders' => Schema::hasTable('orders')
                ? Order::query()->where('status', 'paid')->count()
                : 0,
            'downloads' => (int) Product::query()->published()->sum('downloads_count'),
            'views' => (int) Product::query()->published()->sum('views_count'),
        ];

        return view('marketplace.home', [
            'featuredProducts' => Product::query()->with(['author', 'category'])->published()->where('is_featured', true)->latest('published_at')->take(8)->get(),
            'popularProducts' => Product::query()->with(['author', 'category'])->published()->orderByDesc('views_count')->latest('published_at')->take(8)->get(),
            'latestProducts' => Product::query()->with(['author', 'category'])->published()->latest('published_at')->take(8)->get(),
            'freeProducts' => Product::query()->with(['author', 'category'])->published()->where('is_free', true)->latest('published_at')->take(4)->get(),
            'categories' => Category::query()->where('is_active', true)->orderBy('sort_order')->take(8)->get(),
            'stats' => $stats,
        ]);
    }

    private function redirectAifoTipReturn(Request $request)
    {
        $reference = $request->query('orderReference')
            ?? $request->query('external_id')
            ?? $request->query('pay_id');

        if (! is_string($reference) || ! str_starts_with($reference, 'TIP-') || ! Schema::hasTable('tip_payments')) {
            return null;
        }

        $tipId = (int) substr($reference, 4);
        $payment = TipPayment::query()
            ->with('tip.product')
            ->where(function ($query) use ($reference, $tipId) {
                $query->where('provider_payment_id', $reference);
                if ($tipId > 0) {
                    $query->orWhere('tip_id', $tipId);
                }
            })
            ->latest('id')
            ->first();

        $product = $payment?->tip?->product;
        if (! $product) {
            return null;
        }

        $status = strtolower((string) $request->query('status'));
        $isPaidReturn = in_array($status, ['paid', 'success', 'completed'], true);

        if ($isPaidReturn
            && $payment->status !== 'paid'
            && auth()->check()
            && auth()->id() === $payment->tip->user_id) {
            app(AifoPaymentService::class)->markTipPaid($payment, [
                'aifo_return' => $request->query(),
                'marked_paid_from_return' => true,
            ]);
            $payment->tip->author?->notify(new NewTipNotification($payment->tip));
        }

        $message = $isPaidReturn
            ? __('Дякуємо! Оплату подяки успішно прийнято. Кошти зараховано на баланс автора.')
            : __('Повернення з AIFO отримано. Статус подяки оновиться після підтвердження платежу.');

        return redirect()
            ->route('products.show', $product)
            ->with('status', $message);
    }

    private function redirectAifoOrderReturn(Request $request)
    {
        $reference = $request->query('orderReference')
            ?? $request->query('external_id')
            ?? $request->query('pay_id');

        if (! is_string($reference) || ! str_starts_with($reference, 'ORD-') || ! Schema::hasTable('orders')) {
            return null;
        }

        $order = Order::query()
            ->with(['payment', 'items.product.author', 'items.author', 'user'])
            ->where('number', $reference)
            ->first();

        if (! $order) {
            return null;
        }

        if (auth()->check() && auth()->id() !== $order->user_id) {
            return redirect()
                ->route('dashboard')
                ->with('error', __('Це замовлення належить іншому акаунту.'));
        }

        $status = strtolower((string) $request->query('status'));
        $isPaidReturn = in_array($status, ['paid', 'success', 'completed'], true);
        $isFailedReturn = in_array($status, ['failed', 'fail', 'error', 'declined', 'canceled', 'cancelled'], true);

        if ($isPaidReturn) {
            if ($order->payment && $order->payment->status !== 'paid') {
                app(AifoPaymentService::class)->markPaid($order->payment, [
                    'aifo_return' => $request->query(),
                    'marked_paid_from_return' => true,
                ]);
                app(AuditLogger::class)->record('payment.return_paid', $order, [
                    'order_id' => $order->id,
                    'reference' => $reference,
                ]);

                Mail::to($order->user)->queue(new PurchaseReceiptMail($order));
                foreach ($order->items as $item) {
                    Mail::to($item->author)->queue(new SaleNotificationMail($order, $item->author));
                }
            }

            return redirect()
                ->route('checkout.success', $order)
                ->with('status', __('Оплату успішно прийнято. Файли моделі вже доступні для завантаження.'));
        }

        if ($isFailedReturn) {
            $this->markOrderPaymentFailed($order, $request);

            return redirect()
                ->route('checkout.failed', $order)
                ->with('error', __('Оплата не пройшла. Ви можете повторити покупку або повернутися до моделі.'));
        }

        return redirect()
            ->route('checkout.failed', $order)
            ->with('error', __('Не вдалося підтвердити статус платежу. Якщо кошти списані, зачекайте webhook AIFO або зверніться до підтримки.'));
    }

    private function markOrderPaymentFailed(Order $order, Request $request): void
    {
        if ($order->payment && $order->payment->status !== 'paid') {
            $order->payment->update([
                'status' => 'failed',
                'payload' => array_merge($order->payment->payload ?? [], [
                    'aifo_return' => $request->query(),
                    'marked_failed_from_return' => true,
                ]),
            ]);
        }

        if ($order->status !== 'paid') {
            $order->update(['status' => 'failed']);
            app(AuditLogger::class)->record('payment.return_failed', $order, [
                'order_id' => $order->id,
                'query' => $request->query(),
            ]);
        }

        app(AccountBalanceService::class)->voidOrderDebit($order);
    }
}
