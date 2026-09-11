<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Controller;
use App\Services\Delivery\DeliveryUserManualContentService;
use Illuminate\Http\Request;

class DeliveryUserManualController extends Controller
{
    public function index(Request $request, DeliveryUserManualContentService $manualContent, ?string $locale = null)
    {
        $locale = $locale ?: (string) $request->query('locale', 'bn');

        return view('backend.delivery_management.user_manual', $manualContent->forLocale($locale));
    }
}
