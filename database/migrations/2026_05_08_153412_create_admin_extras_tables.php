<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title', 180);
            $table->text('body')->nullable();
            // info | warning | success | critical
            $table->string('level', 12)->default('info');
            // all | guests | users | authors | admins
            $table->string('audience', 12)->default('all');
            $table->string('cta_label', 60)->nullable();
            $table->string('cta_url', 255)->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_dismissible')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'starts_at', 'ends_at']);
        });

        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('name')->nullable();
            $table->string('locale', 5)->nullable();
            // user-supplied | author-cta | checkout
            $table->string('source', 30)->default('footer');
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->string('unsubscribe_token', 64)->unique();
            $table->timestamps();

            $table->index('unsubscribed_at');
        });

        Schema::create('newsletter_blasts', function (Blueprint $table) {
            $table->id();
            $table->string('subject', 200);
            $table->longText('body');
            // all | authors | buyers
            $table->string('audience', 30)->default('all');
            $table->unsignedInteger('recipients_count')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('manual_verification')->default(false)->after('role');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unsignedSmallInteger('featured_order')->nullable()->after('is_featured');
            $table->index(['is_featured', 'featured_order']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['is_featured', 'featured_order']);
            $table->dropColumn('featured_order');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('manual_verification');
        });
        Schema::dropIfExists('newsletter_blasts');
        Schema::dropIfExists('newsletter_subscribers');
        Schema::dropIfExists('announcements');
    }
};
