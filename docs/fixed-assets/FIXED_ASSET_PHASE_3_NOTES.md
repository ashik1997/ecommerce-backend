# Fixed Asset Module — Phase 3 Patch Notes

## Purpose
This patch hardens the business workflows after Phase 1/2 UI and report setup.

## Added / Updated

### Workflow Guard
- `app/Services/FixedAsset/FixedAssetWorkflowGuardService.php`
- Prevents invalid operations:
  - assigned asset cannot be assigned again
  - in-transfer/maintenance asset cannot be disposed
  - non-capitalized/disposed asset cannot be operated
  - active assignment must be returned before disposal

### Assignment
- Added cancel action
- Added stricter employee/department validation
- Return date must be after assignment date

### Transfer
- Added cancel action
- Destination warehouse must differ from current warehouse
- Receive date must be after transfer date
- Warehouse updates only after receive

### Maintenance
- Added start action
- Added cancel action
- Completion can update asset condition
- Damaged/beyond-repair asset remains damaged after maintenance completion

### Disposal
- Disposal now starts as `pending`
- Added approve action
- Added cancel action
- Asset is retired only after approval

### Depreciation
- Added duplicate-posting guard
- Added no-eligible-assets guard
- Added reverse action
- Reverse restores opening book value and accumulated depreciation

## Important Notes
- Accounting journal posting is still not finalized in this patch.
- This patch uses existing table columns only; no new migration is required.
- If a previous depreciation run already exists for the same period and warehouse, posting is blocked.

## After Extracting
```bash
php artisan optimize:clear
php artisan route:clear
php artisan view:clear
```

## Test URLs
```text
/fixed-assets/assignments
/fixed-assets/transfers
/fixed-assets/maintenance
/fixed-assets/depreciation
/fixed-assets/disposals
```
