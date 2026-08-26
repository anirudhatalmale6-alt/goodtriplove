<?php

namespace App\Services;

use App\Models\CollectorQuery;
use App\Models\CollectorRun;
use App\Models\Video;
use Carbon\CarbonInterval;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * The Video Collector.
 *
 * Finds public videos through the official YouTube Data API, stores only the
 * metadata needed to embed and rank them, classifies them and proposes an
 * association with a listing. Nothing is downloaded, copied or re-hosted:
 * playback always happens in YouTube's own player.
 *
 * Imported videos land in `pending` and are published by an administrator.
 */
class VideoCollectorService
{
    public function __construct(
        private YouTubeClient $youtube,
        private VideoClassifier $classifier,
        private VideoScorer $scorer,
        private PlaceMatcher $matcher,
    ) {}

    /**
     * Runs the due queries, newest-priority first, until the daily budget or
     * the requested number of queries runs out.
     *
     * @return array{queries: int, created: int, updated: int, units: int, stopped: ?string}
     */
    public function runDue(int $maxQueries = 5): array
    {
        $summary = ['queries' => 0, 'created' => 0, 'updated' => 0, 'units' => 0, 'stopped' => null];

        if (! $this->youtube->isConfigured()) {
            $summary['stopped'] = 'no_api_key';

            return $summary;
        }

        $queries = CollectorQuery::due()->limit($maxQueries)->get();

        foreach ($queries as $query) {
            if (! $this->youtube->canSpend(config('goodtriplove.youtube.cost.search'))) {
                $summary['stopped'] = 'quota';
                break;
            }

            $result = $this->runQuery($query);

            $summary['queries']++;
            $summary['created'] += $result['created'];
            $summary['updated'] += $result['updated'];
            $summary['units'] += $result['units'];

            if ($result['status'] === 'failed' && $result['quota']) {
                $summary['stopped'] = 'quota';
                break;
            }
        }

        return $summary;
    }

    /**
     * @return array{status: string, created: int, updated: int, units: int, quota: bool}
     */
    public function runQuery(CollectorQuery $query): array
    {
        $run = CollectorRun::create([
            'collector_query_id' => $query->id,
            'status' => 'running',
            'started_at' => now(),
        ]);

        $created = 0;
        $updated = 0;
        $units = 0;

        try {
            $search = $this->youtube->search($query->query, [
                'max_results' => $query->max_results,
                'locale' => $query->locale,
                'region_code' => $query->region_code,
                'order' => 'relevance',
            ]);

            $units += $search['units'];

            $ids = collect($search['items'])
                ->pluck('id.videoId')
                ->filter()
                ->values()
                ->all();

            if ($ids === []) {
                $run->update([
                    'status' => 'success',
                    'quota_units' => $units,
                    'items_returned' => 0,
                    'finished_at' => now(),
                    'message' => 'No results.',
                ]);

                $query->update(['last_run_at' => now(), 'runs_count' => $query->runs_count + 1]);

                return ['status' => 'success', 'created' => 0, 'updated' => 0, 'units' => $units, 'quota' => false];
            }

            // search.list returns no statistics, no duration and no embeddable
            // flag, so a videos.list pass is mandatory before we can rank.
            $details = $this->youtube->videos($ids);
            $units += $details['units'];

            foreach ($details['items'] as $item) {
                $outcome = $this->importItem($item, $query);
                $outcome === 'created' ? $created++ : $updated++;
            }

            $run->update([
                'status' => 'success',
                'quota_units' => $units,
                'items_returned' => count($details['items']),
                'items_created' => $created,
                'items_updated' => $updated,
                'finished_at' => now(),
            ]);

            $query->update([
                'last_run_at' => now(),
                'runs_count' => $query->runs_count + 1,
                'videos_found' => $query->videos_found + count($details['items']),
                'videos_imported' => $query->videos_imported + $created,
            ]);

            return ['status' => 'success', 'created' => $created, 'updated' => $updated, 'units' => $units, 'quota' => false];
        } catch (QuotaExhaustedException $e) {
            $run->update([
                'status' => 'skipped',
                'quota_units' => $units,
                'message' => $e->getMessage(),
                'finished_at' => now(),
            ]);

            return ['status' => 'failed', 'created' => $created, 'updated' => $updated, 'units' => $units, 'quota' => true];
        } catch (\Throwable $e) {
            Log::error('Collector query failed', ['query' => $query->id, 'error' => $e->getMessage()]);

            $run->update([
                'status' => 'failed',
                'quota_units' => $units,
                'message' => Str::limit($e->getMessage(), 500),
                'finished_at' => now(),
            ]);

            $query->update(['last_run_at' => now(), 'runs_count' => $query->runs_count + 1]);

            return ['status' => 'failed', 'created' => $created, 'updated' => $updated, 'units' => $units, 'quota' => false];
        }
    }

    /**
     * Creates or refreshes one video from a videos.list item.
     */
    public function importItem(array $item, ?CollectorQuery $query = null): string
    {
        $videoId = data_get($item, 'id');
        $snippet = data_get($item, 'snippet', []);
        $statistics = data_get($item, 'statistics', []);
        $status = data_get($item, 'status', []);

        $video = Video::firstOrNew([
            'provider' => 'youtube',
            'provider_video_id' => $videoId,
        ]);

        $isNew = ! $video->exists;

        // Keep the previous snapshot so trending can be a delta, not a guess.
        if (! $isNew && $video->metrics_updated_at) {
            $video->previous_view_count = $video->view_count;
            $video->previous_metrics_at = $video->metrics_updated_at;
        }

        $video->fill([
            'title' => Str::limit((string) data_get($snippet, 'title'), 250, ''),
            'description' => data_get($snippet, 'description'),
            'channel_id' => data_get($snippet, 'channelId'),
            'channel_title' => data_get($snippet, 'channelTitle'),
            'published_at' => data_get($snippet, 'publishedAt'),
            'duration_seconds' => $this->parseDuration(data_get($item, 'contentDetails.duration')),
            'thumbnail_url' => data_get($snippet, 'thumbnails.medium.url'),
            'thumbnail_hq_url' => data_get($snippet, 'thumbnails.maxres.url')
                ?? data_get($snippet, 'thumbnails.high.url'),
            'language' => $this->normalizeLanguage(
                data_get($snippet, 'defaultAudioLanguage') ?? data_get($snippet, 'defaultLanguage')
            ),
            'embeddable' => (bool) data_get($status, 'embeddable', true),
            'is_available' => data_get($status, 'privacyStatus') === 'public',
            'view_count' => (int) data_get($statistics, 'viewCount', 0),
            'like_count' => (int) data_get($statistics, 'likeCount', 0),
            'comment_count' => (int) data_get($statistics, 'commentCount', 0),
            'metrics_updated_at' => now(),
            'last_checked_at' => now(),
            'source' => 'collector',
        ]);

        if ($isNew) {
            $video->status = Video::STATUS_PENDING;
        }

        $this->classifier->classify($video, array_filter([
            'country_id' => $query?->country_id,
            'city_id' => $query?->city_id,
            'category_id' => $query?->category_id,
        ]));

        $video->relevance_score = $this->relevance($video);
        $this->scorer->score($video);
        $video->save();

        $this->matcher->attach($video);

        return $isNew ? 'created' : 'updated';
    }

    /**
     * How well the video fits the place it is filed under. Feeds the
     * "most relevant" ranking and the auto-publication threshold.
     */
    private function relevance(Video $video): float
    {
        $weights = config('goodtriplove.scoring.relevance');
        $score = 0.0;

        if ($video->city_id) {
            $score += $weights['title_city_weight'];
        }

        if ($video->category_id) {
            $score += $weights['category_weight'];
        }

        if ($video->places()->exists()) {
            $score += $weights['title_place_weight'];
        }

        $score += (float) $video->classification_confidence * $weights['ai_weight'];

        return round(min(1.0, $score), 4);
    }

    private function parseDuration(?string $iso8601): ?int
    {
        if (! $iso8601) {
            return null;
        }

        try {
            return (int) CarbonInterval::make($iso8601)?->totalSeconds;
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeLanguage(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        return Str::lower(Str::before($value, '-'));
    }
}
