<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\Edition;
use App\Models\Label;
use App\Support\GroupFilter;
use App\Support\RecordFilter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Artists, labels and platforms: entries with a name and a description that records belong to.
 * The subclasses only name the model and their texts.
 */
abstract class GroupController extends Controller
{
    /**
     * @var class-string<Model>
     */
    protected string $model;

    /**
     * Prefix of the routes and of the print view, e.g. "artists".
     */
    protected string $resource;

    /**
     * Name of the name input, e.g. "artist_name" (also used by the validation messages).
     */
    protected string $nameField;

    protected bool $withUrl = false;

    /**
     * The relation shown in the PDF next to the title (label for artists, artist for labels).
     */
    protected ?string $printRelation = null;

    /**
     * Translated texts: title, new, created, exists, saved, deleted, in_use, confirm_delete.
     * Texts with :name get the name of the entry.
     *
     * @return array<string, string>
     */
    abstract protected function texts(): array;

    public function index(Request $request): View
    {
        $columns = $this->withUrl ? ['name', 'description', 'url'] : ['name', 'description'];
        $filter = new GroupFilter($request, $this->model, $this->resource.'.index', $columns);

        return view('groups.index', [
            'items' => $filter->query()->paginate($filter->values['per_page'])->withQueryString(),
            'filter' => $filter,
        ] + $this->shared());
    }

    public function show(Request $request): View
    {
        $item = $this->item($request);
        $parameter = Str::singular($this->resource);

        // The records of the entry with the filter of the record list.
        $filter = (new RecordFilter($request))
            ->within($parameter.'_id', $item->id, $this->resource.'.show', [$parameter => $item->id]);
        $filter->remember($request);

        return view('groups.details', [
            'item' => $item,
            'records' => $filter->query()->with(RecordController::LIST_RELATIONS)->paginate($filter->values['per_page'])->withQueryString(),
            'filter' => $filter,
            'recordCount' => $item->records()->count(),
            'totalValue' => $item->records()->where('sold', false)->sum('current_price'),
            'labels' => $this->resource === 'labels' ? null : Label::orderBy('name')->get(['id', 'name']),
            'countries' => Country::orderBy('name')->get(['id', 'name']),
            'editions' => Edition::orderBy('name')->get(['id', 'name']),
        ] + $this->shared());
    }

    public function create(): View
    {
        return view('groups.form', ['item' => null] + $this->shared());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());
        $name = $data[$this->nameField];

        if ($this->model::where('name', $name)->exists()) {
            return redirect()->route($this->resource.'.create')->with('error', $this->text('exists', $name));
        }

        $item = new $this->model;
        $this->fill($item, $data);

        return redirect()->route($this->resource.'.create')->with('info', $this->text('created', $item->name));
    }

    public function edit(Request $request): View
    {
        return view('groups.form', ['item' => $this->item($request)] + $this->shared());
    }

    public function update(Request $request): RedirectResponse
    {
        $item = $this->item($request);
        $data = $request->validate($this->rules($item));
        $this->fill($item, $data);

        return redirect()->route($this->resource.'.index')->with('info', $this->text('saved', $item->name));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $item = $this->item($request);

        if ($item->records()->exists()) {
            return redirect()->route($this->resource.'.index')->with('error', $this->text('in_use', $item->name));
        }

        $item->delete();

        return redirect()->route($this->resource.'.index')->with('info', $this->text('deleted', $item->name));
    }

    public function print(Request $request)
    {
        abort_unless($this->printRelation, 404);

        $item = $this->item($request);
        $records = $item->records()->with([$this->printRelation, 'country', 'editions'])->orderBy('title')->get();

        return Pdf::loadView('groups.print', [
            'item' => $item,
            'records' => $records,
            'totalValue' => $records->where('sold', false)->sum('current_price'),
            'relation' => $this->printRelation,
        ])->stream($item->name.'-'.now()->format('d.m.Y').'.pdf');
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(?Model $item = null): array
    {
        $unique = Rule::unique((new $this->model)->getTable(), 'name');

        return array_filter([
            $this->nameField => ['required', 'string', 'max:255', ...($item ? [$unique->ignore($item)] : [])],
            'url' => $this->withUrl ? ['nullable', 'url:http,https', 'max:255'] : null,
            'description' => ['nullable', 'string', 'max:5000'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function fill(Model $item, array $data): void
    {
        $item->name = $data[$this->nameField];
        $item->description = $data['description'] ?? null;
        if ($this->withUrl) {
            $item->url = $data['url'] ?? null;
        }
        $item->save();
    }

    /**
     * The entry of the route ({artist}, {label} or {platform}).
     */
    private function item(Request $request): Model
    {
        return $this->model::findOrFail(Arr::first($request->route()->parameters()));
    }

    private function text(string $key, string $name): string
    {
        return str_replace(':name', $name, $this->texts()[$key]);
    }

    /**
     * @return array<string, mixed>
     */
    private function shared(): array
    {
        return [
            'resource' => $this->resource,
            'nameField' => $this->nameField,
            'withUrl' => $this->withUrl,
            'canPrint' => $this->printRelation !== null,
            'texts' => $this->texts(),
        ];
    }
}
