<?php

namespace App\Http\Requests;

class UpdateRecordRequest extends StoreRecordRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('record'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'kind' => ['nullable', 'in:LP,CD'],
            'sold_on' => ['nullable', 'date'],
            'remove_cover' => ['nullable', 'boolean'],
            'sold_to' => ['nullable', 'string', 'max:255'],
            'sold_price' => ['nullable', 'numeric', 'min:0'],
        ]);
    }
}
