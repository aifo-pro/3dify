<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeliveryLookupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'carrier' => ['required', Rule::in(['nova_poshta', 'ukrposhta'])],
            'q' => ['nullable', 'string', 'max:160'],
            'city_ref' => ['nullable', 'string', 'max:255'],
        ];
    }
}
