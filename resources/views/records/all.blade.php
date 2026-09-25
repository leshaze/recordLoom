<x-app-layout>
<div class="container">
        <div class="wrapper">
            <div class="card table-responsive mx-auto">
                <div class="card-header">{{ __('Records') }} 
                    @if(Route::is('records.selling')) 
                        <div class="position-absolute top-0 end-0"><a class="btn btn-info btn-sm" href="{{ route('records.print') }}" target="_blank" rel="noopener noreferrer">Export</a>
                    </div>
                    @endif
                </div>
                @if (!empty($records) && $records->count())
                    <table class="table table-sm small">
                        <tr>
                            <th>Kind</th>
                            <th>Künstler</th>
                            <th>Title</th>
                            <th>Label</th>
                            <th>Cover</th>
                            <th>Media</th>
                            <th>Katalog-Nr.</th>
                            <th>Matrix-Nr.</th>
                            <th>Archiv-Nr.</th>
                            <th>Barcode</th>
                            @can('admin')<th>Aktueller Preis</th>@endcan
                            <th>Erscheinungsjahr</th>
                            <th>Herkunftsland</th>
                            @can('admin')
                            <th>Edit</th>
                            <th>Delete</th>
                            @endcan
                            @can('mark-interest')
                            <th>Interesse</th>
                            @endcan
                        </tr>
                        @foreach ($records as $record)
                            <tr
                                @if ($record->sold) style="background-color:
                                #d3d3d3; text-decoration: line-through;"
                            @elseif ($record->lost) style="background-color: #800080; text-decoration:
                                line-through; " @endif>
                                <td>{{ $record->kind }}</td>
                                <td><a href="{{ route('artists.show', ['artist' => $record->artist_id]) }}">
                                        {{ $record->artist->name }} </a></td>
                                <td><a
                                        href="{{ route('records.show', ['record' => $record->id]) }}">{{ $record->title }}</a>
                                </td>
                                <td>
                                    @if ($record->label_id)
                                        <a
                                            href="{{ route('labels.show', ['label' => $record->label_id]) }}">{{ $record->label->name }}</a>
                                    @endif
                                </td>
                                <td>
                                    @if ($record->grading_cover)
                                        {{ $record->grading_cover }}
                                    @endif
                                </td>
                                <td>
                                    @if ($record->grading_media)
                                        {{ $record->grading_media }}
                                    @endif
                                </td>
                                <td>
                                    @if ($record->catalog_number)
                                        {{ $record->catalog_number }}
                                    @endif
                                </td>
                                <td>
                                    @if ($record->matrix_number)
                                        {{ $record->matrix_number }}
                                    @endif
                                </td>
                                <td>
                                    @if ($record->archive_number)
                                        {{ $record->archive_number }}
                                    @endif
                                </td>
                                <td>
                                    @if ($record->barcode)
                                        {{ $record->barcode }}
                                    @endif
                                </td>
                                @can('admin')
                                <td>
                                    @if ($record->current_price)
                                        {{ $record->current_price }} €
                                    @endif
                                </td>
                                @endcan
                                <td>
                                    @if ($record->release_date)
                                        {{ $record->release_date }}
                                    @endif
                                </td>
                                <td>
                                    @if ($record->country_id)
                                        {{ $record->country?->name }}
                                    @endif
                                </td>
                                @can('admin')
                                <td><a href="{{ route('records.edit', ['record' => $record->id]) }}"
                                        class="btn btn-sm">
                                        <i class="bi bi-pencil-square"></i></a>
                                </td>
                                <td>
                                    <a href="javascript:document.getElementById('delete-record-form{{$record->id}}').submit();"
                                        class="btn btn-sm" onclick="return confirm(@js('Delete ' . $record->artist->name . ' - ' . $record->title . '?'))"><i class="bi bi-trash"></i></a>
                                    <form id="delete-record-form{{$record->id}}"
                                        action="{{ route('records.destroy', ['record' => $record->id]) }}" method="post"
                                        style="display: none;">
                                        @method('DELETE')
                                        {{ csrf_field() }}
                                    </form>
                                </td>
                                @endcan
                                @can('mark-interest')
                                <td>
                                    <x-interest-toggle :record="$record" :interested="$interestIds->contains($record->id)" label="" />
                                </td>
                                @endcan
                            </tr>
                        @endforeach
                    </table>
                @else
                    There is no data yet.
                @endif
            </div>
            {{ $records->links('pagination::bootstrap-4') }}
        </div>
    </div>
</x-app-layout>