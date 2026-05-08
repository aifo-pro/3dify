<?php

namespace App\Services;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Tip;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AuthorAnalytics
{
    /**
     * Time series of daily views and sales for the last $days days.
     *
     * @return array{labels: list<string>, views: list<int>, sales: list<int>, revenue: list<float>}
     */
    public function timeSeries(User $author, int $days = 30): array
    {
        $start = Carbon::today()->subDays($days - 1);
        $end = Carbon::today();
        $productIds = $author->products()->pluck('id');

        // Views per day.
        $viewsByDate = DB::table('product_view_stats')
            ->whereIn('product_id', $productIds)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->select('date', DB::raw('SUM(count) as total'))
            ->groupBy('date')
            ->pluck('total', 'date');

        // Sales (count + revenue gross) from paid orders.
        $sales = OrderItem::query()
            ->where('author_id', $author->id)
            ->whereHas('order', fn ($q) => $q->where('status', 'paid')->whereBetween('created_at', [$start, $end->copy()->endOfDay()]))
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->select(DB::raw('DATE(orders.created_at) as d'), DB::raw('COUNT(*) as cnt'), DB::raw('SUM(order_items.price) as rev'))
            ->groupBy('d')
            ->get()
            ->keyBy('d');

        $labels = [];
        $views = [];
        $salesArr = [];
        $revenue = [];

        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $key = $d->toDateString();
            $labels[] = $d->format('d.m');
            $views[] = (int) ($viewsByDate[$key] ?? 0);
            $row = $sales->get($key);
            $salesArr[] = (int) ($row->cnt ?? 0);
            $revenue[] = (float) ($row->rev ?? 0);
        }

        return [
            'labels' => $labels,
            'views' => $views,
            'sales' => $salesArr,
            'revenue' => $revenue,
        ];
    }

    /**
     * Aggregate KPIs for the author over the period vs previous equal period.
     *
     * @return array<string, array{value: int|float, delta: float|null}>
     */
    public function kpis(User $author, int $days = 30): array
    {
        $now = Carbon::now();
        $cur = [$now->copy()->subDays($days - 1)->startOfDay(), $now->copy()->endOfDay()];
        $prev = [$now->copy()->subDays(2 * $days - 1)->startOfDay(), $now->copy()->subDays($days)->endOfDay()];

        $productIds = $author->products()->pluck('id');

        $viewsCur = (int) DB::table('product_view_stats')->whereIn('product_id', $productIds)
            ->whereBetween('date', [$cur[0]->toDateString(), $cur[1]->toDateString()])->sum('count');
        $viewsPrev = (int) DB::table('product_view_stats')->whereIn('product_id', $productIds)
            ->whereBetween('date', [$prev[0]->toDateString(), $prev[1]->toDateString()])->sum('count');

        $salesCur = OrderItem::query()->where('author_id', $author->id)
            ->whereHas('order', fn ($q) => $q->where('status', 'paid')->whereBetween('created_at', $cur))->count();
        $salesPrev = OrderItem::query()->where('author_id', $author->id)
            ->whereHas('order', fn ($q) => $q->where('status', 'paid')->whereBetween('created_at', $prev))->count();

        $revCur = (float) OrderItem::query()->where('author_id', $author->id)
            ->whereHas('order', fn ($q) => $q->where('status', 'paid')->whereBetween('created_at', $cur))->sum('price');
        $revPrev = (float) OrderItem::query()->where('author_id', $author->id)
            ->whereHas('order', fn ($q) => $q->where('status', 'paid')->whereBetween('created_at', $prev))->sum('price');

        $tipsCur = (float) Tip::query()->where('author_id', $author->id)
            ->where('status', Tip::STATUS_PAID)->whereBetween('created_at', $cur)->sum('amount');

        $convCur = $viewsCur > 0 ? round(($salesCur / $viewsCur) * 100, 2) : 0.0;
        $convPrev = $viewsPrev > 0 ? round(($salesPrev / $viewsPrev) * 100, 2) : 0.0;

        return [
            'views' => ['value' => $viewsCur, 'delta' => $this->delta($viewsCur, $viewsPrev)],
            'sales' => ['value' => $salesCur, 'delta' => $this->delta($salesCur, $salesPrev)],
            'revenue' => ['value' => round($revCur, 2), 'delta' => $this->delta($revCur, $revPrev)],
            'tips' => ['value' => round($tipsCur, 2), 'delta' => null],
            'conversion' => ['value' => $convCur, 'delta' => $this->delta($convCur, $convPrev)],
        ];
    }

    public function topProducts(User $author, int $days = 30, int $limit = 5): Collection
    {
        $start = Carbon::now()->subDays($days)->toDateString();

        $views = DB::table('product_view_stats')
            ->whereIn('product_id', $author->products()->pluck('id'))
            ->where('date', '>=', $start)
            ->select('product_id', DB::raw('SUM(count) as v'))
            ->groupBy('product_id')
            ->pluck('v', 'product_id');

        $sales = OrderItem::query()
            ->where('author_id', $author->id)
            ->whereHas('order', fn ($q) => $q->where('status', 'paid')->where('created_at', '>=', $start))
            ->select('product_id', DB::raw('COUNT(*) as s'), DB::raw('SUM(price) as r'))
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');

        return Product::query()
            ->whereIn('id', $author->products()->pluck('id'))
            ->get()
            ->map(function (Product $p) use ($views, $sales) {
                $row = $sales->get($p->id);
                return (object) [
                    'product' => $p,
                    'views' => (int) ($views[$p->id] ?? 0),
                    'sales' => (int) ($row->s ?? 0),
                    'revenue' => (float) ($row->r ?? 0),
                    'conversion' => ($views[$p->id] ?? 0) > 0 ? round((($row->s ?? 0) / $views[$p->id]) * 100, 2) : 0.0,
                ];
            })
            ->sortByDesc(fn ($x) => $x->revenue ?: $x->views)
            ->take($limit)
            ->values();
    }

    private function delta(float $cur, float $prev): ?float
    {
        if ($prev == 0.0) {
            return $cur > 0 ? 100.0 : null;
        }
        return round((($cur - $prev) / $prev) * 100, 1);
    }
}
