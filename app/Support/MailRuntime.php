<?php

namespace App\Support;

class MailRuntime
{
    /**
     * Return only non-secret mail configuration values that are useful when
     * debugging delivery. Never expose SMTP username or password here.
     *
     * @return array<string, mixed>
     */
    public static function context(): array
    {
        $mailer = (string) config('mail.default');
        $smtp = (array) config('mail.mailers.smtp', []);

        return [
            'default' => $mailer,
            'smtp_host' => $smtp['host'] ?? null,
            'smtp_port' => $smtp['port'] ?? null,
            'smtp_scheme' => $smtp['scheme'] ?? null,
            'smtp_local_domain' => $smtp['local_domain'] ?? null,
            'from_address' => config('mail.from.address'),
            'from_name' => config('mail.from.name'),
            'queue' => config('queue.default'),
        ];
    }

    public static function summary(): string
    {
        $context = self::context();

        return sprintf(
            'mailer=%s, host=%s, port=%s, scheme=%s, from=%s',
            $context['default'] ?: '-',
            $context['smtp_host'] ?: '-',
            $context['smtp_port'] ?: '-',
            $context['smtp_scheme'] ?: '-',
            $context['from_address'] ?: '-'
        );
    }

    public static function isRealSmtp(): bool
    {
        return self::context()['default'] === 'smtp';
    }
}
