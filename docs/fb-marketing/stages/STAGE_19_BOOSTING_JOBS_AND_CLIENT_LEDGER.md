# FBM-19 - Boosting Jobs and Client Ledger

Completed in the packaged source on 2026-06-11.

## Goal

FBM-19 adds tenant-local boosting job records and a client ledger so own-store advertising and agency/client boosting work remain distinguishable. It links local campaign/ad rows to a job for spend reporting, and tracks received payments, local costs, service fees and balances without mutating Meta campaigns.

## User-facing page

```text
GET /fb-marketing/boosting-jobs
POST /fb-marketing/boosting-jobs
POST /fb-marketing/boosting-jobs/campaigns
POST /fb-marketing/boosting-jobs/payments
POST /fb-marketing/boosting-jobs/costs
```

The page includes:

```text
Job count
Own-store and client boosting counts
Planned budget
Actual spend from linked stored Insights snapshots
Service fee
Local job costs
Received amount
Refund and balance
Campaign/ad-set/ad links
Date, mode, status and customer filters
```

## Ledger boundary

The report uses tenant-local tables only:

```text
fbm_boosting_jobs
fbm_boosting_job_campaigns
fbm_boosting_job_payments
fbm_boosting_job_cost_adjustments
fbm_insight_daily_snapshots
fbm_campaigns
fbm_ad_sets
fbm_ads
customers                  # optional local CRM/customer label source
```

No external Meta API call is made while rendering the page or saving job ledger entries. No campaign publish, budget update, operational action, webhook, provider mutation or queue job is added.

## Calculation contract

```text
Actual Spend = SUM stored ad-level Insights spend for linked campaign/ad-set/ad rows in the selected date range
Received Amount = SUM confirmed advance, client_payment and adjustment payment rows
Refund Amount = SUM confirmed refund rows
Local Job Costs = SUM approved job cost adjustment total_amount
Receivable Total = Actual Spend + Service Fee + Local Job Costs
Balance = Receivable Total - Received Amount + Refund Amount
```

Own-store and client-boosting modes are rendered separately but use the same local ledger formulas. Client names and customer labels are safe local CRM values only.

## Permission

```text
fb_marketing_boosting_jobs_view.read
fb_marketing_boosting_jobs_manage.create
fb_marketing_boosting_job_ledger_manage.create
```

The sidebar link and report route require the view permission. Job creation and campaign linking require job manage permission. Payment and cost entries require ledger manage permission.

## Manual Verification Checklist

Use this checklist after deploying FBM-19:

```text
[ ] Apply all tenant migrations through FBM-19 before opening the page.
[ ] Run php artisan route:list --path=fb-marketing/boosting-jobs in a complete deployment checkout.
[ ] Grant fb_marketing_boosting_jobs_view.read to one test role.
[ ] Confirm the FB MARKETING sidebar shows Boosting Jobs for that role.
[ ] Remove fb_marketing_boosting_jobs_view.read and confirm the page is forbidden and hidden from navigation.
[ ] Grant fb_marketing_boosting_jobs_manage.create only to trusted operators.
[ ] Grant fb_marketing_boosting_job_ledger_manage.create only to trusted finance operators.
[ ] Create an own-store job and confirm it appears in the ledger.
[ ] Create a client-boosting job with a customer or client name and confirm mode/client labels are safe.
[ ] Link a local campaign/ad-set/ad and confirm actual spend equals stored ad-level snapshot spend for the selected date range.
[ ] Add a confirmed advance or client payment and confirm received amount updates.
[ ] Add a refund and confirm balance increases by the refund amount.
[ ] Add an approved local cost and confirm local costs, receivable and balance update.
[ ] Add pending or void payment/cost rows directly in data setup and confirm they do not affect totals.
[ ] Confirm raw provider IDs, URLs, browser identifiers, event IDs, customer private values and ciphertext are not visible in page HTML.
[ ] Confirm schema-missing or empty-data tenants show warnings instead of a crash.
```

## Notes and limitations

This stage is a local operations and finance ledger only. It does not create campaigns, boost posts, publish ads, edit budgets, sync lead forms, send invoices or export reports. Reporting exports remain deferred to FBM-20, and controlled Meta operational actions remain deferred to later stages.
