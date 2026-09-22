# JAAJ Admin Guide — Task Procedures

Sign in as admin, then use the Account page shortcuts or go directly to
the `/admin/...` URLs. Every mutation is recorded in the audit log.

## How to add a product

1. Open `/admin/products`.
2. Fill in name, slug, price, category, and stock per size variant.
3. Save. The product appears in `/shop` immediately if active and in stock.

## How to update stock

1. Open `/admin/products`.
2. Find the product (search by name/SKU), adjust the variant quantity
   (set / increase / decrease), save.
3. Low-stock and out-of-stock badges update automatically.

## How to process an order

1. Open `/admin/orders`, filter by status if needed, open the order.
2. Progress the status (processing → shipped → delivered) as fulfillment
   proceeds. Customers are notified at each step.
3. Create the shipment with carrier + tracking number; use “sync” to pull
   the latest courier status. Tracking events appear on the customer's
   order page.

## How to handle a cancellation

1. Open the order; the pending cancellation request is shown with the
   customer's reason.
2. Approve → the order is cancelled, stock is restored, and a refund is
   created if the order was paid. Reject → the order continues normally.
3. Rejected or already-reviewed requests cannot be approved twice.

## How to handle a return/refund

1. Open `/admin/orders`, find the return request on the order.
2. Approve the return, then mark it received once the package arrives —
   this creates the refund in `pending` status.
3. Move the refund through `processing` → `succeeded` (or `failed` with a
   reason). Each transition notifies the customer once.

## How to create an announcement

1. Open `/admin/content` → Content tab → New Content.
2. Use key `site-announcement`, type `announcement`, status `published`.
3. Title = lead-in text, subtitle = highlighted part (shown in red).
4. Only one published announcement shows at a time.

## How to upload a banner

1. Open `/admin/content` → Banners tab → New Banner (title optional).
2. Save, then use the Image / Mobile image upload on the banner row
   (JPG/PNG/WebP, max 5MB each). Uploading replaces — and deletes — the
   previous file.
3. Set status `published`. The hero slider picks it up within a minute.

## How to schedule a banner

1. Edit the banner, set Starts at / Ends at, save.
2. Future banners stay hidden until their start; expired banners disappear
   automatically. No worker or cron action is required.

## How to reorder banners

1. Edit each banner's sort-order number (lower = earlier) and save.
2. Ties keep creation order. The storefront reflects the change within a minute.

## How to view analytics

1. Open `/admin/analytics`.
2. Pick a preset (Today / 7 / 30 days / month / year / custom).
3. Cards show revenue, orders, customers, inventory, reviews, wishlist,
   and the marketing funnel. Export sales CSV where offered.

## How to inspect audit logs

1. Open `/admin/audit-log`.
2. Filter by action (e.g. `order.status_updated`, `refund.updated`,
   `cms.created`), actor, or date range.
3. Each row shows who did what, to which record, and when.
