# Fixed Asset Module - Phase 5 Accounting & Production Notes

## Added

- Fixed asset accounting posting service.
- Auto posting on asset capitalization.
- Auto posting on depreciation run.
- Auto reversal journal on depreciation reversal.
- Auto posting on maintenance completion when cost exists.
- Auto posting on disposal approval.
- Fixed asset accounting ledger page.
- Migration to add fixed asset references to `ac_transactions`.
- Artisan installer command: `php artisan fixed-assets:install --migrate`.

## Important Accounting Rules

- No hardcoded account IDs are used.
- `FixedAssetAccountHeadService` ensures missing account heads only when absent.
- Duplicate journals are prevented by `event_type + source_type + source_id + asset_id` checks.
- Existing account balances are not overwritten.
- Warehouse/branch asset tracking remains mandatory through `warehouse_id`.

## After Extract

```bash
php artisan migrate
php artisan fixed-assets:install
php artisan optimize:clear
php artisan route:clear
php artisan view:clear
```

## Test URLs

```text
/fixed-assets/accounting/ledger
/fixed-assets/assets/create
/fixed-assets/depreciation
/fixed-assets/disposals
/fixed-assets/maintenance
```

## Suggested Manual Test

1. Create an asset with warehouse selected.
2. Check `ac_transactions` for `fixed_asset_capitalization`.
3. Run depreciation for a period.
4. Check `fixed_asset_depreciation` journal row.
5. Reverse the depreciation run.
6. Check `fixed_asset_depreciation_reverse` journal row.
7. Complete maintenance with cost.
8. Check `fixed_asset_maintenance_expense` journal row.
9. Approve disposal.
10. Check disposal journal rows.
