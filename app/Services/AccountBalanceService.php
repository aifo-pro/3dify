<?php

namespace App\Services;

use App\Models\AccountBalanceTransaction;
use App\Models\Order;
use App\Models\RefundRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AccountBalanceService
{
    public const DEFAULT_CURRENCY = 'UAH';

    public function availableBalance(User $user, string $currency = self::DEFAULT_CURRENCY): float
    {
        if (! Schema::hasTable('account_balance_transactions')) {
            return 0.0;
        }

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
        if (! Schema::hasTable('account_balance_transactions')) {
            return null;
        }

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

            $transaction = AccountBalanceTransaction::create([
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

            app(AuditLogger::class)->record('balance.credit_refund', $transaction, [
                'user_id' => $refundRequest->user_id,
                'order_id' => $order->id,
                'refund_request_id' => $refundRequest->id,
                'amount' => round($amount, 2),
                'currency' => $order->currency ?: self::DEFAULT_CURRENCY,
            ]);

            return $transaction;
        });
    }

    public function reserveForOrder(User $user, Order $order, float $amount, string $currency = self::DEFAULT_CURRENCY): ?AccountBalanceTransaction
    {
        if (! Schema::hasTable('account_balance_transactions')) {
            return null;
        }

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

            $transaction = AccountBalanceTransaction::create([
                'user_id' => $user->id,
                'order_id' => $order->id,
                'type' => AccountBalanceTransaction::TYPE_DEBIT,
                'status' => AccountBalanceTransaction::STATUS_PENDING,
                'amount' => $amount,
                'currency' => $currency,
                'description' => __('Списання балансу для замовлення :number', ['number' => $order->number]),
                'metadata' => ['source' => 'checkout'],
            ]);

            app(AuditLogger::class)->record('balance.reserve_checkout', $transaction, [
                'user_id' => $user->id,
                'order_id' => $order->id,
                'amount' => $amount,
                'currency' => $currency,
            ]);

            return $transaction;
        });
    }

    public function settleOrderDebit(Order $order): void
    {
        if (! Schema::hasTable('account_balance_transactions')) {
            return;
        }

        $count = AccountBalanceTransaction::query()
            ->where('order_id', $order->id)
            ->where('type', AccountBalanceTransaction::TYPE_DEBIT)
            ->where('status', AccountBalanceTransaction::STATUS_PENDING)
            ->update(['status' => AccountBalanceTransaction::STATUS_SETTLED]);

        if ($count > 0) {
            app(AuditLogger::class)->record('balance.settle_checkout', $order, ['order_id' => $order->id]);
        }
    }

    public function voidOrderDebit(Order $order): void
    {
        if (! Schema::hasTable('account_balance_transactions')) {
            return;
        }

        $count = AccountBalanceTransaction::query()
            ->where('order_id', $order->id)
            ->where('type', AccountBalanceTransaction::TYPE_DEBIT)
            ->where('status', AccountBalanceTransaction::STATUS_PENDING)
            ->update(['status' => AccountBalanceTransaction::STATUS_VOID]);

        if ($count > 0) {
            app(AuditLogger::class)->record('balance.void_checkout', $order, ['order_id' => $order->id]);
        }
    }

    public function orderDebitAmount(Order $order): float
    {
        if (! Schema::hasTable('account_balance_transactions')) {
            return 0.0;
        }

        return (float) AccountBalanceTransaction::query()
            ->where('order_id', $order->id)
            ->where('type', AccountBalanceTransaction::TYPE_DEBIT)
            ->whereIn('status', [AccountBalanceTransaction::STATUS_PENDING, AccountBalanceTransaction::STATUS_SETTLED])
            ->sum('amount');
    }
}
