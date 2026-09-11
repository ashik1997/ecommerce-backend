<?php

namespace App\Services\FbMarketing;

use App\Models\GeneralInfo;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class FbmFeedProductProjectionService
{
    protected ?string $resolvedSiteUrl = null;

    public const CANONICAL_FLAG = 'is_facebook_product_feed';
    public const LEGACY_FLAG = 'is_facebook_feed';

    public function selectionColumn(): ?string
    {
        if (!Schema::hasTable('products')) {
            return null;
        }

        if (Schema::hasColumn('products', self::CANONICAL_FLAG)) {
            return self::CANONICAL_FLAG;
        }

        if (Schema::hasColumn('products', self::LEGACY_FLAG)) {
            return self::LEGACY_FLAG;
        }

        return null;
    }

    public function selectionMode(): string
    {
        return match ($this->selectionColumn()) {
            self::CANONICAL_FLAG => 'canonical',
            self::LEGACY_FLAG => 'legacy_fallback',
            default => 'unavailable',
        };
    }

    public function selectedProductsQuery(): Builder
    {
        $query = Product::query();
        $column = $this->selectionColumn();

        return $column === null ? $query->whereRaw('1 = 0') : $query->where($column, 1);
    }

    public function feedReadyProductsQuery(): Builder
    {
        return $this->selectedProductsQuery()
            ->where('status', 1)
            ->whereNotNull('name')->where('name', '!=', '')
            ->whereNotNull('image')->where('image', '!=', '')
            ->where('price', '>', 0);
    }

    public function feedReadyProductsForChunking(): Builder
    {
        return $this->feedReadyProductsQuery()
            ->with('brand')
            ->withSum('variantCombinations as fbm_variant_stock_total', 'stock')
            ->orderBy('id');
    }

    public function selectedProductsForDiagnostics(int $limit = 250)
    {
        return $this->selectedProductsQuery()
            ->with('brand')
            ->withSum('variantCombinations as fbm_variant_stock_total', 'stock')
            ->orderByDesc('id')
            ->limit(max(1, min(1000, $limit)))
            ->get()
            ->map(fn(Product $product): array => $this->project($product));
    }

    public function diagnosticWarningCounts(int $limit = 5000): array
    {
        $limit = max(1, min(10000, $limit));
        $counts = [
            'zero_stock_but_feed_in_stock' => 0,
            'positive_stock_but_feed_out_of_stock' => 0,
            'variant_parent_level_export' => 0,
        ];
        $selectedCount = $this->selectedProductsQuery()->count();
        $rows = $this->selectedProductsQuery()
            ->with('brand')
            ->withSum('variantCombinations as fbm_variant_stock_total', 'stock')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        foreach ($rows as $product) {
            $projection = $this->project($product);
            foreach (array_keys($counts) as $flag) {
                if (in_array($flag, $projection['diagnostic_flags'], true)) {
                    $counts[$flag]++;
                }
            }
        }

        return [
            'counts' => $counts,
            'scanned_products' => $rows->count(),
            'truncated' => $selectedCount > $rows->count(),
            'limit' => $limit,
        ];
    }

    public function project(Product $product): array
    {
        $title = trim((string) $product->name);
        $image = trim((string) $product->image);
        $price = is_numeric($product->price) ? (float) $product->price : 0.0;
        $stock = $this->effectiveStock($product);
        $availability = $this->feedAvailability($product);
        $flags = [];

        if ((int) $product->status !== 1) {
            $flags[] = 'inactive';
        }
        if ($title === '') {
            $flags[] = 'missing_title';
        }
        if ($image === '') {
            $flags[] = 'missing_image';
        }
        if ($price <= 0) {
            $flags[] = 'non_positive_price';
        }
        if ($stock <= 0 && $availability === 'in stock') {
            $flags[] = 'zero_stock_but_feed_in_stock';
        }
        if ($stock > 0 && $availability === 'out of stock') {
            $flags[] = 'positive_stock_but_feed_out_of_stock';
        }
        if ((bool) $product->has_variant) {
            $flags[] = 'variant_parent_level_export';
        }

        $valid = !array_intersect($flags, ['inactive', 'missing_title', 'missing_image', 'non_positive_price']);

        return [
            'id' => (int) $product->id,
            'title' => $title,
            'description' => trim(strip_tags((string) ($product->short_description ?? $product->description ?? ''))),
            'link' => $this->productUrl($product),
            'image_link' => $image === '' ? null : $this->imageUrl($image),
            'availability' => $availability,
            'price_amount' => $price,
            'currency' => (string) config('app.currency_code', 'BDT'),
            'brand' => trim((string) ($product->brand?->name ?? config('app.name', ''))),
            'stock_quantity' => $stock,
            'has_variant' => (bool) $product->has_variant,
            'diagnostic_flags' => array_values(array_unique($flags)),
            'is_feed_ready' => $valid,
        ];
    }

    public function siteUrl(): string
    {
        if ($this->resolvedSiteUrl !== null) {
            return $this->resolvedSiteUrl;
        }

        $generalInfo = Schema::hasTable('general_infos') ? GeneralInfo::query()->find(1) : null;
        $candidate = trim((string) ($generalInfo?->website ?: config('app.url', '')));
        if ($candidate !== '' && !preg_match('#^https?://#i', $candidate)) {
            $candidate = 'https://' . ltrim($candidate, '/');
        }

        return $this->resolvedSiteUrl = filter_var($candidate, FILTER_VALIDATE_URL)
            ? rtrim($candidate, '/')
            : rtrim((string) url('/'), '/');
    }

    protected function productUrl(Product $product): string
    {
        return $this->siteUrl() . '/products/' . ($product->slug ?: $product->id);
    }

    protected function imageUrl(string $image): string
    {
        return function_exists('get_file_url')
            ? rtrim((string) get_file_url(), '/') . '/' . ltrim($image, '/')
            : asset($image);
    }

    protected function effectiveStock(Product $product): float
    {
        if ((bool) $product->has_variant) {
            $value = $product->getAttribute('fbm_variant_stock_total');
            if ($value === null && $product->relationLoaded('variantCombinations')) {
                $value = $product->variantCombinations->sum('stock');
            }

            if ($value === null) {
                $value = $product->variantCombinations()->sum('stock');
            }

            return is_numeric($value) ? (float) $value : 0.0;
        }

        return is_numeric($product->stock ?? null) ? (float) $product->stock : 0.0;
    }

    protected function feedAvailability(Product $product): string
    {
        return strtolower(trim((string) ($product->availability_status ?? 'in_stock'))) === 'in_stock'
            ? 'in stock'
            : 'out of stock';
    }
}
