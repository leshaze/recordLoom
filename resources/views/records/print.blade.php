<x-print-layout :title="__('Verkaufsliste')" :records="$records"
    :subtitle="trans_choice(':count Platte|:count Platten', $records->count())">
    <table>
        <thead>
            <tr>
                <th style="width: 4%;">{{ __('Art') }}</th>
                <th style="width: 16%;">{{ __('Künstler') }}</th>
                <th style="width: 22%;">{{ __('Titel') }}</th>
                <th style="width: 8%;">{{ __('Grading') }}<br><span class="muted">{{ __('Media / Cover') }}</span></th>
                <th style="width: 13%;">{{ __('Matrix-Nr.') }}</th>
                <th style="width: 5%;">{{ __('Jahr') }}</th>
                <th style="width: 8%;">{{ __('Land') }}</th>
                <th style="width: 24%;">{{ __('Notiz') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($records as $record)
                <tr>
                    <td>{{ $record->kind }}</td>
                    <td>{{ $record->artist->name }}</td>
                    <td>
                        <span class="strong">{{ $record->title }}</span>
                        @if ($record->editions->isNotEmpty())
                            <br><span class="muted">{{ $record->editions->pluck('label')->implode(', ') }}</span>
                        @endif
                    </td>
                    <td class="nowrap"><x-print-grading :record="$record" /></td>
                    <td>{{ $record->matrix_number }}</td>
                    <td>{{ $record->release_year }}</td>
                    <td>{{ $record->country?->name }}</td>
                    <td>{{ $record->note }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</x-print-layout>
