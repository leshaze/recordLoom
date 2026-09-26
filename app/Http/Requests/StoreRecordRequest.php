<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRecordRequest extends FormRequest
{
    /**
     * Price fields that may be entered with a decimal comma (e.g. "12,50").
     */
    protected const PRICE_FIELDS = ['current_price', 'buy_price', 'sold_price'];

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'kind' => 'Art',
            'artist_name' => 'Künstler',
            'title' => 'Titel',
            'label_name' => 'Label',
            'current_price' => 'Aktueller Preis',
            'buy_price' => 'Kaufpreis',
            'sold_price' => 'Verkaufspreis',
            'release_year' => 'Erscheinungsjahr',
            'reissue_year' => 'Jahr der Neuauflage',
            'sold_on' => 'Verkaufsdatum',
            'cover' => 'Cover',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $prices = [];
        foreach (self::PRICE_FIELDS as $field) {
            if (is_string($this->input($field))) {
                $prices[$field] = str_replace(',', '.', trim($this->input($field)));
            }
        }

        $this->merge($prices);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'kind' => ['required', 'in:LP,CD'],
            'artist_name' => ['required', 'string', 'max:255'],
            'artist_id' => ['nullable', 'integer', 'exists:artists,id'],
            'title' => ['required', 'string', 'max:255'],
            'label_name' => ['required', 'string', 'max:255'],
            'label_id' => ['nullable', 'integer', 'exists:labels,id'],
            'country_name' => ['nullable', 'string', 'max:255'],
            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'platform' => ['nullable', 'string', 'max:255'],
            'platform_id' => ['nullable', 'integer', 'exists:platforms,id'],
            'catalog_number' => ['nullable', 'string', 'max:255'],
            'matrix_number' => ['nullable', 'string', 'max:255'],
            'barcode' => ['nullable', 'string', 'max:255'],
            'archive_number' => ['nullable', 'string', 'max:255'],
            'release_year' => ['nullable', 'integer', 'between:1900,'.(now()->year + 1)],
            'reissue_year' => ['nullable', 'integer', 'between:1900,'.(now()->year + 1)],
            'grading_media' => ['nullable', 'integer', 'between:0,100'],
            'grading_cover' => ['nullable', 'integer', 'between:0,100'],
            'current_price' => ['nullable', 'numeric', 'min:0'],
            'buy_price' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:5000'],
            'editions' => ['nullable', 'array'],
            'editions.*' => ['integer', 'exists:editions,id'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:8192'],
        ];
    }
}
