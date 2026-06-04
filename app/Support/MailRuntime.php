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
        $smtpUrl = $smtp['url'] ?? null;
        $smtpUsername = $smtp['username'] ?? null;

        return [
            'default' => $mailer,
            'smtp_url_present' => is_string($smtpUrl) && trim($smtpUrl) !== '',
            'smtp_url_host' => self::urlHost($smtpUrl),
            'smtp_host' => $smtp['host'] ?? null,
            'smtp_port' => $smtp['port'] ?? null,
            'smtp_scheme' => $smtp['scheme'] ?? null,
            'smtp_local_domain' => $smtp['local_domain'] ?? null,
            'smtp_username_fingerprint' => self::fingerprint($smtpUsername),
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

    private static function fingerprint(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        return $value === null || $value === ''
            ? null
            : substr(hash('sha256', $value), 0, 12);
    }

    private static function urlHost(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $host = parse_url($value, PHP_URL_HOST);

        return is_string($host) && $host !== '' ? $host : null;
    }
}
