<?php

namespace App\Services;

use App\Models\EmailTemplate;

class EmailTemplateRenderer
{
    public function render(string $key, array $data, ?string $locale = null): array
    {
        $locale ??= app()->getLocale();

        $siteName = app(SiteSettings::class)->string('site.name', config('app.name'));
        $baseSite = [
            'name' => $siteName,
            'url' => rtrim((string) config('app.url'), '/'),
        ];

        $incomingSite = is_array($data['site'] ?? null) ? $data['site'] : [];
        unset($data['site']);
        $data['site'] = array_merge($baseSite, $incomingSite);
        $data = $this->withLegacyAliases($data);

        $candidateKeys = $this->candidateKeys($key);
        $template = $this->pickTemplate($candidateKeys, $locale);

        $fallbackKey = $candidateKeys[0];
        $subject = $this->decodeTemplate((string) ($template?->subject ?? $this->fallbackSubject($fallbackKey)));
        $body = $this->decodeTemplate((string) ($template?->body ?? $this->fallbackBody($fallbackKey)));

        foreach ($this->flatten($data) as $token => $value) {
            $subject = $this->substituteToken($subject, $token, (string) $value);
            $body = $this->substituteToken($body, $token, (string) $value);
        }

        return compact('subject', 'body');
    }

    /**
     * @return array<string, list<string>>
     */
    public static function placeholderMap(): array
    {
        $site = [
            '{{ site.name }}',
            '{{ site.url }}',
            '{{ site_name }}',
            '{{ site_url }}',
        ];
        $user = [
            '{{ user.name }}',
            '{{ user.email }}',
            '{{ user.username }}',
            '{{ user.display_name }}',
            '{{ user.locale }}',
            '{{ user_name }}',
            '{{ user_email }}',
            '{{ user_username }}',
        ];
        $order = [
            '{{ order.number }}',
            '{{ order.total }}',
            '{{ order.currency }}',
            '{{ order.url }}',
            '{{ order_number }}',
            '{{ order_total }}',
            '{{ order_currency }}',
            '{{ order_url }}',
        ];
        $product = [
            '{{ product.title }}',
            '{{ product.url }}',
            '{{ product.slug }}',
            '{{ product.price }}',
            '{{ product.currency }}',
            '{{ product.status }}',
            '{{ product_title }}',
            '{{ product_url }}',
            '{{ product_slug }}',
        ];
        $authLink = [
            '{{ link }}',
        ];

        return [
            'registration' => self::uniqueTokens($site, $user),
            'email_verification' => self::uniqueTokens($site, $user, $authLink, [
                '{{ verification.url }}',
                '{{ verification.expires_minutes }}',
            ]),
            'password_reset' => self::uniqueTokens($site, $user, $authLink, [
                '{{ reset.url }}',
                '{{ reset.expires_minutes }}',
            ]),
            'purchase_success' => self::uniqueTokens($site, $user, $order, [
                '{{ download.url }}',
                '{{ downloads.url }}',
            ]),
            'model_sold' => self::uniqueTokens($site, $user, $order, $product, [
                '{{ seller.name }}',
                '{{ buyer.name }}',
            ]),
            'model_approved' => self::uniqueTokens($site, $user, $product),
            'model_rejected' => self::uniqueTokens($site, $user, $product, [
                '{{ moderation.note }}',
                '{{ moderation.reason }}',
            ]),
        ];
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

    private function withLegacyAliases(array $data): array
    {
        $data['site_name'] ??= $data['site']['name'] ?? null;
        $data['site_url'] ??= $data['site']['url'] ?? null;

        if (is_array($data['user'] ?? null)) {
            $data['user_name'] ??= $data['user']['name'] ?? null;
            $data['user_email'] ??= $data['user']['email'] ?? null;
            $data['user_username'] ??= $data['user']['username'] ?? null;
        }

        if (is_array($data['order'] ?? null)) {
            $data['order_number'] ??= $data['order']['number'] ?? null;
            $data['order_total'] ??= $data['order']['total'] ?? null;
            $data['order_currency'] ??= $data['order']['currency'] ?? null;
            $data['order_url'] ??= $data['order']['url'] ?? null;
        }

        if (is_array($data['product'] ?? null)) {
            $data['product_title'] ??= $data['product']['title'] ?? null;
            $data['product_url'] ??= $data['product']['url'] ?? null;
            $data['product_slug'] ??= $data['product']['slug'] ?? null;
        }

        if (is_array($data['reset'] ?? null)) {
            $data['link'] ??= $data['reset']['url'] ?? null;
        }

        if (is_array($data['verification'] ?? null)) {
            $data['link'] ??= $data['verification']['url'] ?? null;
        }

        return array_filter($data, static fn ($value) => $value !== null);
    }

    private function decodeTemplate(string $text): string
    {
        return html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function substituteToken(string $text, string $token, string $value): string
    {
        $text = str_replace('{{ '.$token.' }}', $value, $text);
        $text = str_replace('{{'.$token.'}}', $value, $text);

        return $text;
    }

    private static function uniqueTokens(array ...$groups): array
    {
        return array_values(array_unique(array_merge(...$groups)));
    }

    private function fallbackSubject(string $key): string
    {
        return match ($key) {
            'purchase', 'purchase_success' => 'Ваше замовлення 3Dify',
            'sale', 'model_sold' => 'Новий продаж на 3Dify',
            'registration' => 'Ласкаво просимо до '.config('app.name'),
            'email_verification' => 'Підтвердьте email',
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
            'email_verification' => 'Підтвердьте email: {{ verification.url }}',
            'password_reset' => 'Скидання пароля: {{ reset.url }}',
            'model_approved' => 'Модель «{{ product.title }}» схвалено. {{ product.url }}',
            'model_rejected' => 'Модель «{{ product.title }}» потребує доопрацювання.',
            default => 'Повідомлення від '.config('app.name').'.',
        };
    }
}
