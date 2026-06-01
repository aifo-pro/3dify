<?php

namespace App\Services;

use App\Models\Advertisement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class AdInjector
{
    /**
     * Load active grid ads for a page, cached for 5 minutes.
     */
    public function gridAds(string $page): Collection
    {
        return Cache::remember("ads.grid.{$page}", 300, fn () =>
            Advertisement::forPage($page)
                ->where('ad_type', 'grid')
                ->orderBy('id')
                ->get()
        );
    }

    /**
     * Inject ad objects into a product collection at regular intervals.
     *
     * Each ad has grid_every = N → insert after position N, 2N, 3N...
     * Returns a flat array of items, each either a Product or an Advertisement.
     */
    public function injectIntoGrid(iterable $products, string $page): array
    {
        $ads  = $this->gridAds($page);
        $items = collect($products)->values()->all();

        if ($ads->isEmpty()) {
            return $items;
        }

        $result  = [];
        $adIndex = 0;
        $total   = count($items);

        foreach ($items as $i => $product) {
            $result[] = $product;

            // After every N products insert the next ad (cycling through all active ads)
            $ad = $ads[$adIndex % $ads->count()];
            if (($i + 1) % $ad->grid_every === 0 && $adIndex < $ads->count() * 3) {
                $result[] = $ad;
                $adIndex++;
            }
        }

        return $result;
    }

    /**
     * Load a single banner or sidebar ad for a page.
     */
    public function bannerAd(string $page): ?Advertisement
    {
        return Cache::remember("ads.banner.{$page}", 300, fn () =>
            Advertisement::forPage($page)
                ->where('ad_type', 'banner')
                ->inRandomOrder()
                ->first()
        );
    }

    public function sidebarAd(string $page): ?Advertisement
    {
        return Cache::remember("ads.sidebar.{$page}", 300, fn () =>
            Advertisement::forPage($page)
                ->where('ad_type', 'sidebar')
                ->inRandomOrder()
                ->first()
        );
    }
}
