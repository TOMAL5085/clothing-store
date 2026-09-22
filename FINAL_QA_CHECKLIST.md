# Final QA Checklist — JAAJ Clothing Store (Phase 15)

Mark each item PASS / FAIL / NOT TESTABLE LOCALLY / REQUIRES EXTERNAL
CONFIGURATION. Do not mark external items PASS merely because code exists.

## A. Code quality — PASS
- [x] `vendor/bin/pint --dirty` clean on changed files
- [x] No debug output, temp routes, or abandoned code in diff
- [x] `git diff --check` clean

## B. Backend tests — PASS
- [x] Full suite green: 439 passed, 1980 assertions, 0 failures
- [x] New `Phase15FinalQaTest`: 35 tests covering storefront, auth,
      cart, checkout, orders, resolution, notifications, reviews,
      wishlist, marketing authority, audit, health, secrets, analytics,
      pagination

## C. Frontend build — PASS
- [x] `npm run build` succeeds, zero TypeScript errors
- [x] No new dependencies; no localhost outside dev fallbacks

## D. Browser smoke test — PASS (HTTP-level; visual pass needs a human)
- [x] Homepage, products, banners, CMS, categories serve 200
- [x] Register → login → cart → promo (JAAJ10) → demo checkout (paid)
- [x] Order history, notifications after queue run, wishlist, logout
- [x] Account, admin, checkout, product pages serve 200
- [ ] Visual/responsive pixel check — NOT TESTABLE LOCALLY (no browser
      automation in repo; needs human pass on desktop/tablet/mobile)

## E. Admin smoke test — PASS (HTTP-level)
- [x] Admin login; orders list/detail; cancellation approve → Cancelled
- [x] CMS create → public visible → delete → hidden
- [x] Analytics overview; audit log; reviews list
- [ ] Banner image upload click-path — covered by API tests; human
      click-through recommended on staging

## F. Authentication/security — PASS
- [x] Guest 401s, customer admin-403s, cross-customer order 403
- [x] Token-gated guest order confirmation (403 without token)
- [x] Rate limits active (login 429, marketing ingestion covered in Phase 12)
- [x] No secrets in scanned responses; prod error contract unchanged

## G. Payments — PASS (demo/test paths) + REQUIRES EXTERNAL CONFIGURATION
- [x] Demo checkout marks paid, decrements stock, notifies once each
- [x] Provider routing server-authoritative (client override ignored)
- [x] Stripe/SSLCOMMERZ webhook + IPN idempotency (existing suites)
- [ ] Live Stripe keys + webhook registration — REQUIRES EXTERNAL CONFIGURATION
- [ ] Live SSLCOMMERZ credentials + IPN registration — REQUIRES EXTERNAL CONFIGURATION
- [ ] Real-money success/fail/cancel round trip — NOT TESTABLE LOCALLY

## H. Orders — PASS
- [x] Owned history, token confirmation, admin status progression +
      invalid-status rejection

## I. Shipping — PASS (existing suites; abstraction intact)
- [x] Shipment/tracking endpoints present; courier sync idempotent (Phase 4B)

## J. Cancellations/returns/refunds — PASS
- [x] Request → approve → cancelled; return → approve → receive → refund row

## K. Notifications — PASS
- [x] DB rows on checkout; unread count; mark-read zeroes it
- [x] Preference suppression channel (existing suites for full matrix)

## L. Reviews/wishlist — PASS
- [x] Review auth boundary; wishlist add/remove round trip
- [x] Eligibility/duplicate rules (existing Phase 6 suite)

## M. Marketing — PASS
- [x] Unknown events rejected; browser purchase rejected; paid order emits
      exactly one server purchase event
- [x] Provider delivery mock-only (existing Phase 12 suite)

## N. CMS/banners — PASS
- [x] Draft/future/expired/archived hidden; ordering deterministic
- [x] Upload validation, path safety, replace/delete cleanup (existing suite)

## O. Production configuration — PASS
- [x] `app:check` passes locally; detects debug-in-prod, localhost URLs in
      prod, invalid APP_URL, unknown broadcast driver
- [x] `.env.example` complete; no secrets committed
- [x] `optimize`/`optimize:clear` compatible; no generated files committed

## P. Backup/recovery — REQUIRES EXTERNAL CONFIGURATION
- [ ] Scheduled PostgreSQL backups + retention — external infra
- [ ] Restore tested — external
- [ ] Media (`storage/app/public`) included in backups — documented

## Q. Deployment — documented, NOT PERFORMED
- [ ] No cloud deployment executed in this phase (explicitly out of scope)
- [x] DEPLOYMENT.md + PRODUCTION_CHECKLIST.md reviewed against repo

## R. Client-provided credentials/dependencies — all pending
- [ ] Domain, hosting, production PostgreSQL, mail provider, Stripe live
      keys, SSLCOMMERZ live credentials, TLS, monitoring/alerting,
      backups, (later) SMS/WhatsApp + realtime + marketing vendors
