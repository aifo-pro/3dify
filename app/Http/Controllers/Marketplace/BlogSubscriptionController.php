<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\BlogSubscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BlogSubscriptionController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'locale' => ['nullable', 'in:uk,en'],
        ]);

        $email = mb_strtolower($data['email']);
        $subscriber = BlogSubscriber::firstOrNew(['email' => $email]);
        if (! $subscriber->exists || ! $subscriber->unsubscribe_token) {
            $subscriber->unsubscribe_token = Str::random(48);
        }
        $subscriber->fill([
            'locale' => $data['locale'] ?? app()->getLocale(),
            'is_active' => true,
            'verified_at' => now(),
        ]);
        $subscriber->save();

        return back()->with('status', __('blog.subscribe_success'));
    }

    public function unsubscribe(string $token)
    {
        $subscriber = BlogSubscriber::where('unsubscribe_token', $token)->firstOrFail();
        $subscriber->update(['is_active' => false]);

        return redirect()->route('blog.index')->with('status', __('blog.unsubscribe_success'));
    }
}
