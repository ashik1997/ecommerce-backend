<?php

namespace App\Services\FixedAsset;

use App\Models\FixedAsset\FixedAsset;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class FixedAssetWorkflowGuardService
{
    public function ensureAssignable(FixedAsset $asset): void
    {
        if ($asset->lifecycle_status !== 'capitalized') {
            throw new RuntimeException('Only capitalized assets can be assigned.');
        }
        if ($asset->operational_status !== 'available') {
            throw new RuntimeException('Only available assets can be assigned.');
        }
    }

    public function ensureTransferable(FixedAsset $asset): void
    {
        if ($asset->lifecycle_status !== 'capitalized') {
            throw new RuntimeException('Only capitalized assets can be transferred.');
        }
        if (in_array($asset->operational_status, ['in_transfer','under_maintenance','retired','lost'], true)) {
            throw new RuntimeException('This asset is not currently transferable.');
        }
    }

    public function ensureMaintainable(FixedAsset $asset): void
    {
        if ($asset->lifecycle_status !== 'capitalized') {
            throw new RuntimeException('Only capitalized assets can be sent to maintenance.');
        }
        if (in_array($asset->operational_status, ['in_transfer','under_maintenance','retired'], true)) {
            throw new RuntimeException('This asset cannot start a new maintenance job right now.');
        }
    }

    public function ensureDisposable(FixedAsset $asset): void
    {
        if ($asset->lifecycle_status === 'disposed') {
            throw new RuntimeException('This asset is already disposed.');
        }
        if (in_array($asset->operational_status, ['in_transfer','under_maintenance'], true)) {
            throw new RuntimeException('Complete transfer/maintenance before disposal.');
        }
        $activeAssignment = DB::table('fa_assignments')
            ->where('asset_id', $asset->id)
            ->where('status', 'active')
            ->exists();
        if ($activeAssignment) {
            throw new RuntimeException('Return the active assignment before disposal.');
        }
    }
}
