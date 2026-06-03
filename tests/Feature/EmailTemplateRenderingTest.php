<?php

namespace Tests\Feature;

use App\Models\EmailTemplate;
use App\Models\User;
use App\Mail\RenderedTemplateMail;
use App\Services\EmailTemplateRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailTemplateRenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_renderer_replaces_html_template_tokens_and_legacy_aliases(): void
    {
        EmailTemplate::updateOrCreate(
            ['key' => 'password_reset', 'locale' => 'uk'],
            [
                'subject' => 'Reset {{ user_name }}',
                'body' => '&lt;div&gt;Hello &lt;b&gt;{{ user.name }}&lt;/b&gt; &lt;a href=&quot;{{'."\u{00A0}".'link'."\u{00A0}".'}}&quot;&gt;{{ site_name }}&lt;/a&gt; {{ reset.expires_minutes }}&lt;/div&gt;',
                'is_active' => true,
            ]
        );

        $rendered = app(EmailTemplateRenderer::class)->render('password_reset', [
            'user' => [
                'name' => 'Denys',
                'email' => 'denys@example.com',
            ],
            'reset' => [
                'url' => 'https://example.test/reset/token',
                'expires_minutes' => '60',
            ],
        ], 'uk');

        $this->assertSame('Reset Denys', $rendered['subject']);
        $this->assertStringContainsString('<div>Hello <b>Denys</b>', $rendered['body']);
        $this->assertStringContainsString('href="https://example.test/reset/token"', $rendered['body']);
        $this->assertStringNotContainsString('{{ link }}', $rendered['body']);
    }

    public function test_password_reset_queues_rendered_html_mailable(): void
    {
        Mail::fake();

        EmailTemplate::updateOrCreate(
            ['key' => 'password_reset', 'locale' => 'uk'],
            [
                'subject' => 'Reset {{ user.name }}',
                'body' => '<h1>Reset</h1><a href="{{ link }}">Go</a>',
                'is_active' => true,
            ]
        );

        $user = User::factory()->create([
            'name' => 'Denys',
            'email' => 'denys@example.com',
            'locale' => 'uk',
        ]);

        $user->sendPasswordResetNotification('token');

        Mail::assertQueued(RenderedTemplateMail::class, function (RenderedTemplateMail $mail) {
            return $mail->subjectLine === 'Reset Denys'
                && str_contains($mail->body, '<h1>Reset</h1>')
                && ! str_contains($mail->body, '{{ link }}');
        });
    }

    public function test_author_contact_uses_rendered_html_mail_with_reply_to(): void
    {
        $this->withoutMiddleware();

        Mail::fake();

        EmailTemplate::updateOrCreate(
            ['key' => 'author_contact', 'locale' => 'uk'],
            [
                'subject' => 'Contact {{ contact.sender_name }}',
                'body' => '<h1>{{ contact.subject }}</h1><p>{{ contact.message }}</p><p>{{ user.email }}</p>',
                'is_active' => true,
            ]
        );

        $sender = User::factory()->create([
            'name' => 'Buyer',
            'email' => 'buyer@example.com',
            'username' => 'buyer',
            'locale' => 'uk',
        ]);

        $author = User::factory()->create([
            'name' => 'Author',
            'email' => 'author@example.com',
            'username' => 'author',
            'locale' => 'uk',
            'contact_enabled' => true,
        ]);

        $this->actingAs($sender)
            ->post(route('authors.contact', $author->username), [
                'subject' => 'Question <script>alert(1)</script>',
                'message' => "Hello <b>raw</b>\nCan we talk?",
            ])
            ->assertRedirect();

        Mail::assertSent(RenderedTemplateMail::class, function (RenderedTemplateMail $mail) use ($sender) {
            return $mail->subjectLine === 'Contact Buyer'
                && $mail->replyToEmail === $sender->email
                && $mail->replyToName === $sender->name
                && str_contains($mail->body, 'Question &lt;script&gt;alert(1)&lt;/script&gt;')
                && str_contains($mail->body, 'Hello &lt;b&gt;raw&lt;/b&gt;<br />')
                && ! str_contains($mail->body, '<script>alert(1)</script>');
        });
    }
}
