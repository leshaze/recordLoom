<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdatePlatformRequest extends StorePlatformRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'platform_name' => ['required', 'string', 'max:255', Rule::unique('platforms', 'name')->ignore($this->route('platform'))],
        ]);
    }
}
