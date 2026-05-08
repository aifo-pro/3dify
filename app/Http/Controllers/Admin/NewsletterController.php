<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\NewsletterBlastMailable;
use App\Models\NewsletterBlast;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class NewsletterController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->input('q');
        $status = $request->input('status', 'active');

        $subs = NewsletterSubscriber::query()
            ->when($q, fn ($w) => $w->where('email', 'like', "%{$q}%")->orWhere('name', 'like', "%{$q}%"))
            ->when($status === 'active', fn ($w) => $w->whereNull('unsubscribed_at'))
            ->when($status === 'unsubscribed', fn ($w) => $w->whereNotNull('unsubscribed_at'))
            ->latest()->paginate(50)->withQueryString();

        $blasts = NewsletterBlast::with('createdBy:id,name')->latest()->limit(10)->get();

        $totals = [
            'active' => NewsletterSubscriber::whereNull('unsubscribed_at')->count(),
            'unsubscribed' => NewsletterSubscriber::whereNotNull('unsubscribed_at')->count(),
            'this_month' => NewsletterSubscriber::whereNull('unsubscribed_at')->where('created_at', '>=', now()->startOfMonth())->count(),
            'authors' => User::where('role', 'author')->count(),
        ];

        return view('admin.newsletter.index', compact('subs', 'blasts', 'totals', 'q', 'status'));
    }

    public function destroy(NewsletterSubscriber $subscriber, AuditLogger $audit)
    {
        $audit->record('newsletter.delete', $subscriber);
        $subscriber->delete();
        return back()->with('status', __('Видалено.'));
    }

    public function blast(Request $request, AuditLogger $audit)
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:20000'],
            'audience' => ['required', Rule::in(['all_subscribers', 'authors', 'buyers'])],
            'confirm' => ['required', 'accepted'],
        ]);

        $recipients = match ($data['audience']) {
            'all_subscribers' => NewsletterSubscriber::whereNull('unsubscribed_at')->get(),
            'authors' => $this->subscribersForUsers(User::where('role', 'author')->pluck('email')),
            'buyers' => $this->subscribersForUsers(User::has('orders')->pluck('email')),
        };

        $blast = NewsletterBlast::create([
            'subject' => $data['subject'],
            'body' => $data['body'],
            'audience' => $data['audience'],
            'recipients_count' => $recipients->count(),
            'sent_at' => now(),
            'created_by' => auth()->id(),
        ]);

        foreach ($recipients as $sub) {
            Mail::to($sub->email)->queue(new NewsletterBlastMailable($blast, $sub));
        }

        $audit->record('newsletter.blast', $blast, ['recipients' => $recipients->count()]);

        return back()->with('status', __('Розсилку поставлено в чергу: :n листів.', ['n' => $recipients->count()]));
    }

    private function subscribersForUsers($emails)
    {
        $emails = collect($emails)->filter()->unique()->all();
        return NewsletterSubscriber::whereIn('email', $emails)->whereNull('unsubscribed_at')->get();
    }
}
