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
