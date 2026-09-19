# API

Base URL: `/api/v1`

Responses use Laravel API Resources. Validation errors return HTTP 422 with `message` and `errors`.

## Frontend Contract Map

| Frontend route/action | API | Auth | Notes |
| --- | --- | --- | --- |
| `/`, product rails | `GET /products?per_page=100` | No | Provides `isNew`, `bestseller`, images, prices. |
| `/shop` | `GET /products` | No | Supports `category`, `q`, `sizes[]`, `colors[]`, `min_price`, `max_price`, `in_stock`, `sort`, `per_page`. |
| `/product/:slug` | `GET /products/{slug}` | No | Includes colors, sizes, details, images. |
| Related products | `GET /products/{slug}/related` | No | Same category, excluding current product. |
| Search overlay | `GET /products?q=...` | No | Frontend currently searches loaded catalog. |
| Register | `POST /auth/register` | No | `{ name, email, phone?, password, password_confirmation }`; returns token and issues email OTP. |
| Login | `POST /auth/login` | No | `{ email, password }`, returns bearer token. |
| Current user | `GET /auth/me` | Yes | Used for account identity. |
| Logout | `POST /auth/logout` | Yes | Deletes current token. |
| OTP request | `POST /auth/otp` | Yes | Issues a new email verification code with resend throttling. |
| OTP verify | `POST /auth/otp/verify` | Yes | `{ code }`; verifies the current user's email. |
| Forgot password | `POST /auth/forgot-password` | No | Always returns a safe generic message. |
| Profile update | `PUT /profile` | Yes | `{ name, email, phone? }`; role/status cannot be changed by customers. |
| Password update | `PUT /profile/password` | Yes | `{ current_password, password, password_confirmation }`. |
| Address book | `GET/POST /addresses`, `GET/PUT/DELETE /addresses/{id}` | Yes | Users can only access their own addresses. |
| Add cart item | `POST /cart/items` | Optional | `{ product_id, size, color?, quantity, cart_token? }`; server validates stock and variant. |
| Update cart item | `PATCH /cart/items/{lineId}` | Optional | `{ quantity, cart_token? }`; line IDs match frontend `productId__size`. |
| Remove cart item | `DELETE /cart/items/{lineId}` | Optional | Uses cart token for guest carts. |
| Apply/remove promo | `POST /cart/promo` | Optional | `{ promo_code, cart_token? }`; null removes coupon and totals are recalculated server-side. |
| Clear cart | `DELETE /cart` | Optional | Clears cart and promo. |
| Wishlist | `GET /wishlist`, `POST /wishlist/items`, `DELETE /wishlist/items/{productId}` | Optional | Duplicate entries are blocked. |
| Checkout | `POST /checkout/orders` | Optional | Validates cart, inventory, address, payment, totals. |
| Account orders | `GET /orders`, `GET /orders/{number}` | Yes | Customers can only access their own orders. |
| Admin inventory | `GET /admin/products` | Admin | Supports `status=all|active|inactive`, `q`, and `per_page`; includes variants and stock status. |
| Admin product create | `POST /admin/products` | Admin | Creates product, images, variants, prices, SKU, category, flags, and stock fields. |
| Admin product update | `PUT /admin/products/{slug}` | Admin | Updates product data, replaces images when submitted, upserts/deactivates variants. |
| Admin product deactivate | `DELETE /admin/products/{slug}` | Admin | Soft-deactivates the product for storefront listings. |
| Admin inventory adjust | `POST /admin/products/{slug}/inventory` | Admin | `{ variant_id?, mode, quantity, low_stock_threshold? }`; mode is `set`, `increase`, or `decrease`. |
| Admin customers | `GET /admin/customers` | Admin | Supports `q`, `status=all|active|inactive`, and `per_page`. |
| Admin customer details | `GET /admin/customers/{id}` | Admin | Returns profile summary, verification state, order/address counts. |
| Admin customer status | `PATCH /admin/customers/{id}/status` | Admin | `{ status: "active"|"inactive" }`; inactive customers cannot log in. |

## OTP Verification

OTP codes are six digits, hashed at rest, expire after 10 minutes, and are one-time use. Verification allows five incorrect attempts before the code must be reissued. Local development and tests expose `debugOtp` in API responses when `APP_DEBUG=true`; production delivery still needs a real mail/SMS provider configuration.

## Product Listing

`GET /products` is backend-powered and supports:

- `category`: category slug.
- `q`: product/category/brand/SKU search.
- `sizes[]`, `colors[]`: variant filters.
- `min_price`, `max_price`: price range filters.
- `in_stock=1`: only products with active stocked variants.
- `status`: `active`, `featured`, `new`, `bestseller`, or `sale`.
- `sort`: `featured`, `newest`, `oldest`, `price-asc`, `price-desc`, `rating`, `name-asc`, or `name-desc`.
- `page`, `per_page`: paginated responses.

## Checkout Payment

The current frontend specifies a demo gateway:

- `4242 4242 4242 4242` succeeds.
- Any card ending in `0000` is declined.
- Raw card numbers are never stored.

Payment logic is isolated in `App\Services\PaymentService`.

## Shopping

Cart totals are authoritative from Laravel. The cart response includes line unit prices, line totals, available stock, product/variant metadata, applied coupon data, and `summary` totals. Frontend totals should display this response instead of recalculating prices.

`JAAJ10` is seeded as an active percent coupon worth 10%. Coupon validation supports active/inactive state, start date, expiration date, minimum order amount, maximum discount amount, global usage limit, and per-customer usage limit. Successful paid checkout increments coupon usage; declined payments do not.
