<?php

namespace Tests\Feature;

use App\Models\EmailTemplate;
use App\Models\User;
use App\Notifications\TemplatedPasswordResetNotification;
use App\Services\EmailTemplateRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Tests\TestCase;

class EmailTemplateRenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_renderer_replaces_html_template_tokens_and_legacy_aliases(): void
    {
        EmailTemplate::create([
            'key' => 'password_reset',
            'locale' => 'uk',
            'subject' => 'Reset {{ user_name }}',
            'body' => '&lt;div&gt;Hello &lt;b&gt;{{ user.name }}&lt;/b&gt; &lt;a href=&quot;{{ link }}&quot;&gt;{{ site_name }}&lt;/a&gt; {{ reset.expires_minutes }}&lt;/div&gt;',
            'is_active' => true,
        ]);

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

    public function test_password_reset_notification_uses_html_view(): void
    {
        $user = User::factory()->create(['locale' => 'uk']);

        $mail = (new TemplatedPasswordResetNotification('token'))->toMail($user);

        $this->assertInstanceOf(MailMessage::class, $mail);
        $this->assertSame('emails.templated', $mail->view);
        $this->assertArrayHasKey('body', $mail->viewData);
    }
}
