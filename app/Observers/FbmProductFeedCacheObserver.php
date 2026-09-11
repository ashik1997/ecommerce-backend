<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\FbMarketing\FbmProductFeedCacheService;

class FbmProductFeedCacheObserver
{
    public function saved(Product $product): void
    {
        $this->invalidate();
    }

    public function deleted(Product $product): void
    {
        $this->invalidate();
    }

    public function restored(Product $product): void
    {
        $this->invalidate();
    }

    private function invalidate(): void
    {
        app(FbmProductFeedCacheService::class)->invalidate();
    }
}
