<?php

namespace App\Console\Commands;

use App\Services\VideoCollectorService;
use Illuminate\Console\Command;

class CollectVideosCommand extends Command
{
    protected $signature = 'gtl:collect {--queries=5 : How many saved searches to run}';

    protected $description = 'Run the due Video Collector searches against the YouTube Data API';

    public function handle(VideoCollectorService $collector): int
    {
        $result = $collector->runDue((int) $this->option('queries'));

        if ($result['stopped'] === 'no_api_key') {
            $this->warn('YOUTUBE_API_KEY is not configured — collector idle.');

            return self::SUCCESS;
        }

        $this->info(sprintf(
            '%d queries · %d new · %d refreshed · %d failed · %d quota units%s',
            $result['queries'],
            $result['created'],
            $result['updated'],
            $result['failed'],
            $result['units'],
            $result['stopped'] === 'quota' ? ' · stopped: daily budget reached' : ''
        ));

        // A run where every query errored spends no quota and imports nothing,
        // which reads exactly like "nothing new to collect". Say so, and exit
        // non-zero so the scheduler's output is not silently reassuring.
        if ($result['failed'] > 0) {
            $this->error('Last error: '.$result['last_error']);

            if ($result['failed'] === $result['queries']) {
                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }
}
