<?php

namespace App\Http\Controllers\Backend\FbMarketing;

use App\Http\Controllers\Controller;
use App\Models\FbMarketing\FbmCatalog;
use App\Models\FbMarketing\FbmCatalogProductMapping;
use App\Models\FbMarketing\FbmCatalogSyncRun;
use App\Models\FbMarketing\FbmConnection;
use App\Models\FbMarketing\FbmProductSet;
use App\Models\Product;
use App\Services\FbMarketing\FbmCatalogProductMappingSyncService;
use App\Services\FbMarketing\FbmFeedDiagnosticsService;
use App\Services\RoleSidebarPermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class FbMarketingProductsCatalogController extends Controller
{
    public function index(
        Request $request,
        RoleSidebarPermissionService $permissions,
        FbmFeedDiagnosticsService $feedDiagnostics,
        FbmCatalogProductMappingSyncService $catalogMappings
    ): View {
        $tabs = ['feed-products', 'product-mapping', 'catalog-sync', 'feed-diagnostics'];
        $activeTab = in_array((string) $request->query('tab'), $tabs, true) ? (string) $request->query('tab') : 'feed-products';
        $schemaReady = FbmCatalogProductMappingSyncService::schemaReady();
        $limit = max(1, min(1000, (int) config('fb_marketing.catalog_sync.page_item_limit', 250)));
        $summary = $catalogMappings->operationalSummary();
        $mappings = collect();
        $productSets = collect();
        $syncRuns = collect();
        $connections = collect();
        $catalogs = collect();

        if ($schemaReady) {
            $mappings = FbmCatalogProductMapping::query()
                ->with(['catalog:id,asset_name', 'product:id,name,status'])
                ->orderByDesc('is_available')
                ->orderBy('mapping_status')
                ->orderByDesc('id')
                ->limit($limit)
                ->get()
                ->map(fn(FbmCatalogProductMapping $mapping): array => $mapping->toSafeSummary());

            $productSets = FbmProductSet::query()
                ->with('catalog:id,asset_name')
                ->orderByDesc('is_available')
                ->orderBy('set_name')
                ->limit($limit)
                ->get()
                ->map(fn(FbmProductSet $set): array => $set->toSafeSummary());

            $syncRuns = FbmCatalogSyncRun::query()
                ->with(['connection:id,connection_name', 'catalog:id,asset_name'])
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit(max(1, (int) config('fb_marketing.catalog_sync.history_limit', 30)))
                ->get()
                ->map(fn(FbmCatalogSyncRun $run): array => $run->toSafeSummary());

            $catalogs = FbmCatalog::query()
                ->with('connection:id,connection_name')
                ->where('is_available', true)
                ->where('is_selected', true)
                ->orderBy('asset_name')
                ->get()
                ->map(fn(FbmCatalog $catalog): array => $catalog->toSafeSummary());

            $connections = FbmConnection::query()
                ->where('is_active', true)
                ->orderBy('connection_name')
                ->get(['id', 'connection_name'])
                ->filter(fn(FbmConnection $connection): bool => FbmCatalog::query()
                    ->where('fbm_connection_id', (int) $connection->id)
                    ->where('is_available', true)
                    ->where('is_selected', true)
                    ->exists());

        }

        return view('backend.fb-marketing.products-catalog', [
            'activeTab' => $activeTab,
            'schemaReady' => $schemaReady,
            'catalogSummary' => $summary,
            'feedDiagnostics' => $feedDiagnostics->summary(),
            'feedProducts' => $feedDiagnostics->productDiagnostics($limit),
            'mappings' => $mappings,
            'productSets' => $productSets,
            'syncRuns' => $syncRuns,
            'connections' => $connections,
            'catalogs' => $catalogs,
            'manualCatalogSyncEnabled' => (bool) config('fb_marketing.catalog_sync.manual_enabled', true),
            'canViewConfiguration' => $permissions->userCan($request->user(), 'fb_marketing_configuration_view', 'read'),
            'canRunCatalogSync' => $permissions->userCan($request->user(), 'fb_marketing_catalog_sync_run', 'read'),
            'canManageMappings' => $permissions->userCan($request->user(), 'fb_marketing_catalog_mapping_manage', 'update'),
        ]);
    }

    public function refreshCatalogNow(
        Request $request,
        int $connection,
        FbmCatalogProductMappingSyncService $catalogMappings
    ): RedirectResponse {
        try {
            $result = $catalogMappings->refreshManual(
                FbmConnection::query()->findOrFail($connection),
                $request->user()
            );
        } catch (Throwable) {
            return redirect()->route('fbMarketing.products-catalog.index', ['tab' => 'catalog-sync'])
                ->with('error', 'Manual catalog mapping refresh stopped safely. Review the redacted catalog and API ledgers, then retry after correcting the readiness issue.');
        }

        $status = (string) ($result['status'] ?? 'failed');
        $message = match ($status) {
            'success' => 'Manual catalog mapping refresh completed without a queue worker. Catalog products and product sets were mirrored read-only.',
            'partial_success' => 'Manual catalog mapping refresh completed with safe warnings. Existing unseen rows were preserved for incomplete families.',
            'skipped' => 'Manual catalog mapping refresh was skipped safely. Select a catalog or wait for the existing connection lock to be released.',
            default => 'Manual catalog mapping refresh stopped safely. Review the redacted catalog sync ledger before retrying.',
        };

        return redirect()->route('fbMarketing.products-catalog.index', ['tab' => 'catalog-sync'])
            ->with(in_array($status, ['success', 'partial_success'], true) ? 'success' : 'error', $message);
    }

    public function updateMapping(
        Request $request,
        int $mapping,
        FbmCatalogProductMappingSyncService $catalogMappings
    ): RedirectResponse {
        abort_unless(FbmCatalogProductMappingSyncService::schemaReady(), 503, 'FBM-11 catalog mapping schema is not ready.');
        $validated = $request->validate([
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'mapping_note' => ['nullable', 'string', 'max:500'],
        ]);
        $product = !empty($validated['product_id']) ? Product::query()->findOrFail((int) $validated['product_id']) : null;
        $catalogMappings->updateManualMapping(
            FbmCatalogProductMapping::query()->findOrFail($mapping),
            $product,
            $request->user(),
            $validated['mapping_note'] ?? null
        );

        return redirect()->route('fbMarketing.products-catalog.index', ['tab' => 'product-mapping'])
            ->with('success', $product ? 'Local ERP catalog mapping override saved. No Meta write request was sent.' : 'Local override cleared. Retailer-ID automatic mapping was reconciled locally.');
    }
}
