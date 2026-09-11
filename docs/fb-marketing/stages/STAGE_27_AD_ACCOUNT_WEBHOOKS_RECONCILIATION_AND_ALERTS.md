# FBM-27 — Ad-account Webhooks, Reconciliation and Alerts

## Scope

FBM-27 adds an inbound ad-account webhook receiver, a local reconciliation run ledger and a durable alert ledger. The stage makes webhook loss recoverable by pairing callback evidence with existing bounded read-only sync and local detectors.

## Implemented artifacts

```text
database/migrations/2026_06_12_000027_create_fbm_webhook_reconciliation_alert_tables.php
app/Models/FbMarketing/FbmAdAccountWebhookLog.php
app/Models/FbMarketing/FbmAdAccountReconciliationRun.php
app/Models/FbMarketing/FbmAlert.php
app/Services/FbMarketing/FbmWebhookReconciliationAlertService.php
app/Http/Controllers/Api/FbmAdAccountWebhookController.php
app/Http/Controllers/Backend/FbMarketing/FbMarketingAlertController.php
resources/views/backend/fb-marketing/alerts.blade.php
```

## Webhook contract

Meta verification is served at:

```text
GET /api/fb-marketing/webhooks/ad-account
POST /api/fb-marketing/webhooks/ad-account
```

Verification compares the challenge token against encrypted tenant connection `webhook_verify_token_ciphertext`. Callback rows store safe shape metadata, change field, local connection/ad-account references when matched, and a hidden request fingerprint. Raw provider payloads, reusable tokens and provider object IDs are not rendered in browser-safe projections.

## Reconciliation and alerts

Trusted operators can run:

```text
POST /fb-marketing/alerts/reconcile
```

The route requires both `fb_marketing_alerts_view.read` and `fb_marketing_alert_reconcile.update` and is throttled by `fb_marketing.alerts.reconciliation_rate_limit_per_minute`.

Local detectors refresh alerts for:

```text
token_expiry
sync_failure
overspend
poor_performance
stock_risk
```

Overspend detection is opt-in by default because `fb_marketing.alerts.daily_spend_alert_threshold` is `0`. Poor-performance and stock-risk detection use stored Ads Insights snapshots and catalog product mappings only.

## Safety notes

No provider write endpoint is called in this stage. Webhook evidence, reconciliation runs and alerts are tenant-local. Provider object IDs and request fingerprints are hidden from browser output. Webhook loss is recoverable through existing bounded read-only sync and manual alert reconciliation.

## Acceptance checks

```text
[x] Ad-account webhook verify/callback routes are registered and throttled.
[x] Health & Alerts page is permission-filtered.
[x] Reconciliation action requires contextual update permission.
[x] Alert ledger supports token expiry, sync failure, overspend, poor-performance and stock-risk detectors.
[x] Provider writes remain absent from the FBM-27 flow.
```
