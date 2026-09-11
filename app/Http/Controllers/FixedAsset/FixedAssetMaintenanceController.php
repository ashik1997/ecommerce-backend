<?php

namespace App\Http\Controllers\FixedAsset;

use App\Http\Controllers\Controller;
use App\Models\FixedAsset\FixedAsset;
use App\Models\FixedAsset\FixedAssetMaintenanceJob;
use App\Services\FixedAsset\FixedAssetAccountingPostingService;
use App\Services\FixedAsset\FixedAssetEventService;
use App\Services\FixedAsset\FixedAssetWorkflowGuardService;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use RuntimeException;

class FixedAssetMaintenanceController extends Controller
{
    public function index()
    {
        $jobs = FixedAssetMaintenanceJob::latest()->paginate(30);
        return view('backend.fixed_asset.maintenance.index', compact('jobs'));
    }

    public function create(FixedAsset $asset, FixedAssetWorkflowGuardService $guard)
    {
        try {
            $guard->ensureMaintainable($asset);
        } catch (RuntimeException $exception) {
            Toastr::warning($exception->getMessage(), 'Warning');
            return back();
        }

        return view('backend.fixed_asset.maintenance.create', compact('asset'));
    }

    public function store(Request $request, FixedAsset $asset, FixedAssetEventService $eventService, FixedAssetWorkflowGuardService $guard)
    {
        try {
            $guard->ensureMaintainable($asset);
        } catch (RuntimeException $exception) {
            Toastr::error($exception->getMessage(), 'Error');
            return back()->withInput();
        }

        $data = $request->validate([
            'type' => ['required', 'in:preventive,corrective,warranty,inspection,calibration'],
            'title' => ['required', 'string', 'max:255'],
            'problem_description' => ['nullable', 'string'],
            'reported_at' => ['required', 'date'],
            'started_at' => ['nullable', 'date', 'after_or_equal:reported_at'],
            'notes' => ['nullable', 'string'],
        ]);

        $job = FixedAssetMaintenanceJob::create($data + [
            'job_no' => 'MNT-' . now()->format('YmdHis'),
            'asset_id' => $asset->id,
            'warehouse_id' => $asset->warehouse_id,
            'status' => empty($data['started_at']) ? 'open' : 'in_progress',
            'created_by' => auth()->id(),
        ]);

        $before = $asset->toArray();
        $asset->update(['operational_status' => 'under_maintenance']);
        $eventService->record($asset, 'maintenance_opened', $before, $asset->fresh()->toArray(), 'Maintenance job opened.', 'maintenance', $job->id);

        Toastr::success('Maintenance job created.', 'Success');
        return redirect()->route('fixed-assets.maintenance.index');
    }

    public function start(FixedAssetMaintenanceJob $job, FixedAssetEventService $eventService)
    {
        if ($job->status !== 'open') {
            Toastr::warning('Only open jobs can be started.', 'Warning');
            return back();
        }

        $asset = FixedAsset::findOrFail($job->asset_id);
        $before = $asset->toArray();

        $job->update(['started_at' => now()->toDateString(), 'status' => 'in_progress', 'updated_by' => auth()->id()]);
        $asset->update(['operational_status' => 'under_maintenance']);
        $eventService->record($asset, 'maintenance_started', $before, $asset->fresh()->toArray(), 'Maintenance job started.', 'maintenance', $job->id);

        Toastr::success('Maintenance job started.', 'Success');
        return back();
    }

    public function complete(Request $request, FixedAssetMaintenanceJob $job, FixedAssetEventService $eventService, FixedAssetAccountingPostingService $postingService)
    {
        if (! in_array($job->status, ['open', 'in_progress'], true)) {
            Toastr::warning('Only open/in-progress jobs can be completed.', 'Warning');
            return back();
        }

        $data = $request->validate([
            'completed_at' => ['required', 'date', 'after_or_equal:' . ($job->started_at ?: $job->reported_at)],
            'parts_cost' => ['nullable', 'numeric', 'min:0'],
            'labor_cost' => ['nullable', 'numeric', 'min:0'],
            'other_cost' => ['nullable', 'numeric', 'min:0'],
            'condition_status' => ['nullable', 'in:new,excellent,good,fair,poor,damaged,beyond_repair'],
            'notes' => ['nullable', 'string'],
        ]);

        $total = ($data['parts_cost'] ?? 0) + ($data['labor_cost'] ?? 0) + ($data['other_cost'] ?? 0);
        $job->update($data + ['total_cost' => $total, 'status' => 'completed', 'updated_by' => auth()->id()]);

        $asset = FixedAsset::findOrFail($job->asset_id);
        $before = $asset->toArray();
        $condition = $data['condition_status'] ?? $asset->condition_status;

        if ($total > 0) {
            $postingService->postMaintenanceExpense($asset, (float) $total, 'maintenance', $job->id, 'Maintenance completed: ' . $job->job_no);
        }

        $asset->update([
            'operational_status' => in_array($condition, ['damaged', 'beyond_repair'], true) ? 'damaged' : 'available',
            'condition_status' => $condition,
        ]);

        $eventService->record($asset, 'maintenance_completed', $before, $asset->fresh()->toArray(), 'Maintenance job completed and accounting expense posted if cost exists.', 'maintenance', $job->id);

        Toastr::success('Maintenance job completed.', 'Success');
        return back();
    }

    public function cancel(FixedAssetMaintenanceJob $job, FixedAssetEventService $eventService)
    {
        if (! in_array($job->status, ['open', 'in_progress'], true)) {
            Toastr::warning('Only open/in-progress jobs can be cancelled.', 'Warning');
            return back();
        }

        $asset = FixedAsset::findOrFail($job->asset_id);
        $before = $asset->toArray();

        $job->update(['status' => 'cancelled', 'updated_by' => auth()->id()]);
        $asset->update(['operational_status' => 'available']);
        $eventService->record($asset, 'maintenance_cancelled', $before, $asset->fresh()->toArray(), 'Maintenance job cancelled.', 'maintenance', $job->id);

        Toastr::success('Maintenance job cancelled.', 'Success');
        return back();
    }
}
