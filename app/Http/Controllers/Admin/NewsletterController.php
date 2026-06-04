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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class NewsletterController extends Controller
{
    public function index(Request $request, NewsletterTemplateService $templates)
    {
        $this->syncUsersIntoNewsletterRecipients();

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
            'users' => User::whereNotNull('email')->count(),
        ];

        $audienceCounts = [
            'all_subscribers' => $totals['active'],
            'authors' => $this->subscribersForUsers(User::where('role', 'author')->pluck('email'))->count(),
            'buyers' => $this->subscribersForUsers(User::has('orders')->pluck('email'))->count(),
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
        $this->syncUsersIntoNewsletterRecipients();

        $data = $request->validate([
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:50000'],
            'audience' => ['required', Rule::in(['all_subscribers', 'authors', 'buyers'])],
            'template_key' => ['nullable', 'string', 'max:100'],
            'confirm' => ['required', 'accepted'],
        ]);

        $recipients = $this->recipientsForAudience($data['audience']);

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

        $audit->record('newsletter.blast', $blast, [
            'recipients' => $recipients->count(),
            'template_key' => $data['template_key'] ?? null,
        ]);

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

    private function recipientsForAudience(string $audience): Collection
    {
        return match ($audience) {
            'authors' => $this->subscribersForUsers(User::where('role', 'author')->pluck('email')),
            'buyers' => $this->subscribersForUsers(User::has('orders')->pluck('email')),
            default => NewsletterSubscriber::query()
                ->whereNull('unsubscribed_at')
                ->get()
                ->unique('email')
                ->values(),
        };
    }

    private function subscribersForUsers($emails): Collection
    {
        $emails = collect($emails)
            ->filter()
            ->map(fn ($email) => mb_strtolower(trim((string) $email)))
            ->unique()
            ->all();

        return NewsletterSubscriber::query()
            ->whereIn('email', $emails)
            ->whereNull('unsubscribed_at')
            ->get()
            ->unique('email')
            ->values();
    }

    private function syncUsersIntoNewsletterRecipients(): void
    {
        User::query()
            ->whereNotNull('email')
            ->select(['id', 'name', 'email', 'locale', 'email_verified_at'])
            ->orderBy('id')
            ->chunkById(500, function ($users): void {
                foreach ($users as $user) {
                    $email = mb_strtolower(trim((string) $user->email));
                    if ($email === '') {
                        continue;
                    }

                    $subscriber = NewsletterSubscriber::firstOrNew(['email' => $email]);

                    if (! $subscriber->exists) {
                        $subscriber->name = $user->name;
                        $subscriber->locale = $user->locale ?: app()->getLocale();
                        $subscriber->source = 'user';
                        $subscriber->verified_at = $user->email_verified_at;
                        $subscriber->save();

                        continue;
                    }

                    $dirty = false;

                    if (! $subscriber->name && $user->name) {
                        $subscriber->name = $user->name;
                        $dirty = true;
                    }

                    if (! $subscriber->locale && $user->locale) {
                        $subscriber->locale = $user->locale;
                        $dirty = true;
                    }

                    if (! $subscriber->verified_at && $user->email_verified_at) {
                        $subscriber->verified_at = $user->email_verified_at;
                        $dirty = true;
                    }

                    if (! str_contains((string) $subscriber->source, 'user')) {
                        $subscriber->source = trim(((string) $subscriber->source).'+user', '+');
                        $dirty = true;
                    }

                    if ($dirty) {
                        $subscriber->save();
                    }
                }
            });
    }
}
