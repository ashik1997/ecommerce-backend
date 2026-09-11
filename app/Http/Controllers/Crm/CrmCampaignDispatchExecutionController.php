<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\ExecuteCrmCampaignDispatchAttemptRequest;
use App\Services\Crm\CrmCampaignDispatchExecutionService;
use Illuminate\Http\JsonResponse;

class CrmCampaignDispatchExecutionController extends Controller
{
    public function __construct(protected CrmCampaignDispatchExecutionService $executionService)
    {
    }

    public function execute(ExecuteCrmCampaignDispatchAttemptRequest $request, int $draft, int $attempt): JsonResponse
    {
        return response()->json($this->executionService->execute($draft, $attempt, $request->user()));
    }
}
