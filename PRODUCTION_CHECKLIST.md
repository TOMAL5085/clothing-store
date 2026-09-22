# Production Checklist — Clothing Store (JAAJ)

Run through this list before and after every production deploy. Anything
marked EXTERNAL needs infrastructure outside this repository.

## Pre-flight (before deploy)

- [ ] `backend/.env` filled from `.env.example`; no real secrets committed
- [ ] `APP_ENV=production`, `APP_DEBUG=false`
- [ ] `APP_URL=https://…` (not localhost) and `APP_KEY` set
- [ ] `FRONTEND_URLS` lists the real storefront origin(s) (CORS)
- [ ] `TRUSTED_PROXIES` set when behind an HTTPS-terminating proxy
- [ ] Database reachable; PostgreSQL backups scheduled (EXTERNAL)
- [ ] `QUEUE_CONNECTION=database` (or shared store for multi-server)
- [ ] `CACHE_STORE` suitable for the topology (database is single-node)
- [ ] Mailer + sender configured (`MAIL_MAILER`, `MAIL_FROM_ADDRESS`)
- [ ] Payment mode decided: `demo` (no real money) vs live credentials
- [ ] Live Stripe: `STRIPE_KEY`/`STRIPE_SECRET`/`STRIPE_WEBHOOK_SECRET` set,
      webhook URL registered in Stripe dashboard
- [ ] Live SSLCOMMERZ: store ID/password set, `SSLCOMMERZ_SANDBOX=false`,
      IPN/callback URLs registered
- [ ] Broadcast: `log`/`null` (nothing to run) or `pusher` + supervised
      socket server (EXTERNAL process)
- [ ] SMS/WhatsApp/marketing drivers intentionally `mock`/`null` unless a
      real vendor was selected (none selected as of Phase 13)
- [ ] `VITE_API_URL` (frontend) points at the production API
- [ ] `php artisan app:check` passes (run it — it prints OK/FAIL lines)

## Deploy

- [ ] `composer install --no-dev --optimize-autoloader`
- [ ] `php artisan migrate --force` (review output; see rollback note)
- [ ] `php artisan storage:link` (verify `public/storage`)
- [ ] `php artisan optimize`
- [ ] `php artisan queue:restart` (workers pick up new code)
- [ ] Frontend: `npm ci && npm run build`, deploy output
- [ ] Reload supervised socket server if broadcasting is live

## Post-deploy smoke checks

Distinguish locally-testable vs production-only:

Locally testable (no real money/providers):
- [ ] `GET /up` → 200
- [ ] Homepage renders; products load
- [ ] `GET /api/v1/cms/content` and `GET /api/v1/banners` return published rows
- [ ] Register + login; account page loads
- [ ] Cart add/update; checkout quote
- [ ] Demo checkout (`PAYMENT_DRIVER=demo`) creates a paid order
- [ ] Order lookup with checkout token
- [ ] Notifications list + unread count; admin login + dashboard pages
- [ ] Admin CMS create/publish + banner image upload + `public/storage` URL loads

Staging-testable (test credentials/callbacks):
- [ ] Stripe test webhook marks an order paid exactly once
- [ ] SSLCOMMERZ sandbox IPN validates and marks paid exactly once

Production-only (do NOT fake):
- [ ] Live Stripe webhook delivery succeeds in the dashboard
- [ ] Live SSLCOMMERZ IPN validates against a real transaction
- [ ] Real payment success/failure/cancel redirects land correctly
- [ ] No `APP_DEBUG=true`, no stack traces in error responses

## Backup / recovery (EXTERNAL unless noted)

- [ ] Scheduled PostgreSQL backups with retention (EXTERNAL)
- [ ] Restore tested at least once (EXTERNAL)
- [ ] `storage/app/public` (CMS/banner uploads) included in backups
- [ ] Secrets/env stored in a vault or secured store (EXTERNAL)
- [ ] Recovery order documented: database → storage → environment/secrets
      → application code → queue/realtime services
- [ ] `audit:prune` (daily) and `marketing:prune` (weekly) scheduled via cron

## Monitoring (observe with existing tooling; EXTERNAL alerting)

- [ ] `GET /up` monitored for 200s (EXTERNAL uptime check)
- [ ] 5xx rate + `request_id` trail in application logs
- [ ] `failed_jobs` watched (`php artisan queue:failed`)
- [ ] Queue worker process supervised with restarts
- [ ] Disk space for `storage/` (logs, uploads, cache)
- [ ] `notification_deliveries` failures and `marketing_event_deliveries`
      failures reviewed periodically
- [ ] Payment webhook failures investigated (idempotent — safe to replay)
- [ ] `audit_logs` retained per `AUDIT_RETENTION_DAYS`

## Rollback

- [ ] Previous release commit noted before deploying
- [ ] Code rollback = repoint web root/image + `queue:restart`
- [ ] Database rollback reviewed case-by-case (prefer pre-deploy backup
      restore over blind `migrate:rollback`)
- [ ] Post-rollback: `app:check` + smoke checks re-run
