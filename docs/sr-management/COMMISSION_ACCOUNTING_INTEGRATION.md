# Commission Accounting Integration — v9

This patch adds optional accounting posting for commission settlement.

## Required Seeder

Run after migration:

```bash
php artisan db:seed --class=CommissionAccountingSeeder
```

The seeder creates/updates two chart-of-account heads:

- `sales_commission_expense` → Sales Commission Expense
- `commission_payable` → Commission Payable

## Accounting Entries

### Settlement Approved

```text
Dr Sales Commission Expense
Cr Commission Payable
```

### Settlement Fully Paid

```text
Dr Commission Payable
Cr Cash/Bank
```

## Partial Payment Note

v9 keeps partial-payment accounting conservative. Partial paid status remains operationally tracked, but payment accounting is posted when the settlement becomes fully paid. This avoids duplicate payment postings because the current `commission_settlements` table does not yet have a separate settlement-payment ledger.

A future v10 can add `commission_settlement_payments` for every partial payment transaction.

## Safety

- Approval posting is idempotent via `accounting_transaction_id`.
- Payment posting is idempotent via `payment_transaction_id`.
- Cancelled settlement reverses related accounting transactions by marking them `inactive`.
- If account heads are missing, `accounting_status = account_missing`.
