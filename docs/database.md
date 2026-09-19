# Database

The schema is normalized around the frontend's current product, cart, wishlist, checkout, and account requirements.

## Core Tables

- `users`: customer/admin accounts, Sanctum token auth, `role` for authorization, `status` for activation, optional phone, and email verification timestamp.
- `categories`: `women`, `men`, `accessories`, including display copy and imagery.
- `products`: frontend-facing product fields including string `external_id`, `slug`, price, compare-at price, rating, badges, featured flags, stock state, low-stock threshold, and details.
- `product_images`: ordered image URLs and alt text.
- `sizes`, `colors`: reusable variant dimensions.
- `product_variants`: product-size-color stock/SKU rows with per-variant low-stock thresholds.
- `coupons`: supports `JAAJ10` percent discount plus active dates, minimum spend, maximum discount, usage limits, and per-customer limits.
- `carts`, `cart_items`: guest or user carts with promo code and item quantities.
- `wishlists`, `wishlist_items`: guest or user saved products with duplicate prevention.
- `addresses`: customer address book and checkout shipping snapshots, including optional label, phone, and default marker.
- `user_otps`: hashed one-time verification codes with purpose, channel, expiry, attempts, and verification timestamp.
- `orders`, `order_items`: persisted orders with historical prices and product snapshots.
- `payments`: demo payment result, reference, status, amount, and card last four only.

## Important Constraints

- Product slugs and external IDs are unique.
- Variant SKU is unique.
- `cart_items` are unique by cart and product variant so same-size/different-color items stay separate.
- `wishlist_items` are unique by wishlist and product.
- Order item prices are copied at purchase time and do not depend on future product price changes.
- Customer address ownership is enforced by `addresses.user_id`.
- OTP codes are hashed and expire; raw codes are not stored.
- Coupon usage is incremented only after a successful paid order.

## Stock

Inventory is represented on both `products.stock_quantity` and `product_variants.stock_quantity`.
Checkout locks and validates products and variants before creating an order, then decrements both product and variant stock.

Inventory status is derived server-side:

- `out_of_stock`: product or variant has no sellable stock.
- `low_stock`: stock is greater than zero and less than or equal to its low-stock threshold.
- `in_stock`: stock is above the low-stock threshold.

Admin inventory adjustments update variant stock and then synchronize aggregate product stock.
