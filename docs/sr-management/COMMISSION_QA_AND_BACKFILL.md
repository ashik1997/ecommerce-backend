# Sales Commission & Affiliate — QA and Backfill Guide

## 1. Diagnose module health

Run after extracting patch files and running migrations:

```bash
php artisan commission:diagnose
```

With date range:

```bash
php artisan commission:diagnose --from=2026-06-01 --to=2026-06-30
```

Refresh order summary fields from existing commission entries:

```bash
php artisan commission:diagnose --fix-summary
```

## 2. Backfill commission for existing orders

Preview first:

```bash
php artisan commission:backfill-orders --from=2026-06-01 --to=2026-06-30 --dry-run
```

Run for eligible confirmed orders:

```bash
php artisan commission:backfill-orders --from=2026-06-01 --to=2026-06-30
```

Run for one order:

```bash
php artisan commission:backfill-orders --order-id=123
```

Limit batch size:

```bash
php artisan commission:backfill-orders --limit=500
```

Default eligible statuses:

```text
invoiced, delivered, accepted, processing
```

Override statuses:

```bash
php artisan commission:backfill-orders --status=invoiced,delivered
```

## 3. Safe order before live backfill

1. Database backup.
2. Run migrations and seeders.
3. Create commission rules.
4. Add/verify salesman and affiliate references on orders.
5. Run dry-run backfill.
6. Run actual backfill for a small date range.
7. Check Commission Entries and Commission Report.
8. Run full backfill only after small sample is correct.

## 4. Important behavior

- Backfill uses the same `OrderCommissionService` as live POS/Ecommerce lifecycle hooks.
- Paid/settled entries are protected. If recalculation touches protected entries, order status can move to `requires_review` or deduction adjustment is created.
- Dry-run never writes data.
- `commission:diagnose --fix-summary` does not create new commission entries; it only refreshes order totals/profit summary from existing entries and profit costs.
