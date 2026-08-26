<?php

namespace App\Console\Commands;

use App\Services\DataQualityService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DataQualityScanCommand extends Command
{
    protected $signature = 'growth:data-quality';
    protected $description = 'Scan GoodTripLove data quality issues';

    public function handle(DataQualityService $quality): int
    {
        // Adapter les noms de tables aux modèles réels GoodTripLove.

        if (DB::getSchemaBuilder()->hasTable('videos')) {
            $videos = DB::table('videos')
                ->whereNull('country_id')
                ->orWhereNull('category_id')
                ->limit(500)
                ->get();

            foreach ($videos as $video) {
                $quality->report(
                    'missing_classification',
                    'video',
                    $video->id,
                    'Video missing country or category',
                    'warning'
                );
            }
        }

        $this->info('Data quality scan completed.');
        return self::SUCCESS;
    }
}
