<?php
namespace App\Models\FbMarketing;
class FbmInstagramAccount extends FbmDiscoverableAsset
{
    public const ASSET_TYPE = 'instagram_accounts';
    protected $table = 'fbm_instagram_accounts';
    protected function safeMetadata(): array { return ['username' => $this->username, 'fbm_page_id' => $this->fbm_page_id ? (int) $this->fbm_page_id : null]; }
}
