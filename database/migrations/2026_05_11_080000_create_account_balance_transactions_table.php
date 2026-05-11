<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_balance_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('refund_request_id')->nullable()->constrained('refund_requests')->nullOnDelete();
            $table->string('type', 16);
            $table->string('status', 16)->default('settled');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('UAH');
            $table->string('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'currency', 'status']);
            $table->index(['order_id', 'type']);
            $table->index(['refund_request_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_balance_transactions');
    }
};
