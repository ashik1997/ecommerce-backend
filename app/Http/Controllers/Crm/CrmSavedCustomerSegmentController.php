<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\ArchiveCrmSavedCustomerSegmentRequest;
use App\Http\Requests\Crm\CrmSavedCustomerSegmentWorklistRequest;
use App\Http\Requests\Crm\StoreCrmSavedCustomerSegmentRequest;
use App\Http\Requests\Crm\UpdateCrmSavedCustomerSegmentRequest;
use App\Models\Crm\CrmSavedCustomerSegment;
use App\Services\Crm\CrmCustomerSegmentWorklistService;
use App\Services\Crm\CrmSavedCustomerSegmentService;
use App\Services\RoleSidebarPermissionService;
use DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmSavedCustomerSegmentController extends Controller
{
    public function __construct(
        protected CrmSavedCustomerSegmentService $savedSegmentService,
        protected CrmCustomerSegmentWorklistService $customerSegmentService,
        protected RoleSidebarPermissionService $permissionService
    ) {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $permissions = [
            'can_create' => $this->permissionService->userCan($user, 'crm.saved-customer-segments.list', 'create'),
            'can_update' => $this->permissionService->userCan($user, 'crm.saved-customer-segments.list', 'update'),
            'can_archive' => $this->permissionService->userCan($user, 'crm.saved-customer-segments.list', 'delete'),
            'can_view_portfolio' => $this->permissionService->userCan($user, 'crm.customer-segments.list', 'read'),
        ];

        return view('backend.crm.customers.saved-segments.index', compact('permissions'));
    }

    public function data(CrmSavedCustomerSegmentWorklistRequest $request)
    {
        $query = $this->savedSegmentService->worklistQuery($request->user(), $request->validated());

        return DataTables::of($query)
            ->filter(function ($query) use ($request) {
                $this->savedSegmentService->applyGlobalSearch($query, $request->input('search.value'));
            })
            ->addColumn('segment', fn (CrmSavedCustomerSegment $segment) => $this->savedSegmentService->payload($segment, $request->user()))
            ->make(true);
    }

    public function options(Request $request): JsonResponse
    {
        return response()->json([
            'results' => $this->savedSegmentService->options($request->user(), $request->query('q'))->values(),
        ]);
    }

    public function show(Request $request, int $segment): JsonResponse
    {
        $savedSegment = $this->savedSegmentService->findVisible($segment, $request->user());
        $payload = $this->savedSegmentService->payload($savedSegment, $request->user(), true);
        $payload['filter_selections'] = $this->customerSegmentService->filterSelections($payload['filters']);

        return response()->json(['segment' => $payload]);
    }

    public function apply(Request $request, int $segment): JsonResponse
    {
        $payload = $this->savedSegmentService->applicablePayload($segment, $request->user());
        $payload['filter_selections'] = $this->customerSegmentService->filterSelections($payload['filters']);

        return response()->json(['segment' => $payload]);
    }

    public function store(StoreCrmSavedCustomerSegmentRequest $request): JsonResponse
    {
        $segment = $this->savedSegmentService->create($request->validated(), $request->user());

        return response()->json([
            'message' => 'CRM saved customer segment created successfully.',
            'segment' => $this->savedSegmentService->payload($segment, $request->user(), true),
        ], 201);
    }

    public function update(UpdateCrmSavedCustomerSegmentRequest $request, int $segment): JsonResponse
    {
        $savedSegment = $this->savedSegmentService->update($segment, $request->validated(), $request->user());

        return response()->json([
            'message' => 'CRM saved customer segment updated successfully.',
            'segment' => $this->savedSegmentService->payload($savedSegment, $request->user(), true),
        ]);
    }

    public function archive(ArchiveCrmSavedCustomerSegmentRequest $request, int $segment): JsonResponse
    {
        $savedSegment = $this->savedSegmentService->archive($segment, $request->user());

        return response()->json([
            'message' => 'CRM saved customer segment archived successfully.',
            'segment' => $this->savedSegmentService->payload($savedSegment, $request->user(), true),
        ]);
    }
}
