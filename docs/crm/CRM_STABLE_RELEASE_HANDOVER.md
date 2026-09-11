# CRM Stable Release Handover

Last updated: 2026-06-08  
Stable closure stage: Stage 32

## 1. Release Position

The CRM module is ready for controlled production UAT as a stable ERP-grade CRM baseline. The release preserves the existing customer, quotation, order, payment, due, ledger, accounting, return, refund, SMS, bulk-SMS, newsletter, support-ticket, contact-request, dashboard, sidebar, permission, and tenant database flows.

The release deliberately separates immutable campaign dispatch ledgers from real provider transport execution.

```text
Campaign planning channels: BulkSMSBD SMS and Email
Dispatch-ledger channels: BulkSMSBD SMS and Email
Real-send channels: BulkSMSBD SMS only
Enabled real provider adapter: BulkSMSBD only
Email real SMTP sending: disabled
```

## 2. Completed CRM Capabilities

### Customer operations

```text
Customer tags settings and assignment
Customer 360 profile
CRM tasks and follow-up worklist
Read-only CRM task calendar
CRM communications worklist and manual logging
Read-only CRM activity audit worklist
Read-only customer health and risk worklist
Read-only duplicate-customer review worklist
Duplicate-review stabilization and permission hardening
Read-only customer portfolio segmentation worklist
Saved customer segments with governance hardening
```

### Lead operations

```text
Lead management foundation
Lead integration polishing
Lead pipeline board
```

### Campaign governance and immutable dispatch ledgers

```text
Two active planning channels: BulkSMSBD SMS and Email
Read-only saved-segment audience preview
Draft lifecycle governance
Independent approval boundary
Approval-time recipient-set sealing
Stage 22 immutable recipient preparation
Stage 23 provider-neutral released-run ledger
Stage 24 single-consumer execution-batch claim ledger
Stage 25 manual provider-attempt ledger
Stage 26B non-sending safety gate and BulkSMSBD protocol hardening
Stage 27 tightly bounded real BulkSMSBD SMS execution
Stage 29 local SMTP-readiness inspection without password exposure
Stage 30 future SMTP protocol hardening without SMTP execution
Stage 31 non-sending Email ledger advancement
Stage 32 browser UI-gate repair and operator-clarity synchronization
```

## 3. Production Transport Boundaries

### BulkSMSBD SMS

BulkSMSBD SMS is the only enabled real CRM campaign transport. The execution path remains intentionally bounded:

```text
Provider key: bulksmsbd
Transport: HTTPS POST only
Maximum recipients per manually confirmed attempt: 5
Provider request shape: one recipient per HTTPS POST
Operator confirmation phrase: SEND SMS
Recipient exchange events: append only
Automatic retry: disabled
Browser duplicate resend: disabled
Queue execution: disabled
Scheduled execution: disabled
Webhook mutation: disabled
Polling mutation: disabled
```

The tenant-local `sms_gateways` row must provide one unambiguous active BulkSMSBD configuration for the campaign website scope or one tenant-wide fallback row:

```text
provider_name = bulksmsbd
status = 1
api_endpoint = absolute HTTPS URL without query string or fragment
api_key = configured server-side
sender_id = configured server-side
product_website_id = matching website id or NULL tenant-wide fallback
```

Never expose API keys, provider endpoints, raw requests, or raw provider responses in browser payloads or logs.

### Email

Email is available for planning, independent approval, local SMTP-readiness inspection, and immutable dispatch-ledger advancement only:

```text
Stage 22 preparation ledger: enabled
Stage 23 released-run ledger: enabled
Stage 24 execution-batch claim ledger: enabled
Stage 25 provider-attempt ledger: enabled
SMTP transport invocation: disabled
Email provider call: disabled
Email destination decryption for sending: disabled
Remote SMTP connectivity probe: disabled
Email typed SEND confirmation: absent
Email execute permission: absent
Automatic retry: disabled
Browser resend: disabled
```

The tenant-local `email_configures` row should contain one unambiguous active SMTP configuration for the campaign website scope or one tenant-wide fallback row so the read-only readiness panel can pass:

```text
status = 1
host = safe non-empty SMTP host
port = integer from 1 to 65535
email = safe non-empty SMTP username
password = configured server-side and hidden from readiness payloads
mail_from_email = valid sender email or SMTP username fallback
mail_from_name = safe sender name or application-name fallback
encryption = 0, 1, or 2 for none, TLS, or SSL
product_website_id = matching website id or NULL tenant-wide fallback
```

The dormant execution-only SMTP configuration resolver must remain disconnected from controllers, browser-facing readiness flows, and transport invocation until a separately approved future stage.

## 4. CRM Permission Checklist

Assign only the minimum role permissions needed for each operator responsibility. Keep responsibility separation between preparation, release, claim, attempt preparation, and real SMS execution.

```text
crm.customers.profile
crm.leads.list
crm.tasks.list
crm.tasks.create
crm.tasks.complete
crm.tasks.calendar
crm.communications.list
crm.activities.list
crm.customer-health.list
crm.customer-segments.list
crm.saved-customer-segments.list
crm.campaign-drafts.list
crm.campaign-drafts.approve
crm.campaign-drafts.prepare-dispatch
crm.campaign-drafts.release-dispatch
crm.campaign-drafts.claim-dispatch-execution
crm.campaign-drafts.prepare-dispatch-attempt
crm.campaign-drafts.execute-dispatch
crm.duplicate-customers.list
crm.settings.tags
```

`crm.campaign-drafts.execute-dispatch` authorizes the bounded BulkSMSBD SMS execute endpoint only. It does not enable Email transport execution.

## 5. Database and Tenant Migration Checklist

Stage 32 itself adds no migration. For a tenant that has not received the earlier CRM migrations, run the project-standard tenant-aware migration process against that tenant database before CRM UAT.

Required CRM migration files remain non-destructive and tenant-safe:

```text
2026_06_06_000001_add_crm_summary_fields_to_customers_table.php
2026_06_06_000002_create_crm_foundation_tables.php
2026_06_06_000003_add_unique_indexes_to_crm_customer_tags_table.php
2026_06_06_000004_create_crm_leads_table.php
2026_06_06_000005_create_crm_saved_customer_segments_table.php
2026_06_07_000006_create_crm_campaign_drafts_table.php
2026_06_07_000007_add_snapshot_integrity_to_crm_campaign_drafts_table.php
2026_06_07_000009_create_crm_campaign_dispatch_preparation_foundation.php
2026_06_07_000010_create_crm_campaign_dispatch_run_foundation.php
2026_06_07_000011_create_crm_campaign_dispatch_execution_foundation.php
2026_06_07_000012_create_crm_campaign_dispatch_attempt_foundation.php
2026_06_07_000013_create_crm_campaign_dispatch_recipient_attempt_event_foundation.php
```

Do not drop CRM tables or columns. Do not bypass tenant database switching. Do not run a migration command against production until the active connection has been verified for the intended tenant.

## 6. Stage 32 Patch Application

Extract the patch from the Laravel project root:

```bash
cd /path/to/laravel-project
unzip -o /path/to/crm_stage32_stable_release_closure_patch.zip -d .
php artisan optimize:clear
```

Stage 32 requires no migration command.

Recommended post-deployment static checks:

```bash
php -l app/Services/Crm/CrmCampaignDraftService.php
php -l app/Services/Crm/CrmCampaignDispatchAttemptService.php
php -l routes/crmRoutes.php
php artisan optimize:clear
```

## 7. Manual UAT Checklist

### Core regression

```text
Open customer list and create, edit, and view a customer
Open Customer 360 and verify profile tabs
Verify quotations, orders, payments, due screens, ledger, and accounting screens
Verify returns and refunds
Verify legacy SMS, bulk-SMS, newsletter, support-ticket, and contact-request flows
Verify CRM sidebar visibility for permitted and non-permitted roles
```

### CRM operations

```text
Create and assign customer tags
Create, edit, and complete CRM tasks
Verify calendar, communication history, activity audit, health, duplicate review, segmentation, and saved segments
Create and progress a lead through the pipeline board
```

### BulkSMSBD SMS campaign

```text
Create a BulkSMSBD SMS campaign draft from a saved segment
Submit for independent review and approve with a different authorized user
Freeze the immutable preparation snapshot
Release the provider-neutral run with a separate authorized user
Claim the execution batch with a separate authorized user
Prepare the bounded provider-attempt ledger with a separate authorized user
Verify the real-send button appears only when the SMS attempt is executable
Verify SweetAlert2 requires SEND SMS exactly
Use a safe test recipient set of at most five recipients
Confirm aggregate counters and append-only event history after the test send
Confirm no automatic retry is available after uncertain or failed outcomes
```

### Email campaign non-sending ledger

```text
Create an Email campaign draft with subject and body
Submit for independent review and approve with a different authorized user
Verify the campaign row exposes preparation, release, claim, and provider-readiness ledger actions when permissions and lifecycle checks pass
Freeze preparation, release the run, claim the execution batch, and prepare the Email provider-attempt ledger
Verify the modal clearly states that Email is non-sending
Verify no Email execute button appears
Verify no SMTP transport call, destination decryption for sending, queueing, retry, or browser resend occurs
Verify readiness payloads never expose SMTP password values
```

### Permission separation

```text
Verify users without each contextual permission cannot access the matching route
Verify campaign creator cannot approve the same campaign
Verify responsibility-separation guards reject invalid operator reuse where enforced
Verify execute permission does not make Email executable
```

## 8. Known Limitations and Deferred Enhancements

```text
Real CRM Email sending is not implemented
WhatsApp campaign delivery is not implemented
Additional SMS providers are not implemented
Queue workers are not implemented
Scheduled campaign execution is not implemented
Automatic retry and manual resend are not implemented
Open tracking, click tracking, bounce processing, and webhook mutation are not implemented
Legacy newsletter rewriting is not implemented
SMTP password encryption migration is not implemented
Tenant middleware redesign is not implemented
```

## 9. Operational Notes

```text
Use the latest full-project ZIP as the only source of truth for future stages
Apply one stage at a time
Back up tenant databases before production deployment
Run tenant-aware migrations only through the project's verified tenant switching process
Keep CRM provider credentials server-side
Review application logs after the first bounded SMS production UAT
Do not enable Email SMTP transport by wiring the dormant resolver manually
```
