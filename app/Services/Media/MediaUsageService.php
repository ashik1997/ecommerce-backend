<?php

namespace App\Services\Media;

use App\Models\MediaFile;
use App\Models\Product;
use App\Models\ProductVariantCombination;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MediaUsageService
{
    public function productMediaIds(Product $product): array
    {
        $galleryPaths = is_array($product->multiple_images)
            ? $product->multiple_images
            : (json_decode((string) $product->multiple_images, true) ?: []);

        $paths = collect([
            $product->image,
            $product->notification_image_path,
        ])
            ->merge($galleryPaths)
            ->merge(
                ProductVariantCombination::query()
                    ->where('product_id', $product->id)
                    ->pluck('image')
            )
            ->filter()
            ->unique()
            ->values();

        if ($paths->isEmpty()) {
            return [];
        }

        return MediaFile::query()
            ->whereIn('file_path', $paths)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    public function syncProductMedia(Product $product, array $mediaIds = []): void
    {
        $usageSlug = $this->productUsageSlug($product->id);

        DB::table('media_in_uses')
            ->where('slug', $usageSlug)
            ->delete();

        $creatorId = Auth::id();
        $now = now();
        $rows = [];

        $this->appendUsageRow($rows, [
            'media_id' => $this->normalizeId($mediaIds['product_image_id'] ?? null) ?: $this->mediaIdFromPath($product->image),
            'model' => Product::class,
            'model_id' => $product->id,
            'col_name' => 'image',
            'product_website_id' => $product->product_website_id,
            'creator' => $creatorId,
            'slug' => $usageSlug,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        foreach ($this->normalizeIds($mediaIds['gallery_image_ids'] ?? []) as $mediaId) {
            $this->appendUsageRow($rows, [
                'media_id' => $mediaId,
                'model' => Product::class,
                'model_id' => $product->id,
                'col_name' => 'multiple_images',
                'product_website_id' => $product->product_website_id,
                'creator' => $creatorId,
                'slug' => $usageSlug,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->appendUsageRow($rows, [
            'media_id' => $this->normalizeId($mediaIds['notification_image_id'] ?? null) ?: $this->mediaIdFromPath($product->notification_image_path),
            'model' => Product::class,
            'model_id' => $product->id,
            'col_name' => 'notification_image',
            'product_website_id' => $product->product_website_id,
            'creator' => $creatorId,
            'slug' => $usageSlug,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $variantImageIds = $mediaIds['variant_image_ids'] ?? [];
        ProductVariantCombination::query()
            ->where('product_id', $product->id)
            ->where('status', 1)
            ->get(['id', 'image'])
            ->each(function (ProductVariantCombination $variant) use (&$rows, $variantImageIds, $product, $creatorId, $usageSlug, $now) {
                $this->appendUsageRow($rows, [
                    'media_id' => $this->normalizeId($variantImageIds[$variant->id] ?? null) ?: $this->mediaIdFromPath($variant->image),
                    'model' => ProductVariantCombination::class,
                    'model_id' => $variant->id,
                    'col_name' => 'image',
                    'product_website_id' => $product->product_website_id,
                    'creator' => $creatorId,
                    'slug' => $usageSlug,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });

        if ($rows) {
            DB::table('media_in_uses')->insert($rows);
        }
    }

    public function forgetProduct(Product $product): void
    {
        if (!Schema::hasTable('media_in_uses')) {
            return;
        }

        DB::table('media_in_uses')
            ->where('slug', $this->productUsageSlug($product->id))
            ->delete();
    }

    public function deleteUnusedMedia(array $mediaIds): void
    {
        if (!Schema::hasTable('media_in_uses')) {
            return;
        }

        MediaFile::query()
            ->whereIn('id', $this->normalizeIds($mediaIds))
            ->get()
            ->each(function (MediaFile $media): void {
                $isUsed = DB::table('media_in_uses')
                    ->where('media_id', $media->id)
                    ->where('status', 1)
                    ->exists();

                if (!$isUsed) {
                    $media->forceDelete();
                }
            });
    }

    private function appendUsageRow(array &$rows, array $row): void
    {
        if (empty($row['media_id'])) {
            return;
        }

        $row['status'] = 1;
        $rows[] = $row;
    }

    private function normalizeIds($ids): array
    {
        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }

        if (!is_array($ids)) {
            return [];
        }

        return collect($ids)
            ->map(fn ($id) => $this->normalizeId($id))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeId($id): ?int
    {
        $id = (int) $id;
        return $id > 0 ? $id : null;
    }

    private function mediaIdFromPath(?string $path): ?int
    {
        if (!$path) {
            return null;
        }

        return MediaFile::query()
            ->where('file_path', $path)
            ->value('id');
    }

    private function productUsageSlug(int $productId): string
    {
        return 'product:' . $productId;
    }
}
