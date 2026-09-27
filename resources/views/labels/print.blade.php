<x-print-layout :title="$label->name" :records="$records"
    :subtitle="trans_choice(':count Platte|:count Platten', $records->count()).' · '.__('Wert').' '.\App\Support\Format::euro($total_value ?: 0)">
    <table>
        <thead>
            <tr>
                <th style="width: 4%;">{{ __('Art') }}</th>
                <th style="width: 19%;">{{ __('Titel') }}</th>
                <th style="width: 12%;">{{ __('Künstler') }}</th>
                <th style="width: 8%;">{{ __('Grading') }}<br><span class="muted">{{ __('Media / Cover') }}</span></th>
                <th style="width: 10%;">{{ __('Katalog-Nr.') }}</th>
                <th style="width: 12%;">{{ __('Matrix-Nr.') }}</th>
                <th style="width: 8%;">{{ __('Archiv-Nr.') }}</th>
                <th style="width: 9%;">{{ __('Barcode') }}</th>
                <th style="width: 4%;">{{ __('Jahr') }}</th>
                <th style="width: 7%;">{{ __('Land') }}</th>
                <th class="right" style="width: 7%;">{{ __('Preis') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($records as $record)
                <tr>
                    <td>{{ $record->kind }}</td>
                    <td>
                        <span class="strong">{{ $record->title }}</span>
                        @if ($record->editions->isNotEmpty())
                            <br><span class="muted">{{ $record->editions->pluck('label')->implode(', ') }}</span>
                        @endif
                    </td>
                    <td>{{ $record->artist->name }}</td>
                    <td class="nowrap"><x-print-grading :record="$record" /></td>
                    <td>{{ $record->catalog_number }}</td>
                    <td>{{ $record->matrix_number }}</td>
                    <td>{{ $record->archive_number }}</td>
                    <td>{{ $record->barcode }}</td>
                    <td>{{ $record->release_year }}</td>
                    <td>{{ $record->country?->name }}</td>
                    <td class="right nowrap">{{ \App\Support\Format::euro($record->current_price) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</x-print-layout>
