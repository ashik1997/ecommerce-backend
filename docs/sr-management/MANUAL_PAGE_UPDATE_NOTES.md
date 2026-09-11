# Sales Commission & Affiliate Module - Manual Page Update

This final update adds a user-facing manual page as the last menu item under **SR MANAGEMENT**.

## Added

- `CommissionManualController`
- Bangla manual Blade:
  - `resources/views/backend/sr_management/manual/commission_manual_bn.blade.php`
- English manual Blade:
  - `resources/views/backend/sr_management/manual/commission_manual_en.blade.php`
- Routes:
  - `sr-management/commission-manual`
  - `sr-management/commission-manual/bn`
  - `sr-management/commission-manual/en`
- Sidebar item:
  - `SR MANAGEMENT → Manual`
- Permission routes for manual pages in `CommissionPermissionRoutesSeeder`

## Usage

Open:

- Bangla: `/sr-management/commission-manual/bn`
- English: `/sr-management/commission-manual/en`

The sidebar menu points to the Bangla manual by default and the page includes a language switch button.

## After Extract

```bash
php artisan db:seed --class=CommissionPermissionRoutesSeeder
php artisan optimize:clear
```
