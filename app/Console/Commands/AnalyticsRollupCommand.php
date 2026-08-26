<?php

namespace App\Console\Commands;

use App\Models\AnalyticsEvent;
use App\Models\AnalyticsRollup;
use Illuminate\Console\Command;

class AnalyticsRollupCommand extends Command
{
    protected $signature = 'growth:analytics-rollup';
    protected $description = 'Aggregate GoodTripLove analytics';

    public function handle(): int
    {
        $date = now()->toDateString();

        $metrics = [
            'page_views' => AnalyticsEvent::whereDate('occurred_at',$date)->where('event','page_view')->count(),
            'video_clicks' => AnalyticsEvent::whereDate('occurred_at',$date)->where('event','video_click')->count(),
            'searches' => AnalyticsEvent::whereDate('occurred_at',$date)->where('event','search')->count(),
        ];

        foreach ($metrics as $metric => $value) {
            AnalyticsRollup::updateOrCreate(
                ['date'=>$date,'metric'=>$metric,'dimension'=>null],
                ['value'=>$value]
            );
        }

        $this->info('Analytics rollup completed.');
        return self::SUCCESS;
    }
}
