<?php

namespace App\Services;

use App\Models\AccountBalanceTransaction;
use App\Models\Order;
use App\Models\RefundRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AccountBalanceService
{
    public const DEFAULT_CURRENCY = 'UAH';

    public function availableBalance(User $user, string $currency = self::DEFAULT_CURRENCY): float
    {
        $settledCredits = (float) $user->accountBalanceTransactions()
            ->where('currency', $currency)
            ->where('type', AccountBalanceTransaction::TYPE_CREDIT)
            ->where('status', AccountBalanceTransaction::STATUS_SETTLED)
            ->sum('amount');

        $lockedOrSpentDebits = (float) $user->accountBalanceTransactions()
            ->where('currency', $currency)
            ->where('type', AccountBalanceTransaction::TYPE_DEBIT)
            ->whereIn('status', [AccountBalanceTransaction::STATUS_PENDING, AccountBalanceTransaction::STATUS_SETTLED])
            ->sum('amount');

        return round(max(0, $settledCredits - $lockedOrSpentDebits), 2);
    }

    public function creditRefund(RefundRequest $refundRequest): ?AccountBalanceTransaction
    {
        $order = $refundRequest->order;
        $amount = $order ? (float) $order->items()->sum('price') : 0.0;
        if (! $order || ! $refundRequest->user_id || $amount <= 0) {
            return null;
        }

        return DB::transaction(function () use ($refundRequest, $order, $amount) {
            $existing = AccountBalanceTransaction::query()
                ->where('refund_request_id', $refundRequest->id)
                ->where('type', AccountBalanceTransaction::TYPE_CREDIT)
                ->first();

            if ($existing) {
                return $existing;
            }

            return AccountBalanceTransaction::create([
                'user_id' => $refundRequest->user_id,
                'order_id' => $order->id,
                'refund_request_id' => $refundRequest->id,
                'type' => AccountBalanceTransaction::TYPE_CREDIT,
                'status' => AccountBalanceTransaction::STATUS_SETTLED,
                'amount' => round($amount, 2),
                'currency' => $order->currency ?: self::DEFAULT_CURRENCY,
                'description' => __('Повернення за замовлення :number', ['number' => $order->number]),
                'metadata' => ['source' => 'refund_request'],
            ]);
        });
    }

    public function reserveForOrder(User $user, Order $order, float $amount, string $currency = self::DEFAULT_CURRENCY): ?AccountBalanceTransaction
    {
        $amount = round(max(0, $amount), 2);
        if ($amount <= 0) {
            return null;
        }

        return DB::transaction(function () use ($user, $order, $amount, $currency) {
            $available = $this->availableBalance($user, $currency);
            $amount = min($amount, $available);
            if ($amount <= 0) {
                return null;
            }

            return AccountBalanceTransaction::create([
                'user_id' => $user->id,
                'order_id' => $order->id,
                'type' => AccountBalanceTransaction::TYPE_DEBIT,
                'status' => AccountBalanceTransaction::STATUS_PENDING,
                'amount' => $amount,
                'currency' => $currency,
                'description' => __('Списання балансу для замовлення :number', ['number' => $order->number]),
                'metadata' => ['source' => 'checkout'],
            ]);
        });
    }

    public function settleOrderDebit(Order $order): void
    {
        AccountBalanceTransaction::query()
            ->where('order_id', $order->id)
            ->where('type', AccountBalanceTransaction::TYPE_DEBIT)
            ->where('status', AccountBalanceTransaction::STATUS_PENDING)
            ->update(['status' => AccountBalanceTransaction::STATUS_SETTLED]);
    }

    public function voidOrderDebit(Order $order): void
    {
        AccountBalanceTransaction::query()
            ->where('order_id', $order->id)
            ->where('type', AccountBalanceTransaction::TYPE_DEBIT)
            ->where('status', AccountBalanceTransaction::STATUS_PENDING)
            ->update(['status' => AccountBalanceTransaction::STATUS_VOID]);
    }

    public function orderDebitAmount(Order $order): float
    {
        return (float) AccountBalanceTransaction::query()
            ->where('order_id', $order->id)
            ->where('type', AccountBalanceTransaction::TYPE_DEBIT)
            ->whereIn('status', [AccountBalanceTransaction::STATUS_PENDING, AccountBalanceTransaction::STATUS_SETTLED])
            ->sum('amount');
    }
}
