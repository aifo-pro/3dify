<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Payout;
use App\Services\PayoutService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
        ]);
    }

    public function store(Request $request, PayoutService $payouts)
    {
        $author = $request->user();
        $available = $payouts->availableBalance($author);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:'.PayoutService::MIN_PAYOUT_AMOUNT, 'max:'.max($available, PayoutService::MIN_PAYOUT_AMOUNT)],
            'method' => ['required', 'string', Rule::in(array_keys(Payout::METHODS))],
            'details' => ['required', 'string', 'max:2000'],
        ], [
            'amount.max' => __('Сума перевищує доступний баланс :max грн.', ['max' => number_format($available, 2)]),
            'amount.min' => __('Мінімальна сума виплати — :min грн.', ['min' => number_format(PayoutService::MIN_PAYOUT_AMOUNT, 2)]),
        ]);

        $payouts->requestPayout($author, (float) $data['amount'], $data['method'], $data['details']);

        return redirect()->route('author.payouts')->with('status', __('Заявка на виплату створена. Адміністратор перевірить її найближчим часом.'));
    }
}
