<?php

namespace App\Http\Requests;

use App\Models\CustomOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomOrderStoreRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->input('type') === CustomOrder::TYPE_MODEL_CREATION) {
            $this->merge([
                'quantity' => null,
                'dimensions' => null,
                'material' => null,
                'color' => null,
                'delivery_service' => null,
                'delivery_address' => null,
                'extra_comment' => null,
            ]);
        }
    }

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'author_id' => ['nullable', 'integer', 'exists:users,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'type' => ['required', Rule::in([CustomOrder::TYPE_MODEL_CREATION, CustomOrder::TYPE_PRINT_SERVICE])],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['required', 'string', 'min:20', 'max:12000'],
            'budget_amount' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'budget_is_negotiable' => ['nullable', 'boolean'],
            'deadline_at' => ['nullable', 'date', 'after_or_equal:today'],
            'quantity' => ['nullable', 'required_if:type,'.CustomOrder::TYPE_PRINT_SERVICE, 'integer', 'min:1', 'max:10000'],
            'dimensions' => ['nullable', 'string', 'max:255'],
            'material' => ['nullable', 'string', 'max:120'],
            'color' => ['nullable', 'string', 'max:120'],
            'delivery_service' => ['nullable', 'string', 'max:120'],
            'delivery_address' => ['nullable', 'string', 'max:2000'],
            'extra_comment' => ['nullable', 'string', 'max:4000'],
            'files' => ['nullable', 'array', 'max:10'],
            'files.*' => ['file', 'max:51200', 'mimes:jpg,jpeg,png,webp,gif,stl,obj,glb,gltf,3mf,zip,pdf,txt'],
        ];
    }
}
