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
        $check('APP_URL is a valid http(s) URL', $this->validUrl((string) config('app.url')));
        $check('APP_KEY is set', (bool) config('app.key'));
        $check('Debug mode is off for production', ! $this->isProduction() || ! config('app.debug'), 'APP_DEBUG='.var_export((bool) config('app.debug'), true));

        if ($this->isProduction()) {
            $check('APP_URL is not localhost in production', ! $this->isLocalUrl((string) config('app.url')), (string) config('app.url'));
            $check('Frontend origins are not localhost in production', ! $this->originsAreLocal(), implode(',', $this->frontendOrigins()));
        }

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

        $this->info('Broadcasting & storage:');
        // A null/empty driver means broadcasting is disabled (test default).
        $broadcastDriver = config('broadcasting.default') ?? 'null';
        $check(
            'Broadcast driver is known',
            in_array($broadcastDriver, ['null', 'log', 'pusher', 'ably', 'redis'], true),
            'BROADCAST_CONNECTION='.$broadcastDriver
        );
        $this->warnIfMissingStorageLink();

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

    private function validUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false
            && in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);
    }

    private function isLocalUrl(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return $host === '' || $host === 'localhost' || $host === '127.0.0.1' || $host === '[::1]' || str_ends_with($host, '.local');
    }

    private function originsAreLocal(): bool
    {
        foreach ($this->frontendOrigins() as $origin) {
            if ($this->isLocalUrl($origin)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The public/storage symlink is environment-specific, so a missing
     * link is a warning rather than a failure — CMS/banner images need it.
     */
    private function warnIfMissingStorageLink(): void
    {
        $link = public_path('storage');

        if (! is_link($link) && ! is_dir($link)) {
            $this->warn('  [WARN] public/storage link is missing — run php artisan storage:link so uploaded CMS/banner images resolve.');
        }
    }
}
