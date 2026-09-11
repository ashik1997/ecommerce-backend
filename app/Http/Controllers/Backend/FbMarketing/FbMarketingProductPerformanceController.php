<?php

namespace App\Http\Controllers\Backend\FbMarketing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\FbMarketing\FbMarketingProductPerformanceFilterRequest;
use App\Services\FbMarketing\FbmProductPerformanceReportService;
use Illuminate\View\View;

class FbMarketingProductPerformanceController extends Controller
{
    public function index(
        FbMarketingProductPerformanceFilterRequest $request,
        FbmProductPerformanceReportService $reports
    ): View {
        return view('backend.fb-marketing.product-performance', [
            'report' => $reports->build($request->validated()),
        ]);
    }
}
