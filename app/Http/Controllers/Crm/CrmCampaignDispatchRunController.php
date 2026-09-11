<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\CancelCrmCampaignDispatchRunRequest;
use App\Http\Requests\Crm\ReleaseCrmCampaignDispatchRunRequest;
use App\Services\Crm\CrmCampaignDispatchRunService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmCampaignDispatchRunController extends Controller
{
    public function __construct(protected CrmCampaignDispatchRunService $dispatchRunService)
    {
    }

    public function preview(Request $request, int $draft): JsonResponse
    {
        return response()->json([
            'preview' => $this->dispatchRunService->preview($draft, $request->user()),
        ]);
    }

    public function history(Request $request, int $draft): JsonResponse
    {
        return response()->json([
            'history' => $this->dispatchRunService->history($draft, $request->user()),
        ]);
    }

    public function store(ReleaseCrmCampaignDispatchRunRequest $request, int $draft, int $preparation): JsonResponse
    {
        $run = $this->dispatchRunService->release($draft, $preparation, $request->user());

        return response()->json([
            'message' => 'Provider-neutral CRM campaign dispatch run released successfully. No message was sent or queued.',
            'run' => $this->dispatchRunService->payload($run, $request->user()),
        ], 201);
    }

    public function cancel(CancelCrmCampaignDispatchRunRequest $request, int $draft, int $run): JsonResponse
    {
        $record = $this->dispatchRunService->cancel($draft, $run, $request->validated(), $request->user());

        return response()->json([
            'message' => 'Provider-neutral CRM campaign dispatch run cancelled successfully.',
            'run' => $this->dispatchRunService->payload($record, $request->user()),
        ]);
    }
}
