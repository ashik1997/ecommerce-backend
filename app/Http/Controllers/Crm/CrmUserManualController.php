<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Services\Crm\CrmUserManualContentService;
use Illuminate\View\View;

class CrmUserManualController extends Controller
{
    /** @var CrmUserManualContentService */
    protected $manualContent;

    public function __construct(CrmUserManualContentService $manualContent)
    {
        $this->manualContent = $manualContent;
    }

    public function index(): View
    {
        return $this->render('bn');
    }

    public function bn(): View
    {
        return $this->render('bn');
    }

    public function en(): View
    {
        return $this->render('en');
    }

    protected function render(string $locale): View
    {
        return view('backend.crm.user-manual.index', $this->manualContent->forLocale($locale));
    }
}
