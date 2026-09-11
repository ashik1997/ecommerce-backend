# FBM-15 — ERP Order Attribution Bridge

Completed in the packaged source on 2026-06-10.

## Goal

FBM-15 connects the consent-bounded landing-attribution handoff from FBM-12, the browser Pixel `eventID` contract from FBM-13 and the encrypted server CAPI event ledger from FBM-14 to tenant-local ERP orders. The bridge creates an immutable order snapshot once, records later lifecycle changes as append-only reconciliations and never makes ERP order placement depend on Meta, queue or FB MARKETING schema availability.

## Tenant-local schema

The guarded migration creates:

```text
fbm_order_attributions
fbm_order_attribution_items
fbm_attribution_reconciliations
```

It also adds nullable `fbm_order_attribution_id` to `fbm_conversion_events` when the FBM-14 ledger exists.

`fbm_order_attributions` enforces one row per `(source_order_type, source_order_id)`. The origin reference, landing-evidence JSON and Purchase event identifier are encrypted at rest. Hidden HMAC values support local comparison without rendering raw values.

`fbm_order_attribution_items` stores immutable line snapshots for future product-level reporting. `fbm_attribution_reconciliations` stores append-only `captured`, `confirmed`, `cancelled`, `returned`, `status_changed` and `amount_adjusted` observations.

## ERP adapters

Two tenant-local ERP families are normalized:

```text
legacy_order     -> orders + order_details
product_order    -> product_orders + product_order_products
```

Legacy checkout hooks capture the browser handoff after order lines and totals exist. Shipping completion, COD payment, online payment and admin lifecycle changes reconcile the existing immutable snapshot.

Newer product-order hooks reconcile status updates, ecommerce editing and return creation. Only `order_source=ecommerce` or `order_source=website` is eligible. POS-only and unrelated manual product orders are ignored by the attribution bridge.

## Lifecycle compatibility boundary

The packaged source contains pre-existing product-order status drift. Some code paths validate or use values such as `canceled`, `cancelled`, `returned`, `accepted` and `processing`, while an older base migration defines a narrower enum. FBM-15 does not destructively alter the ERP order-status column. It normalizes recognized states and records unknown states as a safe review warning.

Before relying on later profitability reports, verify the deployed tenant schema and the actual status values in use.

## Browser and server Purchase deduplication

The standalone landing helper may enrich checkout payloads with:

```text
fbm_attribution_session_uuid
fbm_purchase_event_id
```

For a new checkout attempt, storefront code should call:

```text
FbmLandingAttribution.withCheckoutAttribution(payload, { newPurchase: true })
```

For retries of the same checkout request, call `withCheckoutAttribution(payload)` without rotation. On the order-confirmation page, reuse:

```text
FbmLandingAttribution.getPurchaseEventId()
```

as the `eventId` passed to `FbmBrowserPixel.trackPurchase(...)`. After the confirmed browser Purchase attempt, clear it with:

```text
FbmLandingAttribution.clearPurchaseEventId()
```

The server ledger reuses the same opaque identifier. Calling the FBM-14 boundary repeatedly with the same destination, event name and event identifier returns the existing event row instead of creating a duplicate provider-delivery candidate.

Older storefront clients remain compatible. If they omit the new field, the ERP bridge generates a server-safe UUID. That fallback cannot provide browser/server Purchase deduplication until the storefront wiring is updated.

## Provider and queue boundary

Production Purchase candidate creation occurs only when all of the following are true:

```text
attribution evidence state = attributed
normalized lifecycle state = confirmed
server CAPI mode = live
an active vault connection has a configured CAPI token
no linked conversion event already exists
```

Queue readiness triggers the existing dedicated tenant-safe dispatch path. If queue readiness is absent, the immutable event remains available for the existing permission-controlled **Retry now** no-queue path. Meta, queue, schema and bridge exceptions never invalidate ERP order placement or lifecycle updates.

## Operator UI

`GET /fb-marketing/tracking-attribution` adds privacy-safe totals and recent order-snapshot summaries. The page excludes raw order references, event IDs, Pixel IDs, URLs, UTM values, `_fbc`, `_fbp`, customer values and encrypted payloads.

Trusted roles may run bounded recent reconciliation through:

```text
POST /fb-marketing/tracking-attribution/orders/reconcile-recent
permission: fb_marketing_order_attribution_reconcile.update
```

The action scans a configured maximum number of recent legacy orders and eligible ecommerce product orders. Existing immutable snapshots are reused.

## Deployment checklist

```text
Back up each tenant database and APP_KEY securely.
Apply the tenant-aware FBM-15 migration to every tenant database.
Run php artisan route:list --path=fb-marketing after restoring artisan and Composer dependencies.
Grant fb_marketing_order_attribution_reconcile.update only to intended trusted operators.
Verify actual product_orders.order_status values and column definition in each tenant.
Wire new checkout creation to withCheckoutAttribution(payload, { newPurchase: true }).
Reuse getPurchaseEventId() for the order-confirmed browser Purchase event and clear it afterwards.
Create test legacy guest and authenticated orders and verify one immutable snapshot per ERP order.
Create or update one eligible ecommerce product_order and verify POS-only orders are not attributed.
Confirm cancellation and return produce reconciliation rows without overwriting the initial snapshot.
Keep production APP_DEBUG=false and ALLOW_LOCAL_UNSAFE_WEB_MAINTENANCE_ROUTES=false.
```

## Deferred scope

FBM-16 will build the sales-attribution dashboard. FBM-17 will consume item snapshots for product sales, profit and stock-risk reporting. FBM-18 will compare ERP-attributed revenue with campaign cost.
