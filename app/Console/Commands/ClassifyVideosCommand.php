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
        // The model improves the second pass, it is not required for the first.
        // Bailing out entirely when it is absent left every video wearing
        // whichever category its search term implied, with nothing to correct
        // it — so run the text rules either way and say which mode we are in.
        $withModel = $ollama->enabled() && $ollama->isUp();

        if (! $withModel) {
            $this->warn($ollama->enabled()
                ? 'Ollama is not reachable — running text classification only.'
                : 'Ollama is disabled — running text classification only.');
        }

        $limit = (int) ($this->option('limit') ?: config('goodtriplove.ollama.max_per_run'));

        // Includes videos that already HAVE a category but only because the
        // collector query implied one. Those are the wrong ones, and selecting
        // solely on NULL columns never saw them.
        $weak = (float) config('goodtriplove.ollama.review_below', 0.65);

        $videos = Video::query()
            ->where(fn ($q) => $q->whereNull('city_id')
                ->orWhereNull('category_id')
                ->orWhereNull('classification_confidence')
                ->orWhere('classification_confidence', '<', $weak))
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

        $this->info(sprintf(
            'Classified %d videos · %d resolved · mode: %s',
            $videos->count(),
            $resolved,
            $withModel ? 'text + local model' : 'text only'
        ));

        return self::SUCCESS;
    }
}
