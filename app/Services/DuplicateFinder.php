<?php

namespace App\Services;

use App\Models\Video;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Finds the same clip imported more than once.
 *
 * The database already refuses a second row for the same provider video id, so
 * an exact re-import cannot happen. What it cannot see is the same content
 * uploaded again under a *new* id — a channel reposting its own clip. The
 * catalogue had fifteen copies of one Faro restaurant video that way, and every
 * one of them was a legitimately distinct YouTube id.
 *
 * So the match is on the title, normalised: case folded, hashtags and emoji
 * dropped, punctuation removed and runs of spaces collapsed. That is what makes
 * "Melhor Restaurante em Faro, Algarve.  #restaurante" and
 * "Melhor Restaurante em Faro, Algarve  #restaurantes" land in one group.
 */
class DuplicateFinder
{
    /** Titles shorter than this are too generic to group on ("Lisboa"). */
    private const MIN_KEY_LENGTH = 12;

    public static function key(?string $title): string
    {
        $key = Str::lower((string) $title);

        // Hashtags carry no meaning here and are exactly what varies between
        // reposts of the same clip.
        $key = preg_replace('/#\S+/u', ' ', $key);

        // Anything that is not a letter, a digit or a space: punctuation,
        // emoji, box drawing, separators.
        $key = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $key);

        return trim(preg_replace('/\s+/u', ' ', $key));
    }

    /**
     * Groups of two or more videos sharing a normalised title.
     *
     * Rejected videos are left out: they have already been dealt with, and
     * showing them again would ask the same question twice.
     *
     * @return Collection<int, array{key: string, videos: Collection<int, Video>}>
     */
    public function groups(int $limit = 100): Collection
    {
        return Video::query()
            ->where('status', '!=', Video::STATUS_REJECTED)
            ->orderBy('id')
            ->get(['id', 'title', 'channel_title', 'provider', 'provider_video_id',
                'status', 'published_at', 'view_count', 'created_at'])
            ->groupBy(fn (Video $video) => self::key($video->title))
            ->reject(fn (Collection $videos, string $key) => mb_strlen($key) < self::MIN_KEY_LENGTH || $videos->count() < 2)
            ->sortByDesc(fn (Collection $videos) => $videos->count())
            ->take($limit)
            ->map(fn (Collection $videos, string $key) => ['key' => $key, 'videos' => $videos])
            ->values();
    }

    /**
     * Which copy to keep by default: the one that is already approved, then the
     * most watched, then the oldest — the one most likely to be the original
     * and the one any existing link points at.
     */
    public function suggestedKeeper(Collection $videos): Video
    {
        return $videos
            ->sortBy([
                fn (Video $a, Video $b) => ($b->status === Video::STATUS_APPROVED ? 1 : 0) <=> ($a->status === Video::STATUS_APPROVED ? 1 : 0),
                fn (Video $a, Video $b) => (int) $b->view_count <=> (int) $a->view_count,
                fn (Video $a, Video $b) => $a->id <=> $b->id,
            ])
            ->first();
    }
}
