<?php

namespace App\Http\Controllers\Backend\FbMarketing;

use App\Http\Controllers\Controller;
use App\Services\FbMarketing\FbmUserManualContentService;
use Illuminate\Http\Request;

class FbMarketingUserManualController extends Controller
{
    public function index(Request $request, FbmUserManualContentService $manualContent, ?string $locale = null)
    {
        $locale = $locale ?: (string) $request->query('locale', 'bn');

        return view('backend.fb-marketing.user-manual', $manualContent->forLocale($locale));
    }
}
