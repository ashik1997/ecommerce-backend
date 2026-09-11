<?php
namespace App\Models\FbMarketing;
class FbmBusinessAccount extends FbmDiscoverableAsset
{
    public const ASSET_TYPE = 'business_accounts';
    protected $table = 'fbm_business_accounts';
    protected function safeMetadata(): array { return ['verification_status' => $this->verification_status]; }
}
