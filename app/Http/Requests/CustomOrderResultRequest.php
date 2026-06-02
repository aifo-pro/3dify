<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomOrderResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'result_comment' => ['nullable', 'string', 'max:4000'],
            'result_files' => ['required', 'array', 'min:1', 'max:20'],
            'result_files.*' => [
                'file',
                'max:102400',
                'mimes:stl,obj,step,stp,3mf,zip,glb,gltf,fbx,blend,dae,3ds,ply,txt,pdf,jpg,jpeg,png,webp',
            ],
        ];
    }
}
