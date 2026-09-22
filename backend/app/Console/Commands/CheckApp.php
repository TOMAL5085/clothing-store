<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckApp extends Command
{
    protected $signature = 'app:check';

    protected $description = 'Validate production-critical configuration without printing secret values.';

    public function handle(): int
    {
        $failures = 0;

        $check = function (string $label, bool $ok, string $detail = '') use (&$failures) {
            $status = $ok ? 'OK  ' : 'FAIL';
            $this->line("  [{$status}] {$label}".($detail !== '' ? " ({$detail})" : ''));
            if (! $ok) {
                $failures++;
            }
        };

        $this->info('Application:');
        $check('APP_URL is set', (bool) config('app.url'));
        $check('APP_KEY is set', (bool) config('app.key'));
        $check('Debug mode is off for production', ! $this->isProduction() || ! config('app.debug'), 'APP_DEBUG='.var_export((bool) config('app.debug'), true));

        $this->info('Database:');
        $connection = (string) config('database.default');
        $check('DB connection is configured', $connection !== '', $connection);
        try {
            DB::connection()->getPdo();
            $check('Database is reachable', true, $connection);
        } catch (\Throwable) {
            $check('Database is reachable', false, $connection);
        }

        $this->info('Queue & cache:');
        $check(
            'Queue connection is supported',
            in_array(config('queue.default'), ['sync', 'database', 'redis', 'sqs'], true),
            'QUEUE_CONNECTION='.config('queue.default')
        );
        $check(
            'Cache store is supported',
            in_array(config('cache.default'), ['array', 'file', 'database', 'redis', 'memcached'], true),
            'CACHE_STORE='.config('cache.default')
        );
        $check('Failed jobs use the database driver', str_starts_with((string) config('queue.failed.driver'), 'database'));

        $this->info('Mail:');
        $check('Mailer is configured', (bool) config('mail.default'), 'MAIL_MAILER='.config('mail.default'));
        $check('Mail sender is set', (bool) config('mail.from.address'));

        $this->info('Payments (presence only, values never printed):');
        $driver = (string) config('payments.driver', 'demo');
        $check('Payment driver is known', in_array($driver, ['demo', 'stripe', 'sslcommerz'], true), "driver={$driver}");
        if ($driver === 'stripe' || config('app.env') === 'production') {
            $check('Stripe credentials are present', (bool) config('payments.stripe.secret') && (bool) config('payments.stripe.webhook_secret'));
        }
        if ($driver === 'sslcommerz' || config('app.env') === 'production') {
            $check('SSLCOMMERZ credentials are present', (bool) config('payments.sslcommerz.store_id') && (bool) config('payments.sslcommerz.store_password'));
        }

        $this->info('Frontend:');
        $check('Frontend origin allowlist is set', count($this->frontendOrigins()) > 0, implode(',', $this->frontendOrigins()));

        if ($failures > 0) {
            $this->error("app:check found {$failures} problem(s).");

            return self::FAILURE;
        }

        $this->info('app:check passed.');

        return self::SUCCESS;
    }

    private function isProduction(): bool
    {
        return config('app.env') === 'production';
    }

    /** @return list<string> */
    private function frontendOrigins(): array
    {
        $configured = config('cors.allowed_origins', []);

        if (is_string($configured)) {
            $configured = array_map('trim', explode(',', $configured));
        }

        return array_values(array_filter((array) $configured));
    }
}
