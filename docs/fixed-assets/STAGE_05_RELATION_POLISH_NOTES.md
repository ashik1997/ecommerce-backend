# Fixed Asset Module — Stage 05 Relation Polish Patch

This patch updates only Eloquent model files under:

`app/Models/FixedAsset/`

## What changed

- Added missing model relationships for assets, categories, locations, assignments, transfers, depreciation, disposal, verification and account mappings.
- Added decimal/date/datetime/boolean casts matching existing migration columns.
- Added safe query scopes for common filters such as warehouse, category, status, period and active records.
- Added display helper attributes for asset and location names.

## What this patch does not do

- No migration changes.
- No database data changes.
- No route/controller/view changes.
- No accounting balance changes.

## After extraction

Run:

```bash
php artisan optimize:clear
php artisan view:clear
php artisan route:clear
```

Optional quick check:

```bash
php artisan tinker
>>> App\Models\FixedAsset\FixedAsset::with(['category','warehouse','location','department','custodianEmployee'])->first();
```
