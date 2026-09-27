<?php

namespace App\Services\Discogs;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Small client for the Discogs API (https://www.discogs.com/developers).
 * Authenticates with a personal access token, the API allows 60 requests per minute.
 */
class DiscogsClient
{
    /**
     * Hosts the cover images are downloaded from. Other URLs are never fetched (SSRF protection).
     */
    public const IMAGE_HOSTS = ['i.discogs.com', 'img.discogs.com', 'st.discogs.com'];

    public function __construct(
        private readonly ?string $token,
        private readonly string $userAgent,
        private readonly string $baseUrl,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            config('services.discogs.token'),
            config('services.discogs.user_agent'),
            config('services.discogs.base_url'),
        );
    }

    public function isConfigured(): bool
    {
        return filled($this->token);
    }

    /**
     * Search releases, e.g. ['barcode' => '...'], ['catno' => '...'] or ['q' => 'artist title'].
     *
     * @param  array<string, string>  $criteria
     * @return array<int, array<string, mixed>>
     */
    public function search(array $criteria, int $perPage = 15): array
    {
        $criteria = array_filter($criteria, fn ($value) => filled($value));
        if ($criteria === []) {
            return [];
        }

        $query = [...$criteria, 'type' => 'release', 'per_page' => $perPage];

        return Cache::remember('discogs:search:'.md5(json_encode($query)), now()->addHour(), function () use ($query) {
            return $this->get('/database/search', $query)['results'] ?? [];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function release(int $id): array
    {
        return Cache::remember("discogs:release:{$id}", now()->addDay(), fn () => $this->get("/releases/{$id}"));
    }

    /**
     * @return array<string, mixed>
     */
    public function master(int $id): array
    {
        return Cache::remember("discogs:master:{$id}", now()->addWeek(), fn () => $this->get("/masters/{$id}"));
    }

    /**
     * Suggested prices per condition, e.g. ["Very Good Plus (VG+)" => ["currency" => "EUR", "value" => 12.5]].
     * Discogs only answers when the seller settings of the account are filled in.
     *
     * @return array<string, array{currency: string, value: float}>
     */
    public function priceSuggestions(int $releaseId): array
    {
        return $this->get("/marketplace/price_suggestions/{$releaseId}");
    }

    /**
     * Lowest price and number of offers in the marketplace.
     *
     * @return array<string, mixed>
     */
    public function marketplaceStats(int $releaseId): array
    {
        return $this->get("/marketplace/stats/{$releaseId}");
    }

    /**
     * Downloads a cover image from Discogs. Returns [content, extension].
     *
     * @return array{0: string, 1: string}
     */
    public function downloadImage(string $url): array
    {
        $parts = parse_url($url);
        if (($parts['scheme'] ?? null) !== 'https' || ! in_array($parts['host'] ?? '', self::IMAGE_HOSTS, true)) {
            throw new DiscogsException(__('Das Bild stammt nicht von Discogs.'));
        }

        // No redirects: the host check above must apply to the URL that is really fetched.
        $response = $this->request()->withOptions(['allow_redirects' => false])->get($url);
        $type = strtolower((string) $response->header('Content-Type'));
        $extension = match (true) {
            str_contains($type, 'png') => 'png',
            str_contains($type, 'webp') => 'webp',
            str_contains($type, 'gif') => 'gif',
            str_contains($type, 'jpeg'), str_contains($type, 'jpg') => 'jpg',
            default => null,
        };

        if (! $response->successful() || $extension === null || strlen($response->body()) > 8 * 1024 * 1024) {
            throw new DiscogsException(__('Das Cover konnte nicht von Discogs geladen werden.'));
        }

        return [$response->body(), $extension];
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function get(string $path, array $query = []): array
    {
        if (! $this->isConfigured()) {
            throw new DiscogsException(__('Discogs ist nicht eingerichtet. Bitte DISCOGS_TOKEN in der .env eintragen.'));
        }

        try {
            $response = $this->request()->get(rtrim($this->baseUrl, '/').$path, $query);
        } catch (ConnectionException) {
            throw new DiscogsException(__('Discogs ist gerade nicht erreichbar.'));
        }

        $this->throwOnError($response);

        return $response->json() ?? [];
    }

    private function request(): PendingRequest
    {
        return Http::withHeaders([
            'User-Agent' => $this->userAgent,
            'Authorization' => 'Discogs token='.$this->token,
            'Accept' => 'application/vnd.discogs.v2.discogs+json',
        ])->timeout(15)->connectTimeout(5);
    }

    private function throwOnError(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        throw new DiscogsException(match ($response->status()) {
            401, 403 => str_contains((string) $response->json('message'), 'seller settings')
                ? __('Für Preisvorschläge müssen im Discogs-Konto die Verkäufer-Einstellungen ausgefüllt sein.')
                : __('Discogs hat den Zugriff abgelehnt. Bitte den DISCOGS_TOKEN prüfen.'),
            404 => __('Bei Discogs nicht gefunden.'),
            429 => __('Zu viele Anfragen an Discogs. Bitte eine Minute warten.'),
            default => __('Discogs hat mit einem Fehler geantwortet (:status).', ['status' => $response->status()]),
        }, $response->status());
    }
}
