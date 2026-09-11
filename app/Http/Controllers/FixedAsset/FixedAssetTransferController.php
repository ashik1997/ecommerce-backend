<?php

namespace App\Http\Controllers\FixedAsset;

use App\Http\Controllers\Controller;
use App\Http\Requests\FixedAsset\FixedAssetTransferReceiveRequest;
use App\Http\Requests\FixedAsset\FixedAssetTransferRequest;
use App\Models\FixedAsset\FixedAsset;
use App\Models\FixedAsset\FixedAssetTransfer;
use App\Services\FixedAsset\FixedAssetTransferService;
use App\Services\FixedAsset\FixedAssetWorkflowGuardService;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class FixedAssetTransferController extends Controller
{
    public function index()
    {
        $transfers = FixedAssetTransfer::with(['asset', 'fromWarehouse', 'toWarehouse'])->latest()->paginate(30);
        return view('backend.fixed_asset.transfers.index', compact('transfers'));
    }

    public function create(FixedAsset $asset, FixedAssetWorkflowGuardService $guard)
    {
        try {
            $guard->ensureTransferable($asset);
        } catch (RuntimeException $exception) {
            Toastr::warning($exception->getMessage(), 'Warning');
            return back();
        }

        return view('backend.fixed_asset.transfers.create', [
            'asset' => $asset,
            'warehouses' => DB::table('product_warehouses')->where('status', 'active')->orderBy('title')->get(),
        ]);
    }

    public function store(FixedAssetTransferRequest $request, FixedAsset $asset, FixedAssetTransferService $service)
    {
        try {
            $service->dispatch($asset, $request->validated());
        } catch (RuntimeException|InvalidArgumentException $exception) {
            Toastr::error($exception->getMessage(), 'Error');
            return back()->withInput();
        }

        Toastr::success('Asset transfer created.', 'Success');
        return redirect()->route('fixed-assets.transfers.index');
    }

    public function receive(FixedAssetTransferReceiveRequest $request, FixedAssetTransfer $transfer, FixedAssetTransferService $service)
    {
        if ($transfer->status !== 'dispatched') {
            Toastr::warning('Only dispatched transfers can be received.', 'Warning');
            return back();
        }

        $data = $request->validated();
        if (strtotime($data['received_date']) < strtotime($transfer->transfer_date)) {
            return back()->withErrors(['received_date' => 'Receive date must be after or equal to transfer date.'])->withInput();
        }

        $service->receive($transfer, $data);
        Toastr::success('Transfer received and warehouse updated.', 'Success');
        return back();
    }

    public function cancel(FixedAssetTransfer $transfer, FixedAssetTransferService $service)
    {
        if (!in_array($transfer->status, ['pending', 'dispatched'], true)) {
            Toastr::warning('Only pending/dispatched transfers can be cancelled.', 'Warning');
            return back();
        }

        $service->cancel($transfer);
        Toastr::success('Transfer cancelled.', 'Success');
        return back();
    }
}
