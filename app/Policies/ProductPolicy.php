<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Product $product): bool
    {
        return $product->status === 'published' || $user?->id === $product->user_id || $user?->canModerate();
    }

    public function create(User $user): bool
    {
        return ! $user->is_suspended;
    }

    public function update(User $user, Product $product): bool
    {
        return $user->id === $product->user_id || $user->canModerate();
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->id === $product->user_id || $user->isAdmin();
    }

    public function moderate(User $user): bool
    {
        return $user->canModerate();
    }
}
