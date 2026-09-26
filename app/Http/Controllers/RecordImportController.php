<?php

namespace App\Http\Controllers;

use App\Actions\SaveRecord;
use App\Http\Requests\StoreRecordRequest;
use App\Models\Edition;
use App\Models\Record;
use App\Support\RecordCsv;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Imports new records from a CSV file (same format as the export).
 * Rows with an ID of an existing record are skipped, existing records are never changed.
 */
class RecordImportController extends Controller
{
    public function create()
    {
        return view('records.import', ['columns' => RecordCsv::knownHeaders()]);
    }

    public function store(Request $request, SaveRecord $saveRecord)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:10240']], [], ['file' => 'Datei']);

        $rules = collect((new StoreRecordRequest)->rules())
            ->except(['cover', 'editions', 'editions.*', 'artist_id', 'label_id', 'country_id', 'platform_id'])
            ->merge([
                'sold_on' => ['nullable', 'date'],
                'sold_to' => ['nullable', 'string', 'max:255'],
                'sold_price' => ['nullable', 'numeric', 'min:0'],
            ])
            ->all();

        $imported = 0;
        $skipped = [];
        $errors = [];

        foreach (RecordCsv::read($request->file('file')->getRealPath()) as $line => $row) {
            if (filled($row['id'] ?? null) && Record::whereKey($row['id'])->exists()) {
                $skipped[] = $line;

                continue;
            }

            $data = $this->normalize($row);
            $validator = Validator::make($data, $rules, [], (new StoreRecordRequest)->attributes());
            if ($validator->fails()) {
                $errors[] = 'Zeile '.$line.': '.implode(' ', $validator->errors()->all());

                continue;
            }

            $values = $validator->validated();
            $values['editions'] = $this->editionIds($row['editions'] ?? '');
            foreach (RecordCsv::FLAGS as $flag) {
                $values[$flag] = $data[$flag];
            }
            $saveRecord(new Record, $values);
            $imported++;
        }

        $message = $imported.' '.($imported === 1 ? 'Platte' : 'Platten').' importiert.';
        if ($skipped) {
            $message .= ' '.count($skipped).' Zeile(n) übersprungen, weil die ID schon existiert.';
        }

        return redirect()->route('records.import')
            ->with($errors ? 'warning' : 'info', $message)
            ->with('import_errors', $errors);
    }

    /**
     * @param  array<string, string>  $row
     * @return array<string, mixed>
     */
    private function normalize(array $row): array
    {
        $data = array_map(fn ($value) => $value === '' ? null : $value, $row);
        unset($data['id'], $data['editions']);

        $data['kind'] = isset($data['kind']) ? strtoupper($data['kind']) : null;
        foreach (['current_price', 'buy_price', 'sold_price'] as $field) {
            if (isset($data[$field])) {
                $data[$field] = str_replace([' ', '€', ','], ['', '', '.'], $data[$field]);
            }
        }
        foreach (RecordCsv::FLAGS as $flag) {
            $data[$flag] = in_array(mb_strtolower((string) ($data[$flag] ?? '')), ['ja', 'yes', 'true', '1', 'x'], true);
        }
        if (isset($data['sold_on'])) {
            foreach (['d.m.Y', 'Y-m-d', 'd.m.y'] as $format) {
                if (Carbon::canBeCreatedFromFormat($data['sold_on'], $format)) {
                    $data['sold_on'] = Carbon::createFromFormat('!'.$format, $data['sold_on'])->format('Y-m-d');
                    break;
                }
            }
        }

        return $data;
    }

    /**
     * @return array<int, int>
     */
    private function editionIds(string $names): array
    {
        return collect(explode(',', $names))
            ->map(fn ($name) => trim($name))
            ->filter()
            ->map(fn ($name) => Edition::firstOrCreate(['name' => $name])->id)
            ->unique()
            ->values()
            ->all();
    }
}
