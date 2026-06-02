<?php

namespace App\Policies;

use App\Models\CustomOrder;
use App\Models\User;

class CustomOrderPolicy
{
    public function view(User $user, CustomOrder $order): bool
    {
        return $order->isParticipant($user) || $user->canModerate();
    }

    public function sendResult(User $user, CustomOrder $order): bool
    {
        return $order->isModelCreation()
            && ($user->id === $order->author_id || $user->canModerate())
            && in_array($order->status, [CustomOrder::STATUS_PAID, CustomOrder::STATUS_IN_PROGRESS, CustomOrder::STATUS_DELIVERED], true);
    }

    public function ship(User $user, CustomOrder $order): bool
    {
        return $order->isPrintService()
            && ($user->id === $order->author_id || $user->canModerate())
            && in_array($order->status, [CustomOrder::STATUS_PAID, CustomOrder::STATUS_IN_PROGRESS], true);
    }

    public function complete(User $user, CustomOrder $order): bool
    {
        return $user->id === $order->buyer_id && $order->status === CustomOrder::STATUS_DELIVERED;
    }
}
