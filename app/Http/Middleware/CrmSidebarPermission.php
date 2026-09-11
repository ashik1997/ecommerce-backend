<?php

namespace App\Http\Middleware;

use App\Services\RoleSidebarPermissionService;
use Closure;
use Illuminate\Http\Request;

class CrmSidebarPermission
{
    protected RoleSidebarPermissionService $permissionService;

    public function __construct(RoleSidebarPermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function handle(Request $request, Closure $next, string $permissionKey, string $action = 'read')
    {
        $user = $request->user();

        if (!$user) {
            return $this->deny($request, 401, 'Authentication is required.');
        }

        if ((int) ($user->status ?? 0) !== 1) {
            return $this->deny($request, 403, 'Your account is inactive.');
        }

        if (!$this->permissionService->userCan($user, $permissionKey, $action)) {
            return $this->deny($request, 403, 'You do not have permission to perform this CRM action.');
        }

        return $next($request);
    }

    protected function deny(Request $request, int $status, string $message)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => $message], $status);
        }

        abort($status, $message);
    }
}
