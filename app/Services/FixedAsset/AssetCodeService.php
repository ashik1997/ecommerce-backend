<?php

namespace App\Services\FixedAsset;

use App\Models\FixedAsset\FixedAsset;
use App\Models\FixedAsset\FixedAssetCategory;

class AssetCodeService
{
    public function generate(int $categoryId): string
    {
        $category = FixedAssetCategory::find($categoryId);
        $prefix = $category ? strtoupper($category->code) : 'GEN';
        $year = date('Y');
        $base = 'FA-' . $prefix . '-' . $year . '-';
        $last = FixedAsset::where('asset_code', 'like', $base . '%')->orderBy('id', 'desc')->first();
        $next = 1;
        if ($last && preg_match('/(\d+)$/', $last->asset_code, $m)) {
            $next = ((int) $m[1]) + 1;
        }
        return $base . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }
}
