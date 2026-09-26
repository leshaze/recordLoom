@php
    $messages = collect([
        ['success', 'bi-check-circle', session('info')],
        ['warning', 'bi-exclamation-triangle', session('warning')],
        ['danger', 'bi-x-circle', session('error')],
        ['danger', 'bi-x-circle', $errors->any() ? 'Bitte die markierten Eingaben prüfen.' : null],
    ])->filter(fn ($message) => filled($message[2]));
@endphp

@if ($messages->isNotEmpty() || session('import_errors'))
    <div class="toast-container position-fixed top-0 end-0 p-3" style="margin-top: 3.5rem; z-index: 1090;">
        @foreach ($messages as [$color, $icon, $text])
            <div class="toast align-items-center text-bg-{{ $color }} border-0" role="alert" aria-live="assertive"
                aria-atomic="true" data-bs-delay="{{ $color === 'success' ? 5000 : 10000 }}">
                <div class="d-flex">
                    <div class="toast-body"><i class="bi {{ $icon }} me-1"></i> {{ $text }}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                        aria-label="Schließen"></button>
                </div>
            </div>
        @endforeach
    </div>
@endif
