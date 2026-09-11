<?php

namespace App\Http\Controllers\Backend\FbMarketing;

use App\Http\Controllers\Controller;
use App\Services\FbMarketing\FbmLeadAdsService;
use Illuminate\View\View;

class FbMarketingLeadAdsController extends Controller
{
    public function index(FbmLeadAdsService $leadAds): View
    {
        return view('backend.fb-marketing.lead-ads', [
            'report' => $leadAds->build(),
        ]);
    }
}
