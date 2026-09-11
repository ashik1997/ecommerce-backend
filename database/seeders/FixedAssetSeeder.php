<?php

namespace Database\Seeders;

use App\Models\FixedAsset\FixedAssetCategory;
use App\Models\FixedAsset\FixedAssetDepreciationProfile;
use App\Models\FixedAsset\FixedAssetDisposalReason;
use App\Services\FixedAsset\FixedAssetAccountHeadService;
use Illuminate\Database\Seeder;

class FixedAssetSeeder extends Seeder
{
    public function run()
    {
        app(FixedAssetAccountHeadService::class)->ensureRequiredHeads();

        $categories = [
            ['name' => 'Land', 'code' => 'LAND', 'is_depreciable' => false, 'default_useful_life_months' => null],
            ['name' => 'Buildings', 'code' => 'BLD', 'is_depreciable' => true, 'default_useful_life_months' => 240],
            ['name' => 'Furniture & Fixtures', 'code' => 'FUR', 'is_depreciable' => true, 'default_useful_life_months' => 60],
            ['name' => 'Office Equipment', 'code' => 'OFF', 'is_depreciable' => true, 'default_useful_life_months' => 60],
            ['name' => 'IT Equipment', 'code' => 'IT', 'is_depreciable' => true, 'default_useful_life_months' => 36],
            ['name' => 'Electrical Equipment', 'code' => 'ELEC', 'is_depreciable' => true, 'default_useful_life_months' => 60],
            ['name' => 'Vehicles', 'code' => 'VEH', 'is_depreciable' => true, 'default_useful_life_months' => 60],
            ['name' => 'Machinery & Equipment', 'code' => 'MACH', 'is_depreciable' => true, 'default_useful_life_months' => 120],
            ['name' => 'Other Fixed Assets', 'code' => 'OTH', 'is_depreciable' => true, 'default_useful_life_months' => 60],
        ];
        foreach ($categories as $category) FixedAssetCategory::firstOrCreate(['code' => $category['code']], $category);

        FixedAssetDepreciationProfile::firstOrCreate(['name' => 'Straight Line - 3 Years'], ['method' => 'straight_line', 'useful_life_months' => 36, 'residual_value' => 0, 'is_active' => true]);
        FixedAssetDepreciationProfile::firstOrCreate(['name' => 'Straight Line - 5 Years'], ['method' => 'straight_line', 'useful_life_months' => 60, 'residual_value' => 0, 'is_active' => true]);

        foreach (['Sold','Scrapped','Obsolete','Beyond Repair','Lost','Stolen','Donated','Transfer-out'] as $reason) {
            FixedAssetDisposalReason::firstOrCreate(['code' => strtolower(str_replace([' ', '-'], '_', $reason))], ['name' => $reason, 'is_active' => true]);
        }
    }
}
