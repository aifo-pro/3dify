<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
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
     *
     * When a $product is supplied, an author-scoped promo code is only valid for
     * that author's own products. System promo codes (author_id null) apply to any.
     */
    public function validate(string $code, User $user, float $amount, ?Product $product = null): ?array
    {
        $promo = $this->find($code);
        if (! $promo || ! $promo->isUsable($amount)) {
            return null;
        }

        // Author promo codes only work on that author's products.
        if ($promo->author_id !== null) {
            if (! $product || (int) $product->user_id !== (int) $promo->author_id) {
                return null;
            }
            // An author must not discount their own purchase via their own code.
            if ((int) $user->id === (int) $promo->author_id) {
                return null;
            }
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
