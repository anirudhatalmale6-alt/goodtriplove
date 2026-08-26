<?php

namespace App\Console\Commands;

use App\Models\Video;
use App\Services\OllamaClient;
use App\Services\PlaceMatcher;
use App\Services\VideoClassifier;
use Illuminate\Console\Command;

/**
 * Second-pass classification for videos the text rules could not place.
 * Strictly capped per run: the model shares this server with other sites.
 */
class ClassifyVideosCommand extends Command
{
    protected $signature = 'gtl:classify {--limit= : Videos to classify in this run}';

    protected $description = 'Classify unresolved videos with the local model';

    public function handle(VideoClassifier $classifier, OllamaClient $ollama, PlaceMatcher $matcher): int
    {
        if (! $ollama->enabled()) {
            $this->warn('Ollama is disabled.');

            return self::SUCCESS;
        }

        if (! $ollama->isUp()) {
            $this->warn('Ollama is not reachable — skipping this run.');

            return self::SUCCESS;
        }

        $limit = (int) ($this->option('limit') ?: config('goodtriplove.ollama.max_per_run'));

        $videos = Video::query()
            ->where(fn ($q) => $q->whereNull('city_id')->orWhereNull('category_id'))
            ->where(fn ($q) => $q->whereNull('classified_by')->orWhere('classified_by', 'heuristic'))
            ->where('is_available', true)
            ->orderByDesc('view_count')
            ->limit($limit)
            ->get();

        $resolved = 0;

        foreach ($videos as $video) {
            $classifier->classify($video);
            $video->save();

            if ($video->city_id || $video->category_id) {
                $matcher->attach($video);
                $resolved++;
            }
        }

        $this->info("Classified {$videos->count()} videos · {$resolved} resolved.");

        return self::SUCCESS;
    }
}
