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
- Current backend test result: ✅ `279 passed`, `1386 assertions`.
- Phase 4A (checkout, Stripe, SSLCOMMERZ, order management) is implemented and fully tested.
- Phase 5 (order alerts/notifications) is implemented and tested (`Phase5NotificationTest`, 35 tests).
- Phase 6 (product reviews & ratings + wishlist completion) is implemented and tested (`Phase6ReviewsWishlistTest`, 35 tests).
- Phase 7 (admin analytics & reporting) is implemented and tested (`Phase7AnalyticsTest`, 32 tests).
- Phase 8 (security hardening & auditability) is implemented and tested (`Phase8SecurityTest`, 33 tests).
- Phase 9 (production readiness & reliability) is implemented and tested (`Phase9ReliabilityTest`, 23 tests).
- Phase 10 (notification preferences + real-time notifications) is implemented and tested (`Phase10NotificationRealtimeTest`, 25 tests).
- Git status: ✅ Git repository present at the project root; branch `main` tracks `origin/main`.

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
{"tool":"phpunit","result":"passed","tests":279,"passed":279,"assertions":1386,"duration_ms":75181}
```

Status: ✅ backend test suite passes.

Suite covers Phases 1-3 (`ProductApiTest`, `CartApiTest`, `OrderApiTest`, `AuthApiTest`, `Phase1ProductInventoryTest`, `Phase2UsersAccountsTest`, `AuthorizationApiTest`) plus Phase 4A (`Phase4CheckoutPaymentTest`, `Phase4AdminOrdersTest`), Phase 4B-1 (`Phase4BShippingTest`), Phase 4B-2 (`Phase4BTrackingTest`), Phase 4B-3A (`Phase4B3CourierFoundationTest`), Phase 4B-3B (`Phase4B3CourierShipmentCreationTest`), Phase 4B-3C (`Phase4B3CourierStatusTest`), Phase 4B-4 (`Phase4ResolutionTest`), Phase 5 (`Phase5NotificationTest`, 35 dedicated feature tests), Phase 6 (`Phase6ReviewsWishlistTest`, 35 dedicated feature tests), Phase 7 (`Phase7AnalyticsTest`, 32 dedicated feature tests), and Phase 8 (`Phase8SecurityTest`, 33 dedicated feature tests), Phase 9 (`Phase9ReliabilityTest`, 23 dedicated feature tests), and Phase 10 (`Phase10NotificationRealtimeTest`, 25 dedicated feature tests).

## Git State

Command run from project root:

```bash
git status --short --branch
git log --oneline -3
```

Status: ✅ Git repository detected at `C:\Users\User\Desktop\clothing-store`. Current branch: `main`, tracking `origin/main`. The Phase 5 correction work is committed as `Fix Phase 5 notification triggers and tests` and pushed; the working tree is clean.

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

### Phase 4B-3A - Courier API Integration Foundation

Status: ✅ complete.

Implemented:

- **CourierGateway interface** (`app/Services/Couriers/CourierGateway.php`): Clean contract defining the operations courier providers must implement:
  - `name()` - provider identifier
  - `createShipment(Order $order, Shipment $shipment)` - creates shipment with courier, returns tracking info
  - `getTracking(Shipment $shipment)` - retrieves tracking info and events
  - `getStatus(Shipment $shipment)` - retrieves current status
  - `cancelShipment(Shipment $shipment)` - cancels shipment if supported

- **MockCourierGateway** (`app/Services/Couriers/MockCourierGateway.php`): Deterministic mock provider for development and testing:
  - Generates tracking numbers (TRK prefix) and carrier references (MOCK prefix)
  - Returns estimated delivery dates (3-7 days)
  - Returns current shipment status
  - Returns tracking events from local ShipmentEvent records
  - No network calls, fully deterministic

- **CourierService** (`app/Services/Couriers/CourierService.php`): Provider resolution and management:
  - Resolves default provider from config
  - Can resolve specific driver by name
  - Uses Laravel service container for instantiation

- **Configuration** (`config/couriers.php`):
  - `COURIER_DRIVER` environment variable (default: `mock`)
  - Provider definitions with class references
  - Extensible for future real providers

- **Service Registration** (`AppServiceProvider`):
  - Binds `CourierGateway` interface to configured provider
  - Registers `CourierService` as singleton

- **Tests** (`Phase4B3CourierFoundationTest`): 10 tests covering:
  - Mock provider resolution
  - Courier service gateway resolution
  - Deterministic shipment creation
  - Tracking data retrieval
  - Status retrieval
  - Driver resolution
  - Invalid driver handling
  - Cancel shipment
  - Configuration validation
  - Regression protection

- **Database**: No changes required. Existing Shipment and ShipmentEvent architecture is sufficient for the abstraction.

- **Files Changed**:
  - `backend/app/Services/Couriers/CourierGateway.php` (new)
  - `backend/app/Services/Couriers/MockCourierGateway.php` (new)
  - `backend/app/Services/Couriers/CourierService.php` (new)
  - `backend/config/couriers.php` (new)
  - `backend/app/Providers/AppServiceProvider.php` (modified)
  - `backend/database/factories/ShipmentFactory.php` (new)
  - `backend/tests/Feature/Phase4B3CourierFoundationTest.php` (new)
  - `backend/app/Models/Shipment.php` (modified - added HasFactory trait)

### Phase 4B-3B - Courier Shipment Creation Integration

Status: ✅ complete.

Implemented:

- **ShippingService integration** (`app/Services/ShippingService.php`): Extended to use CourierService for creating courier shipments when admin creates a shipment:
  - Creates internal shipment record first
  - Calls configured CourierGateway (mock provider by default) to create external courier shipment
  - Updates internal shipment with courier response: tracking_number, carrier_reference, carrier, estimated_delivery_at, status
  - Preserves admin-provided values (carrier, tracking_number, tracking_reference) when provided
  - Creates appropriate ShipmentEvent for courier shipment creation
  - Maintains idempotency: prevents duplicate courier shipment creation for same internal shipment

- **MockCourierGateway** (`app/Services/Couriers/MockCourierGateway.php`): Returns deterministic courier response:
  - Generates tracking numbers (TRK prefix) and carrier references (MOCK prefix)
  - Returns estimated delivery dates (3-7 days)
  - Carrier name: "Mock Courier"
  - No network calls, fully deterministic

- **Shipment model** (`app/Models/Shipment.php`): Added `carrier_reference` field to `$fillable` array

- **Database migration** (`2026_09_20_085927_add_carrier_reference_to_shipments_table.php`): Added `carrier_reference` column to shipments table

- **API Resources**:
  - `ShipmentResource`: Added `carrierReference` field
  - `OrderResource`: Added `carrierReference` to shipment data

- **Admin UI** (`/admin/orders`): Extended shipment creation form to work with courier integration (preserves admin-provided values, displays courier-generated tracking info)

- **Tests** (`Phase4B3CourierShipmentCreationTest`): 9 tests covering:
  - Admin creates shipment with courier integration (admin-provided tracking)
  - Admin creates shipment with courier-generated tracking
  - Unpaid order rejection
  - Customer authorization enforcement
  - Duplicate shipment prevention
  - Courier failure handling (mock provider)
  - Customer tracking visibility
  - Cross-customer access prevention

- **API Endpoints**: No new endpoints; extended existing `POST /api/v1/admin/orders/{order}/shipment` to integrate with CourierGateway

- **Database**: Added `carrier_reference` column to `shipments` table via migration

- **Files Changed**:
  - `backend/app/Services/ShippingService.php` (modified)
  - `backend/app/Models/Shipment.php` (modified - added carrier_reference to fillable, HasFactory trait)
  - `backend/app/Http/Resources/ShipmentResource.php` (modified - added carrierReference)
  - `backend/app/Http/Resources/OrderResource.php` (modified - added carrierReference to shipment data)
  - `backend/app/Services/ShippingService.php` (modified - integrated CourierService)
  - `backend/database/migrations/2026_09_20_085927_add_carrier_reference_to_shipments_table.php` (new)
  - `backend/tests/Feature/Phase4B3CourierShipmentCreationTest.php` (new)

### Phase 4B-3C - Courier Tracking Synchronization (proposed)

Phase 4B-3C-1 is complete: Added admin-only endpoint `GET /api/v1/admin/orders/{order}/shipment/status` that fetches the latest courier status for an existing shipment using the existing `CourierGateway->getStatus(Shipment)` method. Requires authenticated admin access via Sanctum token. Returns JSON `{ "status": "..." }`.

Phase 4B-3C-2 is complete: Added status mapping layer. The `CourierGateway` interface now includes `mapExternalStatusToInternal(string $rawStatus): string` method. This maps external courier provider statuses to this project's internal shipment statuses (`pending`, `processing`, `ready_to_ship`, `shipped`, `in_transit`, `out_for_delivery`, `delivered`, `failed_delivery`). The mapping is safe - for unknown statuses, it returns the raw status unchanged without modifying the Shipment model or creating ShipmentEvent records.

**4B-3C-1 Endpoint**: `GET /api/v1/admin/orders/{order}/shipment/status`
- Requires admin Sanctum authentication
- Returns 404 if no shipment exists for the order
- Returns 200 with `{"status": "mapped_internal_status"}` on success

**4B-3C-2 Mapping**: `CourierGateway->mapExternalStatusToInternal(string $rawStatus): string`
- MockCourierGateway: identity mapping (statuses are already internal)
- Real providers: implement proper mapping from external statuses to internal statuses
- Unknown statuses: returned as-is without modifying shipment state

**Tests** (`Phase4B3CourierStatusTest`):
- Admin can fetch courier status
- Unauthenticated access is rejected (401)
- Non-admin/customer access is rejected (403)
- Missing shipment/order returns 404
- Endpoint returns the mapped courier status
- Calling the endpoint does not modify shipment status

Phase 4B-3C-3A is complete: Added `ShippingService::syncShipmentStatus(Shipment $shipment)` method that synchronizes an existing Shipment with the latest courier status. The method:
1. Calls `CourierGateway->getStatus($shipment)` to get the raw courier status
2. Maps it using `CourierGateway->mapExternalStatusToInternal($rawStatus)`
3. If the mapped status differs from the Shipment's current status, reuses the existing `ShippingService::updateShipment()` transition logic (which validates via `canTransitionTo()`, updates the Shipment, and creates exactly one `ShipmentEvent`)
4. If the status is unchanged, does nothing (idempotent)
5. If the mapped status is not a valid internal status, returns without modifying the Shipment or creating an event

**Sync Endpoint**: `POST /api/v1/admin/orders/{order}/shipment/status/sync` (admin Sanctum auth)
- Returns 200 with `{"status": "mapped_status"}` on success
- Returns 404 if no shipment exists for the order

Phase 4B-3B is complete. Recommended next work:

1. Courier tracking synchronization with ShipmentEvent.
2. Courier webhook architecture.
3. Admin courier UI enhancements.
4. Return / Refund / Cancellation Management (Phase 4B-4).
5. Real courier provider configuration and webhook handling.
6. Email/SMS notifications for shipping status changes.

### Phase 4B-4 - Cancellation, Return & Refund Management

**Status: ✅ Complete**

Implemented complete cancellation, return, and refund lifecycle with proper authorization, inventory handling, and provider-aware refund processing.

#### Database Migrations
- `2026_09_21_000000_create_phase4_resolution_tables.php`: Created four new tables:
  - `cancellation_requests` - One per order, tracks cancellation request lifecycle
  - `return_requests` - One per order, tracks return request lifecycle
  - `return_items` - Items within a return request with quantity and resolution status
  - `refunds` - Tracks refund state separately from orders/returns/cancellations

#### Models
- `CancellationRequest`: statuses (pending, approved, rejected, executed), links to Order, User, Refund
- `ReturnRequest`: statuses (pending, approved, rejected, received, resolved), links to Order, User, items, Refund
- `ReturnItem`: resolution_statuses (requested, approved, rejected, received, refund_pending, refunded), links to ReturnRequest and OrderItem
- `Refund`: statuses (pending, processing, succeeded, failed, canceled), links to Order, Payment, ReturnRequest, CancellationRequest; tracks amount, currency, provider, provider_reference

#### Service: OrderResolutionService
- `createCancellationRequest(Order, User, reason)` - Creates cancellation request with eligibility checks
- `reviewCancellation(CancellationRequest, admin, decision, adminReason)` - Admin approves/rejects; on approval executes cancellation
- `createReturnRequest(Order, User, items[], reason)` - Creates return request with item validation
- `reviewReturn(ReturnRequest, admin, decision, adminReason)` - Admin approves/rejects return
- `markReturnReceived(ReturnRequest, admin, adminReason)` - Marks return as received, creates refund
- `updateRefund(Refund, admin, status, providerReference, failureReason)` - Admin updates refund status
- `executeCancellation()` - Handles inventory restoration, payment cancellation/refund, courier shipment cancellation
- `remainingRefundableAmount()` - Prevents over-refund by tracking total refunded amount per order

#### Business Rules
- **Cancellation**: Allowed for orders in `pending`, `confirmed`, `processing` status. Blocked if shipment status is `shipped`, `in_transit`, `out_for_delivery`, or `delivered`.
- **Returns**: Only for `delivered` orders with `paid` payment status. Items must belong to order, quantities validated against purchased quantities.
- **Refunds**: Created only after cancellation approval (for paid orders) or return receipt. Amount calculated server-side. Provider tracked for Stripe/SSLCOMMERZ integration.
- **Inventory**: Restored on cancellation if previously decremented. Returns do not automatically restore inventory (requires inspection workflow).
- **Courier**: On order cancellation, calls `CourierGateway->cancelShipment()` if shipment exists and is not delivered.

#### API Endpoints

**Customer Routes (auth:sanctum):**
- `POST /api/v1/orders/{order}/cancellation` - Create cancellation request
- `GET /api/v1/orders/{order}/cancellation` - View own cancellation request
- `GET /api/v1/orders/{order}/returns` - List return requests for order
- `POST /api/v1/orders/{order}/returns` - Create return request
- `GET /api/v1/orders/{order}/returns/{returnRequest}` - View specific return request

**Admin Routes (auth:sanctum + admin):**
- `GET /api/v1/admin/cancellations` - List cancellation requests (filterable by status)
- `PATCH /api/v1/admin/cancellations/{cancellationRequest}` - Approve/reject cancellation
- `GET /api/v1/admin/returns` - List return requests (filterable by status)
- `PATCH /api/v1/admin/returns/{returnRequest}` - Approve/reject return
- `POST /api/v1/admin/returns/{returnRequest}/received` - Mark return as received
- `GET /api/v1/admin/refunds` - List refunds (filterable by status)
- `PATCH /api/v1/admin/refunds/{refund}` - Update refund status (processing/succeeded/failed/canceled)

#### Authorization
- Customers can only access their own orders' cancellation/return requests
- Admin endpoints require admin role (via `can:create,Product` policy)
- OrderPolicy enforces ownership on customer endpoints

#### Tests
- `Phase4ResolutionTest` (5 tests, 39 assertions):
  - Customer can request cancellation, admin approval cancels order with pending refund
  - Cancellation blocked after shipment leaves warehouse; cross-customer forbidden
  - Delivered order return request → admin review → received → refund lifecycle
  - Return requires delivered owned order and valid order items
  - Customer cannot access admin resolution endpoints

#### Frontend Integration
- Account page: "Request Cancellation" button for eligible orders (pending/confirmed/processing)
- Account page: "Return" button for delivered order items
- Admin orders page: Cancellation review (Approve/Reject), Return review (Approve/Reject/Mark Received), Refund status management
- Admin orders page: "Sync Courier Status" button to trigger courier synchronization

#### Configuration
- `config/orders.php`: Configurable allowed order statuses for cancellation and returns
  - `cancellation.allowed_order_statuses`: ['pending', 'confirmed', 'processing']
  - `cancellation.blocked_shipment_statuses`: ['shipped', 'in_transit', 'out_for_delivery', 'delivered']
  - `returns.allowed_order_statuses`: ['delivered']

### Phase 5 - Order Alerts / Notification System

**Status: ✅ Complete**

Implemented a comprehensive notification system using Laravel's native notification architecture with database and email channels.

#### Database Migration
- `2026_09_21_143406_create_notifications_table.php`: Laravel standard notifications table with UUID primary key, polymorphic `notifiable` relationship, JSON `data` column, `read_at` timestamp

#### Notification Architecture
- **Base classes** (2 files): `OrderNotification` (customer), `AdminOrderNotification` (admin) - both queueable via `ShouldQueue`
- **File counts**: 25 notification files total = 23 concrete + 2 base = 17 customer concrete + 6 admin concrete + 2 base
- **Customer notifications** (17 types):
  - Order: `OrderPlacedNotification`, `OrderPaidNotification`, `OrderPaymentFailedNotification`, `OrderStatusChangedNotification`
  - Shipment: `ShipmentStatusChangedNotification`
  - Cancellation: `CancellationRequestedNotification`, `CancellationApprovedNotification`, `CancellationRejectedNotification`, `CancellationCompletedNotification`
  - Returns: `ReturnRequestedNotification`, `ReturnApprovedNotification`, `ReturnRejectedNotification`, `ReturnReceivedNotification`
  - Refunds: `RefundCreatedNotification`, `RefundProcessingNotification`, `RefundCompletedNotification`, `RefundFailedNotification`
- **Admin notifications** (6 types):
  - `AdminNewOrderNotification`, `AdminOrderPaidNotification`, `AdminCancellationRequestNotification`, `AdminReturnRequestNotification`, `AdminRefundActionRequiredNotification`, `AdminShipmentProblemNotification`
- All notifications include structured data: type, title, message, category, order_id, order_number, action_url, and admin-specific fields (customer_name, customer_email)
- Guest orders are safe: admin notifications render `customer_name` as `Guest` and `customer_email` as `N/A`, and `$order` is a public property on both base classes.

#### Deep links and payload hygiene
- `action_url` for customer order notifications appends `checkout_token` (for example `/order/success/{number}?checkout_token=...`). This is an intentional owner-facing deep link used by the public order confirmation lookup endpoint, not a stored credential. Do not assert that the literal string `token` is absent from notification payloads.
- Notification payloads never contain passwords, card numbers, CVV/CVC values, provider secrets, or auth tokens. This is covered by `test_no_sensitive_data_in_notification_payloads`.

#### Service Layer
- `NotificationService`: Centralized notification triggering with:
  - Duplicate prevention via `DB::afterCommit` callbacks
  - Null-safe user checks (handles guest orders)
  - Error logging without breaking main flow
  - Admin notification broadcasting to all active admins
- Integrated into existing services:
  - `CheckoutService::placeOrder()`: `orderPlaced` dispatched through `DB::afterCommit` after the order/payment rows are written. This was the missing trigger — without it, no order-placed notification was ever produced by a real checkout.
  - `OrderFulfillmentService`: paid, failed (`markPaid`/`markFailed`, both idempotent and already called from demo checkout, Stripe webhook and SSLCOMMERZ IPN)
  - `OrderResolutionService`: cancellation request/approved/rejected/completed, return requested/approved/rejected/received, refund created/processing/completed/failed
  - `ShippingService`: shipment status changes and courier synchronization
- Notifications fire only after the surrounding database transaction commits. A checkout that fails inside its transaction (for example an inventory revalidation failure) rolls back and sends nothing.

#### Admin query fix
- `notifications.data` is a `text` column, so PostgreSQL rejects `data->>'category'` on it. `AdminNotificationController` now casts before extracting: `whereRaw("(data::json->>'category') like ?", ['admin\_%'])`, exposed as the `ADMIN_CATEGORY_PATTERN` / `ADMIN_CATEGORY_BINDING` constants and applied consistently to `index`, `unreadCount`, `markAsRead`, and `markAllAsRead`.
- `markAllAsRead` reads through the `unreadNotifications()` relation (queryable) rather than the resolved collection property, so the filter applies before `markAsRead()`.

#### API Endpoints

**Customer Routes (auth:sanctum):**
- `GET /api/v1/notifications` - Paginated list
- `GET /api/v1/notifications/unread-count` - Unread count
- `POST /api/v1/notifications/{id}/read` - Mark as read
- `POST /api/v1/notifications/mark-all-read` - Mark all as read

**Admin Routes (auth:sanctum + admin):**
- `GET /api/v1/admin/notifications` - Paginated list (admin-only categories)
- `GET /api/v1/admin/notifications/unread-count` - Unread count
- `POST /api/v1/admin/notifications/{id}/read` - Mark as read
- `POST /api/v1/admin/notifications/mark-all-read` - Mark all as read

#### Frontend Integration
- **Notification Store** (`frontend/src/store/notificationStore.ts`): Customer and admin Zustand stores with fetch, unread count, mark as read, mark all as read
- **Header Bell Icon**: Shows unread badge, links to `/notifications`
- **Notifications Page** (`/notifications`): Full notification list with:
  - Category icons and labels
  - Read/unread visual distinction
  - Click to mark as read and navigate to order
  - Mark all as read button
  - Infinite scroll pagination
  - Order number display
  - Relative timestamps

#### Email Notifications
- All notifications implement `toMail()` with:
  - Customer name greeting
  - Order number and total
  - Action button linking to order
  - Brand-consistent styling
- Queueable via `ShouldQueue` interface
- No hardcoded credentials - uses existing mail config
- Test environment uses log/sync drivers

#### Duplicate Prevention and Idempotency
- All notifications are triggered inside `DB::afterCommit` callbacks, so they are transaction-safe: committed work notifies, rolled-back work notifies nothing.
- There is deliberately **no** database unique constraint on notification payload and no JSON hash column. Duplicate prevention lives at the lifecycle/idempotency layer instead:
  - Payment: `StripeWebhookController` skips already-processed provider events (`provider_event_id` match or an already-`paid` payment for a paid-type event), and `OrderFulfillmentService::markPaid()` has its own paid guard. Stock is decremented once, and paid notifications fire once, however many webhooks arrive.
  - Courier sync: `ShippingService::syncShipmentStatus()` returns early when the mapped external status equals the shipment's current status, so repeated syncs create no extra `ShipmentEvent` and no extra notification.
  - Shipment/order status: `ShippingService::updateShipment()` only emits events/notifications when the status actually changes, and `NotificationService` additionally guards `$oldStatus === $newStatus`.
- Coverage for these three cases is in `Phase5NotificationTest` (`test_duplicate_payment_webhook_sends_paid_notifications_once`, `test_courier_sync_is_idempotent_for_notifications`, `test_identical_shipment_status_update_does_not_duplicate_notification`), each driven through real application flows (HTTP checkout + signed webhooks, `ShippingService` with a bound `CourierGateway` stub).

#### Authorization
- Customer notifications: Polymorphic `notifiable` relationship ensures users only see their own
- Admin notifications: Filtered by category prefix `admin_` + admin middleware
- API endpoints protected by `auth:sanctum` + admin policy

#### Tests
- Dedicated feature suite: `backend/tests/Feature/Phase5NotificationTest.php` — **35 tests, 606 assertions across the whole suite**.
- Every notification test drives a real application flow (HTTP routes, `CheckoutService`, `OrderFulfillmentService`, `ShippingService`, `OrderResolutionService`, signature-verified Stripe webhooks); none simply instantiate a notification class or call `NotificationService` directly.
- Coverage:
  - End-to-end demo checkout: customer + admin notified; guest checkout notifies admins only
  - Failed checkout (in-transaction stock failure) → rollback → `Notification::assertNothingSent()`
  - Stripe webhook: paid notifications once; duplicate/second provider event does not duplicate notifications, orders or stock decrements; `payment_intent.payment_failed` notifies the customer without paid notifications
  - Shipment: normal transition notifies the customer only; `failed_delivery` raises the admin alert; courier sync and identical status updates are idempotent
  - Cancellation, return and refund lifecycles through the real customer/admin endpoints
  - Refund pending on a real provider (`payment_provider = 'stripe'`) raises `AdminRefundActionRequiredNotification`; the demo provider does not
  - Customer API: list, pagination (`?per_page=1`, `meta.total`), unread count, mark one, mark all
  - Admin API: list/unread count/mark one/mark all, all filtered to `admin_%` categories only
  - Authorization: unauthenticated 401, customer denied admin endpoints 403, cross-customer read 404
  - Database row persistence (UUID id, `notifiable_type`, `type`, payload key order, `read_at`)
  - Email versions carry the order number/greeting, are `ShouldQueue`, and use the `mail` channel
  - No card/CVV/CVC/secret/password material in customer or admin payloads
- Full suite result: `131 passed (606 assertions)`; no regressions in Phase 1-4 functionality.

#### Configuration
- No new required environment variables
- Uses existing `MAIL_*` configuration
- Queue driver: uses existing queue config (sync for testing, database/redis for production)
- `APP_FRONTEND_URL` / `config('app.frontend_url')` for action links

#### Files Changed

**Backend (New):**
- `backend/app/Notifications/` (25 files: 23 concrete + 2 base)
- `backend/app/Services/NotificationService.php`
- `backend/app/Http/Controllers/Api/V1/Notifications/NotificationController.php`
- `backend/app/Http/Controllers/Api/V1/Notifications/AdminNotificationController.php`
- `backend/app/Http/Resources/NotificationResource.php`
- `backend/database/migrations/2026_09_21_143406_create_notifications_table.php`
- `backend/tests/Feature/Phase5NotificationTest.php` (35 feature tests)

**Backend (Modified):**
- `backend/app/Providers/AppServiceProvider.php` (NotificationService binding)
- `backend/app/Services/CheckoutService.php` (order-placed trigger via `DB::afterCommit`)
- `backend/app/Services/OrderFulfillmentService.php` (paid/failed notifications)
- `backend/app/Services/OrderResolutionService.php` (notifications integration)
- `backend/app/Services/ShippingService.php` (notifications integration)
- `backend/app/Http/Controllers/Api/V1/Notifications/AdminNotificationController.php` (admin category filter)
- `backend/app/Notifications/AdminNewOrderNotification.php`, `AdminOrderNotification.php`, `AdminShipmentProblemNotification.php`, `OrderNotification.php` (guest-order safety, public `$order`, string interpolation fix)
- `backend/routes/api.php` (notification endpoints)

**Frontend (New):**
- `frontend/src/store/notificationStore.ts`
- `frontend/src/pages/NotificationsPage.tsx`

**Frontend (Modified):**
- `frontend/src/App.tsx` (notifications route)
- `frontend/src/components/layout/Header.tsx` (bell icon + unread badge)
- `frontend/src/utils/cn.ts` (formatDate utility)

#### Test Results
- Backend: `131 passed (606 assertions)` (96 tests before Phase 5 coverage was added, +35 in `Phase5NotificationTest`)
- Frontend: `npm run build` ✅ successful

#### Intentionally Deferred (Future Roadmap)
- SMS/WhatsApp provider integrations (Phase 20/21)
- Real-time WebSocket/Laravel Reverb broadcasting
- Browser push notifications
- Marketing/promotional notifications
- Notification preferences per user
- Rich notification actions (buttons in email)
- In-app notification center with filtering/search

### Phase 6 - Product Reviews & Ratings + Wishlist

**Status: ✅ Complete**

#### Audit outcome (read this before touching wishlist/reviews)
- **Reviews did not exist at all**: no model, table, controller, route, test, or frontend component. Only legacy denormalized columns `products.rating` (decimal 3,2 default 0) and `products.reviews_count` (uint default 0), populated with random values by `ProductFactory` and rendered by `ProductResource` (`rating`/`reviews` fields) plus sort-by-rating.
- **Wishlist already existed end-to-end and was NOT rebuilt**: `Wishlist`/`WishlistItem` models, `2026_09_16_154620/21` migrations (with `unique[wishlist_id, product_id]`), guest (`guest_token`) + authenticated (`user_id`) resolution in `WishlistController`, `WishlistResource` (`token`/`ids`/`products`), public `GET /wishlist`, `POST /wishlist/items`, `DELETE /wishlist/items/{productId}` routes, and frontend connection via `useUiStore` (`loadWishlist` on app boot, optimistic toggle + server sync). Phase 6 only fixed gaps (see below).
- Dead code found and removed: `frontend/src/store/wishlistStore.ts` (local-only scaffold, superseded by `useUiStore`). The legacy `components/ui/ProductCard.tsx` (used by `ProductRail`) was still wired to the dead store; it now uses `useUiStore` like the main product card.

#### Database
- New migration `2026_09_22_000000_create_reviews_table.php` creates `reviews`: `id`, `user_id` FK cascade, `product_id` FK cascade, `rating` unsigned tiny int, `title` varchar(120) nullable, `body` text, `status` varchar(20) default `pending` (indexed), `verified_purchase` boolean default false, timestamps, `unique[user_id, product_id]` (one active review per customer per product; editing updates in place), `index[product_id, status]` for public listings, and a `CHECK (rating >= 1 AND rating <= 5)` constraint added via `DB::statement` (`Blueprint::check()` does not exist in this Laravel version).
- No historical migrations were rewritten. The legacy `products.rating` / `products.reviews_count` columns are now maintained from approved reviews (see service) instead of holding seed data.

#### Review architecture
- Model `App\Models\Review` (`STATUSES = pending/approved/rejected`, `HasFactory`, casts `rating => integer`, `verified_purchase => boolean`). `$fillable` covers only `user_id/product_id/rating/title/body`; `status` and `verified_purchase` are server-managed and assigned directly by the service, never mass-assigned from requests.
- Relations added: `Product::reviews()`, `User::reviews()`.
- Factory `Database\Factories\ReviewFactory` with `approved()` / `rejected()` states.
- Policy `App\Policies\ReviewPolicy`: `update`/`delete` restricted to the review author (auto-discovered by naming convention, enforced via `Gate::authorize`). Moderation is admin-only through the management controller + existing `can:create,Product` admin gate.

#### Purchase verification (server-side, never trusts the client)
- `ReviewService::eligibility()` derives everything from `order_items` joined to `orders`:
  - already reviewed → `eligible: false`, reason "already reviewed" (create path → 422);
  - no order item for this user+product in any order → `purchased: false` (create path → 403);
  - purchased but no `status = delivered` AND `payment_status = paid` row → 422 "once your order is delivered".
- Canonical status values used: `Order::STATUSES` (`delivered` is the completion state, same value returns require) plus `payment_status = paid`. Guest orders (null `user_id`) can never satisfy the check.
- No `verified_purchase`, `user_id`, or `status` input is accepted: `StoreReviewRequest`/`UpdateReviewRequest` only permit `rating/title/body`, and `validated()` output drops everything else. The stored `verified_purchase` flag is always true for created reviews (eligibility implies purchase) and is the only thing the UI may display as a "Verified purchase" badge.

#### Moderation workflow
- New reviews are created `pending` and are invisible publicly.
- Editing an approved review resets it to `pending` (no bait-and-switch).
- Admins approve/reject via `PATCH /api/v1/admin/reviews/{review}` (`ModerateReviewRequest`: `decision in approved/rejected`).
- Public listing/summary query `status = approved` only.

#### Aggregation
- `ReviewService::summary()` returns `{count, average, distribution{1..5}}` over approved reviews in a single grouped query; `approvedList()` eager-loads `user,product` (no N+1).
- `refreshProductAggregates()` writes approved count/average back to `products.reviews_count` / `products.rating` after every create/update/delete/moderate, so listings, rating sort, and `ProductResource` stay consistent. `ProductController`/`ProductResource` themselves were not changed.

#### API routes (`/api/v1`)
- Public: `GET products/{product}/reviews` (approved, paginated, `per_page` default 10), `GET products/{product}/reviews/summary`. Inactive/missing products → 404 (same rule as product detail). Route binding is by product slug.
- Authenticated: `GET products/{product}/reviews/mine` (own review or 404), `GET products/{product}/reviews/eligibility` (`{eligible, reason, hasReviewed, verifiedPurchase}`), `POST products/{product}/reviews` → 201, `PUT|PATCH reviews/{review}`, `DELETE reviews/{review}` (owner only, else 403).
- Admin (`auth:sanctum` + `can:create,Product`): `GET admin/reviews` (`status` filter all/pending/approved/rejected, `per_page` default 50), `GET admin/reviews/{review}`, `PATCH admin/reviews/{review}`.
- Wishlist routes unchanged. One behavior change: `POST /wishlist/items` now returns 422 for inactive products (`abort_unless($product->is_active, 422)`); duplicates still resolve to a single row via `firstOrCreate` + the DB unique constraint (second POST returns 200, first 201).

#### Resources / requests
- New `ReviewResource`: `id/rating/title/body/status/verifiedPurchase/customerName/productId(product external_id)/productSlug/productName/createdAt/updatedAt`. No user ids, no emails, no internal notes.
- New `StoreReviewRequest` (`rating required|integer|1-5`, `title nullable|max:120`, `body required|max:2000`), `UpdateReviewRequest` (sometimes variants), `Admin\ModerateReviewRequest` (`decision in approved/rejected`).

#### Frontend (design preserved, no new design system)
- New `frontend/src/components/product/Reviews.tsx`: summary card (average, star bar, count, 1–5 distribution bars), sign-in prompt for guests, ineligibility reason note, own-review card (edit/delete, pending-mod-eration note), validated form with interactive star input reusing `Rating`/`Button`/`Field`/`Input`/`SectionHeading`/`Spinner`/`EmptyState` primitives, approved list with "Show more" pagination, "Verified purchase" badge rendered only from the backend flag. Mounted on `ProductPage` between the product grid and the Related section with an `#reviews` anchor.
- New `frontend/src/pages/admin/AdminReviewsPage.tsx` (route `/admin/reviews`, lazy-loaded in `App.tsx`): status filter (defaults to pending), approve/reject buttons, customer/product links, same admin guard/nav/empty-state patterns as the other admin pages. "Reviews" links added to all three existing admin page headers and to the AccountPage admin shortcut cards.
- `AccountPage` delivered order lines now show a "Review" link to `/product/{slug}#reviews` next to "Return".
- Wishlist UI needed no new components: `ProductCard`, product detail heart, and `WishlistPage` already talk to the backend through `useUiStore`; the only fix was migrating the legacy rail card off the deleted scaffold store.
- Guest wishlist behavior intentionally kept public (token-based, pre-existing design); customer isolation is enforced server-side by user/token resolution.

#### Tests
- New `backend/tests/Feature/Phase6ReviewsWishlistTest.php` — **35 tests**: eligible create (201, pending, verified), unauthenticated 401, never-purchased 403, undelivered 422, unpaid 422, duplicate 422, rating 0/6/3.5 rejected, body required + title max, edit own, approved-edit → pending, edit/delete another's → 403, delete own, status/user_id/verified_purchase tampering ignored, pending/rejected hidden, approved visible without email leakage, summary counts/average/distribution over mixed statuses, inactive/missing product 404s, admin approve/reject/list-filter, non-admin 403 + guest 401, mine/eligibility endpoints, plus 9 wishlist tests (guest flow, auth add/list, duplicate single row, remove, cross-customer isolation, inactive 422, unknown product 422, unknown remove 404).
- Full suite: `166 passed (740 assertions)` — 131 pre-existing + 35 new, zero failures.
- Frontend: `npm run build` ✅ (vite 7.3.6, 2382 modules, `dist/index.html` 1,897.12 kB, gzip 1,044.88 kB).
- `vendor/bin/pint --dirty --format agent` ✅ clean.

#### Known limitations / follow-ups
- Review listing sort is newest-first only; no helpfulness votes or sorting options.
- No review notifications/emails (Phase 5 untouched by design).
- Product `rating`/`reviews_count` seed values in existing dev databases predate the maintenance logic; fresh `migrate:fresh --seed` or a re-save resolves them.
- Wishlist `show` still materializes a row per new guest token (pre-existing behavior, unchanged).
- `products/{product}` show response does not embed reviews; the product page fetches the two review endpoints separately (keeps the listing payload lean).

#### Next recommended phase
- Per the roadmap notes in Phase 5: SMS/WhatsApp provider integrations, real-time (Reverb/WebSocket) notifications, notification preferences. Do not start them without an explicit phase brief.

### Phase 7 - Admin Analytics & Reporting

**Status: ✅ Complete**

#### Audit outcome
- No analytics/dashboard page or endpoint existed. Admin pages were products, customers, orders, reviews; all admin APIs follow `Gate::authorize('viewAny', Order::class)` inside the `can:create,Product` admin group.
- No export/reporting pattern existed anywhere, so export is a client-side CSV download of the already-fetched sales series (no backend work, no new dependency).
- No chart library is installed (`lucide-react` + Tailwind only), so dashboard charts are hand-rolled proportional bar rows reusing existing primitives and design tokens.

#### Metric definitions (do not reinterpret these)
- **Paid population**: orders with `payment_status = 'paid'` (any order `status`). Unpaid/pending/failed/canceled payments never contribute to revenue or AOV.
- **Gross revenue**: `SUM(orders.total)` over the paid population in range.
- **Refunded amount**: `SUM(refunds.amount)` for `status = 'succeeded'` with `requested_at` in range. Refunds only exist for paid orders per the Phase 4 resolution model, so every succeeded refund offsets revenue.
- **Net revenue** (the headline `revenue` metric): gross minus refunded.
- **AOV**: gross revenue divided by paid order count (same paid population); `0.0` when there are no paid orders.
- **Orders KPI**: total orders in range (any status); `paid_orders` reported alongside.
- **Order breakdown**: counts over `Order::STATUSES` (`pending/confirmed/processing/shipped/delivered/cancelled`, zero-filled, never invented) plus `Order::PAYMENT_STATUSES` plus `refunded_orders` (distinct orders with a succeeded refund requested in range).
- **Customers**: `total_customers` (`role = customer`), `purchasing_customers` (all-time distinct buyers of paid orders), `new_customers` (created in range), `repeat_customers` (all-time 2+ paid orders), `guest_orders` vs `authenticated_orders` (paid orders in range by null/non-null `user_id`). Aggregate only — no names, emails, or phones leave the API.
- **Inventory**: exact `Product::getInventoryStatusAttribute` rule in SQL — out of stock when `in_stock = false OR stock_quantity <= 0`, otherwise low stock when `stock_quantity <= low_stock_threshold` (per-product threshold, schema default 5). Product-level only (variants excluded, matching the accessor).
- **Reviews**: status counts, average over `approved` only, distinct approved products. Never bypasses `ReviewService` moderation.
- **Wishlist**: total items, account vs guest wishlist counts, most-saved **active** products.
- **Money** stays in major units with two decimals (same representation as `OrderResource`); the frontend formats via the existing currency store. Timezone is the app timezone (UTC) for all range boundaries; every response carries `{preset, from, to, timezone, group}` metadata.
- **Comparisons** are like-for-like: calendar presets compare against the previous calendar unit, rolling/custom ranges against the immediately preceding equal-length span; `change_percent` is `null` when the previous value is zero.

#### Backend
- `app/Services/Analytics/AnalyticsRange.php`: preset resolution (`today/7d/30d/month/prev_month/year/custom`), auto grouping (day ≤ 62d, week ≤ 370d, else month), explicit `group` override, `previous()` period, chronological bucket list with gap filling, Postgres `date_trunc` bucket expressions.
- `app/Services/Analytics/AnalyticsService.php`: single service with `overview`, `salesSeries`, `orderBreakdown`, `topProducts`, `topCategories`, `customerMetrics`, `inventoryMetrics`, `lowStockList`, `reviewMetrics`, `wishlistMetrics` — all database-side aggregation (`SUM/COUNT/AVG/GROUP BY`), no dataset loading into PHP. (`MAX(boolean)` does not exist in Postgres — boolean rollups use `MAX(col::int)`.)
- `app/Http/Requests/Admin/AnalyticsRequest.php`: `preset/from/to/group/limit/sort` validation (`from <= to`, max 366-day span via `after()` hook, `limit 1-50`, `sort`/`group` whitelists so no arbitrary order-by input is possible).
- `app/Http/Controllers/Api/V1/Admin/AnalyticsController.php`: 9 actions, each opening with `Gate::authorize('viewAny', Order::class)`, returning `{"data": ...}`.
- Migration `2026_09_22_000001_add_analytics_index_to_orders_table.php`: one composite index `orders(payment_status, created_at)` for range queries. No other indexes added (FKs and status columns are already indexed); no caching added (invalidation not justified yet).

#### API endpoints (all `GET /api/v1/admin/...`, admin-only)
- `analytics/overview` — KPIs with previous-period values and percent changes.
- `analytics/sales` — `{range, series[]}` with `{period, orders, gross_revenue, refunded_amount, revenue}` per bucket.
- `analytics/orders` — `{total, by_status[], by_payment_status[], refunded_orders}`.
- `analytics/products` — top products (`limit`, `sort=quantity|revenue`): external id, slug, name, `is_active`, units, revenue, order count; sourced from `order_items` of paid orders.
- `analytics/categories` — per-category units, revenue, order count.
- `analytics/customers` — aggregate customer metrics (no personal data).
- `analytics/inventory` — active/out/low counts, total units, `low_stock_list`.
- `analytics/reviews` — moderation counts, approved average, reviewed products.
- `analytics/wishlist` — item/account/guest counts plus most-saved active products.

#### Frontend
- New `frontend/src/pages/admin/AdminAnalyticsPage.tsx` (route `/admin/analytics`, lazy-loaded): preset buttons (Today/7 Days/30 Days/This Month/Previous Month/This Year/Custom with date inputs and client-side `from <= to` check), 8 KPI cards with prior-period badges, revenue + orders bar charts, status breakdown bars, sortable top-products table, categories, inventory-attention list, review/wishlist panels, client-side sales CSV download. Loads all nine endpoints in parallel (`Promise.allSettled`); a failed overview shows an error banner while other sections degrade to empty states. Responsive grid (`sm:2`, `xl:4`, `lg:2` sections) reusing existing primitives/tokens.
- Nav: "Analytics" link added to all four existing admin page headers, the route in `App.tsx`, and an Analytics shortcut card on `AccountPage` for admins.

#### Tests
- New `backend/tests/Feature/Phase7AnalyticsTest.php` — **32 tests**: admin/customer/guest authorization; unpaid exclusion; succeeded vs non-succeeded refund handling; date filtering; AOV value and zero-order safety; prior-period comparison; daily grouping with gap fill and per-bucket net math; explicit week/month grouping; real status breakdown (rejects invented statuses); refunded-order count; product qty/revenue aggregation, limit, both sorts, unpaid exclusion; category aggregation; customer counts incl. repeat and guest/auth split with no `email` key; inventory rule conformance and low-stock list; review counts and approved-only average; wishlist counts and active-only top list; six validation rejections (preset, date format, reversed/oversized range, limit, sort/group injection); full 9-endpoint sensitive-value scan (`checkout_token`, card fields, passwords, secrets, emails, phones).
- Full suite: `198 passed (982 assertions)` — 166 pre-existing + 32 new, zero failures.
- Frontend: `npm run build` ✅ (vite 7.3.6, 2383 modules, `dist/index.html` 1,915.25 kB, gzip 1,048.63 kB).
- `vendor/bin/pint --dirty --format agent` ✅ clean.

#### Known limitations / follow-ups
- Multi-currency is not normalized: totals are summed as stored (same convention as order display).
- `products/{product}` detail does not embed analytics; dashboard-only.
- No server-side export; CSV is generated in the browser from the fetched series.
- No analytics caching; revisit only with measured need and an invalidation plan.
- `prev_month`/`year` comparisons use calendar units; intraday `today` compares against yesterday.

#### Next recommended phase
- Per earlier roadmap notes: SMS/WhatsApp integrations, real-time (Reverb/WebSocket) updates, notification preferences. Do not start them without an explicit phase brief.

### Phase 8 - Security, Abuse Prevention & Auditability

**Status: ✅ Complete**

This is a hardening phase, not a penetration test and not a substitute for production infrastructure (WAF, TLS termination, DDoS protection — see limitations). No payment, courier, or authorization architecture was replaced.

#### Audit findings (verified in the repo before changing anything)
- **Already strong, left untouched**: generic login failure message (no account enumeration); generic password-reset response with Laravel's expiring single-use broker; OTP resend throttle (1/min) + 5-attempt cap + hashed codes; server-authoritative checkout (totals/provider computed server-side, `payment_provider`/`provider` stripped from input in `CreateOrderRequest::prepareForValidation`); UUID checkout tokens compared with `hash_equals`; Stripe signature-verified idempotent webhooks; SSLCOMMERZ server-side `val_id` + amount matching with paid guards; coupon `validateFor` with global + per-customer limits; review eligibility/ownership/moderation; `can:create,Product` gate on every admin route plus per-action `Gate` checks; CORS restricted to explicit `FRONTEND_URLS` origins with no wildcard; logging limited to order numbers/event ids (no payloads/secrets); frontend has no `dangerouslySetInnerHTML`, card input lives only in component state and only last-four digits persist server-side.
- **Gaps closed by this phase**: zero rate limiting anywhere; no audit trail for admin mutations; no application security headers; registration/password-reset/OTP/guest-lookup/checkout/promo/review/cart/wishlist/resolution endpoints unthrottled.

#### Rate limit architecture
- Laravel named limiters registered in `AppServiceProvider::boot()`, thresholds centralized in `config/security.php`. All limiters segment **authenticated users by ID and guests by IP** (login/password-reset use lowercased `email|ip`), so one abusive client cannot block shared-network users. Secrets are never limiter keys. Exceeding a limit returns Laravel's standard **429 with `Retry-After`**. Webhook/callback routes are deliberately **unthrottled** (providers retry delivery; throttling could break reconciliation), as are public catalog reads and admin/analytics GETs.
- Limiters (`name` → scope → key → thresholds → purpose):
  - `login` → `POST auth/login` → email+IP → 5/min + 30/hour → credential stuffing / brute force.
  - `register` → `POST auth/register` → IP → 3/min + 10/hour → mass account creation.
  - `password-reset` → `POST auth/forgot-password` → email+IP → 3/min + 10/hour → reset-mail abuse.
  - `otp` → `POST auth/otp*` → user-or-IP → 10/min → OTP flooding (complements the 1/min resend rule + 5-attempt cap).
  - `order-lookup` → `GET checkout/orders/{order}` → user-or-IP → 20/min → guest-token brute forcing backstop (122-bit UUID tokens make guessing infeasible; this caps automation).
  - `checkout-quote` → `POST checkout/quote` → user-or-IP → 30/min → quote scraping.
  - `checkout` → `POST checkout/orders` → user-or-IP → 6/min + 60/hour → order flooding while allowing legitimate retries.
  - `promo` → `POST cart/promo` → user-or-IP → 10/min + 100/hour → coupon-code guessing.
  - `reviews` → review create/update/delete → user ID → 10/min + 100/hour → review spam (eligibility + moderation unchanged).
  - `storefront-mutations` → cart/wishlist mutations → user-or-IP → 60/min → API flooding without hurting normal bursts.
  - `resolution-requests` → cancellation/return creation → user ID → 10/min + 60/hour.
  - `admin-mutations` → all non-GET admin routes (products, inventory, orders, shipments incl. courier sync, cancellations, returns, refunds, reviews, customers) → admin user ID → 120/min → runaway-script backstop; dashboard/analytics reads unaffected.
- False-positive review: thresholds allow normal checkout + payment retry, review editing, guest lookup, and dashboard use; per-user segmentation keeps shared-IP customers working. Full suite (231 tests) passes with limiters active, proving legitimate flows are unaffected.

#### Authentication / abuse controls (beyond throttling)
- No login/register/password-reset logic changes were needed: messages already generic, roles forced server-side (`role => customer`), broker tokens already expiring/single-use. Tests prove `role=admin` smuggling is ignored on register and profile update.

#### Guest lookup, checkout, payments, promos, reviews, wishlist/cart
- No architectural changes: UUID tokens + `hash_equals` ownership check kept; server remains authoritative for price/inventory/discount/shipping/provider/totals/ownership/payment state (tests prove provider/total/status overrides are ignored); Stripe/SSLCOMMERZ flows kept with signature/server-side validation; duplicate-callback idempotency re-covered by new tests (Stripe invalid-signature 400, SSLCOMMERZ duplicate IPN single payment, empty callback no-op).

#### Admin mutation security
- Verified: every admin mutation requires the existing gate (method/parameter tampering can't bypass route-level `can` + in-action `Gate` checks); FormRequests accept only allowlisted fields (statuses constrained by `in:` rules; review/verified fields not validatable). Tests prove customers get 403 and state is unchanged.

#### Audit logging
- New `audit_logs` table (migration `2026_09_22_000002`): `actor_id` (nullable, null-on-delete so history survives account removal), `actor_role`, `action` (indexed), nullable polymorphic `auditable`, `ip`, `user_agent`, `metadata` JSON, single `created_at` (indexed). No `updated_at` (rows immutable).
- `App\Services\AuditLogger::log()` captures actor/role/IP/UA and runs metadata through a recursive sanitizer enforcing a sensitive-key denylist (`password, card, cvv, cvc, secret, checkout_token, authorization, bearer, remember_token`), scalar-only values, 500-char cap — so even a careless caller cannot persist credentials.
- Recorded actions: `product.created/updated/deactivated`, `inventory.adjusted` (with previous/new quantities), `order.status_updated`, `shipment.created/updated/synced`, `cancellation.reviewed`, `return.reviewed/received`, `refund.updated`, `review.moderated`, `customer.status_updated`. Metadata holds only public references (order numbers, product external ids, old/new statuses, amounts) — never tokens, addresses, emails, or card data. Ordinary GETs and customer actions are not logged.
- Admin API: `GET admin/audit-logs` (filters `action/actor_id/from/to`, `per_page` max 100, newest-first paginated) + `GET admin/audit-logs/actions`; admin-only via the existing gate. Admin UI: `AdminAuditPage` at `/admin/audit-log` with action/actor/date filters, paginated table (time, action, actor, target, change, IP), linked from all admin headers, the route registry, and the AccountPage admin cards. Retention: `security.audit_retention_days` (default 365, env-overridable) enforced by `php artisan audit:prune`.

#### Security headers / CORS / errors / logging
- New `SecurityHeaders` middleware (global): `X-Content-Type-Options: nosniff`, `Referrer-Policy: same-origin`, `X-Frame-Options: SAMEORIGIN` — all safe for the JSON API + SPA. HSTS/TLS/WAF/edge throttling are reverse-proxy responsibilities (see limitations).
- CORS unchanged (already correct): explicit `FRONTEND_URLS` origins, credentials supported, no wildcard. Production must set `FRONTEND_URLS` to the real storefront origin(s); local dev keeps `http://localhost:5173`.
- Errors unchanged (already correct): `APP_DEBUG` defaults false, API responses forced JSON, no stack traces/paths/secrets in responses; validation messages stay descriptive. Existing `Log::` calls audited — order numbers and event ids only.
- Frontend: no changes required beyond nav links — token lives in `localStorage` (standard for this Sanctum bearer SPA), admin pages guard on role client-side with server enforcement, errors render generic `ApiError` messages, no secrets in Vite env.

#### Files changed
- New: `config/security.php`, `app/Services/AuditLogger.php`, `app/Models/AuditLog.php`, `app/Http/Middleware/SecurityHeaders.php`, `app/Http/Controllers/Api/V1/Admin/AuditLogController.php`, `app/Http/Resources/AuditLogResource.php`, `app/Console/Commands/PruneAuditLogs.php`, migration `2026_09_22_000002_create_audit_logs_table.php`, `database/factories/AuditLogFactory.php`, `tests/Feature/Phase8SecurityTest.php`, `frontend/src/pages/admin/AdminAuditPage.tsx`.
- Modified: `app/Providers/AppServiceProvider.php` (limiters), `bootstrap/app.php` (headers middleware), `routes/api.php` (throttle attachments + audit routes), 8 admin controllers (audit hooks only), `frontend/src/App.tsx` + `AccountPage.tsx` + 6 admin pages (nav links), `CONTEXT.md`.

#### Tests
- New `backend/tests/Feature/Phase8SecurityTest.php` — **33 tests, 143 assertions**: login/register/reset/OTP throttling with 429 + `Retry-After`; login still works; identical enumeration-safe messages; guest lookup valid/invalid/throttled; checkout flood throttling with provider/total/status override rejection; promo flood throttling with discount-amount rejection; Stripe invalid-signature 400; SSLCOMMERZ duplicate idempotency + empty-callback no-op; review spam throttle with ownership/eligibility/field-ignorance checks; storefront-mutation throttle; cross-user cart 404; resolution-request throttle; customer-403 on admin mutation; admin-mutation throttle; audit event content without secrets; sanitizer unit coverage; audit read auth + filters; prune command; register/profile/review mass-assignment rejection; security headers; 4-response sensitive-value scan.
- Full suite: `231 passed (1125 assertions)` — 198 pre-existing + 33 new, zero failures.
- Frontend: `npm run build` ✅ (vite 7.3.6, `dist/index.html` 1,923.86 kB, gzip 1,050.00 kB, ~10.5s).
- `vendor/bin/pint --dirty --format agent` ✅ clean.

#### Remaining limitations (deployment-specific, NOT implemented)
- No WAF, CDN edge filtering, or infrastructure DDoS protection in the repo.
- TLS termination and HSTS must be configured on the production reverse proxy.
- No CAPTCHA (no provider in the repo; throttling used instead), no fraud vendor, no SIEM/external monitoring.
- Rate-limit counters use the default cache store; multi-server deployments must point the cache at a shared driver.
- This phase does not claim the application is "fully secure" — it hardens the documented surfaces with tested controls.

#### Next recommended phase
- Per earlier roadmap notes: SMS/WhatsApp integrations, real-time (Reverb/WebSocket) updates, notification preferences. Do not start them without an explicit phase brief.

### Phase 9 - Production Readiness, Reliability & Observability

**Status: ✅ Complete**

A hardening phase: no payment/courier/auth architecture rewritten, no new infrastructure (no Redis, APM, or vendors), no fake health checks. Every change below was justified by the audit; everything else was documented, not built.

#### Audit findings (verified before changing anything)
- **Already sound, left untouched**: `failed_jobs` table + `database-uuids` driver configured; queue defaults to `database` in prod (`sync` in tests); notifications are `ShouldQueue` and dispatched inside `DB::afterCommit`; inventory hold/release, coupon consumption, cancellation execution, and return receipt all timestamp-guarded (no double decrement/restore); refund re-finalization rejected; duplicate webhooks idempotent; checkout button disabled while processing (no double submit); route-level lazy loading + Suspense present; no frontend polling or mutation auto-retry; cart/wishlist/orders/reviews controllers all eager-load; analytics fully aggregate.
- **Gaps closed**: `/up` existed but ran zero dependency checks; no request correlation ID; 500s rendered framework default; notification/admin/review lists and three admin indexes accepted unbounded `per_page`; `audit:prune` existed but was unscheduled; no env/config validation command; Stripe/SSLCOMMERZ HTTP calls had no timeouts (and timeouts would have escaped as 500s since only `RequestException` was caught); gateway initiation runs inside the checkout DB transaction (documented limitation, architecture preserved).

#### Health / readiness
- `/up` (already registered in `bootstrap/app.php`) now runs `CheckDatabaseHealth` via the `DiagnosingHealth` event: a DB ping covering orders, the database queue/cache stores, and failed jobs. Optional payment/courier APIs are never checked. Failure → 500 in production (exception rethrown with debug on); the response never carries DSNs or exception text.

#### Queue / retry / failure
- No new job system: `database` queue + `failed_jobs` (database-uuids) already configured. Operational contract documented: run `php artisan queue:work --tries=3 --backoff=5,15,60`; inspect with `queue:failed`, recover with `queue:retry all`, clear with `queue:flush`. Notification jobs inherit worker-level retries; permanent validation failures surface once in `failed_jobs` instead of looping.
- Scheduler (`routes/console.php`, app timezone, `withoutOverlapping`): `audit:prune` daily, `queue:prune-failed --hours=720` monthly. Both idempotent and safe to rerun.

#### Request IDs, errors, logging
- New global `RequestId` middleware: server-generated UUID v4 per request (never accepted from clients, never used for auth), returned as `X-Request-ID`, bound into Laravel `Context` for log correlation, and echoed on error responses.
- Production error contract (`bootstrap/app.php`, debug-off only): unexpected `api/*` failures return `{message: "Server error.", code: "INTERNAL_ERROR", request_id}` + header — no traces, paths, or env values. Validation (422), auth (401/403), 404, and 429 responses are byte-identical to before; local/test diagnostics untouched.
- Logging unchanged by design (already minimal and secret-free); request IDs now ride along via Context. No verbose SQL logging in production; query investigation stays a local exercise (`DB::listen` in tests).

#### Performance
- Pagination caps (contract-preserving clamps, 1–100): customer + admin notification lists, review listings, admin cancellation/refund/return indexes. All other lists already capped.
- N+1 audit: no live N+1 found (verified eager loads on products, cart, orders, tracking, wishlist, notifications, reviews, admin orders, analytics aggregates). Locked with an intentional regression test: product listing with 5 products stays within 20 queries.
- Analytics untouched (already aggregate; no caching added — no measured need, invalidation unsafe).
- Frontend: no changes required beyond `ApiError.requestId` plumbing (reads `x-request-id` for support diagnosis). Lazy routes, no polling, and manual payment retry were already correct. Bundle unchanged in shape (~1.92 MB single-file).

#### Reliability fixes
- Outbound payment HTTP now bounded: `payments.http_timeout` (default 15s) + `http_connect_timeout` (default 5s), env-overridable; `ConnectionException` handled on all three call sites (Stripe initiate, SSLCOMMERZ initiate + IPN validation) mapping to the existing user-safe 422 / INVALID paths with secret-free logs.
- Known limitation documented (not rewritten): gateway session initiation still executes inside the checkout transaction, so row locks are held during provider I/O and a provider failure rolls the order back (cart intact, retry safe). The correct future fix is initiate-after-commit with reconciliation, not a bigger transaction.

#### Configuration / deployment
- New `php artisan app:check`: validates APP_URL/APP_KEY, debug-off in production, DB reachability, queue/cache driver allowlists, failed-job driver, mailer + sender, payment-driver credential presence (presence only), frontend origin allowlist. Prints OK/FAIL lines, never secret values, exits non-zero on failure. Safe in CI (passes in the test env).
- Production requirements documented: `APP_ENV=production`, `APP_DEBUG=false`, `FRONTEND_URLS` set to real origins, secrets via env only, `php artisan migrate --force`, `php artisan optimize` (config/route/event/view caches; generated files never committed), a supervised `queue:work` process, scheduler cron (`* * * * *`), shared cache driver for multi-server rate limits, TLS/HSTS at the proxy. Session driver is irrelevant (stateless Sanctum bearer API).

#### Files changed
- New: `app/Listeners/CheckDatabaseHealth.php`, `app/Http/Middleware/RequestId.php`, `app/Console/Commands/CheckApp.php`, `tests/Feature/Phase9ReliabilityTest.php`.
- Modified: `bootstrap/app.php` (RequestId middleware + prod error renderer), `routes/console.php` (scheduler), `config/payments.php` (HTTP guardrails), `StripeGateway.php` + `SslCommerzGateway.php` (timeouts + timeout handling), notification controllers + `ReviewService` + 3 admin controllers (pagination clamps), `frontend/src/lib/api.ts` (`requestId`), `CONTEXT.md`.

#### Tests
- New `backend/tests/Feature/Phase9ReliabilityTest.php` — **23 tests**: healthy `/up` without leaks; unhealthy `/up` on DB loss; request-ID presence/uniqueness/UUID shape and its inability to authorize; prod-shaped 500 with matching header/body IDs and no trace/path/secret leakage; validation shape preserved; failing job persisted to `failed_jobs`; retry-then-fail lifecycle; `queue:failed` listing; scheduler registration; idempotent `audit:prune`; `app:check` pass without leaks + failure on bad config; three pagination clamps; product-listing query-count bound; duplicate cancellation/return-receipt rejection; single refund-completion notification; login-throttle, audit-write, and admin-403 regressions.
- Full suite: `254 passed (1203 assertions)` — 231 pre-existing + 23 new, zero failures.
- Frontend: `npm run build` ✅ (vite 7.3.6, `dist/index.html` 1,923.93 kB, gzip 1,050.03 kB, ~14.8s).
- `vendor/bin/pint --dirty --format agent` ✅ clean.

#### Remaining limitations (deployment-specific, NOT implemented)
- No WAF/CDN, external APM, SIEM, autoscaling, multi-region, automated backups, managed Redis, managed workers, or proxy TLS/HSTS in the repo.
- Rate-limit counters are cache-store local; multi-server needs a shared store.
- No verbose production query logging; no analytics caching.
- Gateway initiation inside the checkout transaction (see above).
- This phase does not claim "production ready" — it implements and tests the listed controls and documents the rest.

#### Remaining limitations (deployment-specific, NOT implemented)
- No WAF/CDN, external APM, SIEM, autoscaling, multi-region, automated backups, managed Redis, managed workers, or proxy TLS/HSTS in the repo.
- Rate-limit counters are cache-store local; multi-server needs a shared store.
- No verbose production query logging; no analytics caching.
- Gateway initiation inside the checkout transaction (see above).
- This phase does not claim "production ready" — it implements and tests the listed controls and documents the rest.

### Phase 10 - Notification Preferences + Real-Time Notifications

**Status: ✅ Complete**

Built on the existing notification architecture (25 files: 23 concrete + 2 base). No payment/courier/auth rewrite, no polling, no new state library, no SMS/WhatsApp vendor.

#### Audit findings (verified before changing anything)
- **Already present, reused**: `NotificationService` with `notifyUser`/`notifyAdmins` choke points and `DB::afterCommit` dispatch; `OrderNotification`/`AdminOrderNotification` base classes (`ShouldQueue`, `via() == ['database','mail']`, `toArray()` payloads); 17 customer + 6 admin categories; customer + admin REST notification endpoints; `useNotificationStore`/`useAdminNotificationStore` (REST polling on mount, bell badge in Header); Sanctum Bearer auth; `database` queue default with `sync` in tests (`BROADCAST_CONNECTION=null` in phpunit); `can:create,Product` admin gate.
- **Missing, added**: preference persistence/API, channel filtering, broadcast transport, channel authorization, Echo integration, preferences UI.
- **No broadcasting existed**: no `config/broadcasting.php`, no `routes/channels.php`, no Reverb/Pusher/Echo dependencies.

#### Preference schema and behavior
- New `notification_preferences` table (migration `2026_09_22_000003`): `user_id` FK cascade, `category` (60), `in_app_enabled` / `email_enabled` / `sms_enabled` / `whatsapp_enabled` (all default true), timestamps, `unique(user_id, category)`.
- `NotificationPreferenceService::catalog()` is the single source of truth: all 23 existing categories with display labels. Missing rows mean "all enabled", so behavior is byte-identical until a user opts out. All categories are informational-optional — no checkout/fulfillment workflow reads notifications, so no category is force-enabled (documented choice, not an oversight).
- `sms`/`whatsapp` columns are stored but inert extension points: no vendor is wired, no channel named `sms`/`whatsapp` exists in any `via()`. Unknown future categories pass through unfiltered so new notification types keep working until catalogued; unknown *channels* default to suppressed (secure default).

#### Preference API
- `GET /api/v1/notification-preferences` → all 23 categories with effective flags for the caller only.
- `PUT /api/v1/notification-preferences` with `{preferences: [{category, in_app_enabled?, email_enabled?, sms_enabled?, whatsapp_enabled?}]}` → upserts the listed categories (omitted ones keep state), returns the full effective list. `UpdateNotificationPreferencesRequest` rejects unknown categories (`Rule::in` over the catalog), unknown fields (after-hook), and non-booleans. There is no user parameter anywhere, so cross-customer writes are structurally impossible.

#### Channel filtering (one centralized mechanism)
- `NotificationPreferenceService::effectiveChannels()` is called from `via()` in **both base classes only** — no concrete class changed. `database` + `broadcast` share the in-app flag (broadcast only feeds the bell); `mail` uses the email flag. Non-user notifiables keep full delivery. `ShouldQueue`, `DB::afterCommit`, and duplicate/idempotency behavior are untouched (`Notification::fake()` never invokes `via()`, so all Phase 5/9 tests still pass unmodified).

#### Real-time transport
- Backend pushes over the Pusher protocol via the framework `pusher` broadcast driver (`pusher/pusher-php-server ^7.3`, the only new composer dependency). `laravel/reverb` could NOT be installed: every 1.x release requires `guzzlehttp/psr7 ^2.6`, which conflicts with the locked `guzzlehttp/guzzle 8.2` → `psr7 ^3.1` chain (verified via composer; downgrading risked the framework HTTP client). The wire protocol is identical, so any Pusher-protocol server (Reverb, soketi) works; this is documented, not hidden.
- `via()` gains `'broadcast'` for everyone (subject to the same preference filter). `broadcastOn()`: customers → private `App.Models.User.{id}`; admins → shared private `admin.notifications`. `broadcastAs()`: `notification.created` (Echo listens `.notification.created`). `broadcastWith()`: explicit allowlist only (type/title/message/category/order_number[/customer_name]/action_url/read_at) — no emails, tokens (other than the pre-existing owner checkout_token deep link also present in the DB record and order emails), payment data, or secrets.
- Ordering guarantee: `via()` order is `database, mail, broadcast` and the sender delivers sequentially, so the DB record (source of truth) always persists before the socket event. Queued `ShouldQueue` notifications broadcast from the worker after commit; multiple tabs receive the same event safely (broadcasts never create rows).
- `routes/channels.php`: user channel checks `(int)$user->id === (int)$id`; admin channel requires `isAdmin() && isActive()`. Socket auth route `POST /broadcasting/auth` via `Broadcast::routes(['middleware' => ['auth:sanctum']])` — guests 401 before callbacks run. Note: channel callbacks live on the boot-time default driver's broadcaster instance (framework behavior); deployments must keep a fixed broadcast driver, as documented.
- `config/broadcasting.php` created (default `null`, `log` for driverless dev, `pusher` toward env-configured host). `.env.example` documents `BROADCAST_CONNECTION=log` default plus commented `PUSHER_*` vars. `broadcasting/auth` added to CORS paths. Tests run on `null` (already the phpunit default) — no socket server needed.

#### Frontend real-time flow
- New `laravel-echo@2.5.0` + `pusher-js@8.6.0` (only new npm dependencies). New `src/lib/realtime.ts`: `connectRealtime()` (auth-gated, idempotent, never throws) subscribes the user channel and, for admins, the admin channel; on `.notification.created` it refetches the unread count and refetches page 1 when on `/notifications` — no optimistic inserts, backend stays authoritative. `disconnectRealtime()` runs on logout/account change. Header wires connect on user change plus a `visibilitychange` unread refetch for cross-tab read consistency. Socket failure degrades to plain HTTP silently.
- `ApiError` gained `authToken()`-backed realtime auth (Bearer header to the auth endpoint).
- Preferences UI: new `Notifications` tab in AccountPage rendering `NotificationPreferences.tsx` (existing Field/Input/Button/Spinner/EmptyState primitives, accent-bronze checkboxes): per-category In-app + Email toggles saved immediately via PUT with revert-on-error toast; admin sections split customer vs store alerts; SMS/WhatsApp rendered disabled as "soon".
- `frontend/.env.example` documents `VITE_REALTIME_ENABLED`, `VITE_REVERB_*`, and optional `VITE_BROADCAST_AUTH_ENDPOINT` (defaults derive from `VITE_API_URL`).

#### Files changed
- New backend: migration `2026_09_22_000003`, `NotificationPreference` model + factory, `NotificationPreferenceService`, `NotificationPreferenceController`, `UpdateNotificationPreferencesRequest`, `NotificationPreferenceResource`, `config/broadcasting.php`, `routes/channels.php`, `Phase10NotificationRealtimeTest`.
- Modified backend: `OrderNotification` + `AdminOrderNotification` (`via` filter, `broadcastOn/As/With`), `User` (relation), `routes/api.php` (preference routes), `AppServiceProvider` (broadcast routes + channel loading), `config/cors.php`, `.env.example`, `composer.json`/`composer.lock` (pusher-php-server only).
- New frontend: `src/lib/realtime.ts`, `src/components/account/NotificationPreferences.tsx`.
- Modified frontend: `notificationStore` untouched (reused as-is); `Header.tsx` (connect + focus refetch), `AccountPage.tsx` (Notifications tab), `lib/api.ts` (`authToken`), `package.json`/`package-lock.json` (echo + pusher-js), `.env.example`.

#### Tests
- New `backend/tests/Feature/Phase10NotificationRealtimeTest.php` — **25 tests, 183 assertions**: preference defaults (23 categories, all true); auth required; update own + untouched-default preservation; caller scoping with smuggled `user_id` ignored; invalid category/field/boolean rejections; DB unique constraint; defaults preserve delivery; in-app-off suppresses DB row; email-off removes mail channel only; all-off sends nothing; per-recipient admin filtering; `via()` default/filtered; private user + admin channel names; `broadcastAs`; minimal safe payload (incl. checkout_token deep-link parity note); socket-auth allows own / rejects other / rejects guests / protects admin channel (pusher driver with local dummy creds, no network); broadcast creates no extra rows; database-before-broadcast ordering; `ShouldQueue` intact.
- Full suite: `279 passed (1386 assertions)` — 254 pre-existing + 25 new, zero failures.
- Frontend: `npm run build` ✅ (vite 7.3.6, 2390 modules, `dist/index.html` 2,004.50 kB, gzip 1,072.87 kB, ~11.4s).
- `vendor/bin/pint --dirty --format agent` ✅ clean.

#### Environment variables / deployment notes
- Backend: `BROADCAST_CONNECTION` (`null` test, `log` default dev, `pusher` for live); `PUSHER_APP_ID/KEY/SECRET/HOST/PORT/SCHEME` toward the socket server; run a Pusher-protocol server (Reverb/soketi) plus `queue:work` (broadcasts of queued notifications emit from the worker). QUEUE_CONNECTION stays `database`.
- Frontend: `VITE_REALTIME_ENABLED=false` disables sockets entirely; `VITE_REVERB_*` point Echo at the server; auth endpoint defaults to `<origin>/broadcasting/auth`.

#### Deferred SMS/WhatsApp work
- Schema columns + API fields + UI placeholders exist; no vendor selected, no channel registered, no sending code. Next phase needs: provider selection, `sms`/`whatsapp` channel classes honoring the stored flags, credential handling, and delivery tests.

#### Remaining limitations
- No dedicated admin notifications page exists (the admin store was already unused); admin realtime currently feeds the existing admin store's unread count.
- `laravel/reverb` server package not installed (documented dependency conflict above); any Pusher-protocol server works.
- Refetch-on-event (not optimistic prepend) trades one extra HTTP call for zero desync risk.
- This phase does not claim full production realtime coverage — socket server operation/scaling stays a deployment concern.

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
- notification dispatch on real checkout, webhooks, shipment and resolution flows
- notification idempotency (duplicate payment event, repeated courier sync, identical status update)
- customer and admin notification endpoints, pagination, and authorization
- notification database persistence, email construction, and payload hygiene
- review creation/visibility/aggregation/moderation/ownership backed by delivered+paid order checks
- wishlist add/remove/list/isolation/inactive-product handling for guest and authenticated flows
- analytics revenue/AOV/series/breakdown/product/category/customer/inventory/review/wishlist math backed by real persisted rows
- analytics authorization (admin-only) and response hygiene (no sensitive values)
- rate-limit behavior (429 + Retry-After) and audit-log integrity (allowlisted metadata only)

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
| 52 | Courier API foundation | ✅ | interface, mock provider, config, provider resolution |
| 53 | Courier shipment creation | ✅ | admin creates shipment via courier gateway, mock provider integration |
| 54 | Courier status fetch (4B-3C-1) | ✅ | admin endpoint GET /api/v1/admin/orders/{order}/shipment/status returns courier status via CourierGateway->getStatus() |
| 55 | Courier status mapping (4B-3C-2) | ✅ | maps external courier statuses to internal shipment statuses via CourierGateway->mapExternalStatusToInternal(); unknown statuses handled safely without modifying shipment |
| 56 | Shipment event sync (4B-3C-3A) | ✅ | ShippingService::syncShipmentStatus() syncs Shipment with courier status, reuses transition validation and event creation; idempotent - no duplicate events on repeat calls; 8 dedicated tests verify behavior |
| 57 | Cancellation workflow | ✅ | Customer requests, admin reviews/approves/rejects, execution with inventory restoration and refund creation; duplicate prevention; authorization enforced |
| 58 | Return workflow | ✅ | Customer requests returns for delivered items, admin approves/rejects/marks received, refund creation; quantity validation; duplicate prevention |
| 59 | Refund management | ✅ | Admin manages refunds (processing/succeeded/failed/canceled); provider-aware; idempotent; prevents over-refund; tracks refund state separately |
| 60 | Courier cancellation on order cancel | ✅ | When order is cancelled, existing courier shipment is cancelled via CourierGateway->cancelShipment() |
| 61 | Notification architecture | ✅ | 25 files = 23 concrete (17 customer + 6 admin) + 2 base classes |
| 62 | Order-placed trigger | ✅ | `CheckoutService::placeOrder()` dispatches via `DB::afterCommit`; failed checkout notifies nothing |
| 63 | Admin notification API | ✅ | category filter `admin_%` on a `text` data column (`data::json->>'category'`) applied to all four endpoints |
| 64 | Customer notification API | ✅ | list/pagination, unread count, mark one, mark all, owner-scoped |
| 65 | Phase 5 test coverage | ✅ | `Phase5NotificationTest`, 35 feature tests through real flows; suite total 131 passed (606 assertions) |
| 66 | Product reviews & ratings | ✅ | `reviews` table, `ReviewService` purchase verification, moderation, aggregates; customer + admin APIs; product page UI; admin moderation page |
| 67 | Wishlist completion | ✅ | backend already existed (guest+auth, unique constraint); added inactive-product guard, connected all UI to backend, removed dead scaffold store, 9 feature tests |
| 68 | Phase 6 test coverage | ✅ | `Phase6ReviewsWishlistTest`, 35 feature tests; suite total 166 passed (740 assertions) |
| 69 | Admin analytics & reporting | ✅ | `AnalyticsService` + 9 admin endpoints, metric definitions below, dashboard UI at `/admin/analytics` |
| 70 | Phase 7 test coverage | ✅ | `Phase7AnalyticsTest`, 32 feature tests; suite total 198 passed (982 assertions) |
| 71 | Security hardening & auditability | ✅ | 12 named rate limiters, audit log + admin UI, security headers; see Phase 8 section |
| 72 | Phase 8 test coverage | ✅ | `Phase8SecurityTest`, 33 tests; suite total 231 passed (1125 assertions) |
| 73 | Production readiness & reliability | ✅ | health checks, request IDs, safe errors, timeouts, scheduler, app:check, pagination caps; see Phase 9 section |
| 74 | Phase 9 test coverage | ✅ | `Phase9ReliabilityTest`, 23 tests; suite total 254 passed (1203 assertions) |
| 75 | Notification preferences + real-time | ✅ | preference schema/API, channel filtering, Reverb/Echo private channels, Account preferences tab; see Phase 10 section |
| 76 | Phase 10 test coverage | ✅ | `Phase10NotificationRealtimeTest`, 25 tests; suite total 279 passed (1386 assertions) |

Legend:

- ✅ implemented and verified by tests or code inspection
- 🟡 partial or needs production hardening
- ❌ not implemented yet

## Known Issues and Technical Debt

1. `frontend/src/App.tsx` contains an older commented-out app implementation above the active implementation.
2. Some frontend text appears mojibake-encoded in inspected output, for example smart punctuation or comments rendered as garbled characters. Be careful before editing text content.
3. Product fallback mock data still exists by design. Do not remove it unless the app no longer needs offline/dev fallback.
4. Demo payment is not a real production payment gateway.
5. OTP debug codes are intentionally exposed only in debug/testing. Production delivery still needs a real provider.
6. Live payment credentials (real Stripe keys, live SSLCOMMERZ store) and webhook/IPN registration in provider dashboards are still pending; dev/test uses demo driver.
7. Browser `Something went wrong` should be debugged from console stack traces and network responses before changing backend contracts.
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
