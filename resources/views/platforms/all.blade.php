<x-app-layout>
    <div class="container">
        <div class="wrapper">
            <div class="card table-responsive">
                <div class="card-header">{{ __('Platforms') }}</div>
                @if (!empty($platforms) && $platforms->count())
                <table class="table table-sm small d-inline-flex">
                    <tr>
                        <th>Name</th>
                        <th>Summe LP</th>
                        <th>Summe CD</th>
                        <th>Description</th>
                        <th>URL</th>
                    </tr>
                    @foreach ($platforms as $platform)
                    <tr>
                        <td align="left">
                            <a href="{{ route('platforms.show', $platform->id) }}">{{ $platform->name }} </a>
                        </td>
                        <td style="text-align: center;">
                            @if ($records->where('platform_id', $platform->id)->where('kind', 'LP')->sum(function ($record) {
                            return $record->current_price;
                            }))
                            {{ $records->where('platform_id', $platform->id)->where('kind', 'LP')->sum(function ($record) {
                                                return $record->current_price;
                                        }) }} €
                            @endif
                        </td>
                        <td style="text-align: center;">
                            @if ($records->where('platform_id', $platform->id)->where('kind', 'CD')->sum(function ($record) {
                            return $record->current_price;
                            }))
                            {{ $records->where('platform_id', $platform->id)->where('kind', 'CD')->sum(function ($record) {
                                                return $record->current_price;
                                        }) }} €
                            @endif
                        </td>
                        <td>
                            @if ($platform->description)
                            {{ $platform->description }}
                            @endif
                        </td>
                        <td>
                            @if ($platform->safe_url)
                            <a href="{{ $platform->safe_url }}" target="_blank" rel="noopener noreferrer">{{ $platform->url }}</a>
                            @endif
                        </td>
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