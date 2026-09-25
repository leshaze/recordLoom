<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateLabelRequest extends StoreLabelRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'label_name' => ['required', 'string', 'max:255', Rule::unique('labels', 'name')->ignore($this->route('label'))],
        ]);
    }
}
