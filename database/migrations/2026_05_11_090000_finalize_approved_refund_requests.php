<?php

use App\Models\AccountBalanceTransaction;
use App\Models\RefundRequest;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::hasTable('refund_requests')
            || ! Schema::hasTable('orders')
            || ! Schema::hasTable('order_items')
            || ! Schema::hasTable('account_balance_transactions')
        ) {
            return;
        }

        DB::table('refund_requests')
            ->where('status', RefundRequest::STATUS_APPROVED)
            ->orderBy('id')
            ->get()
            ->each(function ($refund): void {
                $order = DB::table('orders')->where('id', $refund->order_id)->first();

                DB::table('refund_requests')
                    ->where('id', $refund->id)
                    ->update([
                        'status' => RefundRequest::STATUS_REFUNDED,
                        'processed_at' => $refund->processed_at ?: now(),
                        'updated_at' => now(),
                    ]);

                if (! $order) {
                    return;
                }

                DB::table('orders')
                    ->where('id', $order->id)
                    ->update([
                        'status' => 'refunded',
                        'updated_at' => now(),
                    ]);

                $alreadyCredited = DB::table('account_balance_transactions')
                    ->where('refund_request_id', $refund->id)
                    ->where('type', AccountBalanceTransaction::TYPE_CREDIT)
                    ->exists();

                if ($alreadyCredited) {
                    return;
                }

                $amount = (float) DB::table('order_items')
                    ->where('order_id', $order->id)
                    ->sum('price');

                if ($amount <= 0) {
                    $amount = (float) $order->total;
                }

                if ($amount <= 0 || ! $refund->user_id) {
                    return;
                }

                DB::table('account_balance_transactions')->insert([
                    'user_id' => $refund->user_id,
                    'order_id' => $order->id,
                    'refund_request_id' => $refund->id,
                    'type' => AccountBalanceTransaction::TYPE_CREDIT,
                    'status' => AccountBalanceTransaction::STATUS_SETTLED,
                    'amount' => round($amount, 2),
                    'currency' => $order->currency ?: 'UAH',
                    'description' => 'Refund for order '.$order->number,
                    'metadata' => json_encode(['source' => 'approved_refund_backfill']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        // Data repair migration: intentionally not reversible.
    }
};
