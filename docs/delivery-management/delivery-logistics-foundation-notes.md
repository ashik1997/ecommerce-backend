# Delivery & Logistics Foundation Notes

This batch adds the ERP delivery module foundation without changing existing POS,
ecommerce, sales order, courier dispatch, or settlement behavior.

## Source of Truth

The new operational source of truth is `delivery_shipments`.

Legacy fields such as `product_orders.delivery_info` and
`product_orders.courier_info` are intentionally preserved for compatibility until
the order flows are migrated.

## Added Foundation

- Dynamic delivery providers: API couriers, local providers, internal fleet,
  manual providers, and store pickup can be represented by `delivery_providers`.
- Internal delivery staff can be represented by `delivery_employees`.
- Shipment lifecycle is represented by `delivery_shipments`,
  `delivery_assignments`, and `delivery_shipment_status_logs`.
- Provider raw statuses can map to normalized statuses through
  `delivery_provider_status_maps`.
- Zone pricing foundation is represented by `delivery_zones`,
  `delivery_zone_areas`, `delivery_service_types`, and `delivery_rate_cards`.
- COD and settlement foundations are represented by `delivery_cod_collections`,
  `delivery_settlements`, and `delivery_settlement_items`.
- A separate `DELIVERY MANAGEMENT` sidebar module now owns the delivery dashboard
  and provider management screens. The old courier settings remain untouched for
  backward compatibility.
- Delivery employee management screens are available under the same module for
  company-owned delivery staff/riders. These records can optionally link to a
  system user and later to HR employee profiles.
- Zone and rate card management screens are available. They provide the pricing
  foundation for provider-wise and area-wise delivery charge calculation.
- Legacy backfill command is available:
  `php artisan delivery:backfill-legacy --dry-run` and
  `php artisan delivery:backfill-legacy`.
- Shipment sync service is available through `DeliveryShipmentSyncService`.
  `/pos/desktop` now calls it only after the order transaction commits, inside
  a guarded try/catch. If shipment sync fails, POS order creation/update still
  succeeds and the failure is only logged.
- Shipment assignment workflow is available from the shipment detail page:
  assign/reassign provider or employee, update shipment status, and maintain
  assignment/status history.
- COD collection UI is available from shipment detail and the COD Collections
  list. It tracks expected, collected, submitted, verified, posted, and disputed
  states without posting accounting yet.
- Settlement foundation UI is available for draft settlements from verified COD
  collections. Approval marks settlement items and shipments as settled; no
  accounting posting is performed yet.
- Dashboard and report foundation are available with shipment status, provider
  performance, employee COD outstanding, and delivery finance summaries.

## Current Scope

This is schema, model, and route foundation only.

## Order Integration Constraint

`/pos/desktop` is the stable POS flow. Delivery shipment integration must use the
existing POS form payload and must start as a non-breaking dual-write layer. Do
not change POS totals, payment calculation, invoice creation, stock movement, or
current courier dispatch behavior while adding shipment records.

Do not remove or rename:

- `product_order_courier_methods`
- `area_base_courier_names`
- `area_base_couriers`
- `product_orders.delivery_info`
- `product_orders.courier_info`
- existing courier controllers

## Next Safe Batch

1. Add provider management UI under Delivery & Logistics.
2. Add employee management UI.
3. Add a backfill command that creates inactive/manual provider records from
   existing courier methods and area-based couriers.
4. Add a shipment creation service that dual-writes from existing order delivery
   data without changing dispatch behavior.
