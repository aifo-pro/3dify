<?php

namespace App\Http\Middleware;

use App\Models\Translation;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Schema;

class SetLocale
{
    /** Locales that ship with a resources/lang/{locale} directory. */
    public const SUPPORTED_LOCALES = ['uk', 'en', 'pl'];

    public function handle(Request $request, Closure $next)
    {
        $locale = $request->session()->get('locale')
            ?? $request->user()?->locale
            ?? config('app.locale', 'uk');

        if (! in_array($locale, self::SUPPORTED_LOCALES, true)) {
            $locale = in_array(config('app.locale'), self::SUPPORTED_LOCALES, true)
                ? config('app.locale')
                : 'uk';
        }

        App::setLocale($locale);
        $request->session()->put('locale', $locale);

        if (Schema::hasTable('translations')) {
            Translation::query()
                ->where('locale', $locale)
                ->whereNotNull('value')
                ->get()
                ->each(function (Translation $translation) use ($locale) {
                    Lang::addLines([$translation->key => $translation->value], $locale, '*');

                    if ($translation->group && ! in_array($translation->group, ['*', 'messages'], true)) {
                        Lang::addLines([$translation->key => $translation->value], $locale, $translation->group);
                    }
                });
        }

        return $next($request);
    }
}
