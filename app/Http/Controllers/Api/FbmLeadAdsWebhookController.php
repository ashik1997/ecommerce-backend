<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FbMarketing\FbmLeadAdsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FbmLeadAdsWebhookController extends Controller
{
    public function verify(Request $request, FbmLeadAdsService $leadAds): Response
    {
        $result = $leadAds->verify($request);

        if (!empty($result['ok'])) {
            return response((string) $result['challenge'], 200)->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403)->header('Content-Type', 'text/plain');
    }

    public function receive(Request $request, FbmLeadAdsService $leadAds): JsonResponse
    {
        return response()->json($leadAds->receive($request));
    }
}
