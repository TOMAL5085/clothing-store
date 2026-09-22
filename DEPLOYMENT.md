# Production Deployment — Clothing Store (JAAJ)

This document describes how to deploy the already-built application. It
introduces no new features and assumes no particular cloud vendor.

Related: `PRODUCTION_CHECKLIST.md` (pre-flight verification),
`backend/.env.example` (every variable documented), `CONTEXT.md` Phase 14.

## A. Prerequisites

| Requirement | Version / notes |
|---|---|
| PHP | 8.3+ with `pdo_pgsql`, `mbstring`, `openssl`, `fileinfo`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath` |
| Composer | 2.x |
| PostgreSQL | 13+ (application targets PostgreSQL semantics) |
| Node.js + npm | 20+ (frontend build only; not needed at runtime) |
| Web server | Nginx/Apache/Caddy terminating HTTPS, proxying PHP-FPM |

No Redis, queue worker framework, APM, or SIEM is required. The app runs
on database-backed cache/queue out of the box.

## B. Backend deploy sequence

From a clean checkout, with environment variables exported (never commit
real values — see `backend/.env.example` for the full contract):

```bash
cd backend
composer install --no-dev --optimize-autoloader
cp .env.example .env   # then fill in real production values
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan app:check
php artisan optimize
```

Then (re)start supervised processes:

```bash
# queue worker (required: notifications, SMS/WhatsApp, marketing delivery)
php artisan queue:work --tries=3 --backoff=5,15,60 --timeout=120 --sleep=3
# after every deploy, restart workers so they load the new code:
php artisan queue:restart
```

Scheduler (single cron entry; runs audit/marketing pruning):

```cron
* * * * * cd /path/to/backend && php artisan schedule:run >> /dev/null 2>&1
```

Realtime (only if `BROADCAST_CONNECTION=pusher`): start/supervise a
Pusher-protocol socket server (e.g. Laravel Reverb or soketi) reachable at
`PUSHER_HOST`/`PUSHER_PORT`, then reload it on deploy. With the default
`log`/`null` drivers there is nothing to run.

Frontend relationship: build once per deploy (`cd ../frontend && npm ci
&& npm run build`), then serve per section D. API and frontend may live on
different origins; keep `APP_URL`, `FRONTEND_URLS`, and `VITE_API_URL`
consistent (section H).

## C. Optimization commands

```bash
php artisan optimize          # caches config + routes + events + views
php artisan optimize:clear    # clears all of the above (use before debugging)
```

Verified compatible with this application (Laravel 13): no `env()` calls
exist outside `config/` and `bootstrap/`, so cached configuration is safe.
Never commit the generated files under `bootstrap/cache/` or
`storage/framework/`.

## D. Web server

- Document root MUST be `backend/public`. Never serve the repository root
  (that would expose `.env`, `vendor/`, `composer.json`, migrations, and tests).
- Example Nginx location blocks:
  - `try_files $uri $uri/ /index.php?$query_string;` for `/`
  - PHP-FPM upstream for `*.php`
- If the SPA is served from the same host, add a fallback so unknown
  non-`/api` paths serve the built `index.html` (client-side routing).
  If hosted separately (e.g. static host/CDN), no backend fallback is needed.
- Deny access to dotfiles (`.env`, `.git/`) at the server layer as
  defense-in-depth.

## E. Queue

- Driver default is `database` (`QUEUE_CONNECTION`); `failed_jobs` uses the
  `database-uuids` driver. Both tables migrate with the app.
- Supervise at least one `queue:work` process (systemd/supervisord/docker
  restart policy). The worker handles notification mail/broadcast fan-out,
  SMS/WhatsApp sends, and marketing provider deliveries.
- Always run `php artisan queue:restart` on deploy (long-lived workers
  otherwise keep running old code).
- Inspect failures with `php artisan queue:failed`; retry with
  `php artisan queue:retry all`; prune monthly via the scheduled
  `queue:prune-failed --hours=720`.
- For multiple app servers, point `CACHE_STORE` (rate-limit counters) and
  the queue at a shared store; local database-backed counters are
  single-node only.

## F. Storage

- Run `php artisan storage:link` so `public/storage` serves the `public`
  disk (CMS/banner uploads). `php artisan app:check` warns when the link
  is missing.
- Writable at runtime: `storage/` (logs, cache, uploads, sessions if used).
- CMS/banner uploads live under `storage/app/public/{cms,banners}/{id}/`
  with UUID filenames; orphans are deleted by the app on replace/delete.
- Backups must include `storage/app/public` alongside the database.
- To move to S3-compatible object storage later, set `FILESYSTEM_DISK`
  (and `AWS_*`) — no code changes required (Laravel filesystem abstraction).

## G. Payments

- `PAYMENT_DRIVER` selects the backend-authoritative provider routing,
  which the frontend cannot override: Bangladesh (`BD`) → `sslcommerz`,
  everything else → `stripe`. Demo cards only work with `demo`.
- Stripe production: `STRIPE_KEY`, `STRIPE_SECRET`,
  `STRIPE_WEBHOOK_SECRET`; register
  `https://<APP_URL>/api/v1/payments/stripe/webhook` in the Stripe
  dashboard with the same signing secret.
- SSLCOMMERZ production: `SSLCOMMERZ_STORE_ID`,
  `SSLCOMMERZ_STORE_PASSWORD`, `SSLCOMMERZ_SANDBOX=false`; the IPN and
  success/fail/cancel URLs must point at
  `https://<APP_URL>/api/v1/payments/sslcommerz/*`.
- Webhooks are signature/server-validated and idempotent (repeated
  delivery never double-pays, double-decrements stock, or duplicates
  notifications). No live credentials are committed anywhere.

## H. HTTPS

- Terminate TLS at the reverse proxy and serve everything over HTTPS.
- Set `APP_URL=https://<api-host>` and `FRONTEND_URLS=https://<storefront>`
  (comma-separated if more than one). `app:check` fails a production
  deploy still pointing at localhost.
- If terminating TLS at the proxy, set `TRUSTED_PROXIES` to the proxy
  IP/CIDR(s) so generated URLs (order links, redirects, webhooks) use the
  correct scheme/host. Leave empty only for direct-serve setups.
- Sanctum issues Bearer tokens (no session cookies); `SESSION_SECURE_COOKIE`
  and `SESSION_SAME_SITE` only matter if session flows are added later.

## I. Rollback

1. Note the previous release commit (`git log --oneline -5`).
2. Point the web root / container image back at the previous release and
   run `php artisan queue:restart`.
3. Database: roll back code first and assess. `php artisan migrate:rollback`
   exists but is NOT always safe — the Phase 13 CMS/banner tables and any
   data written by the new release must be reviewed before rolling schema
   back. Prefer restoring from the pre-deploy backup (section: backups in
   `PRODUCTION_CHECKLIST.md`) over blind rollback.
4. Re-run `php artisan app:check` and the smoke checks in
   `PRODUCTION_CHECKLIST.md`.
