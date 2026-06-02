<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'kyc_status')) {
                $table->string('kyc_status', 32)->default('not_started')->after('manual_verification');
            }
            if (! Schema::hasColumn('users', 'kyc_verified_at')) {
                $table->timestamp('kyc_verified_at')->nullable()->after('kyc_status');
            }
            if (! Schema::hasColumn('users', 'is_verified')) {
                $table->boolean('is_verified')->default(false)->after('kyc_verified_at');
            }
        });

        Schema::create('kyc_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider')->default('didit');
            $table->string('provider_session_id')->nullable()->index();
            $table->string('provider_applicant_id')->nullable()->index();
            $table->string('status', 32)->default('pending')->index();
            $table->string('decision')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('verification_url')->nullable();
            $table->json('webhook_payload')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kyc_verifications');

        Schema::table('users', function (Blueprint $table) {
            foreach (['is_verified', 'kyc_verified_at', 'kyc_status'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
