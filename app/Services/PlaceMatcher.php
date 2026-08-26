<?php

namespace App\Services;

use App\Models\Place;
use App\Models\Video;
use Illuminate\Support\Str;

/**
 * Connects a video to the listing it is actually about.
 *
 * The rule that matters: an association is only proposed when the place name
 * appears in the video, in the right city. A weak match is left unconfirmed
 * for the moderation queue rather than published — a restaurant page showing
 * a video of a different restaurant is worse than a page with no video.
 */
class PlaceMatcher
{
    public const MIN_SCORE = 0.45;
    public const AUTO_CONFIRM_SCORE = 0.8;

    /**
     * @return array<int, array{place: Place, score: float, reason: string}>
     */
    public function candidates(Video $video, int $limit = 5): array
    {
        if (! $video->city_id && ! $video->country_id) {
            return [];
        }

        $haystack = Str::lower(Str::ascii($video->title.' '.Str::limit((string) $video->description, 800)));

        $places = Place::query()
            ->published()
            ->when($video->city_id, fn ($q) => $q->where('city_id', $video->city_id))
            ->when(! $video->city_id, fn ($q) => $q->where('country_id', $video->country_id))
            ->when($video->category_id, fn ($q) => $q->where(function ($sub) use ($video) {
                $sub->where('category_id', $video->category_id)
                    ->orWhere('subcategory_id', $video->category_id)
                    ->orWhereNull('category_id');
            }))
            ->limit(500)
            ->get();

        $matches = [];

        foreach ($places as $place) {
            [$score, $reason] = $this->score($place, $haystack, $video);

            if ($score >= self::MIN_SCORE) {
                $matches[] = ['place' => $place, 'score' => round($score, 4), 'reason' => $reason];
            }
        }

        usort($matches, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($matches, 0, $limit);
    }

    /**
     * Attaches the candidates and returns how many were linked.
     */
    public function attach(Video $video, int $limit = 3): int
    {
        $attached = 0;

        foreach ($this->candidates($video, $limit) as $index => $candidate) {
            $video->places()->syncWithoutDetaching([
                $candidate['place']->id => [
                    'match_score' => $candidate['score'],
                    'match_reason' => $candidate['reason'],
                    'is_primary' => $index === 0,
                    'confirmed' => $candidate['score'] >= self::AUTO_CONFIRM_SCORE,
                ],
            ]);

            $candidate['place']->increment('videos_count');
            $attached++;
        }

        return $attached;
    }

    /**
     * @return array{0: float, 1: string}
     */
    private function score(Place $place, string $haystack, Video $video): array
    {
        $name = Str::lower(Str::ascii($place->name));

        if (Str::length($name) < 3) {
            return [0.0, 'too_short'];
        }

        $score = 0.0;
        $reason = 'context';

        // Exact place name in the title is the only strong signal.
        $title = Str::lower(Str::ascii($video->title));

        if ($this->containsWord($title, $name)) {
            $score += 0.7;
            $reason = 'title';
        } elseif ($this->containsWord($haystack, $name)) {
            $score += 0.45;
            $reason = 'description';
        } else {
            // Without the name we do not associate at all: "a restaurant in
            // Porto" must not attach itself to every restaurant in Porto.
            return [0.0, 'no_name_match'];
        }

        if ($video->city_id && $video->city_id === $place->city_id) {
            $score += 0.2;
        }

        if ($video->category_id && in_array($video->category_id, [$place->category_id, $place->subcategory_id], true)) {
            $score += 0.1;
        }

        return [min(1.0, $score), $reason];
    }

    private function containsWord(string $haystack, string $needle): bool
    {
        return (bool) preg_match('/(?<![\p{L}])'.preg_quote($needle, '/').'(?![\p{L}])/u', $haystack);
    }
}
