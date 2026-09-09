<?php

namespace Tests\Unit;

use App\Services\SeoAi\SeoAiQualityService;
use Tests\TestCase;

class SeoAiQualityServiceTest extends TestCase
{
    public function test_thin_content_scores_below_publish_threshold(): void
    {
        $service = new SeoAiQualityService();
        $result = $service->score(['fr' => ['title'=>'Test','meta_description'=>'short','sections'=>[],'faq'=>[]]], ['places'=>[]]);
        $this->assertLessThan(72, $result['score']);
    }
}
