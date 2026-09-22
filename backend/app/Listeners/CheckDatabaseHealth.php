<?php

namespace App\Listeners;

use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CheckDatabaseHealth
{
    /**
     * Fail the /up health check when the primary database is unreachable.
     * Only critical dependencies are checked here: the database backs
     * orders, the database queue, the database cache store, and failed
     * jobs, so its availability implies readiness of those subsystems.
     * Optional third-party payment/courier APIs are never checked.
     */
    public function handle(DiagnosingHealth $event): void
    {
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $exception) {
            throw new RuntimeException('Database unreachable.', 0, $exception);
        }
    }
}
