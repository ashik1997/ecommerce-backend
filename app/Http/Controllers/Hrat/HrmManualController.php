<?php

namespace App\Http\Controllers\Hrat;

use App\Http\Controllers\Controller;

class HrmManualController extends Controller
{
    public function bn()
    {
        return view('backend.hrat.manual.hrm_manual_bn');
    }

    public function en()
    {
        return view('backend.hrat.manual.hrm_manual_en');
    }

    public function payrollBn()
    {
        return view('backend.hrat.manual.payroll_manual_bn');
    }

    public function payrollEn()
    {
        return view('backend.hrat.manual.payroll_manual_en');
    }
}
