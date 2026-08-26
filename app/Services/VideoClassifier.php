<?php

namespace App\Services;

use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Video;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Works out which country, city and category a video is about, and in which
 * language it is presented.
 *
 * Two passes: a deterministic text pass first (cheap, predictable, works
 * offline), then the local model only where the text pass was unsure. The
 * model never overrides a confident text match — it fills gaps.
 */
class VideoClassifier
{
    public function __construct(private OllamaClient $ollama) {}

    public function classify(Video $video, array $context = []): Video
    {
        $haystack = Str::lower($video->title.' '.Str::limit((string) $video->description, 600));

        $countryId = $context['country_id'] ?? null;
        $cityId = $context['city_id'] ?? null;
        $categoryId = $context['category_id'] ?? null;
        $confidence = 0.0;
        $by = 'heuristic';
        $raw = [];

        // --- pass 1: the text itself -----------------------------------
        if (! $cityId) {
            $city = $this->matchCity($haystack, $countryId);

            if ($city) {
                $cityId = $city->id;
                $countryId ??= $city->country_id;
                $confidence += 0.45;
            }
        } else {
            $confidence += 0.45;
        }

        if (! $countryId) {
            $country = $this->matchCountry($haystack);

            if ($country) {
                $countryId = $country->id;
                $confidence += 0.2;
            }
        } else {
            $confidence += 0.2;
        }

        $matchedCategory = $this->matchCategory($haystack);

        if ($matchedCategory) {
            $categoryId = $categoryId ?: $matchedCategory->id;
            $confidence += 0.2;
        } elseif ($categoryId) {
            $confidence += 0.1;
        }

        $language = $video->language ?: $this->guessLanguage($haystack);

        // --- pass 2: the local model, only where we are still unsure ----
        if ($confidence < 0.65 && $this->ollama->enabled()) {
            $ai = $this->askModel($video);

            if ($ai) {
                $by = 'ollama';
                $raw = $ai;

                if (! $cityId && ! empty($ai['city'])) {
                    $city = $this->matchCity(Str::lower($ai['city']), $countryId);

                    if ($city) {
                        $cityId = $city->id;
                        $countryId ??= $city->country_id;
                        $confidence += 0.25;
                    }
                }

                if (! $countryId && ! empty($ai['country'])) {
                    $country = $this->matchCountry(Str::lower($ai['country']));

                    if ($country) {
                        $countryId = $country->id;
                        $confidence += 0.15;
                    }
                }

                if (! $categoryId && ! empty($ai['category'])) {
                    $category = Category::active()->where('slug', Str::slug($ai['category']))->first()
                        ?? $this->matchCategory(Str::lower($ai['category']));

                    if ($category) {
                        $categoryId = $category->id;
                        $confidence += 0.1;
                    }
                }

                if (! $language && ! empty($ai['language'])) {
                    $language = Str::lower(Str::substr($ai['language'], 0, 5));
                }
            }
        }

        $video->country_id = $countryId;
        $video->city_id = $cityId;
        $video->category_id = $categoryId;
        $video->language = $language;
        $video->classification = $raw ?: null;
        $video->classified_by = $by;
        $video->classification_confidence = round(min(1.0, $confidence), 4);
        $video->classified_at = now();

        return $video;
    }

    private function askModel(Video $video): ?array
    {
        $system = 'You classify travel videos. Answer with JSON only, no explanation. '
            .'Use null when you are not sure. Never invent a place that is not mentioned.';

        $prompt = "Video title: {$video->title}\n"
            ."Channel: {$video->channel_title}\n"
            .'Description: '.Str::limit((string) $video->description, 500)."\n\n"
            ."Return JSON with these keys:\n"
            .'{"country": string|null, "city": string|null, "place_name": string|null, '
            .'"category": one of ["restaurant","local-food","hotel","guest-house","bar-cafe",'
            .'"activity","place-to-visit","beach"]|null, "language": ISO 639-1 code|null}';

        return $this->ollama->json($prompt, $system);
    }

    private function matchCity(string $haystack, ?int $countryId = null): ?City
    {
        foreach ($this->cityIndex() as $entry) {
            if ($countryId && $entry['country_id'] !== $countryId) {
                continue;
            }

            foreach ($entry['names'] as $name) {
                if ($this->containsWord($haystack, $name)) {
                    return City::find($entry['id']);
                }
            }
        }

        return null;
    }

    private function matchCountry(string $haystack): ?Country
    {
        foreach ($this->countryIndex() as $entry) {
            foreach ($entry['names'] as $name) {
                if ($this->containsWord($haystack, $name)) {
                    return Country::find($entry['id']);
                }
            }
        }

        return null;
    }

    private function matchCategory(string $haystack): ?Category
    {
        $best = null;
        $bestHits = 0;

        foreach ($this->categoryIndex() as $entry) {
            $hits = 0;

            foreach ($entry['terms'] as $term) {
                if ($this->containsWord($haystack, $term)) {
                    $hits++;
                }
            }

            if ($hits > $bestHits) {
                $bestHits = $hits;
                $best = $entry['id'];
            }
        }

        return $best ? Category::find($best) : null;
    }

    /**
     * Word-boundary match on the unaccented forms, so "porto" does not fire on
     * "opportunity" and "Le Porto" still matches "porto".
     */
    private function containsWord(string $haystack, string $needle): bool
    {
        $needle = trim(Str::lower(Str::ascii($needle)));

        if (Str::length($needle) < 3) {
            return false;
        }

        return (bool) preg_match('/(?<![\p{L}])'.preg_quote($needle, '/').'(?![\p{L}])/u', Str::ascii($haystack));
    }

    private function guessLanguage(string $text): ?string
    {
        $markers = [
            'fr' => [' le ', ' la ', ' les ', ' des ', ' une ', ' pour ', ' avec ', 'meilleur'],
            'pt' => [' os ', ' as ', ' uma ', ' para ', ' com ', ' melhor', ' comida'],
            'es' => [' los ', ' las ', ' una ', ' para ', ' con ', ' mejor', ' comida'],
            'it' => [' il ', ' gli ', ' una ', ' per ', ' con ', ' migliore', ' cibo'],
            'de' => [' der ', ' die ', ' das ', ' und ', ' mit ', ' beste', ' essen'],
            'en' => [' the ', ' best ', ' food ', ' with ', ' guide ', ' travel '],
        ];

        $padded = ' '.$text.' ';
        $scores = [];

        foreach ($markers as $locale => $words) {
            $scores[$locale] = 0;

            foreach ($words as $word) {
                $scores[$locale] += substr_count($padded, $word);
            }
        }

        arsort($scores);
        $top = array_key_first($scores);

        return $scores[$top] > 1 ? $top : null;
    }

    /* ------------------------------------------------------------------ *
     | Cached lookup indexes — rebuilt whenever the admin edits geography.
     * ------------------------------------------------------------------ */

    private function cityIndex(): array
    {
        return Cache::remember('classifier:cities', 3600, function () {
            return City::active()->get()->map(fn (City $city) => [
                'id' => $city->id,
                'country_id' => $city->country_id,
                'names' => array_values(array_unique(array_filter(
                    array_merge(array_values((array) $city->name), [$city->slug])
                ))),
            ])->all();
        });
    }

    private function countryIndex(): array
    {
        return Cache::remember('classifier:countries', 3600, function () {
            return Country::active()->get()->map(fn (Country $country) => [
                'id' => $country->id,
                'names' => array_values(array_unique(array_filter(
                    array_merge(array_values((array) $country->name), [$country->slug])
                ))),
            ])->all();
        });
    }

    private function categoryIndex(): array
    {
        return Cache::remember('classifier:categories', 3600, function () {
            return Category::active()->get()->map(function (Category $category) {
                $terms = array_merge(array_values((array) $category->name), [$category->slug]);

                foreach ((array) $category->search_terms as $localeTerms) {
                    $terms = array_merge($terms, (array) $localeTerms);
                }

                return [
                    'id' => $category->id,
                    'terms' => array_values(array_unique(array_filter($terms))),
                ];
            })->all();
        });
    }
}
