# Architecture

`clothing-store` is split into an existing Vite React frontend and a Laravel REST API backend.

## Frontend

- React 19, React Router 7, Vite 7, Tailwind CSS 4.
- Zustand stores for auth, cart, wishlist, orders, theme, currency, and UI overlays.
- `VITE_API_URL` points to the Laravel API, defaulting to `http://localhost:8000/api/v1`.
- The approved UI is preserved. Integration changes are limited to data fetching and store synchronization.
- `/admin/products` adds a minimal role-protected inventory management screen using the existing Tailwind/components.
- `/account` uses the existing account layout for profile edits, password changes, OTP verification, orders, and addresses.
- `/admin/customers` adds minimal role-protected customer management without introducing a new dashboard design.

## Backend

- Laravel 13 application in `backend/`.
- Versioned REST API under `/api/v1`.
- PostgreSQL through Eloquent migrations/models.
- Sanctum personal access tokens for authenticated customer endpoints.
- Form Requests validate incoming payloads.
- API Resources keep response shapes stable for the current frontend.
- Services contain cart, checkout, inventory, and demo payment logic.
- Admin product and inventory routes are protected with Sanctum plus product policy authorization.
- OTP verification is provider-independent; development responses include `debugOtp` only when app debug/testing is enabled.
- Customer-private routes derive identity from Sanctum and never trust frontend-supplied user IDs.

## Runtime Flow

1. Frontend loads product catalog from `GET /api/v1/products`.
2. Product reads fall back to local mock data if the API is unavailable during development.
3. Auth stores a Sanctum bearer token in `localStorage`.
4. Cart actions update local UI immediately and sync to the backend cart API.
5. Checkout sends cart token, shipping address, delivery method, promo, and demo card details to Laravel.
6. Laravel recalculates totals, validates inventory, creates order/payment rows, decrements stock, and clears the cart.

## Local Services

- Frontend: `http://localhost:5173`
- Backend: `http://localhost:8000`
- Database: PostgreSQL database named `clothing_store`

Enable PHP's `pdo_pgsql` extension before running migrations/tests.
