<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($userId),
            ],
            'display_name' => ['nullable', 'string', 'max:120'],
            'username' => [
                'nullable',
                'string',
                'max:60',
                'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique(User::class)->ignore($userId),
            ],
            'bio_uk' => ['nullable', 'string', 'max:3000'],
            'bio_en' => ['nullable', 'string', 'max:3000'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'telegram_url' => ['nullable', 'url', 'max:255'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'youtube_url' => ['nullable', 'url', 'max:255'],
            'github_url' => ['nullable', 'url', 'max:255'],
            'twitter_url' => ['nullable', 'url', 'max:255'],
            'location' => ['nullable', 'string', 'max:120'],
            'country_code' => ['nullable', 'string', 'size:2', Rule::in(array_keys(config('countries', [])))],
            'city' => ['nullable', 'string', 'max:120'],
            'contact_enabled' => ['nullable', 'boolean'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }
}
