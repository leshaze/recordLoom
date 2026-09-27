<x-mail::message>
# {{ __('Sicherung der Datenbank') }}

{{ __('Seit der letzten Sicherung hat sich die Sammlung geändert. Im Anhang findest du eine Kopie der Datenbank.') }}

<x-mail::table>
| | |
|:--|--:|
@foreach ($counts as $label => $count)
| {{ $label }} | {{ $count }} |
@endforeach
</x-mail::table>

@if ($previousBackup)
{{ __('Letzte Sicherung: :date', ['date' => $previousBackup]) }}
@endif

**{{ __('Wiederherstellen') }}:** {{ __('Die Datei entpacken (z. B. mit 7-Zip oder gunzip) und als database/database.sqlite einspielen. Cover-Bilder sind nicht enthalten, sie liegen in storage/app/private/covers.') }}

RecordLoom
</x-mail::message>
