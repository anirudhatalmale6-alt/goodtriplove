<?php

namespace App\Services\SeoAi;

use App\Models\SeoAiPage;
use App\Models\SeoAiPageTranslation;
use App\Models\SeoAiSetting;
use Illuminate\Support\Facades\DB;
use Throwable;

class SeoAiPublisher
{
    public function __construct(
        private SeoAiPlanner $planner,
        private SeoGeneratorFactory $generators,
        private SeoAiQualityService $quality,
    ) {}

    public function generateNext(bool $forceReview = false): ?SeoAiPage
    {
        $topic = $this->planner->nextTopic();
        if (!$topic) return null;

        $page = SeoAiPage::create([
            ...$topic,
            'status' => SeoAiPage::STATUS_DRAFT,
            'indexable' => false,
        ]);

        try {
            $context = $this->planner->context($topic);
            $translations = $this->generators->make()->generate($context);
            $quality = $this->quality->score($translations, $context);
            $settings = SeoAiSetting::current();
            $threshold = $settings->quality_threshold ?: config('seo_ai.quality_threshold', 72);
            $publish = !$forceReview && $settings->auto_publish && $quality['score'] >= $threshold;

            DB::transaction(function () use ($page, $context, $translations, $quality, $publish) {
                foreach ($translations as $locale => $data) {
                    SeoAiPageTranslation::updateOrCreate(
                        ['seo_ai_page_id' => $page->id, 'locale' => $locale],
                        [
                            'slug' => $data['slug'], 'title' => $data['title'],
                            'meta_description' => $data['meta_description'], 'h1' => $data['h1'],
                            'excerpt' => $data['excerpt'], 'content_json' => $data['sections'],
                            'faq_json' => $data['faq'], 'keywords_json' => $data['keywords'],
                        ]
                    );
                }

                $page->update([
                    'quality_score' => $quality['score'], 'quality_details' => $quality['details'],
                    'generation_context' => $context, 'generated_at' => now(),
                    'status' => $publish ? SeoAiPage::STATUS_PUBLISHED : SeoAiPage::STATUS_REVIEW,
                    'indexable' => $publish,
                    'published_at' => $publish ? now() : null,
                    'last_error' => null,
                ]);
            });
        } catch (Throwable $e) {
            $page->update(['status' => SeoAiPage::STATUS_REVIEW, 'indexable' => false, 'last_error' => $e->getMessage()]);
            report($e);
        }

        return $page->fresh('translations');
    }
}
