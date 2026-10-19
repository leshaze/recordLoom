{{-- Records that are probably the same as the one being entered; saving needs the checkbox then. --}}
@if ($duplicates->isNotEmpty())
    <div class="alert alert-warning mb-3" role="alert">
        <div class="fw-semibold mb-2"><i class="bi bi-exclamation-triangle"></i> {{ __('Diese Platte ist vielleicht schon in deiner Sammlung:') }}</div>
        <ul class="list-unstyled mb-2">
            @foreach ($duplicates as ['record' => $duplicate, 'reasons' => $reasons])
                <li class="d-flex align-items-center gap-2 mb-1">
                    <x-cover :record="$duplicate" :size="32" class="flex-shrink-0" />
                    <span style="min-width: 0;">
                        <a href="{{ route('records.show', $duplicate) }}" target="_blank" class="fw-semibold">{{ $duplicate->title }}</a>
                        · {{ $duplicate->artist->name }} · {{ $duplicate->kind }}
                        @if ($duplicate->sold) <span class="badge text-bg-secondary">{{ __('Verkauft') }}</span>
                        @elseif ($duplicate->lost) <span class="badge text-bg-danger">{{ __('Verloren') }}</span> @endif
                        <span class="small">({{ collect($reasons)->map(fn ($reason) => __($reason))->implode(', ') }})</span>
                    </span>
                </li>
            @endforeach
        </ul>
        <div class="form-check mb-0">
            <input class="form-check-input" type="checkbox" name="confirm_duplicate" value="1" id="confirm_duplicate" @checked(old('confirm_duplicate'))>
            <label class="form-check-label" for="confirm_duplicate">{{ __('Trotzdem als weitere Platte speichern') }}</label>
        </div>
    </div>
@endif
