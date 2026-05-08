<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\SavedSearch;
use App\Notifications\SavedSearchMatchesNotification;
use Illuminate\Console\Command;

class RunSavedSearches extends Command
{
    protected $signature = 'saved-searches:run {--limit=50 : Max searches per run}';

    protected $description = 'Notify users about new products matching their saved searches.';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        SavedSearch::query()
            ->where('notify_email', true)
            ->with('user')
            ->orderBy('last_notified_at')
            ->limit($limit)
            ->get()
            ->each(function (SavedSearch $search) {
                $since = $search->last_notified_at ?? now()->subDays(7);
                $matches = $search->apply(
                    Product::query()
                        ->where('status', 'published')
                        ->where('published_at', '>', $since)
                )->latest('published_at')->limit(20)->get();

                if ($matches->isEmpty()) {
                    $search->update(['last_notified_at' => now()]);
                    return;
                }

                $payload = $matches->map(fn (Product $p) => [
                    'title' => (string) $p->localized('title'),
                    'slug' => $p->slug,
                    'price' => $p->is_free ? null : ($p->display_price ?? null),
                ])->all();

                $search->user?->notify(new SavedSearchMatchesNotification($search, $payload));
                $search->update(['last_notified_at' => now()]);

                $this->info("Notified {$search->user?->email} about {$matches->count()} matches for «{$search->name}».");
            });

        return self::SUCCESS;
    }
}
