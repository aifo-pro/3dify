<?php

namespace App\Providers;

use App\Mail\RenderedTemplateMail;
use App\Models\Product;
use App\Policies\ProductPolicy;
use App\Services\EmailTemplateRenderer;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Product::class, ProductPolicy::class);

        ResetPassword::toMailUsing(function ($notifiable, string $token): RenderedTemplateMail {
            $locale = $notifiable->locale ?: app()->getLocale();
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));
            $expire = (string) config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

            $rendered = app(EmailTemplateRenderer::class)->render('password_reset', [
                'user' => ['name' => $notifiable->name],
                'reset' => [
                    'url' => $url,
                    'expires_minutes' => $expire,
                ],
            ], $locale);

            return (new RenderedTemplateMail($rendered['subject'], $rendered['body']))
                ->to($notifiable->routeNotificationFor('mail'));
        });

        VerifyEmail::toMailUsing(function ($notifiable, string $verificationUrl): RenderedTemplateMail {
            $locale = $notifiable->locale ?: app()->getLocale();
            $expire = (string) config('auth.verification.expire', 60);

            $rendered = app(EmailTemplateRenderer::class)->render('email_verification', [
                'user' => ['name' => $notifiable->name],
                'verification' => [
                    'url' => $verificationUrl,
                    'expires_minutes' => $expire,
                ],
            ], $locale);

            return (new RenderedTemplateMail($rendered['subject'], $rendered['body']))
                ->to($notifiable->routeNotificationFor('mail'));
        });
    }
}
