<?php

namespace App\Services;

use App\Models\Download;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductAccessEvent;
use App\Models\RefundRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class RefundEvidenceService
{
    public function forRequest(RefundRequest $refundRequest): array
    {
        $order = $refundRequest->order;
        if (! $order) {
            return [
                'risk' => 'unknown',
                'summary' => __('Замовлення не знайдено.'),
                'items' => collect(),
            ];
        }

        $items = $order->items
            ->map(fn ($item) => $this->forProduct($refundRequest, $order, $item->product))
            ->filter()
            ->values();

        $downloadCount = $items->sum('download_count');
        $slicerCount = $items->sum('slicer_count');
        $printProfileCount = $items->sum('print_profile_count');
        $hasUsage = $downloadCount + $slicerCount + $printProfileCount > 0;

        return [
            'risk' => $hasUsage ? 'high' : 'low',
            'summary' => $hasUsage
                ? __('До заявки вже був доступ до файлів: :downloads скачувань, :slicer відкриттів у slicer, :profiles профілів друку.', [
                    'downloads' => $downloadCount,
                    'slicer' => $slicerCount,
                    'profiles' => $printProfileCount,
                ])
                : __('До заявки немає зафіксованого скачування або відкриття у slicer.'),
            'items' => $items,
        ];
    }

    private function forProduct(RefundRequest $refundRequest, Order $order, ?Product $product): ?array
    {
        if (! $product) {
            return null;
        }

        $from = $order->paid_at ?: $order->created_at;
        $to = $refundRequest->created_at ?: now();
        $downloads = $this->downloads($order, $product, $from, $to);
        $events = $this->events($order, $product, $from, $to);

        $slicerEvents = $events->where('event', ProductAccessEvent::EVENT_SLICER_OPEN);
        $printProfileEvents = $events->where('event', ProductAccessEvent::EVENT_PRINT_PROFILE_DOWNLOAD);
        $modalEvents = $events->where('event', ProductAccessEvent::EVENT_DOWNLOAD_MODAL_OPEN);

        $lastActivityAt = collect([
            $downloads->max('downloaded_at'),
            $events->max('occurred_at'),
        ])->filter()->max();

        return [
            'product' => $product,
            'download_count' => $downloads->count(),
            'download_files' => $downloads
                ->groupBy('model_file_id')
                ->map(function (Collection $rows) {
                    $first = $rows->first();

                    return [
                        'name' => $first?->file?->original_name ?: __('Файл видалено'),
                        'count' => $rows->count(),
                        'last_at' => $rows->max('downloaded_at'),
                        'ips' => $rows->pluck('ip_address')->filter()->unique()->take(3)->values(),
                    ];
                })
                ->values(),
            'slicer_count' => $slicerEvents->count(),
            'slicer_targets' => $slicerEvents->pluck('target')->filter()->countBy(),
            'print_profile_count' => $printProfileEvents->count(),
            'modal_open_count' => $modalEvents->count(),
            'last_activity_at' => $lastActivityAt,
            'events' => $events->take(8)->values(),
        ];
    }

    private function downloads(Order $order, Product $product, mixed $from, mixed $to): Collection
    {
        if (! Schema::hasTable('downloads')) {
            return collect();
        }

        return Download::query()
            ->with('file')
            ->where('user_id', $order->user_id)
            ->where('product_id', $product->id)
            ->when($from, fn ($query) => $query->where('downloaded_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('downloaded_at', '<=', $to))
            ->latest('downloaded_at')
            ->get();
    }

    private function events(Order $order, Product $product, mixed $from, mixed $to): Collection
    {
        if (! Schema::hasTable('product_access_events')) {
            return collect();
        }

        return ProductAccessEvent::query()
            ->with('file')
            ->where('user_id', $order->user_id)
            ->where('product_id', $product->id)
            ->when($from, fn ($query) => $query->where('occurred_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('occurred_at', '<=', $to))
            ->latest('occurred_at')
            ->get();
    }
}
