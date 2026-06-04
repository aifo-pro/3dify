<?php

namespace App\Console\Commands;

use App\Models\CustomOrder;
use App\Models\CustomOrderShipment;
use App\Services\CustomOrderService;
use App\Services\ParcelTrackingService;
use Illuminate\Console\Command;

class TrackCustomOrderShipments extends Command
{
    protected $signature = 'custom-orders:track {--limit=100 : Max shipments to poll per run}';

    protected $description = 'Poll Nova Poshta / Ukrposhta for parcel statuses and auto-complete delivered orders.';

    public function handle(ParcelTrackingService $tracker, CustomOrderService $orders): int
    {
        $limit = (int) $this->option('limit');
        $tracked = 0;
        $completed = 0;

        // 1) Update tracking for shipped orders that are not yet delivered/failed.
        CustomOrderShipment::query()
            ->whereNotNull('tracking_number')
            ->whereNotIn('status', [
                ParcelTrackingService::STATUS_DELIVERED,
                ParcelTrackingService::STATUS_FAILED,
            ])
            ->whereHas('customOrder', fn ($q) => $q->where('status', CustomOrder::STATUS_SHIPPED))
            ->with('customOrder.author', 'customOrder.buyer')
            ->limit($limit)
            ->get()
            ->each(function (CustomOrderShipment $shipment) use ($tracker, $orders, &$tracked) {
                try {
                    $update = $tracker->track($shipment);
                    if ($update && $orders->applyTracking($shipment, $update)) {
                        $tracked++;
                        $this->info("Shipment #{$shipment->id} → {$update['status']}");
                    }
                } catch (\Throwable $e) {
                    $this->warn("Track failed #{$shipment->id}: {$e->getMessage()}");
                }
            });

        // 2) Auto-complete delivered orders whose grace window elapsed (no open dispute).
        CustomOrder::query()
            ->where('status', CustomOrder::STATUS_DELIVERED)
            ->whereNotNull('auto_complete_at')
            ->where('auto_complete_at', '<=', now())
            ->with(['author', 'buyer'])
            ->limit($limit)
            ->get()
            ->each(function (CustomOrder $order) use ($orders, &$completed) {
                try {
                    if ($orders->autoComplete($order)) {
                        $completed++;
                        $this->info("Auto-completed {$order->number}");
                    }
                } catch (\Throwable $e) {
                    $this->warn("Auto-complete failed {$order->number}: {$e->getMessage()}");
                }
            });

        $this->info("Done. Tracked: {$tracked}, auto-completed: {$completed}.");

        return self::SUCCESS;
    }
}
