<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\KycVerification;
use App\Services\DiditKycService;
use Illuminate\Http\Request;
use Illuminate\Http\Client\RequestException;
use RuntimeException;

class KycController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user()->load(['kycVerifications' => fn ($query) => $query->latest()]);

        return view('marketplace.kyc.show', [
            'verification' => $user->latestKycVerification(),
        ]);
    }

    public function start(Request $request, DiditKycService $kyc)
    {
        $user = $request->user();

        if ($user->hasApprovedKyc()) {
            return redirect()->route('author.payouts')->with('status', __('kyc.already_verified'));
        }

        try {
            $verification = $kyc->createSession($user);
        } catch (RequestException|RuntimeException $exception) {
            report($exception);

            return back()->withErrors(['kyc' => __('kyc.errors.start_failed')]);
        }

        return redirect()->away($verification->verification_url);
    }

    public function returned(Request $request)
    {
        $user = $request->user();
        $verification = $user->latestKycVerification();

        if ($verification?->status === KycVerification::STATUS_APPROVED) {
            return redirect()->route('author.payouts')->with('status', __('kyc.return.approved'));
        }

        return redirect()->route('kyc.show')->with('status', __('kyc.return.pending'));
    }
}
