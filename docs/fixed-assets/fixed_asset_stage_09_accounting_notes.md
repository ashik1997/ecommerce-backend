# Fixed Asset Stage 09 — Accounting Integration Polish

This patch is production-safe and focuses only on accounting integration.

## What changed

- Fixed Asset accounting posting now uses the project's canonical `AcTransaction` model.
- `App\Models\AccountTransaction` is kept as a compatibility alias.
- Journal posting is schema-aware and only writes columns that exist in `ac_transactions`.
- Posting guard added:
  - no zero/negative amount posting
  - no same debit and credit account
  - inactive/deleted account heads blocked
  - duplicate posting blocked by source/event checks
  - depreciation reverse blocked if already reversed
- Ledger page now supports:
  - date filter
  - event filter
  - asset/source filter
  - debit/credit totals
  - CSV export
- Optional indexes added safely for fixed asset ledger queries.

## Apply

```bash
php artisan migrate
php artisan optimize:clear
php artisan route:clear
php artisan view:clear
```

## Test URLs

```text
/fixed-assets/accounting/ledger
/fixed-assets/accounting/ledger/export
```

## Important

Run the Fixed Asset installer from Settings first if account heads are missing.
