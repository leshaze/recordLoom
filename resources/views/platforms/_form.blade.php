{{-- Shared by create and edit. $platform is null when creating. --}}
<p class="small text-body-secondary mb-3">{!! __('Felder mit :star sind Pflichtfelder.', ['star' => '<span class="text-danger">*</span>']) !!}</p>
<div class="mb-3">
    <label for="platform_name" class="form-label">{{ __('Name') }} <span class="text-danger">*</span></label>
    <input type="text" name="platform_name" id="platform_name" maxlength="255" required autofocus
        class="form-control @error('platform_name') is-invalid @enderror" value="{{ old('platform_name', $platform?->name) }}">
    @error('platform_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
<div class="mb-3">
    <label for="url" class="form-label">{{ __('URL') }}</label>
    <input type="url" name="url" id="url" maxlength="255" placeholder="https://…"
        class="form-control @error('url') is-invalid @enderror" value="{{ old('url', $platform?->url) }}">
    @error('url') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
<div class="mb-3">
    <label for="description" class="form-label">{{ __('Beschreibung') }}</label>
    <textarea name="description" id="description" rows="4" maxlength="5000"
        class="form-control @error('description') is-invalid @enderror">{{ old('description', $platform?->description) }}</textarea>
    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
