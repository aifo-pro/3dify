<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            if (Schema::hasColumn('blog_posts', 'content_uk')) {
                $table->dropColumn('content_uk');
            }
            if (Schema::hasColumn('blog_posts', 'content_en')) {
                $table->dropColumn('content_en');
            }
        });
    }

    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->longText('content_uk')->nullable();
            $table->longText('content_en')->nullable();
        });
    }
};
