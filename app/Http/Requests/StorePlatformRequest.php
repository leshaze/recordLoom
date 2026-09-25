<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePlatformRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'platform_name' => ['required', 'string', 'max:255'],
            'url' => ['nullable', 'url:http,https', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
