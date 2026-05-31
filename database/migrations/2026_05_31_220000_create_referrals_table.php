<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('referral_code', 12)->nullable()->unique()->after('role');
            // Store as unsignedBigInteger to avoid FK recreation on SQLite
            $table->unsignedBigInteger('referred_by')->nullable()->after('referral_code');
            $table->index('referred_by');
        });

        Schema::create('referral_rewards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('referrer_id')->index();
            $table->unsignedBigInteger('referred_id')->index();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('currency', 3)->default('UAH');
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_rewards');
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['referred_by']);
            $table->dropColumn(['referral_code', 'referred_by']);
        });
    }
};
