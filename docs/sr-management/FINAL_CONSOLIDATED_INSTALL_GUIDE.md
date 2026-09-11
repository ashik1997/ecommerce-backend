# Sales Commission + Affiliate Management — Final Consolidated Patch v11

This ZIP is a consolidated latest patch. You do **not** need to apply v1-v10 one by one if you use this package.

## What This Module Adds

- Affiliate Partners
- Commission Rules
- Order-wise Commission Entries
- Salesman + Affiliate reference fields on orders
- POS + Ecommerce lifecycle commission hooks
- Commission Settlement with partial/full payment support
- Commission Ledger
- Commission Reports and CSV export
- Order profit cost integration
- Commission accounting integration
- Permission route seeder
- Diagnostics and backfill console commands

## Safe Install Order

1. Take full code backup.
2. Take full tenant database backup.
3. Extract ZIP into project root.
4. Run:

```bash
php artisan migrate
php artisan db:seed --class=CommissionPermissionRoutesSeeder
php artisan db:seed --class=CommissionAccountingSeeder
php artisan optimize:clear
```

5. Run diagnostics:

```bash
php artisan commission:diagnose
```

6. If summary mismatch is found:

```bash
php artisan commission:diagnose --fix-summary
```

## Existing Order Backfill

Dry run first:

```bash
php artisan commission:backfill-orders --dry-run
```

Examples:

```bash
php artisan commission:backfill-orders --status=delivered --dry-run
php artisan commission:backfill-orders --from=2026-01-01 --to=2026-06-05 --dry-run
php artisan commission:backfill-orders --order-id=123
```

Run actual backfill only after checking dry-run output:

```bash
php artisan commission:backfill-orders --status=delivered
```

## Core Verification Flow

1. Create active affiliate.
2. Create active commission rule.
3. Create POS order with salesman/affiliate.
4. Confirm/invoice order.
5. Check commission entries.
6. Approve entries.
7. Generate settlement.
8. Approve settlement.
9. Pay settlement.
10. Check ledger, report, and accounting entries.

## Critical Business Rules

- Pending/approved unpaid commission may be reversed.
- Paid commission is not deleted; reversal creates next-settlement adjustment.
- Duplicate commission generation is guarded by order commission status.
- Profit cost sync uses `order_profit_costs`.
- Accounting integration uses seeded commission expense/payable accounts.

## Rollback Notes

Do not rollback migrations on production after orders have generated commissions unless you have a full DB backup. Prefer disabling sidebar/menu/routes and keeping data intact.
