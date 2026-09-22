<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;

class PruneAuditLogs extends Command
{
    protected $signature = 'audit:prune';

    protected $description = 'Delete audit log rows older than the configured retention period.';

    public function handle(): int
    {
        $days = (int) config('security.audit_retention_days', 365);
        $cutoff = now()->subDays($days);

        $deleted = AuditLog::query()->where('created_at', '<', $cutoff)->delete();

        $this->info("Pruned {$deleted} audit log row(s) older than {$days} day(s).");

        return self::SUCCESS;
    }
}
