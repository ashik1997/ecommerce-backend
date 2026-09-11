<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\CancelCrmCampaignDispatchPreparationRequest;
use App\Http\Requests\Crm\InvalidateCrmCampaignDispatchPreparationRequest;
use App\Http\Requests\Crm\PrepareCrmCampaignDispatchRequest;
use App\Services\Crm\CrmCampaignDispatchPreparationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmCampaignDispatchPreparationController extends Controller
{
    public function __construct(protected CrmCampaignDispatchPreparationService $preparationService)
    {
    }

    public function preview(Request $request, int $draft): JsonResponse
    {
        return response()->json([
            'preview' => $this->preparationService->preview($draft, $request->user()),
        ]);
    }

    public function history(Request $request, int $draft): JsonResponse
    {
        return response()->json([
            'history' => $this->preparationService->history($draft, $request->user()),
        ]);
    }

    public function store(PrepareCrmCampaignDispatchRequest $request, int $draft): JsonResponse
    {
        $preparation = $this->preparationService->prepare($draft, $request->user());

        return response()->json([
            'message' => 'CRM campaign dispatch preparation frozen successfully. No message was sent.',
            'preparation' => $this->preparationService->payload($preparation, $request->user()),
        ], 201);
    }

    public function cancel(CancelCrmCampaignDispatchPreparationRequest $request, int $draft, int $preparation): JsonResponse
    {
        $record = $this->preparationService->cancel($draft, $preparation, $request->validated(), $request->user());

        return response()->json([
            'message' => 'CRM campaign dispatch preparation cancelled successfully.',
            'preparation' => $this->preparationService->payload($record, $request->user()),
        ]);
    }

    public function invalidate(InvalidateCrmCampaignDispatchPreparationRequest $request, int $draft, int $preparation): JsonResponse
    {
        $record = $this->preparationService->invalidate($draft, $preparation, $request->validated(), $request->user());

        return response()->json([
            'message' => 'CRM campaign dispatch preparation invalidated successfully.',
            'preparation' => $this->preparationService->payload($record, $request->user()),
        ]);
    }
}
