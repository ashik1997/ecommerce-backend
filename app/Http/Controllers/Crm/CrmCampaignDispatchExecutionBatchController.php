<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\CancelCrmCampaignDispatchExecutionBatchRequest;
use App\Http\Requests\Crm\ClaimCrmCampaignDispatchExecutionBatchRequest;
use App\Services\Crm\CrmCampaignDispatchExecutionBatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmCampaignDispatchExecutionBatchController extends Controller
{
    public function __construct(protected CrmCampaignDispatchExecutionBatchService $executionBatchService)
    {
    }

    public function preview(Request $request, int $draft): JsonResponse
    {
        return response()->json([
            'preview' => $this->executionBatchService->preview($draft, $request->user()),
        ]);
    }

    public function history(Request $request, int $draft): JsonResponse
    {
        return response()->json([
            'history' => $this->executionBatchService->history($draft, $request->user()),
        ]);
    }

    public function store(ClaimCrmCampaignDispatchExecutionBatchRequest $request, int $draft, int $run): JsonResponse
    {
        $batch = $this->executionBatchService->claim($draft, $run, $request->user());

        return response()->json([
            'message' => 'Provider-neutral CRM campaign dispatch execution batch claimed successfully. No message was sent, queued, or executed.',
            'batch' => $this->executionBatchService->payload($batch, $request->user()),
        ], 201);
    }

    public function cancel(CancelCrmCampaignDispatchExecutionBatchRequest $request, int $draft, int $batch): JsonResponse
    {
        $record = $this->executionBatchService->cancel($draft, $batch, $request->validated(), $request->user());

        return response()->json([
            'message' => 'Provider-neutral CRM campaign dispatch execution batch cancelled successfully.',
            'batch' => $this->executionBatchService->payload($record, $request->user()),
        ]);
    }
}
