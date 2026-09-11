<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FbMarketing\FbmBrowserPixelContractService;
use Illuminate\Http\JsonResponse;

class FbmBrowserPixelConfigurationController extends Controller
{
    public function __invoke(FbmBrowserPixelContractService $contractService): JsonResponse
    {
        return response()
            ->json([
                'success' => true,
                'data' => $contractService->publicConfiguration(),
            ])
            ->header('Cache-Control', 'no-store, private')
            ->header('Pragma', 'no-cache');
    }
}
