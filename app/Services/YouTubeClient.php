<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin wrapper over the YouTube Data API v3.
 *
 * The free quota is 10 000 units/day and a single search costs 100 units, so
 * roughly 100 searches per day for the whole platform. Every call is metered
 * against a daily ledger and refused once the budget is spent — a 403
 * quotaExceeded from Google would otherwise poison the rest of the day.
 */
class YouTubeClient
{
    public function __construct(
        private YouTubeQuotaManager $quota,
        private ?string $apiKey = null,
    ) {
        $this->apiKey = $apiKey ?: config('goodtriplove.youtube.api_key');
    }

    public function isConfigured(): bool
    {
        return filled($this->apiKey);
    }

    /**
     * search.list — 100 units. Returns the raw items plus the units spent.
     *
     * @return array{items: array<int, array>, units: int}
     */
    public function search(string $query, array $options = []): array
    {
        $cost = config('goodtriplove.youtube.cost.search');
        $this->assertBudget($cost);

        $response = $this->request()->get('/search', array_filter([
            'part' => 'snippet',
            'q' => $query,
            'type' => 'video',
            'maxResults' => min((int) ($options['max_results'] ?? 25), 50),
            'order' => $options['order'] ?? 'relevance',
            'relevanceLanguage' => $options['locale'] ?? null,
            'regionCode' => $options['region_code'] ?? null,
            'videoEmbeddable' => 'true',       // we only ever embed
            'safeSearch' => 'moderate',
            'publishedAfter' => $options['published_after'] ?? null,
            'key' => $this->apiKey,
        ], fn ($value) => $value !== null && $value !== ''));

        $this->spend($cost);

        if (! $response->successful()) {
            throw new RuntimeException('YouTube search failed: '.$this->errorMessage($response->json()));
        }

        return ['items' => $response->json('items', []), 'units' => $cost];
    }

    /**
     * videos.list — 1 unit for up to 50 ids, which is how metrics stay cheap
     * to refresh. Always call this after a search: search snippets carry no
     * statistics, no duration and no embeddable flag.
     *
     * @param  array<int, string>  $ids
     * @return array{items: array<int, array>, units: int}
     */
    public function videos(array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids)));

        if ($ids === []) {
            return ['items' => [], 'units' => 0];
        }

        $items = [];
        $units = 0;
        $cost = config('goodtriplove.youtube.cost.videos');

        foreach (array_chunk($ids, config('goodtriplove.youtube.batch_size')) as $chunk) {
            $this->assertBudget($cost);

            $response = $this->request()->get('/videos', [
                'part' => 'snippet,contentDetails,statistics,status',
                'id' => implode(',', $chunk),
                'maxResults' => 50,
                'key' => $this->apiKey,
            ]);

            $this->spend($cost);
            $units += $cost;

            if (! $response->successful()) {
                throw new RuntimeException('YouTube videos.list failed: '.$this->errorMessage($response->json()));
            }

            $items = array_merge($items, $response->json('items', []));
        }

        return ['items' => $items, 'units' => $units];
    }

    /* ------------------------------------------------------------------ *
     | Quota ledger
     * ------------------------------------------------------------------ */

    public function unitsUsedToday(): int
    {
        return $this->quota->used();
    }

    /**
     * What is left before the configured hard-stop percentage, not before the
     * raw daily quota — the collector must stop with headroom so a burst never
     * gets Google to 403 the whole day.
     */
    public function unitsRemaining(): int
    {
        $limit = (int) config('core_operations.youtube.daily_quota');
        $hardStop = (int) floor($limit * ((int) config('core_operations.youtube.hard_stop_percent') / 100));

        return max(0, $hardStop - $this->quota->used());
    }

    public function canSpend(int $units): bool
    {
        return $this->quota->canSpend($units);
    }

    /** True once usage crosses the warning threshold shown in the admin. */
    public function isNearLimit(): bool
    {
        $limit = max(1, (int) config('core_operations.youtube.daily_quota'));

        return ($this->quota->used() / $limit) * 100 >= (int) config('core_operations.youtube.warning_percent');
    }

    private function assertBudget(int $units): void
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('YOUTUBE_API_KEY is not configured.');
        }

        if (! $this->canSpend($units)) {
            throw new QuotaExhaustedException(
                'Daily YouTube quota budget exhausted ('.$this->unitsUsedToday().' units used).'
            );
        }
    }

    private function spend(int $units): void
    {
        $this->quota->spend($units);
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl(config('goodtriplove.youtube.base_url'))
            ->timeout(config('goodtriplove.youtube.timeout'))
            ->acceptJson();
    }

    private function errorMessage(?array $body): string
    {
        return (string) data_get($body, 'error.message', 'unknown error');
    }
}
