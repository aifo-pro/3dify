<?php

namespace App\Services;

use App\Models\OrderItem;
use App\Models\Payout;
use App\Models\Tip;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PayoutService
{
    /**
     * Platform commission applied to each sale before crediting author balance.
     * Can later be moved to settings.
     */
    public const COMMISSION_PERCENT = 15;

    public const MIN_PAYOUT_AMOUNT = 20.00;

    /**
     * Total earnings (lifetime, after commission) from paid orders.
     */
    public function totalEarnings(User $author, string $currency = 'EUR'): float
    {
        $gross = (float) OrderItem::query()
            ->where('author_id', $author->id)
            ->where('currency', $currency)
            ->whereHas('order', fn ($q) => $q->where('status', 'paid'))
            ->sum('price');

        $tips = (float) Tip::query()
            ->where('author_id', $author->id)
            ->where('currency', $currency)
            ->where('status', Tip::STATUS_PAID)
            ->sum('amount');

        return round($gross * (1 - self::COMMISSION_PERCENT / 100) + $tips, 2);
    }

    /**
     * Sum of approved/paid/pending payouts (everything that is reserved or paid out).
     */
    public function reservedTotal(User $author, string $currency = 'EUR'): float
    {
        return (float) $author->payouts()
            ->where('currency', $currency)
            ->whereIn('status', ['pending', 'approved', 'paid'])
            ->sum('amount');
    }

    /**
     * Available balance ready to be requested.
     */
    public function availableBalance(User $author, string $currency = 'EUR'): float
    {
        return round(max(0, $this->totalEarnings($author, $currency) - $this->reservedTotal($author, $currency)), 2);
    }

    /**
     * Sales count from paid orders.
     */
    public function salesCount(User $author): int
    {
        return OrderItem::query()
            ->where('author_id', $author->id)
            ->whereHas('order', fn ($q) => $q->where('status', 'paid'))
            ->count();
    }

    public function requestPayout(User $author, float $amount, string $method, ?string $details = null, string $currency = 'EUR'): Payout
    {
        return DB::transaction(function () use ($author, $amount, $method, $details, $currency) {
            return Payout::create([
                'author_id' => $author->id,
                'amount' => $amount,
                'currency' => $currency,
                'status' => 'pending',
                'method' => $method,
                'details' => $details,
                'requested_at' => now(),
            ]);
        });
    }
}
