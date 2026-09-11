<?php
namespace App\Models\FbMarketing;
class FbmPixel extends FbmDiscoverableAsset
{
    public const ASSET_TYPE = 'pixels';
    protected $table = 'fbm_pixels';
    protected function safeMetadata(): array { return ['data_use_setting' => $this->data_use_setting]; }
}
