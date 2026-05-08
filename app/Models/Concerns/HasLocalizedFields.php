<?php

namespace App\Models\Concerns;

trait HasLocalizedFields
{
    public function localized(string $field, ?string $locale = null, ?string $fallback = 'uk'): string
    {
        $value = $this->{$field};

        if (! is_array($value)) {
            return (string) ($value ?? '');
        }

        $locale ??= app()->getLocale();

        return (string) ($value[$locale] ?? $value[$fallback] ?? reset($value) ?: '');
    }
}
