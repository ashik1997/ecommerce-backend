<?php

namespace App\Services\FixedAsset;

use App\Models\FixedAsset\FixedAsset;
use App\Models\FixedAsset\FixedAssetTransfer;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FixedAssetTransferService
{
    public function __construct(
        protected FixedAssetEventService $eventService,
        protected FixedAssetWorkflowGuardService $guard
    ) {}

    public function dispatch(FixedAsset $asset, array $data): FixedAssetTransfer
    {
        $this->guard->ensureTransferable($asset);

        if ((int) $data['to_warehouse_id'] === (int) $asset->warehouse_id) {
            throw new InvalidArgumentException('Destination warehouse must be different from current warehouse.');
        }

        return DB::transaction(function () use ($asset, $data) {
            $before = $asset->toArray();
            $transfer = FixedAssetTransfer::create($data + [
                'transfer_no' => 'TRN-' . now()->format('YmdHis'),
                'asset_id' => $asset->id,
                'from_warehouse_id' => $asset->warehouse_id,
                'from_location_id' => $asset->location_id,
                'status' => 'dispatched',
                'created_by' => auth()->id(),
            ]);

            $asset->update(['operational_status' => 'in_transfer', 'updated_by' => auth()->id()]);
            $this->eventService->record($asset->fresh(), 'asset_transfer_dispatched', $before, $asset->fresh()->toArray(), 'Asset transfer dispatched.', 'transfer', $transfer->id);

            return $transfer;
        });
    }

    public function receive(FixedAssetTransfer $transfer, array $data): FixedAssetTransfer
    {
        return DB::transaction(function () use ($transfer, $data) {
            $asset = FixedAsset::findOrFail($transfer->asset_id);
            $before = $asset->toArray();

            $transfer->update($data + ['status' => 'received', 'received_by' => auth()->id()]);
            $asset->update([
                'warehouse_id' => $transfer->to_warehouse_id,
                'location_id' => $transfer->to_location_id,
                'condition_status' => $data['condition_at_receive'],
                'operational_status' => in_array($data['condition_at_receive'], ['damaged', 'beyond_repair'], true) ? 'damaged' : 'available',
                'updated_by' => auth()->id(),
            ]);

            $this->eventService->record($asset->fresh(), 'asset_transfer_received', $before, $asset->fresh()->toArray(), 'Asset transfer received.', 'transfer', $transfer->id);

            return $transfer->fresh();
        });
    }

    public function cancel(FixedAssetTransfer $transfer): FixedAssetTransfer
    {
        return DB::transaction(function () use ($transfer) {
            $asset = FixedAsset::findOrFail($transfer->asset_id);
            $before = $asset->toArray();

            $transfer->update(['status' => 'cancelled', 'updated_by' => auth()->id()]);
            $asset->update(['operational_status' => 'available', 'updated_by' => auth()->id()]);

            $this->eventService->record($asset->fresh(), 'asset_transfer_cancelled', $before, $asset->fresh()->toArray(), 'Asset transfer cancelled.', 'transfer', $transfer->id);

            return $transfer->fresh();
        });
    }
}
