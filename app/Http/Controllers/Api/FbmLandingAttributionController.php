<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FbMarketing\FbmLandingAttributionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FbmLandingAttributionController extends Controller
{
    public function __invoke(Request $request, FbmLandingAttributionService $service): JsonResponse
    {
        $data = $request->validate([
            'session_uuid' => ['nullable', 'uuid', 'max:36'],
            'landing_url' => ['required', 'url', 'max:4096'],
            'referrer_url' => ['nullable', 'string', 'max:4096'],
            'utm_source' => ['nullable', 'string', 'max:255'],
            'utm_medium' => ['nullable', 'string', 'max:255'],
            'utm_campaign' => ['nullable', 'string', 'max:255'],
            'utm_content' => ['nullable', 'string', 'max:255'],
            'utm_term' => ['nullable', 'string', 'max:255'],
            'utm_id' => ['nullable', 'string', 'max:255'],
            'fbclid' => ['nullable', 'string', 'max:2048'],
            'fbc' => ['nullable', 'string', 'max:2048'],
            'fbp' => ['nullable', 'string', 'max:2048'],
        ]);

        $data['fbc'] = $data['fbc'] ?? $request->cookie('_fbc');
        $data['fbp'] = $data['fbp'] ?? $request->cookie('_fbp');

        $summary = $service->capture($data, $request->ip(), $request->userAgent());
        $status = (int) ($summary['http_status'] ?? 200);
        unset($summary['http_status']);

        return response()->json([
            'success' => $status < 400,
            'data' => $summary,
        ], $status);
    }
}
