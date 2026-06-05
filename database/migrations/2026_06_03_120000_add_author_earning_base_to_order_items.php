<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Base used to compute the author's earnings, independent of what the
            // buyer actually paid. For system (site) promo codes this stays at the
            // full price so the platform absorbs the discount; for author promo
            // codes it equals the discounted price (the author funds the discount).
            $table->decimal('author_earning_base', 10, 2)->nullable()->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('author_earning_base');
        });
    }
};
