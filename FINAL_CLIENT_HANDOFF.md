# Client handoff checklist

Updated: 2026-10-29. This checklist reflects the completed Phase A-G
application features and the remaining client-owned production setup.

This project contains the React storefront and Laravel/PostgreSQL API. The application code and admin workflows are included; the client must still provide their own production credentials and business data.

## First setup

```powershell
cd backend
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve

cd ..\frontend
copy .env.example .env
npm install
npm run dev
```

Admin login for local development:

```text
admin@jaaj.test
password
```

## Admin areas

- `/admin/products` — products, inventory, variations
- `/admin/categories` and `/admin/brands` — catalog management
- `/admin/content` — CMS pages and banners
- `/admin/commerce` — wholesale and weight pricing
- `/admin/logistics` — courier import, auto-entry and reports
- `/admin/marketing` — Pixel, Analytics, SMS and fraud settings
- `/admin/order-workflow` — assignment, disbursement, export and recovery
- `/admin/reports/staff-orders` — date-filtered staff report
- `/admin/settings` — store settings and customer IP blocking
- `/admin/analytics` — sales, order, inventory and marketing dashboard

## Production configuration required from the client

Configure these in `backend/.env` before launch:

- PostgreSQL connection
- `APP_URL` and `FRONTEND_URLS`
- Stripe or SSLCommerz credentials
- SMTP/mail credentials
- Queue worker configuration
- Courier provider credentials
- `SMS_DRIVER=twilio` and Twilio credentials, if live SMS is required
- Meta Pixel and Google Analytics IDs through `/admin/marketing`

## Operations before launch

1. Replace seeded admin credentials.
2. Add real products, categories, brands, sizes, suppliers, cities and zones.
3. Upload approved banners and CMS copy.
4. Configure the production mail driver and run `php artisan queue:work`.
5. Configure a real courier driver instead of the mock courier.
6. Run `php artisan test` against the PostgreSQL test database.
7. Test checkout, payment callbacks, courier creation, invoice email, recovery links and consent tracking in staging.
8. Enable HTTPS, backups, monitoring and scheduled maintenance commands.

The full test suite must be run against a reachable PostgreSQL test database
before the client signs off. Local frontend build and focused code validation
have passed, but provider credentials, queue delivery, courier behavior, and
live payment callbacks require staging verification.
