# FB MARKETING — Release Runbook

## Pre-release commands

```bash
php -l routes/fbMarketingRoutes.php
php -l routes/api.php
php -l routes/web.php
bash scripts/fbm-security-gate.sh
bash scripts/fbm-production-readiness.sh
php artisan route:list --path=fb-marketing
php artisan route:list --path=api/fb-marketing
```

## Queue and cron

Prepare the dedicated central queue storage, then verify:

```bash
php artisan fb-marketing:queue-readiness --tenant=<registry-id>
```

Run a dedicated worker:

```bash
php artisan queue:work fb-marketing --queue=fb-marketing
```

Enable scheduler dispatch only after the worker and tenant registry are verified.

## Release package

```text
Include scripts/fbm-security-gate.sh.
Include scripts/fbm-production-readiness.sh.
Include PATCH_DELETE_MANIFEST.txt.
Include docs/fb-marketing/PRODUCTION_READINESS_CHECKLIST.md.
Include docs/fb-marketing/RELEASE_RUNBOOK.md.
Apply deletion manifest entries on hosts that already contain public/info.php or public/error_log.
```

## Rollback

```text
Revoke fb_marketing_access.read to hide and block the module quickly.
Stop the fb-marketing queue worker.
Disable scheduled sync in module settings.
Keep append-only ledgers unless data-retention owners approve removal.
```
