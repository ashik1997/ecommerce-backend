<?php

namespace App\Services;

use App\Models\RoleSidebarPermission;
use App\Models\User;
use App\Models\UserRolePermission;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class RoleSidebarPermissionService
{
    /**
     * Canonical FB MARKETING shell permissions introduced in FBM-01.
     */
    public const FB_MARKETING_PERMISSION_KEYS = [
        'fb_marketing_access',
        'fb_marketing_dashboard_view',
        'fb_marketing_performance_view',
        'fb_marketing_catalog_view',
        'fb_marketing_product_performance_view',
        'fb_marketing_profitability_view',
        'fb_marketing_boosting_jobs_view',
        'fb_marketing_audiences_view',
        'fb_marketing_campaign_drafts_view',
        'fb_marketing_lead_ads_view',
        'fb_marketing_alerts_view',
        'fb_marketing_recommendations_view',
        'fb_marketing_reports_view',
        'fb_marketing_creative_library_view',
        'fb_marketing_tracking_view',
        'fb_marketing_attribution_reports_view',
        'fb_marketing_setup_wizard_view',
        'fb_marketing_configuration_view',
        'fb_marketing_user_manual_view',
        'fb_marketing_configuration_manage',
        'fb_marketing_credentials_manage',
        'fb_marketing_connection_health_test',
        'fb_marketing_asset_discovery_run',
        'fb_marketing_asset_selection_manage',
        'fb_marketing_sync_run',
        'fb_marketing_catalog_sync_run',
        'fb_marketing_catalog_mapping_manage',
        'fb_marketing_capi_diagnostic_run',
        'fb_marketing_capi_event_dispatch',
        'fb_marketing_capi_event_retry',
        'fb_marketing_order_attribution_reconcile',
        'fb_marketing_attribution_reports_reconcile',
        'fb_marketing_profitability_adjustment_manage',
        'fb_marketing_boosting_jobs_manage',
        'fb_marketing_boosting_job_ledger_manage',
        'fb_marketing_audience_manage',
        'fb_marketing_audience_selection_manage',
        'fb_marketing_audience_sync_run',
        'fb_marketing_campaign_draft_manage',
        'fb_marketing_campaign_draft_submit',
        'fb_marketing_campaign_draft_approve',
        'fb_marketing_campaign_publish_create',
        'fb_marketing_campaign_operational_action_create',
        'fb_marketing_alert_reconcile',
        'fb_marketing_recommendation_refresh',
        'fb_marketing_recommendation_decide',
        'fb_marketing_report_export_create',
        'fb_marketing_report_export_download',
        'fb_marketing_creative_asset_manage',
        'fb_marketing_creative_preflight_run',
    ];

    /**
     * Assignable FB MARKETING permissions without dedicated sidebar links.
     * Credential mutations remain contextual actions inside Configuration.
     */
    public const CONTEXTUAL_FB_MARKETING_PERMISSION_ITEMS = [
        [
            'key' => 'fb_marketing_configuration_manage',
            'title' => 'Manage Isolated FB Marketing Module Settings',
            'module_key' => 'fb_marketing_access',
            'group_key' => 'fb_marketing_configuration_view',
        ],
        [
            'key' => 'fb_marketing_credentials_manage',
            'title' => 'Manage Encrypted FB Marketing Credentials',
            'module_key' => 'fb_marketing_access',
            'group_key' => 'fb_marketing_configuration_view',
        ],
        [
            'key' => 'fb_marketing_connection_health_test',
            'title' => 'Run Read-only FB Marketing Connection Health Test',
            'module_key' => 'fb_marketing_access',
            'group_key' => 'fb_marketing_configuration_view',
        ],
        [
            'key' => 'fb_marketing_asset_discovery_run',
            'title' => 'Run Read-only FB Marketing Meta Asset Discovery',
            'module_key' => 'fb_marketing_access',
            'group_key' => 'fb_marketing_configuration_view',
        ],
        [
            'key' => 'fb_marketing_asset_selection_manage',
            'title' => 'Manage Local FB Marketing Active Asset Selection',
            'module_key' => 'fb_marketing_access',
            'group_key' => 'fb_marketing_dashboard_view',
        ],
        [
            'key' => 'fb_marketing_sync_run',
            'title' => 'Queue Read-only FB Marketing Sync Runs',
            'module_key' => 'fb_marketing_access',
            'group_key' => 'fb_marketing_configuration_view',
        ],
        [
            'key' => 'fb_marketing_catalog_sync_run',
            'title' => 'Run Read-only FB Marketing Catalog Mapping Refresh',
            'module_key' => 'fb_marketing_access',
            'group_key' => 'fb_marketing_configuration_view',
        ],
        [
            'key' => 'fb_marketing_catalog_mapping_manage',
            'title' => 'Manage Local FB Marketing Catalog Product Mapping Overrides',
            'module_key' => 'fb_marketing_access',
            'group_key' => 'fb_marketing_catalog_view',
        ],
        [
            'key' => 'fb_marketing_capi_diagnostic_run',
            'title' => 'Run FB Marketing CAPI Dry-run or Explicit Test Diagnostic',
            'module_key' => 'fb_marketing_access',
            'group_key' => 'fb_marketing_tracking_view',
        ],
        [
            'key' => 'fb_marketing_capi_event_dispatch',
            'title' => 'Queue FB Marketing CAPI Event Delivery',
            'module_key' => 'fb_marketing_access',
            'group_key' => 'fb_marketing_tracking_view',
        ],
        [
            'key' => 'fb_marketing_capi_event_retry',
            'title' => 'Retry FB Marketing CAPI Event Without Queue',
            'module_key' => 'fb_marketing_access',
            'group_key' => 'fb_marketing_tracking_view',
        ],
        [
            'key' => 'fb_marketing_order_attribution_reconcile',
            'title' => 'Reconcile Recent ERP Order Attribution Snapshots',
            'module_key' => 'fb_marketing_access',
            'group_key' => 'fb_marketing_tracking_view',
        ],
        [
            'key' => 'fb_marketing_attribution_reports_reconcile',
            'title' => 'Reconcile ERP Order Attribution From Attribution Reports',
            'module_key' => 'fb_marketing_access',
            'group_key' => 'fb_marketing_attribution_reports_view',
        ],
        [
            'key' => 'fb_marketing_profitability_adjustment_manage',
            'title' => 'Manage Local FB Marketing Campaign Cost Adjustments',
            'module_key' => 'fb_marketing_access',
            'group_key' => 'fb_marketing_profitability_view',
        ],
        [
            'key' => 'fb_marketing_boosting_jobs_manage',
            'title' => 'Manage FB Marketing Boosting Jobs',
            'module_key' => 'fb_marketing_access',
            'group_key' => 'fb_marketing_boosting_jobs_view',
        ],
        [
            'key' => 'fb_marketing_boosting_job_ledger_manage',
            'title' => 'Manage FB Marketing Boosting Job Ledger Entries',
            'module_key' => 'fb_marketing_access',
            'group_key' => 'fb_marketing_boosting_jobs_view',
        ],
        [
            'key' => 'fb_marketing_audience_manage',
            'title' => 'Manage FB Marketing Local Audience Plans',
            'module_key' => 'fb_marketing_access',
            'group_key' => 'fb_marketing_audiences_view',
        ],
        [
            'key' => 'fb_marketing_audience_selection_manage',
            'title' => 'Manage FB Marketing Audience and Product-set Selection',
            'module_key' => 'fb_marketing_access',
            'group_key' => 'fb_marketing_audiences_view',
        ],
        [
            'key' => 'fb_marketing_audience_sync_run',
            'title' => 'Run Read-only FB Marketing Audience Sync',
            'module_key' => 'fb_marketing_access',
            'group_key' => 'fb_marketing_audiences_view',
        ],
        [
            'key' => 'fb_marketing_campaign_draft_manage',
            'title' => 'Manage FB Marketing Campaign Drafts',
            'module_key' => 'fb_marketing_access',
            'group_key' => 'fb_marketing_campaign_drafts_view',
        ],
        [
            'key' => 'fb_marketing_campaign_draft_submit',
            'title' => 'Submit FB Marketing Campaign Drafts for Approval',
            'module_key' => 'fb_marketing_access',
            'group_key' => 'fb_marketing_campaign_drafts_view',
        ],
        [
            'key' => 'fb_marketing_campaign_draft_approve',
            'title' => 'Approve or Reject FB Marketing Campaign Drafts',
            'module_key' => 'fb_marketing_access',
            'group_key' => 'fb_marketing_campaign_drafts_view',
        ],
        [
            'key' => 'fb_marketing_campaign_publish_create',
            'title' => 'Create Controlled FB Marketing Campaign Publish Attempts',
            'module_key' => 'fb_marketing_access',
            'group_key' => 'fb_marketing_campaign_drafts_view',
        ],
        [
            'key' => 'fb_marketing_campaign_operational_action_create',
            'title' => 'Create Controlled FB Marketing Operational Actions',
            'module_key' => 'fb_marketing_access',
            'group_key' => 'fb_marketing_campaign_drafts_view',
        ],
        [
            'key' => 'fb_marketing_alert_reconcile',
            'title' => 'Run FB Marketing Ad-account Reconciliation and Alert Refresh',
            'module_key' => 'fb_marketing_access',
            'group_key' => 'fb_marketing_alerts_view',
        ],
        [
            'key' => 'fb_marketing_recommendation_refresh',
            'title' => 'Refresh FB Marketing Controlled Recommendations',
            'module_key' => 'fb_marketing_access',
            'group_key' => 'fb_marketing_recommendations_view',
        ],
        [
            'key' => 'fb_marketing_recommendation_decide',
            'title' => 'Approve or Dismiss FB Marketing Recommendations',
            'module_key' => 'fb_marketing_access',
            'group_key' => 'fb_marketing_recommendations_view',
        ],
        [
            'key' => 'fb_marketing_report_export_create',
            'title' => 'Create FB Marketing Report Exports',
            'module_key' => 'fb_marketing_access',
            'group_key' => 'fb_marketing_reports_view',
        ],
        [
            'key' => 'fb_marketing_report_export_download',
            'title' => 'Download FB Marketing Report Exports',
            'module_key' => 'fb_marketing_access',
            'group_key' => 'fb_marketing_reports_view',
        ],
        [
            'key' => 'fb_marketing_creative_asset_manage',
            'title' => 'Manage FB Marketing Local Creative Assets',
            'module_key' => 'fb_marketing_access',
            'group_key' => 'fb_marketing_creative_library_view',
        ],
        [
            'key' => 'fb_marketing_creative_preflight_run',
            'title' => 'Run FB Marketing Creative Preflight Checks',
            'module_key' => 'fb_marketing_access',
            'group_key' => 'fb_marketing_creative_library_view',
        ],
    ];

    /**
     * Canonical CRM permission keys. Operational keys are exposed only after
     * matching routes and UI exist; future keys remain hidden until then.
     */
    public const RESERVED_CRM_PERMISSION_KEYS = [
        'crm.customers.profile',
        'crm.leads.list',
        'crm.tasks.list',
        'crm.tasks.create',
        'crm.tasks.complete',
        'crm.tasks.calendar',
        'crm.communications.list',
        'crm.activities.list',
        'crm.customer-health.list',
        'crm.customer-segments.list',
        'crm.saved-customer-segments.list',
        'crm.campaign-drafts.list',
        'crm.campaign-drafts.approve',
        'crm.campaign-drafts.prepare-dispatch',
        'crm.campaign-drafts.release-dispatch',
        'crm.campaign-drafts.claim-dispatch-execution',
        'crm.campaign-drafts.prepare-dispatch-attempt',
        'crm.campaign-drafts.execute-dispatch',
        'crm.duplicate-customers.list',
        'crm.settings.tags',
        'crm.user-manual',
    ];

    /**
     * Assignable CRM permissions without dedicated sidebar links. Customer
     * profile access is contextual; task write permissions extend the worklist.
     */
    public const CONTEXTUAL_CRM_PERMISSION_ITEMS = [
        [
            'key' => 'crm.customers.profile',
            'title' => 'Customer 360 Profile',
            'module_key' => 'crm',
            'group_key' => 'crm.customers',
        ],
        [
            'key' => 'crm.tasks.create',
            'title' => 'Create and Edit CRM Tasks',
            'module_key' => 'crm',
            'group_key' => 'crm.tasks',
        ],
        [
            'key' => 'crm.tasks.complete',
            'title' => 'Complete CRM Tasks',
            'module_key' => 'crm',
            'group_key' => 'crm.tasks',
        ],
        [
            'key' => 'crm.campaign-drafts.approve',
            'title' => 'Approve CRM Campaign Drafts',
            'module_key' => 'crm',
            'group_key' => 'crm.communications',
        ],
        [
            'key' => 'crm.campaign-drafts.prepare-dispatch',
            'title' => 'Prepare CRM Campaign Dispatch Snapshot',
            'module_key' => 'crm',
            'group_key' => 'crm.communications',
        ],
        [
            'key' => 'crm.campaign-drafts.release-dispatch',
            'title' => 'Release Provider-Neutral CRM Campaign Dispatch Run',
            'module_key' => 'crm',
            'group_key' => 'crm.communications',
        ],
        [
            'key' => 'crm.campaign-drafts.claim-dispatch-execution',
            'title' => 'Claim Provider-Neutral CRM Campaign Dispatch Execution Batch',
            'module_key' => 'crm',
            'group_key' => 'crm.communications',
        ],
        [
            'key' => 'crm.campaign-drafts.prepare-dispatch-attempt',
            'title' => 'Prepare Non-Sending CRM Campaign Provider Attempt Ledger',
            'module_key' => 'crm',
            'group_key' => 'crm.communications',
        ],
        [
            'key' => 'crm.campaign-drafts.execute-dispatch',
            'title' => 'Execute Bounded BulkSMSBD CRM Campaign SMS Attempt',
            'module_key' => 'crm',
            'group_key' => 'crm.communications',
        ],
    ];

    protected array $actions = ['create', 'read', 'update', 'delete'];

    public function reservedCrmPermissionKeys(): array
    {
        return self::RESERVED_CRM_PERMISSION_KEYS;
    }

    public function motherSidebar(): array
    {
        $modules = config('backend_sidebar.modules', []);

        if (empty($modules) && function_exists('backend_sidebar_modules')) {
            $modules = backend_sidebar_modules();
        }

        return is_array($modules) ? $modules : [];
    }

    public function motherHash(): string
    {
        return md5(json_encode($this->serializableSidebar($this->motherSidebar())));
    }

    public function permissionTree(): array
    {
        $tree = [];

        foreach ($this->motherSidebar() as $moduleIndex => $module) {
            if (!$this->visible($module)) {
                continue;
            }

            $moduleTitle = $module['module'] ?? 'Module';
            $moduleKey = $this->permissionKey($module, [$moduleTitle]);
            $moduleNode = [
                'key' => $moduleKey,
                'title' => $moduleTitle,
                'icon' => $this->resolve($module['icon'] ?? ''),
                'groups' => [],
            ];

            if (!empty($module['assignable_permission'])) {
                $moduleNode['groups'][] = [
                    'key' => $moduleKey . '.access',
                    'title' => $moduleTitle . ' Access',
                    'icon' => $this->resolve($module['icon'] ?? ''),
                    'items' => [[
                        'key' => $moduleKey,
                        'legacy_key' => $moduleKey,
                        'title' => $moduleTitle . ' Module Access',
                        'url' => '',
                        'actions' => $this->actions,
                        'contextual' => true,
                    ]],
                ];
            }

            foreach (($module['submodules'] ?? []) as $submodule) {
                if (!$this->visible($submodule)) {
                    continue;
                }

                $subTitle = $submodule['submodule'] ?? 'Submodule';
                $children = $this->children($submodule);

                $group = [
                    'key' => $this->permissionKey($submodule, [$moduleTitle, $subTitle]),
                    'title' => $subTitle,
                    'icon' => $this->resolve($submodule['icon'] ?? ''),
                    'items' => [],
                ];

                if (count($children) > 0) {
                    foreach ($children as $child) {
                        if (!$this->visible($child)) {
                            continue;
                        }

                        $childTitle = $child['childmodule'] ?? 'Menu';
                        $group['items'][] = [
                            'key' => $this->permissionKey($child, [$moduleTitle, $subTitle, $childTitle]),
                            'legacy_key' => $this->makeKey([$moduleTitle, $subTitle, $childTitle]),
                            'title' => $childTitle,
                            'url' => $this->resolve($child['url'] ?? ''),
                            'active_paths' => $this->resolve($child['active_paths'] ?? ''),
                            'actions' => $this->actions,
                        ];
                    }
                } else {
                    $group['items'][] = [
                        'key' => $this->permissionKey($submodule, [$moduleTitle, $subTitle]),
                        'legacy_key' => $this->makeKey([$moduleTitle, $subTitle]),
                        'title' => $subTitle,
                        'url' => $this->resolve($submodule['url'] ?? ''),
                        'active_paths' => $this->resolve($submodule['active_paths'] ?? ''),
                        'actions' => $this->actions,
                    ];
                }

                if (!empty($group['items'])) {
                    $moduleNode['groups'][] = $group;
                }
            }

            if (!empty($moduleNode['groups'])) {
                $tree[] = $moduleNode;
            }
        }

        return $this->appendContextualFbMarketingPermissions(
            $this->appendContextualCrmPermissions($tree)
        );
    }

    /**
     * Expose configuration-context FB MARKETING actions in the role editor
     * without creating a dead operational sidebar URL.
     */
    protected function appendContextualFbMarketingPermissions(array $tree): array
    {
        foreach (self::CONTEXTUAL_FB_MARKETING_PERMISSION_ITEMS as $contextual) {
            foreach ($tree as &$module) {
                if (($module['key'] ?? null) !== $contextual['module_key']) {
                    continue;
                }

                foreach ($module['groups'] as &$group) {
                    if (($group['key'] ?? null) !== $contextual['group_key']) {
                        continue;
                    }

                    $alreadyExists = collect($group['items'])
                        ->contains(fn(array $item) => ($item['key'] ?? null) === $contextual['key']);

                    if (!$alreadyExists) {
                        $group['items'][] = [
                            'key' => $contextual['key'],
                            'legacy_key' => $contextual['key'],
                            'title' => $contextual['title'],
                            'url' => '',
                            'actions' => $this->actions,
                            'contextual' => true,
                        ];
                    }
                }
                unset($group);
            }
            unset($module);
        }

        return $tree;
    }

    /**
     * Expose customer-context permissions in the role editor without creating
     * a dead navigation URL in the operational sidebar.
     */
    protected function appendContextualCrmPermissions(array $tree): array
    {
        foreach (self::CONTEXTUAL_CRM_PERMISSION_ITEMS as $contextual) {
            foreach ($tree as &$module) {
                if (($module['key'] ?? null) !== $contextual['module_key']) {
                    continue;
                }

                foreach ($module['groups'] as &$group) {
                    if (($group['key'] ?? null) !== $contextual['group_key']) {
                        continue;
                    }

                    $alreadyExists = collect($group['items'])
                        ->contains(fn(array $item) => ($item['key'] ?? null) === $contextual['key']);

                    if (!$alreadyExists) {
                        $group['items'][] = [
                            'key' => $contextual['key'],
                            'legacy_key' => $contextual['key'],
                            'title' => $contextual['title'],
                            'url' => '',
                            'actions' => $this->actions,
                            'contextual' => true,
                        ];
                    }
                }
                unset($group);
            }
            unset($module);
        }

        return $tree;
    }

    public function validPermissionKeys(): array
    {
        return array_fill_keys(array_keys($this->permissionAliases()), true);
    }

    /**
     * Map canonical permission keys to their accepted aliases.
     *
     * Existing installations may already store title-derived keys. Explicit
     * keys are preferred for new CRM entries, while the legacy aliases remain
     * readable so saved role permissions do not disappear after deployment.
     */
    public function permissionAliases(): array
    {
        $aliases = [];

        foreach ($this->permissionTree() as $module) {
            foreach ($module['groups'] as $group) {
                foreach ($group['items'] as $item) {
                    $canonical = $item['key'];
                    $legacy = $item['legacy_key'] ?? $canonical;
                    $aliases[$canonical] = array_values(array_unique([$canonical, $legacy]));
                }
            }
        }

        return $aliases;
    }

    public function normalizePermissions(array $permissions): array
    {
        $normalized = [];

        foreach ($this->permissionAliases() as $canonicalKey => $aliases) {
            $matched = false;
            $normalizedActions = array_fill_keys($this->actions, false);

            foreach ($aliases as $alias) {
                $actions = $permissions[$alias] ?? null;
                if (!is_array($actions)) {
                    continue;
                }

                $matched = true;
                foreach ($this->actions as $action) {
                    $normalizedActions[$action] = $normalizedActions[$action]
                        || filter_var($actions[$action] ?? false, FILTER_VALIDATE_BOOLEAN);
                }
            }

            if ($matched) {
                $normalized[$canonicalKey] = $normalizedActions;
            }
        }

        return $normalized;
    }

    public function saveForRole(int $roleId, array $permissions, ?int $updatedBy = null): RoleSidebarPermission
    {
        $permissions = $this->normalizePermissions($permissions);
        $sidebar = $this->buildFilteredSidebar($permissions);
        $cacheKey = $this->roleCacheKey($roleId);

        $record = RoleSidebarPermission::updateOrCreate(
            ['role_id' => $roleId],
            [
                'permissions_json' => $permissions,
                'sidebar_json' => $sidebar,
                'sidebar_hash' => md5(json_encode($sidebar)),
                'mother_sidebar_hash' => $this->motherHash(),
                'cache_key' => $cacheKey,
                'updated_by' => $updatedBy,
            ]
        );

        $this->writeCache($cacheKey, $sidebar);
        $this->clearUserCacheFiles();

        return $record;
    }

    public function buildFilteredSidebar(array $permissions): array
    {
        $permissions = $this->normalizePermissions($permissions);
        $filtered = [];

        foreach ($this->motherSidebar() as $module) {
            if (!$this->visible($module)) {
                continue;
            }

            $moduleTitle = $module['module'] ?? 'Module';
            $moduleKey = $this->permissionKey($module, [$moduleTitle]);

            if (!empty($module['enforce_permission_read']) && !$this->canRead($permissions, $moduleKey)) {
                continue;
            }

            $moduleCopy = $this->serializableItem($module);
            $moduleCopy['submodules'] = [];

            foreach (($module['submodules'] ?? []) as $submodule) {
                if (!$this->visible($submodule)) {
                    continue;
                }

                $subTitle = $submodule['submodule'] ?? 'Submodule';
                $children = $this->children($submodule);
                $subCopy = $this->serializableItem($submodule);
                $subCopy['childmodule'] = [];

                if (count($children) > 0) {
                    foreach ($children as $child) {
                        if (!$this->visible($child)) {
                            continue;
                        }

                        $childTitle = $child['childmodule'] ?? 'Menu';
                        $key = $this->permissionKey($child, [$moduleTitle, $subTitle, $childTitle]);

                        if ($this->canRead($permissions, $key)) {
                            $childCopy = $this->serializableItem($child);
                            $childCopy['_permission_key'] = $key;
                            $subCopy['childmodule'][] = $childCopy;
                        }
                    }

                    if (!empty($subCopy['childmodule'])) {
                        $subCopy['_permission_key'] = $this->permissionKey($submodule, [$moduleTitle, $subTitle]);
                        $moduleCopy['submodules'][] = $subCopy;
                    }
                } else {
                    $key = $this->permissionKey($submodule, [$moduleTitle, $subTitle]);

                    if ($this->canRead($permissions, $key)) {
                        $subCopy['_permission_key'] = $key;
                        $moduleCopy['submodules'][] = $subCopy;
                    }
                }
            }

            if (!empty($moduleCopy['submodules'])) {
                $moduleCopy['_permission_key'] = $this->permissionKey($module, [$moduleTitle]);
                $filtered[] = $moduleCopy;
            }
        }

        return $filtered;
    }

    public function sidebarForUser(?User $user): array
    {
        if (!$user) {
            return [];
        }

        if ((int) ($user->user_type ?? 0) === 1) {
            return $this->motherSidebar();
        }

        $roleIds = UserRolePermission::where('user_id', $user->id)
            ->whereNotNull('role_id')
            ->distinct()
            ->pluck('role_id')
            ->filter()
            ->map(fn($id) => (int) $id)
            ->values()
            ->all();

        if (empty($roleIds)) {
            return [];
        }

        if (count($roleIds) === 1) {
            return $this->sidebarForRole($roleIds[0]);
        }

        return $this->combinedSidebarForRoles($roleIds, $user->id);
    }

    public function sidebarForRole(int $roleId): array
    {
        $record = RoleSidebarPermission::where('role_id', $roleId)->first();
        if (!$record) {
            return [];
        }

        if ($record->mother_sidebar_hash !== $this->motherHash()) {
            $permissions = $this->ensureArray($record->permissions_json);
            $record = $this->saveForRole($roleId, $permissions, $record->updated_by);
        }

        $cacheKey = $record->cache_key ?: $this->roleCacheKey($roleId);
        $cached = $this->readCache($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }

        $sidebar = $this->ensureArray($record->sidebar_json);
        $this->writeCache($cacheKey, $sidebar);

        return $sidebar;
    }

    public function permissionsForRole(int $roleId): array
    {
        $record = RoleSidebarPermission::where('role_id', $roleId)->first();
        if (!$record) {
            return [];
        }

        return $this->normalizePermissions($this->ensureArray($record->permissions_json));
    }

    /**
     * Authorize a canonical sidebar permission key on the server side.
     *
     * Admins retain full access. Staff access is granted only when at least one
     * assigned role has the requested canonical key and action enabled.
     */
    public function userCan(?User $user, string $permissionKey, string $action = 'read'): bool
    {
        if (!$user || (int) ($user->status ?? 0) !== 1) {
            return false;
        }

        if ((int) ($user->user_type ?? 0) === 1) {
            return true;
        }

        if (!in_array($action, $this->actions, true)) {
            return false;
        }

        $permissionKey = trim($permissionKey);
        if ($permissionKey === '' || !array_key_exists($permissionKey, $this->permissionAliases())) {
            return false;
        }

        try {
            $roleIds = UserRolePermission::where('user_id', $user->id)
                ->whereNotNull('role_id')
                ->distinct()
                ->pluck('role_id')
                ->filter()
                ->map(fn($id) => (int) $id)
                ->values()
                ->all();

            foreach ($roleIds as $roleId) {
                $permissions = $this->permissionsForRole($roleId);

                if (!empty($permissions[$permissionKey][$action])) {
                    return true;
                }
            }
        } catch (\Throwable $exception) {
            \Illuminate\Support\Facades\Log::error('Unable to authorize CRM sidebar permission.', [
                'user_id' => $user->id ?? null,
                'permission_key' => $permissionKey,
                'action' => $action,
                'error' => $exception->getMessage(),
            ]);
        }

        return false;
    }

    public function permissionKeyForRequest($request): ?string
    {
        $currentPath = $this->normalizeUrlPath('/' . ltrim($request->path(), '/'));

        foreach ($this->permissionTree() as $module) {
            foreach ($module['groups'] as $group) {
                foreach ($group['items'] as $item) {
                    if (!empty($item['contextual'])) {
                        continue;
                    }

                    foreach ($this->sidebarItemPaths($item) as $path) {
                        if ($this->pathMatches($currentPath, $path)) {
                            return $item['key'];
                        }
                    }
                }
            }
        }

        return null;
    }

    protected function sidebarItemPaths(array $item): array
    {
        $paths = [];

        foreach ([$item['url'] ?? '', $item['active_paths'] ?? ''] as $value) {
            foreach (explode(',', (string) $value) as $path) {
                $path = trim($path);
                if ($path !== '') {
                    $paths[] = $this->normalizeUrlPath($path);
                }
            }
        }

        return array_values(array_unique(array_filter($paths)));
    }

    protected function normalizeUrlPath(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $path = $path !== null ? $path : $url;
        $path = '/' . ltrim(trim($path), '/');

        return rtrim($path, '/') ?: '/';
    }

    protected function pathMatches(string $currentPath, string $allowedPath): bool
    {
        if ($currentPath === $allowedPath) {
            return true;
        }

        if (!str_contains($allowedPath, '*')) {
            return false;
        }

        $pattern = '#^' . str_replace('\*', '[^/]+', preg_quote($allowedPath, '#')) . '$#';

        return (bool) preg_match($pattern, $currentPath);
    }

    public function combinedSidebarForRoles(array $roleIds, int $userId): array
    {
        sort($roleIds);
        $cacheKey = $this->userCacheKey($userId, $roleIds);
        $cached = $this->readCache($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }

        $mergedPermissions = [];
        foreach ($roleIds as $roleId) {
            $rolePermissions = $this->permissionsForRole((int) $roleId);
            foreach ($rolePermissions as $key => $actions) {
                foreach ($this->actions as $action) {
                    $mergedPermissions[$key][$action] = ($mergedPermissions[$key][$action] ?? false) || !empty($actions[$action]);
                }
            }
        }

        $sidebar = $this->buildFilteredSidebar($mergedPermissions);
        $this->writeCache($cacheKey, $sidebar);

        return $sidebar;
    }

    public function serializableSidebar(array $modules): array
    {
        return array_map(fn($module) => $this->serializableItemRecursive($module), $modules);
    }

    protected function serializableItemRecursive(array $item): array
    {
        $copy = $this->serializableItem($item);

        if (isset($item['submodules']) && is_array($item['submodules'])) {
            $copy['submodules'] = array_map(fn($child) => $this->serializableItemRecursive($child), $item['submodules']);
        }

        $children = $this->children($item);
        if (!empty($children)) {
            $copy['childmodule'] = array_map(fn($child) => $this->serializableItemRecursive($child), $children);
        }

        return $copy;
    }

    protected function serializableItem(array $item): array
    {
        $copy = [];
        foreach ($item as $key => $value) {
            if (in_array($key, ['submodules', 'childmodule'], true)) {
                continue;
            }
            if (is_callable($value)) {
                $copy[$key] = $this->resolve($value);
            } elseif (is_array($value)) {
                $copy[$key] = $this->arrayResolve($value);
            } else {
                $copy[$key] = $value;
            }
        }

        $copy['show_on_nav'] = $copy['show_on_nav'] ?? true;
        return $copy;
    }

    protected function arrayResolve(array $values): array
    {
        $resolved = [];
        foreach ($values as $key => $value) {
            $resolved[$key] = is_callable($value) ? $this->resolve($value) : (is_array($value) ? $this->arrayResolve($value) : $value);
        }
        return $resolved;
    }

    protected function children(array $item): array
    {
        $children = $item['childmodule'] ?? [];

        if (function_exists('backend_sidebar_children')) {
            return backend_sidebar_children($item);
        }

        if (is_callable($children)) {
            $children = $children();
        }

        if ($children instanceof \Illuminate\Support\Collection) {
            $children = $children->toArray();
        }

        return is_array($children) ? $children : [];
    }

    protected function visible(array $item): bool
    {
        if (function_exists('backend_sidebar_visible')) {
            return backend_sidebar_visible($item);
        }

        return ($item['show_on_nav'] ?? true) === true;
    }

    protected function resolve($value, $default = '')
    {
        if (function_exists('backend_sidebar_value')) {
            return backend_sidebar_value($value, $default);
        }

        if (is_callable($value)) {
            try {
                return $value();
            } catch (\Throwable $exception) {
                return $default;
            }
        }

        return $value ?? $default;
    }

    protected function canRead(array $permissions, string $key): bool
    {
        return !empty($permissions[$key]['read']);
    }

    protected function permissionKey(array $item, array $legacyParts): string
    {
        $explicitKey = trim((string) ($item['permission_key'] ?? ''));

        return $explicitKey !== '' ? $explicitKey : $this->makeKey($legacyParts);
    }

    public function makeKey(array $parts): string
    {
        $parts = array_filter(array_map(function ($part) {
            $part = Str::ascii((string) $part);
            $part = Str::slug($part, '_');
            return trim($part, '_');
        }, $parts));

        return implode('.', $parts);
    }

    public function roleCacheKey(int $roleId): string
    {
        return $this->cacheBasePath() . '/role_' . $roleId . '.json';
    }

    public function userCacheKey(int $userId, array $roleIds): string
    {
        sort($roleIds);
        return $this->cacheBasePath() . '/user_' . $userId . '_roles_' . md5(implode(',', $roleIds)) . '.json';
    }

    public function cacheBasePath(): string
    {
        return storage_path('app/sidebar-cache/' . $this->applicationKey());
    }

    public function applicationKey(): string
    {
        $host = parse_url((string) config('app.app_frontend_url'), PHP_URL_HOST);
        $host = $host ?: (request()->getHost() ?: config('app.url', 'default'));
        $host = preg_replace('/^https?:\/\//', '', $host);
        $host = preg_replace('/[^A-Za-z0-9_.-]/', '_', $host);

        return str_replace('.', '_', trim($host, '_')) ?: 'default';
    }


    protected function ensureArray($value): array
    {
        if ($value instanceof \Illuminate\Support\Collection) {
            return $value->toArray();
        }

        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    protected function readCache(string $path): ?array
    {
        if (!File::exists($path)) {
            return null;
        }

        $decoded = json_decode(File::get($path), true);
        return is_array($decoded) ? $decoded : null;
    }

    protected function writeCache(string $path, array $sidebar): void
    {
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($sidebar, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public function clearRoleCache(int $roleId): void
    {
        $path = $this->roleCacheKey($roleId);
        if (File::exists($path)) {
            File::delete($path);
        }
        $this->clearUserCacheFiles();
    }

    public function clearUserCacheFiles(): void
    {
        $dir = $this->cacheBasePath();
        if (!File::isDirectory($dir)) {
            return;
        }

        foreach (File::glob($dir . '/user_*.json') ?: [] as $file) {
            File::delete($file);
        }
    }
}
