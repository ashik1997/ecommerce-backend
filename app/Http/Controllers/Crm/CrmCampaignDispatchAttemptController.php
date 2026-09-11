<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\CancelCrmCampaignDispatchAttemptRequest;
use App\Http\Requests\Crm\PrepareCrmCampaignDispatchAttemptRequest;
use App\Services\Crm\CrmCampaignDispatchAttemptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmCampaignDispatchAttemptController extends Controller
{
    public function __construct(protected CrmCampaignDispatchAttemptService $attemptService)
    {
    }

    public function preview(Request $request, int $draft): JsonResponse
    {
        return response()->json([
            'preview' => $this->attemptService->preview($draft, $request->user()),
        ]);
    }

    public function history(Request $request, int $draft): JsonResponse
    {
        return response()->json([
            'history' => $this->attemptService->history($draft, $request->user()),
        ]);
    }

    public function store(PrepareCrmCampaignDispatchAttemptRequest $request, int $draft, int $batch): JsonResponse
    {
        $attempt = $this->attemptService->prepare($draft, $batch, $request->user());

        return response()->json([
            'message' => 'Manual CRM campaign provider-attempt ledger prepared successfully. No SMS was sent during preparation. Real SMS execution remains a separate explicitly confirmed action.',
            'attempt' => $this->attemptService->payload($attempt, $request->user()),
        ], 201);
    }

    public function cancel(CancelCrmCampaignDispatchAttemptRequest $request, int $draft, int $attempt): JsonResponse
    {
        $record = $this->attemptService->cancel($draft, $attempt, $request->validated(), $request->user());

        return response()->json([
            'message' => 'Manual CRM campaign provider-attempt ledger cancelled successfully. No provider call occurred for this prepared attempt.',
            'attempt' => $this->attemptService->payload($record, $request->user()),
        ]);
    }
}
