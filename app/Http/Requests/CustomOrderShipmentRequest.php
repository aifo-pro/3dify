<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
}
