You are working on an existing Laravel-based multi-tenant ERP/POS project.

Your task is to upgrade the existing CRM foundation into a professional ERP-grade CRM. Work slowly, safely, and stage-by-stage. Never implement multiple major stages at once.

IMPORTANT:
- Inspect the latest uploaded project ZIP before every stage.
- Do not replace existing customer, order, payment, due, accounting, SMS, newsletter, contact-request, or support-ticket flows.
- Extend existing tables and services safely.
- Do not cause data loss.
- Do not start the next stage until I write: next.
- At the end of each stage, provide a root-extractable ZIP patch.

==================================================
PROJECT CONTEXT
==================================================

The Laravel ERP/POS system is multi-tenant. The tenant database is switched dynamically based on the tenant domain. All new migrations must be tenant-safe.

Existing CRM-related foundations include:

Customers
E-commerce Customers
Customer Categories
Customer Source Types
Contact History
Scheduled Contacts
Customer Payments
Opening Balances
Orders
Quotations
Returns
Refunds
Support Tickets
Newsletter Subscribers
Contact Requests
SMS
Customer Ledger
Customer Due Reports

Existing customer-payment and accounting logic must be preserved. Do not rewrite or bypass existing accounting services.

Existing sidebar config:

app/Http/Helpers/BackendSidebarHelper.php

Existing sidebar renderer:

resources/views/backend/sidebar.blade.php
resources/views/backend/sidebarWithAssignedMenu.blade.php

Role-wise sidebar permissions may load from DB and cache. Use stable explicit permission keys for new CRM items. Preserve fallback compatibility for existing generated keys.

Shared Axios instance already exists. All new AJAX requests must reuse:

window.AppAxios

Never create repeated Axios configurations in CRM pages.

==================================================
MANDATORY CODING RULES
==================================================

Laravel + PHP
Multi-tenant safe migrations
Thin controllers
Business logic in services
Form Request validation
Authorization middleware or policies
Axios-only AJAX
Shared AppAxios instance reuse
SweetAlert2 confirmation and feedback
AJAX DataTable where suitable
Select2 for large dropdowns
Inline 422 validation errors
Loading state
Double-submit prevention
Responsive and user-friendly Blade UI
DB transaction for multi-table operations
Try/catch and rollback where required
Log critical failures
No unnecessary page reload
No destructive migration
No hard delete unless explicitly approved
No unrelated code removal

Every new migration must use Schema::hasTable() and Schema::hasColumn() guards where relevant.

==================================================
WORKFLOW FOR EVERY STAGE
==================================================

For each stage:

1. Extract and inspect the latest ZIP.
2. Inspect all relevant existing files.
3. Map reusable logic.
4. Identify duplicate table, column, route, namespace, and permission risks.
5. Show the exact implementation scope.
6. Implement only that stage.
7. Run PHP syntax checks.
8. Review tenant migration safety.
9. Review existing functionality regression risk.
10. Create a root-extractable ZIP patch.
11. Provide changed-files list.
12. Provide apply commands.
13. Provide a manual testing checklist.
14. Provide known limitations.
15. Stop and wait for my “next” command.

Never jump to the next stage automatically.

If any existing error is found:
- Do not ignore it.
- Explain the root cause.
- Fix it within the relevant compatibility patch.
- Retest before moving forward.

==================================================
STAGE PIPELINE
==================================================

Stage 0 — Existing CRM Audit
- Inspect existing schema, models, controllers, routes, sidebar, permissions, shared Axios, payment flow, due flow, order relations, contact history, scheduled follow-ups, SMS, newsletter, support tickets.
- Do not modify code.
- Deliver an architecture map, risks, reusable files, gaps, and the exact next-stage scope.

Stage 0.5 — Stabilization and Compatibility Repair
- Fix critical CRM runtime errors and schema mismatches.
- Verify non-admin sidebar rendering.
- Add stable explicit sidebar permission_key support with legacy fallback.
- Review unsafe debug/test production routes separately.
- Provide a root-extractable compatibility ZIP patch.

Stage 1 — CRM Database Foundation
- Safely extend customers only with missing CRM summary fields.
- Add:
  crm_customer_tags
  crm_customer_tag_pivots
  crm_tasks
  crm_activities
  crm_notes
  crm_communications
- Add models, relations, and service foundations.
- Do not build full UI yet.

Stage 2 — CRM Settings Masters
Create AJAX CRUD screens:
- Customer Types
- Customer Tags
- Lifecycle Stages
- Task Types
- Task Priorities
- Lead Sources
- Lead Statuses
- Pipeline Stages
- Lost Reasons

Stage 3 — Customer 360 Profile
Build AJAX-loaded profile tabs:
- Overview
- Contact Information
- Addresses
- Contact Persons
- Tags
- Orders
- Quotations
- Payments
- Due and Advance
- Returns and Refunds
- Tasks
- Follow-ups
- Communications
- Support Tickets
- Notes
- Activity Timeline

Stage 4 — Tasks and Follow-ups
Build:
- Today’s Tasks
- Upcoming Tasks
- Overdue Tasks
- Completed Tasks
- My Tasks
- Team Tasks
- Task Calendar
- Add Task
- Assign Staff
- Mark Complete
- Reschedule
- Completion Note
- Create Next Follow-up

Stage 5 — Lead Management
Create crm_leads.
Support:
- New
- Assigned
- Contacted
- Interested
- Follow-up
- Converted
- Lost
Lead conversion must preserve timeline, notes, tasks, and customer links inside a DB transaction.

Stage 6 — Opportunities and Sales Pipeline
Create:
- crm_pipeline_stages
- crm_opportunities
- crm_opportunity_histories
Build Axios drag-and-drop Kanban board.
Log every stage change.

Stage 7 — Credit Control
Integrate existing customer payment, due, order, and accounting flow.
Implement:
- Due Customers
- Overdue Customers
- Credit Limit Monitor
- Blocked Customers
- Due Aging Report
Rules:
- allow_due false → block due sale
- blocked customer → restrict order
- credit_limit exceed → warning or configurable block
- payment_terms_days exceed → overdue flag
- authorized override → reason log

Stage 8 — Duplicate Detection and Merge
Detect:
- Exact phone
- Normalized phone
- Email
- Similar phone
- Same name + address
- Linked e-commerce identity
Merge:
- Orders
- Payments
- Opening balances
- Addresses
- Contact persons
- Contact histories
- Scheduled contacts
- Tasks
- Notes
- Communications
- Activities
- Tickets
- Tags
- Leads
- Opportunities
Use DB transaction and immutable merge logs.
Archive duplicate records; do not hard delete.

Stage 9 — Unified Communication History
Centralize:
- SMS
- Email
- WhatsApp
- Phone Call
- Manual Note
- System Notification
- Support Reply
- Campaign Message
Use adapters to log existing sending flows without breaking them.

Stage 10 — Campaigns
Create:
- crm_campaigns
- crm_campaign_audiences
- crm_campaign_messages
- crm_campaign_logs
Support audience filters and SMS/email campaign logs.

Stage 11 — Dashboard and Reports
Dashboard:
- Total Customers
- New Customers
- New Leads
- Converted Leads
- Open Opportunities
- Pipeline Value
- Today’s Follow-ups
- Overdue Follow-ups
- High Due Customers
- Inactive Customers

Reports:
- Customer 360 Report
- Lead Conversion Report
- Follow-up Performance
- Pipeline Report
- Due Aging Report
- VIP Customer Report
- Inactive Customer Report
- Source Report
- Communication Report
- Campaign Report
- Salesperson Performance

Stage 12 — Final Hardening and User Manuals
- Full regression testing
- Admin and non-admin permission tests
- Existing tenant test
- Fresh tenant test
- Mobile UI test
- Queue test
- Axios error handling test
- Generate Bangla and English CRM user manuals
- Provide final release ZIP patch and deployment checklist

==================================================
STABLE PERMISSION KEY EXAMPLES
==================================================

crm.dashboard
crm.customers.list
crm.customers.create
crm.customers.edit
crm.customers.profile
crm.customers.import
crm.tasks.list
crm.tasks.create
crm.tasks.complete
crm.tasks.calendar
crm.leads.list
crm.leads.create
crm.leads.convert
crm.pipeline.list
crm.pipeline.board
crm.pipeline.stage-change
crm.credit.due
crm.credit.overdue
crm.credit.monitor
crm.credit.block
crm.duplicates.list
crm.duplicates.merge
crm.communications.list
crm.communications.send
crm.campaigns.list
crm.campaigns.create
crm.campaigns.send
crm.reports.summary
crm.reports.export
crm.settings.tags
crm.settings.lifecycle
crm.settings.task-types
crm.settings.lead-sources
crm.settings.pipeline-stages

==================================================
ZIP PATCH RULES
==================================================

Every ZIP patch must be project-root extractable.

Example paths:

app/Http/Controllers/Crm/...
app/Http/Requests/Crm/...
app/Models/Crm/...
app/Services/Crm/...
database/migrations/...
database/seeders/...
resources/views/backend/crm/...
routes/crmRoutes.php
app/Http/Helpers/BackendSidebarHelper.php
routes/web.php

Inspect an existing file before overwriting it.
Do not remove unrelated code.
Use surgical changes where possible.

==================================================
STAGE COMPLETION RESPONSE FORMAT
==================================================

# Stage X Completion Report

## Completed Scope
## Files Added
## Files Modified
## Database Changes
## Reused Existing Logic
## Syntax Check Result
## Migration Safety Review
## Existing Functionality Regression Review
## ZIP Patch Download
## Apply Commands
## Manual Testing Checklist
## Known Limitations
## Next Recommended Stage

Status:
Stage X ✅ Complete
Next Stage ⏳ Awaiting “next”

==================================================
FIRST ACTION
==================================================

Start only with Stage 0.5 — Stabilization and Compatibility Repair.

Before modifying code:
- Reinspect the latest uploaded ZIP.
- Show the exact Stage 0.5 files you need to modify.
- Explain the risks.
- Then implement Stage 0.5 only.