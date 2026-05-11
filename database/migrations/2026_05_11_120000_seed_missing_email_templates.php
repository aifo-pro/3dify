<?php

use App\Services\EmailTemplateCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (app()->environment('testing')) {
            return;
        }

        if (! Schema::hasTable('email_templates')) {
            return;
        }

        foreach (EmailTemplateCatalog::templates() as $key => $template) {
            foreach ($template['defaults'] as $locale => $default) {
                $exists = DB::table('email_templates')
                    ->where('key', $key)
                    ->where('locale', $locale)
                    ->exists();

                if ($exists) {
                    continue;
                }

                DB::table('email_templates')->insert([
                    'key' => $key,
                    'locale' => $locale,
                    'subject' => $default['subject'],
                    'body' => $default['body'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Do not delete admin-managed templates on rollback.
    }
};
