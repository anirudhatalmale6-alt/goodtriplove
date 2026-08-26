<?php

namespace App\Services;

use App\Models\Video;

/**
 * Turns raw platform metrics into the four rankings the site exposes:
 * most viewed, most popular, trending, most relevant.
 *
 * Views alone are a poor ranking — an old 3 M-view video would permanently
 * outrank an excellent recent one. Popularity therefore blends reach,
 * engagement and freshness; trending looks only at the recent delta.
 */
class VideoScorer
{
    public function score(Video $video): Video
    {
        $video->popularity_score = $this->popularity($video);
        $video->trending_score = $this->trending($video);
        $video->quality_score = $this->quality($video);
        $video->scored_at = now();

        return $video;
    }

    public function popularity(Video $video): float
    {
        $config = config('goodtriplove.scoring.popularity');

        // log10 keeps a 3 M-view video ahead of a 200 k one without letting it
        // outrank everything by four orders of magnitude.
        $reach = min(1.0, log10(max(1, (int) $video->view_count)) / 7);

        $engagement = $this->engagementRate($video);
        $engagementScore = min(1.0, $engagement / 0.05);   // 5% likes+comments = full marks

        $freshness = $this->freshness($video, (int) $config['freshness_halflife_days']);

        return round(
            $reach * $config['views_weight']
            + $engagementScore * $config['engagement_weight']
            + $freshness * $config['freshness_weight'],
            4
        );
    }

    /**
     * Views gained since the previous metrics refresh, normalised per day.
     * A video with no previous snapshot scores 0 until it has been measured
     * twice — we do not guess a trend from a single data point.
     */
    public function trending(Video $video): float
    {
        $config = config('goodtriplove.scoring.trending');

        if (! $video->previous_metrics_at || ! $video->metrics_updated_at) {
            return 0.0;
        }

        $delta = max(0, (int) $video->view_count - (int) $video->previous_view_count);

        if ($delta < 1) {
            return 0.0;
        }

        $days = max(0.5, $video->previous_metrics_at->diffInDays($video->metrics_updated_at) ?: 0.5);
        $perDay = $delta / $days;

        if ($perDay < ($config['min_views'] / max(1, $config['window_days']))) {
            return 0.0;
        }

        return round(min(1.0, log10(max(1, $perDay)) / 5), 4);
    }

    public function quality(Video $video): float
    {
        $score = 0.0;

        // A usable card needs a title, a thumbnail and a duration that is not
        // a 20-second short or a 4-hour stream.
        if (filled($video->title)) {
            $score += 0.25;
        }

        if (filled($video->thumbnail_hq_url) || filled($video->thumbnail_url)) {
            $score += 0.25;
        }

        $duration = (int) $video->duration_seconds;

        if ($duration >= 60 && $duration <= 3600) {
            $score += 0.3;
        } elseif ($duration > 0) {
            $score += 0.1;
        }

        if ($video->embeddable) {
            $score += 0.2;
        }

        return round($score, 4);
    }

    public function engagementRate(Video $video): float
    {
        $views = max(1, (int) $video->view_count);

        return ((int) $video->like_count + (int) $video->comment_count) / $views;
    }

    private function freshness(Video $video, int $halfLifeDays): float
    {
        if (! $video->published_at) {
            return 0.0;
        }

        $ageDays = max(0, $video->published_at->diffInDays(now()));

        return round(2 ** (-$ageDays / max(1, $halfLifeDays)), 4);
    }
}
