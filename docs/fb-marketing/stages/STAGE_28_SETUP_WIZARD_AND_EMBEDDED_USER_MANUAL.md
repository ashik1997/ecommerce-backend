# FBM-28 — Setup Wizard and Embedded User Manual

## Scope

FBM-28 completes the operator onboarding surface so a new user can configure and operate FB MARKETING without code edits. It extends the Setup Wizard with later-stage readiness checks and adds database-driven BN/EN embedded manual content with safe built-in fallback sections.

## Implemented artifacts

```text
database/migrations/2026_06_12_000028_create_fbm_user_manual_sections_table.php
app/Models/FbMarketing/FbmUserManualSection.php
app/Services/FbMarketing/FbmUserManualContentService.php
app/Http/Controllers/Backend/FbMarketing/FbMarketingUserManualController.php
resources/views/backend/fb-marketing/setup-wizard.blade.php
resources/views/backend/fb-marketing/user-manual.blade.php
```

## Routes

```text
GET /fb-marketing/configuration/setup-wizard
GET /fb-marketing/user-manual
GET /fb-marketing/user-manual/en
GET /fb-marketing/user-manual/bn
```

All routes remain behind `fb_marketing_access.read` and the existing contextual setup/manual read permissions.

## Manual content

The manual reads active sections from `fbm_user_manual_sections` by locale and order. If the migration has not run, or no active content exists for a locale, the service returns built-in safe fallback content covering:

```text
credential guide
health checklist
campaign guide
reporting guide
lead ads and alerts
troubleshooting and FAQ
```

The table is intended for documentation text only. It does not store provider credentials, raw webhook payloads or provider identifiers.

## Setup Wizard

The wizard keeps the earlier operational readiness cards and now adds a compact advanced checklist for FBM-15 through FBM-28. Each row reports whether required local tables exist and names missing tables when a tenant migration is incomplete.

## Acceptance checks

```text
[x] BN and EN manual routes are available.
[x] Manual content can come from tenant-local database rows with fallback content.
[x] Setup Wizard includes later-stage readiness checks through FBM-28.
[x] Manual and setup routes remain permission-filtered.
[x] No provider request or provider write is performed by the onboarding surfaces.
```
