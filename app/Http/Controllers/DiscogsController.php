<?php

namespace App\Http\Controllers;

use App\Models\Record;
use App\Services\Discogs\DiscogsClient;
use App\Services\Discogs\DiscogsComparison;
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
     * Comparison of a record with a release: for every field the existing value or the Discogs value can be chosen.
     */
    public function review(Request $request, Record $record, DiscogsComparison $comparison)
    {
        $releaseId = (int) $request->validate(['release_id' => ['required', 'integer', 'min:1']])['release_id'];

        try {
            $release = $this->releaseData($releaseId);
        } catch (DiscogsException $exception) {
            return redirect()->route('discogs.match')->with('error', $exception->getMessage());
        }

        return view('discogs.review', [
            'record' => $record->load(['artist', 'label', 'country', 'editions']),
            'releaseId' => $releaseId,
            'release' => $release,
            'rows' => $comparison->rows($record, $release),
            'newEditions' => $comparison->newEditions($record, $release),
        ]);
    }

    /**
     * Links a record with a release and takes over the chosen values.
     */
    public function link(Request $request, Record $record, DiscogsComparison $comparison): RedirectResponse
    {
        $data = $request->validate([
            'release_id' => ['required', 'integer', 'min:1'],
            'use' => ['nullable', 'array'],
            'use.*' => ['in:'.DiscogsComparison::KEEP.','.DiscogsComparison::DISCOGS],
            'editions' => ['nullable', 'array'],
            'editions.*' => ['integer', 'exists:editions,id'],
            'cover' => ['nullable', 'in:'.DiscogsComparison::KEEP.','.DiscogsComparison::DISCOGS],
        ]);

        try {
            $release = $this->releaseData((int) $data['release_id']);
        } catch (DiscogsException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $this->setRelease($record, (int) $data['release_id']);
        $comparison->apply($record, $release, $data['use'] ?? []);
        $record->save();

        if (! empty($data['editions'])) {
            $record->editions()->syncWithoutDetaching($data['editions']);
        }

        $warning = null;
        if (($data['cover'] ?? null) === DiscogsComparison::DISCOGS && filled($release['cover_url'] ?? null)) {
            $warning = $this->covers->store($record, $release['cover_url']);
            $record->save();
        }

        return redirect()->route('discogs.match')
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
            $prices->update($record);
        } catch (DiscogsException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('info', __('Marktdaten von Discogs wurden aktualisiert.'));
    }

    public function applyPrice(Request $request, Record $record, DiscogsPrices $prices): RedirectResponse
    {
        $source = $request->validate([
            'source' => ['required', 'in:'.DiscogsPrices::SOURCE_LOWEST.','.DiscogsPrices::SOURCE_SUGGESTION],
        ])['source'];

        try {
            $prices->apply($record, $source);
        } catch (DiscogsException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('info', __('Der Preis von Discogs wurde als aktueller Preis übernommen und in der Preisentwicklung gespeichert.'));
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
