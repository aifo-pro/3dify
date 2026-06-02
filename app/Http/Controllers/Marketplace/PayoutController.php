<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuthorPayoutRequest;
use App\Models\Payout;
use App\Services\PayoutService;
use Illuminate\Http\Request;

class PayoutController extends Controller
{
    public function index(Request $request, PayoutService $payouts)
    {
        $author = $request->user();

        return view('marketplace.author.payouts', [
            'history' => $author->payouts()->latest('requested_at')->paginate(15),
            'available' => $payouts->availableBalance($author),
            'reserved' => $payouts->reservedTotal($author),
            'totalEarnings' => $payouts->totalEarnings($author),
            'salesCount' => $payouts->salesCount($author),
            'commission' => PayoutService::COMMISSION_PERCENT,
            'minimum' => PayoutService::MIN_PAYOUT_AMOUNT,
            'methods' => Payout::METHODS,
            'kycStatus' => $author->kyc_status ?: 'not_started',
            'kycVerification' => $author->latestKycVerification(),
            'kycApproved' => $author->hasApprovedKyc(),
        ]);
    }

    public function store(AuthorPayoutRequest $request, PayoutService $payouts)
    {
        $data = $request->validated();

        $payouts->requestPayout(
            $request->user(),
            (float) $data['amount'],
            $data['method'],
            $data['details']
        );

        return redirect()
            ->route('author.payouts')
            ->with('status', __('kyc.payout.created'));
    }
}
