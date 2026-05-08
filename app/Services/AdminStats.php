<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminStats
{
    /** Allowed period keys → number of days. */
    public const PERIODS = [
        '7d' => 7,
        '30d' => 30,
        '90d' => 90,
    ];

    public function __construct(
        protected ?string $period = null
    ) {
        if (! $this->period || ! array_key_exists($this->period, self::PERIODS)) {
            $this->period = '30d';
        }
    }

    public static function for(?string $period = null): self
    {
        return new self($period);
    }

    public function period(): string
    {
        return $this->period;
    }

    public function days(): int
    {
        return self::PERIODS[$this->period];
    }

    public function rangeStart(): CarbonImmutable
    {
        return CarbonImmutable::now()->subDays($this->days() - 1)->startOfDay();
    }

    public function previousRangeStart(): CarbonImmutable
    {
        return $this->rangeStart()->subDays($this->days());
    }

    public function previousRangeEnd(): CarbonImmutable
    {
        return $this->rangeStart()->subSecond();
    }

    /**
     * Stat block: current value, delta % vs previous period, sparkline points.
     *
     * @return array{value:int, delta:?float, trend:string, sparkline:array<int,int>, total:int}
     */
    public function metric(string $table, string $dateColumn = 'created_at', ?\Closure $applyFilter = null): array
    {
        if (! Schema::hasTable($table)) {
            return ['value' => 0, 'delta' => null, 'trend' => 'flat', 'sparkline' => array_fill(0, $this->days(), 0), 'total' => 0];
        }

        $start = $this->rangeStart();
        $prevStart = $this->previousRangeStart();
        $prevEnd = $this->previousRangeEnd();

        $current = DB::table($table)->where($dateColumn, '>=', $start);
        $previous = DB::table($table)->whereBetween($dateColumn, [$prevStart, $prevEnd]);
        $totalQuery = DB::table($table);

        if ($applyFilter) {
            $applyFilter($current);
            $applyFilter($previous);
            $applyFilter($totalQuery);
        }

        $value = (int) $current->count();
        $previousValue = (int) $previous->count();
        $total = (int) $totalQuery->count();

        $delta = null;
        if ($previousValue > 0) {
            $delta = round((($value - $previousValue) / $previousValue) * 100, 1);
        } elseif ($value > 0) {
            $delta = 100.0;
        }

        $trend = $delta === null ? 'flat' : ($delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'flat'));

        return [
            'value' => $value,
            'delta' => $delta,
            'trend' => $trend,
            'sparkline' => $this->dailyCounts($table, $dateColumn, $applyFilter),
            'total' => $total,
        ];
    }

    /**
     * Daily counts for the current period (for sparklines).
     *
     * @return array<int,int>
     */
    public function dailyCounts(string $table, string $dateColumn = 'created_at', ?\Closure $applyFilter = null): array
    {
        if (! Schema::hasTable($table)) {
            return array_fill(0, $this->days(), 0);
        }

        $start = $this->rangeStart();
        $query = DB::table($table)
            ->selectRaw('DATE('.$dateColumn.') as day, COUNT(*) as count')
            ->where($dateColumn, '>=', $start)
            ->groupBy('day');

        if ($applyFilter) {
            $applyFilter($query);
        }

        $rows = $query->pluck('count', 'day');

        return $this->fillTimeline($rows);
    }

    /**
     * Sum of a numeric column per day for the current period.
     *
     * @return array<int,float>
     */
    public function dailySums(string $table, string $sumColumn, string $dateColumn = 'created_at', ?\Closure $applyFilter = null): array
    {
        if (! Schema::hasTable($table)) {
            return array_fill(0, $this->days(), 0);
        }

        $start = $this->rangeStart();
        $query = DB::table($table)
            ->selectRaw('DATE('.$dateColumn.') as day, COALESCE(SUM('.$sumColumn.'), 0) as total')
            ->where($dateColumn, '>=', $start)
            ->groupBy('day');

        if ($applyFilter) {
            $applyFilter($query);
        }

        $rows = $query->pluck('total', 'day');

        return array_map(static fn ($v) => (float) $v, $this->fillTimeline($rows));
    }

    /**
     * @param  Collection<string,int|float>  $rows
     * @return array<int,int|float>
     */
    protected function fillTimeline(Collection $rows): array
    {
        $start = $this->rangeStart();
        $out = [];
        for ($i = 0; $i < $this->days(); $i++) {
            $key = $start->addDays($i)->toDateString();
            $out[] = $rows->get($key, 0);
        }

        return $out;
    }

    /** @return array<string,float> currency => total */
    public function revenueByCurrency(): array
    {
        if (! Schema::hasTable('payments')) {
            return [];
        }

        return Payment::query()
            ->where('status', 'paid')
            ->where('created_at', '>=', $this->rangeStart())
            ->selectRaw('currency, SUM(amount) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency')
            ->map(static fn ($v) => (float) $v)
            ->all();
    }

    /** Revenue total over all time grouped by currency. @return array<string,float> */
    public function revenueByCurrencyTotal(): array
    {
        if (! Schema::hasTable('payments')) {
            return [];
        }

        return Payment::query()
            ->where('status', 'paid')
            ->selectRaw('currency, SUM(amount) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency')
            ->map(static fn ($v) => (float) $v)
            ->all();
    }

    /** Average order value by currency in current period. @return array<string,float> */
    public function aovByCurrency(): array
    {
        if (! Schema::hasTable('orders')) {
            return [];
        }

        return Order::query()
            ->whereNotNull('paid_at')
            ->where('paid_at', '>=', $this->rangeStart())
            ->selectRaw('currency, AVG(total) as avg')
            ->groupBy('currency')
            ->pluck('avg', 'currency')
            ->map(static fn ($v) => round((float) $v, 2))
            ->all();
    }

    /** Top authors by published products count. */
    public function topAuthors(int $limit = 5): Collection
    {
        if (! Schema::hasTable('products')) {
            return collect();
        }

        return User::query()
            ->whereHas('products', fn ($q) => $q->where('status', 'published'))
            ->withCount(['products as published_count' => fn ($q) => $q->where('status', 'published')])
            ->withCount(['products as total_count'])
            ->orderByDesc('published_count')
            ->limit($limit)
            ->get();
    }

    /** Top categories by product count. */
    public function topCategories(int $limit = 5): Collection
    {
        if (! Schema::hasTable('categories')) {
            return collect();
        }

        return \App\Models\Category::query()
            ->withCount(['products'])
            ->orderByDesc('products_count')
            ->limit($limit)
            ->get();
    }

    /** Mixed activity timeline (registrations, publications, orders). */
    public function activity(int $limit = 10): Collection
    {
        $events = collect();

        if (Schema::hasTable('users')) {
            User::query()
                ->latest('created_at')
                ->limit($limit)
                ->get(['id', 'name', 'email', 'created_at'])
                ->each(function ($u) use ($events) {
                    $events->push([
                        'type' => 'user',
                        'icon' => 'user',
                        'tint' => 'emerald',
                        'title' => __('Новий користувач'),
                        'description' => $u->name.' ('.$u->email.')',
                        'at' => Carbon::parse($u->created_at),
                    ]);
                });
        }

        if (Schema::hasTable('products')) {
            Product::query()
                ->whereNotNull('published_at')
                ->latest('published_at')
                ->limit($limit)
                ->with('author:id,name')
                ->get(['id', 'user_id', 'title', 'published_at'])
                ->each(function ($p) use ($events) {
                    $events->push([
                        'type' => 'product',
                        'icon' => 'box',
                        'tint' => 'sky',
                        'title' => __('Опубліковано модель'),
                        'description' => $p->localized('title').' · '.($p->author?->name ?? '—'),
                        'at' => Carbon::parse($p->published_at),
                    ]);
                });
        }

        if (Schema::hasTable('orders')) {
            Order::query()
                ->whereNotNull('paid_at')
                ->latest('paid_at')
                ->limit($limit)
                ->with('user:id,name,email')
                ->get(['id', 'number', 'user_id', 'total', 'currency', 'paid_at'])
                ->each(function ($o) use ($events) {
                    $events->push([
                        'type' => 'order',
                        'icon' => 'bag',
                        'tint' => 'violet',
                        'title' => __('Оплачено замовлення'),
                        'description' => '#'.$o->number.' · '.($o->user?->name ?? '—').' · '.$o->total.' '.$o->currency,
                        'at' => Carbon::parse($o->paid_at),
                    ]);
                });
        }

        return $events->sortByDesc('at')->values()->take($limit);
    }

    /** Pending moderation queue. */
    public function moderationQueue(int $limit = 5): Collection
    {
        if (! Schema::hasTable('products')) {
            return collect();
        }

        return Product::query()
            ->where('status', 'pending')
            ->with('author:id,name,email')
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function pendingCount(): int
    {
        if (! Schema::hasTable('products')) {
            return 0;
        }

        return Product::where('status', 'pending')->count();
    }

    public function failedJobsCount(): int
    {
        return Schema::hasTable('failed_jobs') ? (int) DB::table('failed_jobs')->count() : 0;
    }

    public function queuedJobsCount(): int
    {
        return Schema::hasTable('jobs') ? (int) DB::table('jobs')->count() : 0;
    }

    /** Storage size in bytes for a public/private disk root. */
    public function storageBytes(string $disk): int
    {
        try {
            $root = storage_path('app/'.$disk);
            if (! is_dir($root)) {
                return 0;
            }

            $size = 0;
            $rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
            foreach ($rii as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            }

            return $size;
        } catch (\Throwable) {
            return 0;
        }
    }

    public static function formatBytes(int $bytes, int $precision = 1): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $pow = min((int) floor(log($bytes, 1024)), count($units) - 1);

        return round($bytes / (1024 ** $pow), $precision).' '.$units[$pow];
    }
}
