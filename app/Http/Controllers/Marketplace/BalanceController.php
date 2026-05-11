<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\AccountBalanceTransaction;
use App\Services\AccountBalanceService;
use Illuminate\Http\Request;

class BalanceController extends Controller
{
    public function __invoke(Request $request, AccountBalanceService $balances)
    {
        $user = $request->user();
        $currency = AccountBalanceService::DEFAULT_CURRENCY;

        $transactions = $user->accountBalanceTransactions()
            ->with(['order', 'refundRequest.order'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $totals = [
            'available' => $balances->availableBalance($user, $currency),
            'credited' => (float) $user->accountBalanceTransactions()
                ->where('currency', $currency)
                ->where('type', AccountBalanceTransaction::TYPE_CREDIT)
                ->where('status', AccountBalanceTransaction::STATUS_SETTLED)
                ->sum('amount'),
            'spent' => (float) $user->accountBalanceTransactions()
                ->where('currency', $currency)
                ->where('type', AccountBalanceTransaction::TYPE_DEBIT)
                ->where('status', AccountBalanceTransaction::STATUS_SETTLED)
                ->sum('amount'),
            'reserved' => (float) $user->accountBalanceTransactions()
                ->where('currency', $currency)
                ->where('type', AccountBalanceTransaction::TYPE_DEBIT)
                ->where('status', AccountBalanceTransaction::STATUS_PENDING)
                ->sum('amount'),
        ];

        return view('marketplace.balance.index', compact('transactions', 'totals', 'currency'));
    }
}
