<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('custom_orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('custom_orders', 'delivery_city')) {
                $table->string('delivery_city')->nullable()->after('delivery_service');
            }

            if (! Schema::hasColumn('custom_orders', 'delivery_city_ref')) {
                $table->string('delivery_city_ref')->nullable()->after('delivery_city');
            }

            if (! Schema::hasColumn('custom_orders', 'delivery_warehouse_ref')) {
                $table->string('delivery_warehouse_ref')->nullable()->after('delivery_city_ref');
            }

            if (! Schema::hasColumn('custom_orders', 'delivery_selected_at')) {
                $table->timestamp('delivery_selected_at')->nullable()->after('delivery_address');
            }
        });
    }

    public function down(): void
    {
        Schema::table('custom_orders', function (Blueprint $table): void {
            foreach (['delivery_selected_at', 'delivery_warehouse_ref', 'delivery_city_ref', 'delivery_city'] as $column) {
                if (Schema::hasColumn('custom_orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
