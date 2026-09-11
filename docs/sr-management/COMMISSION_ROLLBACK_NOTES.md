# Rollback Notes

This patch adds files and database migrations. For safest rollback in production:

1. Restore database backup if migrations have already run.
2. Restore source backup or revert Git branch.
3. Run:

```bash
php artisan optimize:clear
```

Do not manually drop commission tables from a live database after users start creating settlements, because order profit summaries and commission settlement history may become inconsistent.
