<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Daily aggregate of product views — used for author analytics charts.
        Schema::create('product_view_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('count')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'date']);
            $table->index('date');
        });

        // Printer profiles owned by users (for compatibility checks).
        Schema::create('printer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            // fdm | sla | mjf
            $table->string('technology', 8)->default('fdm');
            $table->unsignedSmallInteger('bed_x')->nullable();
            $table->unsignedSmallInteger('bed_y')->nullable();
            $table->unsignedSmallInteger('bed_z')->nullable();
            $table->decimal('nozzle', 4, 2)->nullable();
            $table->json('materials')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'is_default']);
        });

        // Saved catalog searches with optional email alerts on new matches.
        Schema::create('saved_searches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->json('filters');
            $table->boolean('notify_email')->default(false);
            $table->timestamp('last_notified_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });

        // Add fields to products: dimensions, recommended materials, print profile attachment.
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedSmallInteger('dim_x')->nullable()->after('preview_path');
            $table->unsignedSmallInteger('dim_y')->nullable()->after('dim_x');
            $table->unsignedSmallInteger('dim_z')->nullable()->after('dim_y');
            $table->json('recommended_materials')->nullable()->after('dim_z');
            $table->string('print_profile_path')->nullable()->after('recommended_materials');
            $table->string('print_profile_name')->nullable()->after('print_profile_path');
            $table->json('print_profile_settings')->nullable()->after('print_profile_name');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'dim_x', 'dim_y', 'dim_z', 'recommended_materials',
                'print_profile_path', 'print_profile_name', 'print_profile_settings',
            ]);
        });
        Schema::dropIfExists('saved_searches');
        Schema::dropIfExists('printer_profiles');
        Schema::dropIfExists('product_view_stats');
    }
};
