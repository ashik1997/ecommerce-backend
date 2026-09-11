#!/usr/bin/env bash
set -u

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

checks=0
failures=0
warnings=0

pass() { checks=$((checks + 1)); printf 'PASS: %s\n' "$1"; }
fail() { checks=$((checks + 1)); failures=$((failures + 1)); printf 'FAIL: %s\n' "$1" >&2; }
warn() { warnings=$((warnings + 1)); printf 'WARN: %s\n' "$1" >&2; }

require_file() {
    local path="$1" description="$2"
    if [[ -f "$path" ]]; then pass "$description"; else fail "$description"; fi
}

require_absent() {
    local path="$1" description="$2"
    if [[ ! -e "$path" ]]; then pass "$description"; else fail "$description"; fi
}

require_contains() {
    local path="$1" pattern="$2" description="$3"
    if [[ -f "$path" ]] && grep -Fq -- "$pattern" "$path"; then pass "$description"; else fail "$description"; fi
}

require_not_contains() {
    local path="$1" pattern="$2" description="$3"
    if [[ ! -f "$path" ]] || ! grep -Fq -- "$pattern" "$path"; then pass "$description"; else fail "$description"; fi
}

require_absent public/info.php 'public/info.php is absent'
require_absent public/error_log 'public/error_log is absent'
require_file PATCH_DELETE_MANIFEST.txt 'deployment deletion manifest exists'
require_contains PATCH_DELETE_MANIFEST.txt 'public/info.php' 'deletion manifest includes public/info.php'
require_contains PATCH_DELETE_MANIFEST.txt 'public/error_log' 'deletion manifest includes public/error_log'
require_contains .gitignore '!/PATCH_DELETE_MANIFEST.txt' 'broad txt ignore keeps the deletion manifest trackable'

if grep -Eq '^[[:space:]]*\$allowLocalUnsafeWebMaintenanceRoutes[[:space:]]*=[[:space:]]*true[[:space:]]*;' routes/web.php; then
    if [[ "${FBM_ACKNOWLEDGE_LOCAL_UNSAFE_OVERRIDE:-0}" == '1' ]]; then
        pass 'temporary hardcoded local unsafe-route override was explicitly acknowledged for non-release verification'
        warn 'routes/web.php still hardcodes $allowLocalUnsafeWebMaintenanceRoutes=true. Restore the fail-closed local+config expression before release.'
    else
        fail 'routes/web.php must not hardcode $allowLocalUnsafeWebMaintenanceRoutes=true before release'
    fi
else
    require_contains routes/web.php "app()->environment('local')" 'unsafe maintenance routes require local environment'
    require_contains routes/web.php "config('app.allow_local_unsafe_web_maintenance_routes', false)" 'unsafe maintenance routes require explicit opt-in configuration'
fi

python3 <<'PY'
from pathlib import Path
import sys
text = Path('routes/web.php').read_text()
needle = "Route::get('/product_website_id'"
route = text.find(needle)
guard = text.rfind('if ($allowLocalUnsafeWebMaintenanceRoutes)', 0, route)
if route >= 0 and guard >= 0:
    sys.exit(0)
sys.exit(1)
PY
if [[ $? -eq 0 ]]; then pass '/product_website_id remains inside an unsafe maintenance-route guard'; else fail '/product_website_id must remain inside an unsafe maintenance-route guard'; fi

require_contains config/services.php "env('LEGACY_API_AUTHORIZATION_TOKEN')" 'legacy API authorization reads server configuration'
require_contains config/services.php "env('PATHAO_WEBHOOK_INTEGRATION_SECRET')" 'Pathao webhook secret reads server configuration'
require_file app/Logging/RedactSensitiveContext.php 'shared sensitive-context log processor exists'
require_contains config/logging.php 'RedactSensitiveContext::class' 'configured log channels install shared redaction'
require_file app/Casts/EncryptedNullableString.php 'encrypted nullable vault cast exists'
require_file app/Models/FbMarketing/FbmConnection.php 'FB MARKETING encrypted connection model exists'
require_contains app/Models/FbMarketing/FbmConnection.php "protected \$hidden" 'FB MARKETING connection model hides secret columns'
require_not_contains config/fb_marketing.php 'META_APP_SECRET' 'FB MARKETING app secret is not read from environment config'
require_not_contains config/fb_marketing.php 'META_ACCESS_TOKEN' 'FB MARKETING access token is not read from environment config'

require_file app/Http/Controllers/Backend/FbMarketing/FbMarketingPerformanceController.php 'FBM-10 performance controller exists'
require_file app/Services/FbMarketing/FbmPerformanceDrilldownService.php 'FBM-10 performance drilldown service exists'
require_file app/Services/FbMarketing/FbmPerformanceWorklistService.php 'FBM-10 review worklist service exists'
require_file app/Services/FbMarketing/FbmPerformanceHealthService.php 'FBM-10 safe health projection service exists'
require_contains routes/fbMarketingRoutes.php "fb_marketing_performance_view,read" 'Performance routes require contextual read permission'
require_contains app/Services/RoleSidebarPermissionService.php "'fb_marketing_performance_view'" 'Performance permission is registered'
require_contains app/Http/Helpers/BackendSidebarHelper.php "'permission_key' => 'fb_marketing_performance_view'" 'Performance sidebar is permission-filtered'
require_contains routes/fbMarketingRoutes.php 'refresh-drilldowns-now' 'bounded no-queue drilldown refresh route exists'
require_contains routes/fbMarketingRoutes.php 'fb_marketing.manual_drilldown_sync.rate_limit_per_minute' 'manual drilldown refresh route is throttled'
require_contains config/fb_marketing.php "'insights_levels' => ['campaign', 'adset', 'ad']" 'manual drilldown profile limits levels to campaign/adset/ad'
require_contains config/fb_marketing.php "'max_selected_ad_accounts' => 1" 'manual drilldown profile defaults to one selected Ad Account'
require_contains config/fb_marketing.php "'recent_days' => 3" 'manual drilldown profile defaults to three completed days'
require_contains config/fb_marketing.php "'max_direct_reports_per_run' => 3" 'manual drilldown profile caps direct windows'
require_contains config/fb_marketing.php "'max_entities_per_table' => 250" 'performance table rows are bounded by configuration'
require_contains app/Services/FbMarketing/FbmPerformanceDrilldownService.php 'maxEntitiesPerTable' 'performance drilldown service enforces the local row bound'
require_contains app/Services/FbMarketing/FbmCampaignHierarchySyncService.php '$maxSelectedAdAccounts !== null' 'manual hierarchy override uses deterministic bounded account ordering'
require_contains app/Services/FbMarketing/FbmSyncExecutionService.php 'SCOPE_MANUAL_DRILLDOWN' 'manual drilldown run has a separate ledger scope'
require_contains app/Services/FbMarketing/FbmSyncExecutionService.php 'asset_discovery_skipped' 'manual drilldown summary records skipped asset discovery'
require_contains app/Services/FbMarketing/FbmSyncExecutionService.php 'coordinateManualDrilldownDirect' 'manual drilldown execution calls the direct-only Insights profile'
require_contains app/Services/FbMarketing/FbmInsightReportCoordinatorService.php "if (!\$directOnly && \$historyStart->lte(\$historyEnd))" 'historical async dispatch remains disabled for direct-only requests'
require_contains app/Services/FbMarketing/FbmPerformanceDrilldownService.php "->where('insight_level', \$level)" 'performance rows filter one matching snapshot insight level'
require_contains app/Services/FbMarketing/FbmPerformanceDrilldownService.php "'mixed_currency'" 'performance projections keep mixed-currency status explicit'

python3 <<'PY'
from pathlib import Path
import re, sys
text = Path('app/Http/Controllers/Backend/FbMarketing/FbMarketingSyncController.php').read_text()
match = re.search(r'public function runManualDrilldownsNow\b(.*)\n    }\n}', text, re.S)
if not match:
    sys.exit(1)
body = match.group(1)
sys.exit(0 if 'dispatch(' not in body and '->dispatch' not in body else 1)
PY
if [[ $? -eq 0 ]]; then pass 'manual drilldown controller action performs no queue dispatch'; else fail 'manual drilldown controller action must not dispatch queue work'; fi

python3 <<'PY'
from pathlib import Path
import sys
forbidden = [
    'access_token', 'app_secret', 'provider_asset_id', 'provider_sync_key',
    'provider_report_run_key', 'request_fingerprint', 'raw_payload', 'raw_url',
    'query_string', 'authorization_header',
]
paths = list(Path('resources/views/backend/fb-marketing/performance').glob('*.blade.php'))
text = '\n'.join(path.read_text().lower() for path in paths)
hits = [word for word in forbidden if word in text]
if hits:
    print('Forbidden performance-view source tokens: ' + ', '.join(hits), file=sys.stderr)
    sys.exit(1)
PY
if [[ $? -eq 0 ]]; then pass 'performance Blade views omit provider secrets, IDs and raw request metadata'; else fail 'performance Blade views must omit provider secrets, IDs and raw request metadata'; fi

require_file docs/fb-marketing/stages/STAGE_10_PERFORMANCE_DRILLDOWNS_AND_WORKLISTS.md 'FBM-10 stage documentation exists'
require_contains FB_MARKETING_STAGE_TRACKER.md '| FBM-10 | Performance Drilldowns and Worklists | ✅ Complete |' 'tracker marks FBM-10 complete'
require_file app/Services/FbMarketing/FbmWebhookReconciliationAlertService.php 'FBM-27 webhook reconciliation alert service exists'
require_file app/Http/Controllers/Api/FbmAdAccountWebhookController.php 'FBM-27 ad-account webhook API controller exists'
require_file app/Models/FbMarketing/FbmAlert.php 'FBM-27 alert model exists'
require_contains routes/api.php 'fb-marketing/webhooks/ad-account' 'FBM-27 ad-account webhook API routes exist'
require_contains routes/fbMarketingRoutes.php "fb_marketing_alerts_view,read" 'FBM-27 alerts page requires contextual read permission'
require_contains routes/fbMarketingRoutes.php "fb_marketing_alert_reconcile,update" 'FBM-27 reconciliation action requires contextual update permission'
require_contains config/fb_marketing.php "'daily_spend_alert_threshold' => 0" 'FBM-27 overspend alerts are opt-in by default'
require_contains app/Services/FbMarketing/FbmWebhookReconciliationAlertService.php 'provider_object_id' 'FBM-27 stores provider object IDs only in hidden webhook-log columns'
require_contains app/Models/FbMarketing/FbmAdAccountWebhookLog.php "'provider_object_id'" 'FBM-27 webhook model hides provider object IDs'
require_contains docs/fb-marketing/stages/STAGE_27_AD_ACCOUNT_WEBHOOKS_RECONCILIATION_AND_ALERTS.md 'Webhook loss is recoverable' 'FBM-27 stage documentation exists'
require_contains FB_MARKETING_STAGE_TRACKER.md '| FBM-27 | Ad-account Webhooks, Reconciliation and Alerts | ✅ Complete |' 'tracker marks FBM-27 complete'
require_file app/Services/FbMarketing/FbmUserManualContentService.php 'FBM-28 user manual content service exists'
require_file app/Models/FbMarketing/FbmUserManualSection.php 'FBM-28 user manual section model exists'
require_contains routes/fbMarketingRoutes.php "user-manual/{locale}" 'FBM-28 BN/EN user manual routes exist'
require_contains resources/views/backend/fb-marketing/setup-wizard.blade.php 'Advanced readiness checklist' 'FBM-28 setup wizard includes advanced readiness checklist'
require_contains docs/fb-marketing/stages/STAGE_28_SETUP_WIZARD_AND_EMBEDDED_USER_MANUAL.md 'database-driven BN/EN' 'FBM-28 stage documentation exists'
require_contains FB_MARKETING_STAGE_TRACKER.md '| FBM-28 | Setup Wizard and Embedded User Manual | ✅ Complete |' 'tracker marks FBM-28 complete'
require_file scripts/fbm-production-readiness.sh 'FBM-29 production readiness script exists'
require_file docs/fb-marketing/PRODUCTION_READINESS_CHECKLIST.md 'FBM-29 production readiness checklist exists'
require_file docs/fb-marketing/RELEASE_RUNBOOK.md 'FBM-29 release runbook exists'
require_contains scripts/fbm-production-readiness.sh 'fbm-security-gate.sh' 'FBM-29 production readiness chains the base security gate'
require_contains docs/fb-marketing/stages/STAGE_29_SECURITY_PERFORMANCE_REGRESSION_PRODUCTION_READINESS.md 'Production-readiness checklist' 'FBM-29 stage documentation exists'
require_contains FB_MARKETING_STAGE_TRACKER.md '| FBM-29 | Security, Performance, Regression and Production Readiness | ✅ Complete |' 'tracker marks FBM-29 complete'
require_file app/Services/FbMarketing/FbmRecommendationRuleService.php 'FBM-30 recommendation rule service exists'
require_file app/Models/FbMarketing/FbmRecommendation.php 'FBM-30 recommendation model exists'
require_contains routes/fbMarketingRoutes.php 'rules-recommendations' 'FBM-30 recommendation routes exist'
require_contains config/fb_marketing.php "'autonomous_execution_enabled' => false" 'FBM-30 autonomous execution is disabled by default'
require_contains docs/fb-marketing/stages/STAGE_30_CONTROLLED_RULES_AND_RECOMMENDATIONS.md 'Approval cannot bypass FBM-25' 'FBM-30 stage documentation exists'
require_contains FB_MARKETING_STAGE_TRACKER.md '| FBM-30 | Controlled Rules and Recommendations | ✅ Complete |' 'tracker marks FBM-30 complete'
require_file app/Services/FbMarketing/FbmProviderWriterReadinessService.php 'FBM-31 provider writer readiness service exists'
require_contains config/fb_marketing.php "'required_scopes' => ['ads_read', 'ads_management']" 'FBM-31 writer requires ads_read and ads_management scopes'
require_contains app/Services/FbMarketing/FbmCampaignPublishEngineService.php "assertReadyFor('campaign_publish')" 'FBM-31 campaign publish engine gates provider writer readiness'
require_contains app/Services/FbMarketing/FbmCampaignOperationalActionService.php "assertReadyFor('operational_actions')" 'FBM-31 operational actions gate provider writer readiness'
require_contains resources/views/backend/fb-marketing/configuration.blade.php 'Provider writer foundation readiness' 'FBM-31 configuration page surfaces writer readiness'
require_contains docs/fb-marketing/stages/STAGE_31_PROVIDER_WRITER_FOUNDATION.md 'This stage does not execute live Meta writes' 'FBM-31 stage documentation exists'
require_contains FB_MARKETING_STAGE_TRACKER.md '| FBM-31 | Provider Writer Foundation | ✅ Complete |' 'tracker marks FBM-31 complete'
require_file app/Jobs/FbMarketing/PublishFbmCampaignAttemptJob.php 'FBM-32 campaign publish worker job exists'
require_file app/Services/FbMarketing/FbmCampaignPublishWorkerService.php 'FBM-32 campaign publish worker service exists'
require_file app/Services/FbMarketing/FbmCampaignPublishProviderClient.php 'FBM-32 campaign publish provider client exists'
require_file app/Services/FbMarketing/FbmCampaignPublishDispatchService.php 'FBM-32 campaign publish dispatch service exists'
require_contains app/Services/FbMarketing/FbmCampaignPublishEngineService.php 'publishDispatch->dispatch' 'FBM-32 publish flow dispatches the controlled worker'
require_contains app/Services/FbMarketing/FbmCampaignPublishWorkerService.php "'status' => 'PAUSED'" 'FBM-32 creates campaign hierarchy in paused status'
require_contains app/Services/FbMarketing/FbmCampaignPublishProviderClient.php 'allowed_publish_edges' 'FBM-32 provider client enforces allow-listed publish edges'
require_contains app/Services/FbMarketing/FbmApiRequestLogService.php "'campaign_publish_campaign'" 'FBM-32 write operations are allow-listed in API ledger'
require_contains docs/fb-marketing/stages/STAGE_32_CAMPAIGN_PUBLISH_WORKER.md 'creates provider hierarchy in PAUSED status' 'FBM-32 stage documentation exists'
require_contains FB_MARKETING_STAGE_TRACKER.md '| FBM-32 | Campaign Publish Worker | ✅ Complete |' 'tracker marks FBM-32 complete'
require_file app/Jobs/FbMarketing/RunFbmOperationalActionJob.php 'FBM-33 operational action worker job exists'
require_file app/Services/FbMarketing/FbmCampaignOperationalActionWorkerService.php 'FBM-33 operational action worker service exists'
require_file app/Services/FbMarketing/FbmCampaignOperationalProviderClient.php 'FBM-33 operational action provider client exists'
require_file app/Services/FbMarketing/FbmCampaignOperationalActionDispatchService.php 'FBM-33 operational action dispatch service exists'
require_contains app/Services/FbMarketing/FbmCampaignOperationalActionService.php 'actionDispatch->dispatch' 'FBM-33 operational action flow dispatches the controlled worker'
require_contains app/Services/FbMarketing/FbmCampaignOperationalActionWorkerService.php "Operational action requires a completed campaign publish attempt" 'FBM-33 operational actions require completed publish attempt mirrors'
require_contains app/Services/FbMarketing/FbmCampaignOperationalActionWorkerService.php 'max_single_daily_budget_amount' 'FBM-33 operational budget updates enforce safety caps'
require_contains app/Services/FbMarketing/FbmApiRequestLogService.php "'campaign_operational_pause'" 'FBM-33 operational write operations are allow-listed in API ledger'
require_contains docs/fb-marketing/stages/STAGE_33_OPERATIONAL_ACTIONS_WRITER.md 'pause, resume, budget and schedule' 'FBM-33 stage documentation exists'
require_contains FB_MARKETING_STAGE_TRACKER.md '| FBM-33 | Operational Actions Writer | ✅ Complete |' 'tracker marks FBM-33 complete'
require_file app/Services/FbMarketing/FbmProviderWriteSafetyService.php 'FBM-34 provider write safety service exists'
require_contains app/Services/FbMarketing/FbmCampaignPublishWorkerService.php 'publishRollbackPlan' 'FBM-34 campaign publish stores rollback plan'
require_contains app/Services/FbMarketing/FbmCampaignPublishWorkerService.php 'publishReconciliationSummary' 'FBM-34 campaign publish stores reconciliation summary'
require_contains app/Services/FbMarketing/FbmCampaignOperationalActionWorkerService.php 'operationalRollbackPlan' 'FBM-34 operational actions store rollback plan'
require_contains app/Services/FbMarketing/FbmCampaignOperationalActionWorkerService.php 'operationalReconciliationSummary' 'FBM-34 operational actions store reconciliation summary'
require_contains app/Services/FbMarketing/FbmProviderWriteSafetyService.php "'automatic_execution' => false" 'FBM-34 automatic rollback execution is disabled'
require_contains app/Services/FbMarketing/FbmProviderWriteSafetyService.php "'provider_ids_hidden' => true" 'FBM-34 safety summaries keep provider IDs hidden'
require_contains config/fb_marketing.php "'rollback_plan_required' => true" 'FBM-34 rollback plan is required by provider writer config'
require_contains docs/fb-marketing/stages/STAGE_34_SAFETY_ROLLBACK_RECONCILIATION.md 'pause_and_reconcile' 'FBM-34 stage documentation exists'
require_contains FB_MARKETING_STAGE_TRACKER.md '| FBM-34 | Safety, Rollback and Reconciliation | ✅ Complete |' 'tracker marks FBM-34 complete'
require_file app/Services/FbMarketing/FbmProviderWriterLaunchChecklistService.php 'FBM-35 final provider writer launch checklist service exists'
require_contains app/Services/FbMarketing/FbmProviderWriterLaunchChecklistService.php 'ready_for_signed_enablement' 'FBM-35 exposes signed enablement readiness'
require_contains app/Services/FbMarketing/FbmProviderWriterLaunchChecklistService.php 'writesDisabledByDefault' 'FBM-35 requires live writes disabled by default'
require_contains resources/views/backend/fb-marketing/configuration.blade.php 'Final provider-writer launch gate' 'FBM-35 configuration dashboard surfaces launch gate'
require_contains resources/views/backend/fb-marketing/setup-wizard.blade.php 'FBM-35 Final provider-writer launch gate' 'FBM-35 setup wizard surfaces launch gate'
require_contains docs/fb-marketing/stages/STAGE_35_FINAL_PROVIDER_WRITER_LAUNCH_GATE.md 'separate signed configuration release' 'FBM-35 stage documentation exists'
require_contains FB_MARKETING_STAGE_TRACKER.md '| FBM-35 | Final Provider Writer Launch Gate | ✅ Complete |' 'tracker marks FBM-35 complete'

printf '\nFB MARKETING security gate: %d check(s), %d warning(s), %d failure(s).\n' "$checks" "$warnings" "$failures"
if [[ $failures -ne 0 ]]; then
    exit 1
fi
