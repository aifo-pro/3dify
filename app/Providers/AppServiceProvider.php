<?php

namespace App\Providers;

use App\Events\ProductPublished;
use App\Listeners\NotifyFollowersOnProductPublish;
use App\Models\Product;
use App\Models\Setting;
use App\Policies\ProductPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
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
        $this->configureMailFromSettings();

        Gate::policy(Product::class, ProductPolicy::class);
        Event::listen(ProductPublished::class, NotifyFollowersOnProductPublish::class);
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
}
