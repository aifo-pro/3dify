<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Services\ReferralService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReferralController extends Controller
{
    public function __invoke(Request $request, ReferralService $referral)
    {
        $user = $request->user();
        $code = $referral->getOrCreateCode($user);
        $url  = $referral->referralUrl($user);

        $rewards = DB::table('referral_rewards')
            ->where('referrer_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        $totalEarned = DB::table('referral_rewards')
            ->where('referrer_id', $user->id)
            ->where('status', 'credited')
            ->sum('amount');

        $referralsCount = DB::table('users')
            ->where('referred_by', $user->id)
            ->count();

        return view('marketplace.referral', compact('code', 'url', 'rewards', 'totalEarned', 'referralsCount'));
    }
}
