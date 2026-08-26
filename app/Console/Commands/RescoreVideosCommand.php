<?php

namespace App\Console\Commands;

use App\Models\Video;
use App\Services\VideoScorer;
use Illuminate\Console\Command;

class RescoreVideosCommand extends Command
{
    protected $signature = 'gtl:rescore';

    protected $description = 'Recompute popularity, trending and quality scores';

    public function handle(VideoScorer $scorer): int
    {
        $count = 0;

        Video::query()->chunkById(500, function ($videos) use ($scorer, &$count) {
            foreach ($videos as $video) {
                $scorer->score($video)->saveQuietly();
                $count++;
            }
        });

        $this->info("Rescored {$count} videos.");

        return self::SUCCESS;
    }
}
