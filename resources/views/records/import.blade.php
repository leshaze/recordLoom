<x-app-layout title="CSV-Import">
    <div class="container" style="max-width: 50rem;">
        <h1 class="h3 mb-3">CSV-Import</h1>

        @if (session('import_errors'))
            <div class="alert alert-warning">
                <strong>Diese Zeilen wurden nicht importiert:</strong>
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
                    <label for="file" class="form-label">CSV-Datei</label>
                    <input type="file" name="file" id="file" accept=".csv,text/csv" required
                        class="form-control @error('file') is-invalid @enderror">
                    @error('file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <button type="submit" class="btn btn-primary mt-3"><i class="bi bi-upload"></i> Importieren</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">So funktioniert der Import</div>
            <div class="card-body small">
                <ul>
                    <li>Das Format entspricht dem <a href="{{ route('records.export') }}">CSV-Export</a>: Semikolon als Trennzeichen, erste Zeile mit Spaltennamen. Aus Excel als „CSV (Trennzeichen-getrennt)“ speichern.</li>
                    <li>Pflichtspalten: <strong>Art</strong> (LP oder CD), <strong>Künstler</strong>, <strong>Titel</strong>, <strong>Label</strong>. Alle anderen Spalten sind optional, ihre Reihenfolge ist egal.</li>
                    <li>Es werden nur <strong>neue</strong> Platten angelegt. Zeilen, deren <em>ID</em> bereits existiert, werden übersprungen – bestehende Platten werden nie verändert.</li>
                    <li>Künstler, Labels, Länder, Anbieter und Zusatzinfos werden bei Bedarf automatisch angelegt. Mehrere Zusatzinfos mit Komma trennen.</li>
                    <li>Preise mit Komma oder Punkt, Datum als TT.MM.JJJJ, Ja/Nein-Spalten mit „ja“ oder „nein“.</li>
                </ul>
                <div class="text-body-secondary">Bekannte Spalten: {{ implode(', ', $columns) }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
