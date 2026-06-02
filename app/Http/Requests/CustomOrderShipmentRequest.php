<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CustomOrderShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'carrier' => ['required', 'string', 'max:120'],
            'tracking_number' => ['required', 'string', 'max:160'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $order = $this->route('customOrder');

                if ($order && $order->type !== \App\Models\CustomOrder::TYPE_PRINT_SERVICE) {
                    $validator->errors()->add('carrier', __('custom_orders.errors.shipment_only_for_print'));
                }
            },
        ];
    }
}
