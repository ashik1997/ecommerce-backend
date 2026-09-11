<?php

namespace App\Http\Controllers\Backend\FbMarketing;

use App\Http\Controllers\Controller;
use App\Services\FbMarketing\FbmAssetDiscoveryService;
use App\Services\FbMarketing\FbmAssetSelectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class FbMarketingAssetController extends Controller
{
    public function updateSelection(
        Request $request,
        string $assetType,
        int $asset,
        FbmAssetSelectionService $selectionService
    ): RedirectResponse {
        $this->ensureDiscoverySchemaReady();
        $data = $request->validate([
            'is_selected' => ['required', 'boolean'],
            'change_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $selectionService->updateSelection(
            $assetType,
            $asset,
            (bool) $data['is_selected'],
            $request->user(),
            $request->ip(),
            $data['change_reason'] ?? null
        );

        return redirect()
            ->route('fbMarketing.dashboard')
            ->with('success', 'Local active-asset selection updated. No Meta write request was sent.');
    }

    private function ensureDiscoverySchemaReady(): void
    {
        abort_unless(
            FbmAssetDiscoveryService::schemaReady(),
            503,
            'FB MARKETING asset-discovery schema is not ready. Run the FBM-04 migration first.'
        );
    }
}
