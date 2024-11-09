<x-app-layout>
    <div class="container">
        <div class="wrapper">
            <div class="card table-responsive">
                <div class="card-header">{{ __('Label') }}</div>
                @if (!empty($labels) && $labels->count())
                <table class="table table-sm small d-inline-flex">
                    <tr>
                        <th style="min-width:100px;">Name</th>
                        <th style="min-width:80px;">Summe LP</th>
                        <th style="min-width:80px;">Summe CD</th>
                        <th style="min-width:100px;">Description</th>
                    </tr>
                    @foreach ($labels as $label)
                    <tr>
                        <th align="left">
                            <a href="{{ route('labels.show', $label->id) }}">{{ $label->name }} </a>
                        </th>
                        <th style="text-align: center;">
                            @if ($records->where('label_id', $label->id)->where('kind', 'LP')->sum(function ($record) {
                            return $record->current_price;
                            }))
                            {{ $records->where('label_id', $label->id)->where('kind', 'LP')->sum(function ($record) {
                                                return $record->current_price;
                                        }) }} €
                            @endif
                        </th>
                        <th style="text-align: center;">
                            @if ($records->where('label_id', $label->id)->where('kind', 'CD')->sum(function ($record) {
                            return $record->current_price;
                            }))
                            {{ $records->where('label_id', $label->id)->where('kind', 'CD')->sum(function ($record) {
                                                return $record->current_price;
                                        }) }} €
                            @endif
                        </th>
                        <th>
                            @if ($label->description)
                            {{ $label->description }}
                            @endif
                        </th>
                    </tr>
                    @endforeach
                </table>
                @else
                There is no data yet.
                @endif
            </div>
            {{ $labels->links('pagination::bootstrap-4') }}
        </div>
    </div>
</x-app-layout>