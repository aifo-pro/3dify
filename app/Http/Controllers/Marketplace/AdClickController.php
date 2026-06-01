<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use Illuminate\Support\Facades\Cache;

class AdClickController extends Controller
{
    public function click(Advertisement $ad)
    {
        $ad->increment('clicks');

        // Bust cache so stats update
        Cache::forget("ads.grid.catalog");
        Cache::forget("ads.grid.category");
        Cache::forget("ads.grid.home");
        Cache::forget("ads.grid.search");

        return redirect()->away($ad->target_url);
    }

    public function impression(Advertisement $ad)
    {
        $ad->increment('impressions');
        return response()->noContent();
    }
}
