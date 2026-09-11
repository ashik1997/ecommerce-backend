<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\CrmCommunicationWorklistRequest;
use App\Http\Requests\Crm\StoreCrmCommunicationRequest;
use App\Models\Crm\CrmCommunication;
use App\Services\Crm\CrmCommunicationService;
use App\Services\RoleSidebarPermissionService;
use DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmCommunicationController extends Controller
{
    public function __construct(
        protected CrmCommunicationService $communicationService,
        protected RoleSidebarPermissionService $permissionService
    ) {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $permissions = [
            'can_create' => $this->permissionService->userCan($user, 'crm.communications.list', 'create'),
        ];
        $prefillCustomer = $this->communicationService->customerOption((int) $request->query('customer_id'));
        $openCreateModal = (bool) $request->boolean('create') && $permissions['can_create'];
        $filterOptions = $this->communicationService->filterOptions();
        $manualChannels = CrmCommunicationService::MANUAL_CHANNELS;
        $directions = CrmCommunicationService::DIRECTIONS;

        return view('backend.crm.communications.index', compact(
            'permissions',
            'prefillCustomer',
            'openCreateModal',
            'filterOptions',
            'manualChannels',
            'directions'
        ));
    }

    public function data(CrmCommunicationWorklistRequest $request)
    {
        $query = $this->communicationService->worklistQuery($request->validated());

        return DataTables::of($query)
            ->filter(function ($query) use ($request) {
                $this->communicationService->applyGlobalSearch($query, $request->input('search.value'));
            })
            ->addColumn('communication', fn (CrmCommunication $communication) => $this->communicationService->payload($communication, $request->user()))
            ->make(true);
    }

    public function customerOptions(Request $request): JsonResponse
    {
        return response()->json(['results' => $this->communicationService->customerOptions($request->query('q'))->values()]);
    }

    public function store(StoreCrmCommunicationRequest $request): JsonResponse
    {
        $communication = $this->communicationService->createManual($request->validated(), $request->user());

        return response()->json([
            'message' => 'CRM communication history logged successfully. No message was sent.',
            'communication' => $this->communicationService->payload($communication, $request->user(), true),
        ], 201);
    }

    public function show(Request $request, int $communication): JsonResponse
    {
        $record = $this->communicationService->details($communication, $request->user());

        return response()->json([
            'communication' => $this->communicationService->payload($record, $request->user(), true),
        ]);
    }
}
