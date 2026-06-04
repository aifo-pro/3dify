<?php

namespace App\Providers;

use App\Events\ProductPublished;
use App\Listeners\NotifyFollowersOnProductPublish;
use App\Models\Product;
use App\Models\Setting;
use App\Policies\ProductPolicy;
use App\Support\MailRuntime;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

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
        $this->configureMailFromSettings();
        $this->registerMailDiagnostics();

        Gate::policy(Product::class, ProductPolicy::class);
        Event::listen(ProductPublished::class, NotifyFollowersOnProductPublish::class);
    }

    private function registerMailDiagnostics(): void
    {
        Event::listen(MessageSending::class, function (MessageSending $event): void {
            Log::info('Mail delivery started.', [
                'subject' => $event->message->getSubject(),
                'from' => $this->mailAddresses($event->message->getFrom()),
                'to' => $this->mailAddresses($event->message->getTo()),
                'mail' => MailRuntime::context(),
            ]);
        });

        Event::listen(MessageSent::class, function (MessageSent $event): void {
            /** @var Email $message */
            $message = $event->message;

            Log::info('Mail delivery accepted by mailer.', [
                'subject' => $message->getSubject(),
                'from' => $this->mailAddresses($message->getFrom()),
                'to' => $this->mailAddresses($message->getTo()),
                'mail' => MailRuntime::context(),
            ]);
        });
    }

    /**
     * @param  array<int, Address>  $addresses
     * @return list<string>
     */
    private function mailAddresses(array $addresses): array
    {
        return array_values(array_map(
            static fn (Address $address): string => $address->toString(),
            $addresses
        ));
    }

    private function configureMailFromSettings(): void
    {
        try {
            if (! Schema::hasTable('settings')) {
                return;
            }

            $settings = Setting::query()
                ->whereIn('key', [
                    'mail.mailer',
                    'mail.host',
                    'mail.port',
                    'mail.username',
                    'mail.password',
                    'mail.encryption',
                    'mail.from_address',
                    'mail.from_name',
                    'mail.ehlo_domain',
                ])
                ->pluck('value', 'key')
                ->all();
        } catch (\Throwable) {
            return;
        }

        $string = static function (array $settings, string $key): ?string {
            $value = $settings[$key] ?? null;

            if (is_array($value)) {
                $value = reset($value);
            }

            $value = is_string($value) ? trim($value) : $value;

            return $value === null || $value === '' ? null : (string) $value;
        };

        $mailer = $string($settings, 'mail.mailer');
        if ($mailer && in_array($mailer, ['smtp', 'log', 'array', 'sendmail', 'failover'], true)) {
            config(['mail.default' => $mailer]);
        }

        $smtp = [];
        foreach ([
            'host' => 'mail.host',
            'port' => 'mail.port',
            'username' => 'mail.username',
            'password' => 'mail.password',
            'local_domain' => 'mail.ehlo_domain',
        ] as $configKey => $settingKey) {
            $value = $string($settings, $settingKey);
            if ($value !== null) {
                $smtp[$configKey] = $configKey === 'port' ? (int) $value : $value;
            }
        }

        $encryption = $string($settings, 'mail.encryption');
        if ($encryption !== null) {
            $smtp['scheme'] = $encryption;
        }

        if ($smtp !== []) {
            $smtp = $this->normalizeSmtpConfig($smtp);
            config(['mail.mailers.smtp' => array_replace(config('mail.mailers.smtp', []), $smtp)]);
        }

        $fromAddress = $string($settings, 'mail.from_address');
        $fromName = $string($settings, 'mail.from_name');

        if ($fromAddress) {
            config(['mail.from.address' => $fromAddress]);
        }

        if ($fromName) {
            config(['mail.from.name' => $fromName]);
        }
    }

    /**
     * The admin UI stores human-friendly encryption values, while Laravel 12
     * passes the value as a Symfony Mailer DSN scheme. For Mailjet that means
     * port 587 must become "smtp" (STARTTLS) and port 465 must become "smtps".
     */
    private function normalizeSmtpConfig(array $smtp): array
    {
        $port = (int) ($smtp['port'] ?? 0);
        $scheme = strtolower((string) ($smtp['scheme'] ?? ''));

        if ($port === 587) {
            $smtp['scheme'] = 'smtp';
            return $smtp;
        }

        if ($port === 465) {
            $smtp['scheme'] = 'smtps';
            return $smtp;
        }

        if (in_array($scheme, ['ssl', 'smtps'], true)) {
            $smtp['scheme'] = 'smtps';
            $smtp['port'] = $port ?: 465;
            return $smtp;
        }

        if (in_array($scheme, ['tls', 'starttls', 'smtp'], true)) {
            $smtp['scheme'] = 'smtp';
            $smtp['port'] = $port ?: 587;
        }

        return $smtp;
    }
}
