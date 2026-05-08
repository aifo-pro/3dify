<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:200'],
            'name' => ['nullable', 'string', 'max:100'],
            'source' => ['nullable', 'string', 'max:30'],
        ]);

        $sub = NewsletterSubscriber::firstOrNew(['email' => mb_strtolower($data['email'])]);
        if ($sub->exists && $sub->unsubscribed_at) {
            $sub->unsubscribed_at = null;
        }
        if (! $sub->exists) {
            $sub->name = $data['name'] ?? null;
            $sub->source = $data['source'] ?? 'footer';
            $sub->locale = app()->getLocale();
        }
        $sub->save();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('status', __('Дякуємо! Ви підписані на новини 3dify.'));
    }

    public function unsubscribe(Request $request, string $token)
    {
        $sub = NewsletterSubscriber::where('unsubscribe_token', $token)->firstOrFail();
        if (! $sub->unsubscribed_at) {
            $sub->update(['unsubscribed_at' => now()]);
        }
        return view('newsletter.unsubscribed', ['email' => $sub->email]);
    }
}
