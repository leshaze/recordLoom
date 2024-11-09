<x-app-layout>
    <div class="container">
        <div class="wrapper">
            <div class="card table-responsive">
                <div class="card-header">{{ __('Platforms') }}</div>
                @if (!empty($platforms) && $platforms->count())
                <table class="table table-sm small d-inline-flex">
                    <tr>
                        <th style="min-width:100px;">Name</th>
                        <th style="min-width:80px;">Summe LP</th>
                        <th style="min-width:80px;">Summe CD</th>
                        <th style="min-width:100px;">Description</th>
                        <th style="min-width:100px;">URL</th>
                    </tr>
                    @foreach ($platforms as $platform)
                    <tr>
                        <th align="left">
                            <a href="{{ route('platforms.show', $platform->id) }}">{{ $platform->name }} </a>
                        </th>
                        <th style="text-align: center;">
                            @if ($records->where('platform_id', $platform->id)->where('kind', 'LP')->sum(function ($record) {
                            return $record->current_price;
                            }))
                            {{ $records->where('platform_id', $platform->id)->where('kind', 'LP')->sum(function ($record) {
                                                return $record->current_price;
                                        }) }} €
                            @endif
                        </th>
                        <th style="text-align: center;">
                            @if ($records->where('platform_id', $platform->id)->where('kind', 'CD')->sum(function ($record) {
                            return $record->current_price;
                            }))
                            {{ $records->where('platform_id', $platform->id)->where('kind', 'CD')->sum(function ($record) {
                                                return $record->current_price;
                                        }) }} €
                            @endif
                        </th>
                        <th>
                            @if ($platform->description)
                            {{ $platform->description }}
                            @endif
                        </th>
                        <th>
                            @if ($platform->url)
                            <a href="{{ $platform->url }}" target="_blank">{{ $platform->url }}</a>
                            @endif
                        </th>
                    </tr>
                    @endforeach
                </table>
                @else
                There is no data yet.
                @endif
            </div>
            {{ $platforms->links('pagination::bootstrap-4') }}
        </div>
    </div>
</x-app-layout>