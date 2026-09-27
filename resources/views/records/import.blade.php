<x-app-layout title="{{ __('CSV-Import') }}">
    <div class="container" style="max-width: 50rem;">
        <h1 class="h3 mb-3">{{ __('CSV-Import') }}</h1>

        @if (session('import_errors'))
            <div class="alert alert-warning">
                <strong>{{ __('Diese Zeilen wurden nicht importiert:') }}</strong>
                <ul class="mb-0 small">
                    @foreach (session('import_errors') as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card mb-3">
            <div class="card-body">
                <form method="POST" action="{{ route('records.import.store') }}" enctype="multipart/form-data">
                    @csrf
                    <label for="file" class="form-label">{{ __('CSV-Datei') }}</label>
                    <input type="file" name="file" id="file" accept=".csv,text/csv" required
                        class="form-control @error('file') is-invalid @enderror">
                    @error('file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <button type="submit" class="btn btn-primary mt-3"><i class="bi bi-upload"></i> {{ __('Importieren') }}</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">{{ __('So funktioniert der Import') }}</div>
            <div class="card-body small">
                <ul>
                    <li>{{ __('Das Format entspricht dem CSV-Export: Semikolon als Trennzeichen, erste Zeile mit Spaltennamen. Aus Excel als „CSV (Trennzeichen-getrennt)“ speichern.') }}
                        <a href="{{ route('records.export') }}">{{ __('Beispiel herunterladen (CSV-Export)') }}</a></li>
                    <li>{{ __('Pflichtspalten: Art (LP oder CD), Künstler, Titel, Label. Alle anderen Spalten sind optional, ihre Reihenfolge ist egal.') }}</li>
                    <li>{{ __('Es werden nur neue Platten angelegt. Zeilen, deren ID bereits existiert, werden übersprungen – bestehende Platten werden nie verändert.') }}</li>
                    <li>{{ __('Künstler, Labels, Länder, Anbieter und Zusatzinfos werden bei Bedarf automatisch angelegt. Mehrere Zusatzinfos mit Komma trennen.') }}</li>
                    <li>{{ __('Preise mit Komma oder Punkt, Datum als TT.MM.JJJJ, Ja/Nein-Spalten mit „ja“ oder „nein“.') }}</li>
                    <li>{{ __('Die Spaltennamen sind immer deutsch, unabhängig von der gewählten Sprache.') }}</li>
                </ul>
                <div class="text-body-secondary">{{ __('Bekannte Spalten:') }} {{ implode(', ', $columns) }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
