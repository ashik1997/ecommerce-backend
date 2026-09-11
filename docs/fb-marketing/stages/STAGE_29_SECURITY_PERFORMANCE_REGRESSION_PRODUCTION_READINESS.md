# FBM-29 — Security, Performance, Regression and Production Readiness

## Scope

FBM-29 adds the production-readiness signoff layer for the completed FB MARKETING module. It focuses on repeatable source checks, tenant-isolation review, route permission audit, migration safety, release runbook, UAT checklist and rollback notes.

## Implemented artifacts

```text
scripts/fbm-production-readiness.sh
docs/fb-marketing/PRODUCTION_READINESS_CHECKLIST.md
docs/fb-marketing/RELEASE_RUNBOOK.md
```

The stage also restores the fail-closed local maintenance-route switch in `routes/web.php` before release verification.

## Verification policy

Run:

```bash
bash scripts/fbm-security-gate.sh
bash scripts/fbm-production-readiness.sh
```

The production readiness script chains the base security gate, checks that unsafe local maintenance routes are not hardcoded on, confirms provider writes remain disabled by default, checks release/runbook docs and scans FB MARKETING views for hidden secret, provider-ID, raw-payload and request-fingerprint fields.

## Signoff checklist

```text
[x] Tenant-isolation review documented.
[x] Permissions audit checklist documented.
[x] Migration safety and deletion-manifest notes documented.
[x] Secret scan and source gate are repeatable.
[x] Queue/cron runbook documented.
[x] Rate-limit and provider-write strategy documented.
[x] UAT checklist documented.
[x] Rollback notes documented.
```

## Completion gate

Production-readiness checklist is documented and the repeatable source-level readiness checks pass with zero failures.
