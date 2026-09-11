<?php

namespace App\Http\Controllers\RolePermission;

use App\Http\Controllers\Controller;
use App\Models\RoleSidebarPermission;
use App\Models\UserRole;
use App\Services\RoleSidebarPermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use DataTables;

class RoleSidebarPermissionController extends Controller
{
    protected RoleSidebarPermissionService $service;

    public function __construct(RoleSidebarPermissionService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $roles = UserRole::query()->orderBy('id', 'desc');

            return DataTables::of($roles)
                ->addIndexColumn()
                ->editColumn('created_at', fn($role) => $role->created_at ? date('Y-m-d h:i:s a', strtotime($role->created_at)) : '')
                ->addColumn('permission_status', function ($role) {
                    $exists = RoleSidebarPermission::where('role_id', $role->id)->exists();
                    return $exists
                        ? '<span class="badge badge-success">Configured</span>'
                        : '<span class="badge badge-warning">Not Configured</span>';
                })
                ->addColumn('action', function ($role) {
                    $url = url('/role-sidebar-permissions/' . $role->id . '/manage');
                    return '<a href="' . $url . '" class="btn btn-sm btn-primary rounded"><i class="feather-shield"></i> Manage Permission</a>';
                })
                ->rawColumns(['permission_status', 'action'])
                ->make(true);
        }

        return view('backend.role_permission.sidebar_permissions.index');
    }

    public function manage(int $roleId)
    {
        $role = UserRole::findOrFail($roleId);
        $permissionTree = $this->service->permissionTree();
        $savedPermissions = $this->service->permissionsForRole($roleId);
        $summary = RoleSidebarPermission::where('role_id', $roleId)->first();

        return view('backend.role_permission.sidebar_permissions.manage', compact(
            'role',
            'permissionTree',
            'savedPermissions',
            'summary'
        ));
    }

    public function save(Request $request, int $roleId): JsonResponse
    {
        $role = UserRole::find($roleId);
        if (!$role) {
            return response()->json(['message' => 'Selected role was not found.'], 404);
        }

        $rawPermissions = $request->input('permissions', []);

        if (is_string($rawPermissions)) {
            $decodedPermissions = json_decode($rawPermissions, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'message' => 'Invalid permission JSON data was submitted.',
                    'errors' => ['permissions' => ['Permission data must be a valid JSON object.']],
                ], 422);
            }
            $rawPermissions = $decodedPermissions;
        }

        if ($rawPermissions instanceof \Illuminate\Support\Collection) {
            $rawPermissions = $rawPermissions->toArray();
        }

        $request->merge(['permissions' => $rawPermissions]);

        $validator = Validator::make($request->all(), [
            'permissions' => ['nullable', 'array'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed. Please check the permission data.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $record = $this->service->saveForRole(
            $roleId,
            is_array($rawPermissions) ? $rawPermissions : [],
            optional($request->user())->id
        );

        return response()->json([
            'message' => 'Role sidebar permissions have been saved successfully.',
            'role_id' => $roleId,
            'cache_key' => $record->cache_key,
            'sidebar_hash' => $record->sidebar_hash,
            'mother_sidebar_hash' => $record->mother_sidebar_hash,
            'updated_at' => optional($record->updated_at)->format('Y-m-d h:i:s'),
        ]);
    }

    public function refresh(int $roleId): JsonResponse
    {
        $role = UserRole::find($roleId);
        if (!$role) {
            return response()->json(['message' => 'Selected role was not found.'], 404);
        }

        $permissions = $this->service->permissionsForRole($roleId);
        $record = $this->service->saveForRole($roleId, $permissions, optional(auth()->user())->id);

        return response()->json([
            'message' => 'Permission cache has been refreshed successfully.',
            'cache_key' => $record->cache_key,
            'updated_at' => optional($record->updated_at)->format('Y-m-d h:i:s'),
        ]);
    }
}
