<?php

namespace App\Console\Commands;

use App\Models\Video;
use App\Services\QuotaExhaustedException;
use App\Services\VideoScorer;
use App\Services\YouTubeClient;
use Illuminate\Console\Command;

/**
 * Refreshes view counts and re-checks that each video still exists, is still
 * public and is still embeddable. videos.list costs 1 unit per 50 ids, so this
 * is the cheap half of the quota budget and can run often.
 *
 * A video that disappears from the response has been deleted or made private:
 * it is marked unavailable rather than deleted, so the moderation history and
 * the place association survive.
 */
class RefreshVideosCommand extends Command
{
    protected $signature = 'gtl:refresh-videos {--limit=200 : Maximum videos to refresh}';

    protected $description = 'Refresh video metrics and availability from the YouTube API';

    public function handle(YouTubeClient $youtube, VideoScorer $scorer): int
    {
        if (! $youtube->isConfigured()) {
            $this->warn('YOUTUBE_API_KEY is not configured.');

            return self::SUCCESS;
        }

        $videos = Video::query()
            ->where('is_available', true)
            ->orderByRaw('last_checked_at IS NULL DESC')
            ->orderBy('last_checked_at')
            ->limit((int) $this->option('limit'))
            ->get();

        if ($videos->isEmpty()) {
            $this->info('Nothing to refresh.');

            return self::SUCCESS;
        }

        try {
            $response = $youtube->videos($videos->pluck('provider_video_id')->all());
        } catch (QuotaExhaustedException $e) {
            $this->warn($e->getMessage());

            return self::SUCCESS;
        }

        $returned = collect($response['items'])->keyBy('id');
        $refreshed = 0;
        $lost = 0;

        foreach ($videos as $video) {
            $item = $returned->get($video->provider_video_id);

            if (! $item) {
                $video->update([
                    'is_available' => false,
                    'unavailable_reason' => 'not_returned_by_api',
                    'last_checked_at' => now(),
                ]);
                $lost++;

                continue;
            }

            if ($video->metrics_updated_at) {
                $video->previous_view_count = $video->view_count;
                $video->previous_metrics_at = $video->metrics_updated_at;
            }

            $public = data_get($item, 'status.privacyStatus') === 'public';

            $video->fill([
                'view_count' => (int) data_get($item, 'statistics.viewCount', 0),
                'like_count' => (int) data_get($item, 'statistics.likeCount', 0),
                'comment_count' => (int) data_get($item, 'statistics.commentCount', 0),
                'embeddable' => (bool) data_get($item, 'status.embeddable', true),
                'is_available' => $public,
                'unavailable_reason' => $public ? null : 'not_public',
                'metrics_updated_at' => now(),
                'last_checked_at' => now(),
            ]);

            $scorer->score($video)->save();
            $refreshed++;

            if (! $public) {
                $lost++;
            }
        }

        $this->info("Refreshed {$refreshed} videos · {$lost} no longer available · {$response['units']} quota units.");

        return self::SUCCESS;
    }
}
