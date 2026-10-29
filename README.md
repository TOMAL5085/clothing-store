# Clothing Store

Existing React ecommerce frontend plus a Laravel/PostgreSQL REST API backend.

## Requirements

- PHP 8.3+
- Composer
- PostgreSQL
- PHP extension `pdo_pgsql`
- Node.js and npm

## Backend Setup

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

Update `.env` with your PostgreSQL credentials:

```env
DB_CONNECTION=pgsql
DB_DATABASE=clothing_store
DB_USERNAME=postgres
DB_PASSWORD=
FRONTEND_URLS=http://localhost:5173
```

Run migrations and seeders:

```bash
php artisan migrate --seed
php artisan serve
```

Seeded users:

- `admin@jaaj.test` / `password`
- `customer@jaaj.test` / `password`

Admin inventory management is available at `/admin/products` after signing in as the seeded admin user. Admin customer management is available at `/admin/customers`.

Account features are available at `/account` after login: profile editing, password changes, OTP email verification, order history, and saved addresses. In local development, OTP responses include a `debugOtp` value while `APP_DEBUG=true`; configure a real mail/SMS delivery provider before production use.

Shopping cart, wishlist, and promo code behavior are backed by Laravel APIs. Use promo code `JAAJ10` in the cart to test the seeded 10% discount; prices, stock validation, discounts, shipping, and totals are recalculated server-side.

## Frontend Setup

```bash
cd frontend
npm install
cp .env.example .env
npm run dev
```

Default frontend API URL:

```env
VITE_API_URL=http://localhost:8000/api/v1
```

## Tests

Create a PostgreSQL test database named `clothing_store_test`, then run:

```bash
cd backend
php artisan test
```

This local PHP install must have `pdo_pgsql` enabled. Without a PDO database driver, migrations/tests cannot run. Keep your local test database password in `backend/.env.testing` or your local environment, not in source-controlled files.

## Docs

- [Architecture](docs/architecture.md)
- [Database](docs/database.md)
- [API](docs/api.md)

## Production Deployment

See [DEPLOYMENT.md](DEPLOYMENT.md) for the full deploy sequence (backend,
workers, scheduler, storage, payments, HTTPS, rollback) and
[PRODUCTION_CHECKLIST.md](PRODUCTION_CHECKLIST.md) for pre-flight, smoke
checks, backup, and monitoring checklists. Validate any environment with
`php artisan app:check` from `backend/`.

## Current feature coverage

The application now includes the planned Phase A-G modules: catalog/content
management, suppliers and purchasing, accounts and expenses, wholesale
customers and weight-based pricing, courier/invoice operations, marketing and
analytics, and the order workflow. The admin pages are:

- `/admin/products`, `/admin/categories`, `/admin/brands`, `/admin/content`
- `/admin/commerce`, `/admin/logistics`, `/admin/marketing`
- `/admin/order-workflow`, `/admin/reports/staff-orders`, `/admin/settings`
- `/admin/analytics`, `/admin/orders`, and `/admin/customers`

Before client launch, configure real payment, mail, courier, and optional SMS
credentials. The default courier and SMS drivers are safe mock drivers for
development. Courier bulk import accepts CSV and XLSX files, invoice email is
queued, visitor IPs are stored as one-way hashes, and marketing scripts load
only after consent.

For the client-facing operating instructions, see
[CLIENT_HANDOFF.md](CLIENT_HANDOFF.md), [ADMIN_GUIDE.md](ADMIN_GUIDE.md), and
[FINAL_CLIENT_HANDOFF.md](FINAL_CLIENT_HANDOFF.md).
