<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PromoCode;
use App\Models\PromoCodeRedemption;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PromoCodeService
{
    public function find(string $code): ?PromoCode
    {
        return PromoCode::query()->whereRaw('LOWER(code) = ?', [strtolower(trim($code))])->first();
    }

    /**
     * Validate code for the given user/amount; returns discount or null if invalid.
     */
    public function validate(string $code, User $user, float $amount): ?array
    {
        $promo = $this->find($code);
        if (! $promo || ! $promo->isUsable($amount)) {
            return null;
        }

        // Prevent the same user using the same code twice.
        $alreadyUsed = PromoCodeRedemption::query()
            ->where('promo_code_id', $promo->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($alreadyUsed) {
            return null;
        }

        $discount = $promo->calculateDiscount($amount);

        return [
            'promo' => $promo,
            'discount' => $discount,
        ];
    }

    /**
     * Persist redemption + decrement counters atomically.
     */
    public function redeem(PromoCode $promo, User $user, ?Order $order, float $discount): PromoCodeRedemption
    {
        return DB::transaction(function () use ($promo, $user, $order, $discount) {
            $promo->increment('used_count');
            return PromoCodeRedemption::create([
                'promo_code_id' => $promo->id,
                'user_id' => $user->id,
                'order_id' => $order?->id,
                'discount_amount' => $discount,
            ]);
        });
    }
}
