# FBM-01 — Module Shell, Sidebar and Permissions

Implemented: 2026-06-09

## Scope implemented

FBM-01 adds the tenant-context backend shell for the ERP `FB MARKETING` module. It introduces authenticated routes, dedicated controllers, permission-filtered sidebar navigation and placeholder-only pages. It does not introduce Meta credentials, token fields, Graph API requests, sync, reports or ecommerce changes.

## FBM-00 blocker repair applied before FBM-01

The uploaded package contained the FBM-00 tracker and documentation, but static verification found three packaged-source regressions:

```text
routes/web.php hardcoded $allowLocalUnsafeWebMaintenanceRoutes = true
public/info.php was present
public/error_log was present
```

The tracker also referenced `scripts/fbm-security-gate.sh`, but that file was absent from the uploaded ZIP.

FBM-01 repaired these blockers before module work continued:

```text
Unsafe browser maintenance routes again require APP_ENV=local plus ALLOW_LOCAL_UNSAFE_WEB_MAINTENANCE_ROUTES=true
public/info.php removed
public/error_log removed
scripts/fbm-security-gate.sh restored and executed
```

## Route map

All routes are registered through `routes/fbMarketingRoutes.php`, required by the existing `routes/web.php` backend flow. The global `TenantDbMiddleware` remains active because it is part of the HTTP kernel middleware stack.

| Method | URL | Route name | Controller | Required permissions |
| --- | --- | --- | --- | --- |
| GET | `/fb-marketing` | `fbMarketing.index` | `FbMarketingDashboardController@index` | `fb_marketing_access` + `fb_marketing_dashboard_view` |
| GET | `/fb-marketing/dashboard` | `fbMarketing.dashboard` | `FbMarketingDashboardController@index` | `fb_marketing_access` + `fb_marketing_dashboard_view` |
| GET | `/fb-marketing/configuration` | `fbMarketing.configuration.index` | `FbMarketingConfigurationController@index` | `fb_marketing_access` + `fb_marketing_configuration_view` |
| GET | `/fb-marketing/user-manual` | `fbMarketing.user-manual.index` | `FbMarketingUserManualController@index` | `fb_marketing_access` + `fb_marketing_user_manual_view` |

Route group middleware:

```text
auth
CheckUserType
DemoMode
fb-marketing.sidebar.permission:fb_marketing_access,read
```

Every page adds its page-specific `fb-marketing.sidebar.permission` middleware.

## Permission keys

```text
fb_marketing_access
fb_marketing_dashboard_view
fb_marketing_configuration_view
fb_marketing_user_manual_view
```

The project already stores role permissions in `role_sidebar_permissions.permissions_json` and filtered sidebar snapshots in `role_sidebar_permissions.sidebar_json`. FBM-01 reuses that architecture. No permission migration or seeder is required: the new keys enter the existing role editor through `BackendSidebarHelper` and are persisted when an operator saves role permissions.

Admin users (`user_type = 1`) retain the existing full-access bypass. Non-admin users require the top-level `fb_marketing_access` read permission and the matching page read permission.

## Sidebar visibility rules

The top-level sidebar module is:

```text
FB MARKETING
```

Visible submenus:

```text
Dashboard
Configuration
User Manual
```

Future roadmap menus are intentionally not rendered in FBM-01.

For staff users, the filtered sidebar includes the module only when `fb_marketing_access.read = true`. Each submenu is included only when its own page permission has `read = true`. The route middleware independently enforces the same boundary, so hiding a sidebar item is not the only security control.

## Placeholder UI pages

Created:

```text
resources/views/backend/fb-marketing/dashboard.blade.php
resources/views/backend/fb-marketing/configuration.blade.php
resources/views/backend/fb-marketing/user-manual.blade.php
resources/views/backend/fb-marketing/_status-card.blade.php
```

Each page uses the existing backend master layout and includes:

```text
Page title
Breadcrumb
Module status card
Current stage notice
Upcoming later-stage notice
No real credential form
No token field
No Meta API call
No mock report values
```

## Tenant-context verification

```text
TenantDbMiddleware remains in app/Http/Kernel.php global middleware.
FBM routes use the existing web backend route flow, so tenant resolution runs before controller work.
FBM-01 adds no query, static cache, tenant identifier or central-database assumption.
FBM-01 adds no credential, token, app secret or provider ID to source.
Existing role-sidebar cache remains tenant-key namespaced.
```

## Changed files

Added:

```text
routes/fbMarketingRoutes.php
app/Http/Middleware/FbMarketingSidebarPermission.php
app/Http/Controllers/Backend/FbMarketing/FbMarketingDashboardController.php
app/Http/Controllers/Backend/FbMarketing/FbMarketingConfigurationController.php
app/Http/Controllers/Backend/FbMarketing/FbMarketingUserManualController.php
resources/views/backend/fb-marketing/_status-card.blade.php
resources/views/backend/fb-marketing/dashboard.blade.php
resources/views/backend/fb-marketing/configuration.blade.php
resources/views/backend/fb-marketing/user-manual.blade.php
scripts/fbm-security-gate.sh
docs/fb-marketing/stages/STAGE_01_MODULE_SHELL_SIDEBAR_AND_PERMISSIONS.md
```

Updated:

```text
routes/web.php
app/Http/Kernel.php
app/Http/Helpers/BackendSidebarHelper.php
app/Services/RoleSidebarPermissionService.php
FB_MARKETING_STAGE_TRACKER.md
docs/fb-marketing/ARCHITECTURE.md
docs/fb-marketing/DB_MAP.md
docs/fb-marketing/SECURITY_GATE.md
```

Removed as FBM-00 blocker repair:

```text
public/info.php
public/error_log
```

## Validation results

Passed:

```text
Changed PHP files syntax lint
Static route registration review
Controller namespace/import review
Permission key review
Unauthorized direct URL boundary review
Sidebar permission filtering review
Tenant middleware/context review
Blade directive balance review
Secret leakage grep
Debug and unsafe route regression scan
Migration safety review: no migration added
bash scripts/fbm-security-gate.sh
ZIP integrity test
```

Not runnable in uploaded source package:

```text
php artisan route:list
Framework boot smoke tests
```

Reason: the uploaded source package omits `artisan` and Composer `vendor/autoload.php`. Run framework boot and route-list smoke tests in the deployment checkout after restoring dependencies.

## Known limitations

```text
Existing non-admin roles do not automatically receive FB MARKETING access. Grant the four read permissions as appropriate from the existing role-sidebar permission editor.
FBM-01 is intentionally a shell only.
Encrypted tenant-database credential vault begins in FBM-02.
No Meta access token, app secret, Graph client, campaign sync or reporting logic exists yet.
Previously exposed credentials from FBM-00 still require external rotation outside source control.
```

## Regression notes

```text
No existing ecommerce logic changed.
No existing CRM permission key changed.
Existing role-sidebar behavior remains unchanged for modules that do not opt into module-level read enforcement.
FB MARKETING opts into module-level read enforcement through explicit sidebar metadata.
```

## Next exact command

```text
FBM-02 implement করুন: Encrypted Database Credential Vault
```
