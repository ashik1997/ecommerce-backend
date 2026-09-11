# Sales Commission & Affiliate Module — Deployment Checklist

## 1. Before Extract

- Take full project backup.
- Take tenant database backup.
- Confirm current order edit, POS and e-commerce order flows are working.
- Apply first on staging or a copied tenant database.

## 2. Extract Patch

Extract the ZIP at Laravel project root.

## 3. Run Commands

```bash
php artisan migrate
php artisan db:seed --class=CommissionPermissionRoutesSeeder
php artisan optimize:clear
```

If non-admin users do not see the module, regenerate/assign permission routes from the existing Role & Permission UI.

## 4. First Configuration

1. Go to SR Management → Affiliate Partners.
2. Create one affiliate with a unique code.
3. Go to SR Management → Commission Rules.
4. Create one general salesman rule with priority 100.
5. Create one affiliate rule with priority 100.
6. Keep broad rules at higher priority numbers and specific rules at lower priority numbers.

## 5. Production Safety Notes

- Do not delete paid commission entries.
- Reverse/cancel paid commission through adjustment only.
- Use recalculation only for orders that need review.
- Confirm rule priority before activating overlapping rules.
