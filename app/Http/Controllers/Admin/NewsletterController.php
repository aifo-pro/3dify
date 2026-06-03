<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\NewsletterBlastMailable;
use App\Models\NewsletterBlast;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\NewsletterTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class NewsletterController extends Controller
{
    public function index(Request $request, NewsletterTemplateService $templates)
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

        $audienceCounts = [
            'all_subscribers' => $totals['active'],
            'authors' => NewsletterSubscriber::whereNull('unsubscribed_at')
                ->whereIn('email', User::where('role', 'author')->pluck('email'))
                ->count(),
            'buyers' => NewsletterSubscriber::whereNull('unsubscribed_at')
                ->whereIn('email', User::has('orders')->pluck('email'))
                ->count(),
        ];

        return view('admin.newsletter.index', [
            'subs' => $subs,
            'blasts' => $blasts,
            'totals' => $totals,
            'q' => $q,
            'status' => $status,
            'templates' => $templates->catalog(),
            'audienceCounts' => $audienceCounts,
        ]);
    }

    public function blastForm()
    {
        return redirect()->route('admin.newsletter');
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
            'body' => ['required', 'string', 'max:50000'],
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

    /**
     * Return a fully-composed template (subject + HTML body) for the admin UI.
     * Called via fetch when the admin clicks a template card.
     */
    public function template(string $key, NewsletterTemplateService $templates): JsonResponse
    {
        $payload = $templates->compose($key);

        return response()->json($payload);
    }

    /**
     * Render the email preview (full wrapper + body) into raw HTML so the
     * admin can embed it in an iframe without leaking app session/auth.
     */
    public function preview(Request $request)
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:50000'],
        ]);

        $blast = new NewsletterBlast([
            'subject' => $data['subject'],
            'body' => $data['body'],
            'audience' => 'preview',
            'recipients_count' => 0,
            'sent_at' => now(),
        ]);

        $subscriber = new NewsletterSubscriber([
            'email' => 'preview@'.parse_url(config('app.url'), PHP_URL_HOST),
            'unsubscribe_token' => 'preview',
        ]);

        return response(view('emails.newsletter-blast', [
            'blast' => $blast,
            'subscriber' => $subscriber,
            'unsubscribeUrl' => '#',
        ])->render(), 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    private function subscribersForUsers($emails)
    {
        $emails = collect($emails)->filter()->unique()->all();

        return NewsletterSubscriber::whereIn('email', $emails)->whereNull('unsubscribed_at')->get();
    }
}
