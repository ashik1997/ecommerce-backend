# FBM-30 — Controlled Rules and Recommendations

## Scope

FBM-30 adds a local recommendation engine and approval ledger for overspend, low-performance, stock-risk and sync-health signals. Autonomous provider writes remain disabled by default and are not implemented in this stage.

## Implemented artifacts

```text
database/migrations/2026_06_12_000030_create_fbm_recommendation_rule_tables.php
app/Models/FbMarketing/FbmRecommendationRule.php
app/Models/FbMarketing/FbmRecommendation.php
app/Services/FbMarketing/FbmRecommendationRuleService.php
app/Http/Controllers/Backend/FbMarketing/FbMarketingRecommendationController.php
resources/views/backend/fb-marketing/recommendations.blade.php
```

## Routes

```text
GET  /fb-marketing/rules-recommendations
POST /fb-marketing/rules-recommendations/refresh
POST /fb-marketing/rules-recommendations/{uuid}/approve
POST /fb-marketing/rules-recommendations/{uuid}/dismiss
```

The page requires `fb_marketing_recommendations_view.read`. Refresh requires `fb_marketing_recommendation_refresh.update`; approve/dismiss requires `fb_marketing_recommendation_decide.update`. Mutating routes are throttled.

## Rule behavior

Default rules are seeded locally for:

```text
overspend
poor_performance
stock_risk
sync_failure
token_expiry
```

Refresh reads open FBM-27 alerts and creates deduplicated local recommendations. Recommendation approval records an operator decision only. Any real pause, budget change or safe edit must be created separately through the FBM-25 controlled operational action flow.

## Safety notes

```text
autonomous_execution_enabled=false
provider writes disabled by default
no Meta request while rendering, refreshing, approving or dismissing recommendations
recommendation decisions are tenant-local audit records
```

## Acceptance checks

```text
[x] Recommendation rules and recommendation ledgers exist.
[x] Refresh creates local recommendations from alert signals.
[x] Approve/dismiss actions are permissioned and throttled.
[x] Approval cannot bypass FBM-25 operational action audit.
[x] No provider write or autonomous execution path exists in FBM-30.
```
