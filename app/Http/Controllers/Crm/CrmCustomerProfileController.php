<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Services\Crm\CrmTaskService;
use App\Services\Crm\Customer360ProfileService;
use App\Services\RoleSidebarPermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmCustomerProfileController extends Controller
{
    public function __construct(
        protected Customer360ProfileService $profileService,
        protected RoleSidebarPermissionService $permissionService,
        protected CrmTaskService $taskService
    ) {
    }

    public function show(Request $request, int $customer)
    {
        $profile = $this->profileService->profile($customer);
        $user = $request->user();
        $permissions = [
            'can_create_note' => $this->permissionService->userCan($user, 'crm.customers.profile', 'create'),
            'can_update_note' => $this->permissionService->userCan($user, 'crm.customers.profile', 'update'),
            'can_archive_note' => $this->permissionService->userCan($user, 'crm.customers.profile', 'delete'),
            'can_manage_tags' => $this->permissionService->userCan($user, 'crm.settings.tags', 'read')
                && $this->permissionService->userCan($user, 'crm.settings.tags', 'update'),
            'can_view_tasks' => $this->permissionService->userCan($user, 'crm.tasks.list', 'read'),
            'can_create_task' => $this->permissionService->userCan($user, 'crm.tasks.list', 'read')
                && $this->permissionService->userCan($user, 'crm.tasks.create', 'create'),
            'can_view_task_calendar' => $this->permissionService->userCan($user, 'crm.tasks.calendar', 'read'),
            'can_view_activity_worklist' => $this->permissionService->userCan($user, 'crm.activities.list', 'read'),
            'can_view_customer_health' => $this->permissionService->userCan($user, 'crm.customer-health.list', 'read'),
            'can_view_customer_segments' => $this->permissionService->userCan($user, 'crm.customer-segments.list', 'read'),
            'can_view_duplicate_customers' => $this->permissionService->userCan($user, 'crm.duplicate-customers.list', 'read'),
            'can_view_communications_worklist' => $this->permissionService->userCan($user, 'crm.communications.list', 'read'),
            'can_create_communication' => $this->permissionService->userCan($user, 'crm.communications.list', 'read')
                && $this->permissionService->userCan($user, 'crm.communications.list', 'create'),
            'can_view_leads' => $this->permissionService->userCan($user, 'crm.leads.list', 'read'),
            'can_create_lead' => $this->permissionService->userCan($user, 'crm.leads.list', 'read')
                && $this->permissionService->userCan($user, 'crm.leads.list', 'create'),
        ];

        return view('backend.crm.customers.profile.show', compact('profile', 'permissions'));
    }

    public function activities(Request $request, int $customer): JsonResponse
    {
        return response()->json($this->profileService->activities($customer, $this->perPage($request, 15)));
    }

    public function communications(Request $request, int $customer): JsonResponse
    {
        return response()->json($this->profileService->communications($customer, $this->perPage($request)));
    }

    public function tasks(Request $request, int $customer): JsonResponse
    {
        return response()->json($this->taskService->customerTasks($customer, $request->user(), $this->perPage($request)));
    }

    public function leads(Request $request, int $customer): JsonResponse
    {
        return response()->json($this->profileService->leads($customer, $this->perPage($request)));
    }

    public function orders(Request $request, int $customer): JsonResponse
    {
        return response()->json($this->profileService->orders($customer, $this->perPage($request)));
    }

    public function quotations(Request $request, int $customer): JsonResponse
    {
        return response()->json($this->profileService->quotations($customer, $this->perPage($request)));
    }

    public function contactHistories(Request $request, int $customer): JsonResponse
    {
        return response()->json($this->profileService->contactHistories($customer, $this->perPage($request)));
    }

    public function scheduledContacts(Request $request, int $customer): JsonResponse
    {
        return response()->json($this->profileService->scheduledContacts($customer, $this->perPage($request)));
    }

    public function returnsAndRefunds(int $customer): JsonResponse
    {
        return response()->json($this->profileService->returnsAndRefunds($customer));
    }

    protected function perPage(Request $request, int $default = 10): int
    {
        return max(1, min((int) $request->query('per_page', $default), 50));
    }
}
