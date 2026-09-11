<?php

namespace App\Http\Controllers\SrManagement;

use App\Http\Controllers\Controller;

class CommissionManualController extends Controller
{
    public function bn()
    {
        return view('backend.sr_management.manual.commission_manual_bn');
    }

    public function en()
    {
        return view('backend.sr_management.manual.commission_manual_en');
    }
}
