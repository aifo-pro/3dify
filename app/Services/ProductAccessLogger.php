<?php

namespace App\Services;

use App\Models\ModelFile;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductAccessEvent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ProductAccessLogger
{
    public function log(
        Product $product,
        ?User $user,
        string $event,
        ?ModelFile $file = null,
        ?Request $request = null,
        ?string $target = null,
        array $metadata = [],
        ?Order $order = null,
    ): void {
        if (! Schema::hasTable('product_access_events')) {
            return;
        }

        $request ??= request();
        $order ??= $user ? $this->resolveOrder($user, $product) : null;

        ProductAccessEvent::query()->create([
            'user_id' => $user?->id,
            'product_id' => $product->id,
            'model_file_id' => $file?->id,
            'order_id' => $order?->id,
            'event' => $event,
            'target' => $target,
            'ip_address' => $request?->ip(),
            'user_agent' => str($request?->userAgent() ?? '')->limit(255, '')->toString(),
            'metadata' => $metadata ?: null,
            'occurred_at' => now(),
        ]);
    }

    private function resolveOrder(User $user, Product $product): ?Order
    {
        return $user->orders()
            ->whereIn('status', [Order::STATUS_PAID, Order::STATUS_REFUNDED])
            ->whereHas('items', fn ($query) => $query->where('product_id', $product->id))
            ->latest('paid_at')
            ->latest('id')
            ->first();
    }
}
