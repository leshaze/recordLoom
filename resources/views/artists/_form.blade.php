{{-- Shared by create and edit. $artist is null when creating. --}}
<p class="small text-body-secondary mb-3">{!! __('Felder mit :star sind Pflichtfelder.', ['star' => '<span class="text-danger">*</span>']) !!}</p>
<div class="mb-3">
    <label for="artist_name" class="form-label">{{ __('Name') }} <span class="text-danger">*</span></label>
    <input type="text" name="artist_name" id="artist_name" maxlength="255" required autofocus
        class="form-control @error('artist_name') is-invalid @enderror" value="{{ old('artist_name', $artist?->name) }}">
    @error('artist_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
<div class="mb-3">
    <label for="description" class="form-label">{{ __('Beschreibung') }}</label>
    <textarea name="description" id="description" rows="4" maxlength="5000"
        class="form-control @error('description') is-invalid @enderror">{{ old('description', $artist?->description) }}</textarea>
    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
