<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SiteSettings
{
    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("setting:{$key}", 300, fn () => Setting::value($key, $default));
    }

    public function string(string $key, string $default = ''): string
    {
        $value = $this->get($key, $default);

        return is_array($value) ? json_encode($value) : (string) $value;
    }

    /**
     * Read a list setting, always returning an array.
     */
    public function list(string $key, array $default = []): array
    {
        $value = $this->get($key, $default);

        return is_array($value) ? $value : $default;
    }

    public function forget(string $key): void
    {
        Cache::forget("setting:{$key}");
    }
}
