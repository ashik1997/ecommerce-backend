<?php
namespace App\Models\FbMarketing;
class FbmPage extends FbmDiscoverableAsset
{
    public const ASSET_TYPE = 'pages';
    protected $table = 'fbm_pages';
    protected function safeMetadata(): array { return ['category' => $this->category]; }
}
