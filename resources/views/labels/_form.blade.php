{{-- Shared by create and edit. $label is null when creating. --}}
<p class="small text-body-secondary mb-3">Felder mit <span class="text-danger">*</span> sind Pflichtfelder.</p>
<div class="mb-3">
    <label for="label_name" class="form-label">Name <span class="text-danger">*</span></label>
    <input type="text" name="label_name" id="label_name" maxlength="255" required autofocus
        class="form-control @error('label_name') is-invalid @enderror" value="{{ old('label_name', $label?->name) }}">
    @error('label_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
<div class="mb-3">
    <label for="description" class="form-label">Beschreibung</label>
    <textarea name="description" id="description" rows="4" maxlength="5000"
        class="form-control @error('description') is-invalid @enderror">{{ old('description', $label?->description) }}</textarea>
    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
