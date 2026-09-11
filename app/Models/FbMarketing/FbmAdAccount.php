<?php
namespace App\Models\FbMarketing;
class FbmAdAccount extends FbmDiscoverableAsset
{
    public const ASSET_TYPE = 'ad_accounts';
    protected $table = 'fbm_ad_accounts';
    protected function safeMetadata(): array { return ['account_status' => $this->account_status, 'currency' => $this->currency, 'timezone_name' => $this->timezone_name]; }
}
