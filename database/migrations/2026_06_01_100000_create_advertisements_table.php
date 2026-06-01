<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advertisements', function (Blueprint $table) {
            $table->id();
            $table->json('title');                              // localized title
            $table->json('description')->nullable();           // localized short description
            $table->string('image_path')->nullable();          // cover image
            $table->string('target_url');                      // destination URL on click
            $table->string('badge_label')->default('Реклама'); // label shown on card
            $table->string('ad_type')->default('grid');        // grid | banner | sidebar
            // Grid placement: show ad at every N-th position (e.g. 8 = after 8th, 16th, 24th…)
            $table->unsignedSmallInteger('grid_every')->default(8);
            // On which pages to inject (json array: catalog, category, home, search)
            $table->json('pages')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedBigInteger('impressions')->default(0);
            $table->unsignedBigInteger('clicks')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advertisements');
    }
};
