<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class SecurityAppBackupCommand extends Command
{
    protected $signature = 'security:backup-app';
    protected $description = 'Create GoodTripLove application database/config backup';

    public function handle(): int
    {
        $dir = config('security_center.backup_path');
        File::ensureDirectoryExists($dir);

        $stamp = now()->format('Ymd_His');
        $dbFile = rtrim($dir,'/')."/goodtriplove_{$stamp}.sql";

        $host = config('database.connections.mysql.host','127.0.0.1');
        $port = config('database.connections.mysql.port','3306');
        $db = config('database.connections.mysql.database');
        $user = config('database.connections.mysql.username');
        $pass = config('database.connections.mysql.password');

        if (!$db || !$user) {
            $this->error('Database credentials missing.');
            return self::FAILURE;
        }

        $process = new Process([
            'mysqldump',
            '-h'.$host,
            '-P'.$port,
            '-u'.$user,
            '--single-transaction',
            '--quick',
            '--routines',
            $db,
        ]);

        $process->setEnv(['MYSQL_PWD' => $pass]);
        $process->setTimeout(120);
        $process->run();

        if (!$process->isSuccessful()) {
            $this->error($process->getErrorOutput());
            return self::FAILURE;
        }

        file_put_contents($dbFile, $process->getOutput());
        chmod($dbFile, 0600);

        $this->info("Backup created: {$dbFile}");
        return self::SUCCESS;
    }
}
