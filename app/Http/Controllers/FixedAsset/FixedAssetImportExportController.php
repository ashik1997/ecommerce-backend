<?php

namespace App\Http\Controllers\FixedAsset;

use App\Http\Controllers\Controller;
use App\Models\FixedAsset\FixedAsset;
use App\Models\FixedAsset\FixedAssetCategory;
use App\Models\FixedAsset\FixedAssetLocation;
use App\Services\FixedAsset\AssetCodeService;
use App\Services\FixedAsset\FixedAssetEventService;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FixedAssetImportExportController extends Controller
{
    public function index()
    {
        return view('backend.fixed_asset.import_export.index');
    }

    public function template(): StreamedResponse
    {
        $headers = ['asset_name','category_name','warehouse_id','location_name','serial_number','brand_name','model_name','purchase_date','available_for_use_date','purchase_cost','additional_cost','residual_value','useful_life_months','depreciation_method','condition_status','invoice_number','notes'];

        return response()->streamDownload(function () use ($headers) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            fputcsv($out, ['Dell Laptop','Computer & IT','1','Head Office','SN-001','Dell','Latitude','2026-06-01','2026-06-01','85000','0','5000','36','straight_line','good','INV-001','sample row']);
            fclose($out);
        }, 'fixed_asset_import_template.csv', ['Content-Type' => 'text/csv']);
    }

    public function export(Request $request): StreamedResponse
    {
        $fileName = 'fixed_asset_register_' . now()->format('Ymd_His') . '.csv';
        $query = FixedAsset::with(['category','warehouse','location'])->orderBy('asset_code');

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('status')) {
            $query->where('operational_status', $request->status);
        }

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Asset Code','Asset Name','Category','Warehouse','Location','Serial','Brand','Model','Purchase Date','Purchase Cost','Capitalized Cost','Accumulated Depreciation','Book Value','Lifecycle Status','Operational Status']);
            $query->chunk(500, function ($assets) use ($out) {
                foreach ($assets as $asset) {
                    fputcsv($out, [
                        $asset->asset_code,
                        $asset->asset_name,
                        optional($asset->category)->name,
                        optional($asset->warehouse)->title,
                        optional($asset->location)->name,
                        $asset->serial_number,
                        $asset->brand_name,
                        $asset->model_name,
                        optional($asset->purchase_date)->format('Y-m-d'),
                        $asset->purchase_cost,
                        $asset->capitalized_cost,
                        $asset->accumulated_depreciation,
                        $asset->carrying_amount,
                        $asset->lifecycle_status,
                        $asset->operational_status,
                    ]);
                }
            });
            fclose($out);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }

    public function import(Request $request, AssetCodeService $codeService, FixedAssetEventService $eventService)
    {
        $request->validate([
            'file' => ['required','file','mimes:csv,txt','max:5120'],
        ]);

        $path = $request->file('file')->getRealPath();
        $handle = fopen($path, 'r');
        $headers = fgetcsv($handle);
        if (!$headers) {
            Toastr::error('CSV file is empty.', 'Import Failed');
            return back();
        }

        $headers = array_map(fn($h) => trim(strtolower($h)), $headers);
        $created = 0;
        $skipped = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            $rowNo = 1;
            while (($row = fgetcsv($handle)) !== false) {
                $rowNo++;
                $data = array_combine($headers, array_pad($row, count($headers), null));

                $assetName = trim($data['asset_name'] ?? '');
                $categoryName = trim($data['category_name'] ?? '');
                $warehouseId = trim($data['warehouse_id'] ?? '');
                $purchaseCost = (float)($data['purchase_cost'] ?? 0);

                if (!$assetName || !$categoryName || !$warehouseId) {
                    $skipped++;
                    $errors[] = "Row {$rowNo}: asset_name, category_name and warehouse_id are required.";
                    continue;
                }

                $warehouseExists = DB::table('product_warehouses')->where('id', $warehouseId)->exists();
                if (!$warehouseExists) {
                    $skipped++;
                    $errors[] = "Row {$rowNo}: warehouse_id {$warehouseId} not found.";
                    continue;
                }

                $category = FixedAssetCategory::firstOrCreate(
                    ['name' => $categoryName],
                    ['slug' => Str::slug($categoryName), 'is_active' => true]
                );

                $locationId = null;
                $locationName = trim($data['location_name'] ?? '');
                if ($locationName) {
                    $location = FixedAssetLocation::firstOrCreate(
                        ['name' => $locationName],
                        ['warehouse_id' => $warehouseId, 'is_active' => true]
                    );
                    $locationId = $location->id;
                }

                $additionalCost = (float)($data['additional_cost'] ?? 0);
                $capitalizedCost = $purchaseCost + $additionalCost;
                $asset = FixedAsset::create([
                    'uuid' => (string) Str::uuid(),
                    'asset_code' => $codeService->generate($category->id),
                    'asset_name' => $assetName,
                    'category_id' => $category->id,
                    'warehouse_id' => $warehouseId,
                    'location_id' => $locationId,
                    'serial_number' => $data['serial_number'] ?? null,
                    'brand_name' => $data['brand_name'] ?? null,
                    'model_name' => $data['model_name'] ?? null,
                    'purchase_date' => $data['purchase_date'] ?: null,
                    'available_for_use_date' => $data['available_for_use_date'] ?: null,
                    'depreciation_start_date' => ($data['available_for_use_date'] ?? null) ?: ($data['purchase_date'] ?? now()->toDateString()),
                    'purchase_cost' => $purchaseCost,
                    'additional_cost' => $additionalCost,
                    'capitalized_cost' => $capitalizedCost,
                    'residual_value' => (float)($data['residual_value'] ?? 0),
                    'useful_life_months' => $data['useful_life_months'] ?: null,
                    'depreciation_method' => $data['depreciation_method'] ?: 'straight_line',
                    'condition_status' => $data['condition_status'] ?: 'good',
                    'carrying_amount' => $capitalizedCost,
                    'invoice_number' => $data['invoice_number'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'created_by' => auth()->id(),
                ]);

                $eventService->record($asset, 'asset_imported', null, $asset->toArray(), 'Asset imported from CSV.');
                $created++;
            }
            fclose($handle);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Toastr::error($e->getMessage(), 'Import Failed');
            return back();
        }

        $message = "Import completed. Created: {$created}, Skipped: {$skipped}.";
        if ($errors) {
            session()->flash('fixed_asset_import_errors', array_slice($errors, 0, 20));
        }
        Toastr::success($message, 'Import Completed');
        return back();
    }
}
