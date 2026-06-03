<?php

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

        $templates = [
            'uk' => [
                'subject' => 'Нове повідомлення від {{ contact.sender_name }}',
                'body' => '<h1>{{ contact.subject }}</h1><p>{{ contact.message }}</p><p>Відправник: {{ contact.sender_name }} ({{ user.email }})</p>',
            ],
            'en' => [
                'subject' => 'New message from {{ contact.sender_name }}',
                'body' => '<h1>{{ contact.subject }}</h1><p>{{ contact.message }}</p><p>Sender: {{ contact.sender_name }} ({{ user.email }})</p>',
            ],
        ];

        foreach ($templates as $locale => $template) {
            $row = DB::table('email_templates')
                ->where('key', 'author_contact')
                ->where('locale', $locale)
                ->first();

            if (! $row) {
                DB::table('email_templates')->insert([
                    'key' => 'author_contact',
                    'locale' => $locale,
                    'subject' => $template['subject'],
                    'body' => $template['body'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                continue;
            }

            $looksBroken = str_contains((string) $row->subject, 'Р')
                || str_contains((string) $row->body, 'Р');

            if ($looksBroken) {
                DB::table('email_templates')
                    ->where('id', $row->id)
                    ->update([
                        'subject' => $template['subject'],
                        'body' => $template['body'],
                        'updated_at' => now(),
                    ]);
            }
        }
    }
};
