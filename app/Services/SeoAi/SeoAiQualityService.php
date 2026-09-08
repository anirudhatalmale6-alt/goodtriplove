<?php

namespace App\Services\SeoAi;

use App\Models\SeoAiPageTranslation;
use Illuminate\Support\Str;

class SeoAiQualityService
{
    public function score(array $translations, array $context): array
    {
        $locales = array_keys(config('goodtriplove.locales'));
        $score = 0;
        $details = [];

        $coverage = count(array_intersect($locales, array_keys($translations)));
        $details['language_coverage'] = $coverage.'/'.count($locales);
        $score += (int) round(20 * ($coverage / max(1, count($locales))));

        $supportedPlaces = count($context['places'] ?? []);
        $details['supporting_places'] = $supportedPlaces;
        $score += min(20, $supportedPlaces * 4);

        $contentPoints = 0;
        $titles = [];
        foreach ($translations as $locale => $data) {
            $titles[] = Str::lower(trim($data['title'] ?? ''));
            $wordCount = str_word_count(strip_tags(collect($data['sections'] ?? [])->pluck('paragraphs')->flatten()->implode(' ')));
            if ($wordCount >= 450) $contentPoints += 4;
            elseif ($wordCount >= 250) $contentPoints += 2;
            if (strlen($data['meta_description'] ?? '') >= 110 && strlen($data['meta_description'] ?? '') <= 180) $contentPoints += 2;
            if (count($data['faq'] ?? []) >= 3) $contentPoints += 2;
        }
        $score += min(35, $contentPoints);

        $uniqueTitles = count(array_unique(array_filter($titles)));
        $details['unique_titles'] = $uniqueTitles;
        $score += $uniqueTitles === count($translations) ? 10 : 0;

        $hasLinks = $supportedPlaces >= 2;
        $details['internal_link_targets'] = $hasLinks;
        $score += $hasLinks ? 10 : 0;

        $genericPenalty = 0;
        foreach ($translations as $data) {
            $text = Str::lower(json_encode($data, JSON_UNESCAPED_UNICODE) ?: '');
            foreach (['lorem ipsum', 'as an ai', 'i cannot', 'placeholder', 'tbd'] as $needle) {
                if (str_contains($text, $needle)) $genericPenalty += 10;
            }
        }
        $details['generic_penalty'] = $genericPenalty;
        $score = max(0, min(100, $score - $genericPenalty));

        return ['score' => $score, 'details' => $details];
    }
}
