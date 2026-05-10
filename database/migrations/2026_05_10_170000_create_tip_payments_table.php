<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tip_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tip_id')->constrained('tips')->cascadeOnDelete();
            $table->string('provider')->default('aifo');
            $table->string('provider_payment_id')->nullable()->index();
            $table->string('status')->default('created')->index();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('UAH');
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['tip_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tip_payments');
    }
};

