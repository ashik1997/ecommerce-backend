<?php

namespace App\Services;

use App\Models\RoleSidebarPermission;
use App\Models\UserRolePermission;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class RoleBasedSidebarService
{
    public function getSidebarForUser($user = null): array
    {
        if (!$user) {
            return [];
        }

        if ((int) ($user->user_type ?? 0) === 1) {
            return config('backend_sidebar.modules', []);
        }

        $cachePath = $this->cachePathForUser($user);
        $cachedSidebar = $this->readCache($cachePath);

        if (is_array($cachedSidebar)) {
            return $cachedSidebar;
        }

        $sidebar = $this->buildSidebarForUser($user);
        $this->writeCache($cachePath, $sidebar, [
            'type' => 'user',
            'user_id' => $user->id ?? null,
            'role_ids' => $this->roleIdsForUser($user),
        ]);

        return $sidebar;
    }

    public function refreshSidebarForUser($user = null): array
    {
        if (!$user) {
            return [];
        }

        $sidebar = $this->buildSidebarForUser($user);
        $this->writeCache($this->cachePathForUser($user), $sidebar, [
            'type' => 'user',
            'user_id' => $user->id ?? null,
            'role_ids' => $this->roleIdsForUser($user),
        ]);

        return $sidebar;
    }

    public function clearUserCache($user = null): void
    {
        if (!$user) {
            return;
        }

        $path = $this->cachePathForUser($user);

        if (File::exists($path)) {
            File::delete($path);
        }
    }

    public function clearRoleCache($roleId): void
    {
        $dir = $this->applicationCacheDirectory();

        if (!File::isDirectory($dir)) {
            return;
        }

        foreach (File::files($dir) as $file) {
            $payload = json_decode(File::get($file->getPathname()), true);
            $roleIds = $payload['meta']['role_ids'] ?? [];

            if (in_array((int) $roleId, array_map('intval', (array) $roleIds), true)) {
                File::delete($file->getPathname());
            }
        }
    }

    public function buildSidebarForUser($user): array
    {
        $roleIds = $this->roleIdsForUser($user);

        if (!empty($roleIds)) {
            $sidebarFromNewTable = $this->sidebarFromRoleSidebarPermissions($roleIds);

            if (!empty($sidebarFromNewTable)) {
                return $this->normalizeSidebar($sidebarFromNewTable);
            }
        }

        return $this->sidebarFromLegacyUserPermissions($user);
    }

    public function buildSidebarFromPermissions(array $permissions): array
    {
        $motherSidebar = config('backend_sidebar.modules', []);
        $normalized = [];

        foreach ($motherSidebar as $module) {
            if (!$this->isVisible($module)) {
                continue;
            }

            if ($this->isAlwaysVisibleModule($module)) {
                $normalized[] = $this->normalizeItem($module);
                continue;
            }

            $filteredSubmodules = [];

            foreach (($module['submodules'] ?? []) as $submodule) {
                $filteredSubmodule = $this->filterSubmoduleByActionPermissions($submodule, $permissions);

                if (!empty($filteredSubmodule)) {
                    $filteredSubmodules[] = $filteredSubmodule;
                }
            }

            if (!empty($filteredSubmodules)) {
                $module['submodules'] = $filteredSubmodules;
                $normalized[] = $this->normalizeItem($module);
            }
        }

        return $normalized;
    }

    public function motherSidebarHash(): string
    {
        return md5(json_encode($this->normalizeSidebar(config('backend_sidebar.modules', []))));
    }


    protected function isAlwaysVisibleModule(array $module): bool
    {
        $moduleName = strtoupper(trim((string) ($module['module'] ?? '')));

        return in_array($moduleName, ['LOGOUT'], true);
    }

    protected function sidebarFromRoleSidebarPermissions(array $roleIds): array
    {
        if (!Schema::hasTable('role_sidebar_permissions')) {
            return [];
        }

        $records = RoleSidebarPermission::whereIn('role_id', $roleIds)->get();

        if ($records->isEmpty()) {
            return [];
        }

        $sidebars = [];

        foreach ($records as $record) {
            $sidebar = $record->sidebar_json;

            if (is_string($sidebar)) {
                $sidebar = json_decode($sidebar, true);
            }

            if (is_array($sidebar)) {
                $sidebars[] = $sidebar;
            }
        }

        return $this->mergeSidebarCollections($sidebars);
    }

    protected function sidebarFromLegacyUserPermissions($user): array
    {
        if (!Schema::hasTable('user_role_permissions')) {
            return [];
        }

        $permissions = UserRolePermission::where('user_id', $user->id)
            ->get(['route', 'route_name'])
            ->flatMap(function ($permission) {
                return [$permission->route, $permission->route_name];
            })
            ->filter()
            ->map(function ($route) {
                return $this->normalizePath($route);
            })
            ->unique()
            ->values()
            ->all();

        return $this->filterSidebarByAllowedPaths(config('backend_sidebar.modules', []), $permissions);
    }

    protected function filterSidebarByAllowedPaths(array $modules, array $allowedPaths): array
    {
        $filteredModules = [];

        foreach ($modules as $module) {
            if (!$this->isVisible($module)) {
                continue;
            }

            if ($this->isAlwaysVisibleModule($module)) {
                $filteredModules[] = $this->normalizeItem($module);
                continue;
            }

            $filteredSubmodules = [];

            foreach (($module['submodules'] ?? []) as $submodule) {
                $filteredSubmodule = $this->filterSubmoduleByAllowedPaths($submodule, $allowedPaths);

                if (!empty($filteredSubmodule)) {
                    $filteredSubmodules[] = $filteredSubmodule;
                }
            }

            if (!empty($filteredSubmodules)) {
                $module['submodules'] = $filteredSubmodules;
                $filteredModules[] = $this->normalizeItem($module);
            }
        }

        return $filteredModules;
    }

    protected function filterSubmoduleByAllowedPaths(array $submodule, array $allowedPaths): array
    {
        if (!$this->isVisible($submodule)) {
            return [];
        }

        $children = $this->children($submodule);
        $filteredChildren = [];

        foreach ($children as $child) {
            if (!$this->isVisible($child)) {
                continue;
            }

            if ($this->itemAllowedByPath($child, $allowedPaths)) {
                $filteredChildren[] = $this->normalizeItem($child);
            }
        }

        if (!empty($filteredChildren)) {
            $submodule['childmodule'] = $filteredChildren;
            return $this->normalizeItem($submodule);
        }

        if ($this->itemAllowedByPath($submodule, $allowedPaths)) {
            $submodule['childmodule'] = [];
            return $this->normalizeItem($submodule);
        }

        return [];
    }

    protected function filterSubmoduleByActionPermissions(array $submodule, array $permissions): array
    {
        if (!$this->isVisible($submodule)) {
            return [];
        }

        $children = $this->children($submodule);
        $filteredChildren = [];

        foreach ($children as $child) {
            if (!$this->isVisible($child)) {
                continue;
            }

            if ($this->hasReadPermission($child, $permissions)) {
                $filteredChildren[] = $this->normalizeItem($child);
            }
        }

        if (!empty($filteredChildren)) {
            $submodule['childmodule'] = $filteredChildren;
            return $this->normalizeItem($submodule);
        }

        if ($this->hasReadPermission($submodule, $permissions)) {
            $submodule['childmodule'] = [];
            return $this->normalizeItem($submodule);
        }

        return [];
    }

    protected function hasReadPermission(array $item, array $permissions): bool
    {
        $key = $item['key'] ?? $this->makeKeyForItem($item);

        return !empty($permissions[$key]['read']);
    }

    protected function itemAllowedByPath(array $item, array $allowedPaths): bool
    {
        $candidates = $this->candidatePathsForItem($item);

        foreach ($candidates as $candidate) {
            foreach ($allowedPaths as $allowedPath) {
                if ($candidate === $allowedPath) {
                    return true;
                }

                if ($allowedPath !== '' && $candidate !== '' && (strpos($candidate, $allowedPath) !== false || strpos($allowedPath, $candidate) !== false)) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function candidatePathsForItem(array $item): array
    {
        $values = [];

        foreach (['url', 'active_paths'] as $field) {
            if (!empty($item[$field])) {
                $value = $this->value($item[$field]);

                if (is_array($value)) {
                    $values = array_merge($values, $value);
                } else {
                    $values[] = $value;
                }
            }
        }

        return collect($values)
            ->flatMap(function ($value) {
                return preg_split('/[|,]/', (string) $value) ?: [];
            })
            ->map(function ($value) {
                return $this->normalizePath($value);
            })
            ->filter(function ($value) {
                return $value !== '' && $value !== 'javascript: void(0);' && $value !== '#';
            })
            ->unique()
            ->values()
            ->all();
    }

    protected function normalizeSidebar(array $sidebar): array
    {
        return array_values(array_filter(array_map(function ($module) {
            return is_array($module) ? $this->normalizeItem($module) : null;
        }, $sidebar)));
    }

    protected function normalizeItem(array $item): array
    {
        $normalized = [];

        foreach ($item as $key => $value) {
            if ($key === 'submodules') {
                $normalized[$key] = array_values(array_filter(array_map(function ($submodule) {
                    return is_array($submodule) ? $this->normalizeItem($submodule) : null;
                }, (array) $value)));
                continue;
            }

            if ($key === 'childmodule') {
                $children = is_callable($value) ? $this->children(['childmodule' => $value]) : (array) $value;
                $normalized[$key] = array_values(array_filter(array_map(function ($child) {
                    return is_array($child) ? $this->normalizeItem($child) : null;
                }, $children)));
                continue;
            }

            if (is_callable($value)) {
                $value = $this->value($value);
            }

            if (is_array($value)) {
                $value = array_values($value);
            }

            $normalized[$key] = $value;
        }

        if (!isset($normalized['key'])) {
            $normalized['key'] = $this->makeKeyForItem($normalized);
        }

        if (!array_key_exists('show_on_nav', $normalized)) {
            $normalized['show_on_nav'] = true;
        }

        return $normalized;
    }

    protected function mergeSidebarCollections(array $sidebars): array
    {
        $merged = [];

        foreach ($sidebars as $sidebar) {
            foreach ($this->normalizeSidebar($sidebar) as $module) {
                $moduleKey = $module['key'] ?? $this->slug($module['module'] ?? 'module');

                if (!isset($merged[$moduleKey])) {
                    $merged[$moduleKey] = $module;
                    continue;
                }

                $merged[$moduleKey]['submodules'] = $this->mergeSubmodules(
                    $merged[$moduleKey]['submodules'] ?? [],
                    $module['submodules'] ?? []
                );
            }
        }

        return array_values($merged);
    }

    protected function mergeSubmodules(array $current, array $incoming): array
    {
        $merged = [];

        foreach (array_merge($current, $incoming) as $submodule) {
            $key = $submodule['key'] ?? $this->makeKeyForItem($submodule);

            if (!isset($merged[$key])) {
                $merged[$key] = $submodule;
                continue;
            }

            $merged[$key]['childmodule'] = $this->mergeChildren(
                $merged[$key]['childmodule'] ?? [],
                $submodule['childmodule'] ?? []
            );
        }

        return array_values($merged);
    }

    protected function mergeChildren(array $current, array $incoming): array
    {
        $merged = [];

        foreach (array_merge($current, $incoming) as $child) {
            $key = $child['key'] ?? $this->makeKeyForItem($child);
            $merged[$key] = $child;
        }

        return array_values($merged);
    }

    protected function roleIdsForUser($user): array
    {
        $roleIds = [];

        if (!empty($user->role_id)) {
            $roleIds[] = (int) $user->role_id;
        }

        if (Schema::hasTable('user_role_permissions')) {
            $assignedRoleIds = UserRolePermission::where('user_id', $user->id)
                ->whereNotNull('role_id')
                ->select('role_id')
                ->distinct()
                ->pluck('role_id')
                ->map(function ($roleId) {
                    return (int) $roleId;
                })
                ->all();

            $roleIds = array_merge($roleIds, $assignedRoleIds);
        }

        return array_values(array_unique(array_filter($roleIds)));
    }

    protected function readCache(string $path)
    {
        if (!File::exists($path)) {
            return null;
        }

        $payload = json_decode(File::get($path), true);

        if (!is_array($payload)) {
            return null;
        }

        if (($payload['meta']['mother_sidebar_hash'] ?? null) !== $this->motherSidebarHash()) {
            return null;
        }

        return $payload['sidebar'] ?? null;
    }

    protected function writeCache(string $path, array $sidebar, array $meta = []): void
    {
        File::ensureDirectoryExists(dirname($path));

        File::put($path, json_encode([
            'meta' => array_merge($meta, [
                'application_key' => $this->applicationKey(),
                'mother_sidebar_hash' => $this->motherSidebarHash(),
                'generated_at' => now()->toDateTimeString(),
            ]),
            'sidebar' => $sidebar,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    protected function cachePathForUser($user): string
    {
        return $this->applicationCacheDirectory() . DIRECTORY_SEPARATOR . 'user_' . ($user->id ?? 'guest') . '.json';
    }

    protected function applicationCacheDirectory(): string
    {
        return storage_path('app/sidebar-cache/' . $this->applicationKey());
    }

    protected function applicationKey(): string
    {
        $frontendUrl = config('app.app_frontend_url') ?: config('app.url') ?: request()->getHost();
        $host = parse_url($frontendUrl, PHP_URL_HOST) ?: $frontendUrl;
        $host = preg_replace('/[^A-Za-z0-9_.-]/', '_', (string) $host);

        return str_replace('.', '_', trim($host, '._-')) ?: 'default';
    }

    protected function normalizePath($value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        if (strpos($value, 'http://') === 0 || strpos($value, 'https://') === 0) {
            $path = parse_url($value, PHP_URL_PATH) ?: '';
            $query = parse_url($value, PHP_URL_QUERY);
            $value = $path . ($query ? '?' . $query : '');
        }

        return trim($value, " /\t\n\r\0\x0B");
    }

    protected function children(array $item): array
    {
        if (function_exists('backend_sidebar_children')) {
            return backend_sidebar_children($item);
        }

        $children = $item['childmodule'] ?? [];

        if (is_callable($children)) {
            $children = $children();
        }

        if ($children instanceof \Illuminate\Support\Collection) {
            $children = $children->toArray();
        }

        if (!is_array($children)) {
            return [];
        }

        return array_values(array_filter($children ?? [], 'is_array'));
    }

    protected function isVisible(array $item): bool
    {
        if (function_exists('backend_sidebar_visible')) {
            return backend_sidebar_visible($item);
        }

        return ($item['show_on_nav'] ?? true) === true;
    }

    protected function value($value, $default = '')
    {
        if (function_exists('backend_sidebar_value')) {
            return backend_sidebar_value($value, $default);
        }

        return is_callable($value) ? $value() : ($value ?? $default);
    }

    protected function makeKeyForItem(array $item): string
    {
        $parts = [];

        foreach (['module', 'submodule', 'childmodule'] as $field) {
            if (!empty($item[$field])) {
                $parts[] = $item[$field];
            }
        }

        if (empty($parts) && !empty($item['url'])) {
            $parts[] = $this->normalizePath($this->value($item['url']));
        }

        return $this->slug(implode('.', $parts));
    }

    protected function slug(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/i', '.', $value);
        $value = trim($value, '.');

        return $value ?: 'item';
    }
}
