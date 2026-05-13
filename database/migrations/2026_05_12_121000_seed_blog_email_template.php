<?php

use App\Services\EmailTemplateCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('email_templates')) {
            return;
        }

        $template = EmailTemplateCatalog::templates()['blog_post_published'] ?? null;
        if (! $template) {
            return;
        }

        foreach ($template['defaults'] as $locale => $default) {
            DB::table('email_templates')->updateOrInsert(
                ['key' => 'blog_post_published', 'locale' => $locale],
                [
                    'subject' => $default['subject'],
                    'body' => $default['body'],
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        // Keep admin-managed templates.
    }
};
