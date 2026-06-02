<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomOrderMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'body' => ['nullable', 'string', 'max:12000', 'required_without:files'],
            'files' => ['nullable', 'array', 'max:10'],
            'files.*' => ['file', 'max:51200', 'mimes:jpg,jpeg,png,webp,gif,stl,obj,glb,gltf,3mf,zip,pdf,txt'],
        ];
    }
}
