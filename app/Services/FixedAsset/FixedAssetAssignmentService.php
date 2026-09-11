<?php

namespace App\Services\FixedAsset;

use App\Models\FixedAsset\FixedAsset;
use App\Models\FixedAsset\FixedAssetAssignment;
use Illuminate\Support\Facades\DB;

class FixedAssetAssignmentService
{
    public function __construct(
        protected FixedAssetEventService $eventService,
        protected FixedAssetWorkflowGuardService $guard
    ) {}

    public function assign(FixedAsset $asset, array $data): FixedAssetAssignment
    {
        $this->guard->ensureAssignable($asset);

        return DB::transaction(function () use ($asset, $data) {
            $before = $asset->toArray();

            $assignment = FixedAssetAssignment::create($data + [
                'assignment_no' => 'ASN-' . now()->format('YmdHis'),
                'asset_id' => $asset->id,
                'status' => 'active',
                'created_by' => auth()->id(),
            ]);

            $asset->update([
                'warehouse_id' => $data['warehouse_id'],
                'department_id' => $data['department_id'] ?? null,
                'custodian_employee_id' => $data['employee_id'] ?? null,
                'operational_status' => 'assigned',
                'condition_status' => $data['issue_condition'],
                'updated_by' => auth()->id(),
            ]);

            $this->eventService->record($asset->fresh(), 'asset_assigned', $before, $asset->fresh()->toArray(), 'Asset assigned.', 'assignment', $assignment->id);

            return $assignment;
        });
    }

    public function returnAsset(FixedAssetAssignment $assignment, array $data): FixedAssetAssignment
    {
        return DB::transaction(function () use ($assignment, $data) {
            $asset = FixedAsset::findOrFail($assignment->asset_id);
            $before = $asset->toArray();

            $assignment->update($data + ['status' => 'returned', 'updated_by' => auth()->id()]);

            $asset->update([
                'operational_status' => in_array($data['return_condition'], ['damaged', 'beyond_repair'], true) ? 'damaged' : 'available',
                'condition_status' => $data['return_condition'],
                'custodian_employee_id' => null,
                'updated_by' => auth()->id(),
            ]);

            $this->eventService->record($asset->fresh(), 'asset_returned', $before, $asset->fresh()->toArray(), 'Asset returned.', 'assignment', $assignment->id);

            return $assignment->fresh();
        });
    }

    public function cancel(FixedAssetAssignment $assignment): FixedAssetAssignment
    {
        return DB::transaction(function () use ($assignment) {
            $asset = FixedAsset::findOrFail($assignment->asset_id);
            $before = $asset->toArray();

            $assignment->update(['status' => 'cancelled', 'updated_by' => auth()->id()]);
            $asset->update(['operational_status' => 'available', 'custodian_employee_id' => null, 'updated_by' => auth()->id()]);

            $this->eventService->record($asset->fresh(), 'assignment_cancelled', $before, $asset->fresh()->toArray(), 'Assignment cancelled.', 'assignment', $assignment->id);

            return $assignment->fresh();
        });
    }
}
