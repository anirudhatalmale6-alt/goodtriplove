<?php

namespace App\Console\Commands;

use App\Models\Video;
use App\Services\VideoClassifier;
use Illuminate\Console\Command;

/**
 * Removes videos the relevance gate would no longer accept.
 *
 * The gate was added after the first collection runs, so the catalogue still
 * contains results imported before it existed — carpentry clips from "madeira",
 * property listings, a video game. This re-judges what is already stored.
 *
 * Dry run by default: it prints what it would delete and changes nothing until
 * --force is passed. Anything a human has already reviewed is never touched,
 * whatever the gate now thinks.
 */
class PruneVideosCommand extends Command
{
    protected $signature = 'gtl:prune-videos
        {--force : Actually delete. Without this the command only reports}
        {--show=15 : How many examples to print}';

    protected $description = 'Delete never-reviewed videos that fail the relevance gate';

    public function handle(VideoClassifier $classifier): int
    {
        $doomed = [];

        Video::query()
            ->where('status', 'pending')
            ->whereNull('reviewed_at')
            ->whereNull('reviewed_by')
            ->chunkById(200, function ($videos) use ($classifier, &$doomed) {
                foreach ($videos as $video) {
                    $verdict = $classifier->relevance($video, $video->country_id);

                    if (! $verdict['relevant']) {
                        $doomed[] = $video;
                    }
                }
            });

        $protected = Video::where(fn ($q) => $q->whereNotNull('reviewed_at')->orWhere('status', '!=', 'pending'))->count();

        $this->line(sprintf(
            '%d of %d videos fail the gate. %d are protected because a human has reviewed them.',
            count($doomed),
            Video::count(),
            $protected
        ));

        foreach (array_slice($doomed, 0, (int) $this->option('show')) as $video) {
            $this->line('  - '.mb_substr($video->title, 0, 66));
        }

        if (! $this->option('force')) {
            $this->warn('Dry run — nothing deleted. Pass --force to apply.');

            return self::SUCCESS;
        }

        foreach ($doomed as $video) {
            $video->delete();
        }

        $this->info(count($doomed).' videos deleted.');

        return self::SUCCESS;
    }
}
