<?php

namespace App\Http\Controllers\FixedAsset;

use App\Http\Controllers\Controller;
use App\Models\FixedAsset\FixedAsset;
use App\Models\FixedAsset\FixedAssetDisposal;
use App\Models\FixedAsset\FixedAssetDisposalReason;
use App\Services\FixedAsset\FixedAssetAccountingPostingService;
use App\Services\FixedAsset\FixedAssetEventService;
use App\Services\FixedAsset\FixedAssetWorkflowGuardService;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class FixedAssetDisposalController extends Controller
{
    public function index()
    {
        $disposals = FixedAssetDisposal::latest()->paginate(30);
        return view('backend.fixed_asset.disposals.index', compact('disposals'));
    }

    public function create(FixedAsset $asset, FixedAssetWorkflowGuardService $guard)
    {
        try {
            $guard->ensureDisposable($asset);
        } catch (RuntimeException $exception) {
            Toastr::warning($exception->getMessage(), 'Warning');
            return back();
        }

        return view('backend.fixed_asset.disposals.create', [
            'asset' => $asset,
            'reasons' => FixedAssetDisposalReason::where('is_active', true)->get(),
        ]);
    }

    public function store(Request $request, FixedAsset $asset, FixedAssetWorkflowGuardService $guard)
    {
        try {
            $guard->ensureDisposable($asset);
        } catch (RuntimeException $exception) {
            Toastr::error($exception->getMessage(), 'Error');
            return back()->withInput();
        }

        $data = $request->validate([
            'disposal_reason_id' => ['nullable', 'exists:fa_disposal_reasons,id'],
            'disposal_date' => ['required', 'date'],
            'proceeds_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $carrying = (float) $asset->carrying_amount;
        $proceeds = (float) ($data['proceeds_amount'] ?? 0);

        FixedAssetDisposal::create($data + [
            'disposal_no' => 'DSP-' . now()->format('YmdHis'),
            'asset_id' => $asset->id,
            'warehouse_id' => $asset->warehouse_id,
            'carrying_amount' => $carrying,
            'gain_loss_amount' => $proceeds - $carrying,
            'status' => 'pending',
            'created_by' => auth()->id(),
        ]);

        Toastr::success('Disposal request created. Approve it to retire the asset.', 'Success');
        return redirect()->route('fixed-assets.disposals.index');
    }

    public function approve(FixedAssetDisposal $disposal, FixedAssetEventService $eventService, FixedAssetAccountingPostingService $postingService)
    {
        if ($disposal->status !== 'pending') {
            Toastr::warning('Only pending disposals can be approved.', 'Warning');
            return back();
        }

        try {
            DB::transaction(function () use ($disposal, $eventService, $postingService) {
                $asset = FixedAsset::findOrFail($disposal->asset_id);
                $before = $asset->toArray();

                $disposal->update([
                    'status' => 'completed',
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                ]);

                $postingService->postDisposal($disposal->fresh());

                $asset->update([
                    'lifecycle_status' => 'disposed',
                    'operational_status' => 'retired',
                    'carrying_amount' => 0,
                ]);

                $eventService->record($asset, 'asset_disposed', $before, $asset->fresh()->toArray(), 'Asset disposal approved, accounting posted, and asset retired.', 'disposal', $disposal->id);
            });

            Toastr::success('Disposal approved, accounting posted, and asset retired.', 'Success');
        } catch (RuntimeException $exception) {
            Toastr::error($exception->getMessage(), 'Error');
        }

        return back();
    }

    public function cancel(FixedAssetDisposal $disposal)
    {
        if ($disposal->status !== 'pending') {
            Toastr::warning('Only pending disposals can be cancelled.', 'Warning');
            return back();
        }

        $disposal->update(['status' => 'cancelled']);

        Toastr::success('Disposal request cancelled.', 'Success');
        return back();
    }
}
