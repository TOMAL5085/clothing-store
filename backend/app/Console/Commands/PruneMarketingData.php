<?php

namespace App\Console\Commands;

use App\Models\MarketingAttribution;
use App\Models\MarketingEvent;
use App\Models\MarketingEventDelivery;
use Illuminate\Console\Command;

class PruneMarketingData extends Command
{
    protected $signature = 'marketing:prune';

    protected $description = 'Delete marketing events, deliveries, and stale attributions older than the configured retention period.';

    public function handle(): int
    {
        $days = (int) config('marketing.retention_days', 90);
        $cutoff = now()->subDays($days);

        $events = 0;

        MarketingEvent::query()
            ->where('occurred_at', '<', $cutoff)
            ->orderBy('id')
            ->chunkById(1000, function ($rows) use (&$events) {
                $events += $rows->count();

                foreach ($rows as $row) {
                    $row->delete();
                }
            });

        // Deliveries cascade from events; only orphan leftovers (if any)
        // are swept here in the same bounded fashion.
        $deliveries = 0;

        MarketingEventDelivery::query()
            ->where('created_at', '<', $cutoff)
            ->whereNotIn('marketing_event_id', MarketingEvent::query()->select('id'))
            ->orderBy('id')
            ->chunkById(1000, function ($rows) use (&$deliveries) {
                $deliveries += $rows->count();

                foreach ($rows as $row) {
                    $row->delete();
                }
            });

        $attributions = MarketingAttribution::query()
            ->where('last_seen_at', '<', $cutoff)
            ->delete();

        $this->info("Pruned {$events} event(s), {$deliveries} orphan deliverie(s), {$attributions} attribution(s) older than {$days} day(s).");

        return self::SUCCESS;
    }
}
