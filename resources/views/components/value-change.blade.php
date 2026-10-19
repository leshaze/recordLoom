{{-- Gain or loss as "+35,00 € (+70 %)" with an arrow, so it does not depend on the color alone. --}}
@props(['change'])

@if ($change)
    @php
        $amount = $change['amount'];
        $sign = $amount > 0 ? '+' : ($amount < 0 ? '−' : '±');
        $percent = $change['percent'] === null ? null : number_format(abs($change['percent']), 1, app()->getLocale() === 'de' ? ',' : '.', '');
    @endphp
    <span {{ $attributes->class(['text-nowrap', 'text-success' => $amount > 0, 'text-danger' => $amount < 0, 'text-body-secondary' => $amount == 0]) }}>
        <i @class(['bi', 'bi-arrow-up-right' => $amount > 0, 'bi-arrow-down-right' => $amount < 0, 'bi-arrow-right' => $amount == 0]) aria-hidden="true"></i>
        {{ $sign }}{{ \App\Support\Format::euro(abs($amount)) }}@if ($percent !== null) ({{ $sign }}{{ $percent }} %)@endif
    </span>
@endif
