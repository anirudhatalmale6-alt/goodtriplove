<?php

namespace App\Console\Commands;

use App\Models\SeoAiSetting;
use App\Services\SeoAi\SeoAiPublisher;
use Illuminate\Console\Command;

class GenerateWeeklySeoAiPageCommand extends Command
{
    protected $signature = 'gtl:seo-ai-weekly {--review : Never auto-publish this run}';
    protected $description = 'Generate one quality-gated multilingual SEO page for GoodTripLove';

    public function handle(SeoAiPublisher $publisher): int
    {
        $settings = SeoAiSetting::current();
        if (!$settings->enabled) {
            $this->info('SEO AI is disabled.');
            return self::SUCCESS;
        }

        $page = $publisher->generateNext((bool) $this->option('review'));
        if (!$page) {
            $this->warn('No eligible SEO topic found.');
            return self::SUCCESS;
        }

        $this->info("SEO AI page #{$page->id}: {$page->status}, quality {$page->quality_score}/100");
        if ($page->last_error) $this->error($page->last_error);
        return $page->last_error ? self::FAILURE : self::SUCCESS;
    }
}
