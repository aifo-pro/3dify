<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LeaderboardController extends Controller
{
    public function __invoke()
    {
        $topByDownloads = User::query()
            ->whereHas('products', fn ($q) => $q->where('status', 'published'))
            ->withSum(['products as total_downloads' => fn ($q) => $q->where('status', 'published')], 'downloads_count')
            ->withCount(['products as published_count' => fn ($q) => $q->where('status', 'published')])
            ->orderByDesc('total_downloads')
            ->take(20)
            ->get();

        $topByProducts = User::query()
            ->withCount(['products as published_count' => fn ($q) => $q->where('status', 'published')])
            ->having('published_count', '>', 0)
            ->orderByDesc('published_count')
            ->take(20)
            ->get();

        $topByFollowers = User::query()
            ->withCount('followers')
            ->having('followers_count', '>', 0)
            ->orderByDesc('followers_count')
            ->take(20)
            ->get();

        return view('marketplace.leaderboard', compact('topByDownloads', 'topByProducts', 'topByFollowers'));
    }
}
