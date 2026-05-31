<?php

namespace App\Services;

use App\Models\Product;
use App\Models\RefundRequest;
use App\Models\User;

class MarketplaceAccess
{
    public function canDownload(?User $user, Product $product): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->is_suspended) {
            return false;
        }

        if ($product->is_free || $product->user_id === $user->id || $user->canModerate()) {
            return true;
        }

        return $user->orders()
            ->where('status', 'paid')
            ->whereHas('items', fn ($query) => $query->where('product_id', $product->id))
            ->whereDoesntHave('refundRequests', function ($query) {
                $query->whereIn('status', [RefundRequest::STATUS_APPROVED, RefundRequest::STATUS_REFUNDED]);
            })
            ->exists();
    }
}
