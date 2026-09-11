<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\CrmCustomerHealthWorklistRequest;
use App\Http\Controllers\Customer\Models\Customer;
use App\Services\Crm\CrmCustomerHealthWorklistService;
use DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmCustomerHealthController extends Controller
{
    public function __construct(protected CrmCustomerHealthWorklistService $healthService)
    {
    }

    public function index(Request $request)
    {
        $prefillCustomer = $this->healthService->customerOption((int) $request->query('customer_id'));
        $filterOptions = $this->healthService->filterOptions();

        return view('backend.crm.customers.health.index', compact('prefillCustomer', 'filterOptions'));
    }

    public function data(CrmCustomerHealthWorklistRequest $request)
    {
        $query = $this->healthService->worklistQuery($request->validated());

        return DataTables::of($query)
            ->filter(function ($query) use ($request) {
                $this->healthService->applyGlobalSearch($query, $request->input('search.value'));
            })
            ->addColumn('health', fn (Customer $customer) => $this->healthService->payload($customer, $request->user()))
            ->make(true);
    }

    public function customerOptions(Request $request): JsonResponse
    {
        return response()->json(['results' => $this->healthService->customerOptions($request->query('q'))->values()]);
    }

    public function userOptions(Request $request): JsonResponse
    {
        return response()->json(['results' => $this->healthService->userOptions($request->query('q'))->values()]);
    }
}
