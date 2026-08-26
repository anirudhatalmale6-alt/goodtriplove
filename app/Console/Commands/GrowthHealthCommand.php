<?php

namespace App\Console\Commands;

use App\Services\OperationsHealthService;
use Illuminate\Console\Command;

class GrowthHealthCommand extends Command
{
    protected $signature = 'growth:health';
    protected $description = 'Run GoodTripLove operations health checks';

    public function handle(OperationsHealthService $health): int
    {
        foreach ($health->runAll() as $row) {
            $this->line($row['service'].': '.$row['status'].' - '.$row['message']);
        }

        return self::SUCCESS;
    }
}
