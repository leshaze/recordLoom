{{-- Compact record table used on the artist, label and platform pages. --}}
@if ($records->isEmpty())
    <div class="card card-body">{{ __('Keine Platten vorhanden.') }}</div>
@else
    <div class="card">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle small mb-0">
                <thead>
                    <tr>
                        <th></th>
                        <th>{{ __('Art') }}</th>
                        <th>{{ __('Künstler') }}</th>
                        <th>{{ __('Titel') }}</th>
                        <th>{{ __('Label') }}</th>
                        <th>{{ __('Grading') }}</th>
                        <th>{{ __('Katalog-Nr.') }}</th>
                        <th>{{ __('Jahr') }}</th>
                        <th class="text-end">{{ __('Preis') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($records as $record)
                        <tr @class(['text-decoration-line-through opacity-50' => $record->sold || $record->lost])>
                            <td><x-cover :record="$record" :size="32" /></td>
                            <td>{{ $record->kind }}</td>
                            <td><a href="{{ route('artists.show', $record->artist_id) }}">{{ $record->artist->name }}</a></td>
                            <td>
                                <a href="{{ route('records.show', $record) }}" class="fw-semibold">{{ $record->title }}</a>
                                @foreach ($record->editions as $edition)
                                    <span class="badge text-bg-dark border fw-normal">{{ $edition->name }}</span>
                                @endforeach
                            </td>
                            <td><a href="{{ route('labels.show', $record->label_id) }}">{{ $record->label->name }}</a></td>
                            <td class="text-nowrap">
                                <x-grading-badge :grading="$record->gradingMedia()" />
                                <x-grading-badge :grading="$record->gradingCover()" />
                            </td>
                            <td>{{ $record->catalog_number }}</td>
                            <td>{{ $record->release_year }}</td>
                            <td class="text-end text-nowrap">{{ \App\Support\Format::euro($record->current_price) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
