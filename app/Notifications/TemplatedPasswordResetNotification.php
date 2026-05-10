<?php

namespace App\Notifications;

use App\Services\EmailTemplateRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Password reset using DB templates — invoked from {@see User::sendPasswordResetNotification}
 * so production never falls back to Laravel's markdown notification layout.
 */
class TemplatedPasswordResetNotification extends Notification
{
    use Queueable;

    public function __construct(
        #[\SensitiveParameter] public string $token
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $locale = $notifiable->locale ?: app()->getLocale();
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));
        $expire = (string) config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        $rendered = app(EmailTemplateRenderer::class)->render('password_reset', [
            'user' => [
                'name' => $notifiable->name,
                'email' => $notifiable->email,
            ],
            'link' => $url,
            'reset' => [
                'url' => $url,
                'expires_minutes' => $expire,
            ],
        ], $locale);

        return (new MailMessage)
            ->subject($rendered['subject'])
            ->view('emails.templated', ['body' => $rendered['body']]);
    }
}
