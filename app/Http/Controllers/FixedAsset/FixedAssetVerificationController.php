<?php

namespace App\Http\Controllers\FixedAsset;

use App\Http\Controllers\Controller;
use App\Models\FixedAsset\FixedAsset;
use App\Models\FixedAsset\FixedAssetVerificationItem;
use App\Models\FixedAsset\FixedAssetVerificationSession;
use App\Services\FixedAsset\FixedAssetEventService;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FixedAssetVerificationController extends Controller
{
    public function index(Request $request)
    {
        $sessions = FixedAssetVerificationSession::with('warehouse')
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('backend.fixed_asset.verification.index', compact('sessions'));
    }

    public function create()
    {
        return view('backend.fixed_asset.verification.create', [
            'warehouses' => DB::table('product_warehouses')->where('status', 'active')->orderBy('title')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'warehouse_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:190'],
            'started_at' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $session = DB::transaction(function () use ($data) {
            $session = FixedAssetVerificationSession::create($data + [
                'session_no' => 'FAV-' . now()->format('YmdHis'),
                'status' => 'open',
                'created_by' => auth()->id(),
            ]);

            FixedAsset::where('warehouse_id', $data['warehouse_id'])
                ->whereNotIn('lifecycle_status', ['disposed', 'archived'])
                ->select('id')
                ->chunkById(200, function ($assets) use ($session) {
                    foreach ($assets as $asset) {
                        FixedAssetVerificationItem::firstOrCreate([
                            'verification_session_id' => $session->id,
                            'asset_id' => $asset->id,
                        ], ['result' => 'expected']);
                    }
                });

            return $session;
        });

        Toastr::success('Verification session opened.', 'Success');
        return redirect()->route('fixed-assets.verification.show', $session);
    }

    public function show(FixedAssetVerificationSession $session)
    {
        $session->load('warehouse');
        $items = FixedAssetVerificationItem::with(['asset.category', 'asset.location'])
            ->where('verification_session_id', $session->id)
            ->orderBy('result')
            ->paginate(50);

        $summary = FixedAssetVerificationItem::where('verification_session_id', $session->id)
            ->select('result', DB::raw('COUNT(*) as total'))
            ->groupBy('result')
            ->pluck('total', 'result');

        return view('backend.fixed_asset.verification.show', compact('session', 'items', 'summary'));
    }

    public function scan(Request $request, FixedAssetVerificationSession $session, FixedAssetEventService $eventService)
    {
        abort_if($session->status !== 'open', 422, 'This verification session is closed.');

        $data = $request->validate([
            'asset_code' => ['required', 'string', 'max:190'],
            'result' => ['nullable', 'in:found,wrong_location,damaged'],
            'note' => ['nullable', 'string'],
        ]);

        $asset = FixedAsset::where('asset_code', $data['asset_code'])->first();

        if (!$asset) {
            Toastr::error('Asset code not found.', 'Not Found');
            return back()->withInput();
        }

        $result = $asset->warehouse_id == $session->warehouse_id ? ($data['result'] ?? 'found') : 'wrong_location';

        $item = FixedAssetVerificationItem::updateOrCreate([
            'verification_session_id' => $session->id,
            'asset_id' => $asset->id,
        ], [
            'result' => $result,
            'scanned_at' => now(),
            'note' => $data['note'] ?? null,
        ]);

        $eventService->record($asset, 'asset_verified', null, $item->toArray(), 'Asset scanned in verification session ' . $session->session_no);

        Toastr::success('Asset scan recorded.', 'Success');
        return back();
    }

    public function updateItem(Request $request, FixedAssetVerificationItem $item, FixedAssetEventService $eventService)
    {
        $data = $request->validate([
            'result' => ['required', 'in:expected,found,missing,wrong_location,damaged'],
            'note' => ['nullable', 'string'],
        ]);

        $before = $item->toArray();
        $item->update($data + ['scanned_at' => in_array($data['result'], ['found', 'wrong_location', 'damaged']) ? now() : $item->scanned_at]);
        $eventService->record($item->asset, 'verification_item_updated', $before, $item->fresh()->toArray(), 'Verification item updated.');

        Toastr::success('Verification item updated.', 'Success');
        return back();
    }

    public function complete(FixedAssetVerificationSession $session)
    {
        $session->update(['status' => 'completed', 'completed_at' => now()]);
        Toastr::success('Verification session completed.', 'Success');
        return redirect()->route('fixed-assets.verification.show', $session);
    }
}
