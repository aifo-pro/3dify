<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_comments', function (Blueprint $table) {
            $table->unsignedInteger('likes_count')->default(0)->after('status');
        });

        Schema::create('product_comment_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comment_id')->constrained('product_comments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['comment_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_comment_likes');
        Schema::table('product_comments', function (Blueprint $table) {
            $table->dropColumn('likes_count');
        });
    }
};
