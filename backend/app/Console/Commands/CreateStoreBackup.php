<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class CreateStoreBackup extends Command
{
    protected $signature = 'store:backup {--keep= : Override retention days}';
    protected $description = 'Create a database backup in the configured backup directory';

    public function handle(): int
    {
        $directory = (string) config('backup.path', storage_path('app/backups'));
        File::ensureDirectoryExists($directory);
        $filename = 'store-'.now()->format('Ymd-His').'.'.(config('database.default') === 'pgsql' ? 'dump' : 'sql');
        $target = $directory.DIRECTORY_SEPARATOR.$filename;
        $connection = config('database.connections.'.config('database.default'));

        if (config('database.default') === 'sqlite') {
            File::copy($connection['database'], $target);
        } else {
            $binary = config('backup.binary');
            $command = config('database.default') === 'pgsql'
                ? [$binary ?: 'pg_dump', '--format=custom', '--file='.$target, '--host='.$connection['host'], '--port='.$connection['port'], '--username='.$connection['username'], $connection['database']]
                : [$binary ?: 'mysqldump', '--host='.$connection['host'], '--port='.$connection['port'], '--user='.$connection['username'], '--result-file='.$target, $connection['database']];
            $process = new Process($command, base_path(), null, array_filter(['PGPASSWORD' => $connection['password'] ?? null, 'MYSQL_PWD' => $connection['password'] ?? null]));
            $process->setTimeout((int) config('backup.timeout', 3600));
            $process->run();
            if (! $process->isSuccessful()) {
                $this->error(trim($process->getErrorOutput()) ?: 'The database backup command failed.');
                @unlink($target);
                return self::FAILURE;
            }
        }

        $retention = (int) ($this->option('keep') ?: config('backup.retention_days', 30));
        foreach (File::files($directory) as $file) {
            if ($file->getMTime() < now()->subDays($retention)->timestamp) File::delete($file->getPathname());
        }
        $this->info('Backup created: '.$target);
        return self::SUCCESS;
    }
}
