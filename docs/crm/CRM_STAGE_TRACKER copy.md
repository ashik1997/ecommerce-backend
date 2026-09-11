# CRM Upgrade Stage Tracker

Last updated: 2026-06-08

## Current Status

| Stage | Scope | Status |
| --- | --- | --- |
| Stage 0 | Existing CRM audit | ✅ Complete |
| Stage 0.5 | Stabilization and compatibility repair | ✅ Complete |
| Stage 1 | CRM database foundation | ✅ Complete |
| Stage 2 | CRM customer tags settings and assignment | ✅ Complete |
| Stage 3 | Customer 360 profile | ✅ Complete |
| Stage 4 | CRM tasks and follow-up worklist | ✅ Complete |
| Stage 5 | CRM communications worklist and manual logging | ✅ Complete |
| Stage 6 | Read-only CRM operational dashboard modernization | ✅ Complete |
| Stage 7 | Lead management foundation | ✅ Complete |
| Stage 8 | Lead integration polishing | ✅ Complete |
| Stage 9 | Lead pipeline board | ✅ Complete |
| Stage 10 | Read-only CRM task calendar | ✅ Complete |
| Stage 11 | Read-only CRM activity audit worklist | ✅ Complete |
| Stage 12 | Read-only CRM Customer Health & Risk Worklist | ✅ Complete |
| Stage 13 | Read-only CRM Duplicate Customer Review Worklist | ✅ Complete |
| Stage 14 | CRM Duplicate Review Stabilization and Permission Hardening | ✅ Complete |
| Stage 15 | Read-only CRM Customer Portfolio Segmentation Worklist | ✅ Complete |
| Stage 16 | CRM Saved Customer Segments Foundation | ✅ Complete |
| Stage 17 | CRM Saved Segment Stabilization and Governance Hardening | ✅ Complete |
| Stage 18 | CRM Campaign Planning Foundation — Read-only Audience Preview and Draft Governance | ✅ Complete |
| Stage 19 | CRM Campaign Draft Stabilization and Governance Hardening | ✅ Complete |
| Stage 20 | CRM Campaign Approval and Send-Readiness Governance Foundation | ✅ Complete |
| Stage 21 | CRM-Wide Stabilization, Regression Audit and Production Readiness | ✅ Complete |
| Stage 22 | CRM Campaign Dispatch Preparation Foundation — Immutable Recipient Snapshot and Auditable Execution Boundary | ✅ Complete |
| Stage 23 | CRM Campaign Dispatch Run Foundation — Controlled Release Ledger and Provider-Neutral Execution Boundary | ✅ Complete |
| Stage 24 | CRM Campaign Dispatch Execution Foundation — Controlled Run Claim, Provider-Neutral Execution Contract and Manual Batch Boundary | ✅ Complete |
| Stage 25 | CRM Campaign Provider Adapter Readiness and Manual Dispatch Attempt Ledger Foundation | ✅ Complete |
| Stage 26B | CRM Campaign Non-Sending Safety Gate and BulkSMSBD Protocol Hardening | ✅ Complete |
| Stage 27 | First Tightly Controlled Real SMS Dispatch Execution Path Through One Hardened BulkSMSBD Adapter | ✅ Complete |
| Stage 28 | CRM Campaign Channel Rationalization, Stage 27 Real-SMS Stabilization, and Explicit Email-Deferred UI Boundary | ✅ Complete |
| Stage 29 | CRM Campaign Email Dispatch Readiness Foundation and SMTP Safety Boundary | ✅ Complete |
| Stage 30 | CRM Campaign Email SMTP Protocol Hardening and Future Execution Boundary | ✅ Complete |
| Stage 31 | CRM Campaign Email Dispatch Ledger Advancement Foundation — Controlled Non-Sending Email Preparation, Release, Claim and Attempt Boundary | ✅ Complete |
| Stage 32 | CRM Stable Release Closure, Email Ledger UI Gate Repair, Operator-Clarity Sync and Production Handover | ✅ Complete |
| Stage 33 | CRM Bilingual User Manual and Sidebar Entry | ✅ Complete |
| Stage 34 | CRM Tutorial Manual UX Expansion with Separate Bangla and English Versions | ✅ Complete |

## Stage 5 Baseline Verification

The latest uploaded full project ZIP was reinspected as the single source of truth before Stage 5 implementation.

Verified existing foundations:

```text
crm_communications migration and table definition
App\Models\Crm\CrmCommunication
Customer::communications()
App\Services\Crm\CrmCommunicationService
Customer 360 communications lazy tab and endpoint
Stage 4 CRM task worklist, profile task tab, routes, and sidebar permissions
TenantDbMiddleware runtime tenant DB selection
Existing SMS, bulk-SMS, newsletter, contact-request, support-ticket, and notification flows
```

Baseline compatibility repair:

```text
CRM_STAGE_TRACKER.md was missing from the latest uploaded ZIP.
Stage 5 restores the tracker at project root and updates it through Stage 5.
```

The latest ZIP already contains a syntactically valid Stage 4 task Blade route array. The previously reported `resources/views/backend/crm/tasks/index.blade.php` parse-error area was rechecked through extracted Blade `@php` lint and passed.

## Stage 5 Completed Scope

Stage 5 adds an ERP-grade CRM communication-history layer without replacing production messaging systems.

Completed:

```text
Server-side CRM communications history worklist
Customer, channel, direction, status, source-module, and sent-date filters
Safe search and pagination
No-N+1 eager loading for customer and sender relations
Permission-aware Customer 360 links
Manual customer communication history logging
History-only UI notice: no SMS, email, WhatsApp, or provider request is sent
Fixed manual source_module = crm_manual
Fixed manual status = logged
Read-only AJAX communication details modal
Customer 360 communications-tab enhancement
Customer-prefilled manual-log link from Customer 360
Customer 360 read-only details modal
CRM customer activity event: crm_communication_logged
Root tracker restoration and update
```

Explicitly not implemented:

```text
Production SMS-provider hook integration
Bulk-SMS rewrite
Newsletter rewrite
Email delivery engine
WhatsApp provider integration
Provider webhooks
Queue workers
Campaigns
Templates
Automation workflows
Legacy backfill
Communication edit, archive, or delete actions
Lead management
Pipeline
Task calendar
Reports
```

## Files Added

```text
CRM_STAGE_TRACKER.md
app/Http/Controllers/Crm/CrmCommunicationController.php
app/Http/Requests/Crm/StoreCrmCommunicationRequest.php
resources/views/backend/crm/communications/index.blade.php
```

## Files Modified

```text
app/Http/Controllers/Crm/CrmCustomerProfileController.php
app/Http/Helpers/BackendSidebarHelper.php
app/Services/Crm/CrmCommunicationService.php
app/Services/Crm/Customer360ProfileService.php
resources/views/backend/crm/customers/profile/show.blade.php
routes/crmRoutes.php
```

## Database Changes

Stage 5 introduces no migration and no database-schema modification.

Reused existing `crm_communications` fields:

```text
product_website_id
customer_id
channel
direction
subject
message
status
provider
provider_reference
source_module
sent_by
sent_at
failed_at
failure_reason
metadata
created_at
updated_at
```

Confirmed existing table does not have `deleted_at` or a verified archive field. Communication history therefore remains immutable after creation. No edit, archive, delete, hard-delete, or destructive migration endpoint was added.

## Routes Added

```text
GET  /crm/communications
     crm.communications.index

GET  /crm/communications/data
     crm.communications.data

GET  /crm/communications/options/customers
     crm.communications.options.customers

POST /crm/communications
     crm.communications.store

GET  /crm/communications/{communication}
     crm.communications.show
```

Existing Customer 360 lazy route preserved:

```text
GET /crm/customers/{customer}/profile/communications
    crm.customers.profile.communications
```

No send-message, provider-webhook, edit, archive, or delete route was added.

## Permission Keys Exposed

New working sidebar permission key:

```text
crm.communications.list
```

Action flags reuse the current permission-service convention:

```text
crm.communications.list,read
crm.communications.list,create
```

Preserved keys:

```text
crm.settings.tags
crm.customers.profile
crm.tasks.list
crm.tasks.create
crm.tasks.complete
```

Reserved future key remains hidden:

```text
crm.tasks.calendar
```

## CRM Communications Worklist

Worklist capabilities:

```text
AJAX server-side DataTable
Pagination
Safe empty state for no records
Search by subject, summary, channel, direction, status, source module, provider, customer, and sender
Filter by customer
Filter by channel
Filter by direction
Filter by status
Filter by source module
Filter by sent or logged date range
Customer 360 profile link where authorized
Read-only details action
No sensitive raw metadata JSON in DataTable or details payload
Customer and sender eager loading
```

## Manual Communication Logging

Supported manual channels:

```text
phone
email
sms
whatsapp
meeting
other
```

Supported directions:

```text
inbound
outbound
```

Manual-write behavior:

```text
Required customer
Optional subject
Required message or interaction summary
Optional interaction timestamp
DB transaction
Customer row lock
sent_by = authenticated actor
sent_at = interaction timestamp or current timestamp
source_module = crm_manual
status = logged
provider = null
provider_reference = null
failed_at = null
failure_reason = null
metadata.manual_log = true
```

The manual flow never calls SMS helpers, mail queues, provider clients, credentials, or third-party APIs.

## Communication Details Behavior

Read-only AJAX details modal displays:

```text
Customer
Channel
Direction
Subject
Full message or interaction summary
Status
Source module
Logged by / sent by actor
Sent or logged date
Created date
Provider only when stored
Provider reference only when stored
Failure timestamp and reason only when stored and read-authorized
```

Raw metadata JSON is not exposed.

## Customer 360 Communication Integration

Existing lazy communications tab remains customer-scoped and lazy-loaded.

Enhanced tab behavior:

```text
Inbound and outbound rows
Channel
Direction
Status
Subject or message summary
Source module
Actor
Timestamp
Permission-aware read-only details action
Permission-aware Worklist link
Permission-aware Log communication link with current customer prefilled
```

The initial Customer 360 profile request is not overloaded.

## CRM Activity Logging

New activity type:

```text
crm_communication_logged
```

Customer-specific manual communication activity is recorded after the main transaction through the existing best-effort `App\Services\Crm\CrmActivityService` convention. Activity-log failure does not corrupt a successful communication-history write.

## Existing SMS and Newsletter Compatibility Findings

Inspection found no existing production SMS, bulk-SMS, newsletter, contact-request, support-ticket, or notification flow writing to `crm_communications`.

Existing SMS helper and controllers do not consistently provide a customer ID plus a durable provider-reference convention appropriate for safe audit integration. Stage 5 therefore does not attach production hooks and does not fabricate sent or delivered states.

Preserved unchanged:

```text
app/Http/Controllers/Customer/BulkSmsBdManagementController.php
app/Http/Controllers/Customer/SmsManagementController.php
app/Http/Controllers/SubscribedUsersController.php
app/Http/Controllers/ContactRequestontroller.php
app/Http/Controllers/SupportTicketController.php
app/Http/Controllers/NotificationController.php
app/Http/Helpers/Helpers.php
```

## Reused Existing Logic

Stage 5 deliberately reuses:

```text
app/Models/Crm/CrmCommunication.php
app/Services/Crm/CrmCommunicationService.php
app/Services/Crm/CrmActivityService.php
app/Http/Middleware/TenantDbMiddleware.php
app/Http/Middleware/CrmSidebarPermission.php
app/Services/RoleSidebarPermissionService.php
window.AppAxios
AJAX DataTables
Select2
SweetAlert2
```

Accounting source of truth remains untouched:

```text
app/Services/Customer/CustomerTransactionService.php
```

## Validation Results

Static checks completed in the uploaded partial source export:

```text
Targeted PHP files linted: 284
PHP syntax errors: 0
Added and modified PHP files: PASS
Extracted Blade @php blocks checked: 4
Blade @php syntax errors: 0
Inline Blade JavaScript blocks checked with node --check: 3
Inline JavaScript syntax errors: 0
```

Static Stage 5 route checks:

```text
crm.communications.index: 1
crm.communications.data: 1
crm.communications.options.customers: 1
crm.communications.store: 1
crm.communications.show: 1
```

Static URI checks:

```text
GET  /communications: 1
GET  /communications/data: 1
GET  /communications/options/customers: 1
POST /communications: 1
GET  /communications/{communication}: 1
```

Class and relation collision checks:

```text
CrmCommunicationController declarations: 1
StoreCrmCommunicationRequest declarations: 1
CrmCommunicationService declarations: 1
Customer::communications declarations: 1
Customer::tasks declarations: 1
Customer::activities declarations: 1
Customer::notes declarations: 1
```

Permission and sidebar checks:

```text
crm.communications.list reserved permission key: 1
crm.communications.list working sidebar link: 1
crm.tasks.calendar route declarations: 0
crm.tasks.calendar operational sidebar links: 0
crm.tasks.calendar reserved key retained: 1
```

Frontend checks:

```text
Shared window.AppAxios used for AJAX writes and details requests
Second Axios instance: none
axios.create(): none
SweetAlert2 feedback: present
Inline 422 validation errors: present
Loading state: present
Double-submit prevention: present
Server-side DataTable: present
Raw metadata rendering: none
```

## Migration Safety Review

Stage 5:

```text
New migration files: none
Modified migration files: none
DROP TABLE: none
DROP COLUMN: none
Destructive rename: none
Hard delete communication endpoint: none
forceDelete(): none
truncate(): none
```

Pre-existing migration timestamp collisions remain outside Stage 5 scope:

```text
2026_05_25_000001_create_product_order_refunds_table.php
2026_05_25_000001_upgrade_fund_transfer_tables.php

2026_06_05_000001_create_role_sidebar_permissions_table.php
2026_06_05_000001_create_affiliates_table.php
```

## Regression Review

Unchanged and preserved:

```text
Customer CRUD
Customer 360 profile tabs
CRM tags
CRM notes
CRM activities
CRM tasks
Legacy contact history
Legacy scheduled contacts
Customer payments
Opening balances
Orders
Quotations
Returns
Refunds
Customer ledger
Customer due reports
Accounting integration
SMS
Bulk SMS
Newsletter subscribers
Contact requests
Support tickets
Tenant DB credential switching
Sidebar compatibility aliases
```

File-level compare confirms no modifications to:

```text
app/Services/Customer/CustomerTransactionService.php
app/Http/Controllers/Customer/BulkSmsBdManagementController.php
app/Http/Controllers/Customer/SmsManagementController.php
app/Http/Controllers/SubscribedUsersController.php
app/Http/Controllers/ContactRequestontroller.php
app/Http/Controllers/SupportTicketController.php
app/Http/Controllers/Customer/CustomerContactHistoryController.php
app/Http/Controllers/Customer/CustomerNextContactDateController.php
app/Http/Middleware/TenantDbMiddleware.php
```

## Separate Security Note

Existing project routes outside Stage 5 scope still include maintenance-like public route indicators:

```text
/api/migrate-tenants
/append-columns
/update-all-tentants-db
/update-all-tentants-db/status/{jobId}
```

These were not modified. They should be handled as a separate reviewed hardening patch.

## Known Limitations

```text
Uploaded ZIP is a partial source export: artisan, composer.json, and package.json are absent.
Laravel boot, artisan route:list, migrate:status, and framework tests could not run in this export.
No production SMS or newsletter adapter integration exists in Stage 5.
No provider callback or delivery reconciliation exists in Stage 5.
Manual communication records are immutable because crm_communications has no verified archive or correction field.
No legacy communication backfill exists.
Pre-existing migration timestamp collisions require separate review.
Public maintenance-like routes require separate security hardening review.
```

## Next Command

Stage 6 is not started automatically.

```text
Status: Stage 5 ✅ Complete
Next Stage: ⏳ Awaiting `next`
```


## Stage 6 Baseline Verification

The latest uploaded full project ZIP was reinspected before implementation. Stage 5 CRM communication files, routes, sidebar links, manual-history semantics, Customer 360 lazy integration, task foundation, tenant middleware, and accounting source-of-truth separation were preserved.

Stage 6 implementation intentionally focuses on static source changes. Tenant runtime boot and tenant-database execution were not attempted because database credentials and active connection settings load dynamically per tenant domain.

## Stage 6 Completed Scope

Stage 6 modernizes the existing `/crm-home` page into an ERP-grade read-only operational dashboard without replacing existing CRM, customer, communication, scheduled-contact, payment, due, or accounting flows.

Completed:

```text
Thin CRM dashboard controller
Dedicated CRM dashboard read service
Existing GET /crm-home URI preserved
Existing crm.home route name preserved
Existing CRM dashboard sidebar link preserved
Read-only active-customer metric
Read-only recently-added-customer metric
Read-only open-task, overdue-task, and due-today metrics
Read-only upcoming customer follow-up metric
Read-only recent-communication and manual-log metrics
Lifecycle-stage summary
Credit-status summary
Open CRM task snapshot
Upcoming customer follow-up snapshot
Recent communication snapshot
Recent CRM activity snapshot
Recently-added customer snapshot
Legacy contact-history and scheduled-contact dashboard snapshot
Schema::hasTable() and Schema::hasColumn() guarded optional reads
Best-effort dashboard logging for skipped optional reads
Permission-aware Customer 360, task-worklist, and communication-history links
Server-rendered dashboard without a new CDN, Axios client, or write operation
```

Explicitly not implemented:

```text
Database migration
Database write endpoint
Lead management
Sales pipeline
Campaign management
SMS provider hook integration
Email or WhatsApp provider integration
Newsletter rewrite
Task calendar
Duplicate-customer merge
Credit enforcement
Dashboard AJAX endpoint
Chart.js CDN dependency
Accounting changes
Legacy flow removal
```

## Stage 6 Files Added

```text
CRM_STAGE_TRACKER.md
app/Http/Controllers/Crm/CrmDashboardController.php
app/Services/Crm/CrmDashboardService.php
```

## Stage 6 Files Modified

```text
resources/views/backend/crm-dashboard.blade.php
routes/dashboardRoutes.php
```

## Stage 6 Route Behavior

Preserved route contract:

```text
GET /crm-home
    crm.home
    App\Http\Controllers\Crm\CrmDashboardController@index
```

The route remains inside the existing `CheckUserType` and `DemoMode` middleware group. The new controller retains explicit `auth` middleware to preserve the authentication protection previously applied through `HomeController`.

## Stage 6 Sidebar and Permission Behavior

Existing sidebar entry remains unchanged:

```text
permission_key = crm.dashboard
url = /crm-home
```

Stage 6 adds no permission key. Dashboard quick links are displayed only when existing role-sidebar permissions allow access:

```text
crm.customers.list,read
crm.customers.profile,read
crm.tasks.list,read
crm.communications.list,read
```

Reserved future key remains hidden:

```text
crm.tasks.calendar
```

## Stage 6 Database Changes

Stage 6 introduces no migration and no schema modification.

```text
New tables: none
New columns: none
Dropped tables: none
Dropped columns: none
Destructive rename: none
Data backfill: none
```

Dashboard reads are tenant-safe and guarded. Missing optional CRM tables or columns do not break available dashboard sections.

## Stage 6 Validation Results

Static checks completed:

```text
Changed PHP files linted: 3
Changed PHP syntax errors: 0
Targeted CRM PHP lint sweep: 38 files
Targeted CRM PHP syntax errors: 0
Dashboard Blade @php extraction lint: PASS
Dashboard Blade directive balance: PASS
Dashboard metric-key coverage: PASS
GET /crm-home route occurrences: 1
crm.home route-name occurrences: 1
CRM dashboard sidebar occurrences: 1
Dashboard CDN Chart.js references: 0
Dashboard axios.create() references: 0
Dashboard AppAxios write calls: 0
New migration files: 0
```

## Stage 6 Regression Review

Unchanged and preserved:

```text
Customer CRUD
Customer 360 profile
CRM tags
CRM notes
CRM tasks and follow-up worklist
CRM communications worklist and manual logging
Legacy contact history
Legacy scheduled contacts
Customer payments
Opening balances
Orders
Quotations
Returns
Refunds
Customer ledger
Customer due reports
Accounting integration
SMS
Bulk SMS
Newsletter subscribers
Contact requests
Support tickets
Tenant DB credential switching
Sidebar compatibility aliases
```

Accounting source of truth remains untouched:

```text
app/Services/Customer/CustomerTransactionService.php
```

## Stage 6 Known Limitations

```text
Tenant runtime boot was intentionally not attempted because tenant DB configuration is domain-driven.
Dashboard metrics are operational snapshots and do not replace dedicated worklists or reports.
Legacy contact snapshot preserves the existing dashboard date/status semantics until tenant-side behavior verification approves a semantic correction.
Optional-table fallback logs skipped read sections but does not auto-run tenant migrations.
Pre-existing migration timestamp collisions remain outside Stage 6 scope.
Public maintenance-like routes identified in Stage 5 remain outside Stage 6 scope.
```

## Next Command

Stage 7 is not started automatically.

```text
Status: Stage 6 ✅ Complete
Next Stage: ⏳ Awaiting `next`
```

---

## Stage 7 Baseline Verification

The latest uploaded full project ZIP was reinspected as the single source of truth before Stage 7 implementation. Stage 6 dashboard files, `/crm-home` route behavior, CRM task and communication worklists, Customer 360 profile integration, tenant DB middleware, and accounting source-of-truth separation were preserved.

The tracker still described Stage 7 as the next approved stage, while the user command explicitly approved:

```text
Stage 7 Lead Management Foundation
```

Stage 7 therefore implements only a lead-management foundation and does not start Stage 8.

## Stage 7 Completed Scope

Completed:

```text
Tenant-safe crm_leads table foundation
CrmLead model with customer, assigned user, creator, and updater relations
Customer::leads() relation
Thin CRM lead controller
Dedicated CRM lead service
Form Requests for create/update and status changes
AJAX DataTable lead worklist
Lead filters for customer, assigned user, status, priority, source, and follow-up date range
Select2 customer and assigned-user lookups
Lead create and edit modal
Lead details modal
Lead status workflow: new, contacted, qualified, converted, lost
Conversion requires selecting an existing customer only
Soft archive endpoint for leads
CRM activity events for lead create, update, status change, and archive
Sidebar Lead Worklist entry
Role permission key crm.leads.list
Shared window.AppAxios only for AJAX writes
SweetAlert2 feedback and archive confirmation
Inline 422 validation errors
Loading state and double-submit prevention on write forms
```

Explicitly not implemented:

```text
Lead-to-customer auto creation
Lead pipeline kanban board
Deal/opportunity module
Quotation creation from lead
Order creation from lead
Payment, due, ledger, or accounting writes
SMS, bulk-SMS, newsletter, email, or WhatsApp provider integration
Lead import/export
Lead merge or duplicate matching
Lead scoring automation
Campaign attribution engine
Task calendar
Dashboard lead widgets
Hard delete flows
```

## Stage 7 Files Added

```text
app/Http/Controllers/Crm/CrmLeadController.php
app/Http/Requests/Crm/StoreCrmLeadRequest.php
app/Http/Requests/Crm/UpdateCrmLeadRequest.php
app/Http/Requests/Crm/UpdateCrmLeadStatusRequest.php
app/Models/Crm/CrmLead.php
app/Services/Crm/CrmLeadService.php
database/migrations/2026_06_06_000004_create_crm_leads_table.php
resources/views/backend/crm/leads/index.blade.php
```

## Stage 7 Files Modified

```text
CRM_STAGE_TRACKER.md
app/Http/Controllers/Customer/Models/Customer.php
app/Http/Helpers/BackendSidebarHelper.php
app/Services/RoleSidebarPermissionService.php
routes/crmRoutes.php
```

## Stage 7 Database Changes

New guarded tenant table:

```text
crm_leads
```

Columns:

```text
id
product_website_id
customer_id
assigned_user_id
name
company_name
phone
email
source
status
priority
estimated_value
score
requirement
lost_reason
next_follow_up_at
qualified_at
converted_at
created_by
updated_by
created_at
updated_at
deleted_at
```

Migration safety:

```text
Schema::hasTable('crm_leads') guard used
No destructive down migration
No table drop
No column drop
No existing table altered
Soft deletes only
```

## Stage 7 Routes Added

```text
GET  /crm/leads
     crm.leads.index

GET  /crm/leads/data
     crm.leads.data

GET  /crm/leads/options/customers
     crm.leads.options.customers

GET  /crm/leads/options/users
     crm.leads.options.users

POST /crm/leads
     crm.leads.store

GET  /crm/leads/{lead}
     crm.leads.show

POST /crm/leads/{lead}
     crm.leads.update

POST /crm/leads/{lead}/status
     crm.leads.status

POST /crm/leads/{lead}/archive
     crm.leads.archive
```

## Stage 7 Permission Keys Exposed

```text
crm.leads.list
```

Action usage:

```text
crm.leads.list,read
crm.leads.list,create
crm.leads.list,update
crm.leads.list,delete
```

Preserved existing CRM permission keys:

```text
crm.dashboard
crm.customers.profile
crm.tasks.list
crm.tasks.create
crm.tasks.complete
crm.communications.list
crm.settings.tags
```

Reserved hidden future key remains hidden:

```text
crm.tasks.calendar
```

## Stage 7 Validation Results

Static checks completed:

```text
Changed PHP files linted: 11
Changed PHP syntax errors: 0
Targeted CRM PHP lint sweep: completed separately during packaging
New migration timestamp collision check: no collision with existing CRM migration sequence
Lead route-name review: no duplicate crm.leads route names found
Lead URI review: /crm/leads routes are grouped after fixed /crm/leads/options routes to avoid route shadowing
Lead Blade AJAX review: window.AppAxios is used; axios.create() is not used
Lead write UX review: SweetAlert2, inline 422 errors, loading states, and double-submit prevention are present
```

## Stage 7 Regression Review

Unchanged and preserved:

```text
Customer CRUD
Customer 360 profile
CRM dashboard
CRM tags and tag assignment
CRM notes
CRM tasks and follow-up worklist
CRM communications worklist and manual logging
Legacy contact history
Legacy scheduled contacts
Customer payments
Opening balances
Orders
Quotations
Returns
Refunds
Customer ledger
Customer due reports
Accounting integration
SMS
Bulk SMS
Newsletter subscribers
Contact requests
Support tickets
Tenant DB credential switching
Sidebar compatibility aliases
```

Accounting source of truth remains untouched:

```text
app/Services/Customer/CustomerTransactionService.php
```

Sensitive flow files remain intentionally unmodified:

```text
app/Services/Customer/CustomerTransactionService.php
app/Http/Controllers/Customer/BulkSmsBdManagementController.php
app/Http/Controllers/Customer/SmsManagementController.php
app/Http/Controllers/SubscribedUsersController.php
app/Http/Controllers/ContactRequestontroller.php
app/Http/Controllers/SupportTicketController.php
app/Http/Middleware/TenantDbMiddleware.php
```

## Stage 7 Known Limitations

```text
Tenant runtime boot was intentionally not attempted because tenant DB configuration is domain-driven.
Lead conversion links a lead to an existing customer only; it does not create a customer automatically.
Lead management does not create quotations, orders, payments, due records, ledger entries, SMS, email, or campaign records.
Lead dashboard metrics are intentionally deferred.
Optional tenant migrations must be run before using /crm/leads on existing tenants.
Pre-existing migration timestamp collisions remain outside Stage 7 scope.
Public maintenance-like routes identified earlier remain outside Stage 7 scope.
```

## Next Command

Stage 8 is not started automatically.

```text
Status: Stage 7 ✅ Complete
Next Stage: ⏳ Awaiting `next`
```


---

## Stage 8 Baseline Verification

The latest uploaded full project ZIP was reinspected before Stage 8 implementation. The detailed Stage 7 completion section, lead controller, service, model, guarded migration, worklist Blade, routes, sidebar entry, permission entry, and `Customer::leads()` relation were verified as present.

The top tracker status table still described Stage 7 as awaiting `next` even though the detailed Stage 7 completion report was already present. Stage 8 corrects that tracker inconsistency.

Existing Stage 7 defect found and repaired before extending the module:

```text
Root cause:
App\Services\Crm\CrmLeadService::customerProfileUrl() generated route name
crm.customers.profile.show, but the registered Customer 360 route name is
crm.customers.profile.

Impact:
Lead DataTable payload generation could throw RouteNotFoundException for users
who have Customer 360 profile-read permission and view a lead linked to a customer.

Fix:
Use route('crm.customers.profile', ['customer' => $customer->id]).
```

Stage 8 implementation intentionally focuses on additive read integration and safe worklist-prefill behavior. Tenant runtime boot and tenant-database execution were not attempted because database credentials and active connection settings load dynamically per tenant domain.

## Stage 8 Completed Scope

Stage 8 polishes the existing lead-management foundation without introducing a new sales pipeline, campaign engine, or accounting behavior.

Completed:

```text
Customer 360 permission-aware Customer leads quick link
Customer 360 permission-aware Add lead quick link
Customer 360 lazy CRM Leads tab
Customer 360 paginated linked-lead read endpoint
Customer 360 lead summary rows for contact, assigned user, source, priority,
status, estimated value, requirement summary, and next follow-up
CRM dashboard guarded lead availability state
CRM dashboard open-lead metric
CRM dashboard overdue lead-follow-up metric
CRM dashboard qualified-lead metric
CRM dashboard converted-leads-in-30-days metric
CRM dashboard lead-status summary
CRM dashboard lead follow-up snapshot
CRM dashboard permission-aware Lead Worklist links
Lead worklist URL customer-filter prefill
Lead create modal URL customer prefill for ?create=1&customer_id={id}
Existing Stage 7 Customer 360 route-name defect repair
Tracker top-status correction for Stage 7
```

Explicitly not implemented:

```text
Database migration
Database schema modification
Lead pipeline kanban board
Deal or opportunity module
Lead-to-customer auto creation
Quotation creation from lead
Order creation from lead
Payment, due, ledger, or accounting writes
SMS, bulk-SMS, newsletter, email, or WhatsApp provider integration
Campaign management
Lead import or export
Lead merge or duplicate matching
Lead scoring automation
Dashboard write endpoint
Hard delete flow
```

## Stage 8 Files Added

```text
None
```

## Stage 8 Files Modified

```text
CRM_STAGE_TRACKER.md
app/Http/Controllers/Crm/CrmCustomerProfileController.php
app/Http/Controllers/Crm/CrmLeadController.php
app/Services/Crm/CrmDashboardService.php
app/Services/Crm/CrmLeadService.php
app/Services/Crm/Customer360ProfileService.php
resources/views/backend/crm-dashboard.blade.php
resources/views/backend/crm/customers/profile/show.blade.php
resources/views/backend/crm/leads/index.blade.php
routes/crmRoutes.php
```

## Stage 8 Database Changes

Stage 8 introduces no migration and no database-schema modification.

```text
New tables: none
New columns: none
Dropped tables: none
Dropped columns: none
Destructive rename: none
Data backfill: none
```

Stage 8 dashboard and Customer 360 lead reads reuse the existing guarded tenant table:

```text
crm_leads
```

Optional lead reads are guarded with `Schema::hasTable()` and relevant `Schema::hasColumn()` checks. The pre-existing Stage 7 lead migration remains unchanged and non-destructive.

## Stage 8 Route Added

```text
GET /crm/customers/{customer}/profile/leads
    crm.customers.profile.leads
```

Route safety:

```text
Numeric customer constraint retained
crm.customers.profile,read middleware required
crm.leads.list,read middleware required
No conflicting CRM route name introduced
No URI shadowing introduced
```

## Stage 8 Permission Behavior

No new permission key is introduced. Stage 8 reuses the existing lead permission key:

```text
crm.leads.list
```

Customer 360 and dashboard behavior:

```text
crm.leads.list,read   show lead links, lazy lead tab, and lead worklist links
crm.leads.list,create show Add lead links and allow ?create=1 modal opening
```

Preserved existing keys:

```text
crm.dashboard
crm.customers.profile
crm.tasks.list
crm.tasks.create
crm.tasks.complete
crm.communications.list
crm.settings.tags
```

## Stage 8 Validation Results

Static checks completed:

```text
Changed PHP files linted: 6
Changed PHP syntax errors: 0
Targeted CRM PHP lint sweep: 41 files
Targeted CRM PHP syntax errors: 0
Modified Blade files checked: 3
Blade directive balance: PASS
Blade @php extraction lint: PASS
New migration files: 0
Modified migration files: 0
New profile-leads route-name occurrences: 1
New profile-leads URI occurrences: 1
CRM terminal route-name duplicate review: none
Stale route('crm.customers.profile.show') calls: 0
Stage 7 guarded crm_leads migration still present: yes
Stage 7 migration destructive-drop references: 0
Modified Blade axios.create() references: 0
Modified Blade window.AppAxios references: present
CRM dashboard write-AJAX references: 0
```

## Stage 8 Route, Migration, Namespace, Permission, and Duplicate-Column Risk Review

```text
Route risk:
One read-only Customer 360 lazy endpoint added with numeric customer constraint.
The endpoint is distinct from /crm/leads/{lead}; no route shadowing is introduced.
Existing /crm/leads/options routes remain before /crm/leads/{lead}.

Migration risk:
No Stage 8 migration exists. The Stage 7 guarded crm_leads migration is unchanged.
No duplicate-column possibility is introduced by this patch.

Namespace risk:
No new class namespace is introduced. Existing CRM namespaces are reused.

Permission risk:
No new permission key is required. Existing crm.leads.list read/create actions are reused.
The lazy Customer 360 endpoint requires both profile-read and lead-read middleware.

Duplicate-column risk:
None. No schema alteration is included.
```

## Stage 8 Regression Review

Unchanged and preserved:

```text
Customer CRUD
Customer 360 existing tabs
CRM dashboard existing customer, task, communication, activity, and legacy snapshots
CRM tags and tag assignment
CRM notes
CRM tasks and follow-up worklist
CRM communications worklist and manual logging
CRM lead create, edit, status, conversion, and archive flows
Legacy contact history
Legacy scheduled contacts
Customer payments
Opening balances
Orders
Quotations
Returns
Refunds
Customer ledger
Customer due reports
Accounting integration
SMS
Bulk SMS
Newsletter subscribers
Contact requests
Support tickets
Tenant DB credential switching
Sidebar compatibility aliases
```

Accounting source of truth remains untouched:

```text
app/Services/Customer/CustomerTransactionService.php
```

Sensitive flow files remain intentionally unmodified:

```text
app/Services/Customer/CustomerTransactionService.php
app/Http/Controllers/Customer/BulkSmsBdManagementController.php
app/Http/Controllers/Customer/SmsManagementController.php
app/Http/Controllers/SubscribedUsersController.php
app/Http/Controllers/ContactRequestontroller.php
app/Http/Controllers/SupportTicketController.php
app/Http/Middleware/TenantDbMiddleware.php
```

## Stage 8 Known Limitations

```text
Tenant runtime boot was intentionally not attempted because tenant DB configuration is domain-driven.
Lead dashboard sections are operational snapshots, not dedicated reporting pages.
Customer 360 linked-lead rows are read-only; lead editing stays in the Lead Worklist.
Lead conversion still links to an existing customer only; it does not create customers automatically.
Lead management still does not create quotations, orders, payments, due records, ledger entries, SMS, email, or campaign records.
Optional tenant migrations must be run before using lead features on existing tenants.
Pre-existing migration timestamp collisions remain outside Stage 8 scope.
Public maintenance-like routes identified earlier remain outside Stage 8 scope.
```

## Next Command

Stage 9 is not started automatically.

```text
Status: Stage 8 ✅ Complete
Next Stage: ⏳ Awaiting `next`
```


# Stage 9 — Lead Pipeline Board

## Stage 9 Baseline Verification

The latest uploaded full project ZIP was reinspected as the single source of truth before Stage 9 implementation.

Verified existing Stage 8 foundations:

```text
crm.customers.profile.leads route exists
Customer 360 CRM Leads tab is lazy-loaded and permission-aware
CRM dashboard lead metrics and summaries are guarded and read-only
/crm/leads?customer_id={id} customer-filter prefill exists
/crm/leads?create=1&customer_id={id} customer-prefilled create flow exists
CrmLeadService uses crm.customers.profile, not crm.customers.profile.show
Stage 8 introduced no migration
```

Stage 9 implementation remains additive and reuses the existing lead status workflow. Tenant runtime boot and tenant-database execution were not attempted because active database credentials and connection settings load dynamically per tenant domain.

## Stage 9 Completed Scope

Stage 9 adds an ERP-grade CRM lead pipeline board without introducing new business tables or parallel write logic.

Completed:

```text
Permission-aware Lead Pipeline sidebar link
Permission-aware CRM dashboard Lead Pipeline quick link
Lead Worklist Pipeline Board quick link
Kanban-style operational board with columns:
- new
- contacted
- qualified
- converted
- lost

Read-only AJAX pipeline feed
Per-column record totals
Per-column visible-card limits for operational safety
Customer, assigned-user, priority, source, follow-up date, and text-search filters
Customer URL prefill support for /crm/leads/pipeline?customer_id={id}
Pipeline cards showing:
- lead name
- company
- customer
- assigned user
- source
- priority
- estimated value
- next follow-up

Action-based status movement
Existing lead status route reused for writes
Existing DB transaction and row lock reused for status updates
Existing conversion rule reused: converted leads link to an existing customer only
Lost transition strengthened: lost reason is required
Select2 remote options routed through window.AppAxios
Pipeline AJAX feed and status writes routed through window.AppAxios
SweetAlert2 status feedback
Inline 422 validation errors
Loading state and double-submit prevention
```

Explicitly not implemented:

```text
Database migration
Database schema modification
New table
New column
Pipeline-specific write endpoint
Drag-and-drop dependency
Opportunity or deal module
Quotation creation from lead
Order creation from lead
Payment, due, ledger, or accounting write
Customer auto-creation during conversion
SMS, bulk-SMS, newsletter, email, or WhatsApp integration
Campaign engine
Workflow automation
Import or export
Hard delete
```

## Stage 9 Files Added

```text
app/Http/Requests/Crm/CrmLeadPipelineRequest.php
resources/views/backend/crm/leads/pipeline.blade.php
```

## Stage 9 Files Modified

```text
CRM_STAGE_TRACKER.md
app/Http/Controllers/Crm/CrmLeadController.php
app/Http/Helpers/BackendSidebarHelper.php
app/Http/Requests/Crm/UpdateCrmLeadStatusRequest.php
app/Services/Crm/CrmLeadService.php
resources/views/backend/crm-dashboard.blade.php
resources/views/backend/crm/leads/index.blade.php
routes/crmRoutes.php
```

## Stage 9 Database Changes

Stage 9 introduces no migration and no database-schema modification.

```text
New tables: none
New columns: none
Dropped tables: none
Dropped columns: none
Destructive rename: none
Data backfill: none
```

The pipeline board reads the existing tenant CRM table only:

```text
crm_leads
```

Status movements reuse the existing transactional `CrmLeadService::updateStatus()` method and the existing lead-status endpoint.

## Stage 9 Routes Added

```text
GET /crm/leads/pipeline
    crm.leads.pipeline

GET /crm/leads/pipeline/data
    crm.leads.pipeline.data
```

Route safety:

```text
Both endpoints require crm.leads.list,read middleware
Both routes are registered before /crm/leads/{lead}
Existing /crm/leads/{lead} numeric constraint remains present
No pipeline-specific POST, PUT, PATCH, or DELETE route added
Existing /crm/leads/{lead}/status middleware remains crm.leads.list,update
No terminal CRM route-name duplicate introduced
```

## Stage 9 Permission Behavior

No new permission key is introduced. Stage 9 reuses:

```text
crm.leads.list,read   view pipeline page and AJAX feed
crm.leads.list,update move a lead between statuses
crm.customers.profile,read show Customer 360 profile links on cards
```

Pipeline read users without lead-update permission receive a read-only board without Move actions.

## Stage 9 Validation Results

Static checks completed:

```text
Changed PHP files linted: 6
Changed PHP syntax errors: 0
CRM-targeted PHP lint sweep: 45 files
CRM-targeted PHP syntax errors: 0
Modified/new Blade files checked: 3
Blade directive balance: PASS
Pipeline inline JavaScript parse: PASS
New migration files: 0
Modified migration files: 0
Migration directory diff: 0
CRM terminal route-name duplicates: 0
crm.leads.pipeline terminal route-name occurrences: 1
crm.leads.pipeline.data terminal route-name occurrences: 1
Pipeline-specific POST route declarations: 0
Stale route('crm.customers.profile.show') calls: 0
Pipeline raw axios references: 0
Pipeline window.AppAxios references: present
```

## Stage 9 Route, Migration, Namespace, Permission, and Duplicate-Column Risk Review

```text
Route risk:
Two read-only pipeline routes were added before the numeric lead-detail route.
The existing lead-status write route is reused and remains permission-protected.
No URI shadowing or terminal route-name duplicate was detected.

Migration risk:
No Stage 9 migration exists. The database migration directory is unchanged.

Namespace risk:
One Form Request class was added under the existing App\Http\Requests\Crm namespace.
Existing controller and service namespaces are reused.

Permission risk:
No new permission key is required. Existing crm.leads.list read/update actions are reused.
Customer profile links remain gated by crm.customers.profile,read.

Duplicate-column risk:
None. No schema alteration is included.
```

## Stage 9 Regression Review

Unchanged and preserved:

```text
Customer CRUD
Customer 360 profile and existing lazy tabs
CRM tags and tag assignment
CRM notes
CRM tasks and follow-up worklist
CRM communications worklist and manual logging
CRM lead create, edit, details, conversion, and soft archive flows
CRM dashboard existing guarded sections
Legacy contact history
Legacy scheduled contacts
Customer payments
Opening balances
Orders
Quotations
Returns
Refunds
Customer ledger
Customer due reports
Accounting integration
SMS
Bulk SMS
Newsletter subscribers
Contact requests
Support tickets
Tenant DB credential switching
Sidebar compatibility aliases
```

Sensitive flow files verified unchanged:

```text
app/Services/Customer/CustomerTransactionService.php
app/Http/Controllers/Customer/BulkSmsBdManagementController.php
app/Http/Controllers/Customer/SmsManagementController.php
app/Http/Controllers/SubscribedUsersController.php
app/Http/Controllers/ContactRequestontroller.php
app/Http/Controllers/SupportTicketController.php
app/Http/Middleware/TenantDbMiddleware.php
```

## Stage 9 Known Limitations

```text
Tenant runtime boot was intentionally not attempted because tenant DB configuration is domain-driven.
The pipeline is an operational board, not a dedicated reporting page.
The board loads at most 100 visible cards per status; narrower filters should be used for larger datasets.
Status movement is action-based rather than drag-and-drop to reduce UI dependency and accidental-write risk.
Lead conversion still links to an existing customer only; it does not auto-create a customer.
Lead management still does not create quotations, orders, payments, due records, ledger entries, accounting records, SMS, email, or campaign records.
Optional tenant migrations must be run before using CRM lead features on existing tenants.
Pre-existing migration timestamp collisions remain outside Stage 9 scope.
Public maintenance-like routes identified earlier remain outside Stage 9 scope.
```

## Stage 10 Baseline Verification

The latest uploaded full project ZIP was reinspected as the single source of truth before Stage 10 implementation.

Verified existing foundations:

```text
Stage 9 Lead Pipeline Board routes, service feed, Blade UI, permission behavior, and sidebar link
crm_tasks migration and existing task worklist
CrmTaskController existing CRUD, completion, and archive endpoints
CrmTaskService existing transactional task writes
crm.tasks.calendar reserved permission key
Customer 360 task lazy tab
CRM dashboard task metrics and quick links
TenantDbMiddleware dynamic tenant DB switching
Existing customer, quotation, order, payment, due, ledger, accounting, return, refund, SMS, bulk-SMS, newsletter, support-ticket, and contact-request flows
```

## Stage 10 Completed Scope

Stage 10 adds a permission-aware read-only CRM task calendar without introducing schema changes or parallel task-write logic.

Completed:

```text
Permission-aware Task Calendar sidebar link
Permission-aware CRM dashboard Task Calendar quick link
Permission-aware Task Worklist Task Calendar quick link
Permission-aware Customer 360 task-section Calendar link with customer URL prefill

GET-only CRM task calendar page
GET-only AJAX calendar event feed
GET-only calendar-specific customer Select2 options feed
GET-only calendar-specific assigned-user Select2 options feed

Month-grid task calendar with previous, today, next, and refresh controls
Visible date-window filtering based on the rendered calendar grid
Customer filter
Assigned-user filter
Status filter
Priority filter
Customer URL prefill support for /crm/tasks/calendar?customer_id={id}

Read-only task cards showing:
- title
- due time
- customer
- assigned user
- priority
- status
- overdue state

Customer 360 links gated by crm.customers.profile,read
Task Worklist card links gated by crm.tasks.list,read
Lead Worklist quick link gated by crm.leads.list,read
Select2 remote options routed through window.AppAxios
Calendar AJAX feed routed through window.AppAxios
Inline 422 validation errors
Loading state
Safe empty state
500-card operational result limit with visible warning
93-day maximum requested date window
Refresh double-click prevention through loading-state button disabling
```

Explicitly not implemented:

```text
Database migration
Database schema modification
New table
New column
Calendar-specific POST, PUT, PATCH, or DELETE endpoint
Drag-and-drop task rescheduling
Task write from the calendar
Legacy scheduled-contact rewrite
Opportunity or deal module
Quotation creation from lead
Order creation from lead
Payment, due, ledger, or accounting write
SMS, bulk-SMS, newsletter, email, or WhatsApp integration
Campaign engine
Workflow automation
Import or export
Hard delete
```

## Stage 10 Files Added

```text
app/Http/Requests/Crm/CrmTaskCalendarRequest.php
resources/views/backend/crm/tasks/calendar.blade.php
```

## Stage 10 Files Modified

```text
CRM_STAGE_TRACKER.md
app/Http/Controllers/Crm/CrmCustomerProfileController.php
app/Http/Controllers/Crm/CrmTaskController.php
app/Http/Helpers/BackendSidebarHelper.php
app/Services/Crm/CrmDashboardService.php
app/Services/Crm/CrmTaskService.php
resources/views/backend/crm-dashboard.blade.php
resources/views/backend/crm/customers/profile/show.blade.php
resources/views/backend/crm/tasks/index.blade.php
routes/crmRoutes.php
```

## Stage 10 Database Changes

Stage 10 introduces no migration and no database-schema modification.

```text
New tables: none
New columns: none
Dropped tables: none
Dropped columns: none
Destructive rename: none
Data backfill: none
```

The calendar reads the existing tenant CRM table only:

```text
crm_tasks
```

Existing task writes remain in the Stage 4 transactional worklist service methods and endpoints.

## Stage 10 Routes Added

```text
GET /crm/tasks/calendar
    crm.tasks.calendar

GET /crm/tasks/calendar/data
    crm.tasks.calendar.data

GET /crm/tasks/calendar/options/customers
    crm.tasks.calendar.options.customers

GET /crm/tasks/calendar/options/users
    crm.tasks.calendar.options.users
```

Route safety:

```text
All four endpoints require crm.tasks.calendar,read middleware
All four endpoints are GET-only
No calendar-specific POST, PUT, PATCH, or DELETE route added
Existing task write routes remain unchanged
No terminal CRM route-name duplicate introduced
```

## Stage 10 Permission Behavior

The previously reserved permission key is now operational:

```text
crm.tasks.calendar,read view calendar page, AJAX feed, and calendar Select2 feeds
```

Preserved permission behavior:

```text
crm.tasks.list,read show Task Worklist links
crm.leads.list,read show Lead Worklist quick link
crm.customers.profile,read show Customer 360 profile links on calendar cards
```

A calendar-only user can open and filter the read-only calendar without receiving task-write actions.

## Stage 10 Validation Results

Static checks completed:

```text
Changed PHP files linted: 7
Changed PHP syntax errors: 0
CRM-targeted PHP lint sweep: 38 files
CRM-targeted PHP syntax errors: 0
Modified/new Blade files checked: 4
Blade directive balance: PASS
Calendar Blade @php lint: PASS
Calendar inline JavaScript parse: PASS
New migration files: 0
Modified migration files: 0
Migration directory diff: 0
CRM terminal route-name declarations: 106
CRM terminal route-name duplicates: 0
crm.tasks.calendar terminal route-name occurrences: 1
crm.tasks.calendar.data terminal route-name occurrences: 1
crm.tasks.calendar.options.customers terminal route-name occurrences: 1
crm.tasks.calendar.options.users terminal route-name occurrences: 1
Calendar-specific mutating routes: 0
Calendar feed mutation indicators: 0
Stale route('crm.customers.profile.show') calls: 0
Calendar raw axios client references: 0
Calendar window.AppAxios references: present
```

## Stage 10 Route, Migration, Namespace, Permission, and Duplicate-Column Risk Review

```text
Route risk:
Four GET-only calendar routes were added under /crm/tasks/calendar.
The existing Stage 4 task-write endpoints remain unchanged.
No calendar-specific mutating endpoint or terminal route-name duplicate was detected.

Migration risk:
No Stage 10 migration exists. The database migration directory is unchanged.

Namespace risk:
One Form Request class was added under the existing App\Http\Requests\Crm namespace.
Existing controller and service namespaces are reused.

Permission risk:
The reserved crm.tasks.calendar key is now exposed through an operational sidebar URL.
Calendar page, data feed, and calendar-specific Select2 feeds require crm.tasks.calendar,read.
Customer profile links, Task Worklist links, and Lead Worklist links remain independently gated.

Duplicate-column risk:
None. No schema alteration is included.
```

## Stage 10 Regression Review

Unchanged and preserved:

```text
Customer CRUD
Customer 360 profile and existing lazy tabs
CRM tags and tag assignment
CRM notes
CRM task creation, editing, completion, and soft archive flows
CRM task worklist
CRM communications worklist and manual logging
CRM lead worklist, pipeline, details, conversion, and soft archive flows
CRM dashboard existing guarded sections
Legacy contact history
Legacy scheduled contacts
Customer payments
Opening balances
Orders
Quotations
Returns
Refunds
Customer ledger
Customer due reports
Accounting integration
SMS
Bulk SMS
Newsletter subscribers
Contact requests
Support tickets
Tenant DB credential switching
Sidebar compatibility aliases
```

Sensitive flow files verified unchanged:

```text
app/Services/Customer/CustomerTransactionService.php
app/Http/Controllers/Customer/BulkSmsBdManagementController.php
app/Http/Controllers/Customer/SmsManagementController.php
app/Http/Controllers/SubscribedUsersController.php
app/Http/Controllers/ContactRequestontroller.php
app/Http/Controllers/SupportTicketController.php
app/Http/Middleware/TenantDbMiddleware.php
```

## Stage 10 Known Limitations

```text
Tenant runtime boot was intentionally not attempted because tenant DB configuration is domain-driven.
The ZIP does not include artisan or vendor/autoload.php, so php artisan route:list and browser-level checks were not run.
The calendar is a read-only operational month grid, not a task-write surface or reporting suite.
Tasks without due_at are intentionally excluded from calendar cards and remain visible in the Task Worklist.
The calendar returns at most 500 matching due-date cards; narrower filters should be used for larger operational sets.
The requested calendar date window is limited to 93 days.
Task edits, completion, and soft archive remain available only through existing permitted task flows.
Optional tenant migrations must be run before using CRM foundations on tenants that do not yet have crm_tasks.
Pre-existing migration timestamp collisions remain outside Stage 10 scope.
Public maintenance-like routes identified earlier remain outside Stage 10 scope.
```

## Next Command

Stage 11 is not started automatically.

```text
Status: Stage 10 ✅ Complete
Next Stage: ⏳ Awaiting `next`
```

## Stage 11 Completed Scope

Stage 11 adds a read-only ERP-grade CRM activity audit worklist on top of the existing `crm_activities` foundation. It does not add activity writes, database schema changes, backfills, or replacements for established customer, quotation, order, payment, due, ledger, accounting, return, refund, messaging, support-ticket, dashboard, task, lead, sidebar, permission, or tenant-switching flows.

Completed:

```text
Global AJAX server-side CRM activity audit worklist
Read-only details modal
Customer filter with remote Select2 lookup
Performed-by user filter with remote Select2 lookup
Activity-type filter
Source-module filter
Occurred-date range filters
Safe global search and pagination
Customer-prefilled URL support: /crm/activities?customer_id={id}
Permission-aware Customer 360 links
Customer 360 audit-worklist quick link with customer prefill
CRM dashboard Activity History quick links
CRM sidebar Activity History link
No-N+1 eager loading for customer and performer relations
Shared window.AppAxios usage for worklist loading, Select2 lookups, and details loading
Read-only loading state and duplicate-details-request prevention
Safe empty states
Raw metadata JSON intentionally hidden from the UI and payload
```

Explicitly not implemented:

```text
Database migration
New table or column
Activity create
Activity edit
Activity archive
Activity delete or hard delete
Activity backfill
Metadata JSON exposure
Export
Campaign engine
Workflow automation
SMS or newsletter integration
Quotation creation
Order creation
Payment, due, ledger, or accounting write
Tenant middleware modification
```

## Stage 11 Files Added

```text
app/Http/Controllers/Crm/CrmActivityController.php
app/Http/Requests/Crm/CrmActivityWorklistRequest.php
app/Services/Crm/CrmActivityWorklistService.php
resources/views/backend/crm/activities/index.blade.php
```

## Stage 11 Files Modified

```text
CRM_STAGE_TRACKER.md
app/Http/Controllers/Crm/CrmCustomerProfileController.php
app/Http/Helpers/BackendSidebarHelper.php
app/Services/Crm/CrmDashboardService.php
app/Services/RoleSidebarPermissionService.php
resources/views/backend/crm-dashboard.blade.php
resources/views/backend/crm/customers/profile/show.blade.php
routes/crmRoutes.php
```

## Stage 11 Database Changes

Stage 11 introduces no migration and no database-schema modification.

Reused existing indexed `crm_activities` fields:

```text
id
product_website_id
customer_id
activity_type
subject
description
source_module
source_id
metadata
performed_by
occurred_at
created_at
updated_at
```

The worklist is immutable. It reads existing CRM audit records only and intentionally does not expose raw `metadata` JSON.

## Stage 11 Routes Added

```text
GET /crm/activities
    crm.activities.index

GET /crm/activities/data
    crm.activities.data

GET /crm/activities/options/customers
    crm.activities.options.customers

GET /crm/activities/options/users
    crm.activities.options.users

GET /crm/activities/{activity}
    crm.activities.show
```

All Stage 11 routes are read-only `GET` routes gated by:

```text
crm.sidebar.permission:crm.activities.list,read
```

No activity-specific mutating endpoint exists.

## Stage 11 Permission Key Exposed

New operational sidebar permission key:

```text
crm.activities.list
```

Existing permission keys remain preserved, including:

```text
crm.customers.profile
crm.leads.list
crm.tasks.list
crm.tasks.create
crm.tasks.complete
crm.tasks.calendar
crm.communications.list
crm.settings.tags
```

## Stage 11 Regression Safety Notes

```text
No migration introduced.
No destructive database change introduced.
No activity mutation route introduced.
No existing CRM write service replaced.
No customer, quotation, order, payment, due, ledger, accounting, return, refund, SMS, bulk-SMS, newsletter, support-ticket, contact-request, dashboard, task, communication, or lead write logic modified.
No TenantDbMiddleware or dynamic tenant database configuration modified.
Customer-profile URLs continue to use crm.customers.profile.
Stage 10 read-only CRM Task Calendar remains unchanged.
Stage 9 Lead Pipeline Board remains unchanged.
```



## Stage 12 Completed Scope

Stage 12 adds a read-only ERP-grade CRM Customer Health & Risk Worklist. It uses existing customer CRM summary fields only and does not change customer, order, payment, due, ledger, accounting, SMS, newsletter, support-ticket, lead, task, communication, activity, sidebar, permission, or tenant DB switching write flows.

Completed:

```text
Read-only Customer Health & Risk Worklist at /crm/customer-health
AJAX server-side DataTable
Remote Select2 customer filter
Remote Select2 assigned-user filter
Lifecycle-stage filter
Credit-status filter
Risk-bucket filter for overdue, due, follow-up due, and duplicate candidates
Next follow-up date-range filters
Last-contact date-range filters
Permission-aware Customer 360 profile links
Customer 360 health-worklist quick link with customer_id prefill
CRM dashboard Customer Health quick link
CRM sidebar Customer Health link
Tenant-schema-safe column checks through Schema::hasTable and Schema::hasColumn
No customer/order/payment/accounting recalculation
No migration
No write endpoints
```

Explicitly not implemented:

```text
Customer editing
Due recalculation
Payment or ledger posting
Credit limit enforcement
Risk scoring writes
Duplicate merge
Background sync
Automation workflows
Provider/SMS/email integration
```

## Stage 12 Files Added

```text
app/Http/Controllers/Crm/CrmCustomerHealthController.php
app/Http/Requests/Crm/CrmCustomerHealthWorklistRequest.php
app/Services/Crm/CrmCustomerHealthWorklistService.php
resources/views/backend/crm/customers/health/index.blade.php
```

## Stage 12 Files Modified

```text
CRM_STAGE_TRACKER.md
app/Http/Controllers/Crm/CrmCustomerProfileController.php
app/Http/Helpers/BackendSidebarHelper.php
app/Services/Crm/CrmDashboardService.php
app/Services/RoleSidebarPermissionService.php
resources/views/backend/crm-dashboard.blade.php
resources/views/backend/crm/customers/profile/show.blade.php
routes/crmRoutes.php
```

## Stage 12 Database Changes

Stage 12 introduces no migration and no database-schema modification.

Reused existing `customers` CRM summary fields when available:

```text
customer_code
lifecycle_stage
assigned_user_id
last_contact_at
next_follow_up_at
last_order_at
total_order_value
total_paid
current_due
overdue_amount
credit_status
is_duplicate_candidate
```

## Stage 12 Routes Added

```text
GET /crm/customer-health
    crm.customer-health.index

GET /crm/customer-health/data
    crm.customer-health.data

GET /crm/customer-health/options/customers
    crm.customer-health.options.customers

GET /crm/customer-health/options/users
    crm.customer-health.options.users
```

All Stage 12 routes are read-only `GET` routes.

## Stage 12 Permission Keys Exposed

```text
crm.customer-health.list,read
```

Preserved keys include:

```text
crm.customers.profile
crm.activities.list
crm.tasks.calendar
crm.leads.list
crm.communications.list
```


## Stage 13 Completed Scope

Stage 13 adds a read-only duplicate-customer review worklist for ERP-grade customer data-quality review. It does not merge, edit, delete, archive, recalculate, or mutate customer records.

Completed:

```text
Read-only CRM duplicate customer review page
AJAX server-side DataTable
Default flagged-candidate-only view using customers.is_duplicate_candidate when available
Optional include-unflagged duplicate phone/email signal review
Customer, assigned-user, lifecycle-stage, match-type, and candidate-flag filters
Customer and assigned-user Select2 AJAX option routes
Customer 360 prefilled duplicate-review quick link
CRM dashboard duplicate-review quick link
Sidebar Duplicate Review link
Permission-gated route group using crm.duplicate-customers.list,read
Shared window.AppAxios usage for DataTable and Select2 requests
No migration and no database write endpoint
```

Explicitly not implemented:

```text
Customer merge
Customer edit
Customer delete/archive
Duplicate flag recalculation
Data backfill
Automatic matching jobs
Destructive migration
Order, payment, due, ledger, accounting, SMS, newsletter, support-ticket, lead, task, activity, or tenant middleware changes
```

## Files Added in Stage 13

```text
app/Http/Controllers/Crm/CrmDuplicateCustomerController.php
app/Http/Requests/Crm/CrmDuplicateCustomerWorklistRequest.php
app/Services/Crm/CrmDuplicateCustomerWorklistService.php
resources/views/backend/crm/customers/duplicates/index.blade.php
```

## Files Modified in Stage 13

```text
CRM_STAGE_TRACKER.md
app/Http/Controllers/Crm/CrmCustomerProfileController.php
app/Http/Helpers/BackendSidebarHelper.php
app/Services/Crm/CrmDashboardService.php
resources/views/backend/crm-dashboard.blade.php
resources/views/backend/crm/customers/profile/show.blade.php
routes/crmRoutes.php
```

## Stage 13 Database Changes

No migration was added. No table or column is created, dropped, renamed, or modified.

Stage 13 reads existing customer columns when available:

```text
id
name
phone
email
customer_code
lifecycle_stage
assigned_user_id
is_duplicate_candidate
```

## Stage 13 Routes Added

```text
GET /crm/duplicate-customers
    crm.duplicate-customers.index

GET /crm/duplicate-customers/data
    crm.duplicate-customers.data

GET /crm/duplicate-customers/options/customers
    crm.duplicate-customers.options.customers

GET /crm/duplicate-customers/options/users
    crm.duplicate-customers.options.users
```

All Stage 13 routes are read-only GET routes.

## Stage 13 Permission Keys Exposed

```text
crm.duplicate-customers.list,read
```

## Stage 14 Completed Scope

Stage 14 stabilizes the Stage 13 read-only CRM Duplicate Customer Review Worklist. It fixes include-unflagged filtering, groups phone-or-email duplicate conditions safely, removes row-by-row duplicate-count query amplification, and registers the duplicate-review permission key in the canonical CRM permission registry.

Completed:

```text
Preserved default flagged-candidate-only review behavior
Fixed Include unflagged matches so it returns duplicate phone/email signal records only
Preserved customer, assigned-user, lifecycle-stage, and match-type filters
Grouped phone-or-email EXISTS conditions in a nested query closure
Prevented OR branches from bypassing other worklist filters
Replaced per-row phone/email count queries with guarded correlated select subqueries
Added crm.duplicate-customers.list to the canonical CRM permission registry
Preserved all Stage 13 routes as read-only GET routes
Preserved window.AppAxios usage in the existing Stage 13 Blade
No migration and no database write endpoint
```

Explicitly not implemented:

```text
Customer merge
Customer edit
Customer delete/archive
Duplicate flag recalculation
Automatic matching jobs
Data backfill
Destructive migration
Tenant middleware changes
Order, payment, due, ledger, accounting, SMS, newsletter, support-ticket, lead, task, activity, or communication write-flow changes
```

## Files Modified in Stage 14

```text
CRM_STAGE_TRACKER.md
app/Services/Crm/CrmDuplicateCustomerWorklistService.php
app/Services/RoleSidebarPermissionService.php
```

## Stage 14 Database Changes

Stage 14 introduces no migration and no database-schema modification.

No table or column is created, dropped, renamed, or modified. The read-only duplicate worklist continues to use tenant-schema-safe `Schema::hasTable()` and `Schema::hasColumn()` checks.

## Stage 14 Route Review

Stage 14 adds, removes, or changes no routes. The existing Stage 13 route group remains:

```text
GET /crm/duplicate-customers
    crm.duplicate-customers.index

GET /crm/duplicate-customers/data
    crm.duplicate-customers.data

GET /crm/duplicate-customers/options/customers
    crm.duplicate-customers.options.customers

GET /crm/duplicate-customers/options/users
    crm.duplicate-customers.options.users
```

All duplicate-review routes remain gated by:

```text
crm.sidebar.permission:crm.duplicate-customers.list,read
```

## Stage 14 Permission Hardening

Canonical CRM permission registry now includes:

```text
crm.duplicate-customers.list
```

The existing sidebar, dashboard, Customer 360 quick link, and route middleware key remain unchanged.

## Stage 14 Query Stabilization Notes

```text
Default candidate_only=true with customers.is_duplicate_candidate present:
    Return flagged candidates only.

candidate_only=false:
    Require duplicate signal filtering for the selected match type.

match_type=any:
    Apply grouped phone EXISTS OR email EXISTS conditions.

Phone/email match counts:
    Calculate through correlated select subqueries in the main worklist query.
    Do not run two additional count queries per returned row.
```

## Stage 14 Regression Safety Notes

```text
No migration introduced.
No destructive database change introduced.
No route mutation introduced.
No merge, edit, delete, archive, or recalculation endpoint introduced.
No customer, quotation, order, payment, due, ledger, accounting, return, refund, SMS, bulk-SMS, newsletter, support-ticket, contact-request, dashboard, task, communication, activity, or lead write logic modified.
No TenantDbMiddleware or dynamic tenant database configuration modified.
Stage 9 Lead Pipeline Board remains unchanged.
Stage 10 CRM Task Calendar remains unchanged.
Stage 11 CRM Activity Audit Worklist remains unchanged.
Stage 12 CRM Customer Health & Risk Worklist remains unchanged.
```


## Stage 15 Completed Scope

Stage 15 adds a read-only CRM Customer Portfolio Segmentation Worklist. It exposes existing CRM tags and guarded customer-summary fields for operational portfolio review without mutating customer, messaging, accounting, order, payment, due, or tenant configuration flows.

Completed:

```text
Read-only CRM customer portfolio segmentation page
AJAX server-side DataTable
Customer, assigned-user, CRM-tag, lifecycle-stage, credit-status, risk-bucket, duplicate-candidate, last-contact, next-follow-up, and last-order filters
Multi-select CRM tag filter with explicit match-any semantics
Customer, assigned-user, and CRM-tag Select2 AJAX option routes
Guarded eager loading of assigned users and tags to avoid per-row application-level relation queries
Tenant-schema-safe fallback when CRM tag tables are unavailable
Customer 360 prefilled Portfolio Segments quick link
CRM dashboard Portfolio Segments quick link
Sidebar Portfolio Segments link
Permission-gated route group using crm.customer-segments.list,read
Canonical CRM permission registration for crm.customer-segments.list
Shared window.AppAxios usage for DataTable and Select2 requests
SweetAlert error feedback and inline 422 validation feedback
No migration and no database write endpoint
```

Explicitly not implemented:

```text
Saved segment definitions
Campaign creation
Bulk SMS execution
Newsletter execution
Email or WhatsApp sending
Customer merge
Customer editing
Due recalculation
Credit enforcement
Data backfill
Exports
Background jobs
Automation workflows
Destructive migration
Tenant middleware changes
```

## Files Added in Stage 15

```text
app/Http/Controllers/Crm/CrmCustomerSegmentController.php
app/Http/Requests/Crm/CrmCustomerSegmentWorklistRequest.php
app/Services/Crm/CrmCustomerSegmentWorklistService.php
resources/views/backend/crm/customers/segments/index.blade.php
```

## Files Modified in Stage 15

```text
CRM_STAGE_TRACKER.md
app/Http/Controllers/Crm/CrmCustomerProfileController.php
app/Http/Helpers/BackendSidebarHelper.php
app/Services/Crm/CrmDashboardService.php
app/Services/RoleSidebarPermissionService.php
resources/views/backend/crm-dashboard.blade.php
resources/views/backend/crm/customers/profile/show.blade.php
routes/crmRoutes.php
```

## Stage 15 Database Changes

Stage 15 introduces no migration and no database-schema modification.

No table or column is created, dropped, renamed, or modified. The read-only worklist reads existing customer-summary columns when available and uses existing CRM tag tables only when both are available:

```text
customers
crm_customer_tags
crm_customer_tag_pivots
```

## Stage 15 Routes Added

```text
GET /crm/customer-segments
    crm.customer-segments.index

GET /crm/customer-segments/data
    crm.customer-segments.data

GET /crm/customer-segments/options/customers
    crm.customer-segments.options.customers

GET /crm/customer-segments/options/users
    crm.customer-segments.options.users

GET /crm/customer-segments/options/tags
    crm.customer-segments.options.tags
```

All Stage 15 routes are read-only GET routes.

## Stage 15 Permission Keys Exposed

```text
crm.customer-segments.list,read
```

## Stage 15 Regression Safety Notes

```text
No migration introduced.
No destructive database change introduced.
No customer, quotation, order, payment, due, ledger, accounting, return, refund, SMS, bulk-SMS, newsletter, support-ticket, contact-request, task, communication, activity, lead, or tenant middleware write logic modified.
No saved segment, campaign, export, background job, or automation write flow introduced.
Stage 9 Lead Pipeline Board remains unchanged.
Stage 10 CRM Task Calendar remains unchanged.
Stage 11 CRM Activity Audit Worklist remains unchanged.
Stage 12 CRM Customer Health & Risk Worklist remains unchanged.
Stage 13 CRM Duplicate Customer Review Worklist remains unchanged.
Stage 14 CRM Duplicate Review Stabilization and Permission Hardening remains unchanged.
```

---

# Stage 16 — CRM Saved Customer Segments Foundation ✅ Complete

Completed on: 2026-06-06

## Stage 16 Completed Scope

Stage 16 adds a reusable saved-segment layer on top of the Stage 15 read-only CRM Customer Portfolio Segmentation Worklist. It stores validated portfolio-filter definitions only. Applying a saved segment repopulates existing Stage 15 filters and reloads the existing read-only customer worklist. It does not mutate customers, messages, orders, payments, dues, ledgers, accounting records, tenant configuration, or automation state.

Completed:

```text
CRM saved customer segment list page
AJAX server-side saved-segment DataTable
Saved segment details modal and JSON endpoint
Saved segment Select2 options endpoint
Save Current Filters action from Portfolio Segments
Apply Saved Segment action on Portfolio Segments
Update Applied Segment action for creator-owned active segments
Private visibility: creator-only read access
Shared visibility: reusable read access for permitted CRM users
Creator-only update and archive enforcement
Active and archived status handling
Archive-only removal with no hard-delete endpoint
Strict saved-filter JSON key whitelist
Stage 15-compatible saved-filter value validation
Empty-filter rejection to prevent accidental all-customer saved definitions
Canonical follow_up_from and follow_up_to storage matching Stage 15
Creator, updater, archiver, archived_at tracking
CRM activity logging for create, update, and archive
DB transactions for create, update, and archive writes
Sidebar Saved Segments link
CRM dashboard Saved Segments quick link
Canonical crm.saved-customer-segments.list permission registration
Shared window.AppAxios usage
SweetAlert2 feedback, inline HTTP 422 errors, loading states, and double-submit prevention
```

## Files Added in Stage 16

```text
app/Http/Controllers/Crm/CrmSavedCustomerSegmentController.php
app/Http/Requests/Crm/ArchiveCrmSavedCustomerSegmentRequest.php
app/Http/Requests/Crm/CrmSavedCustomerSegmentWorklistRequest.php
app/Http/Requests/Crm/SaveCrmSavedCustomerSegmentRequest.php
app/Http/Requests/Crm/StoreCrmSavedCustomerSegmentRequest.php
app/Http/Requests/Crm/UpdateCrmSavedCustomerSegmentRequest.php
app/Models/Crm/CrmSavedCustomerSegment.php
app/Services/Crm/CrmSavedCustomerSegmentService.php
database/migrations/2026_06_06_000005_create_crm_saved_customer_segments_table.php
resources/views/backend/crm/customers/saved-segments/index.blade.php
```

## Files Modified in Stage 16

```text
CRM_STAGE_TRACKER.md
app/Http/Controllers/Crm/CrmCustomerSegmentController.php
app/Http/Helpers/BackendSidebarHelper.php
app/Services/Crm/CrmCustomerSegmentWorklistService.php
app/Services/Crm/CrmDashboardService.php
app/Services/RoleSidebarPermissionService.php
resources/views/backend/crm-dashboard.blade.php
resources/views/backend/crm/customers/segments/index.blade.php
routes/crmRoutes.php
```

## Stage 16 Database Changes

Stage 16 introduces one tenant-safe additive migration:

```text
database/migrations/2026_06_06_000005_create_crm_saved_customer_segments_table.php
```

The migration creates or safely extends:

```text
crm_saved_customer_segments
```

Columns:

```text
id
product_website_id nullable
name
description nullable
filters_json
visibility                 private | shared
status                     active | archived
created_by nullable
updated_by nullable
archived_by nullable
archived_at nullable
created_at
updated_at
```

Indexes:

```text
product_website_id
visibility
status
created_by
archived_at
```

Safety properties:

```text
Schema::hasTable() guard before create
Schema::hasColumn() guards before adding missing columns
Named-index existence checks before additive index creation
No dropped table
No dropped column
No renamed column
Non-destructive down()
```

## Stage 16 Routes Added

```text
GET  /crm/saved-customer-segments
     crm.saved-customer-segments.index

GET  /crm/saved-customer-segments/data
     crm.saved-customer-segments.data

GET  /crm/saved-customer-segments/options
     crm.saved-customer-segments.options

POST /crm/saved-customer-segments
     crm.saved-customer-segments.store

GET  /crm/saved-customer-segments/{segment}
     crm.saved-customer-segments.show

POST /crm/saved-customer-segments/{segment}
     crm.saved-customer-segments.update

POST /crm/saved-customer-segments/{segment}/archive
     crm.saved-customer-segments.archive
```

No hard-delete route exists.

## Stage 16 Permission Keys Exposed

```text
crm.saved-customer-segments.list,read
crm.saved-customer-segments.list,create
crm.saved-customer-segments.list,update
crm.saved-customer-segments.list,delete    archive only
```

## Stage 16 Canonical Saved Filter Keys

```text
customer_id
assigned_user_id
tag_ids
lifecycle_stage
credit_status
risk_bucket
duplicate_candidate
last_contact_from
last_contact_to
follow_up_from
follow_up_to
last_order_from
last_order_to
```

Unknown saved-filter keys are rejected. Saved definitions with no active filters are rejected.

## Stage 16 Explicitly Not Implemented

```text
Campaign execution
Bulk SMS sending
Newsletter sending
Email sending
WhatsApp sending
Automatic customer assignment
Scheduled automation
Customer merge
Exports
Credit enforcement
Due recalculation
Background jobs
Customer CRUD replacement
Hard delete
Unrelated refactoring
Tenant middleware changes
```

## Stage 16 Regression Safety Notes

```text
Existing Stage 15 customer portfolio filtering remains the single customer-query path.
Applying a saved segment only repopulates Stage 15 filters and reloads the read-only DataTable.
No customer, quotation, order, payment, due, ledger, accounting, return, refund, SMS, bulk-SMS, newsletter, support-ticket, contact-request, task, communication, activity-audit, lead, or tenant middleware write logic was replaced.
Stage 9 Lead Pipeline Board remains unchanged.
Stage 10 CRM Task Calendar remains unchanged.
Stage 11 CRM Activity Audit Worklist remains unchanged.
Stage 12 CRM Customer Health & Risk Worklist remains unchanged.
Stage 13 CRM Duplicate Customer Review Worklist remains unchanged.
Stage 14 CRM Duplicate Review Stabilization and Permission Hardening remains unchanged.
Stage 15 CRM Customer Portfolio Segmentation Worklist remains read-only.
```

---

# Stage 17 — CRM Saved Segment Stabilization and Governance Hardening ✅ Complete

Completed on: 2026-06-06

## Stage 17 Completed Scope

Stage 17 stabilizes the Stage 16 saved-segment foundation before any campaign or messaging capability is introduced. It keeps saved segments as reusable read-only Portfolio Segments filter definitions and adds fail-closed governance for legacy or manually altered stored JSON.

Completed:

```text
Strict service-level validation for stored saved-filter JSON
Fail-closed rejection for malformed JSON, unsupported keys, malformed IDs, malformed arrays, duplicate tags, excessive tags, invalid enum values, invalid dates, reversed date ranges, and empty definitions
Invalid legacy definitions remain visible in the saved-segment audit worklist so their creator can archive them
Invalid legacy definitions are excluded from Select2 apply options
Invalid legacy definitions do not expose Apply or Edit Filters links
Dedicated server-enforced read-only saved-segment apply endpoint
Apply endpoint enforces read permission, visibility, active status, strict stored-filter validation, and non-empty definitions
Archived saved segments cannot be applied through direct URLs
Service-level read authorization added for list, details, options, and apply paths
Saved-segment create, update, and archive writes now record required CRM activity inside the same DB transaction
CRM activity recording gains recordOrFail() while existing best-effort record() behavior remains unchanged for unrelated CRM flows
Archive cleanup remains available for creator-owned active legacy definitions even when their stored filters are malformed
Saved Segments details modal warns when a stored definition is invalid
Portfolio Segments continues to use window.AppAxios, Select2, inline HTTP 422 validation, loading state, and double-submit prevention
```

## Files Modified in Stage 17

```text
CRM_STAGE_TRACKER.md
app/Http/Controllers/Crm/CrmSavedCustomerSegmentController.php
app/Services/Crm/CrmActivityService.php
app/Services/Crm/CrmSavedCustomerSegmentService.php
resources/views/backend/crm/customers/saved-segments/index.blade.php
resources/views/backend/crm/customers/segments/index.blade.php
routes/crmRoutes.php
```

## Stage 17 Database Changes

Stage 17 introduces no migration and no database-schema modification.

```text
No table created
No column added
No column dropped
No table dropped
No renamed column
No index change
```

The guarded tenant-safe Stage 16 migration remains the schema source for:

```text
crm_saved_customer_segments
```

## Stage 17 Route Added

```text
GET /crm/saved-customer-segments/{segment}/apply
    crm.saved-customer-segments.apply
```

The apply route is read-only and uses:

```text
crm.saved-customer-segments.list,read
```

The static and action-specific routes remain declared before the generic details route, and every dynamic saved-segment route retains the numeric segment constraint.

## Stage 17 Stored Filter Governance

Stored saved-filter definitions now fail closed unless they use the Stage 15 canonical keys:

```text
customer_id
assigned_user_id
tag_ids
lifecycle_stage
credit_status
risk_bucket
duplicate_candidate
last_contact_from
last_contact_to
follow_up_from
follow_up_to
last_order_from
last_order_to
```

Invalid definitions remain auditable and archiveable by their creator, but cannot be applied to Portfolio Segments.

## Stage 17 CRM Activity Atomicity

The existing activity types remain unchanged:

```text
crm_saved_customer_segment_created
crm_saved_customer_segment_updated
crm_saved_customer_segment_archived
```

Saved-segment row writes and required CRM activity inserts now share one transaction boundary. If the required saved-segment activity insert fails, the saved-segment mutation rolls back.

Unrelated CRM modules continue using the existing best-effort activity-recording path.

## Stage 17 Explicitly Not Implemented

```text
Campaign execution
Bulk SMS sending
Newsletter sending
Email sending
WhatsApp sending
Automatic customer assignment
Scheduled automation
Background jobs
Customer merge
Exports
Credit enforcement
Due recalculation
Customer CRUD replacement
Hard delete
Unrelated refactoring
Tenant middleware changes
```

## Stage 17 Regression Safety Notes

```text
Stage 15 Portfolio Segments remains the only customer-query path used after applying a saved definition.
Applying a saved segment only repopulates Stage 15 filters and reloads the existing read-only DataTable.
Saved-segment audit details remain readable for permitted users, including archived definitions.
No hard-delete path exists.
No schema migration was introduced.
No tenant DB middleware or dynamic database-switching logic was modified.
No customer, quotation, order, payment, due, ledger, accounting, return, refund, SMS, bulk-SMS, newsletter, support-ticket, contact-request, task, communication, lead, or sidebar flow was replaced.
Stage 9 Lead Pipeline Board remains unchanged.
Stage 10 CRM Task Calendar remains unchanged.
Stage 11 CRM Activity Audit Worklist remains unchanged.
Stage 12 CRM Customer Health & Risk Worklist remains unchanged.
Stage 13 CRM Duplicate Customer Review Worklist remains unchanged.
Stage 14 CRM Duplicate Review Stabilization and Permission Hardening remains unchanged.
Stage 15 CRM Customer Portfolio Segmentation Worklist remains read-only.
Stage 16 CRM Saved Customer Segments Foundation remains intact and hardened.
```

---

# Stage 18 — CRM Campaign Planning Foundation — Read-only Audience Preview and Draft Governance ✅ Complete

Completed on: 2026-06-07

## Stage 18 Completed Scope

Stage 18 adds a planning-only CRM campaign-draft layer on top of active, strictly valid saved customer segments. It intentionally does not connect any sending adapter, queue, scheduled execution path, or customer mutation flow.

Completed:

```text
Additive tenant-safe crm_campaign_drafts table
Draft-only lifecycle governance: draft and archived
No hard delete endpoint
AJAX campaign-draft DataTable worklist
Select2 active saved-segment audience source
Private and shared campaign-draft visibility rules
Creator-only active draft update and archive
Historical saved-segment name, visibility, and canonical filter-definition snapshot
Read-only live recipient estimate using the Stage 15 segmentation query path
Read-only preview for a selected active saved segment before draft save
Read-only preview for an existing draft from its historical stored snapshot
Fail-closed stored audience-snapshot validation
Private saved-segment audiences can only create private campaign drafts
Only creator-owned drafts expose private audience snapshots
Shared drafts are readable only when their stored audience source snapshot was shared
window.AppAxios, SweetAlert2, inline HTTP 422 validation, loading states, and double-submit prevention
Required CRM activity writes inside the same transaction as campaign-draft create, update, and archive
Permission-controlled sidebar Campaign Drafts link
Permission-controlled CRM dashboard Campaign Drafts quick link
```

## Files Added in Stage 18

```text
app/Http/Controllers/Crm/CrmCampaignDraftController.php
app/Http/Requests/Crm/ArchiveCrmCampaignDraftRequest.php
app/Http/Requests/Crm/CrmCampaignDraftWorklistRequest.php
app/Http/Requests/Crm/SaveCrmCampaignDraftRequest.php
app/Http/Requests/Crm/StoreCrmCampaignDraftRequest.php
app/Http/Requests/Crm/UpdateCrmCampaignDraftRequest.php
app/Models/Crm/CrmCampaignDraft.php
app/Services/Crm/CrmCampaignDraftService.php
database/migrations/2026_06_07_000006_create_crm_campaign_drafts_table.php
resources/views/backend/crm/campaign-drafts/index.blade.php
```

## Files Modified in Stage 18

```text
CRM_STAGE_TRACKER.md
app/Http/Helpers/BackendSidebarHelper.php
app/Services/Crm/CrmDashboardService.php
app/Services/Crm/CrmSavedCustomerSegmentService.php
app/Services/RoleSidebarPermissionService.php
resources/views/backend/crm-dashboard.blade.php
routes/crmRoutes.php
```

## Stage 18 Database Changes

Stage 18 introduces one additive tenant-safe migration:

```text
database/migrations/2026_06_07_000006_create_crm_campaign_drafts_table.php
```

Table added:

```text
crm_campaign_drafts
```

Columns:

```text
id
product_website_id nullable
name
description nullable
planned_channel nullable
subject nullable
message_body nullable
audience_saved_segment_id nullable
audience_segment_name_snapshot nullable
audience_segment_visibility_snapshot nullable
audience_filters_json
audience_snapshot_at nullable
visibility                 private | shared
status                     draft | archived
created_by nullable
updated_by nullable
archived_by nullable
archived_at nullable
created_at
updated_at
```

Migration safety:

```text
Schema::hasTable() guard
Schema::hasColumn() guards
Named-index existence checks
Non-destructive down()
No dropped table
No dropped column
No renamed column
No foreign-key coupling to legacy tables
```

## Stage 18 Route Additions

```text
GET  /crm/campaign-drafts
GET  /crm/campaign-drafts/data
GET  /crm/campaign-drafts/options/saved-segments
GET  /crm/campaign-drafts/audience-preview/{segment}
POST /crm/campaign-drafts
GET  /crm/campaign-drafts/{draft}/audience-preview
GET  /crm/campaign-drafts/{draft}
POST /crm/campaign-drafts/{draft}
POST /crm/campaign-drafts/{draft}/archive
```

Static routes and action-specific preview routes are declared before generic dynamic draft details routes. Every dynamic segment and draft route retains a numeric route constraint.

## Stage 18 Permission Mapping

Canonical permission key:

```text
crm.campaign-drafts.list
```

Mapping:

```text
read    list, details, Select2 source options, selected-source preview, stored-snapshot preview
create  create a draft from an active visible valid saved segment
update  update a creator-owned active draft from an active visible valid saved segment
delete  archive a creator-owned active draft only
```

Source-segment selection, source preview, draft create, and draft update additionally require:

```text
crm.saved-customer-segments.list,read
```

## Stage 18 Historical Audience Snapshot Governance

When a campaign draft is created, or when an update changes its source segment, the service locks and validates the selected active saved customer segment before storing:

```text
audience_saved_segment_id
audience_segment_name_snapshot
audience_segment_visibility_snapshot
audience_filters_json
audience_snapshot_at
```

When an update keeps the same source segment, the service retains and revalidates the existing historical snapshot instead of silently refreshing it. This allows an existing draft to remain editable after its original source segment is archived while preventing archived segments from being newly attached.

Recipient counts are calculated live from the stored canonical filter snapshot through:

```text
CrmCustomerSegmentWorklistService::worklistQuery()
```

The preview path remains read only. It does not change customer records, saved segments, campaign drafts, communication history, or activity history.

## Stage 18 CRM Activity Atomicity

Required activity events:

```text
crm_campaign_draft_created
crm_campaign_draft_updated
crm_campaign_draft_archived
```

Campaign-draft row writes and required `CrmActivityService::recordOrFail()` inserts share the same transaction boundary. If required activity recording fails, the corresponding campaign-draft mutation rolls back.

## Stage 18 Explicitly Not Implemented

```text
SMS sending
Bulk SMS sending
Newsletter sending
Email sending
WhatsApp sending
Campaign execution
Scheduled sending
Background jobs
Queue workers
Third-party messaging adapters
Provider API calls
Recipient exports
Automatic customer assignment
Customer merge
Credit enforcement
Due recalculation
Customer CRUD replacement
Hard delete
Legacy messaging refactoring
Tenant middleware changes
```

## Stage 18 Regression Safety Notes

```text
Existing newsletter and bulk-SMS execution controllers remain untouched.
crm_communications remains per-customer communication history and is not repurposed.
Stage 15 Portfolio Segments remains the only customer-query path for audience preview.
Stage 16 saved-segment foundation remains intact.
Stage 17 strict saved-segment validation remains intact and is reused for audience snapshots.
Existing drafts remain auditable after their source saved segment is archived because a historical filter snapshot is stored.
No sending, queue, job, provider, export, or scheduled-execution route exists.
No customer, quotation, order, payment, due, ledger, accounting, return, refund, SMS, bulk-SMS, newsletter, support-ticket, contact-request, task, communication, lead, or tenant middleware write logic was replaced.
```

---

# Stage 19 — CRM Campaign Draft Stabilization and Governance Hardening ✅ Complete

Completed on: 2026-06-07

## Stage 19 Completed Scope

Stage 19 hardens the Stage 18 campaign-planning foundation without adding message execution, scheduled sending, queues, provider adapters, frozen recipient exports, or customer mutation paths.

Completed:

```text
Accurate campaign worklist All statuses filter while retaining draft-only initial loading
Strict stored audience-snapshot envelope validation
Positive stored source-segment ID validation
Bounded non-empty stored source-name validation
Stored source visibility validation
Strict stored snapshot timestamp validation
Canonical stored audience-filter validation through the Stage 17 saved-segment governance path
HMAC-SHA256 tamper-evident snapshot sealing for newly created and newly attached audience snapshots
Versioned snapshot signature metadata
HMAC-derived shared-audience token required for cross-user visibility
Creator-owned controlled sealing of structurally valid Stage 18 legacy snapshots during a retained-source update
Verified-signature requirement before a shared draft becomes visible to non-creators
Fail-closed redaction for a signed shared draft if its stored snapshot is later malformed or manually altered
Legacy unsigned snapshot integrity status surfaced for creator audit review
Verified snapshot integrity status surfaced in draft details
Unused raw audience-filter array removed from campaign-draft details responses
Defensive service-level input-length validation matching Form Request boundaries
Explicit CrmCampaignDraft model fillable fields instead of unrestricted guarded assignment
Required CRM activity metadata extended with audience snapshot integrity state
```

## Files Added in Stage 19

```text
database/migrations/2026_06_07_000007_add_snapshot_integrity_to_crm_campaign_drafts_table.php
```

## Files Modified in Stage 19

```text
CRM_STAGE_TRACKER.md
app/Models/Crm/CrmCampaignDraft.php
app/Services/Crm/CrmCampaignDraftService.php
resources/views/backend/crm/campaign-drafts/index.blade.php
```

## Stage 19 Database Changes

Stage 19 introduces one additive tenant-safe migration:

```text
database/migrations/2026_06_07_000007_add_snapshot_integrity_to_crm_campaign_drafts_table.php
```

Columns added to `crm_campaign_drafts` when missing:

```text
audience_snapshot_signature          nullable string(64)
audience_snapshot_signature_version  nullable unsigned small integer
audience_snapshot_share_token         nullable string(64)
```

Migration safety:

```text
Schema::hasTable() guard
Schema::hasColumn() guards
Non-destructive down()
No dropped table
No dropped column
No renamed column
No hard delete
No backfill mutation of historical campaign snapshots
```

## Stage 19 Snapshot Integrity Governance

Newly created campaign drafts and drafts that attach a newly selected active saved segment receive a versioned HMAC-SHA256 signature over the canonical historical snapshot envelope:

```text
audience_saved_segment_id
audience_segment_name_snapshot
audience_segment_visibility_snapshot
audience_filters_json
audience_snapshot_at
```

A retained-source creator update preserves the stored historical audience definition. When the retained historical snapshot is a structurally valid unsigned Stage 18 record, that authenticated update seals the exact retained definition without refreshing it from the source saved segment.

Shared visibility is intentionally stricter after Stage 19:

```text
A shared draft is visible to its creator for audit purposes.
A shared draft is visible to another permitted CRM user only when the stored audience snapshot is sealed with the supported signature version and carries a valid shared-audience token.
A private snapshot cannot become cross-user visible merely through database visibility-field edits because it has no shared-audience token.
A malformed or manually altered sealed snapshot fails integrity verification.
A malformed sealed shared-row payload is redacted from non-creators and direct details or preview access fails closed.
Legacy unsigned Stage 18 snapshots remain creator-readable for audit review and can be sealed through a creator-owned retained-source update.
```

## Stage 19 Defensive Validation Boundaries

Service-layer validation now repeats the key Form Request limits so future internal service callers cannot bypass them:

```text
name                required, max 160
description         nullable, max 2000
planned_channel     nullable allow-list, max 30
subject             nullable, max 255
message_body        nullable, max 10000
visibility          private | shared
audience source ID  positive integer
```

The service continues to reject unsupported planned channels, unsupported visibility values, malformed audience source IDs, unauthorized saved segments, archived saved segments, malformed saved-segment definitions, and invalid retained historical snapshots.

## Stage 19 Worklist Filter Repair

The initial campaign worklist remains draft-only. An explicitly selected blank status filter now correctly means:

```text
All statuses
```

This allows permitted users to review both draft and archived records without changing the default operational view.

## Stage 19 Route and Permission Safety

No route was added, removed, renamed, or reordered.

Preserved canonical permission key:

```text
crm.campaign-drafts.list
```

Preserved mapping:

```text
read    list, details, Select2 source options, selected-source preview, stored-snapshot preview
create  create a draft from an active visible valid saved segment
update  update a creator-owned active draft
delete  archive a creator-owned active draft only
```

Saved-segment read permission remains required where appropriate:

```text
crm.saved-customer-segments.list,read
```

## Stage 19 Activity Atomicity

The existing required activity types remain unchanged:

```text
crm_campaign_draft_created
crm_campaign_draft_updated
crm_campaign_draft_archived
```

Campaign-draft row writes and required `CrmActivityService::recordOrFail()` inserts remain inside the same transaction boundary. Activity metadata now includes the snapshot integrity state.

## Stage 19 Explicitly Not Implemented

```text
SMS sending
Bulk SMS sending
Newsletter sending
Email sending
WhatsApp sending
Campaign execution
Scheduled sending
Background jobs
Queue workers
Third-party messaging adapters
Provider API calls
Frozen recipient exports
Recipient sample lists
Automatic customer assignment
Customer merge
Credit enforcement
Due recalculation
Customer CRUD replacement
Hard delete
Legacy messaging refactoring
Tenant middleware changes
Unrelated refactoring
```

## Stage 19 Regression Safety Notes

```text
Stage 15 Portfolio Segments remains the only customer-query path for live recipient estimation.
Stage 16 saved-segment foundation remains intact.
Stage 17 strict saved-segment validation remains reused for historical audience filters.
Stage 18 campaign planning remains draft-only and archive-only.
No campaign execution endpoint exists.
No hard-delete endpoint exists.
No communication-history record is written by preview.
No customer row is mutated by preview.
No tenant DB middleware or dynamic database-switching logic was modified.
Existing newsletter, SMS, bulk-SMS, support-ticket, customer, quotation, order, payment, due, ledger, accounting, return, refund, task, activity, segmentation, lead, dashboard, sidebar, and permission flows were not replaced.
```

## Stage 20 Baseline Verification

The latest uploaded full-project ZIP was reinspected as the single implementation source before Stage 20 changes.

Confirmed prerequisite state:

```text
Stage 19 snapshot-integrity migration is present
Stage 19 campaign snapshot HMAC signature, version, and shared-audience token logic is present
CrmCampaignDraft uses explicit fillable fields
No campaign send, execute, scheduling, queue, provider, export, or hard-delete endpoint exists
Reported customer-segment BelongsToMany eager-load callback TypeError hotfix is present
Reported duplicate-customer backend.master Blade layout hotfix is present
Tenant DB middleware and dynamic database switching remain untouched
```

## Stage 20 Completed Scope

Stage 20 introduces an ERP-grade campaign approval and send-readiness governance foundation without adding message execution.

Completed:

```text
Controlled campaign lifecycle states:
  draft
  pending_review
  approved
  rejected
  archived

Creator-owned submit-for-review transition
Independent non-creator approval and rejection transitions
Creator-owned auditable return-to-draft transition
Self-approval prevention
Approved-campaign planning immutability until explicit return-to-draft
Read-only send-readiness preflight checklist
Live recipient-count estimate through the existing Stage 15 canonical segmentation query path
Append-only campaign approval-history table
Atomic CRM activity logging for every Stage 20 transition
Dedicated contextual approver permission key
Stage 20 worklist UI lifecycle filters, readiness modal, approval-history modal, and governance buttons
SweetAlert2 confirmation or note prompts, loading states, inline HTTP 422 handling, and double-submit prevention
```

## Files Added in Stage 20

```text
app/Models/Crm/CrmCampaignDraftApprovalHistory.php
app/Http/Requests/Crm/SubmitCrmCampaignDraftForReviewRequest.php
app/Http/Requests/Crm/ApproveCrmCampaignDraftRequest.php
app/Http/Requests/Crm/RejectCrmCampaignDraftRequest.php
app/Http/Requests/Crm/ReturnCrmCampaignDraftToDraftRequest.php
database/migrations/2026_06_07_000008_add_campaign_approval_governance.php
```

## Files Modified in Stage 20

```text
CRM_STAGE_TRACKER.md
app/Models/Crm/CrmCampaignDraft.php
app/Services/Crm/CrmCampaignDraftService.php
app/Http/Controllers/Crm/CrmCampaignDraftController.php
app/Services/RoleSidebarPermissionService.php
resources/views/backend/crm/campaign-drafts/index.blade.php
routes/crmRoutes.php
```

## Stage 20 Database Changes

Stage 20 introduces one additive tenant-safe migration:

```text
database/migrations/2026_06_07_000008_add_campaign_approval_governance.php
```

Columns added to `crm_campaign_drafts` when missing:

```text
submitted_by
submitted_at
reviewed_by
reviewed_at
review_decision
review_note
approved_snapshot_signature
approved_snapshot_signature_version
approved_snapshot_at
```

New append-only audit table created when missing:

```text
crm_campaign_draft_approval_history
```

History fields:

```text
id
product_website_id
crm_campaign_draft_id
action
from_status
to_status
actor_id
review_note
audience_snapshot_signature
audience_snapshot_signature_version
approved_snapshot_signature
approved_snapshot_signature_version
approved_snapshot_at
metadata_json
created_at
```

Migration safety:

```text
Schema::hasTable() guards
Schema::hasColumn() guards
Named-index existence checks before additive indexes
Non-destructive down()
No table drop
No column drop
No column rename
No historical backfill mutation
No foreign-key assumption across tenant schemas
```

## Stage 20 Routes Added

```text
GET  /crm/campaign-drafts/{draft}/preflight
     crm.campaign-drafts.preflight

GET  /crm/campaign-drafts/{draft}/approval-history
     crm.campaign-drafts.approval-history

POST /crm/campaign-drafts/{draft}/submit-for-review
     crm.campaign-drafts.submit-for-review

POST /crm/campaign-drafts/{draft}/approve
     crm.campaign-drafts.approve

POST /crm/campaign-drafts/{draft}/reject
     crm.campaign-drafts.reject

POST /crm/campaign-drafts/{draft}/return-to-draft
     crm.campaign-drafts.return-to-draft
```

All Stage 20 dynamic routes retain numeric `draft` constraints. Nested routes remain before the generic campaign details route.

## Stage 20 Permission Safety

Existing canonical campaign-draft permission remains preserved:

```text
crm.campaign-drafts.list
```

New contextual approver permission exposed in the role editor without a dead sidebar URL:

```text
crm.campaign-drafts.approve
```

Approval and rejection require:

```text
crm.campaign-drafts.list,read
crm.campaign-drafts.approve,update
```

Creator submit and return-to-draft require:

```text
crm.campaign-drafts.list,update
```

Existing archive-only action remains:

```text
crm.campaign-drafts.list,delete
```

Campaign creators cannot approve or reject their own campaign drafts even if they hold the approver permission.

## Stage 20 State-Machine Safety

Supported transitions:

```text
draft          -> pending_review   creator submit-for-review only
pending_review -> approved         independent approver only
pending_review -> rejected         independent approver only
pending_review -> draft            creator return-to-draft only
approved       -> draft            creator return-to-draft only
rejected       -> draft            creator return-to-draft only
draft          -> archived         creator archive-only behavior preserved
```

Planning edits remain limited to `draft`. An approved campaign must deliberately return to draft before any content, channel, visibility, or audience-source edit is accepted.

## Stage 20 Approval Integrity

Approval stores a separate versioned HMAC-SHA256 integrity signature over the approved planning snapshot:

```text
campaign draft ID
name
description
planned channel
subject
message body
visibility
audience saved-segment ID
Stage 19 audience snapshot signature
Stage 19 audience snapshot signature version
Stage 19 shared-audience token
approved snapshot timestamp
```

A manually changed approved campaign fails approval-integrity verification. This detects stale approval after content, channel, visibility, or audience metadata changes.

## Stage 20 Send-Readiness Preflight

The read-only preflight checks:

```text
known non-archived lifecycle state
campaign name present
supported readiness channel: sms, email, newsletter, or whatsapp
subject present for email and newsletter planning
message body present
shared visibility suitable for independent review
valid stored audience snapshot
verified Stage 19 audience snapshot seal
live estimated recipient count greater than zero
no stale or invalid approved planning snapshot
```

Preflight remains informational only:

```text
No send action
No execute action
No schedule action
No queue dispatch
No communication-history mutation
No customer mutation
No frozen recipient export
No recipient sample list
```

## Stage 20 Activity Atomicity

New required activity types:

```text
crm_campaign_draft_submitted_for_review
crm_campaign_draft_approved
crm_campaign_draft_rejected
crm_campaign_draft_returned_to_draft
```

Each state mutation, immutable approval-history insert, and required `CrmActivityService::recordOrFail()` insert remains inside the same database transaction boundary.

## Stage 20 Explicitly Not Implemented

```text
SMS sending
Bulk SMS sending
Newsletter sending
Email sending
WhatsApp sending
Campaign execution
Scheduled sending
Background jobs
Queue workers
Cron-based delivery
Third-party messaging adapters
Provider credentials
Provider API calls
Automatic campaign execution
Frozen recipient exports
Recipient sample lists
Automatic customer assignment
Customer merge
Credit enforcement
Due recalculation
Customer CRUD replacement
Hard delete
Tenant middleware changes
Unrelated refactoring
```

## Stage 20 Regression Safety Notes

```text
Existing customer CRUD and Customer 360 flows were not replaced.
Quotation, order, payment, due, ledger, accounting, return, and refund flows were not modified.
Existing SMS, bulk-SMS, newsletter, support-ticket, and contact-request flows were not modified.
Stage 9 Lead Pipeline Board remains untouched.
Stage 10 Task Calendar remains untouched.
Stage 11 Activity Audit Worklist remains untouched.
Stage 12 Customer Health & Risk Worklist remains untouched.
Stage 13 and Stage 14 Duplicate Customer Review flows remain untouched.
Stage 15 Portfolio Segments remains the canonical live recipient-estimation query path.
Stage 16 and Stage 17 Saved Customer Segment flows remain reused rather than replaced.
Stage 18 campaign planning and Stage 19 audience-snapshot integrity remain extended rather than replaced.
TenantDbMiddleware and dynamic tenant database selection remain untouched.
```

## Stage 21 Completed Scope

Stage 21 performs consolidated CRM-wide production hardening only. It introduces no new major CRM feature and does not add campaign execution, scheduling, queues, provider calls, recipient export, or tenant middleware changes.

Completed:

```text
Reverified the Stage 20 campaign approval-governance layer
Preserved the customer-segment eager-load callback TypeError hotfix
Preserved the duplicate-customer Blade backend.master layout hotfix
Applied crm.dashboard,read contextual middleware to /crm-home
Added validated read-only worklist requests for Tasks, Communications, and Leads
Moved required CRM Task activity logs into their existing write transactions
Moved required manual CRM Communication activity logs into their existing write transaction
Moved required CRM Lead activity logs into their existing write transactions
Upgraded required Task, Communication, and Lead activity writes to CrmActivityService::recordOrFail()
Preserved task-completion idempotency: already-completed tasks do not emit duplicate activity rows
Replaced unrestricted $guarded = [] declarations with explicit $fillable allowlists across earlier CRM models
Moved Tags, Tasks, Communications, and Lead worklist DataTables to window.AppAxios transports
Added safe empty DataTable fallbacks when AppAxios or AJAX requests fail
Moved Lead worklist Select2 remote options to the shared window.AppAxios client
Added muted reset-filter reload behavior for Tasks, Communications, and Leads
Added Lead worklist create/update, status-change, and archive duplicate-submit prevention
Added Lead Pipeline Board status-change duplicate-submit prevention
Preserved SweetAlert2, inline HTTP 422 rendering, loading-state cleanup, AJAX DataTables, and Select2 behavior
```

## Stage 21 Files Added

```text
app/Http/Requests/Crm/CrmTaskWorklistRequest.php
app/Http/Requests/Crm/CrmCommunicationWorklistRequest.php
app/Http/Requests/Crm/CrmLeadWorklistRequest.php
```

## Stage 21 Files Modified

```text
CRM_STAGE_TRACKER.md
app/Http/Controllers/Crm/CrmTaskController.php
app/Http/Controllers/Crm/CrmCommunicationController.php
app/Http/Controllers/Crm/CrmLeadController.php
app/Models/Crm/CrmActivity.php
app/Models/Crm/CrmCommunication.php
app/Models/Crm/CrmLead.php
app/Models/Crm/CrmNote.php
app/Models/Crm/CrmSavedCustomerSegment.php
app/Models/Crm/CrmTask.php
app/Models/Crm/CustomerTag.php
app/Services/Crm/CrmTaskService.php
app/Services/Crm/CrmCommunicationService.php
app/Services/Crm/CrmLeadService.php
resources/views/backend/crm/settings/customer-tags/index.blade.php
resources/views/backend/crm/tasks/index.blade.php
resources/views/backend/crm/communications/index.blade.php
resources/views/backend/crm/leads/index.blade.php
resources/views/backend/crm/leads/pipeline.blade.php
routes/dashboardRoutes.php
```

## Stage 21 Database Changes

Stage 21 introduces no migration and no database-schema modification.

```text
New tables: none
New columns: none
Dropped tables: none
Dropped columns: none
New indexes: none
Data backfills: none
```

Existing CRM migrations remain non-destructive and tenant-safe. TenantDbMiddleware and dynamic domain-based tenant database selection remain untouched.

## Stage 21 Atomicity Hardening

Required CRM Task, manual Communication, and Lead audit rows now persist within the same database transaction as their business write:

```text
crm_task_created
crm_task_updated
crm_task_completed
crm_task_archived
crm_communication_logged
crm_lead_created
crm_lead_updated
crm_lead_status_changed
crm_lead_archived
```

If strict activity persistence fails, the associated business write rolls back rather than committing without its required CRM audit trail.

## Stage 21 Explicitly Not Implemented

```text
Campaign execution
SMS delivery from campaign drafts
Newsletter delivery from campaign drafts
Email sending
WhatsApp sending
Scheduled sending
Background jobs
Queue workers
Cron delivery
Third-party provider adapters
Provider credentials
Provider API calls
Frozen recipient exports
Recipient sample lists
Automatic customer assignment
Customer merge
Credit enforcement
Due recalculation
Tenant middleware redesign
Unrelated refactoring
```

## Stage 21 Regression Safety Notes

```text
Customer CRUD and Customer 360 flows remain extended rather than replaced.
Quotation, order, payment, due, ledger, accounting, return, and refund flows remain untouched.
Existing SMS, bulk-SMS, newsletter, support-ticket, and contact-request flows remain untouched.
Campaign planning remains planning-only with no send, execute, or schedule endpoint.
Existing tenant-aware migrations remain untouched.
No destructive migration was introduced.
Legacy adapter-facing CrmCommunicationService::log() intentionally remains best-effort for future verified provider hooks.
Earlier customer-note and customer-tag activity logging behavior remains unchanged because Stage 21 fixes only the confirmed required atomicity gaps.
```

## Stage 22 Completed Scope

Stage 22 adds an ERP-grade, tenant-safe campaign dispatch-preparation boundary only. It freezes approved recipient identity snapshots without sending any message and without introducing provider, queue, schedule, or execution behavior.

Completed:

```text
Added approval-time normalized recipient-set HMAC sealing during independent campaign approval
Preserved Stage 20 legacy approved planning-signature verification while requiring reapproval before legacy approvals can prepare dispatch snapshots
Added explicit draft-only audience snapshot refresh action for stale-audience recovery
Added guarded crm_campaign_dispatch_preparations storage
Added guarded immutable crm_campaign_dispatch_recipients storage
Stored normalized recipient destinations encrypted at rest
Stored deterministic keyed HMAC destination hashes for integrity and duplicate prevention
Blocked recipient normalization failures and duplicate normalized destinations
Blocked same-count recipient membership or destination swaps through exact recipient-set seal comparison
Added active_slot uniqueness for race-safe duplicate active-preparation prevention
Added prepared, cancelled, and invalidated preparation lifecycle only
Added read-only aggregate preparation preview and history
Added reason-required preparation cancellation and invalidation
Added contextual crm.campaign-drafts.prepare-dispatch permission
Used strict CrmActivityService::recordOrFail() events inside preparation transactions
Preserved window.AppAxios, SweetAlert2 confirmations, HTTP 422 rendering, loading states, and double-submit prevention
Kept recipient rows, destinations, samples, exports, provider payloads, and delivery actions out of browser responses
```

## Stage 22 Files Added

```text
app/Http/Controllers/Crm/CrmCampaignDispatchPreparationController.php
app/Http/Requests/Crm/PrepareCrmCampaignDispatchRequest.php
app/Http/Requests/Crm/CancelCrmCampaignDispatchPreparationRequest.php
app/Http/Requests/Crm/InvalidateCrmCampaignDispatchPreparationRequest.php
app/Http/Requests/Crm/RefreshCrmCampaignDraftAudienceSnapshotRequest.php
app/Models/Crm/CrmCampaignDispatchPreparation.php
app/Models/Crm/CrmCampaignDispatchRecipient.php
app/Services/Crm/CrmCampaignRecipientSnapshotService.php
app/Services/Crm/CrmCampaignDispatchPreparationService.php
database/migrations/2026_06_07_000009_create_crm_campaign_dispatch_preparation_foundation.php
```

## Stage 22 Files Modified

```text
CRM_STAGE_TRACKER.md
app/Http/Controllers/Crm/CrmCampaignDraftController.php
app/Models/Crm/CrmCampaignDraft.php
app/Models/Crm/CrmCampaignDraftApprovalHistory.php
app/Services/Crm/CrmCampaignDraftService.php
app/Services/RoleSidebarPermissionService.php
resources/views/backend/crm/campaign-drafts/index.blade.php
routes/crmRoutes.php
```

## Stage 22 Database Changes

Guarded additive columns added to `crm_campaign_drafts` and `crm_campaign_draft_approval_history`:

```text
approved_recipient_set_signature
approved_recipient_set_signature_version
approved_recipient_count
```

New guarded tables:

```text
crm_campaign_dispatch_preparations
crm_campaign_dispatch_recipients
```

Preparation headers preserve approval metadata, frozen-recipient seals, counts, lifecycle state, actors, timestamps, reasons, and aggregate metadata. Immutable recipient rows contain only campaign/preparation/customer identity, channel, encrypted normalized destination, keyed HMAC destination hash, and creation timestamp.

Named indexes and named unique indexes are guarded with index-existence checks. `down()` is intentionally non-destructive.

## Stage 22 Permission Boundary

Contextual permission key:

```text
crm.campaign-drafts.prepare-dispatch
```

Action flags:

```text
read   => view aggregate preparation preview and history
create => freeze an eligible approved campaign recipient snapshot
update => cancel or invalidate an active preparation with an audit reason
delete => unused
```

Creator, reviewer, and dispatch preparer responsibilities remain separately assignable. No sidebar link is added for the contextual permission.

## Stage 22 Required Activity Events

```text
crm_campaign_draft_audience_snapshot_refreshed
crm_campaign_dispatch_prepared
crm_campaign_dispatch_cancelled
crm_campaign_dispatch_invalidated
```

Preparation creation, immutable recipient inserts, frozen seal persistence, and strict activity logging remain inside one transaction. Cancellation and invalidation state changes and strict activity logging also remain transaction-wrapped.

## Stage 22 Explicitly Not Implemented

```text
SMS sending
Bulk SMS sending
Newsletter sending
Email delivery
WhatsApp delivery
Campaign execution
Provider API calls
Provider adapters
Provider credentials
Queue jobs
Queue workers
Background jobs
Scheduled sending
Cron delivery
Retry workers
Webhook handling
Delivery receipts
Provider polling
Recipient export
Recipient sample list
Full recipient-address UI
Automatic customer assignment
Customer merge
Credit enforcement
Due recalculation
Tenant middleware redesign
Unrelated refactoring
```

## Stage 22 Regression Safety Notes

```text
Existing Customer CRUD and Customer 360 flows remain untouched.
Quotation, order, payment, due, ledger, accounting, return, and refund flows remain untouched.
Existing SMS, bulk-SMS, newsletter, support-ticket, and contact-request flows remain untouched.
Stage 20 approval governance remains extended rather than replaced.
Legacy Stage 20 approved campaigns remain readable but must return to draft, refresh, resubmit, and receive independent reapproval before preparation.
TenantDbMiddleware and dynamic domain-based tenant database switching remain untouched.
No destructive migration or automatic legacy recipient-seal backfill is introduced.
```
## Stage 23 Completed Scope

Stage 23 adds an ERP-grade, tenant-safe, provider-neutral campaign dispatch-run release boundary only. It consumes a valid Stage 22 immutable preparation into an auditable one-way dispatch-run ledger without sending, queueing, scheduling, or executing any message.

Completed:

```text
Added guarded crm_campaign_dispatch_runs provider-neutral release-ledger storage
Added guarded immutable crm_campaign_dispatch_run_recipients execution-identity storage
Added guarded preparation consumption metadata: released_dispatch_run_id, released_by, released_at
Kept normalized destination ciphertext only in the immutable Stage 22 preparation recipient table
Copied only keyed destination hashes and immutable preparation-recipient references into run-recipient rows
Required approved campaign lifecycle and verified Stage 20 approved-planning seal at release time
Required exact Stage 22 approval metadata match between campaign and frozen preparation
Required tenant website scope match between campaign, frozen preparation, and immutable recipient rows
Recomputed and verified frozen recipient-row count, identities, channels, keyed hashes, duplicate prevention, and frozen-recipient HMAC seal before release
Avoided live mutable customer re-query during provider-neutral release
Added versioned provider-neutral run-integrity HMAC seal
Consumed released preparations permanently through one-way active-slot clearing and run reference metadata
Blocked duplicate preparation release and concurrent active campaign runs through validation, row locks, and named unique indexes
Blocked new frozen preparation creation while an active released run exists
Blocked return-to-draft while an active released run exists
Added released and cancelled run lifecycle only
Added audit-reason-required cancellation for an active released but unexecuted provider-neutral run
Added aggregate-only release preview and run history UI
Added contextual crm.campaign-drafts.release-dispatch permission
Used strict CrmActivityService::recordOrFail() events inside release and cancellation transactions
Preserved window.AppAxios, SweetAlert2 confirmations, HTTP 422 rendering, loading states, and double-submit prevention
Kept recipient rows, destinations, hashes, samples, exports, provider payloads, and delivery actions out of browser responses
```

## Stage 23 Files Added

```text
app/Http/Controllers/Crm/CrmCampaignDispatchRunController.php
app/Http/Requests/Crm/ReleaseCrmCampaignDispatchRunRequest.php
app/Http/Requests/Crm/CancelCrmCampaignDispatchRunRequest.php
app/Models/Crm/CrmCampaignDispatchRun.php
app/Models/Crm/CrmCampaignDispatchRunRecipient.php
app/Services/Crm/CrmCampaignDispatchRunService.php
database/migrations/2026_06_07_000010_create_crm_campaign_dispatch_run_foundation.php
```

## Stage 23 Files Modified

```text
CRM_STAGE_TRACKER.md
app/Http/Controllers/Crm/CrmCampaignDraftController.php
app/Models/Crm/CrmCampaignDraft.php
app/Models/Crm/CrmCampaignDispatchPreparation.php
app/Models/Crm/CrmCampaignDispatchRecipient.php
app/Services/Crm/CrmCampaignDraftService.php
app/Services/Crm/CrmCampaignDispatchPreparationService.php
app/Services/RoleSidebarPermissionService.php
resources/views/backend/crm/campaign-drafts/index.blade.php
routes/crmRoutes.php
```

## Stage 23 Database Changes

Guarded additive columns added to `crm_campaign_dispatch_preparations`:

```text
released_dispatch_run_id
released_by
released_at
```

New guarded tables:

```text
crm_campaign_dispatch_runs
crm_campaign_dispatch_run_recipients
```

Run headers preserve campaign, preparation, channel, copied approved-planning metadata, copied approval-time recipient-set metadata, copied frozen recipient-set metadata, immutable run-integrity metadata, release actor and timestamp, cancellation actor and timestamp, reason, lifecycle status, active slot, and aggregate metadata.

Run-recipient rows preserve only provider-neutral execution identity: campaign, preparation, run, immutable preparation-recipient reference, customer, channel, keyed destination hash, lifecycle status, and creation timestamp. Destination ciphertext is intentionally not duplicated and remains encrypted at rest only in the immutable Stage 22 preparation-recipient table.

Named indexes and named unique indexes are guarded with index-existence checks. `down()` is intentionally non-destructive.

## Stage 23 Permission Boundary

Contextual permission key:

```text
crm.campaign-drafts.release-dispatch
```

Action flags:

```text
read   => view aggregate release preview and run history
create => release an eligible immutable preparation into a provider-neutral dispatch run
update => cancel an active released but unexecuted provider-neutral run with an audit reason
delete => unused
```

Creator, reviewer, preparer, and releaser responsibilities remain explicitly assignable through permissions. No sidebar link is added for the contextual permission.

## Stage 23 Required Activity Events

```text
crm_campaign_dispatch_released
crm_campaign_dispatch_run_cancelled
```

Approved-planning integrity verification, immutable frozen-row verification, run-header creation, immutable run-recipient insertion, run-integrity sealing, preparation consumption, and strict activity logging remain inside one release transaction. Cancellation state change and strict activity logging also remain transaction-wrapped.

## Stage 23 Explicitly Not Implemented

```text
SMS sending
Bulk SMS sending
Newsletter sending
Email delivery
WhatsApp delivery
Provider API calls
Provider adapters
Provider credentials
Queue jobs
Queue workers
Background jobs
Scheduled sending
Cron delivery
Retry workers
Webhook handling
Delivery receipts
Provider polling
Provider execution claim
Recipient delivery states
Recipient export
Recipient sample list
Full recipient-address UI
Automatic customer assignment
Customer merge
Credit enforcement
Due recalculation
Tenant middleware redesign
Unrelated refactoring
```

## Stage 23 Regression Safety Notes

```text
Existing Customer CRUD and Customer 360 flows remain untouched.
Quotation, order, payment, due, ledger, accounting, return, and refund flows remain untouched.
Existing SMS, bulk-SMS, newsletter, support-ticket, and contact-request flows remain untouched.
Stage 20 approval governance and Stage 22 immutable preparation boundary remain extended rather than replaced.
Release-time integrity verification depends on the frozen preparation boundary and does not re-query mutable customer records.
A released preparation remains immutable and cannot be reused, cancelled, or invalidated.
A cancelled provider-neutral run remains terminal; a fresh preparation is required for a later release.
TenantDbMiddleware and dynamic domain-based tenant database switching remain untouched.
No destructive migration or automatic legacy run backfill is introduced.
```



## Stage 24 Completed Scope

Stage 24 adds an ERP-grade, tenant-safe, provider-neutral campaign dispatch execution-claim boundary only. It claims one eligible Stage 23 released run into an immutable manual execution-batch ledger without sending, queueing, scheduling, or invoking any provider adapter.

Completed:

```text
Added guarded crm_campaign_dispatch_execution_batches provider-neutral execution-header storage
Added guarded immutable crm_campaign_dispatch_execution_recipients provider-neutral execution-identity storage
Added guarded dispatch-run claim metadata: claimed_execution_batch_id, claimed_by, claimed_at
Extended dispatch-run lifecycle from released/cancelled to released/claimed/cancelled
Required explicit controlled claim of an active released run by an authorized user other than the dispatch releaser
Required approved campaign lifecycle and verified Stage 20 approved-planning seal at claim time
Required exact Stage 22 approval-time recipient-set metadata and frozen preparation lineage at claim time
Recomputed and verified Stage 22 frozen-recipient HMAC material and Stage 23 run-integrity HMAC material from immutable ledgers before claim
Validated Stage 24 -> Stage 23 -> Stage 22 source references without live customer or saved-segment re-query
Required immutable source ciphertext presence without decrypting or duplicating destination ciphertext
Copied only keyed destination hashes and immutable run-recipient references into execution-recipient rows
Added versioned provider-neutral execution-integrity HMAC seal
Added hidden server-side batch and recipient idempotency HMAC identities for a future adapter boundary
Copied approved subject and message-body snapshots into the execution-batch header for a durable future server-side contract
Consumed claimed runs permanently through one-way active-slot clearing and execution-batch reference metadata
Blocked duplicate run claims and concurrent active campaign execution batches through validation, row locks, and named unique indexes
Blocked return-to-draft, new preparation creation, and new release creation while an active execution batch exists
Added prepared and cancelled execution-batch lifecycle only
Added audit-reason-required terminal cancellation for a prepared but unconsumed execution batch
Kept a claimed run permanently consumed after execution-batch cancellation; a later attempt requires a fresh preparation and release chain
Added aggregate-only execution preview and history UI
Added contextual crm.campaign-drafts.claim-dispatch-execution permission
Used strict CrmActivityService::recordOrFail() events inside claim and cancellation transactions
Persisted auditable responsibility-overlap metadata while enforcing dispatch releaser != execution claimant
Preserved window.AppAxios, SweetAlert2 confirmations, HTTP 422 rendering, loading states, and double-submit prevention
Kept recipient rows, destinations, hashes, idempotency keys, samples, exports, provider payloads, and delivery actions out of browser responses
```

## Stage 24 Files Added

```text
app/Http/Controllers/Crm/CrmCampaignDispatchExecutionBatchController.php
app/Http/Requests/Crm/ClaimCrmCampaignDispatchExecutionBatchRequest.php
app/Http/Requests/Crm/CancelCrmCampaignDispatchExecutionBatchRequest.php
app/Models/Crm/CrmCampaignDispatchExecutionBatch.php
app/Models/Crm/CrmCampaignDispatchExecutionRecipient.php
app/Services/Crm/CrmCampaignDispatchExecutionBatchService.php
database/migrations/2026_06_07_000011_create_crm_campaign_dispatch_execution_foundation.php
```

## Stage 24 Files Modified

```text
CRM_STAGE_TRACKER.md
app/Http/Controllers/Crm/CrmCampaignDraftController.php
app/Models/Crm/CrmCampaignDraft.php
app/Models/Crm/CrmCampaignDispatchPreparation.php
app/Models/Crm/CrmCampaignDispatchRun.php
app/Models/Crm/CrmCampaignDispatchRunRecipient.php
app/Services/Crm/CrmCampaignDraftService.php
app/Services/Crm/CrmCampaignDispatchPreparationService.php
app/Services/Crm/CrmCampaignDispatchRunService.php
app/Services/RoleSidebarPermissionService.php
resources/views/backend/crm/campaign-drafts/index.blade.php
routes/crmRoutes.php
```

## Stage 24 Database Changes

Guarded additive columns added to `crm_campaign_dispatch_runs`:

```text
claimed_execution_batch_id
claimed_by
claimed_at
```

New guarded tables:

```text
crm_campaign_dispatch_execution_batches
crm_campaign_dispatch_execution_recipients
```

Execution-batch headers preserve campaign, preparation, run, channel, copied Stage 20 approved-planning metadata, copied Stage 22 approval-time and frozen-recipient metadata, copied Stage 23 run-integrity metadata, immutable approved message snapshots, hidden batch idempotency identity, versioned execution-integrity metadata, claim actor and timestamp, cancellation actor and timestamp, reason, lifecycle status, active slot, and aggregate audit metadata.

Execution-recipient rows preserve only provider-neutral execution identity: campaign, preparation, run, execution batch, immutable run-recipient reference, customer, channel, keyed destination hash, hidden recipient idempotency identity, fixed prepared identity status, and creation timestamp. Destination ciphertext is intentionally not duplicated and remains encrypted at rest only in the immutable Stage 22 preparation-recipient table.

Named indexes and named unique indexes are guarded with index-existence checks. `down()` is intentionally non-destructive.

## Stage 24 Permission Boundary

Contextual permission key:

```text
crm.campaign-drafts.claim-dispatch-execution
```

Action flags:

```text
read   => view aggregate execution-claim preview and execution-batch history
create => claim an eligible released run into an immutable provider-neutral execution batch
update => cancel a prepared but unconsumed execution batch with an audit reason
delete => unused
```

Dispatch releaser and execution claimant must be different users. Creator, reviewer, preparer, releaser, and claimant overlap metadata is retained for audit without imposing a four-or-five-person tenant staffing requirement at this non-sending boundary. No sidebar link is added for the contextual permission.

## Stage 24 Required Activity Events

```text
crm_campaign_dispatch_execution_claimed
crm_campaign_dispatch_execution_cancelled
```

Approved-planning integrity verification, Stage 22 frozen-row verification, Stage 23 run-row verification, execution-header creation, immutable execution-recipient insertion, execution-integrity sealing, run consumption, and strict activity logging remain inside one claim transaction. Cancellation verifies the immutable execution seal and keeps the terminal state change and strict activity log inside one transaction.

## Stage 24 Explicitly Not Implemented

```text
SMS sending
Bulk SMS sending
Newsletter sending
Email delivery
WhatsApp delivery
Provider API calls
Provider adapters
Provider credentials
Queue jobs
Queue workers
Background jobs
Scheduled sending
Cron delivery
Automatic execution
Retry workers
Webhook handling
Delivery receipts
Provider polling
Provider-attempt ledger
Recipient delivery states
Delivery-status UI
Recipient export
Recipient sample list
Full recipient-address UI
Automatic customer assignment
Customer merge
Credit enforcement
Due recalculation
Tenant middleware redesign
Unrelated refactoring
```

## Stage 24 Regression Safety Notes

```text
Existing Customer CRUD and Customer 360 flows remain untouched.
Quotation, order, payment, due, ledger, accounting, return, and refund flows remain untouched.
Existing SMS, bulk-SMS, newsletter, support-ticket, and contact-request flows remain untouched.
Stage 20 governance, Stage 22 preparation, and Stage 23 release boundaries remain extended rather than replaced.
Claim-time verification depends on immutable Stage 22 and Stage 23 ledgers and does not re-query mutable customer or saved-segment records.
A claimed run remains immutable and cannot be reused, cancelled through the Stage 23 endpoint, or claimed into a second execution batch.
A cancelled execution batch remains terminal; a fresh Stage 22 preparation and Stage 23 release are required for a later claim.
Active execution-batch checks prevent return-to-draft, new preparation, and new release flows from opening an unsafe parallel chain.
TenantDbMiddleware and dynamic domain-based tenant database switching remain untouched.
No destructive migration or automatic legacy execution-batch backfill is introduced.
No real provider call, queue dispatch, webhook, retry, schedule, or accidental send path is introduced.
```

## Stage 25 Completed Scope

Stage 25 adds a tenant-safe, readiness-only CRM campaign provider-adapter boundary and an immutable manual dispatch-attempt identity ledger. It remains deliberately non-sending. No destination is decrypted and no remote provider, SMS, email, newsletter, queue, retry, webhook, polling, or scheduler path is invoked.

Completed:

```text
Added guarded crm_campaign_dispatch_attempts immutable attempt-header storage
Added guarded crm_campaign_dispatch_recipient_attempts immutable recipient-attempt identity storage
Added attempt recipient_count header for deterministic immutable-row verification
Added prepared and cancelled attempt lifecycle only
Added one-active-attempt-per-execution-batch active-slot uniqueness
Added fail-closed runtime verification for all required Stage 25 unique indexes before preview or writes
Added per-batch monotonic attempt_number uniqueness
Added hidden server-side attempt and recipient-attempt HMAC idempotency identities
Added versioned attempt-integrity HMAC seal over canonicalized provider-neutral snapshot metadata and immutable recipient-attempt identities
Added readiness-only CrmCampaignDispatchProviderAdapter contract without a send or execute method
Added CrmCampaignDispatchProviderRegistry with a local BulkSMSBD SMS readiness adapter only
Validated server-side tenant gateway storage, active website-compatible gateway scope, configured endpoint presence, HTTPS policy, API-key presence, and sender-ID presence
Kept provider endpoint, API key, secrets, gateway records, idempotency keys, snapshots, destination hashes, ciphertext, and recipient identities out of browser payloads
Added full Stage 24 -> Stage 23 -> Stage 22 immutable-lineage revalidation before attempt preparation
Reverified Stage 20 approved-planning seal before attempt preparation
Required attempt preparer != Stage 24 execution claimant
Prepared header, immutable recipient-attempt rows, attempt-integrity seal, and strict CRM activity log inside one DB transaction
Added audit-reason-required cancellation for an active prepared attempt ledger
Blocked Stage 24 execution-batch cancellation while an active Stage 25 attempt exists
Allowed a cancelled Stage 25 attempt to remain immutable while permitting a fresh attempt number against the still-prepared Stage 24 batch
Added aggregate-only provider-readiness preview and attempt history UI through window.AppAxios, SweetAlert2 confirmations, inline HTTP 422 rendering, loading states, and double-submit prevention
Added contextual crm.campaign-drafts.prepare-dispatch-attempt permission
Added required transactional activity events crm_campaign_dispatch_attempt_prepared and crm_campaign_dispatch_attempt_cancelled
```

## Stage 25 Database Changes

New guarded migration:

```text
database/migrations/2026_06_07_000012_create_crm_campaign_dispatch_attempt_foundation.php
```

New guarded tables:

```text
crm_campaign_dispatch_attempts
crm_campaign_dispatch_recipient_attempts
```

Attempt headers preserve tenant website scope, campaign/preparation/run/execution-batch lineage, monotonic attempt number, prepared/cancelled lifecycle, active slot, channel, provider key, safe provider-neutral request-snapshot version and JSON, immutable recipient count, hidden idempotency key, versioned attempt-integrity HMAC, zeroed provider counters, preparation actor and timestamp, future nullable execution-result timestamps, cancellation metadata, safe aggregate metadata, and timestamps.

Recipient-attempt rows preserve immutable identity only: tenant website, campaign/preparation/run/execution-batch references, execution-recipient reference, attempt reference, customer, channel, provider key, prepared status, hidden recipient idempotency key, and creation timestamp.

Not persisted in Stage 25 recipient-attempt rows:

```text
destination ciphertext
destination hash
decrypted destination
provider message ID
provider request payload
provider raw response
provider credentials
```

Named indexes and named unique indexes use index-existence checks. The longest new Stage 25 index identifier is 50 characters, within MySQL's 64-character identifier limit. `down()` is intentionally non-destructive.

## Stage 25 Provider Adapter Boundary

Added readiness-only contract:

```text
App\Contracts\Crm\CampaignDispatch\CrmCampaignDispatchProviderAdapter
```

Added local registry and first readiness adapter:

```text
App\Services\Crm\CrmCampaignDispatchProviderRegistry
App\Services\Crm\CampaignDispatch\BulkSmsBdCampaignDispatchReadinessAdapter
```

The adapter performs local tenant-configuration checks only. It has no send method, does not invoke legacy SMS helpers, does not call `Http::`, does not call the balance endpoint, does not decrypt a recipient destination, and does not expose endpoints or credentials.

Supported readiness channel:

```text
sms => bulksmsbd local readiness only
```

Deferred channels:

```text
email
newsletter
whatsapp
other
```

## Stage 25 Permission Boundary

Contextual permission key:

```text
crm.campaign-drafts.prepare-dispatch-attempt
```

Action flags:

```text
read   => view aggregate local-readiness preview and immutable attempt history
create => prepare a non-sending immutable manual provider-attempt ledger
update => cancel an active prepared non-sending attempt ledger with an audit reason
delete => unused
```

This permission remains separate from:

```text
crm.campaign-drafts.claim-dispatch-execution
```

A future real provider execution permission remains deferred and is not exposed by Stage 25:

```text
crm.campaign-drafts.execute-dispatch
```

## Stage 25 Required Activity Events

```text
crm_campaign_dispatch_attempt_prepared
crm_campaign_dispatch_attempt_cancelled
```

Strict activity logging uses `CrmActivityService::recordOrFail()` inside the same DB transaction as each write.

## Stage 25 Explicitly Not Implemented

```text
Real SMS sending
Bulk-SMS provider execution
Email delivery
Newsletter delivery
WhatsApp delivery
Remote connectivity validation
Balance endpoint calls
Destination decryption
Provider request transmission
Provider response recording
Provider exchange ledger
Provider polling
Provider webhooks
Delivery receipts
Queue jobs
Queue workers
Retry workers
Scheduled campaign delivery
Cron-triggered campaign execution
Automatic batch consumption
Recipient export
Recipient sample list
Full destination-address UI
Provider credential browser UI
Customer merge
Credit enforcement
Due recalculation
Tenant middleware redesign
Unrelated refactoring
```

## Stage 25 Regression Safety Notes

```text
Existing Customer CRUD and Customer 360 flows remain untouched.
Quotation, order, payment, due, ledger, accounting, return, and refund flows remain untouched.
Existing legacy SMS, bulk-SMS, due-SMS, newsletter, support-ticket, and contact-request flows remain untouched.
Stage 20 approval governance and Stages 22–24 immutable campaign boundaries remain extended rather than replaced.
Attempt preparation revalidates Stage 24 -> Stage 23 -> Stage 22 immutable lineage and never re-queries mutable campaign audiences.
Attempt preparation never decrypts the Stage 22 ciphertext.
Stage 24 execution-batch cancellation now fails closed while an active Stage 25 attempt exists.
A cancelled Stage 25 attempt remains immutable; a later preparation allocates a new attempt number and identity ledger.
TenantDbMiddleware and dynamic domain-based tenant database switching remain untouched.
No destructive migration or automatic legacy backfill is introduced.
```

## Stage 26B Completed Scope

Stage 26B keeps CRM campaign delivery deliberately non-sending while closing the discovered public debug-route blockers and adding a hardened local BulkSMSBD protocol-readiness boundary for the future first bounded SMS execution stage.

Completed:

```text
Removed the public /tp authentication-bypass debug route
Removed the public /dump-autoload hard-coded SMS-send debug route
Fail-closed legacy browser-triggered maintenance, tenant-migration, source-unzip, schema-append, and profit-recalculation routes outside an explicitly enabled local environment
Added default-disabled ALLOW_LOCAL_UNSAFE_WEB_MAINTENANCE_ROUTES configuration gate
Converted the backend cache-clear button from public GET to authenticated CSRF-protected POST
Removed exception-handler auto-dispatch of /append-columns and replaced it with a fail-closed tenant-aware CLI migration instruction
Added tenants:migrate --force CLI command with optional --tenant and --pretend scopes
Added BulkSmsBdCampaignGatewayResolver for active website-compatible tenant gateway selection, ambiguity rejection, HTTPS-only validation, endpoint-shape validation, API-key presence, and sender-ID presence
Added BulkSmsBdCampaignSmsProtocol as a local-only non-sending pure policy layer
Added future one-recipient-per-POST-request boundary
Added future server-side maximum recipient cap = 5
Added future connect timeout = 3 seconds and total timeout = 8 seconds
Added future timeout-unknown and no-automatic-retry policy metadata
Added Bangladeshi destination normalization and validation utility for a later approved execution adapter
Added conservative SMS segment-estimate utility for a later approved execution adapter
Added allowlisted, redacted, size-limited provider-response snapshot utility for a later approved execution adapter
Added guarded append-only crm_campaign_dispatch_recipient_attempt_events foundation
Added duplicate request-sequence and terminal-result uniqueness guards
Extended aggregate-only Stage 25 readiness preview with non-sending Stage 26B policy visibility
Kept send_available=false and execute_available=false
Kept credentials, endpoints, destinations, recipient rows, provider payloads, and provider responses out of browser payloads
```

## Stage 26B Database Changes

New guarded migration:

```text
database/migrations/2026_06_07_000013_create_crm_campaign_dispatch_recipient_attempt_event_foundation.php
```

New guarded append-only table:

```text
crm_campaign_dispatch_recipient_attempt_events
```

The table stores future server-side provider exchange audit events only: tenant website, campaign, attempt, recipient-attempt and execution-batch lineage; event type; provider key; request sequence; terminal slot; safe status; optional provider reference and response code; redacted response summary; response hash; request timing; failure category; redacted failure summary; safe metadata; and creation timestamp.

It intentionally does not store:

```text
plaintext destination
destination ciphertext copy
provider credential
API token
raw provider request body
raw provider response body
browser-visible provider payload
updated_at
```

Named indexes and named unique indexes use guarded existence checks. `down()` is intentionally non-destructive.

## Stage 26B Route Safety Gate

The following public debug routes were removed:

```text
GET /tp
GET /dump-autoload
```

The following legacy browser maintenance routes are fail-closed by default and register only when both the application environment is `local` and `ALLOW_LOCAL_UNSAFE_WEB_MAINTENANCE_ROUTES=true` is explicitly configured:

```text
GET /run-db-clear
GET /api/migrate-tenants
GET /append-columns
GET /upzip-sourcecode
GET /update-all-tentants-db
GET /update-all-tentants-db/status/{jobId}
GET /calculate-profit
GET /clear
```

Production deployments should use authenticated admin actions or CLI commands for required maintenance operations. The existing backend cache-clear action remains available through authenticated CSRF-protected `POST /clear/cache`. Tenant migrations can be applied safely through `php artisan tenants:migrate --force`.

## Stage 26B Explicitly Not Implemented

```text
Real SMS sending
Email sending
Newsletter sending
WhatsApp sending
Remote BulkSMSBD connectivity validation
Provider balance calls
Destination decryption
Provider request transmission
Provider response persistence from a live call
Campaign execute endpoint
Campaign execute permission
Automatic retries
Manual retries
Queue workers
Cron execution
Scheduled execution
Provider polling
Webhooks
Delivery receipt mutation
Recipient export
Recipient sample UI
Credential UI
Legacy SMS rewrite
Legacy bulk-SMS rewrite
Legacy due-SMS rewrite
Tenant middleware redesign
```

## Stage 26B Regression Safety Notes

```text
Stage 20 governance and Stages 22-25 immutable campaign ledgers remain extended rather than replaced.
Existing Customer CRUD, Customer 360, quotation, order, payment, due, ledger, accounting, return, refund, support-ticket, and contact-request flows remain untouched.
Existing legacy SMS, bulk-SMS, due-SMS, and newsletter implementations remain unchanged and are not reused by the future CRM execution adapter.
TenantDbMiddleware and dynamic domain-based tenant database switching remain untouched.
No destination is decrypted and no provider call is made by Stage 26B.
No destructive migration or automatic legacy event backfill is introduced.
A future first real-send stage must remain SMS-only, manually confirmed, server-side capped at 5 recipients, one recipient per HTTPS POST request, append-only audited, and auto-retry disabled.
```

## Stage 27 Completed Scope

Stage 27 introduces the first deliberately narrow real provider execution path for CRM campaigns. It extends the existing immutable Stage 20 -> 26B chain rather than replacing legacy messaging flows.

Enabled execution boundary:

```text
Real-send channel: SMS only
Execution adapter: BulkSMSBD only
Operator action: explicit manual action only
Typed confirmation phrase: SEND SMS
Maximum recipients per attempt: 5
Provider request boundary: one immutable recipient per HTTPS POST
Connect timeout: 3 seconds
Total timeout: 8 seconds
Automatic retry: disabled
Browser resend after timeout or interrupted execution: disabled
Queue, scheduler, webhook, polling, and delivery-receipt mutation: disabled
Email, newsletter, and WhatsApp delivery: disabled
```

Completed:

```text
Added crm.campaign-drafts.execute-dispatch as a separate contextual execute permission
Added a route-order-safe numeric nested POST execute endpoint
Added a Form Request requiring the exact typed phrase SEND SMS
Added a thin execution controller
Added an execution-only server-side BulkSMSBD gateway DTO
Added a separate CRM SMS execution adapter contract and immutable provider-neutral result DTO
Added one isolated BulkSMSBD HTTPS POST execution adapter
Disabled HTTP redirect following so configured credentials cannot be forwarded across provider redirects
Added a single supported sms + bulksmsbd execution registry
Kept the Stage 25 readiness-only contract separate from the execution contract
Extended the pure BulkSMSBD protocol utility with type=text payload shape, strict Bangladeshi mobile validation, explicit response_code=202 success classification, SHA-256 raw-response hashing, allowlisted redacted summaries, and size limits
Added destination decryption only inside the Stage 27 execution boundary
Added canonical Stage 22 normalization and keyed destination-hash recheck before provider-specific phone formatting
Added two-pass ephemeral destination verification: verify all bounded recipients before any provider request, then decrypt and verify again immediately before each one-recipient POST
Discarded plaintext destination and sensitive payload references in finally blocks
Added full Stage 20 -> 25 immutable lineage verification before execution starts
Added responsibility-separation hard blocks for campaign approver, Stage 24 claimant, and Stage 25 attempt preparer
Recorded overlap metadata for campaign creator, Stage 22 preparer, and Stage 23 releaser without exposing destination data
Extended attempt lifecycle with processing, completed, partially_failed, and failed terminal states
Recorded required aggregate strict CRM activities with CrmActivityService::recordOrFail()
Committed request_started exchange-ledger events before provider contact
Recorded exactly one append-only terminal event per bounded recipient request sequence
Recorded timeout and interrupted uncertain outcomes as unknown with no automatic resend
Recorded interrupted-before-request recipients as safely failed without inventing a provider request-start event
Added browser-retry reconciliation that never issues another provider POST
Added a permanent execution-consumption guard preventing fresh Stage 25 preparation after real execution starts
Added a Stage 24 cancellation/reuse guard after any real execution start
Updated aggregate-only browser UI with typed SweetAlert2 confirmation, window.AppAxios, loading state, double-submit prevention, and inline HTTP 422 errors
Kept recipient destinations, destination samples, hashes, ciphertext, credentials, endpoint URLs, provider payloads, raw responses, and per-recipient outcomes out of browser payloads
```

## Stage 27 Database Changes

No new migration is required.

Stage 27 reuses the existing guarded Stage 25 attempt header fields:

```text
status
active_slot
provider_request_count
provider_success_count
provider_failure_count
provider_unknown_count
started_by
started_at
completed_at
failed_at
failure_summary
```

Stage 27 also reuses the guarded append-only Stage 26B table:

```text
crm_campaign_dispatch_recipient_attempt_events
```

Existing uniqueness guards remain the database-level concurrency protection:

```text
crm_campaign_dispatch_rec_events_seq_type_unique
crm_campaign_dispatch_rec_events_seq_terminal_unique
```

The event ledger intentionally stores no plaintext destination, destination ciphertext copy, API key, sender credential, raw request body, or raw response body. Existing non-destructive migration rollback behavior remains unchanged.

## Stage 27 Permission Boundary

New contextual permission:

```text
crm.campaign-drafts.execute-dispatch
```

Action used by the execution route:

```text
create => execute one manually confirmed bounded BulkSMSBD SMS attempt
```

This permission remains separate from:

```text
crm.campaign-drafts.claim-dispatch-execution
crm.campaign-drafts.prepare-dispatch-attempt
```

Hard responsibility-separation blocks:

```text
executor != campaign approver
executor != Stage 24 execution claimant
executor != Stage 25 attempt preparer
```

## Stage 27 Append-Only Provider Exchange Boundary

For each bounded immutable recipient identity:

```text
commit request_started before contacting the provider
send one HTTPS POST outside a database transaction
append exactly one recipient_succeeded, recipient_failed, or recipient_unknown terminal event
persist a safe allowlisted redacted summary and SHA-256 raw-response hash only when a provider response exists
never persist raw request or raw response bodies
never retry automatically
```

Timeout or interrupted outcomes that may have reached the provider remain unknown. A duplicate browser execute request never creates another provider request.

## Stage 27 Required Activity Events

```text
crm_campaign_dispatch_attempt_started
crm_campaign_dispatch_attempt_completed
crm_campaign_dispatch_attempt_partially_failed
crm_campaign_dispatch_attempt_failed
```

Strict aggregate activity logging uses `CrmActivityService::recordOrFail()` inside the same DB transaction as the corresponding attempt state mutation. Per-recipient provider outcomes remain in the append-only exchange ledger rather than the general CRM activity table.

## Stage 27 Explicitly Not Implemented

```text
Email delivery
Newsletter delivery
WhatsApp delivery
Multi-provider CRM execution
Bulk provider endpoint execution
Automatic retries
Manual resend or retry action
Retry workers
Queue workers
Scheduled execution
Cron execution
Provider polling
Webhook mutation
Delivery receipt mutation
Recipient export
Recipient destination UI
Credential browser UI
Legacy SMS helper rewrite
Legacy bulk-SMS rewrite
Legacy due-SMS rewrite
Tenant middleware redesign
Customer merge
Credit enforcement
Due recalculation
Unrelated refactoring
```

## Stage 27 Regression Safety Notes

```text
Existing Customer CRUD, Customer 360, quotation, order, payment, due, ledger, accounting, return, refund, support-ticket, and contact-request flows remain untouched.
Existing legacy SMS, bulk-SMS, due-SMS, and newsletter implementations remain unchanged and are never invoked by the new CRM execution adapter.
The real CRM execution path is isolated behind a dedicated contract, adapter, contextual permission, nested POST route, exact typed phrase, and immutable lineage verification.
Provider requests are never made while a database transaction remains open.
The browser receives aggregate attempt status and counters only.
TenantDbMiddleware and dynamic domain-based tenant database switching remain untouched.
No destructive migration, schema drop, automatic legacy backfill, queue, scheduler, webhook, polling, or retry worker is introduced.
```

## Stage 28 Completed Scope

Stage 28 rationalizes the CRM campaign surface to the two channels that should remain visible to operators while preserving the bounded Stage 27 execution boundary.

User-facing active campaign choices:

```text
BulkSMSBD SMS
Email
```

Stored compatibility values remain unchanged:

```text
sms
email
```

Completed:

```text
Relabeled sms as BulkSMSBD SMS in campaign filters, forms, details, and worklist presentation
Kept Email visible for draft planning, audience preview, independent governance review, and approval
Removed Newsletter, WhatsApp, and Other from new-draft and active-filter UI choices
Preserved historical newsletter, whatsapp, and other rows non-destructively as legacy-disabled read-only records
Blocked forged create and update requests for legacy-disabled channel values
Blocked governance transitions for legacy-disabled historical channel rows
Restricted Stage 22 dispatch preparation advancement to BulkSMSBD SMS only
Restricted Stage 23 dispatch-run release advancement to BulkSMSBD SMS only
Restricted Stage 24 execution-batch claim advancement to BulkSMSBD SMS only
Restricted Stage 25 provider-attempt preparation advancement to BulkSMSBD SMS only
Kept the Stage 27 sms + bulksmsbd execution adapter and typed SEND SMS path unchanged
Added explicit Email planning-only notices in campaign presentation and preflight output
Kept Email real dispatch disabled until dedicated tenant-safe email readiness and execution stages
```

## Stage 28 Database Changes

No migration is required.

Stage 28 performs no destructive schema change, no legacy-row rewrite, no automatic backfill, and no table, column, or index mutation. Historical campaign channel rows remain readable with their original stored values.

## Stage 28 Channel Boundary

```text
BulkSMSBD SMS:
  planning enabled
  independent approval enabled
  bounded manual real send enabled

Email:
  planning enabled
  independent approval enabled
  real send disabled

Newsletter:
  legacy historical read-only presentation only
  new planning disabled
  dispatch disabled

WhatsApp:
  legacy historical read-only presentation only
  new planning disabled
  dispatch disabled

Other:
  legacy historical read-only presentation only
  new planning disabled
  dispatch disabled
```

## Stage 28 Explicitly Not Implemented

```text
Real email delivery
SMTP resolver hardening
SMTP credential redesign
Email execution adapter
Email queue worker
Newsletter dispatch
WhatsApp dispatch
Multi-provider SMS execution
Manual SMS retry
Automatic retry
Scheduled execution
Cron execution
Webhook mutation
Provider polling
Delivery receipt mutation
Legacy newsletter rewrite
Legacy SMS rewrite
Tenant middleware redesign
```

## Stage 28 Regression Safety Notes

```text
Stage 27 BulkSMSBD SMS execution remains the only enabled CRM real-send path.
The sms and email database values remain unchanged for compatibility.
Legacy newsletter, whatsapp, and other campaign rows are not deleted or rewritten.
Existing legacy SMS, bulk-SMS, due-SMS, and newsletter implementations remain untouched.
TenantDbMiddleware and dynamic domain-based tenant database switching remain untouched.
No migration, destructive cleanup, queue, scheduler, webhook, polling, or retry worker is introduced.
```

## Stage 29 Completed Scope

Stage 29 adds a non-sending CRM Email readiness foundation without replacing legacy SMTP behavior or enabling real CRM Email delivery.

Completed:

```text
Added a CRM-only tenant SMTP readiness resolver
Added a provider-neutral Email readiness adapter registered beside the existing BulkSMSBD readiness adapter
Kept Email campaign planning and independent approval available even when SMTP readiness is incomplete
Exposed safe informational local SMTP readiness in the Email campaign preflight UI
Kept real CRM Email dispatch, queueing, scheduling, retry, and connectivity probing disabled
Selected only a server-side password-present boolean during SMTP readiness inspection; the stored password is never selected or exposed
Removed the stored password from the legacy Email credential DataTable payload and exposed a Configured (hidden) status column
Removed the stored password from the legacy Email credential edit-info JSON response
Added EmailConfigure model-level JSON hiding for password as defense in depth
Preserved Stage 27 bounded BulkSMSBD SMS execution unchanged
```

## Stage 29 Database Changes

No migration is required. Stage 29 reuses the existing `email_configures` table in read-only mode for CRM Email readiness evaluation.

```text
No table created
No table dropped
No column added
No column dropped
No password conversion
No credential backfill
```

## Stage 29 SMTP Safety Boundary

The CRM-only Email readiness resolver verifies local tenant configuration safely:

```text
email_configures table and required columns exist
exactly one active website-scoped or tenant-wide SMTP configuration resolves unambiguously
server-side SMTP host is present
SMTP port is between 1 and 65535
server-side SMTP username is present
server-side SMTP password is present without selecting or exposing the secret
encryption mode is one of none, TLS, or SSL
sender address is valid when configured
sender name is configured or the application name fallback exists
```

The browser receives allowlisted status metadata only. It never receives SMTP password, raw SMTP row, endpoint value, provider payload, or provider response. No remote SMTP connectivity probe is performed.

## Stage 29 Explicitly Not Implemented

```text
Real CRM Email sending
SMTP connectivity probe
Mail::send invocation from CRM campaign execution
Email execution endpoint
Email execute permission
Email delivery ledger mutation
Queue worker
Scheduled send
Cron send
Automatic retry
Manual resend
Email template engine
Attachment support
Open tracking
Click tracking
Bounce handling
Webhook mutation
Credential encryption migration
Legacy order-email rewrite
Legacy verification-email rewrite
Legacy newsletter rewrite
Tenant middleware redesign
```

## Stage 29 Regression Safety Notes

```text
Existing Customer CRUD, Customer 360, quotation, order, payment, due, ledger, accounting, return, refund, support-ticket, and contact-request flows remain untouched.
Existing legacy invoice, order, verification, ecommerce, and newsletter mail behavior remains untouched.
Existing SMTP password storage format remains unchanged to avoid legacy-mail regressions.
Stage 27 BulkSMSBD bounded real-SMS execution remains SMS-only and unchanged.
Email SMTP readiness is informational only and does not participate in Stage 20 governance pass/fail calculation.
Email remains blocked from Stage 22 preparation, Stage 23 release, Stage 24 claim, Stage 25 attempt preparation, and Stage 27 execution.
No destructive migration or schema change is introduced.
```


## Stage 30 Completed Scope

Stage 30 adds a non-sending CRM Email SMTP protocol-hardening boundary. It prepares a future tightly controlled execution adapter without enabling any real CRM Email delivery.

Completed:

```text
Added a CRM-only pure Email SMTP protocol policy layer
Added a dormant fail-closed future Email execution-only SMTP configuration resolver
Tightened read-only SMTP readiness checks for safe host, username, effective sender-address fallback, and bounded sender-name metadata
Kept the Stage 29 read-only readiness resolver separate from the future execution-only secret-loading boundary
Added a future server-only Email SMTP configuration DTO
Added a future single-recipient CRM Email command DTO
Added a future provider-neutral CRM Email result DTO
Reused the canonical Stage 22 Email destination normalization rule for future protocol validation
Validated future Email subject presence and bounded subject length
Validated future bounded Email message-body length
Rejected CR or LF header-injection characters from future sender metadata and subject values
Mapped only the recognized SMTP encryption modes: none, TLS, and SSL
Defined a conservative server-side maximum of 10 recipients for one future manually confirmed Email attempt
Defined future one-recipient-per-transport-call behavior with no automatic truncation
Defined no automatic retry after uncertain SMTP outcomes
Defined no browser resend after an interrupted future SMTP execution
Defined safe provider-neutral future result categories without raw SMTP exception exposure
Exposed aggregate-only Stage 30 Email policy-readiness metadata in the existing read-only campaign preflight UI
Kept send_available=false, execute_available=false, and execution_transport_available=false for Email
Kept all Email dispatch advancement blocked
Preserved Stage 27 bounded BulkSMSBD SMS execution unchanged
```

## Stage 30 Database Changes

No migration is required. Stage 30 adds application-layer policy services and future-only DTOs without mutating tenant data or schema.

```text
No table created
No table dropped
No column added
No column dropped
No index added
No credential conversion
No data backfill
```

## Stage 30 Email Safety Boundary

```text
CRM Email planning: enabled
CRM Email independent approval: enabled
Local SMTP readiness inspection: enabled and read only
Remote SMTP connectivity probe: disabled
CRM mail transport invocation: disabled
CRM Email execution route: absent
CRM Email execute permission: absent
Email send_available: false
Email execute_available: false
Email execution_transport_available: false
Future recipient cap metadata: 10 recipients per manually confirmed attempt
Future transport command shape: exactly one normalized recipient per call
Automatic retry after uncertain outcome: disabled
Browser resend after interrupted execution: disabled
```

## Stage 30 Explicitly Not Implemented

```text
Real CRM Email delivery
Mail::send invocation from CRM campaign execution
Mailer or SMTP transport adapter registration
Email execution endpoint
Email typed SEND confirmation
Email execute permission
Email dispatch-ledger mutation
Destination decryption for Email sending
SMTP connectivity probe
Queue worker
Scheduled delivery
Cron execution
Automatic retry
Manual resend
Attachment support
HTML template engine
Open tracking
Click tracking
Bounce processing
Webhook mutation
Legacy newsletter rewrite
Legacy order-email rewrite
Legacy verification-email rewrite
SMTP password encryption migration
Tenant middleware redesign
```

## Stage 30 Regression Safety Notes

```text
Stage 29 read-only SMTP password safety remains intact; readiness inspection still never selects the password.
The new execution-only SMTP secret resolver is dormant and is not called by controllers, readiness adapters, or browser-facing flows.
Existing legacy invoice, order, verification, ecommerce, newsletter, SMS, bulk-SMS, and due-SMS behavior remains untouched.
Stage 27 BulkSMSBD bounded real-SMS execution remains the only CRM real-send path.
Email remains blocked from Stage 22 preparation, Stage 23 release, Stage 24 claim, Stage 25 attempt preparation, and Stage 27 execution.
No route, permission, migration, queue, scheduler, webhook, polling, retry worker, or tenant middleware change is introduced.
```

## Stage 31 Completed Scope

Stage 31 enables Email campaigns to advance through the existing immutable CRM campaign dispatch ledgers without enabling real Email transport execution. BulkSMSBD SMS remains the only real CRM send path.

Completed:

```text
Synchronized the tracker Current Status table with the already-present Stage 30 completion section
Added an explicit dispatch-ledger channel boundary separate from real transport channels
Kept real transport channels SMS-only
Allowed approved SMTP-ready Email campaigns to pass Stage 22 immutable dispatch preparation
Allowed Email preparations to pass Stage 23 provider-neutral dispatch-run release
Allowed Email released runs to pass Stage 24 execution-batch claim
Allowed Email execution batches to prepare Stage 25 manual provider-attempt ledger records
Kept Email attempt metadata non-sending with provider_call_available=false
Kept Email attempt payload send_available=false, execute_available=false, and execution_transport_available=false
Used the existing Email SMTP readiness adapter for local Email provider-readiness checks
Used the Stage 30 Email protocol recipient cap of 10 for Email attempt eligibility
Preserved the existing BulkSMSBD SMS recipient cap and real execution boundary
Preserved the existing generic execute route as SMS-only and BulkSMSBD-only
Preserved destination, provider payload, provider response, and credential browser-exposure blocks
Preserved all responsibility-separation guards across preparation, release, claim, and attempt preparation
```

## Stage 31 Database Changes

No migration is required. Stage 31 reuses the existing immutable dispatch-ledger tables added in earlier stages.

```text
No table created
No table dropped
No column added
No column dropped
No index added
No credential conversion
No data backfill
```

## Stage 31 Email Safety Boundary

```text
CRM Email planning: enabled
CRM Email independent approval: enabled
Local SMTP readiness inspection: enabled and read only
Email Stage 22 dispatch preparation: enabled as non-sending ledger
Email Stage 23 dispatch-run release: enabled as non-sending ledger
Email Stage 24 execution-batch claim: enabled as non-sending ledger
Email Stage 25 provider-attempt preparation: enabled as non-sending ledger
Remote SMTP connectivity probe: disabled
CRM mail transport invocation: disabled
CRM Email execution route: absent
Email typed SEND confirmation: absent
Email execute permission: absent
Email provider_call_available: false
Email send_available: false
Email execute_available: false
Email execution_transport_available: false
Automatic retry: disabled
Browser resend: disabled
```

## Stage 31 Explicitly Not Implemented

```text
Real CRM Email delivery
Mail::send invocation from CRM campaign execution
Mailer or SMTP transport adapter registration
Email execution endpoint
Email typed SEND confirmation
Email execute permission
Destination decryption for Email sending
SMTP connectivity probe
Queue worker
Scheduled delivery
Cron execution
Automatic retry
Manual resend
Open tracking
Click tracking
Bounce processing
Webhook mutation
Legacy newsletter rewrite
SMTP password encryption migration
Tenant middleware redesign
```

## Stage 31 Regression Safety Notes

```text
Existing Customer CRUD, Customer 360, quotation, order, payment, due, ledger, accounting, return, refund, support-ticket, contact-request, SMS, bulk-SMS, newsletter, and sidebar flows remain untouched.
Stage 27 BulkSMSBD bounded real-SMS execution remains the only CRM real-send path.
The generic CRM campaign execute route remains fail-closed for Email because the execution service still requires channel=sms and provider_key=bulksmsbd.
Stage 29 and Stage 30 SMTP password safety remains intact; browser-facing readiness still never selects or exposes SMTP password values.
No destructive migration or schema change is introduced.
```



## Stage 32 Completed Scope

Stage 32 closes the stable CRM release with a targeted Stage 31 browser-access regression fix, operator-facing wording synchronization, route-comment correction, and a production handover document. No new CRM transport capability is enabled.

Completed:

```text
Reinspected the latest uploaded full-project ZIP as the only implementation baseline
Detected and repaired a Stage 31 UI gating regression in CrmCampaignDraftService
Separated dispatch-ledger UI availability from real-send transport availability in draft payloads
Allowed Email drafts to expose Stage 22 preparation, Stage 23 release, Stage 24 claim, and Stage 25 attempt-preview actions in the browser when permissions and lifecycle checks pass
Kept SMS as the only real-send channel
Kept the generic execute endpoint fail-closed for Email because execution still requires channel=sms and provider_key=bulksmsbd
Added dispatch_ledger_enabled aggregate metadata without exposing destinations, credentials, payloads, responses, or endpoint URLs
Made attempt-preview notice wording channel-aware for Email non-sending ledgers and BulkSMSBD SMS real-send boundaries
Updated the attempt-ledger modal title, loading text, preparation confirmation, success fallback, and Email policy panel
Updated the top campaign-page boundary notice to the Stage 32 stable release boundary
Corrected the stale campaign-route comment without changing route definitions
Added CRM_STABLE_RELEASE_HANDOVER.md at project root
```

## Stage 32 Root Cause Fixed

```text
Stage 31 backend services correctly allowed Email to advance through immutable dispatch ledgers.
CrmCampaignDraftService::payload() still gated ledger preview URLs and action booleans with isRealSendChannel().
Because REAL_SEND_CHANNELS contains only sms, Email ledger actions were not exposed in the campaign browser worklist.
Stage 32 introduces dispatchLedgerEnabled = isDispatchLedgerChannel() for ledger-only actions and URLs.
The real-send flag remains unchanged and SMS-only.
```

## Stage 32 Files Added

```text
CRM_STABLE_RELEASE_HANDOVER.md
```

## Stage 32 Files Modified

```text
CRM_STAGE_TRACKER.md
app/Services/Crm/CrmCampaignDraftService.php
app/Services/Crm/CrmCampaignDispatchAttemptService.php
resources/views/backend/crm/campaign-drafts/index.blade.php
routes/crmRoutes.php
```

## Stage 32 Database Changes

No migration is required. Stage 32 does not mutate tenant data or schema.

```text
No table created
No table dropped
No column added
No column dropped
No index added
No credential conversion
No data backfill
```

## Stage 32 Stable CRM Transport Boundary

```text
CRM campaign planning channels: BulkSMSBD SMS and Email
Immutable dispatch-ledger channels: BulkSMSBD SMS and Email
Real transport execution channels: BulkSMSBD SMS only
Enabled real provider adapter: BulkSMSBD only
Email ledger advancement: enabled as non-sending preparation, release, claim, and attempt ledgers
Email provider_call_available: false
Email send_available: false
Email execute_available: false
Email execution_transport_available: false
Remote SMTP connectivity probe: disabled
Automatic retry: disabled
Browser resend: disabled
Queue and scheduled delivery: disabled
```

## Stage 32 Explicitly Not Implemented

```text
Real CRM Email delivery
SMTP mail transport invocation
Email execution endpoint
Email typed SEND confirmation
Email execute permission
Queue worker
Scheduled delivery
Cron execution
Automatic retry
Manual resend
WhatsApp campaign
Additional SMS providers
Open tracking
Click tracking
Bounce processing
Webhook mutation
Legacy newsletter rewrite
Tenant middleware redesign
```

## Stage 32 Regression Safety Notes

```text
Existing Customer CRUD, Customer 360, quotation, order, payment, due, ledger, accounting, return, refund, support-ticket, contact-request, SMS, bulk-SMS, newsletter, and sidebar flows remain untouched.
Stage 27 bounded BulkSMSBD SMS execution remains the only CRM real-send path.
Email browser access is restored only for immutable non-sending ledger steps.
The generic execute route remains present for SMS and fail-closed for Email in CrmCampaignDispatchExecutionService.
No route name, route middleware, controller namespace, permission key, migration, schema, credential-storage, queue, scheduler, or tenant middleware behavior is changed.
```

---

# Stage 33 — CRM Bilingual User Manual and Sidebar Entry ✅ Complete

## Stage 33 Objective

Add a clear operator-facing CRM user manual in a tutorial style, with a left-side index and right-side detailed bilingual English/Bangla instructions, and expose it as the final CRM menu item.

## Stage 33 Completed Scope

```text
Added a read-only CRM User Manual page.
Added bilingual English and Bangla manual content.
Added sticky left index and right-side tutorial detail layout.
Added coverage for CRM overview, customers, Customer 360, leads, tasks, communications, activities, health, duplicates, segments, campaigns, SMS/newsletter, permissions, and troubleshooting.
Added CRM User Manual as the last menu item inside CRM & CUSTOMERS.
Added route permission key: crm.user-manual.
Added crm.user-manual to canonical CRM permission keys.
```

## Stage 33 Added Files

```text
app/Http/Controllers/Crm/CrmUserManualController.php
resources/views/backend/crm/user-manual/index.blade.php
```

## Stage 33 Modified Files

```text
CRM_STAGE_TRACKER.md
app/Http/Helpers/BackendSidebarHelper.php
app/Services/RoleSidebarPermissionService.php
routes/crmRoutes.php
```

## Stage 33 Database Changes

```text
No migration added
No table created
No table dropped
No column added
No column dropped
No index added
No data backfill
No destructive schema change
```

## Stage 33 Safety Notes

```text
The manual page is read-only.
The manual route uses auth, DemoMode, and crm.sidebar.permission middleware.
No customer, order, payment, due, accounting, SMS, newsletter, support-ticket, campaign, lead, task, communication, or activity data is mutated.
Existing CRM campaign transport boundaries remain unchanged.
BulkSMSBD SMS remains the only bounded CRM real-send transport.
Email remains non-sending ledger-only in CRM Campaign Drafts.
```

---

# Stage 34 — CRM Tutorial Manual UX Expansion with Separate Bangla and English Versions ✅ Complete

## Stage 34 Objective

Upgrade the Stage 33 CRM user manual into a beginner-friendly operator tutorial with separate Bangla and English versions, practical examples, expected results, safer campaign explanations, improved navigation, and a final User Module permission guide.

## Stage 34 Completed Scope

```text
Preserved the existing read-only CRM User Manual sidebar entry as the last item inside CRM & CUSTOMERS.
Preserved the existing GET /crm/user-manual route and crm.user-manual.index route name.
Changed the default CRM manual page to the beginner-friendly Bangla version.
Added explicit Bangla and English version routes with a visible language switcher.
Replaced dense bilingual side-by-side content with one-language-at-a-time tutorial pages.
Added 15 tutorial sections with actual menu paths, step-by-step instructions, practical examples, expected results, tips, and warnings.
Added coverage for CRM overview, customer setup, Customer 360, leads, tasks, communications, activity audit, health, duplicate review, segments, legacy contact management, governed campaign drafts, bounded BulkSMSBD SMS execution, Email ledger-only boundary, legacy SMS/newsletter, troubleshooting, and the final User Module permission guide.
Added quick-start cards, sticky searchable left index, shell-scoped scroll progress indicator, active-section highlighting, mobile-responsive layout, daily operator checklist, troubleshooting table, and back-to-top links.
Kept the controller thin by moving localized manual content into a dedicated service class.
Kept User Management and permission setup as the final tutorial section.
Constrained the manual shell to the available viewport height and bound progress calculation plus active-section observation to the shell scroll container.
```

## Stage 34 Added Files

```text
app/Services/Crm/CrmUserManualContentService.php
```

## Stage 34 Modified Files

```text
CRM_STAGE_TRACKER.md
app/Http/Controllers/Crm/CrmUserManualController.php
resources/views/backend/crm/user-manual/index.blade.php
routes/crmRoutes.php
```

## Stage 34 Routes Added

```text
GET /crm/user-manual/bn
    crm.user-manual.bn

GET /crm/user-manual/en
    crm.user-manual.en
```

Existing route preserved:

```text
GET /crm/user-manual
    crm.user-manual.index
```

## Stage 34 Database Changes

```text
No migration added
No table created
No table dropped
No column added
No column dropped
No index added
No data backfill
No destructive schema change
```

## Stage 34 Safety Notes

```text
All manual routes remain read-only GET routes.
All manual routes use auth, DemoMode, and crm.sidebar.permission:crm.user-manual,read middleware.
No customer, order, payment, due, ledger, accounting, SMS, newsletter, support-ticket, campaign, lead, task, communication, activity, sidebar-permission, or tenant database record is mutated.
The Stage 27 BulkSMSBD SMS bounded real-send boundary remains unchanged: maximum five recipients, typed SEND SMS confirmation, one recipient per HTTPS POST, append-only attempt events, and no automatic retry.
CRM Email campaign execution remains intentionally disabled and ledger-only.
Legacy SMS, newsletter, scheduled-contact, order, accounting, support, and sidebar flows remain preserved.
```
