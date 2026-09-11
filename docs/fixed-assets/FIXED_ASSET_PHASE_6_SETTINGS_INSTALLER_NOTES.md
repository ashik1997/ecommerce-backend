# Fixed Asset Phase 6 — Settings Page Installer

## What changed

The Fixed Asset installer can now be triggered from:

```text
Fixed Assets > Settings
```

The button checks installation status before running. If all required Fixed Asset account heads already exist, the button is disabled and the backend skips the installer.

## Safety rules

- No duplicate account heads are created.
- Existing account balances are not overwritten.
- Existing account IDs are not hardcoded.
- Installer uses `account_selection_name` keys to detect required Fixed Asset heads.
- CLI command `php artisan fixed-assets:install` also skips when already installed.

## After extract

```bash
php artisan optimize:clear
php artisan route:clear
php artisan view:clear
```

Then visit:

```text
/fixed-assets/settings
```
