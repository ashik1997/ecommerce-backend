<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\CrmActivityWorklistRequest;
use App\Models\Crm\CrmActivity;
use App\Services\Crm\CrmActivityWorklistService;
use DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmActivityController extends Controller
{
    public function __construct(protected CrmActivityWorklistService $activityService)
    {
    }

    public function index(Request $request)
    {
        $prefillCustomer = $this->activityService->customerOption((int) $request->query('customer_id'));
        $filterOptions = $this->activityService->filterOptions();

        return view('backend.crm.activities.index', compact('prefillCustomer', 'filterOptions'));
    }

    public function data(CrmActivityWorklistRequest $request)
    {
        $query = $this->activityService->worklistQuery($request->validated());

        return DataTables::of($query)
            ->filter(function ($query) use ($request) {
                $this->activityService->applyGlobalSearch($query, $request->input('search.value'));
            })
            ->addColumn('activity', fn (CrmActivity $activity) => $this->activityService->payload($activity, $request->user()))
            ->make(true);
    }

    public function customerOptions(Request $request): JsonResponse
    {
        return response()->json(['results' => $this->activityService->customerOptions($request->query('q'))->values()]);
    }

    public function userOptions(Request $request): JsonResponse
    {
        return response()->json(['results' => $this->activityService->userOptions($request->query('q'))->values()]);
    }

    public function show(Request $request, int $activity): JsonResponse
    {
        $record = $this->activityService->details($activity, $request->user());

        return response()->json([
            'activity' => $this->activityService->payload($record, $request->user(), true),
        ]);
    }
}
