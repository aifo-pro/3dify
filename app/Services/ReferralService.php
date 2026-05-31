<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Str;

class ReferralService
{
    public const REWARD_PERCENT = 5; // 5% of referred user's first purchase

    public function getOrCreateCode(User $user): string
    {
        if (! $user->referral_code) {
            $code = $this->generateUniqueCode();
            $user->update(['referral_code' => $code]);
        }

        return $user->referral_code;
    }

    public function referralUrl(User $user): string
    {
        return route('home').'?ref='.$this->getOrCreateCode($user);
    }

    public function resolveReferrer(string $code): ?User
    {
        return User::where('referral_code', $code)->first();
    }

    public function creditReferrer(Order $order): void
    {
        $buyer = $order->user;
        if (! $buyer?->referred_by) {
            return;
        }

        // Only credit on the FIRST paid order of the referred user
        $prevPaidCount = Order::where('user_id', $buyer->id)
            ->where('status', 'paid')
            ->where('id', '!=', $order->id)
            ->count();

        if ($prevPaidCount > 0) {
            return;
        }

        $amount = round((float) $order->total * self::REWARD_PERCENT / 100, 2);
        if ($amount <= 0) {
            return;
        }

        $referrer = User::find($buyer->referred_by);
        if (! $referrer) {
            return;
        }

        \DB::table('referral_rewards')->insert([
            'referrer_id' => $referrer->id,
            'referred_id' => $buyer->id,
            'order_id'    => $order->id,
            'amount'      => $amount,
            'currency'    => $order->currency,
            'status'      => 'credited',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        // Add to referrer's account balance
        app(AccountBalanceService::class)->credit(
            $referrer,
            $amount,
            $order->currency,
            "Referral reward for order #{$order->number}"
        );
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (User::where('referral_code', $code)->exists());

        return $code;
    }
}
