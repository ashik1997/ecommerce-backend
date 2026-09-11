<?php

namespace App\Http\Controllers\FixedAsset;

use App\Http\Controllers\Controller;
use App\Http\Requests\FixedAsset\FixedAssetAssignmentRequest;
use App\Http\Requests\FixedAsset\FixedAssetAssignmentReturnRequest;
use App\Models\FixedAsset\FixedAsset;
use App\Models\FixedAsset\FixedAssetAssignment;
use App\Services\FixedAsset\FixedAssetAssignmentService;
use App\Services\FixedAsset\FixedAssetWorkflowGuardService;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class FixedAssetAssignmentController extends Controller
{
    public function index()
    {
        $assignments = FixedAssetAssignment::with(['asset', 'warehouse'])->latest()->paginate(30);
        return view('backend.fixed_asset.assignments.index', compact('assignments'));
    }

    public function create(FixedAsset $asset, FixedAssetWorkflowGuardService $guard)
    {
        try {
            $guard->ensureAssignable($asset);
        } catch (RuntimeException $exception) {
            Toastr::warning($exception->getMessage(), 'Warning');
            return back();
        }

        return view('backend.fixed_asset.assignments.create', [
            'asset' => $asset,
            'warehouses' => DB::table('product_warehouses')->where('status', 'active')->orderBy('title')->get(),
            'employees' => DB::table('hrat_employee_profiles')->leftJoin('users', 'hrat_employee_profiles.user_id', '=', 'users.id')->select('hrat_employee_profiles.*', 'users.name as user_name')->orderBy('hrat_employee_profiles.employee_code')->get(),
            'departments' => DB::table('hrat_departments')->orderBy('name')->get(),
        ]);
    }

    public function store(FixedAssetAssignmentRequest $request, FixedAsset $asset, FixedAssetAssignmentService $service)
    {
        try {
            $service->assign($asset, $request->validated());
        } catch (RuntimeException $exception) {
            Toastr::error($exception->getMessage(), 'Error');
            return back()->withInput();
        }

        Toastr::success('Asset assigned successfully.', 'Success');
        return redirect()->route('fixed-assets.assets.show', $asset);
    }

    public function returnAsset(FixedAssetAssignmentReturnRequest $request, FixedAssetAssignment $assignment, FixedAssetAssignmentService $service)
    {
        if ($assignment->status !== 'active') {
            Toastr::warning('Only active assignments can be returned.', 'Warning');
            return back();
        }

        $data = $request->validated();
        if (strtotime($data['returned_at']) < strtotime($assignment->assigned_at)) {
            return back()->withErrors(['returned_at' => 'Return date must be after or equal to assigned date.'])->withInput();
        }

        $service->returnAsset($assignment, $data);
        Toastr::success('Asset returned successfully.', 'Success');
        return redirect()->route('fixed-assets.assets.show', $assignment->asset_id);
    }

    public function cancel(FixedAssetAssignment $assignment, FixedAssetAssignmentService $service)
    {
        if ($assignment->status !== 'active') {
            Toastr::warning('Only active assignments can be cancelled.', 'Warning');
            return back();
        }

        $service->cancel($assignment);
        Toastr::success('Assignment cancelled.', 'Success');
        return back();
    }
}
