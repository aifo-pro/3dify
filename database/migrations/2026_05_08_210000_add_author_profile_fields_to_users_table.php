<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'display_name')) {
                $table->string('display_name')->nullable()->after('name');
            }
            if (! Schema::hasColumn('users', 'cover_path')) {
                $table->string('cover_path')->nullable()->after('avatar_path');
            }
            if (! Schema::hasColumn('users', 'bio_uk')) {
                $table->text('bio_uk')->nullable()->after('bio');
            }
            if (! Schema::hasColumn('users', 'bio_en')) {
                $table->text('bio_en')->nullable()->after('bio_uk');
            }
            if (! Schema::hasColumn('users', 'website_url')) {
                $table->string('website_url')->nullable()->after('bio_en');
            }
            if (! Schema::hasColumn('users', 'telegram_url')) {
                $table->string('telegram_url')->nullable()->after('website_url');
            }
            if (! Schema::hasColumn('users', 'instagram_url')) {
                $table->string('instagram_url')->nullable()->after('telegram_url');
            }
            if (! Schema::hasColumn('users', 'youtube_url')) {
                $table->string('youtube_url')->nullable()->after('instagram_url');
            }
            if (! Schema::hasColumn('users', 'github_url')) {
                $table->string('github_url')->nullable()->after('youtube_url');
            }
            if (! Schema::hasColumn('users', 'twitter_url')) {
                $table->string('twitter_url')->nullable()->after('github_url');
            }
            if (! Schema::hasColumn('users', 'location')) {
                $table->string('location')->nullable()->after('twitter_url');
            }
            if (! Schema::hasColumn('users', 'contact_enabled')) {
                $table->boolean('contact_enabled')->default(true)->after('location');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach ([
                'display_name',
                'cover_path',
                'bio_uk',
                'bio_en',
                'website_url',
                'telegram_url',
                'instagram_url',
                'youtube_url',
                'github_url',
                'twitter_url',
                'location',
                'contact_enabled',
            ] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
