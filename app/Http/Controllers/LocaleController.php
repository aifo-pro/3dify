<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class LocaleController extends Controller
{
    public function switch(string $locale): RedirectResponse
    {
        abort_unless(in_array($locale, ['uk', 'en'], true), 404);

        session(['locale' => $locale]);

        if (auth()->check()) {
            auth()->user()->forceFill(['locale' => $locale])->save();
        }

        return back();
    }
}
