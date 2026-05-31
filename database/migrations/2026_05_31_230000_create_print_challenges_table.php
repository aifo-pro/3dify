<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('print_challenges', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->json('title');
            $table->json('description')->nullable();
            $table->string('cover_path')->nullable();
            $table->unsignedBigInteger('prize_product_id')->nullable();
            $table->string('prize_description')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::create('print_challenge_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('challenge_id')->constrained('print_challenges')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('make_id')->nullable()->constrained('product_makes')->nullOnDelete();
            $table->string('image_path')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('votes')->default(0);
            $table->string('status')->default('pending'); // pending, approved, winner
            $table->timestamps();
        });

        Schema::create('print_challenge_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entry_id')->constrained('print_challenge_entries')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['entry_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('print_challenge_votes');
        Schema::dropIfExists('print_challenge_entries');
        Schema::dropIfExists('print_challenges');
    }
};
