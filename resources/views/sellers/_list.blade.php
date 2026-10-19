{{-- Sellers with the number of records they are interested in and the sum of their offers. --}}
<div class="list-group list-group-flush">
    @foreach ($sellers as $seller)
        <a href="{{ route('sellers.show', $seller) }}" class="list-group-item list-group-item-action d-flex align-items-center gap-2">
            <span class="flex-grow-1 text-break">{{ $seller->name }}</span>
            @if ($seller->interest_count > 0)
                <span class="badge text-bg-warning fw-normal"><i class="bi bi-star-fill"></i> {{ trans_choice(':count Platte|:count Platten', $seller->interest_count) }}</span>
                @if ($seller->offer_sum > 0)
                    <span class="small text-body-secondary text-nowrap" title="{{ __('Summe der Preisvorschläge') }}">{{ \App\Support\Format::euro($seller->offer_sum) }}</span>
                @endif
            @else
                <span class="small text-body-secondary">{{ __('kein Interesse') }}</span>
            @endif
        </a>
    @endforeach
</div>
