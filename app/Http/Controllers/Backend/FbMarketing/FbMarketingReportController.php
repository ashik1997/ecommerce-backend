<?php

namespace App\Http\Controllers\Backend\FbMarketing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\FbMarketing\CreateFbmReportExportRequest;
use App\Http\Requests\Backend\FbMarketing\FbMarketingReportCenterRequest;
use App\Models\FbMarketing\FbmReportExport;
use App\Services\FbMarketing\FbmReportExportService;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\View\View;

class FbMarketingReportController extends Controller
{
    public function index(FbMarketingReportCenterRequest $request, FbmReportExportService $exports): View
    {
        return view('backend.fb-marketing.reports', [
            'report' => $exports->preview($request->validated()),
        ]);
    }

    public function storeExport(CreateFbmReportExportRequest $request, FbmReportExportService $exports): RedirectResponse
    {
        $export = $exports->create($request->validated(), optional($request->user())->id);

        if ((string) $export->status !== 'completed') {
            return redirect()->route('fbMarketing.reports.index', $request->query())->with('error', 'Report export failed safely.');
        }

        return redirect()
            ->route('fbMarketing.reports.download', [$export->id, $export->download_token])
            ->with('success', 'Report export generated safely.');
    }

    public function download(FbmReportExport $export, string $token, FbmReportExportService $exports): BinaryFileResponse
    {
        $path = $exports->downloadPath($export, $token);
        abort_if($path === null, 404);

        return response()->download($path, $export->report_type . '-' . optional($export->generated_at)->format('Ymd-His') . '.csv');
    }
}
