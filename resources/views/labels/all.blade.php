<x-app-layout>
    <div class="container">
        <div class="wrapper">
            <div class="card table-responsive">
                <div class="card-header">{{ __('Label') }}</div>
                @if (!empty($labels) && $labels->count())
                <table class="table table-sm small d-inline-flex">
                    <tr>
                        <th>Name</th>
                        @can('admin')
                        <th>Summe LP</th>
                        <th>Summe CD</th>
                        @endcan
                        <th>Description</th>
                    </tr>
                    @foreach ($labels as $label)
                    <tr>
                        <td align="left">
                            <a href="{{ route('labels.show', $label->id) }}">{{ $label->name }} </a>
                        </td>
                        @can('admin')
                        <td style="text-align: center;">
                            {{ $records->where('label_id', $label->id)->where('kind', 'LP')->sum(function ($record) {
                                                return $record->current_price;
                                        }) }} €
                        </td>
                        <td style="text-align: center;">
                            {{ $records->where('label_id', $label->id)->where('kind', 'CD')->sum(function ($record) {
                                                return $record->current_price;
                                        }) }} €
                        </td>
                        @endcan
                        <td>
                            @if ($label->description)
                            {{ $label->description }}
                            @endif
                        </td>
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