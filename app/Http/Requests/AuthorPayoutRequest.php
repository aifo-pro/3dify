<?php

namespace App\Http\Requests;

use App\Models\Payout;
use App\Services\PayoutService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AuthorPayoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasApprovedKyc() === true;
    }

    public function rules(): array
    {
        $available = app(PayoutService::class)->availableBalance($this->user());

        return [
            'amount' => ['required', 'numeric', 'min:'.PayoutService::MIN_PAYOUT_AMOUNT, 'max:'.max($available, PayoutService::MIN_PAYOUT_AMOUNT)],
            'method' => ['required', 'string', Rule::in(array_keys(Payout::METHODS))],
            'details' => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        $available = app(PayoutService::class)->availableBalance($this->user());

        return [
            'amount.max' => __('kyc.payout.amount_max', ['max' => number_format($available, 2)]),
            'amount.min' => __('kyc.payout.amount_min', ['min' => number_format(PayoutService::MIN_PAYOUT_AMOUNT, 2)]),
        ];
    }

    protected function failedAuthorization()
    {
        abort(403, __('kyc.payout.blocked'));
    }
}
