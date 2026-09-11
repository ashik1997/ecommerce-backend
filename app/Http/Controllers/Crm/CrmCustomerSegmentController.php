<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Customer\Models\Customer;
use App\Http\Requests\Crm\CrmCustomerSegmentWorklistRequest;
use App\Services\Crm\CrmCustomerSegmentWorklistService;
use App\Services\RoleSidebarPermissionService;
use DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmCustomerSegmentController extends Controller
{
    public function __construct(
        protected CrmCustomerSegmentWorklistService $segmentService,
        protected RoleSidebarPermissionService $permissionService
    ) {
    }

    public function index(Request $request)
    {
        $prefillCustomer = $this->segmentService->customerOption((int) $request->query('customer_id'));
        $filterOptions = $this->segmentService->filterOptions();
        $user = $request->user();
        $savedSegmentPermissions = [
            'can_view' => $this->permissionService->userCan($user, 'crm.saved-customer-segments.list', 'read'),
            'can_create' => $this->permissionService->userCan($user, 'crm.saved-customer-segments.list', 'create'),
            'can_update' => $this->permissionService->userCan($user, 'crm.saved-customer-segments.list', 'update'),
        ];
        $savedSegmentId = $savedSegmentPermissions['can_view'] ? max(0, (int) $request->query('saved_segment_id')) : 0;
        $editSavedSegmentId = $savedSegmentPermissions['can_view'] ? max(0, (int) $request->query('edit_saved_segment_id')) : 0;

        return view('backend.crm.customers.segments.index', compact(
            'prefillCustomer',
            'filterOptions',
            'savedSegmentPermissions',
            'savedSegmentId',
            'editSavedSegmentId'
        ));
    }

    public function data(CrmCustomerSegmentWorklistRequest $request)
    {
        $query = $this->segmentService->worklistQuery($request->validated());

        return DataTables::of($query)
            ->filter(function ($query) use ($request) {
                $this->segmentService->applyGlobalSearch($query, $request->input('search.value'));
            })
            ->addColumn('segment', fn (Customer $customer) => $this->segmentService->payload($customer, $request->user()))
            ->make(true);
    }

    public function customerOptions(Request $request): JsonResponse
    {
        return response()->json(['results' => $this->segmentService->customerOptions($request->query('q'))->values()]);
    }

    public function userOptions(Request $request): JsonResponse
    {
        return response()->json(['results' => $this->segmentService->userOptions($request->query('q'))->values()]);
    }

    public function tagOptions(Request $request): JsonResponse
    {
        return response()->json(['results' => $this->segmentService->tagOptions($request->query('q'))->values()]);
    }
}
