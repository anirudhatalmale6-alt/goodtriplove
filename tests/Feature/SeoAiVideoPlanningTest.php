<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Video;
use App\Services\SeoAi\SeoAiPlanner;
use App\Services\SeoAi\SeoAiQualityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Planning a page when the catalogue has videos and no places.
 *
 * This is the production situation: 3 000+ videos, zero places. Before this,
 * every candidate query counted places only, so the module was installed,
 * deployed, green — and could not produce a single page.
 */
class SeoAiVideoPlanningTest extends TestCase
{
    use RefreshDatabase;

    private int $seq = 0;

    private function scaffold(): array
    {
        $country = Country::create([
            'code' => 'PT',
            'slug' => 'portugal', 'name' => ['fr' => 'Portugal', 'en' => 'Portugal'], 'is_active' => true,
        ]);
        $city = City::create([
            'slug' => 'lisbonne', 'country_id' => $country->id,
            'name' => ['fr' => 'Lisbonne', 'en' => 'Lisbon'], 'is_active' => true,
        ]);
        $category = Category::create([
            'slug' => 'restaurants', 'name' => ['fr' => 'Restaurants', 'en' => 'Restaurants'], 'is_active' => true,
        ]);

        return [$country, $city, $category];
    }

    private function video(array $attributes = []): Video
    {
        return Video::create(array_merge([
            'provider' => 'youtube',
            'provider_video_id' => 'vid'.(++$this->seq),
            'title' => 'Une vidéo de test '.$this->seq,
            'status' => Video::STATUS_APPROVED,
            'is_available' => true,
        ], $attributes));
    }

    public function test_a_topic_is_planned_from_videos_when_there_are_no_places(): void
    {
        [$country, $city, $category] = $this->scaffold();

        foreach (range(1, 4) as $i) {
            $this->video([
                'country_id' => $country->id, 'city_id' => $city->id, 'category_id' => $category->id,
            ]);
        }

        $topic = app(SeoAiPlanner::class)->nextTopic();

        $this->assertNotNull($topic, 'no topic was planned from videos alone');
        $this->assertSame('city_category', $topic['topic_type']);
        $this->assertSame($city->id, $topic['city_id']);
        $this->assertSame(4, $topic['video_count']);
        $this->assertSame(0, $topic['place_count']);
    }

    /** Below the minimum there is not enough to write about, and that is correct. */
    public function test_a_thin_topic_is_not_planned(): void
    {
        [$country, $city, $category] = $this->scaffold();

        $this->video(['country_id' => $country->id, 'city_id' => $city->id, 'category_id' => $category->id]);

        $this->assertNull(app(SeoAiPlanner::class)->nextTopic());
    }

    /** Pending and unavailable videos are not something a visitor can watch. */
    public function test_only_approved_available_videos_count(): void
    {
        [$country, $city, $category] = $this->scaffold();

        foreach (range(1, 4) as $i) {
            $this->video([
                'country_id' => $country->id, 'city_id' => $city->id, 'category_id' => $category->id,
                'status' => Video::STATUS_PENDING,
            ]);
        }
        $this->video([
            'country_id' => $country->id, 'city_id' => $city->id, 'category_id' => $category->id,
            'is_available' => false,
        ]);

        $this->assertNull(app(SeoAiPlanner::class)->nextTopic());
    }

    public function test_the_context_hands_the_videos_to_the_model(): void
    {
        [$country, $city, $category] = $this->scaffold();

        foreach (range(1, 3) as $i) {
            $this->video([
                'country_id' => $country->id, 'city_id' => $city->id, 'category_id' => $category->id,
                'title' => "Meilleur restaurant de Lisbonne numéro {$i}",
                'channel_title' => 'visitlisbonfood',
            ]);
        }

        $planner = app(SeoAiPlanner::class);
        $context = $planner->context($planner->nextTopic());

        $this->assertCount(3, $context['videos']);
        $this->assertSame('visitlisbonfood', $context['videos'][0]['channel']);
        $this->assertStringContainsString('Meilleur restaurant', $context['videos'][0]['title']);
    }

    /**
     * The name columns are cast to array and hold every translation. Passing
     * the attribute directly dropped a six-language JSON blob into the prompt
     * instead of "Lisbonne".
     */
    public function test_the_context_uses_readable_names_not_translation_blobs(): void
    {
        [$country, $city, $category] = $this->scaffold();

        foreach (range(1, 3) as $i) {
            $this->video([
                'country_id' => $country->id, 'city_id' => $city->id, 'category_id' => $category->id,
            ]);
        }

        $planner = app(SeoAiPlanner::class);
        $context = $planner->context($planner->nextTopic());

        $this->assertIsString($context['city']['name']);
        $this->assertSame('Lisbonne', $context['city']['name']);
        $this->assertSame('Portugal', $context['country']['name']);
    }

    /**
     * With places at zero, scoring places only capped every page at 70 — under
     * the default threshold of 72 — so nothing could ever be publishable.
     */
    public function test_a_page_supported_only_by_videos_can_pass_the_quality_gate(): void
    {
        $translations = [];

        foreach (array_keys(config('goodtriplove.locales')) as $i => $locale) {
            $translations[$locale] = [
                'title' => "Titre unique pour {$locale}",
                'meta_description' => str_pad("Description de la page en {$locale}. ", 140, 'texte utile. '),
                'sections' => [[
                    'heading' => 'Section',
                    'paragraphs' => [str_repeat('mot ', 500)],
                ]],
                'faq' => [
                    ['question' => 'Q1', 'answer' => 'A1'],
                    ['question' => 'Q2', 'answer' => 'A2'],
                    ['question' => 'Q3', 'answer' => 'A3'],
                ],
            ];
        }

        $result = app(SeoAiQualityService::class)->score($translations, [
            'places' => [],
            'videos' => array_fill(0, 8, ['id' => 1, 'title' => 'Une vidéo']),
        ]);

        $this->assertGreaterThanOrEqual(
            config('seo_ai.quality_threshold', 72),
            $result['score'],
            'a video-only page cannot reach the publication threshold'
        );
        $this->assertSame(8, $result['details']['supporting_videos']);
    }

    /**
     * The publisher must persist a planned topic without choking on the
     * planner's working numbers. This failed on the very first real run with
     * "Unknown column 'support_count'".
     */
    public function test_a_planned_topic_can_actually_be_persisted(): void
    {
        [$country, $city, $category] = $this->scaffold();

        foreach (range(1, 4) as $i) {
            $this->video([
                'country_id' => $country->id, 'city_id' => $city->id, 'category_id' => $category->id,
            ]);
        }

        \Illuminate\Support\Facades\Http::fake([
            '127.0.0.1:11434/api/generate' => \Illuminate\Support\Facades\Http::response([
                'response' => json_encode([
                    'slug' => 'lisbonne', 'title' => 'Titre', 'meta_description' => 'Description assez longue pour tenir.',
                    'h1' => 'Titre', 'excerpt' => 'Intro.', 'keywords' => ['a'],
                    'sections' => [['heading' => 'S', 'paragraphs' => ['P']]],
                    'faq' => [['question' => 'Q', 'answer' => 'A']],
                ]),
            ]),
        ]);

        $page = app(\App\Services\SeoAi\SeoAiPublisher::class)->generateNext(true);

        $this->assertNotNull($page, 'the topic was planned but no page was created');
        $this->assertDatabaseHas('seo_ai_pages', ['id' => $page->id, 'topic_type' => 'city_category']);
        $this->assertNull($page->last_error);
    }
}
