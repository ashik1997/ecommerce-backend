# FBM-20 - Reporting Center and Exports

Completed in the packaged source on 2026-06-11.

## Goal

FBM-20 adds a central reporting page and request-bound CSV export ledger for browser-safe FB MARKETING report projections.

## User-facing page

```text
GET /fb-marketing/reports
POST /fb-marketing/reports/exports
GET /fb-marketing/reports/exports/{export}/{token}/download
```

Supported CSV exports:

```text
Attribution reports
Product performance
Profitability
Boosting jobs ledger
```

## Export boundary

Exports use existing report services and safe projected rows only. They do not render or export raw provider IDs, raw URLs, query strings, browser identifiers, event IDs, customer private values, ciphertext, HMACs, tokens or raw Graph payloads.

No external Meta API call is made while previewing or exporting reports.

## Ledger table

```text
fbm_report_exports
```

The ledger stores:

```text
report_type
format
status
safe filter snapshot
row count
local storage path
download token hash
requested user
generated/download timestamps
safe error text
```

CSV files are written to Laravel's local disk under:

```text
storage/app/fb-marketing/exports
```

## Permission

```text
fb_marketing_reports_view.read
fb_marketing_report_export_create.create
fb_marketing_report_export_download.read
```

## Manual Verification Checklist

```text
[ ] Apply all tenant migrations through FBM-20 before opening Reports.
[ ] Run php artisan route:list --path=fb-marketing/reports in a complete deployment checkout.
[ ] Grant fb_marketing_reports_view.read to one test role.
[ ] Confirm the FB MARKETING sidebar shows Reports for that role.
[ ] Remove fb_marketing_reports_view.read and confirm the page is forbidden and hidden from navigation.
[ ] Grant fb_marketing_report_export_create.create only to intended export users.
[ ] Grant fb_marketing_report_export_download.read only to intended export users.
[ ] Preview each supported report type and confirm rows match the source report page.
[ ] Export each supported report type as CSV and confirm the download opens.
[ ] Confirm fbm_report_exports records report type, filters, status, row count and generated timestamp.
[ ] Confirm exported CSV headers contain safe projected field names only.
[ ] Confirm raw provider IDs, URLs, query strings, browser identifiers, event IDs, customer private values and ciphertext are absent from generated CSV files.
[ ] Confirm empty-data tenants generate a CSV with headers and no crash.
```

## Notes and limitations

FBM-20 implements CSV exports only. XLSX, scheduled exports, email delivery and report packs remain deferred. Export generation is request-bound and uses local stored data only.
