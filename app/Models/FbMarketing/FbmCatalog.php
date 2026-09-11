<?php

namespace App\Models\FbMarketing;

class FbmCatalog extends FbmDiscoverableAsset
{
    public const ASSET_TYPE = 'catalogs';
    protected $table = 'fbm_catalogs';

    public function productMappings()
    {
        return $this->hasMany(FbmCatalogProductMapping::class, 'fbm_catalog_id');
    }

    public function productSets()
    {
        return $this->hasMany(FbmProductSet::class, 'fbm_catalog_id');
    }

    protected function safeMetadata(): array
    {
        return ['vertical' => $this->vertical];
    }
}
