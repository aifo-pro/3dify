<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\RedirectResponse;

class LocaleController extends Controller
{
    public function switch(string $locale): RedirectResponse
    {
        abort_unless(in_array($locale, SetLocale::SUPPORTED_LOCALES, true), 404);

        session(['locale' => $locale]);

        if (auth()->check()) {
            auth()->user()->forceFill(['locale' => $locale])->save();
        }

        return back();
    }
}
