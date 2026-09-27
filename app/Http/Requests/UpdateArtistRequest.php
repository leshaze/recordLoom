<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateArtistRequest extends StoreArtistRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'artist_name' => ['required', 'string', 'max:255', Rule::unique('artists', 'name')->ignore($this->route('artist'))],
        ]);
    }
}
