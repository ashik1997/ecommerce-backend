# Fixed Asset Management Installation

1. Backup project files and tenant databases.
2. Extract this ZIP in the Laravel project root.
3. Run migrations on the tenant database context used by your project.
4. Seed fixed asset defaults:
   ```bash
   php artisan db:seed --class=FixedAssetSeeder
   ```
5. Open `/fixed-assets/settings` and click **Ensure Fixed Asset Account Heads** if required.
6. Create or confirm active warehouses from existing Inventory > Warehouse module.
7. Create asset categories/locations, then add assets.

## Core rule
Every asset requires `warehouse_id`. Warehouse is used as branch/asset base and every report has warehouse overview.

## Existing files patched
- routes/web.php
- resources/views/backend/master.blade.php
- app/Http/Helpers/BackendSidebarHelper.php

## Accounting heads
The service `FixedAssetAccountHeadService` reuses existing heads by `account_selection_name` or `account_code`. It creates only missing heads and never changes balances.
