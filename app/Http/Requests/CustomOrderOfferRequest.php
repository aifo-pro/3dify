<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomOrderOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'price' => ['required', 'numeric', 'min:1', 'max:999999'],
            'delivery_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'offer_description' => ['required', 'string', 'min:10', 'max:8000'],
            'offer_terms' => ['nullable', 'string', 'max:8000'],
            'milestones' => ['nullable', 'array', 'max:10'],
            'milestones.*' => ['nullable', 'string', 'max:180'],
        ];
    }
}
