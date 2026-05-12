<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AllowSocialCrawlers
{
    protected array $bots = [
        'facebookexternalhit',
        'Facebot',
        'Twitterbot',
        'TelegramBot',
        'LinkedInBot',
        'Pinterestbot',
        'redditbot',
        'Slackbot',
        'WhatsApp',
        'Discordbot',
    ];

    public function handle(Request $request, Closure $next)
    {
        $ua = $request->userAgent() ?? '';

        foreach ($this->bots as $bot) {
            if (stripos($ua, $bot) !== false) {
                // Skip session for crawlers to avoid issues
                config(['session.driver' => 'array']);
                break;
            }
        }

        return $next($request);
    }
}
