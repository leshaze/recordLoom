<?php

namespace App\Http\Controllers;

use App\Actions\SaveRecord;
use App\Enums\Role;
use App\Http\Requests\StoreRecordRequest;
use App\Http\Requests\UpdateRecordRequest;
use App\Models\Country;
use App\Models\Edition;
use App\Models\Label;
use App\Models\Record;
use App\Models\User;
use App\Services\Discogs\DiscogsClient;
use App\Services\Discogs\DiscogsCovers;
use App\Support\CoverStorage;
use App\Support\DuplicateRecords;
use App\Support\RecordCsv;
use App\Support\RecordFilter;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RecordController extends Controller
{
    /**
     * Relations shown in the record lists.
     */
    public const LIST_RELATIONS = ['artist', 'label', 'country', 'editions'];

    /**
     * Relations and interest for the record lists (all records, artist, label, platform): sellers see their own
     * interest, owners the number of interested sellers.
     */
    public static function forList(Builder $query, User $user): Builder
    {
        return $query->with(self::LIST_RELATIONS)
            ->when($user->isSeller(), fn (Builder $query) => $query->with(['sellers' => fn ($query) => $query->whereKey($user->id)]))
            ->when(! $user->isSeller(), fn (Builder $query) => $query->withCount(['sellers as interested_count' => fn ($query) => $query->whereNotNull('record_seller.interested_at')]));
    }

    public function index(Request $request)
    {
        $filter = new RecordFilter($request);
        $filter->remember($request);
        $user = $request->user();
        $records = self::forList($filter->query(), $user)
            ->paginate($filter->values['per_page'])
            ->withQueryString();

        return view('records.all', [
            'records' => $records,
            'filter' => $filter,
            'labels' => Label::orderBy('name')->get(['id', 'name']),
            'countries' => Country::orderBy('name')->get(['id', 'name']),
            'editions' => Edition::orderBy('name')->get(['id', 'name']),
            'owners' => $user->isAdmin() ? User::where('role', '!=', Role::Seller->value)->orderBy('name')->get(['id', 'name']) : null,
        ]);
    }

    /**
     * Result of the barcode scanner in the navigation: one record opens directly, otherwise the filtered list.
     */
    public function barcode(Request $request)
    {
        $barcode = RecordFilter::normalizeBarcode((string) $request->query('code', ''));
        $records = $barcode === '' ? collect() : RecordFilter::whereBarcode(Record::visible(), $barcode)->limit(2)->pluck('id');

        if ($records->count() === 1) {
            return redirect()->route('records.show', $records->first());
        }

        return redirect()->route('records.index', ['barcode' => $barcode]);
    }

    public function create(Request $request)
    {
        return view('records.create', [
            'record' => new Record,
            // After saving was stopped because of possible duplicates (the form data is in the old input).
            'duplicates' => $request->session()->has('duplicates') ? DuplicateRecords::find($request->old()) : collect(),
            'editions' => Edition::orderBy('name')->get(),
            'discogsConfigured' => app(DiscogsClient::class)->isConfigured(),
            // From "not in the collection" after scanning: search this barcode on Discogs right away.
            'discogsQuery' => RecordFilter::normalizeBarcode((string) request()->query('barcode', '')),
        ]);
    }

    public function store(StoreRecordRequest $request, SaveRecord $saveRecord, DiscogsCovers $covers)
    {
        // Possible duplicates are only saved after the checkbox "save anyway" was ticked.
        if (! $request->boolean('confirm_duplicate') && DuplicateRecords::find($request->validated())->isNotEmpty()) {
            return redirect()->route('records.create')->withInput()->with('duplicates', true)->with('error', $request->hasFile('cover')
                ? __('Diese Platte ist vielleicht schon in deiner Sammlung. Bitte prüfen und das Cover erneut auswählen.')
                : __('Diese Platte ist vielleicht schon in deiner Sammlung. Bitte prüfen.'));
        }

        $data = [...$request->validated(), 'selling' => $request->boolean('selling')];
        $record = new Record;
        $record->user_id = $request->user()->id;
        $saveRecord($record, $data, $request->file('cover'));
        $warning = $this->coverFromDiscogs($request, $record, $covers);

        return redirect()->route('records.show', $record)
            ->with('info', __('Platte „:title“ von :artist wurde angelegt.', ['title' => $record->title, 'artist' => $record->artist->name]))
            ->with('warning', $warning);
    }

    public function show(Request $request, Record $record)
    {
        Gate::authorize('view', $record);
        $user = $request->user();
        $canEdit = $user->can('update', $record);

        $record->load(['artist', 'label', 'country', 'platform', 'editions', 'owner', 'soldToUser']);
        $prices = $canEdit ? $record->prices()->with('platform')->latest()->take(5)->get()->reverse() : collect();

        return view('records.details', [
            'record' => $record,
            'prices' => $prices,
            'canEdit' => $canEdit,
            // Sellers: assigned ones with their interest (owner), or the own assignment (seller).
            'sellers' => $canEdit ? $record->sellers()->get() : collect(),
            'availableSellers' => $canEdit ? User::where('role', Role::Seller)->orderBy('name')->get(['id', 'name']) : collect(),
            // Sellers: their own assignment or interest (null when the record is only marked for selling).
            'assignment' => $user->isSeller() ? $record->sellers()->whereKey($user->id)->first()?->pivot : null,
            'navigation' => $this->navigation($request, $record),
        ]);
    }

    /**
     * Possible duplicates of the record that is being entered (checked by the form while typing).
     */
    public function duplicates(Request $request)
    {
        $data = $request->only(['barcode', 'catalog_number', 'label_name', 'discogs_release_id']);

        return response()->json([
            'html' => view('records._duplicates', ['duplicates' => DuplicateRecords::find(array_map(fn ($value) => is_string($value) ? $value : null, $data))])->render(),
        ]);
    }

    /**
     * Position of the record in the last used list and its neighbours, so the record page can page through
     * the search result. The link back to the list keeps filter, sorting and page.
     *
     * @return array{list: string, position: ?int, total: int, previous: ?int, next: ?int}
     */
    private function navigation(Request $request, Record $record): array
    {
        [$filter, $query] = RecordFilter::remembered($request);
        $ids = $filter->query()->pluck('id');
        $index = $ids->search($record->id);

        if ($index !== false) {
            // Back to the page of the list that contains the record.
            $query['page'] = intdiv($index, $filter->values['per_page']) + 1;
            if ($query['page'] === 1) {
                unset($query['page']);
            }
        }

        return [
            'list' => $filter->url($query),
            'position' => $index === false ? null : $index + 1,
            'total' => $ids->count(),
            'previous' => $index === false ? null : $ids->get($index - 1),
            'next' => $index === false ? null : $ids->get($index + 1),
        ];
    }

    public function edit(Record $record)
    {
        Gate::authorize('update', $record);

        return view('records.edit', ['record' => $record, 'editions' => Edition::orderBy('name')->get(), 'discogsConfigured' => app(DiscogsClient::class)->isConfigured()]);
    }

    public function update(UpdateRecordRequest $request, Record $record, SaveRecord $saveRecord, DiscogsCovers $covers)
    {
        $data = [
            ...$request->validated(),
            // "Zum Verkauf vormerken" is set on the page of the record (RecordSaleController::selling).
            'sold' => $request->boolean('sold'),
            'lost' => $request->boolean('lost'),
        ];
        if (! $data['sold']) {
            $record->sold_to_user_id = null;
        }
        $saveRecord($record, $data, $request->file('cover'), $request->boolean('remove_cover'));
        $warning = $this->coverFromDiscogs($request, $record, $covers);

        return redirect()->route('records.show', $record)
            ->with('info', __('Platte „:title“ von :artist wurde gespeichert.', ['title' => $record->title, 'artist' => $record->artist->name]))
            ->with('warning', $warning);
    }

    public function destroy(Record $record)
    {
        Gate::authorize('delete', $record);
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
        Gate::authorize('view', $record);
        $path = CoverStorage::path($record, $request->boolean('thumb'));
        abort_if($path === null, 404);

        return response()->file($path, ['Cache-Control' => 'private, max-age=86400']);
    }

    public function print()
    {
        $current = Carbon::now()->format('d.m.Y');

        $records = $this->sellingQuery()->with(['artist', 'country', 'editions'])->get();

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
     * Cover chosen in the Discogs search, used when no own cover was uploaded.
     */
    private function coverFromDiscogs(FormRequest $request, Record $record, DiscogsCovers $covers): ?string
    {
        $url = $request->validated('discogs_cover_url');
        if (! $url || $request->hasFile('cover')) {
            return null;
        }

        $warning = $covers->store($record, $url);
        $record->save();

        return $warning;
    }

    /**
     * Records that are marked for selling and not yet sold.
     */
    private function sellingQuery(): Builder
    {
        return Record::visible()->where('selling', true)
            ->where('sold', false)
            ->orderBy('kind', 'ASC')
            ->orderBy('artist_id', 'ASC');
    }
}
