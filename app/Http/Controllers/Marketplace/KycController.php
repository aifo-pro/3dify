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
    public function show(Request $request, DiditKycService $kyc)
    {
        $user = $request->user();
        $verification = $user->latestKycVerification();

        // Fallback sync: if the webhook never arrived, pull the decision from
        // Didit directly so the user isn't stuck on "pending" forever.
        if ($verification && $verification->status === KycVerification::STATUS_PENDING) {
            try {
                if ($kyc->fetchAndSyncSession($verification)) {
                    $verification->refresh();
                }
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return view('marketplace.kyc.show', [
            'verification' => $verification,
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

    public function refresh(Request $request, DiditKycService $kyc)
    {
        $verification = $request->user()->latestKycVerification();

        if (! $verification) {
            return redirect()->route('kyc.show');
        }

        try {
            $changed = $kyc->fetchAndSyncSession($verification);
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()->route('kyc.show')->withErrors(['kyc' => __('kyc.errors.sync_failed')]);
        }

        $verification->refresh();

        if ($verification->status === KycVerification::STATUS_APPROVED) {
            return redirect()->route('author.payouts')->with('status', __('kyc.return.approved'));
        }

        return redirect()->route('kyc.show')->with(
            'status',
            $changed ? __('kyc.return.pending') : __('kyc.sync.no_change')
        );
    }

    public function returned(Request $request, DiditKycService $kyc)
    {
        $user = $request->user();
        $verification = $user->latestKycVerification();

        // The user just came back from Didit — try to pull the decision now
        // instead of waiting on the webhook.
        if ($verification && $verification->status === KycVerification::STATUS_PENDING) {
            try {
                $kyc->fetchAndSyncSession($verification);
                $verification->refresh();
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        if ($verification?->status === KycVerification::STATUS_APPROVED) {
            return redirect()->route('author.payouts')->with('status', __('kyc.return.approved'));
        }

        return redirect()->route('kyc.show')->with('status', __('kyc.return.pending'));
    }
}
