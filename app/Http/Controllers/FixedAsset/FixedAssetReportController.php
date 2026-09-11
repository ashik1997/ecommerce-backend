<?php

namespace App\Http\Controllers\FixedAsset;

use App\Http\Controllers\Controller;
use App\Models\FixedAsset\FixedAssetCategory;
use App\Services\FixedAsset\FixedAssetReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FixedAssetReportController extends Controller
{
    protected FixedAssetReportService $reports;

    public function __construct(FixedAssetReportService $reports)
    {
        $this->reports = $reports;
    }

    public function index(Request $request)
    {
        $filters = $this->reports->filters($request);
        $data = $this->reports->dashboard($filters);

        return view('backend.fixed_asset.reports.index', array_merge($data, [
            'warehouses' => DB::table('product_warehouses')
                ->where('product_warehouses.status', 'active')
                ->orderBy('product_warehouses.title')
                ->get(),
            'categories' => FixedAssetCategory::active()->orderBy('name')->get(),
            'filters' => $filters,
            'warehouseId' => $filters['warehouse_id'],
            'report' => $filters['report'],
        ]));
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->reports->filters($request);
        [$headings, $rows] = $this->reports->exportRows($filters);
        $filename = 'fixed_asset_' . ($filters['report'] ?? 'warehouse') . '_report_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($headings, $rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headings);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
