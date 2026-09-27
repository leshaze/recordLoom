<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\Record;
use App\Services\Discogs\DiscogsClient;
use App\Services\Discogs\DiscogsCovers;
use App\Services\Discogs\DiscogsException;
use App\Services\Discogs\DiscogsPrices;
use App\Services\Discogs\ReleaseMapper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DiscogsController extends Controller
{
    public function __construct(
        private readonly DiscogsClient $client,
        private readonly ReleaseMapper $mapper,
        private readonly DiscogsCovers $covers,
    ) {}

    /**
     * Search releases for the record form (JSON).
     */
    public function search(Request $request): JsonResponse
    {
        $criteria = $request->validate([
            'barcode' => ['nullable', 'string', 'max:100'],
            'catno' => ['nullable', 'string', 'max:100'],
            'q' => ['nullable', 'string', 'max:200'],
        ]);

        return $this->json(fn () => [
            'results' => array_map($this->mapper->toSearchResult(...), $this->client->search($criteria)),
        ]);
    }

    /**
     * Form data of a release (JSON), used to fill in the record form.
     */
    public function release(int $release): JsonResponse
    {
        return $this->json(fn () => $this->releaseData($release));
    }

    /**
     * Search suggestions for an existing record on the matching page (JSON).
     */
    public function suggestions(Record $record): JsonResponse
    {
        return $this->json(function () use ($record) {
            foreach ($this->mapper->searchCriteriaFor($record) as $criteria) {
                $results = $this->client->search($criteria, 8);
                if ($results !== []) {
                    return ['results' => array_map($this->mapper->toSearchResult(...), $results)];
                }
            }

            return ['results' => []];
        });
    }

    /**
     * Records that are not linked with Discogs yet.
     */
    public function match(Request $request)
    {
        $records = Record::whereNull('discogs_release_id')
            ->when(! $request->boolean('ignored'), fn ($query) => $query->whereNull('discogs_ignored_at'))
            ->with(['artist', 'label'])
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        return view('discogs.match', [
            'records' => $records,
            'configured' => $this->client->isConfigured(),
            'linkedCount' => Record::whereNotNull('discogs_release_id')->count(),
            'ignoredCount' => Record::whereNull('discogs_release_id')->whereNotNull('discogs_ignored_at')->count(),
        ]);
    }

    /**
     * Links a record with a release. Optionally fills in empty fields and a missing cover, nothing is overwritten.
     */
    public function link(Request $request, Record $record): RedirectResponse
    {
        $data = $request->validate([
            'release_id' => ['required', 'integer', 'min:1'],
            'fill_missing' => ['nullable', 'boolean'],
        ]);

        try {
            $release = $this->releaseData((int) $data['release_id']);
        } catch (DiscogsException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $this->setRelease($record, (int) $data['release_id']);
        $warning = null;

        if ($request->boolean('fill_missing')) {
            foreach (['catalog_number', 'barcode', 'matrix_number', 'release_year', 'reissue_year'] as $field) {
                if (blank($record->{$field}) && filled($release[$field] ?? null)) {
                    $record->{$field} = $release[$field];
                }
            }
            if (! $record->country_id && filled($release['country_name'] ?? null)) {
                $record->country_id = Country::firstOrCreate(['name' => $release['country_name']])->id;
            }
            if (! empty($release['editions'])) {
                $record->editions()->syncWithoutDetaching($release['editions']);
            }
            if (! $record->hasCover() && filled($release['cover_url'] ?? null)) {
                $warning = $this->covers->store($record, $release['cover_url']);
            }
        }

        $record->save();

        return back()
            ->with('info', __('Platte „:title“ wurde mit Discogs verknüpft.', ['title' => $record->title]))
            ->with('warning', $warning);
    }

    public function ignore(Record $record): RedirectResponse
    {
        $record->discogs_ignored_at = now();
        $record->save();

        return back()->with('info', __('Platte „:title“ wird beim Abgleich übersprungen.', ['title' => $record->title]));
    }

    public function unlink(Record $record): RedirectResponse
    {
        $this->setRelease($record, null);
        $record->save();

        return back()->with('info', __('Verknüpfung mit Discogs wurde entfernt.'));
    }

    public function updatePrices(Record $record, DiscogsPrices $prices): RedirectResponse
    {
        try {
            $warning = $prices->update($record);
        } catch (DiscogsException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('info', __('Marktdaten von Discogs wurden aktualisiert.'))->with('warning', $warning);
    }

    public function applyPrice(Record $record, DiscogsPrices $prices): RedirectResponse
    {
        try {
            $prices->applySuggestedPrice($record);
        } catch (DiscogsException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('info', __('Der Preisvorschlag von Discogs wurde als aktueller Preis übernommen.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function releaseData(int $id): array
    {
        $release = $this->client->release($id);
        $originalYear = null;

        if (! empty($release['master_id'])) {
            try {
                $originalYear = (int) ($this->client->master((int) $release['master_id'])['year'] ?? 0) ?: null;
            } catch (DiscogsException) {
                // The year of the first release is optional.
            }
        }

        return $this->mapper->toRecordData($release, $originalYear);
    }

    private function setRelease(Record $record, ?int $releaseId): void
    {
        if ($record->discogs_release_id === $releaseId) {
            return;
        }

        $record->discogs_release_id = $releaseId;
        $record->discogs_ignored_at = null;
        $record->discogs_price_suggestions = null;
        $record->discogs_lowest_price = null;
        $record->discogs_currency = null;
        $record->discogs_num_for_sale = null;
        $record->discogs_prices_updated_at = null;
    }

    private function json(callable $callback): JsonResponse
    {
        try {
            return response()->json($callback());
        } catch (DiscogsException $exception) {
            return response()->json(['error' => $exception->getMessage()], 502);
        }
    }
}
