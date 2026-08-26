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
            '%d queries · %d new · %d refreshed · %d quota units%s',
            $result['queries'],
            $result['created'],
            $result['updated'],
            $result['units'],
            $result['stopped'] === 'quota' ? ' · stopped: daily budget reached' : ''
        ));

        return self::SUCCESS;
    }
}
