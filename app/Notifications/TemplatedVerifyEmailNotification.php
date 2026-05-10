<?php

namespace App\Notifications;

use App\Mail\RenderedTemplateMail;
use App\Services\EmailTemplateRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

/**
 * Email verification using DB templates — invoked from {@see User::sendEmailVerificationNotification}.
 */
class TemplatedVerifyEmailNotification extends Notification
{
    use Queueable;

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): RenderedTemplateMail
    {
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes((int) Config::get('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );

        $locale = $notifiable->locale ?: app()->getLocale();
        $expire = (string) Config::get('auth.verification.expire', 60);

        $rendered = app(EmailTemplateRenderer::class)->render('email_verification', [
            'user' => [
                'name' => $notifiable->name,
                'email' => $notifiable->email,
            ],
            'link' => $verificationUrl,
            'verification' => [
                'url' => $verificationUrl,
                'expires_minutes' => $expire,
            ],
        ], $locale);

        return (new RenderedTemplateMail($rendered['subject'], $rendered['body']))
            ->to($notifiable->routeNotificationFor('mail'));
    }
}
