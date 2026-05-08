<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductMake;
use App\Models\ProductReview;
use App\Models\Tip;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $range = (int) $request->input('range', 30);
        $range = in_array($range, [7, 30, 90, 365]) ? $range : 30;
        $from = now()->copy()->subDays($range)->startOfDay();
        $to = now()->copy()->endOfDay();

        $kpis = [
            'gmv' => (float) Order::where('status', 'paid')->whereBetween('paid_at', [$from, $to])->sum('total'),
            'orders' => Order::where('status', 'paid')->whereBetween('paid_at', [$from, $to])->count(),
            'aov' => 0,
            'new_users' => User::whereBetween('created_at', [$from, $to])->count(),
            'new_authors' => User::where('role', 'author')->whereBetween('created_at', [$from, $to])->count(),
            'new_products' => Product::whereBetween('created_at', [$from, $to])->count(),
            'reviews' => ProductReview::whereBetween('created_at', [$from, $to])->count(),
            'makes' => ProductMake::whereBetween('created_at', [$from, $to])->count(),
            'tips' => (float) Tip::where('status', 'paid')->whereBetween('created_at', [$from, $to])->sum('amount'),
            'subscribers' => NewsletterSubscriber::whereNull('unsubscribed_at')->count(),
        ];
        $kpis['aov'] = $kpis['orders'] > 0 ? round($kpis['gmv'] / $kpis['orders'], 2) : 0;

        // Time series
        $sales = $this->daily(Order::query()->where('status', 'paid')->whereBetween('paid_at', [$from, $to]), 'paid_at', 'total', $from, $to);
        $signups = $this->daily(User::query()->whereBetween('created_at', [$from, $to]), 'created_at', null, $from, $to);
        $publishes = $this->daily(Product::query()->whereBetween('created_at', [$from, $to]), 'created_at', null, $from, $to);

        $views = [];
        if (Schema::hasTable('product_view_stats')) {
            $views = DB::table('product_view_stats')
                ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
                ->selectRaw('date as d, SUM(count) as v')
                ->groupBy('date')->orderBy('date')->pluck('v', 'd')->toArray();
        }

        $topProductIds = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', 'paid')
            ->whereBetween('orders.paid_at', [$from, $to])
            ->selectRaw('product_id, COUNT(*) as sales')
            ->groupBy('product_id')
            ->orderByDesc('sales')
            ->limit(10)
            ->pluck('sales', 'product_id');

        $topProducts = Product::whereIn('id', $topProductIds->keys())->get()
            ->each(fn ($p) => $p->sales = $topProductIds[$p->id] ?? 0)
            ->sortByDesc('sales')->values();

        $topAuthors = User::query()
            ->where('role', 'author')
            ->withCount(['products as products_in_period' => fn ($w) => $w->whereBetween('created_at', [$from, $to])])
            ->orderByDesc('products_in_period')->limit(10)->get();

        return view('admin.analytics.index', compact('range', 'kpis', 'sales', 'signups', 'publishes', 'views', 'topProducts', 'topAuthors'));
    }

    private function daily($query, string $dateColumn, ?string $sumColumn, Carbon $from, Carbon $to): array
    {
        $rows = (clone $query)
            ->selectRaw('DATE('.$dateColumn.') as d, '.($sumColumn ? 'SUM('.$sumColumn.') as v' : 'COUNT(*) as v'))
            ->groupBy('d')->orderBy('d')->pluck('v', 'd')->toArray();

        $period = [];
        for ($cur = $from->copy(); $cur->lte($to); $cur->addDay()) {
            $key = $cur->toDateString();
            $period[$key] = (float) ($rows[$key] ?? 0);
        }
        return $period;
    }
}
