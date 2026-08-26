<?php

namespace App\Console\Commands;

use App\Services\DataQualityService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VideoHealthCommand extends Command
{
    protected $signature = 'growth:video-health';
    protected $description = 'Check video records for stale or broken embeds';

    public function handle(DataQualityService $quality): int
    {
        // Le freelance doit brancher les contrôles officiels des plateformes.
        // Ne pas scraper de façon interdite.

        if (!DB::getSchemaBuilder()->hasTable('videos')) {
            $this->warn('videos table not found.');
            return self::SUCCESS;
        }

        $staleDays = config('growth_ops.data_quality.stale_days');

        $videos = DB::table('videos')
            ->where('updated_at','<',now()->subDays($staleDays))
            ->limit(config('growth_ops.video_check_batch',100))
            ->get();

        foreach ($videos as $video) {
            $quality->report(
                'stale_video_metadata',
                'video',
                $video->id,
                'Video metadata has not been refreshed recently',
                'info'
            );
        }

        $this->info('Video health scan completed.');
        return self::SUCCESS;
    }
}
