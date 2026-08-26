<?php

namespace App\Console\Commands;

use App\Services\SecurityHealthService;
use Illuminate\Console\Command;

class SecurityCenterHealthCommand extends Command
{
    protected $signature = 'security:center-health';
    protected $description = 'Run GoodTripLove security center health checks';

    public function handle(SecurityHealthService $health): int
    {
        foreach ($health->runAll() as $row) {
            $this->line($row['service'].': '.$row['status'].' - '.$row['message']);
        }

        return self::SUCCESS;
    }
}
