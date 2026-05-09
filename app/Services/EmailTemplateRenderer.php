<?php

namespace App\Services;

use App\Models\EmailTemplate;

class EmailTemplateRenderer
{
    public function render(string $key, array $data, ?string $locale = null): array
    {
        $locale ??= app()->getLocale();

        $siteName = app(SiteSettings::class)->string('site.name', config('app.name'));
        $data = array_merge([
            'site' => ['name' => $siteName],
        ], $data);

        $candidateKeys = $this->candidateKeys($key);
        $template = $this->pickTemplate($candidateKeys, $locale);

        $fallbackKey = $candidateKeys[0];
        $subject = $template?->subject ?? $this->fallbackSubject($fallbackKey);
        $body = $template?->body ?? $this->fallbackBody($fallbackKey);

        foreach ($this->flatten($data) as $token => $value) {
            $subject = str_replace('{{ '.$token.' }}', (string) $value, $subject);
            $body = str_replace('{{ '.$token.' }}', (string) $value, $body);
        }

        return compact('subject', 'body');
    }

    /**
     * @return list<string>
     */
    private function candidateKeys(string $key): array
    {
        return match ($key) {
            'purchase_success' => ['purchase_success', 'purchase'],
            'model_sold' => ['model_sold', 'sale'],
            default => [$key],
        };
    }

    /**
     * @param  list<string>  $keys
     */
    private function pickTemplate(array $keys, string $locale): ?EmailTemplate
    {
        foreach ($keys as $key) {
            $template = EmailTemplate::query()
                ->where('key', $key)
                ->where('locale', $locale)
                ->where('is_active', true)
                ->first();
            if ($template) {
                return $template;
            }
        }

        foreach ($keys as $key) {
            $template = EmailTemplate::query()
                ->where('key', $key)
                ->where('is_active', true)
                ->first();
            if ($template) {
                return $template;
            }
        }

        return null;
    }

    private function flatten(array $data, string $prefix = ''): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $name = $prefix === '' ? $key : "{$prefix}.{$key}";
            if (is_array($value)) {
                $result += $this->flatten($value, $name);

                continue;
            }

            $result[$name] = $value;
        }

        return $result;
    }

    private function fallbackSubject(string $key): string
    {
        return match ($key) {
            'purchase', 'purchase_success' => 'Ваше замовлення 3Dify',
            'sale', 'model_sold' => 'Новий продаж на 3Dify',
            'registration' => 'Ласкаво просимо до '.config('app.name'),
            'email_verification' => 'Підтвердіть email',
            'password_reset' => 'Скидання пароля',
            'model_approved' => 'Модель схвалено',
            'model_rejected' => 'Модель відхилено',
            default => config('app.name').' · повідомлення',
        };
    }

    private function fallbackBody(string $key): string
    {
        return match ($key) {
            'purchase', 'purchase_success' => 'Дякуємо за покупку. Замовлення {{ order.number }} оплачено.',
            'sale', 'model_sold' => 'Вашу модель купили. Замовлення {{ order.number }} оплачено.',
            'registration' => 'Вітаємо, {{ user.name }}!',
            'email_verification' => 'Підтвердіть email: {{ verification.url }}',
            'password_reset' => 'Скидання пароля: {{ reset.url }}',
            'model_approved' => 'Модель «{{ product.title }}» схвалено. {{ product.url }}',
            'model_rejected' => 'Модель «{{ product.title }}» потребує доопрацювання.',
            default => 'Повідомлення від '.config('app.name').'.',
        };
    }
}
