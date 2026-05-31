<?php

namespace App\Http\Middleware;

use App\Services\ReferralService;
use Closure;
use Illuminate\Http\Request;

class CaptureReferral
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->filled('ref') && ! $request->user()) {
            $referrer = app(ReferralService::class)->resolveReferrer($request->input('ref'));
            if ($referrer) {
                session(['referral_code' => $request->input('ref'), 'referral_user_id' => $referrer->id]);
            }
        }

        return $next($request);
    }
}
