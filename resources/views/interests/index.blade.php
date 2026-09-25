<x-app-layout>
    <div class="container">
        <div class="card table-responsive">
            <div class="card-header">{{ __('Meine Interessen') }}</div>
            @if ($records->isNotEmpty())
                <table class="table table-sm small mb-0">
                    <tr>
                        <th>Kind</th>
                        <th>Künstler</th>
                        <th>Title</th>
                        <th>Label</th>
                                                <th>Seit</th>
                        <th>Interesse</th>
                    </tr>
                    @foreach ($records as $record)
                        <tr>
                            <td>{{ $record->kind }}</td>
                            <td><a href="{{ route('artists.show', $record->artist_id) }}">{{ $record->artist->name }}</a></td>
                            <td><a href="{{ route('records.show', $record) }}">{{ $record->title }}</a></td>
                            <td><a href="{{ route('labels.show', $record->label_id) }}">{{ $record->label->name }}</a></td>
                            <td>{{ $record->pivot->created_at?->format('d.m.Y') }}</td>
                            <td><x-interest-toggle :record="$record" :interested="true" label="" /></td>
                        </tr>
                    @endforeach
                </table>
            @else
                <div class="card-body">
                    Du hast noch keine Platten markiert. Setze bei einer Platte den Haken „Interesse“.
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
