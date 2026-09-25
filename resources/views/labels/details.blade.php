<x-app-layout>
    <div class="container">
        <div class="wrapper flex">
            <div class="card table-responsive">
                <div class="card-header">
                    <div> {{ $label->name }} - {{ $total_value }} €@can('admin')
                        <a href="{{ route('labels.edit', ['label' => $label->id]) }}"
                            class="btn btn-sm"><i class="bi bi-pencil-square"></i></a>
                        <a href="javascript:document.getElementById('delete-label-form').submit();" class="btn btn-sm"
                            onclick="return confirm(@js('Delete ' . $label->name . '?'))"><i class="bi bi-trash"></i></a>
                        <form id="delete-label-form" action="{{ route('labels.destroy', ['label' => $label->id]) }}"
                            method="post" style="display: none;">
                            @method('DELETE')
                            {{ csrf_field() }}
                        </form>
                        @endcan
                        </td>
                    </div>
                    <div class="position-absolute top-0 end-0"><a class="btn btn-info btn-sm" href="{{ route('labels.print', ['label' => $label->id]) }}" target="_blank" rel="noopener noreferrer">Export</a></div>
                </div>
                <table class="table small xs">
                    <tr>
                        <th>Kind</th>
                        <th>Künstler</th>
                        <th>Title</th>
                        <th>Cover</th>
                        <th>Media</th>
                        <th>Katalog-Nr.</th>
                        <th>Aktueller Preis</th>
                    </tr>
                    @foreach ($records as $record)
                    <tr>
                        <td>{{ $record->kind }}</td>
                        <td><a
                                href="{{ route('artists.show', ['artist' => $record->artist_id]) }}">{{ $record->artist->name }}</a>
                        </td>
                        <td><a href="{{ route('records.show', ['record' => $record->id]) }}">{{ $record->title }}</a>
                        </td>
                        <td>{{ $record->grading_cover }}</td>
                        <td>{{ $record->grading_media }}</td>
                        <td>{{ $record->catalog_number }}</td>
                        <td>@if($record->current_price) {{ $record->current_price}} € @endif</td>
                    </tr>
                    @endforeach
                </table>
            </div>
        </div>
</x-app-layout>