# Clothing Store Project Handoff Context

Last verified by opencode on 2026-09-19.

This file is the handoff document for continuing the project in Cursor. It is based on the current repository state, not on the original prompt alone.

## Executive Summary

This project is an ecommerce website named `clothing-store`.

- Frontend: existing React/Vite/Tailwind application in `frontend/`.
- Backend: Laravel REST API in `backend/`.
- Database: PostgreSQL.
- API namespace: `/api/v1`.
- Authentication: Laravel Sanctum token auth.
- Current backend test result: ✅ `39 passed`, `210 assertions`.
- Phase 4A (checkout, Stripe, SSLCOMMERZ, order management) is implemented and fully tested.
- Git status: ⚠️ no Git repository was detected at the project root during this handoff.

The frontend was already implemented before backend work began. Do not rebuild, redesign, or replace the frontend. Treat the current frontend UI as approved.

## Current Project Layout

```text
clothing-store/
├── backend/
├── docs/
├── frontend/
├── README.md
└── CONTEXT.md
```

Important documentation already exists:

- `README.md`
- `docs/architecture.md`
- `docs/database.md`
- `docs/api.md`

## Tech Stack

### Backend

Located in `backend/`.

- PHP: `^8.3`
- Laravel: `^13.17`
- Laravel Sanctum: `^4.3`
- PHPUnit: `^12.5.12`
- Database: PostgreSQL
- ORM: Eloquent
- Validation: Laravel Form Requests
- Responses: Laravel API Resources
- Authorization: Policies and route middleware

Key backend directories:

- `backend/app/Http/Controllers/Api/V1`
- `backend/app/Http/Requests`
- `backend/app/Http/Resources`
- `backend/app/Models`
- `backend/app/Policies`
- `backend/app/Services`
- `backend/database/migrations`
- `backend/database/factories`
- `backend/database/seeders`
- `backend/tests/Feature`

### Frontend

Located in `frontend/`.

- React: `19.2.6`
- React DOM: `19.2.6`
- React Router DOM: `^7.18.4`
- Zustand: `^5.0.15`
- Vite: `^7.3.6`
- TypeScript: `5.9.3`
- Tailwind CSS: `4.1.17`
- `@tailwindcss/vite`: `4.1.17`
- `vite-plugin-singlefile`: `2.3.0`
- Icons: `lucide-react`
- Animation: `framer-motion`

Frontend aliases:

- `@` points to `frontend/src`.

Frontend API base URL:

- `VITE_API_URL`
- Default in code: `http://localhost:8000/api/v1`

## Environment Files

Environment files exist locally:

- `backend/.env`
- `backend/.env.testing`
- `frontend/.env`

Do not commit real `.env` files or real secrets.

Tracked examples/placeholders:

- `backend/.env.example`
- `frontend/.env.example`

### Backend Required Local Values

For local development, `backend/.env` should contain PostgreSQL settings for the normal database:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=clothing_store
DB_USERNAME=postgres
DB_PASSWORD=<local password>
```

For tests, keep the password in `backend/.env.testing` or in local environment variables:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=clothing_store_test
DB_USERNAME=postgres
DB_PASSWORD=<same local PostgreSQL password>
```

The actual password is intentionally not written in this file.

### Frontend Required Local Values

`frontend/.env` should point at the backend API:

```env
VITE_API_URL=http://localhost:8000/api/v1
```

## Test Database Configuration History

Earlier, `php artisan test` failed because Laravel was using PostgreSQL database `clothing_store_test` with username `postgres` but no password.

The test database connection target is:

- connection: `pgsql`
- host: `127.0.0.1`
- port: `5432`
- database: `clothing_store_test`
- username: `postgres`
- password: stored locally, not in source-controlled files

`backend/phpunit.xml` defines the testing database name, host, port, username, and connection, but it does not safely store the real password. Keep the real password in ignored local configuration.

## Verified Test Result

Command run from `backend/`:

```bash
php artisan test
```

Latest result:

```text
{"tool":"phpunit","result":"passed","tests":59,"passed":59,"assertions":276,"duration_ms":20030}
```

Status: ✅ backend test suite passes.

Suite covers Phases 1-3 (`ProductApiTest`, `CartApiTest`, `OrderApiTest`, `AuthApiTest`, `Phase1ProductInventoryTest`, `Phase2UsersAccountsTest`, `AuthorizationApiTest`) plus Phase 4A (`Phase4CheckoutPaymentTest`, `Phase4AdminOrdersTest`), Phase 4B-1 (`Phase4BShippingTest`), and Phase 4B-2 (`Phase4BTrackingTest`).

## Git State

Command run from project root:

```bash
git status --short --branch
```

Result:

```text
## main...origin/main
 M CONTEXT.md
 M backend/app/Http/Controllers/Api/V1/Admin/OrderManagementController.php
 M backend/app/Http/Controllers/Api/V1/Admin/ShipmentController.php
 M backend/app/Http/Controllers/Api/V1/OrderController.php
 M backend/app/Http/Controllers/Api/V1/OrderTrackingController.php
 M backend/app/Http/Resources/OrderResource.php
 M backend/app/Models/Order.php
 M backend/app/Models/Shipment.php
 M backend/app/Services/ShippingService.php
 M backend/database/migrations/2026_09_19_000001_create_shipment_events_table.php
 M backend/routes/api.php
 M frontend/src/pages/AccountPage.tsx
 M frontend/src/pages/admin/AdminOrdersPage.tsx
 M frontend/src/store/orderStore.ts
A backend/app/Http/Controllers/Api/V1/OrderTrackingController.php
A backend/app/Http/Resources/ShipmentEventResource.php
A backend/app/Models/ShipmentEvent.php
A backend/database/migrations/2026_09_19_000001_create_shipment_events_table.php
A backend/tests/Feature/Phase4BTrackingTest.php
```

Status: ✅ Git repository detected at `C:\Users\User\Desktop\clothing-store`. Current branch: `main`. Last commit: `389dd61` "Complete Phase 4B-1 shipping management".

## Completed Phases

### Phase 1 - Product & Inventory

Status: ✅ complete.

Implemented:

- Product listing
- Product details
- Featured products
- Related products
- Product search
- Filtering by category, size, color, price, and stock
- Sorting
- Pagination
- Categories
- Product images
- Sizes
- Colors
- Product variants
- Inventory quantities
- Low stock thresholds
- Inventory status
- Admin product listing
- Admin product create/update/delete
- Admin inventory adjustment
- Non-admin authorization protection

### Phase 2 - Users & Accounts

Status: ✅ complete.

Implemented:

- Registration
- Login
- Logout
- Current authenticated user
- Profile update
- Password update
- OTP email verification model and service
- Address CRUD
- Admin customer list/search/view
- Admin customer status update
- User status handling
- Role-based admin authorization

### Phase 3 - Shopping

Status: ✅ complete.

Implemented:

- Guest cart token support
- Authenticated cart support
- Add cart item
- Update cart quantity
- Remove cart item
- Clear cart
- Variant-aware cart lines
- Stock validation
- Server-side cart totals
- Coupons/promo codes
- Coupon usage validation
- Wishlist for guest and authenticated users
- Checkout order creation
- Demo payment service
- Order persistence
- Order item historical pricing
- Inventory decrement on paid checkout
- Order list and details
- Order ownership authorization

### Phase 4A - Checkout, Payment, and Order Management

Status: ✅ complete.

Implemented:

- Two real gateway integrations behind `PaymentGateway`:
  - **Stripe** (`StripeGateway`) for customers outside Bangladesh
  - **SSLCOMMERZ** (`SslCommerzGateway`) for Bangladesh customers
- Country/provider selection in `CountryResolver` (server-side, backend authoritative)
- Stripe Checkout Session creation with server-side webhook verification and idempotent `markPaid`
- SSLCOMMERZ session init, IPN + success/fail/cancel callbacks, server-side validation via the SSLCOMMERZ validation API
- Demo driver (default in dev/tests) still works with the `4242 4242 4242 4242` / decline `0000` cards
- `CheckoutService` extended with `quote()` (provider + totals preview) and `placeOrder()` (authoritative totals, address ownership, stock revalidation, coupon revalidation)
- `OrderFulfillmentService`: single-time inventory hold/decrement, coupon consumption only after verified payment, cart clearing after success, failure release of any held inventory
- Orders now carry order number, items, variants, qty, unit prices, subtotal, discount, shipping, total, payment provider, payment status, order status, shipping/billing snapshots, country, currency, and checkout token
- Public order confirmation lookup endpoint protected by checkout token
- Admin order management: list, filters (q/status/payment_status/pagination), detail, order status updates
- Order ownership + admin authorization enforced by `OrderPolicy`
- Frontend checkout handles demo cards, Stripe redirect, and SSLCOMMERZ redirect; success page resolves orders via checkout token; new admin orders page

Important lifecycle facts:

- Demo orders and gateway orders are created as `pending`; paid transition (inventory decrement, coupon consumption, cart clear) happens only through the verified fulfillment path (`markPaid`).
- `markPaid` and `markFailed` are idempotent (payment/order row locks + paid guards), preventing duplicate inventory decrements, double coupon consumption, or double order creation.
- Payment callbacks/webhooks never trust a browser redirect alone; Stripe uses signature-verified webhooks, SSLCOMMERZ uses server-side val_id validation plus amount matching.

### Phase 4B-1 - Shipping & Delivery Management

Status: ✅ complete.

Implemented:

- Shipment entity with dedicated `shipments` table (one-to-one with orders).
- Shipment model with statuses: `pending`, `processing`, `ready_to_ship`, `shipped`, `in_transit`, `out_for_delivery`, `delivered`, `failed_delivery`.
- Validated status transitions enforced by backend (e.g., `pending` → `processing` → `ready_to_ship` → `shipped` → `in_transit` → `out_for_delivery` → `delivered`).
- Shipping fields: carrier, tracking number, tracking reference, shipping fee, estimated delivery, shipped/delivered timestamps.
- `ShippingService` for business logic: create shipment, update shipment, validate transitions.
- Admin shipment management: create, view, update shipment via `/admin/orders/{order}/shipment` endpoints.
- Admin UI (`/admin/orders`) extended with shipping section: create shipment form, update shipment form, display shipping info.
- Customer UI (`/account` → Orders) extended with shipping display: status, carrier, tracking number, estimated delivery, shipped/delivered dates.
- Order resource includes shipment relationship when loaded.
- Shipping fee defaults to order's shipping amount; can be overridden.
- Order status syncs with shipment status for `shipped` and `delivered`.
- Authorization: customers can only view their own order's shipment; admins have full access.
- New test file `Phase4BShippingTest` covering: shipment creation, validation, transitions, authorization, resource inclusion.

### Phase 4B-2 - Order Tracking

Status: ✅ complete.

Implemented:

- Shipment events entity with dedicated `shipment_events` table (one-to-many with shipments).
- ShipmentEvent model with fields: status, location, description, metadata, occurred_at.
- Automatic event creation on shipment creation and status changes via `ShippingService`.
- Validated status transitions continue to be enforced with automatic event logging.
- Shipment events resource for API responses.
- Customer tracking API endpoint: `GET /api/v1/orders/{order}/tracking` (protected by OrderPolicy).
- Customer UI (`/account` → Orders) extended with tracking timeline: visual timeline with status dots, descriptions, timestamps.
- Admin UI (`/admin/orders`) extended with tracking events display in shipping section.
- Order resource includes shipment events when loaded (`shipment.events`).
- Authorization: customers can only view their own order's tracking; admins have full access.
- New test file `Phase4BTrackingTest` covering: event creation on creation/status change, chronological order, customer/admin authorization, tracking endpoint, duplicate event prevention, event timestamps.

### Phase 4B-3 - Courier API Integration (proposed)

Phase 4B-2 is complete. Recommended next work:

1. Courier API integration (Phase 4B-3) - build provider abstraction/interface.
2. Return / Refund / Cancellation Management (Phase 4B-4).
3. Real courier provider configuration and webhook handling.
4. Email/SMS notifications for shipping status changes.

## Frontend Architecture

The frontend is a React SPA with route-level lazy loading.

Main entry files:

- `frontend/src/main.tsx`
- `frontend/src/App.tsx`
- `frontend/src/index.css`
- `frontend/vite.config.ts`

The app uses:

- React Router for routes
- Zustand stores for state
- Local storage persistence for auth/cart/wishlist/theme/currency where appropriate
- `frontend/src/lib/api.ts` as the centralized API client

### Frontend API Client

File:

- `frontend/src/lib/api.ts`

Behavior:

- Reads `VITE_API_URL`
- Defaults to `http://localhost:8000/api/v1`
- Adds `Accept: application/json`
- Adds `Content-Type: application/json` unless sending `FormData`
- Adds bearer token from `localStorage["jaaj-api-token"]`
- Throws `ApiError` with status and validation errors

Do not scatter new `fetch` calls directly through components. Use or extend this API layer/stores.

### Frontend Routes

Routes currently defined in `frontend/src/App.tsx`:

| Route | Page |
|---|---|
| `/` | `HomePage` |
| `/shop` | `ShopPage` |
| `/product/:slug` | `ProductPage` |
| `/cart` | `CartPage` |
| `/wishlist` | `WishlistPage` |
| `/checkout` | `CheckoutPage` |
| `/order/success/:orderId` | `OrderSuccessPage` |
| `/order/failed` | `OrderFailurePage` |
| `/account` | `AccountPage` |
| `/contact` | `ContactPage` |
| `/faq` | `FaqPage` |
| `/shipping` | `ShippingPage` |
| `/returns` | `ReturnsPage` |
| `/privacy` | `PrivacyPage` |
| `/terms` | `TermsPage` |
| `/admin/products` | `AdminProductsPage` |
| `/admin/customers` | `AdminCustomersPage` |
| `/admin/orders` | `AdminOrdersPage` |
| `/our-story` | `InfoRoutePage` |
| `/careers` | `InfoRoutePage` |
| `/stores` | `InfoRoutePage` |
| `/press` | `InfoRoutePage` |
| `/sustainability` | `InfoRoutePage` |
| `/refund-policy` | `InfoRoutePage` |
| `/cookies` | `InfoRoutePage` |
| `/track-order` | `InfoRoutePage` |
| `/size-guide` | `InfoRoutePage` |
| `/login` | `LoginPage` inside `AuthLayout` |
| `/register` | `RegisterPage` inside `AuthLayout` |
| `/forgot-password` | `ForgotPasswordPage` inside `AuthLayout` |
| `*` | `NotFoundPage` |

### Frontend Stores

Important stores:

- `frontend/src/store/authStore.ts`
- `frontend/src/store/cartStore.ts`
- `frontend/src/store/catalogStore.ts`
- `frontend/src/store/uiStore.ts`
- `frontend/src/store/orderStore.ts`
- `frontend/src/store/themeStore.ts`
- `frontend/src/store/currencyStore.ts`
- `frontend/src/store/wishlistStore.ts`

Known local storage keys:

- `jaaj-api-token`
- `jaaj-auth`
- `jaaj-cart`
- `jaaj-cart-token`
- `jaaj-ui`
- `jaaj-wishlist-token`

### Frontend Mock/Fallback Data

Product fallback data still exists in:

- `frontend/src/data/products.ts`

`catalogStore` attempts to load `/products?per_page=100`. If the API is unavailable, it logs a warning and falls back to local products.

This fallback is useful during development, but backend-connected pages should continue to use real API data when the backend is available.

### Important Frontend Caution

`frontend/src/App.tsx` contains an older commented-out version of the app before the active implementation. Do not restore the commented block. The active implementation includes backend bootstrapping and admin/info routes.

`frontend/src/components/ErrorBoundary.tsx` displays `Something went wrong`. If that appears, inspect the browser console and network tab first. It usually means a runtime exception, not necessarily a Laravel failure.

## Backend API Routes

All routes are under `/api/v1`.

### Public/Auth Routes

| Method | Endpoint | Controller |
|---|---|---|
| POST | `/auth/register` | `AuthController@register` |
| POST | `/auth/login` | `AuthController@login` |
| POST | `/auth/forgot-password` | `PasswordResetController@store` |

### Product/Catalog Routes

| Method | Endpoint | Controller |
|---|---|---|
| GET | `/categories` | `CategoryController@index` |
| GET | `/products` | `ProductController@index` |
| GET | `/products/featured` | `ProductController@featured` |
| GET | `/products/{product}` | `ProductController@show` |
| GET | `/products/{product}/related` | `ProductController@related` |

### Cart Routes

| Method | Endpoint | Controller |
|---|---|---|
| GET | `/cart` | `CartController@show` |
| POST | `/cart/items` | `CartController@store` |
| PATCH | `/cart/items/{item}` | `CartController@update` |
| DELETE | `/cart/items/{item}` | `CartController@destroy` |
| DELETE | `/cart` | `CartController@clear` |
| POST | `/cart/promo` | `CartController@promo` |

### Wishlist Routes

| Method | Endpoint | Controller |
|---|---|---|
| GET | `/wishlist` | `WishlistController@show` |
| POST | `/wishlist/items` | `WishlistController@store` |
| DELETE | `/wishlist/items/{productId}` | `WishlistController@destroy` |

### Checkout Routes

| Method | Endpoint | Controller |
|---|---|---|
| POST | `/checkout/quote` | `CheckoutController@quote` |
| POST | `/checkout/orders` | `CheckoutController@store` |
| GET | `/checkout/orders/{order}` | `CheckoutController@show` (public lookup, requires matching `checkout_token` query; also serves as the order confirmation endpoint) |

### Payment Callback Routes

Public, no Sanctum auth (server-to-server). LAN-blocked/validated as needed by each gateway.

| Method | Endpoint | Controller |
|---|---|---|
| POST | `/payments/stripe/webhook` | `StripeWebhookController@handle` (Stripe signature verified) |
| GET/POST | `/payments/sslcommerz/ipn` | `SslCommerzController@ipn` (server-side val_id + amount validation) |
| GET/POST | `/payments/sslcommerz/success` | `SslCommerzController@success` |
| GET/POST | `/payments/sslcommerz/fail` | `SslCommerzController@fail` |
| GET/POST | `/payments/sslcommerz/cancel` | `SslCommerzController@cancel` |

### Authenticated User Routes

Require `auth:sanctum`.

| Method | Endpoint | Controller |
|---|---|---|
| GET | `/auth/me` | `AuthController@me` |
| POST | `/auth/logout` | `AuthController@logout` |
| POST | `/auth/otp` | `OtpController@store` |
| POST | `/auth/otp/verify` | `OtpController@verify` |
| PUT | `/profile` | `ProfileController@update` |
| PUT | `/profile/password` | `ProfileController@password` |
| GET | `/addresses` | `AddressController@index` |
| POST | `/addresses` | `AddressController@store` |
| GET | `/addresses/{address}` | `AddressController@show` |
| PUT/PATCH | `/addresses/{address}` | `AddressController@update` |
| DELETE | `/addresses/{address}` | `AddressController@destroy` |
| GET | `/orders` | `OrderController@index` |
| GET | `/orders/{order}` | `OrderController@show` |
| GET | `/orders/{order}/tracking` | `OrderTrackingController@show` |

### Admin Routes

Require Sanctum auth and admin authorization through product creation policy.

| Method | Endpoint | Controller |
|---|---|---|
| GET | `/admin/customers` | `CustomerManagementController@index` |
| GET | `/admin/customers/{customer}` | `CustomerManagementController@show` |
| PATCH | `/admin/customers/{customer}/status` | `CustomerManagementController@updateStatus` |
| GET | `/admin/products` | `ProductManagementController@index` |
| POST | `/admin/products` | `ProductManagementController@store` |
| PUT | `/admin/products/{product}` | `ProductManagementController@update` |
| DELETE | `/admin/products/{product}` | `ProductManagementController@destroy` |
| POST | `/admin/products/{product}/inventory` | `ProductManagementController@adjustInventory` |
| GET | `/admin/orders` | `Admin\OrderManagementController@index` |
| GET | `/admin/orders/{order}` | `Admin\OrderManagementController@show` |
| PATCH | `/admin/orders/{order}/status` | `Admin\OrderManagementController@updateStatus` |
| POST | `/admin/orders/{order}/shipment` | `Admin\ShipmentController@store` |
| GET | `/admin/orders/{order}/shipment` | `Admin\ShipmentController@show` |
| PUT | `/admin/orders/{order}/shipment` | `Admin\ShipmentController@update` |

## Database Schema

Migrations currently include:

- `users`
- `cache`
- `jobs`
- `categories`
- `products`
- `product_images`
- `sizes`
- `colors`
- `product_variants`
- `carts`
- `cart_items`
- `wishlists`
- `wishlist_items`
- `addresses`
- `orders`
- `order_items`
- `payments`
- `coupons`
- `personal_access_tokens`
- inventory threshold additions
- phase 2 account fields
- phase 3 coupon rules
- cart variant uniqueness update
- phase 4 checkout & payment fields (checkout token, country, provider, billing address, payment confirmation fields)
- shipments (Phase 4B-1)
- shipment events (Phase 4B-2)

Current migration files:

```text
0001_01_01_000000_create_users_table.php
0001_01_01_000001_create_cache_table.php
0001_01_01_000002_create_jobs_table.php
2026_09_16_154612_create_categories_table.php
2026_09_16_154613_create_products_table.php
2026_09_16_154614_create_product_images_table.php
2026_09_16_154615_create_sizes_table.php
2026_09_16_154616_create_colors_table.php
2026_09_16_154617_create_product_variants.php
2026_09_16_154618_create_carts_table.php
2026_09_16_154619_create_cart_items_table.php
2026_09_16_154620_create_wishlists_table.php
2026_09_16_154621_create_wishlist_items_table.php
2026_09_16_154622_create_addresses_table.php
2026_09_16_154623_create_orders_table.php
2026_09_16_154624_create_order_items_table.php
2026_09_16_154625_create_payments_table.php
2026_09_16_154626_create_coupons_table.php
2026_09_16_154727_create_personal_access_tokens_table.php
2026_09_18_080853_add_inventory_thresholds_to_products_and_variants.php
2026_09_18_120000_add_phase2_account_fields.php
2026_09_18_130000_add_phase3_coupon_rules.php
2026_09_18_130100_update_cart_variant_uniqueness.php
2026_09_18_210000_add_phase4_checkout_payment_fields.php
2026_09_19_000000_create_shipments_table.php
2026_09_19_000001_create_shipment_events_table.php
```

## Backend Domain Notes

### Products and Inventory

Product responses are shaped by `ProductResource`.

Supported concepts:

- external product IDs expected by frontend
- slug
- name
- description
- short description
- price
- sale price
- SKU
- category
- brand
- images
- image ordering
- variants
- size
- color
- stock quantity
- low stock threshold
- inventory status
- active/inactive status
- featured products

Inventory is server-authoritative. Do not trust browser stock values.

### Cart

Cart logic lives mainly in `CartService`.

Important behavior:

- guest carts use `cart_token`
- authenticated carts use user identity
- cart lines are variant-aware
- server calculates subtotal, discount, shipping, tax, and total
- cart item IDs returned to frontend include product/variant identity
- stock is validated when adding/updating items

### Wishlist

Wishlist supports guest and authenticated users.

Important behavior:

- guest wishlists use `wishlist_token`
- duplicate wishlist entries are prevented
- frontend stores product external IDs

### Checkout and Orders

Checkout logic lives mainly in `CheckoutService`.

Important behavior:

- locks/revalidates cart inventory
- creates address snapshot
- uses backend-calculated totals
- determines the payment provider from the (backend-validated) shipping country via `CountryResolver`
- calls the active `PaymentGateway` driver (`demo` by default, `stripe`, or `sslcommerz`)
- creates order and order items with purchase-time prices
- new orders are always created `pending` (order status + payment status); the paid transition happens only via the verified fulfillment path (`markPaid`)
- decrements inventory only after successful payment
- clears cart after successful order

Orders are protected by `OrderPolicy` (owner or admin). Customers must not be able to access another customer's order.

### Payment

Payment integrations live behind the `PaymentGateway` interface in `app/Services/Payments`.

Active driver is set with `PAYMENT_DRIVER` (default `demo`):

- `demo`: local card simulation. Succeeds for `4242 4242 4242 4242`, declines any card ending in `0000`. Stores provider result and last four digits only. Never stores raw card details.
- `stripe` (`StripeGateway`): Stripe Checkout Session for customers outside Bangladesh. Initiation returns a redirect URL; paid state is only trusted from signature-verified webhooks (`/api/v1/payments/stripe/webhook`).
- `sslcommerz` (`SslCommerzGateway`): SSLCOMMERZ for Bangladesh customers. Initiation calls `init_session.php`; results are validated server-side via the SSLCOMMERZ validation API on IPN (`/api/v1/payments/sslcommerz/ipn`) and success/fail/cancel callbacks.

Provider choice is backend-authoritative, made from the validated shipping country in `CountryResolver`:

- ISO-3166 normalized country `BD` (Bangladesh) → `sslcommerz`
- everything else → `stripe`

The frontend never selects the provider; it renders whichever provider the backend returns and follows the redirect URL.

Order/payment lifecycle (`OrderFulfillmentService`):

- orders are created `pending`
- `markPaid` (idempotent, row-locked): decrements inventory, consumes one coupon use, clears the cart, sets payment status `paid`
- `markFailed` (idempotent): releases any held inventory, sets payment status `failed`
- SSLCOMMERZ callbacks additionally verify `val_id` + amount server-side; Stripe webhooks verify signatures

Environment variables (see `backend/.env.example`):

- `PAYMENT_DRIVER=demo|stripe|sslcommerz`
- `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`, `STRIPE_CURRENCY`
- `SSLCOMMERZ_STORE_ID`, `SSLCOMMERZ_STORE_PASSWORD`, `SSLCOMMERZ_SANDBOX`, `SSLCOMMERZ_CURRENCY`
- `FRONTEND_URLS` (allowed CORS origins, first entry also used as fallback redirect target), `PAYMENT_FRONTEND_URL` (override redirect target)

The frontend displays demo instructions:

- success card: `4242 4242 4242 4242`
- decline simulation: any card ending in `0000`

### OTP

OTP behavior lives in `OtpService`.

Important behavior:

- six-digit code
- hashed at rest
- expires after configured duration
- resend throttle
- max invalid attempts
- marks email as verified after success
- `debugOtp` is exposed only in testing or debug mode

Before production, configure real OTP delivery through mail/SMS or another approved channel.

## Local Development Commands

### Backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Backend local URL:

```text
http://localhost:8000
```

API base URL:

```text
http://localhost:8000/api/v1
```

### Frontend

```bash
cd frontend
npm install
cp .env.example .env
npm run dev
```

Frontend local URL is usually:

```text
http://localhost:5173
```

### Tests

```bash
cd backend
php artisan test
```

Make sure the `clothing_store_test` database exists and the local test password is configured outside source control.

## Seed Data

Seeders populate usable development data.

Known local demo users from README:

| Role | Email | Password |
|---|---|---|
| Admin | `admin@jaaj.test` | `password` |
| Customer | `customer@jaaj.test` | `password` |

These are development-only credentials from project documentation, not production secrets.

Seeded data should support:

- categories
- products
- images
- variants
- sizes
- colors
- inventory
- users
- coupons

## API Response Philosophy

Use predictable JSON responses.

Current pattern:

- item resources: `{ "data": ... }`
- collections: `{ "data": [...], "meta": ... }` where applicable
- validation errors: Laravel validation JSON with `message` and `errors`
- auth errors: correct HTTP status codes
- authorization errors: correct HTTP status codes

Do not expose stack traces, SQL errors, real credentials, or internal secrets.

## Testing Philosophy

Feature tests currently cover:

- registration
- login failures
- authorization
- product listing/details/filtering
- admin product/inventory behavior
- non-admin authorization failures
- cart stock validation
- authoritative totals
- cart variant lines
- coupon validation/removal
- coupon per-customer usage
- order checkout creation
- inventory decrement
- demo card decline
- OTP flows
- profile update
- password update
- address CRUD
- customer management
- checkout quote (provider + totals preview)
- country-based provider routing (BD → sslcommerz, else → stripe)
- demo checkout full fulfillment (paid transition, coupon consumption, cart clear)
- checkout token protected order confirmation lookup
- Stripe webhook signature handling
- SSLCOMMERZ IPN validation
- admin order listing/filters/status updates
- customer denial of admin/gateway-adjacent routes

When adding backend behavior, add or update feature tests first/alongside the implementation.

## Current Feature Status Table

| # | Feature | Status | Notes |
|---:|---|---|---|
| 1 | Existing frontend preserved | ✅ | Do not redesign UI |
| 2 | React/Vite app | ✅ | `frontend/` |
| 3 | Tailwind setup | ✅ | Tailwind 4 via Vite plugin |
| 4 | Central API client | ✅ | `frontend/src/lib/api.ts` |
| 5 | Env API URL | ✅ | `VITE_API_URL` |
| 6 | Laravel backend | ✅ | `backend/` |
| 7 | PostgreSQL config | ✅ | local `.env` required |
| 8 | Test DB config | ✅ | password kept local |
| 9 | Sanctum auth | ✅ | bearer token flow |
| 10 | Register | ✅ | `/auth/register` |
| 11 | Login | ✅ | `/auth/login` |
| 12 | Logout | ✅ | `/auth/logout` |
| 13 | Current user | ✅ | `/auth/me` |
| 14 | Profile update | ✅ | `/profile` |
| 15 | Password update | ✅ | `/profile/password` |
| 16 | OTP issue | ✅ | `/auth/otp` |
| 17 | OTP verify | ✅ | `/auth/otp/verify` |
| 18 | Password reset endpoint | 🟡 | Endpoint exists; production delivery should be reviewed |
| 19 | Address list | ✅ | authenticated |
| 20 | Address create/update/delete | ✅ | owner-scoped |
| 21 | Category listing | ✅ | `/categories` |
| 22 | Product listing | ✅ | `/products` |
| 23 | Product detail | ✅ | `/products/{product}` |
| 24 | Featured products | ✅ | `/products/featured` |
| 25 | Related products | ✅ | `/products/{product}/related` |
| 26 | Search | ✅ | product query |
| 27 | Filtering | ✅ | category/price/size/color/stock |
| 28 | Sorting | ✅ | product query |
| 29 | Pagination | ✅ | product collection meta |
| 30 | Product images | ✅ | ordered images |
| 31 | Sizes/colors | ✅ | normalized tables |
| 32 | Product variants | ✅ | size/color/SKU/stock |
| 33 | Inventory thresholds | ✅ | product and variant |
| 34 | Admin products | ✅ | protected routes |
| 35 | Admin inventory adjustment | ✅ | protected and tested |
| 36 | Admin customers | ✅ | protected routes |
| 37 | Guest cart | ✅ | `cart_token` |
| 38 | Auth cart | ✅ | user-bound |
| 39 | Cart totals | ✅ | server-authoritative |
| 40 | Coupon/promo | ✅ | validation and usage logic |
| 41 | Wishlist | ✅ | guest/user token support |
| 42 | Checkout | ✅ | creates persisted order |
| 43 | Demo payment | ✅ | service isolated, default dev/test driver |
| 44 | Order history/detail | ✅ | authenticated and authorized |
| 45 | Admin order management | ❌ | planned for next phase |

Wait - the table above is stale; it is replaced by the corrected state below.

| # | Feature | Status | Notes |
|---:|---|---|---|
| 42 | Checkout quote | ✅ | provider + totals preview |
| 43 | Demo payment | ✅ | `4242` success `/0000` decline |
| 44 | Order history/detail | ✅ | authenticated and authorized |
| 45 | Stripe gateway | ✅ | Checkout Session + signature-verified webhook |
| 46 | SSLCOMMERZ gateway | ✅ | IPN/success/fail/cancel + val_id validation |
| 47 | Country provider routing | ✅ | BD → sslcommerz, else stripe |
| 48 | Order confirmation lookup | ✅ | protected by checkout token |
| 49 | Admin order management | ✅ | list/filters/detail/status updates |
| 50 | Shipping management | ✅ | shipments, tracking, admin/customer UI |
| 51 | Order tracking | ✅ | shipment events, timeline, customer/admin UI |

Legend:

- ✅ implemented and verified by tests or code inspection
- 🟡 partial or needs production hardening
- ❌ not implemented yet

## Known Issues and Technical Debt

1. No Git repository detected at the current root.
2. `frontend/src/App.tsx` contains an older commented-out app implementation above the active implementation.
3. Some frontend text appears mojibake-encoded in inspected output, for example smart punctuation or comments rendered as garbled characters. Be careful before editing text content.
4. Product fallback mock data still exists by design. Do not remove it unless the app no longer needs offline/dev fallback.
5. Demo payment is not a real production payment gateway.
6. OTP debug codes are intentionally exposed only in debug/testing. Production delivery still needs a real provider.
7. Live payment credentials (real Stripe keys, live SSLCOMMERZ store) and webhook/IPN registration in provider dashboards are still pending; dev/test uses demo driver.
8. Browser `Something went wrong` should be debugged from console stack traces and network responses before changing backend contracts.
8. Browser `Something went wrong` should be debugged from console stack traces and network responses before changing backend contracts.

## Security Notes

- Never trust frontend totals, prices, stock, role, user ID, or order ownership.
- Keep payment provider details isolated in service classes.
- Never store raw card data.
- Keep real passwords and API keys out of tracked files.
- Preserve authorization checks for admin and order ownership.
- Use Form Requests for validation.
- Use API Resources to avoid leaking fields.
- Keep `APP_DEBUG=false` in production.
- Do not use wildcard CORS in production with credentials/auth.

## Instructions for Future Coding Agents

1. Do not rebuild the frontend.
2. Do not redesign the UI.
3. Do not replace React, Vite, Tailwind, Zustand, or the existing component structure.
4. Do not remove frontend routes unless explicitly requested.
5. Before changing an API response, inspect the frontend consumer first.
6. Keep fetch/API logic centralized in `frontend/src/lib/api.ts` and the existing stores.
7. Preserve local storage token keys unless migration code is added.
8. Treat backend calculations as authoritative.
9. Keep controllers thin. Put business rules in services, requests, resources, and policies.
10. Add or update feature tests for behavior changes.
11. Do not commit `.env`, `.env.testing`, passwords, payment secrets, or personal tokens.
12. Run `php artisan test` before handing work back.
13. If editing frontend UI, keep visual changes minimal and integration-driven.
14. If adding payment provider support, extend the `PaymentGateway` interface (`app/Services/Payments`) and route provider selection through `CountryResolver` rather than coupling provider logic to controllers.
15. If adding admin order management, protect routes with admin authorization and test customer denial paths.

## Recommended Cursor Starting Point

If continuing in Cursor, start with:

1. Open this file.
2. Read `README.md`.
3. Read `docs/api.md`.
4. Read `backend/routes/api.php`.
5. Read the relevant frontend store/page before editing any backend response.
6. Run:

```bash
cd backend
php artisan test
```

7. For Phase 4A, begin with order/admin/payment requirements, not frontend redesign.
