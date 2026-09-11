<?php
namespace App\Models\FbMarketing;
class FbmDataset extends FbmDiscoverableAsset
{
    public const ASSET_TYPE = 'datasets';
    protected $table = 'fbm_datasets';
    protected function safeMetadata(): array { return ['dataset_type' => $this->dataset_type]; }
}
