@props(['record', 'interested' => false, 'label' => null])

<form method="POST" action="{{ route('interests.update', $record) }}" class="d-inline">
    @csrf
    @method('PUT')
    <input type="hidden" name="interested" value="0">
    <div class="form-check form-check-inline m-0">
        <input class="form-check-input" type="checkbox" name="interested" value="1"
            id="interest-{{ $record->id }}" onchange="this.form.submit()" @checked($interested)>
        <label class="form-check-label" for="interest-{{ $record->id }}">{{ $label ?? __('Interesse') }}</label>
    </div>
</form>
