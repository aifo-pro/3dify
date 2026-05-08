<?php

namespace App\Services;

use App\Models\EmailTemplate;

class EmailTemplateRenderer
{
    public function render(string $key, array $data, string $locale = 'uk'): array
    {
        $template = EmailTemplate::query()
            ->where('key', $key)
            ->where('locale', $locale)
            ->where('is_active', true)
            ->first()
            ?? EmailTemplate::query()->where('key', $key)->where('is_active', true)->first();

        $subject = $template?->subject ?? $this->fallbackSubject($key);
        $body = $template?->body ?? $this->fallbackBody($key);

        foreach ($this->flatten($data) as $token => $value) {
            $subject = str_replace('{{ '.$token.' }}', (string) $value, $subject);
            $body = str_replace('{{ '.$token.' }}', (string) $value, $body);
        }

        return compact('subject', 'body');
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
            'purchase' => 'Ваше замовлення 3Dify',
            'sale' => 'Новий продаж на 3Dify',
            default => '3Dify notification',
        };
    }

    private function fallbackBody(string $key): string
    {
        return match ($key) {
            'purchase' => 'Дякуємо за покупку. Замовлення {{ order.number }} оплачено.',
            'sale' => 'Вашу модель купили. Замовлення {{ order.number }} оплачено.',
            default => 'Повідомлення від 3Dify.',
        };
    }
}
