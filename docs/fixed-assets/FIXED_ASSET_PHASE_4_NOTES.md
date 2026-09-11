# Fixed Asset Module — Phase 4 Patch Notes

## Added

- CSV import/export screen: `/fixed-assets/import-export`
- CSV template download
- Warehouse-mandatory CSV import validation
- Asset register CSV export with warehouse/category/status filters
- Printable SVG asset tag route: `/fixed-assets/assets/{asset}/qr.svg`
- Updated asset tag Blade to use generated SVG label
- Routes merged with Phase 3 workflow and Phase 2 verification routes

## Import rules

Required CSV columns:

- `asset_name`
- `category_name`
- `warehouse_id`

Optional columns:

- `location_name`
- `serial_number`
- `brand_name`
- `model_name`
- `purchase_date`
- `available_for_use_date`
- `purchase_cost`
- `additional_cost`
- `residual_value`
- `useful_life_months`
- `depreciation_method`
- `condition_status`
- `invoice_number`
- `notes`

## After extract

```bash
php artisan optimize:clear
php artisan route:clear
php artisan view:clear
```

## Test URLs

```text
/fixed-assets/import-export
/fixed-assets/export
/fixed-assets/assets/{asset}/tag
/fixed-assets/assets/{asset}/qr.svg
```
