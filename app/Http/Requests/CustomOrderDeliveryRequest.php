<?php

namespace App\Http\Requests;

use App\Models\CustomOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CustomOrderDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'delivery_service' => ['required', Rule::in(['nova_poshta', 'ukrposhta'])],
            'delivery_city' => ['required', 'string', 'max:160'],
            'delivery_city_ref' => ['nullable', 'string', 'max:255'],
            'delivery_warehouse_ref' => ['nullable', 'string', 'max:255'],
            'delivery_address' => ['required', 'string', 'max:2000'],
            'extra_comment' => ['nullable', 'string', 'max:4000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $order = $this->route('customOrder');

                if (! $order instanceof CustomOrder) {
                    return;
                }

                if (! $order->isPrintService()) {
                    $validator->errors()->add('delivery_service', __('custom_orders.errors.delivery_only_for_print'));
                }

                if (! in_array($order->status, [CustomOrder::STATUS_WAITING_BUYER_ACCEPT, CustomOrder::STATUS_WAITING_PAYMENT], true)) {
                    $validator->errors()->add('delivery_service', __('custom_orders.errors.delivery_not_available'));
                }
            },
        ];
    }
}
