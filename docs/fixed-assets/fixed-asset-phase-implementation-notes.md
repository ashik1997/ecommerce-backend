# Fixed Asset Management Module — Phase 0/1 Patch Notes

## Implemented/confirmed

- Routes loaded from `routes/fixedAssetRoutes.php` and included in `routes/web.php`.
- Sidebar entry exists in `app/Http/Helpers/BackendSidebarHelper.php` between Inventory & Stock and Accounts & Finance.
- Migrations added for:
  - `fa_categories`
  - `fa_locations`
  - `fa_depreciation_profiles`
  - `fa_disposal_reasons`
  - `fa_assets`
  - `fa_asset_events`
  - `fa_assignments`
  - `fa_transfers`
  - `fa_maintenance_jobs`
  - `fa_depreciation_runs`
  - `fa_depreciation_entries`
  - `fa_disposals`
  - `fa_verification_sessions`
  - `fa_verification_items`
  - `fa_account_mappings`
- Warehouse is mandatory on asset create/update using `warehouse_id` validation against `product_warehouses`.
- Warehouse is treated as asset branch/base in asset register, assignment, transfer, maintenance, disposal, and reports.
- Existing HRM tables are reused for department/employee assignment where available.
- Existing `ac_accounts` is reused by `FixedAssetAccountHeadService`.
- Existing `AC-1200 Fixed Assets` is reused if found by `account_selection_name`, `account_code`, or `sort_code`.
- Missing fixed-asset account heads are created only when absent.
- No hardcoded account IDs are used.
- Core Blade views added under `resources/views/backend/fixed_asset`.

## Added/updated in this patch

- Missing Blade views for dashboard, asset register, asset create/edit/show/tag, assignment, transfer, maintenance, depreciation, disposal, reports, and settings.
- Added basic Eloquent relationships for assignment, transfer, maintenance job, and disposal models.
- Fixed dashboard view key mismatch for `open_maintenance` and warehouse total cost.
- Fixed depreciation preview view to match `DepreciationService::preview()` response shape.

## Install steps

1. Extract the ZIP into the Laravel project root.
2. Run:

```bash
php artisan migrate
php artisan optimize:clear
```

3. Open:

```text
/fixed-assets/dashboard
```

4. Go to Fixed Asset Settings and click **Ensure Missing Account Heads** once.

## Important notes

- This patch keeps Inventory and Fixed Asset separate.
- Asset creation will fail validation if warehouse is not selected.
- Reports currently include the basic warehouse-wise summary required for Phase 1/basic Phase 2.
- Depreciation engine currently supports the V1 straight-line flow through preview/post service logic.
- Accounting transaction posting is not yet wired into `ac_transactions`; account head creation service is ready for the next phase.
