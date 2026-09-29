{{-- Status and "Zusatzinfos" of a record, shown next to the cover. --}}
@if ($record->sold) <span class="badge text-bg-secondary">{{ __('Verkauft') }}</span> @endif
@if ($record->lost) <span class="badge text-bg-danger">{{ __('Verloren') }}</span> @endif
@if ($record->selling && ! $record->sold) <span class="badge text-bg-info">{{ __('Zum Verkauf vorgemerkt') }}</span> @endif
@foreach ($record->editions as $edition)
    <a href="{{ route('records.index', ['edition' => $edition->id]) }}" class="badge text-bg-dark border text-decoration-none">{{ $edition->label }}</a>
@endforeach
