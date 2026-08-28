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
    protected $signature = 'gtl:classify
        {--limit= : Videos to classify in this run}
        {--rescan : Re-examine every video, including ones already classified}
        {--no-model : Text rules only, even if the local model is available}';

    protected $description = 'Classify unresolved videos with the local model';

    public function handle(VideoClassifier $classifier, OllamaClient $ollama, PlaceMatcher $matcher): int
    {
        // The model improves the second pass, it is not required for the first.
        // Bailing out entirely when it is absent left every video wearing
        // whichever category its search term implied, with nothing to correct
        // it — so run the text rules either way and say which mode we are in.
        // A full --rescan of a large catalogue with the model on is an
        // overnight job on a shared box: one inference is ~30s. Sweep with the
        // text rules, and let the scheduled run work through the uncertain
        // ones a few at a time.
        $withModel = ! $this->option('no-model') && $ollama->enabled() && $ollama->isUp();

        if (! $withModel) {
            $this->warn(match (true) {
                (bool) $this->option('no-model') => 'Model skipped by request — text classification only.',
                $ollama->enabled() => 'Ollama is not reachable — running text classification only.',
                default => 'Ollama is disabled — running text classification only.',
            });
        }

        $limit = (int) ($this->option('limit') ?: config('goodtriplove.ollama.max_per_run'));

        // Includes videos that already HAVE a category but only because the
        // collector query implied one. Those are the wrong ones, and selecting
        // solely on NULL columns never saw them.
        $weak = (float) config('goodtriplove.ollama.review_below', 0.65);

        $videos = Video::query()
            // Never re-classify what an administrator has corrected by hand —
            // not even on --rescan. A human decision outranks every automatic
            // one, and silently reversing it is worse than leaving a gap.
            ->where(fn ($w) => $w->whereNull('classified_by')->orWhere('classified_by', '!=', 'admin'))
            ->unless($this->option('rescan'), fn ($q) => $q
                ->where(fn ($w) => $w->whereNull('city_id')
                    ->orWhereNull('category_id')
                    ->orWhereNull('classification_confidence')
                    ->orWhere('classification_confidence', '<', $weak))
                ->where(fn ($w) => $w->whereNull('classified_by')->orWhere('classified_by', 'heuristic')))
            ->where('is_available', true)
            ->orderByDesc('view_count')
            ->limit($limit)
            ->get();

        $resolved = 0;
        $changed = 0;

        $videos->load('collectorQuery');

        foreach ($videos as $video) {
            $before = $video->category_id;

            // Hand back the search that originally found this video, so a
            // re-run can still recognise agreement between the video's own
            // words and the query. Without it every rescan scored strictly
            // lower than the original import.
            $classifier->classify($video, array_filter([
                'country_id' => $video->collectorQuery?->country_id,
                'city_id' => $video->collectorQuery?->city_id,
                'category_id' => $video->collectorQuery?->category_id,
            ]) + ['allow_model' => $withModel]);

            $video->save();

            if ($video->category_id !== $before) {
                $changed++;
            }

            if ($video->city_id || $video->category_id) {
                $matcher->attach($video);
                $resolved++;
            }
        }

        $this->info(sprintf(
            'Classified %d videos · %d resolved · %d category changed · mode: %s',
            $videos->count(),
            $resolved,
            $changed,
            $withModel ? 'text + local model' : 'text only'
        ));

        return self::SUCCESS;
    }
}
