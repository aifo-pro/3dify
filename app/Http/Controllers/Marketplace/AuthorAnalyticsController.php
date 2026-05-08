<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Services\AuthorAnalytics;
use Illuminate\Http\Request;

class AuthorAnalyticsController extends Controller
{
    public function index(Request $request, AuthorAnalytics $analytics)
    {
        $author = $request->user();

        $days = (int) $request->input('days', 30);
        if (! in_array($days, [7, 14, 30, 90], true)) {
            $days = 30;
        }

        return view('marketplace.author.analytics', [
            'days' => $days,
            'kpis' => $analytics->kpis($author, $days),
            'series' => $analytics->timeSeries($author, $days),
            'top' => $analytics->topProducts($author, $days, 6),
            'totalProducts' => $author->products()->count(),
            'publishedProducts' => $author->products()->where('status', 'published')->count(),
        ]);
    }
}
