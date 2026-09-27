<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\Country;
use App\Models\Label;
use App\Models\Platform;
use App\Models\Record;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    /**
     * Maximum number of suggestions returned per request.
     */
    private const LIMIT = 25;

    public function getAutocomplete(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('term', ''));

        if ($term === '' || mb_strlen($term) > 255) {
            return response()->json([]);
        }

        $like = '%'.$term.'%';

        $results = match ($request->query('search')) {
            'artist' => Artist::where('name', 'like', $like)->orderBy('name')->limit(self::LIMIT)->get(['id', 'name']),
            'label' => Label::where('name', 'like', $like)->orderBy('name')->limit(self::LIMIT)->get(['id', 'name']),
            'country' => Country::where('name', 'like', $like)->orderBy('name')->limit(self::LIMIT)->get(['id', 'name']),
            'platform' => Platform::where('name', 'like', $like)->orderBy('name')->limit(self::LIMIT)->get(['id', 'name']),
            'title' => Record::where('title', 'like', $like)
                ->where('artist_id', $request->integer('artist_id'))
                ->orderBy('title')
                ->limit(self::LIMIT)
                ->get(['id', 'title', 'archive_number']),
            'all' => $this->searchAll($like),
            default => [],
        };

        return response()->json($results);
    }

    private function searchAll(string $like)
    {
        $records = Record::query()
            ->select('id', DB::raw('title as name'), DB::raw("'record' as type"))
            ->where('title', 'like', $like);
        $labels = Label::query()
            ->select('id', 'name', DB::raw("'label' as type"))
            ->where('name', 'like', $like);

        return Artist::query()
            ->select('id', 'name', DB::raw("'artist' as type"))
            ->where('name', 'like', $like)
            ->union($records)
            ->union($labels)
            ->limit(self::LIMIT)
            ->get();
    }
}
