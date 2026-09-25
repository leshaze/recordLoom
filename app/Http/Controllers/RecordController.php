<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRecordRequest;
use App\Http\Requests\UpdateRecordRequest;
use App\Models\Artist;
use App\Models\Country;
use App\Models\Label;
use App\Models\Platform;
use App\Models\PriceHistory;
use App\Models\Record;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class RecordController extends Controller
{
    /**
     * Relations shown in the record lists.
     */
    private const LIST_RELATIONS = ['artist', 'label', 'country'];

    public function index()
    {
        $records = Record::with(self::LIST_RELATIONS)->paginate(15);

        return view('records.all', ['records' => $records]);
    }

    public function selling()
    {
        $records = $this->sellingQuery()->with(self::LIST_RELATIONS)->paginate(60);

        return view('records.all', ['records' => $records]);
    }

    public function create()
    {
        return view('records.create');
    }

    public function store(StoreRecordRequest $request)
    {
        $data = $request->validated();

        $record = new Record;
        $record->kind = $data['kind'];
        $record->artist_id = $data['artist_id'] ?? Artist::firstOrCreate(['name' => $data['artist_name']])->id;
        $record->title = $data['title'];
        $record->label_id = $data['label_id'] ?? Label::firstOrCreate(['name' => $data['label_name']])->id;

        if (! empty($data['country_name'])) {
            $record->country_id = $data['country_id'] ?? Country::firstOrCreate(['name' => $data['country_name']])->id;
        }

        if (! empty($data['platform'])) {
            $record->platform_id = $data['platform_id'] ?? Platform::firstOrCreate(['name' => $data['platform']])->id;
        }

        $record->catalog_number = $data['catalog_number'] ?? null;
        $record->matrix_number = $data['matrix_number'] ?? null;
        $record->barcode = $data['barcode'] ?? null;
        $record->release_date = $data['release_date'] ?? null;
        $record->reissue_date = $data['reissue_date'] ?? null;
        $record->grading_media = $data['grading_media'] ?? null;
        $record->grading_cover = $data['grading_cover'] ?? null;
        $record->current_price = $data['current_price'] ?? null;
        $record->buy_price = $data['buy_price'] ?? null;
        $record->archive_number = $data['archive_number'] ?? null;
        $record->note = $data['note'] ?? null;
        $record->save();

        if ($record->current_price !== null) {
            $this->addPriceHistory($record);
        }

        return redirect()->route('records.index')->with('info', 'Record '.$record->title.' von '.$record->artist->name.' added successfully');
    }

    public function show(Record $record)
    {
        $prices = $record->prices()->with('platform')->latest()->take(5)->get()->reverse();

        return view('records.details', ['record' => $record, 'prices' => $prices]);
    }

    public function edit(Record $record)
    {
        return view('records.edit', ['record' => $record]);
    }

    public function update(UpdateRecordRequest $request, Record $record)
    {
        $data = $request->validated();

        if (! empty($data['kind'])) {
            $record->kind = $data['kind'];
        }
        $record->artist_id = Artist::firstOrCreate(['name' => $data['artist_name']])->id;
        $record->title = $data['title'];
        $record->label_id = Label::firstOrCreate(['name' => $data['label_name']])->id;

        if (! empty($data['country_name'])) {
            $record->country_id = Country::firstOrCreate(['name' => $data['country_name']])->id;
        }

        if (! empty($data['platform'])) {
            $record->platform_id = Platform::firstOrCreate(['name' => $data['platform']])->id;
        }

        $record->catalog_number = $data['catalog_number'] ?? null;
        $record->matrix_number = $data['matrix_number'] ?? null;
        $record->barcode = $data['barcode'] ?? null;
        $record->release_date = $data['release_date'] ?? null;
        $record->reissue_date = $data['reissue_date'] ?? null;

        if (isset($data['grading_media'])) {
            $record->grading_media = $data['grading_media'];
        }
        if (isset($data['grading_cover'])) {
            $record->grading_cover = $data['grading_cover'];
        }

        $record->current_price = $data['current_price'] ?? null;
        $record->buy_price = $data['buy_price'] ?? null;
        $record->archive_number = $data['archive_number'] ?? null;
        $record->note = $data['note'] ?? null;
        $record->selling = $request->boolean('selling');
        $record->sold = $request->boolean('sold');
        $record->sold_date = $data['sold_date'] ?? null;
        $record->sold_to = $data['sold_to'] ?? null;
        $record->sold_price = $data['sold_price'] ?? null;
        $record->lost = $request->boolean('lost');

        $oldPrice = $record->getOriginal('current_price');
        $priceChanged = $record->current_price !== null
            && ($oldPrice === null || (float) $oldPrice !== (float) $record->current_price);
        $record->save();

        if ($priceChanged) {
            $this->addPriceHistory($record);
        }

        return redirect()->route('records.index')->with('info', 'Record '.$record->title.' von '.$record->artist->name.' updated successfully');
    }

    public function destroy(Record $record)
    {
        $record->prices()->delete();
        $record->delete();

        return redirect()->route('records.index')->with('info', 'Record '.$record->title.' von '.$record->artist->name.' deleted successfully');
    }

    public function print()
    {
        $current = Carbon::now()->format('d.m.Y');

        $records = $this->sellingQuery()->with(['artist', 'country'])->get();

        $pdf = Pdf::loadView('records.print', ['records' => $records]);

        return $pdf->stream('Verkaufsliste -'.$current.'.pdf');
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

    private function addPriceHistory(Record $record): void
    {
        $priceHistory = new PriceHistory;
        $priceHistory->price = $record->current_price;
        $priceHistory->record_id = $record->id;
        $priceHistory->platform_id = $record->platform_id;
        $priceHistory->save();
    }
}
