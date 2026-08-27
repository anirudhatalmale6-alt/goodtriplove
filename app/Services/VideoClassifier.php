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

    /**
     * Is this result plausibly a travel video about the place we searched for?
     *
     * YouTube's search is associative, not literal, so a query is only a hint.
     * "melhores bares Madeira" returned carpentry, furniture and paint videos,
     * because *madeira* is Portuguese for wood; "Restaurant Tycoon 3" arrived
     * on a restaurants query. Importing those makes the site look abandoned.
     *
     * The test is deliberately conjunctive: the text must name a place we cover
     * AND say something about the subject. Either alone is satisfied by far too
     * much of YouTube.
     *
     * @return array{relevant: bool, reason: string}
     */
    public function relevance(Video $video, ?int $countryId = null): array
    {
        $title = Str::lower((string) $video->title);
        $description = Str::lower(Str::limit((string) $video->description, 600));
        $haystack = $title.' '.$description;

        $place = $this->matchCity($haystack, $countryId) ?: $this->matchCountry($haystack);

        if (! $place) {
            return ['relevant' => false, 'reason' => 'no place from our geography named in the text'];
        }

        if ($this->matchCategory($title, $description)) {
            return ['relevant' => true, 'reason' => 'place and subject both present'];
        }

        // A general travel video about a city we cover — "48 tips before
        // visiting Lisbon", "5 erreurs à ne pas faire à Madère" — belongs on
        // the site even though it is about no single category. Requiring a
        // category here threw away good content. Woodworking clips still fail:
        // they satisfy neither this nor the place test.
        if ($this->matchesTravelIntent($haystack)) {
            return ['relevant' => true, 'reason' => 'general travel video about a place we cover'];
        }

        return ['relevant' => false, 'reason' => 'names a place but nothing about travel, food, lodging or things to do'];
    }

    public function classify(Video $video, array $context = []): Video
    {
        $title = Str::lower((string) $video->title);
        $description = Str::lower(Str::limit((string) $video->description, 600));
        $haystack = $title.' '.$description;

        $countryId = $context['country_id'] ?? null;
        $cityId = $context['city_id'] ?? null;
        $categoryId = $context['category_id'] ?? null;
        // Geography and category are scored SEPARATELY and deliberately.
        //
        // They used to share one number, and because the collector query always
        // supplies a country and usually a city, every imported video started at
        // 0.65 before its category had been looked at even once. That did two
        // bad things: it made the "unsure" gate below unreachable, so the local
        // model never ran on a single collected video; and it reported high
        // confidence for a category nothing had actually verified.
        $geoConfidence = 0.0;
        $categoryConfidence = 0.0;
        $by = 'heuristic';
        $raw = [];

        // --- pass 1: the text itself -----------------------------------
        if (! $cityId) {
            $city = $this->matchCity($haystack, $countryId);

            if ($city) {
                $cityId = $city->id;
                $countryId ??= $city->country_id;
                $geoConfidence += 0.45;
            }
        } else {
            $geoConfidence += 0.45;
        }

        if (! $countryId) {
            $country = $this->matchCountry($haystack);

            if ($country) {
                $countryId = $country->id;
                $geoConfidence += 0.2;
            }
        } else {
            $geoConfidence += 0.2;
        }

        $matchedCategory = $this->matchCategory($title, $description);
        $queryCategoryId = $categoryId;

        if ($matchedCategory && $queryCategoryId && $matchedCategory->id === $queryCategoryId) {
            // The video's own words agree with the search that found it.
            $categoryId = $matchedCategory->id;
            $categoryConfidence = 0.9;
        } elseif ($matchedCategory) {
            // They disagree, or there was no query category. Trust the video's
            // own title and description: they describe THIS video, whereas the
            // collector query only describes what we went looking for. A search
            // for "porto bars" legitimately returns "Top 10 Porto Restaurants",
            // and filing that under Bars is exactly the error this avoids.
            $categoryId = $matchedCategory->id;
            $categoryConfidence = $queryCategoryId ? 0.55 : 0.6;
        } elseif ($queryCategoryId) {
            // Nothing in the text confirms it — the search term is the only
            // evidence, which is weak. Deliberately below the gate so the local
            // model gets a look at it.
            $categoryId = $queryCategoryId;
            $categoryConfidence = 0.35;
        }

        $language = $video->language ?: $this->guessLanguage($haystack);

        // --- pass 2: the local model, only where we are still unsure ----
        // Gated on the CATEGORY score alone: knowing which country we searched
        // says nothing about whether this is a hotel or a beach.
        //
        // `allow_model` is false during collection. One inference takes about
        // 30 seconds, so running it inline turned a 25-result import into a
        // twelve-minute job and made the collector look hung. The model pass is
        // a separate batch (gtl:classify) for exactly this reason.
        $allowModel = $context['allow_model'] ?? true;

        if ($allowModel && $categoryConfidence < 0.65 && $this->ollama->enabled()) {
            $ai = $this->askModel($video);

            if ($ai) {
                $by = 'ollama';
                $raw = $ai;

                if (! $cityId && ! empty($ai['city'])) {
                    $city = $this->matchCity(Str::lower($ai['city']), $countryId);

                    if ($city) {
                        $cityId = $city->id;
                        $countryId ??= $city->country_id;
                        $geoConfidence += 0.25;
                    }
                }

                if (! $countryId && ! empty($ai['country'])) {
                    $country = $this->matchCountry(Str::lower($ai['country']));

                    if ($country) {
                        $countryId = $country->id;
                        $geoConfidence += 0.15;
                    }
                }

                // The model is only asked when the category was weak, so its
                // answer is allowed to REPLACE a category we merely inherited
                // from the search term. Testing `! $categoryId` here would have
                // been dead code: the fallback above always sets one.
                if (! empty($ai['category'])) {
                    $category = Category::active()->where('slug', Str::slug($ai['category']))->first()
                        ?? $this->matchCategory(Str::lower($ai['category']));

                    if ($category) {
                        // Agreeing with the weak search-term guess is real
                        // corroboration from an independent source.
                        $categoryConfidence = $category->id === $queryCategoryId ? 0.8 : 0.7;
                        $categoryId = $category->id;
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
        $video->classified_by = $by;
        // This column drives the admin review queue, so it reports how sure we
        // are about the CATEGORY — the thing a reviewer actually corrects.
        // Geography is stored alongside it rather than averaged in, so a
        // well-located video with a guessed category still surfaces for review.
        $video->classification_confidence = round(min(1.0, $categoryConfidence), 4);
        $video->classification = array_filter([
            'raw' => $raw ?: null,
            'geo_confidence' => round(min(1.0, $geoConfidence), 4),
            'category_source' => $by,
        ]) ?: null;
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

    /**
     * Words that mark a video as being about travelling somewhere, in the six
     * languages the site speaks. Only ever consulted once a place we cover has
     * already been named, so these are not sensitive on their own.
     */
    private function matchesTravelIntent(string $haystack): bool
    {
        static $terms = [
            // fr
            'voyage', 'voyager', 'visiter', 'visite', 'sejour', 'escapade', 'itineraire',
            'que faire', 'guide', 'decouvrir', 'incontournable', 'erreurs', 'conseils',
            // en
            'travel', 'trip', 'visit', 'visiting', 'itinerary', 'things to do', 'guide',
            'tour', 'weekend', 'days in', 'hours in', 'tips', 'explore', 'discover',
            // pt
            'viagem', 'viajar', 'visitar', 'roteiro', 'passeio', 'dicas', 'descobrir',
            // es
            'viaje', 'viajar', 'visitar', 'ruta', 'consejos', 'descubrir', 'que ver',
            // it
            'viaggio', 'viaggiare', 'visitare', 'itinerario', 'consigli', 'scoprire',
            // de
            'reise', 'reisen', 'besuchen', 'sehenswurdigkeiten', 'tipps', 'entdecken',

            // Place-descriptor nouns. "PORTO a cidade mais incrível de
            // PORTUGAL" is plainly a travel video, but it names no category and
            // uses no verb of travelling, so it was being rejected. These are
            // only ever consulted after a place we cover has been named, and a
            // carpentry video says "madeira" without ever saying "cidade".
            'cidade', 'ville', 'city', 'ciudad', 'citta', 'stadt',
            'ilha', 'ile', 'island', 'isla', 'isola', 'insel',
            'village', 'pueblo', 'borgo', 'dorf', 'quartier', 'bairro', 'barrio',
            'centro historico', 'vieille ville', 'old town', 'altstadt',
            'destination', 'destino', 'destinazione', 'reiseziel',
        ];

        foreach ($terms as $term) {
            // containsWord() folds accents on both sides, so "itineraire"
            // matches "itinéraire". Do not pre-filter with a raw Str::contains:
            // it compares the unfolded haystack and would reject exactly those.
            if ($this->containsWord($haystack, $term)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Adjectives that decorate a search term without identifying a subject.
     * "meilleurs bars" and "meilleurs hôtels" differ only in the noun, so
     * indexing the adjective would make every category match every query.
     */
    private const QUALIFIERS = [
        'meilleurs', 'meilleures', 'melhores', 'mejores', 'migliori', 'beste', 'best', 'top',
        'les', 'des', 'the', 'and', 'shop', 'que', 'para', 'com', 'con', 'con', 'die', 'der',
    ];

    /**
     * The title is what the video is about; the description is where the
     * creator lists their gear, their other channels and every place they have
     * ever filmed. Weighting them equally is how "Beaches in Porto" ended up
     * under Activities — the word "beaches" appeared once in the title and the
     * description mentioned hiking, tours and restaurants. Title evidence is
     * therefore worth three times description evidence.
     */
    private function matchCategory(string $title, string $description = ''): ?Category
    {
        $best = null;
        $bestScore = 0.0;

        foreach ($this->categoryIndex() as $entry) {
            $score = 0.0;

            foreach ([[$title, 3.0], [$description, 1.0]] as [$text, $weight]) {
                if ($text === '') {
                    continue;
                }

                // A whole phrase is far stronger evidence than one of its words.
                foreach ($entry['phrases'] as $phrase) {
                    if ($this->containsWord($text, $phrase)) {
                        $score += 2.0 * $weight;
                    }
                }

                foreach ($entry['words'] as $word) {
                    if ($this->containsWord($text, $word, plural: true)) {
                        $score += 1.0 * $weight;
                    }
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $entry['id'];
            }
        }

        return $best ? Category::find($best) : null;
    }

    /**
     * Word-boundary match on the unaccented forms, so "porto" does not fire on
     * "opportunity" and "Le Porto" still matches "porto".
     */
    private function containsWord(string $haystack, string $needle, bool $plural = false): bool
    {
        $needle = trim(Str::lower(Str::ascii($needle)));

        if (Str::length($needle) < 3) {
            return false;
        }

        $pattern = preg_quote($needle, '/');

        // Category nouns only. "bar" must also match "bars", "praia" "praias",
        // "hotel" "hoteles". Never applied to place names: Porto and Portos are
        // not the same thing, and a false city is worse than a missed one.
        if ($plural) {
            $pattern = Str::endsWith($needle, 's')
                ? preg_quote(Str::beforeLast($needle, 's'), '/').'(?:s|es)?'
                : $pattern.'(?:s|es)?';
        }

        return (bool) preg_match('/(?<![\p{L}])'.$pattern.'(?![\p{L}])/u', Str::ascii($haystack));
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
                $phrases = array_merge(array_values((array) $category->name), [$category->slug]);

                foreach ((array) $category->search_terms as $localeTerms) {
                    $phrases = array_merge($phrases, (array) $localeTerms);
                }

                $phrases = array_values(array_unique(array_filter($phrases)));

                // Every stored term is a PHRASE — "Bars & cafés", "meilleurs
                // bars", "melhores bares". Matched whole, they never fire on a
                // title that just says "Bars", which is how "Lisbonne : Bars
                // insolites" and "Bar Funchal Madeira" were being discarded.
                // So keep the phrases (precise, worth more) and also index the
                // individual nouns inside them (broader, worth less).
                $words = [];

                foreach ($phrases as $phrase) {
                    foreach (preg_split('/[^\p{L}]+/u', Str::lower($phrase), -1, PREG_SPLIT_NO_EMPTY) as $word) {
                        if (Str::length($word) >= 3 && ! in_array($word, self::QUALIFIERS, true)) {
                            $words[] = $word;
                        }
                    }
                }

                return [
                    'id' => $category->id,
                    'phrases' => $phrases,
                    'words' => array_values(array_diff(array_unique($words), $phrases)),
                ];
            })->all();
        });
    }
}
