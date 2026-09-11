<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Services\Crm\CrmDashboardService;
use Illuminate\Http\Request;

class CrmDashboardController extends Controller
{
    public function __construct(protected CrmDashboardService $dashboardService)
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        return view('backend.crm-dashboard', [
            'dashboard' => $this->dashboardService->overview($request->user()),
        ]);
    }
}
