# FBM-22 - Audience and Product-set Management

Completed in the packaged source on 2026-06-11.

## Goal

FBM-22 adds a guarded audience and product-set preparation surface before campaign draft governance and provider publishing exist.

## User-facing page

```text
GET /fb-marketing/audiences
POST /fb-marketing/audiences
POST /fb-marketing/audiences/sync/{connection}
PATCH /fb-marketing/audiences/{audience}/selection
PATCH /fb-marketing/audiences/product-sets/{productSet}/selection
```

The page includes:

```text
Audience type, status and selection filters
Local saved/custom/lookalike/local-segment audience planning
Read-only audience sync from selected ad accounts
Product-set selection using the existing FBM-11 product-set mirror
Consent basis, consent note and retention planning fields
Append-only selection audit history
Safe audience-sync history
```

## Local tables and columns

```text
fbm_audiences
fbm_audience_sync_runs
fbm_audience_selection_audits
fbm_product_sets.is_selected
fbm_product_sets.selection_status
fbm_product_sets.planned_use
fbm_product_sets.consent_note
fbm_product_sets.selected_by
fbm_product_sets.selected_at
```

## Permission

```text
fb_marketing_audiences_view.read
fb_marketing_audience_manage.create
fb_marketing_audience_selection_manage.update
fb_marketing_audience_sync_run.read
```

## Security and publishing boundary

This stage does not create, update or delete Meta audiences, upload customer lists, mutate product sets, assign audiences to campaigns, publish campaigns, change budgets or call any provider write endpoint.

Read-only sync uses the existing bounded Graph client against selected ad accounts. Provider audience IDs and product-set IDs stay internal. Browser projections exclude raw provider IDs, raw Graph URLs, query strings, request fingerprints, credentials, ciphertext and HMACs.

The FBM security gate also required one blocker repair outside the audience feature: `routes/web.php` now restores the fail-closed local maintenance-route guard instead of hardcoding unsafe maintenance routes on.

## Manual Verification Checklist

```text
[ ] Apply all tenant migrations through FBM-22 before opening Audiences.
[ ] Run php artisan route:list --path=fb-marketing/audiences in a complete deployment checkout.
[ ] Grant fb_marketing_audiences_view.read to one test role.
[ ] Confirm the FB MARKETING sidebar shows Audiences for that role.
[ ] Remove fb_marketing_audiences_view.read and confirm the page is forbidden and hidden from navigation.
[ ] Grant fb_marketing_audience_manage.create only to intended planning operators.
[ ] Grant fb_marketing_audience_selection_manage.update only to intended operators.
[ ] Grant fb_marketing_audience_sync_run.read only to trusted operators.
[ ] Create a local audience plan with consent notes and confirm no Meta write request occurs.
[ ] Select and deselect an audience and confirm append-only audit rows are created.
[ ] Select and deselect a product set and confirm the existing product-set mirror remains intact.
[ ] Run read-only sync only after an active connection and selected ad account are ready.
[ ] Confirm raw provider IDs, query strings, credentials, ciphertext and HMACs are absent from page HTML.
```

## Notes and limitations

This is a preparation and read-only mirror layer only. Campaign draft governance remains deferred to FBM-23, controlled publishing to FBM-24 and operational provider actions to later stages.
