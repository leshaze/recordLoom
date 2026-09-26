<?php

namespace App\Http\Controllers;

use App\Actions\SaveRecord;
use App\Http\Requests\StoreRecordRequest;
use App\Http\Requests\UpdateRecordRequest;
use App\Models\Country;
use App\Models\Edition;
use App\Models\Label;
use App\Models\Record;
use App\Support\CoverStorage;
use App\Support\RecordCsv;
use App\Support\RecordFilter;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class RecordController extends Controller
{
    /**
     * Relations shown in the record lists.
     */
    private const LIST_RELATIONS = ['artist', 'label', 'country', 'editions'];

    public function index(Request $request)
    {
        $filter = new RecordFilter($request);
        $records = $filter->query()
            ->with(self::LIST_RELATIONS)
            ->paginate($filter->values['per_page'])
            ->withQueryString();

        return view('records.all', [
            'records' => $records,
            'filter' => $filter,
            'labels' => Label::orderBy('name')->get(['id', 'name']),
            'countries' => Country::orderBy('name')->get(['id', 'name']),
            'editions' => Edition::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * The old "For Selling" page is now a filter of the record list.
     */
    public function selling()
    {
        return redirect()->route('records.index', ['status' => 'selling']);
    }

    public function create()
    {
        return view('records.create', ['record' => new Record, 'editions' => Edition::orderBy('name')->get()]);
    }

    public function store(StoreRecordRequest $request, SaveRecord $saveRecord)
    {
        $data = [...$request->validated(), 'selling' => $request->boolean('selling')];
        $record = $saveRecord(new Record, $data, $request->file('cover'));

        return redirect()->route('records.show', $record)
            ->with('info', __('Platte „:title“ von :artist wurde angelegt.', ['title' => $record->title, 'artist' => $record->artist->name]));
    }

    public function show(Record $record)
    {
        $record->load(['artist', 'label', 'country', 'platform', 'editions']);
        $prices = $record->prices()->with('platform')->latest()->take(5)->get()->reverse();

        return view('records.details', ['record' => $record, 'prices' => $prices]);
    }

    public function edit(Record $record)
    {
        return view('records.edit', ['record' => $record, 'editions' => Edition::orderBy('name')->get()]);
    }

    public function update(UpdateRecordRequest $request, Record $record, SaveRecord $saveRecord)
    {
        $data = [
            ...$request->validated(),
            'selling' => $request->boolean('selling'),
            'sold' => $request->boolean('sold'),
            'lost' => $request->boolean('lost'),
        ];
        $saveRecord($record, $data, $request->file('cover'), $request->boolean('remove_cover'));

        return redirect()->route('records.show', $record)
            ->with('info', __('Platte „:title“ von :artist wurde gespeichert.', ['title' => $record->title, 'artist' => $record->artist->name]));
    }

    public function destroy(Record $record)
    {
        CoverStorage::delete($record);
        $record->prices()->delete();
        $record->delete();

        return redirect()->route('records.index')
            ->with('info', __('Platte „:title“ von :artist wurde gelöscht.', ['title' => $record->title, 'artist' => $record->artist->name]));
    }

    /**
     * Cover images are stored outside of the public folder and delivered here.
     */
    public function cover(Request $request, Record $record)
    {
        $path = CoverStorage::path($record, $request->boolean('thumb'));
        abort_if($path === null, 404);

        return response()->file($path, ['Cache-Control' => 'private, max-age=86400']);
    }

    public function print()
    {
        $current = Carbon::now()->format('d.m.Y');

        $records = $this->sellingQuery()->with(['artist', 'country'])->get();

        $pdf = Pdf::loadView('records.print', ['records' => $records]);

        return $pdf->stream(__('Verkaufsliste').' '.$current.'.pdf');
    }

    /**
     * CSV export of the (filtered) record list.
     */
    public function export(Request $request)
    {
        $query = (new RecordFilter($request))->query()->with(['artist', 'label', 'country', 'platform', 'editions']);

        return RecordCsv::download($query, 'recordloom-'.Carbon::now()->format('Y-m-d').'.csv');
    }

    /**
     * Records that are marked for selling and not yet sold.
     */
    private function sellingQuery(): Builder
    {
        return Record::where('selling', true)
            ->where('sold', false)
            ->orderBy('kind', 'ASC')
            ->orderBy('artist_id', 'ASC');
    }
}
