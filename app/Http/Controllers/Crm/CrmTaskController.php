<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\CompleteCrmTaskRequest;
use App\Http\Requests\Crm\CrmTaskCalendarRequest;
use App\Http\Requests\Crm\CrmTaskWorklistRequest;
use App\Http\Requests\Crm\StoreCrmTaskRequest;
use App\Http\Requests\Crm\UpdateCrmTaskRequest;
use App\Models\Crm\CrmTask;
use App\Services\Crm\CrmTaskService;
use App\Services\RoleSidebarPermissionService;
use DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmTaskController extends Controller
{
    public function __construct(
        protected CrmTaskService $taskService,
        protected RoleSidebarPermissionService $permissionService
    ) {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $permissions = [
            'can_create' => $this->permissionService->userCan($user, 'crm.tasks.create', 'create'),
            'can_update' => $this->permissionService->userCan($user, 'crm.tasks.create', 'update'),
            'can_complete' => $this->permissionService->userCan($user, 'crm.tasks.complete', 'update'),
            'can_archive' => $this->permissionService->userCan($user, 'crm.tasks.list', 'delete'),
            'can_view_calendar' => $this->permissionService->userCan($user, 'crm.tasks.calendar', 'read'),
        ];
        $prefillCustomer = $this->taskService->customerOption((int) $request->query('customer_id'));
        $openCreateModal = (bool) $request->boolean('create') && $permissions['can_create'];

        return view('backend.crm.tasks.index', compact('permissions', 'prefillCustomer', 'openCreateModal'));
    }

    public function data(CrmTaskWorklistRequest $request)
    {
        $query = $this->taskService->worklistQuery($request->validated());

        return DataTables::of($query)
            ->filter(function ($query) use ($request) {
                $this->taskService->applyGlobalSearch($query, $request->input('search.value'));
            })
            ->addColumn('task', fn (CrmTask $task) => $this->taskService->payload($task, $request->user()))
            ->make(true);
    }

    public function calendar(Request $request)
    {
        $user = $request->user();
        $permissions = [
            'can_view_worklist' => $this->permissionService->userCan($user, 'crm.tasks.list', 'read'),
            'can_view_leads' => $this->permissionService->userCan($user, 'crm.leads.list', 'read'),
        ];
        $prefillCustomer = $this->taskService->customerOption((int) $request->query('customer_id'));
        $initialMonth = now('Asia/Dhaka')->format('Y-m');

        return view('backend.crm.tasks.calendar', compact('permissions', 'prefillCustomer', 'initialMonth'));
    }

    public function calendarData(CrmTaskCalendarRequest $request): JsonResponse
    {
        return response()->json($this->taskService->calendarFeed($request->validated(), $request->user()));
    }

    public function calendarCustomerOptions(Request $request): JsonResponse
    {
        return response()->json(['results' => $this->taskService->customerOptions($request->query('q'))->values()]);
    }

    public function calendarUserOptions(Request $request): JsonResponse
    {
        return response()->json(['results' => $this->taskService->userOptions($request->query('q'))->values()]);
    }

    public function customerOptions(Request $request): JsonResponse
    {
        return response()->json(['results' => $this->taskService->customerOptions($request->query('q'))->values()]);
    }

    public function userOptions(Request $request): JsonResponse
    {
        return response()->json(['results' => $this->taskService->userOptions($request->query('q'))->values()]);
    }

    public function store(StoreCrmTaskRequest $request): JsonResponse
    {
        $task = $this->taskService->create($request->validated(), $request->user());
        return response()->json(['message' => 'CRM task created successfully.', 'task' => $this->taskService->payload($task, $request->user())], 201);
    }

    public function update(UpdateCrmTaskRequest $request, int $task): JsonResponse
    {
        $updated = $this->taskService->update($task, $request->validated(), $request->user());
        return response()->json(['message' => 'CRM task updated successfully.', 'task' => $this->taskService->payload($updated, $request->user())]);
    }

    public function complete(CompleteCrmTaskRequest $request, int $task): JsonResponse
    {
        $result = $this->taskService->complete($task, $request->validated(), $request->user());
        return response()->json([
            'message' => $result['changed'] ? 'CRM task completed successfully.' : 'This CRM task was already completed.',
            'task' => $this->taskService->payload($result['task'], $request->user()),
            'changed' => $result['changed'],
        ]);
    }

    public function archive(Request $request, int $task): JsonResponse
    {
        $this->taskService->archive($task, $request->user());
        return response()->json(['message' => 'CRM task archived successfully.']);
    }
}
