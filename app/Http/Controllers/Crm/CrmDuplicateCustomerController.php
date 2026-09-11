<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Customer\Models\Customer;
use App\Http\Requests\Crm\CrmDuplicateCustomerWorklistRequest;
use App\Services\Crm\CrmDuplicateCustomerWorklistService;
use DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmDuplicateCustomerController extends Controller
{
    public function __construct(protected CrmDuplicateCustomerWorklistService $duplicateService)
    {
    }

    public function index(Request $request)
    {
        $prefillCustomer = $this->duplicateService->customerOption((int) $request->query('customer_id'));
        $filterOptions = $this->duplicateService->filterOptions();

        return view('backend.crm.customers.duplicates.index', compact('prefillCustomer', 'filterOptions'));
    }

    public function data(CrmDuplicateCustomerWorklistRequest $request)
    {
        $query = $this->duplicateService->worklistQuery($request->validated());

        return DataTables::of($query)
            ->filter(function ($query) use ($request) {
                $this->duplicateService->applyGlobalSearch($query, $request->input('search.value'));
            })
            ->addColumn('duplicate', fn (Customer $customer) => $this->duplicateService->payload($customer, $request->user()))
            ->make(true);
    }

    public function customerOptions(Request $request): JsonResponse
    {
        return response()->json(['results' => $this->duplicateService->customerOptions($request->query('q'))->values()]);
    }

    public function userOptions(Request $request): JsonResponse
    {
        return response()->json(['results' => $this->duplicateService->userOptions($request->query('q'))->values()]);
    }
}
