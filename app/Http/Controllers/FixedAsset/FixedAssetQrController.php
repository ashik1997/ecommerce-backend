<?php

namespace App\Http\Controllers\FixedAsset;

use App\Http\Controllers\Controller;
use App\Models\FixedAsset\FixedAsset;

class FixedAssetQrController extends Controller
{
    public function svg(FixedAsset $asset)
    {
        $payload = route('fixed-assets.assets.show', $asset);
        $label = $asset->asset_code . ' | ' . $asset->asset_name;
        $encodedPayload = e($payload);
        $encodedLabel = e($label);

        // Lightweight printable tag without external package dependency.
        // The barcode-like blocks are deterministic from asset code for visual scanning label support.
        $hash = sha1($asset->asset_code . $payload);
        $bars = '';
        $x = 18;
        for ($i = 0; $i < 32; $i++) {
            $height = 34 + (hexdec($hash[$i]) % 5) * 7;
            $width = 2 + (hexdec($hash[$i]) % 3);
            $bars .= '<rect x="'.$x.'" y="36" width="'.$width.'" height="'.$height.'" fill="#111827" />';
            $x += $width + 3;
        }

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="360" height="190" viewBox="0 0 360 190">
  <rect width="360" height="190" fill="#ffffff"/>
  <rect x="8" y="8" width="344" height="174" fill="none" stroke="#111827" stroke-width="2"/>
  <text x="18" y="28" font-family="Arial, sans-serif" font-size="14" font-weight="700" fill="#111827">FIXED ASSET TAG</text>
  {$bars}
  <text x="18" y="128" font-family="Arial, sans-serif" font-size="13" font-weight="700" fill="#111827">{$encodedLabel}</text>
  <text x="18" y="150" font-family="Arial, sans-serif" font-size="10" fill="#374151">{$encodedPayload}</text>
  <text x="18" y="168" font-family="Arial, sans-serif" font-size="10" fill="#6b7280">Warehouse: {$asset->warehouse_id}</text>
</svg>
SVG;

        return response($svg, 200)->header('Content-Type', 'image/svg+xml');
    }
}
