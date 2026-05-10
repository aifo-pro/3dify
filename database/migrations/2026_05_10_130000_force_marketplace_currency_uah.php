<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['products', 'orders', 'order_items', 'payments', 'tips', 'payouts', 'promo_codes'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'currency')) {
                DB::table($table)->update(['currency' => 'UAH']);
            }
        }
    }

    public function down(): void
    {
        // Currency normalization is intentionally not reversed to avoid
        // guessing historical currencies after production data changes.
    }
};
