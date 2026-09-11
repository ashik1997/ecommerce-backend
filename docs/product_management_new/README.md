# Product Management v3 Planning Docs

Last updated: 2026-06-07

## Purpose

This directory is the implementation handoff for the next product creation and editing system.

The new system must be built under:

```text
/product-management/v3
```

The existing product management module is stable and must remain untouched except for read-only reference during analysis.

## Hard Rules

```text
Do not modify the existing product-management create/edit routes.
Do not reuse App\Http\Controllers\ProductManagement\ProductManagementController for v3.
Do not reuse the existing huge product_create_vue.js or product_edit_vue.js as runtime dependencies.
Do not couple create and edit into duplicated files.
Do not submit the entire product form as one giant payload.
Do not let one tab failure block unrelated tab data.
Do not delete reusable media when a picker field is cleared.
```

## v3 Direction

Product Management v3 is a new, modular, chunk-based product builder.

Create mode:

```text
Only Basic Info is enabled first.
Basic Info creates the product row and returns product_id.
All other tabs stay disabled until product_id exists.
Locked tab click shows a small warning.
After product_id exists, each tab acts as its own independent form.
```

Edit mode:

```text
Product already exists.
All tabs are enabled from the start.
Each tab can be saved independently.
```

## Documents

Read these in order:

```text
00_existing_audit.md
01_v3_architecture.md
02_backend_plan.md
03_frontend_plan.md
04_tab_contracts.md
05_global_media_manager.md
06_database_and_model_notes.md
07_implementation_stages.md
08_quality_and_regression_checklist.md
```

## Implementation Philosophy

The preferred pattern is:

```text
One route = one action.
One action = one small controller or action class.
One tab = one request object + one service method + one response contract.
One frontend section = one component + one focused Pinia store or store slice.
```

Controllers should stay thin. Business logic belongs in services/actions.

## Existing Stable Module Reference

Existing stable files can be inspected for behavior only:

```text
routes/productManagementRoutes.php
app/Http/Controllers/ProductManagement/ProductManagementController.php
resources/views/backend/product_management/create.blade.php
resources/views/backend/product_management/edit.blade.php
public/assets/js/product_create_vue.js
public/assets/js/product_edit_vue.js
resources/views/backend/product_management/tabs/*
```

Do not make v3 depend on these files.

