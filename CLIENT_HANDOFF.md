# JAAJ Clothing Store — Client Handoff

This guide is for the store owner. It explains what the system does, how to
operate it day to day, and what you need to provide for production. No
programming knowledge is required.

## A. Project overview

JAAJ is an online clothing store with two parts:

- **Storefront** (what customers see): homepage, product catalog, search,
  product pages, cart, wishlist, checkout, order tracking, customer
  account, reviews, and contact/info pages.
- **Admin panel** (what staff see after signing in as admin): products,
  inventory, orders, shipments, customers, cancellations, returns,
  refunds, reviews, analytics, notifications, audit log, CMS content,
  and banners.

Data lives in a **PostgreSQL database** on the server. The storefront and
the admin panel both talk to the same backend API, so changes made in the
admin panel appear on the storefront immediately.

## B. How the store works

- **Products & inventory.** Products have sizes, colors, prices, photos,
  and stock counts. A product with zero stock shows as sold out and cannot
  be purchased. Prices, discounts, shipping, and totals are always
  calculated by the server — never by the customer's browser.
- **Customers.** Shoppers can browse as guests or register an account.
  Accounts store profile details, addresses, order history, wishlist, and
  notification preferences.
- **Orders.** Checkout collects the shipping address, validates stock,
  applies coupons, creates the order, and charges payment. Every order gets
  a number like `JAAJ-612943` plus a private lookup token for guests.
- **Payments.** The system selects the payment provider automatically:
  customers in **Bangladesh pay via SSLCOMMERZ**; customers **outside
  Bangladesh pay via Stripe**. This routing is enforced by the server and
  cannot be changed from the browser. A demo card (`4242 4242 4242 4242`)
  is available while real provider credentials are not configured.
- **Shipping & tracking.** Staff create shipments for paid orders; tracking
  events are recorded per shipment and visible to the customer.
- **Cancellations / returns / refunds.** Customers request from their
  account; staff approve or reject in the admin panel. Approved
  cancellations restore stock and cancel payment; approved returns create
  refunds once the package is marked received.

## C. Admin operations

Sign in with your admin account, then open **Account → admin shortcuts**
or go directly to any `/admin/...` page.

- **Log in:** use the admin email and password created during setup.
- **Products** (`/admin/products`): create/edit products, adjust stock.
- **Inventory:** set exact quantities per size/variant; low-stock warnings
  appear automatically.
- **Orders** (`/admin/orders`): view, filter, progress statuses, create
  shipments, sync courier status.
- **CMS announcements** (`/admin/content`): edit the top announcement bar
  text, publish/unpublish, schedule start/end dates.
- **Banners** (`/admin/content` → Banners tab): upload hero slide images
  (desktop + mobile), reorder slides, schedule campaigns.
- **Reports** (`/admin/analytics`): revenue, orders, customers, inventory,
  reviews, and marketing funnel.
- **Notifications** (`/notifications`): order and store alerts with unread
  counts, updated in real time.
- **Audit history** (`/admin/audit-log`): who changed what and when
  (products, orders, refunds, reviews, CMS, and more).

## D. CMS instructions

- **Announcement:** edit the `site-announcement` record. The first field
  is the plain lead-in text; the second is the highlighted part (shown in
  red). Keep it short — it shares one line on mobile.
- **Banner:** upload a wide desktop image (JPG/PNG/WebP, max 5 MB) and,
  ideally, a portrait mobile image. Slides play in `sort_order` sequence.
- **Scheduling:** leave start/end empty for always-on content, or set both
  for campaigns. Future items stay hidden until their start; expired items
  disappear automatically.
- **Image replacement/deletion:** uploading a new image replaces (and
  deletes) the old file automatically. Deleting a banner or content row
  removes its uploaded files too. Remote image URLs are never deleted.
- **Ordering:** lower `sort_order` numbers appear first; ties keep creation
  order.
- **Draft / published / archived:** only `published` items within schedule
  appear on the storefront. Drafts and archived items are invisible to
  customers.

## E. Payment setup

- **Stripe:** needs a Stripe account → API keys + webhook secret entered on
  the server (never in the website code). Register the webhook URL
  `https://<your-api>/api/v1/payments/stripe/webhook` in the Stripe
  dashboard and enable `checkout.session.completed`,
  `payment_intent.succeeded`, `payment_intent.payment_failed`, and
  `checkout.session.expired` events. Test mode first, live keys only when
  ready.
- **SSLCOMMERZ:** needs a merchant store ID + password on the server, plus
  the IPN/callback URLs `https://<your-api>/api/v1/payments/sslcommerz/*`
  registered in the merchant panel. Keep sandbox mode on until going live.
- Until real credentials are entered, the store runs in demo mode and no
  real money moves.

## F. Operational responsibilities

- **Backups:** database dumps on a schedule (daily recommended) plus copies
  of uploaded images (`storage/app/public`). Test a restore at least once.
- **Monitoring:** watch the health endpoint (`GET /up`), error rates,
  failed background jobs (`failed_jobs`), disk space, and payment webhook
  failures. Queued notifications/emails only send while a queue worker runs.
- **Queue workers:** at least one `php artisan queue:work` process must
  stay running (supervised), restarted after every deploy.
- **Storage:** keep `public/storage` linked (`php artisan storage:link`)
  so uploaded CMS/banner images resolve.
- **Payment callbacks:** provider dashboards must reach the API over HTTPS;
  callbacks are idempotent, so replaying them is safe.
- **HTTPS/DNS:** serve everything over HTTPS with a valid certificate;
  point DNS at the host; keep `APP_URL`/`FRONTEND_URLS` matching reality.
- **Secrets:** API keys, webhook secrets, and mail credentials live only in
  the server environment — never in code, chat, or email.

## G. What the client must provide

- [ ] Domain name(s) for storefront (and API if separate)
- [ ] Hosting: PHP 8.3+ app server + PostgreSQL database
- [ ] Production mail provider (SMTP credentials) for order emails
- [ ] Stripe account + live keys + webhook secret (for non-BD customers)
- [ ] SSLCOMMERZ merchant account + live store credentials (for BD customers)
- [ ] TLS certificate (usually via host/CDN)
- [ ] Scheduled backups + off-site retention
- [ ] Uptime/error monitoring + alerting contact
- [ ] (Later, if wanted) SMS/WhatsApp vendor, realtime socket hosting,
      analytics/marketing platform credentials — none selected yet

## H. Limitations

- Real payments, live emails, SMS/WhatsApp, and third-party marketing
  delivery all require the credentials above; until then the system runs
  on demo/mock drivers that perform no external I/O.
- Realtime notifications need a socket server process when enabled beyond
  local development; otherwise the app degrades gracefully to HTTP.
- Delivery-status webhooks from SMS/marketing providers don't exist yet.
- Multi-server deployments need shared cache/queue storage (single-node
  database drivers are the default).
- See `CONTEXT.md` and `DEPLOYMENT.md` for the full technical record.

## I. Support / troubleshooting

- **Site unavailable:** check the host/app server, then `GET /up`, then
  application logs. Verify environment variables and database reachability
  with `php artisan app:check`.
- **Queue not processing (no emails/notifications):** ensure a queue
  worker is running; inspect `failed_jobs` via `php artisan queue:failed`.
- **Uploads not appearing:** verify `public/storage` link exists and the
  `storage/` directory is writable.
- **Payment callback problems:** confirm the provider dashboard URL,
  signing secret match, and HTTPS reachability; callbacks are idempotent.
- **Frontend cannot reach API:** check `VITE_API_URL`, CORS origins
  (`FRONTEND_URLS`), and HTTPS/certificate validity.
- **Stale content:** CMS/banner pages cache for ~60 seconds; wait a minute
  or confirm the record is published and within schedule.
- **Realtime not connecting:** the app keeps working over HTTP; check the
  socket server process and `VITE_REVERB_*` values.
