#!/usr/bin/env bash
set -u

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

checks=0
failures=0

pass() { checks=$((checks + 1)); printf 'PASS: %s\n' "$1"; }
fail() { checks=$((checks + 1)); failures=$((failures + 1)); printf 'FAIL: %s\n' "$1" >&2; }

require_contains() {
    local path="$1" pattern="$2" description="$3"
    if [[ -f "$path" ]] && grep -Fq -- "$pattern" "$path"; then pass "$description"; else fail "$description"; fi
}

require_not_contains() {
    local path="$1" pattern="$2" description="$3"
    if [[ ! -f "$path" ]] || ! grep -Fq -- "$pattern" "$path"; then pass "$description"; else fail "$description"; fi
}

if bash scripts/fbm-security-gate.sh >/tmp/fbm-security-gate.out 2>/tmp/fbm-security-gate.err; then
    pass 'base FB MARKETING security gate passes'
else
    fail 'base FB MARKETING security gate must pass before release'
    cat /tmp/fbm-security-gate.out
    cat /tmp/fbm-security-gate.err >&2
fi

require_not_contains routes/web.php '$allowLocalUnsafeWebMaintenanceRoutes = true;' 'unsafe local maintenance override is absent'
require_contains config/fb_marketing.php "'provider_writes_enabled' => false" 'provider writes remain disabled by default'
require_contains config/fb_marketing.php "'required_scopes' => ['ads_read', 'ads_management']" 'provider writer requires explicit ads_management scope'
require_contains app/Services/FbMarketing/FbmProviderWriterReadinessService.php "'ready_disabled'" 'provider writer has a ready-but-disabled production state'
require_contains app/Services/FbMarketing/FbmCampaignPublishWorkerService.php "'status' => 'PAUSED'" 'campaign publish worker creates paused provider objects'
require_contains app/Services/FbMarketing/FbmCampaignPublishWorkerService.php 'max_single_daily_budget_amount' 'campaign publish worker enforces configured budget cap'
require_contains config/fb_marketing.php "'default_targeting_countries' => []" 'campaign publish has no broad default targeting by default'
require_contains app/Services/FbMarketing/FbmCampaignOperationalActionWorkerService.php "Operational action requires a completed campaign publish attempt" 'operational writer requires completed local publish mirror'
require_contains app/Services/FbMarketing/FbmCampaignOperationalActionWorkerService.php 'max_single_daily_budget_amount' 'operational writer enforces configured budget cap'
require_contains app/Services/FbMarketing/FbmProviderWriteSafetyService.php 'pause_and_reconcile' 'provider write safety uses pause-and-reconcile rollback'
require_contains app/Services/FbMarketing/FbmProviderWriteSafetyService.php 'inverse_allow_listed_mutation' 'operational rollback is inverse allow-listed mutation only'
require_contains app/Services/FbMarketing/FbmProviderWriterLaunchChecklistService.php 'ready_for_signed_enablement' 'final provider-writer launch checklist is present'
require_contains resources/views/backend/fb-marketing/configuration.blade.php 'Final provider-writer launch gate' 'configuration dashboard surfaces final launch gate'
require_contains config/fb_marketing.php "'autonomous_execution_enabled' => false" 'recommendation autonomous execution remains disabled by default'
require_contains docs/fb-marketing/PRODUCTION_READINESS_CHECKLIST.md 'Application database' 'production checklist covers the application database'
require_contains docs/fb-marketing/PRODUCTION_READINESS_CHECKLIST.md 'Rollback notes' 'production checklist covers rollback'
require_contains docs/fb-marketing/RELEASE_RUNBOOK.md 'queue:work fb-marketing --queue=fb-marketing' 'release runbook includes dedicated queue worker command'

python3 <<'PY'
from pathlib import Path
import sys

forbidden = [
    'app_secret_ciphertext',
    'access_token_ciphertext',
    'capi_access_token_ciphertext',
    'webhook_verify_token_ciphertext',
    'request_fingerprint',
    'provider_asset_id',
    'provider_object_id',
    'raw_payload',
]

paths = list(Path('resources/views/backend/fb-marketing').glob('*.blade.php'))
hits = []
for path in paths:
    text = path.read_text().lower()
    for token in forbidden:
        if token in text:
            hits.append(f'{path}:{token}')

if hits:
    print('Forbidden FB MARKETING view tokens: ' + ', '.join(hits), file=sys.stderr)
    sys.exit(1)
PY
if [[ $? -eq 0 ]]; then pass 'FB MARKETING views omit hidden secret, provider-ID and fingerprint fields'; else fail 'FB MARKETING views must omit hidden secret, provider-ID and fingerprint fields'; fi

python3 <<'PY'
from pathlib import Path
import re
import sys

text = Path('routes/fbMarketingRoutes.php').read_text()
post_routes = re.findall(r"Route::post\('([^']+)'.*?->name\('([^']+)'\);", text, re.S)
missing = []
for path, name in post_routes:
    block_start = text.find(f"Route::post('{path}'")
    block_end = text.find("->name(", block_start)
    block = text[block_start:block_end]
    if 'throttle:' not in block and any(key in path for key in ['sync', 'test', 'publish', 'reconcile', 'exports', 'assets', 'audiences', 'campaign-drafts', 'profitability', 'boosting-jobs', 'tracking-attribution']):
        missing.append(name)

if missing:
    print('FB MARKETING write/action routes without throttle: ' + ', '.join(missing), file=sys.stderr)
    sys.exit(1)
PY
if [[ $? -eq 0 ]]; then pass 'FB MARKETING write/action routes have explicit throttles where required'; else fail 'FB MARKETING action routes must be throttled'; fi

printf '\nFB MARKETING production readiness: %d check(s), %d failure(s).\n' "$checks" "$failures"
if [[ $failures -ne 0 ]]; then
    exit 1
fi
