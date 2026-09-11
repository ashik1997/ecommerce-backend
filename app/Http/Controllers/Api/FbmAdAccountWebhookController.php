<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FbMarketing\FbmWebhookReconciliationAlertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FbmAdAccountWebhookController extends Controller
{
    public function verify(Request $request, FbmWebhookReconciliationAlertService $service): Response
    {
        $result = $service->verify($request);

        if (!empty($result['ok'])) {
            return response((string) $result['challenge'], 200)->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403)->header('Content-Type', 'text/plain');
    }

    public function receive(Request $request, FbmWebhookReconciliationAlertService $service): JsonResponse
    {
        return response()->json($service->receive($request));
    }
}
