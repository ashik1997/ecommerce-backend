<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\CrmLeadPipelineRequest;
use App\Http\Requests\Crm\CrmLeadWorklistRequest;
use App\Http\Requests\Crm\StoreCrmLeadRequest;
use App\Http\Requests\Crm\UpdateCrmLeadRequest;
use App\Http\Requests\Crm\UpdateCrmLeadStatusRequest;
use App\Models\Crm\CrmLead;
use App\Services\Crm\CrmLeadService;
use App\Services\RoleSidebarPermissionService;
use DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmLeadController extends Controller
{
    public function __construct(
        protected CrmLeadService $leadService,
        protected RoleSidebarPermissionService $permissionService
    ) {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $permissions = [
            'can_create' => $this->permissionService->userCan($user, 'crm.leads.list', 'create'),
            'can_update' => $this->permissionService->userCan($user, 'crm.leads.list', 'update'),
            'can_delete' => $this->permissionService->userCan($user, 'crm.leads.list', 'delete'),
        ];
        $filterOptions = $this->leadService->filterOptions();
        $prefillCustomer = $this->leadService->customerPrefill((int) $request->query('customer_id', 0));
        $openCreate = $request->boolean('create') && $permissions['can_create'];

        return view('backend.crm.leads.index', compact('permissions', 'filterOptions', 'prefillCustomer', 'openCreate'));
    }

    public function pipeline(Request $request)
    {
        $user = $request->user();
        $permissions = [
            'can_update' => $this->permissionService->userCan($user, 'crm.leads.list', 'update'),
        ];
        $filterOptions = $this->leadService->filterOptions();
        $prefillCustomer = $this->leadService->customerPrefill((int) $request->query('customer_id', 0));

        return view('backend.crm.leads.pipeline', compact('permissions', 'filterOptions', 'prefillCustomer'));
    }

    public function pipelineData(CrmLeadPipelineRequest $request): JsonResponse
    {
        return response()->json($this->leadService->pipelineBoard($request->validated(), $request->user()));
    }

    public function data(CrmLeadWorklistRequest $request)
    {
        $query = $this->leadService->worklistQuery($request->validated());

        return DataTables::of($query)
            ->filter(function ($query) use ($request) {
                $this->leadService->applyGlobalSearch($query, $request->input('search.value'));
            })
            ->addColumn('lead', fn (CrmLead $lead) => $this->leadService->payload($lead, $request->user()))
            ->make(true);
    }

    public function customerOptions(Request $request): JsonResponse
    {
        return response()->json(['results' => $this->leadService->customerOptions($request->query('q'))->values()]);
    }

    public function userOptions(Request $request): JsonResponse
    {
        return response()->json(['results' => $this->leadService->userOptions($request->query('q'))->values()]);
    }

    public function store(StoreCrmLeadRequest $request): JsonResponse
    {
        $lead = $this->leadService->create($request->validated(), $request->user());

        return response()->json([
            'message' => 'CRM lead created successfully.',
            'lead' => $this->leadService->payload($lead, $request->user(), true),
        ], 201);
    }

    public function show(Request $request, int $lead): JsonResponse
    {
        $record = $this->leadService->details($lead);

        return response()->json(['lead' => $this->leadService->payload($record, $request->user(), true)]);
    }

    public function update(UpdateCrmLeadRequest $request, int $lead): JsonResponse
    {
        $record = $this->leadService->update($lead, $request->validated(), $request->user());

        return response()->json([
            'message' => 'CRM lead updated successfully.',
            'lead' => $this->leadService->payload($record, $request->user(), true),
        ]);
    }

    public function status(UpdateCrmLeadStatusRequest $request, int $lead): JsonResponse
    {
        $record = $this->leadService->updateStatus($lead, $request->validated(), $request->user());

        return response()->json([
            'message' => 'CRM lead status updated successfully.',
            'lead' => $this->leadService->payload($record, $request->user(), true),
        ]);
    }

    public function archive(Request $request, int $lead): JsonResponse
    {
        $this->leadService->archive($lead, $request->user());

        return response()->json(['message' => 'CRM lead archived successfully.']);
    }
}
