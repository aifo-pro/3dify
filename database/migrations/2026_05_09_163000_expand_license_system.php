<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Expand the license system without breaking existing data:
 * - new flag/UI columns on `licenses`
 * - dual-pricing columns on `products` (personal_price, commercial_price + commercial license toggle)
 * - per-item license capture on `order_items` (license_type + snapshot)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            $table->string('badge_label', 60)->nullable()->after('description');
            $table->string('badge_color', 32)->nullable()->after('badge_label');
            $table->string('icon_slug', 60)->nullable()->after('badge_color');
            $table->boolean('allows_redistribution')->default(false)->after('requires_attribution');
            $table->boolean('allows_remix')->default(true)->after('allows_redistribution');
            $table->boolean('allows_selling_prints')->default(false)->after('allows_remix');
            $table->boolean('forbids_file_resale')->default(true)->after('allows_selling_prints');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->decimal('personal_price', 10, 2)->nullable()->after('price');
            $table->decimal('commercial_price', 10, 2)->nullable()->after('personal_price');
            $table->boolean('commercial_license_enabled')->default(false)->after('commercial_price');
            $table->foreignId('commercial_license_id')->nullable()->after('commercial_license_enabled')->constrained('licenses')->nullOnDelete();
            $table->json('commercial_license_description')->nullable()->after('commercial_license_id');
        });

        Schema::table('order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('order_items', 'license_type')) {
                $table->string('license_type', 24)->nullable()->after('currency');
            }
            if (! Schema::hasColumn('order_items', 'license_snapshot')) {
                $table->json('license_snapshot')->nullable()->after('license_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasColumn('order_items', 'license_snapshot')) {
                $table->dropColumn('license_snapshot');
            }
            if (Schema::hasColumn('order_items', 'license_type')) {
                $table->dropColumn('license_type');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('commercial_license_id');
            $table->dropColumn(['personal_price', 'commercial_price', 'commercial_license_enabled', 'commercial_license_description']);
        });

        Schema::table('licenses', function (Blueprint $table) {
            $table->dropColumn([
                'badge_label',
                'badge_color',
                'icon_slug',
                'allows_redistribution',
                'allows_remix',
                'allows_selling_prints',
                'forbids_file_resale',
            ]);
        });
    }
};
