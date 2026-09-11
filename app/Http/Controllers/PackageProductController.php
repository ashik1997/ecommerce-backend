<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Inventory\Models\ProductStock;
use App\Http\Controllers\Inventory\Models\ProductWarehouse;
use App\Models\Category;
use App\Models\Color;
use App\Models\MediaFile;
use App\Models\PackageProduct;
use App\Models\PackageProductItem;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\ProductWebsite;
use App\Models\ProductVariant;
use App\Models\ProductVariantCombination;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Yajra\DataTables\DataTables;

class PackageProductController extends Controller
{
    // -------------------------------------------------------------------------
    // INDEX
    // -------------------------------------------------------------------------

    public function index()
    {
        return view('backend.package_product.index');
    }

    // -------------------------------------------------------------------------
    // DATATABLE
    // -------------------------------------------------------------------------

    public function getData(Request $request)
    {
        if (!$request->ajax()) {
            return;
        }

        // Packages are now fully self-contained — no join to products table
        $data = PackageProduct::query()
            ->leftJoin('categories', 'package_products.category_id', '=', 'categories.id')
            ->select(
                'package_products.*',
                'package_products.created_at',
                'categories.name as category_name',
                'categories.id as category_id',
            );

        return DataTables::of($data)
            ->addColumn('image', function ($row) {
                $imagePath = $row->primary_media_url
                    ? $row->primary_media_url
                    : asset('assets/images/default-product.png');
                return '<img src="' . $imagePath . '" class="gridProductImage" style="width:50px;height:50px;object-fit:cover;">';
            })
            ->addColumn('price', function ($row) {
                $price = '৳' . number_format($row->package_price ?? 0, 2);
                if ($row->compare_at_price && $row->compare_at_price > $row->package_price) {
                    $price .= '<br><small class="text-muted"><del>৳' . number_format($row->compare_at_price, 2) . '</del></small>';
                }
                return $price;
            })
            ->addColumn('status', function ($row) {
                $map = ['active' => 'badge-success', 'draft' => 'badge-secondary', 'inactive' => 'badge-warning'];
                $cls = $map[$row->status] ?? 'badge-secondary';
                return '<span class="badge ' . $cls . '">' . Str::title($row->status) . '</span>';
            })
            ->addColumn('package_items_count', function ($row) {
                $count = PackageProductItem::where('package_product_id', $row->id)->count();
                return '<span class="badge badge-info">' . $count . ' items</span>';
            })
            ->addColumn('action', function ($row) {
                $frontendBase = rtrim(env('APP_FRONTEND_URL'), '/');
                $previewUrl   = $frontendBase . '/landing/' . $row->id . '/' . $row->slug;

                $btn  = '<a target="_blank" href="' . $previewUrl . '" class="btn btn-sm btn-info mb-1"><i class="fas fa-eye"></i> Preview</a> ';
                $btn .= '<a href="' . route('PackageProducts.Edit', $row->id) . '" class="btn btn-sm btn-warning mb-1"><i class="fas fa-edit"></i> Edit</a> ';
                $btn .= '<a href="javascript:void(0)" data-id="' . $row->id . '" class="btn btn-sm btn-danger mb-1 deleteBtn"><i class="fas fa-trash"></i> Delete</a>';
                return $btn;
            })
            ->addIndexColumn()
            ->rawColumns(['image', 'price', 'status', 'package_items_count', 'action'])
            ->make(true);
    }

    // -------------------------------------------------------------------------
    // CREATE
    // -------------------------------------------------------------------------

    public function create()
    {
        return view('backend.package_product.create', $this->formMasterData());
    }

    // -------------------------------------------------------------------------
    // STORE
    // -------------------------------------------------------------------------

    public function store(Request $request): JsonResponse
    {
        $validator = $this->makeValidator($request->all());

        if ($validator->fails()) {
            return $this->validationFailed($validator);
        }

        $validated  = $validator->validated();
        $overview   = $validated['overview'];
        $pricing    = $validated['pricing'];
        $mediaPayload = $validated['media'] ?? [];
        $infoPayload  = $validated['info'] ?? [];
        $seoPayload   = $validated['seo'] ?? [];
        $itemsPayload = $validated['items'];

        // ── Hero image (required) ─────────────────────────────────────────────
        $mediaHeroId = $mediaPayload['hero_image_id'] ?? null;
        if (!$mediaHeroId) {
            return response()->json(['success' => false, 'message' => 'Package hero image is required.'], 422);
        }

        $heroMedia = MediaFile::find($mediaHeroId);
        if (!$heroMedia) {
            return response()->json(['success' => false, 'message' => 'Hero image not found.'], 422);
        }

        $galleryMedia = collect($mediaPayload['gallery'] ?? [])->pluck('id')->filter()->values();

        // ── Resolve & validate items ─────────────────────────────────────────
        [$items, $itemsTotal, $compareTotal, $itemError] = $this->resolveItems($itemsPayload);
        if ($itemError) {
            return response()->json(['success' => false, 'message' => $itemError], 422);
        }

        // ── Pricing ───────────────────────────────────────────────────────────
        $packagePrice = (float) ($pricing['package_price'] ?? 0);
        $comparePrice = $this->resolveComparePrice($pricing, $compareTotal);

        if ($comparePrice < $packagePrice) {
            return response()->json([
                'success' => false,
                'message' => 'Compare price must be ≥ package selling price.',
            ], 422);
        }

        // ── Package code ──────────────────────────────────────────────────────
        [$packageCode, $codeError] = $this->resolvePackageCode($overview['package_code'] ?? null);
        if ($codeError) {
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => ['overview.package_code' => [$codeError]]], 422);
        }

        $packageSlug = $this->uniquePackageSlug($overview['slug'] ?? $overview['title'] ?? 'package');

        DB::beginTransaction();
        try {
            $savingsAmount  = max(0, $comparePrice - $packagePrice);
            $savingsPercent = $comparePrice > 0 ? round(($savingsAmount / $comparePrice) * 100, 2) : 0;

            // Persist hero image
            $heroMedia->markAsPermanent();
            $galleryPaths = $this->persistGallery($galleryMedia);

            // Create the package — NO product record created
            $package = PackageProduct::create([
                'package_code'              => $packageCode,
                'title'                     => $overview['title'],
                'slug'                      => $packageSlug,
                'tagline'                   => $overview['tagline'] ?? null,
                'product_website_id'        => $overview['product_website_id'] ?? null,
                'status'                    => $infoPayload['status'] ?? 'draft',
                'visibility'                => $infoPayload['visibility'] ?? 'private',
                'publish_at'                => !empty($infoPayload['publish_at']) ? Carbon::parse($infoPayload['publish_at']) : null,
                'category_id'               => $infoPayload['category_id'] ?? null,
                'short_description'         => $infoPayload['short_description'] ?? null,
                'description'               => $infoPayload['description'] ?? null,
                'image'                     => $heroMedia->file_path,
                'multiple_images'           => !empty($galleryPaths) ? json_encode($galleryPaths) : null,
                'package_price'             => $packagePrice,
                'compare_at_price'          => $comparePrice,
                'calculated_savings_amount' => $savingsAmount,
                'calculated_savings_percent' => $savingsPercent,
                'pricing_breakdown'         => [
                    'items_total'   => round($itemsTotal, 2),
                    'compare_total' => round($compareTotal, 2),
                    'package_price' => round($packagePrice, 2),
                    'savings_amount' => round($savingsAmount, 2),
                    'savings_percent' => $savingsPercent,
                    'items_count'   => count($items),
                ],
                'hero_section' => [
                    'headline'    => $overview['hero_headline'] ?? $overview['title'],
                    'subheadline' => $overview['hero_subheadline'] ?? null,
                    'cta_label'   => $overview['hero_cta_label'] ?? null,
                    'cta_link'    => $overview['hero_cta_link'] ?? null,
                    'media_id'    => $heroMedia->id,
                    'media_url'   => $heroMedia->url,
                ],
                'primary_media_id'    => $heroMedia->id,
                'gallery_media_ids'   => $galleryMedia->values()->toArray(),
                'content_blocks'      => $infoPayload['content_blocks'] ?? [],
                'landing_settings'    => [
                    'highlights'    => $infoPayload['highlights'] ?? [],
                    'feature_list'  => $infoPayload['features'] ?? [],
                ],
                'meta_title'       => $seoPayload['meta_title'] ?? null,
                'meta_description' => $seoPayload['meta_description'] ?? null,
                'meta_keywords'    => $seoPayload['meta_keywords'] ?? null,
                'meta_image_id'    => $seoPayload['meta_image_id'] ?? null,
                'created_by'       => Auth::id(),
                'updated_by'       => Auth::id(),
            ]);

            $this->saveItems($package->id, $items);

            DB::commit();

            return response()->json([
                'success'  => true,
                'message'  => 'Package created successfully.',
                'redirect' => route('PackageProducts.Edit', $package->id),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return $this->serverError($e);
        }
    }

    // -------------------------------------------------------------------------
    // EDIT
    // -------------------------------------------------------------------------

    public function edit($id)
    {
        // Root entity is now PackageProduct, not Product
        $package = PackageProduct::with(['heroMedia', 'metaImage', 'items.product'])
            ->findOrFail($id);

        $heroPreview = optional($package->heroMedia)->url
            ?? ($package->image ? asset($package->image) : null);

        $galleryMediaIds = $package->gallery_media_ids ?? [];
        $galleryMedia    = !empty($galleryMediaIds)
            ? MediaFile::whereIn('id', $galleryMediaIds)->get()->keyBy('id')
            : collect();

        $gallery = collect($galleryMediaIds)->map(fn($mid) => [
            'id'      => $mid,
            'preview' => optional($galleryMedia->get($mid))->url,
            'token'   => null,
        ])->values()->all();

        while (count($gallery) < 4) {
            $gallery[] = ['id' => null, 'preview' => null, 'token' => null];
        }

        $items = $package->items()
            ->with('product')
            ->orderBy('position')
            ->get()
            ->map(function (PackageProductItem $item) {
                $product = $item->product;
                if (!$product) {
                    return null;
                }

                $matrix       = $this->prepareProductVariantMatrix($product);
                $basePrice    = $product->discount_price && $product->discount_price > 0
                    ? $product->discount_price
                    : ($product->price ?? 0);
                $snapshot     = $item->variant_snapshot ?? [];

                $displayTitle = $item->title ?: $product->name;
                $imagePath    = $item->image ?: $product->image;
                $imageUrl     = $imagePath ? get_file_url() . '/' . ltrim($imagePath, '/') : asset('assets/images/default-product.png');

                return [
                    'key'                  => 'existing-' . $item->id,
                    'product_id'           => $product->id,
                    'product_name'         => $product->name,
                    'title'                => $displayTitle,
                    'sku'                  => $product->sku,
                    'image_url'            => $imageUrl,
                    'image'                => $item->image,
                    'variant_type'         => $matrix['variant_type'],
                    'variant_options'      => [
                        'combinations'    => $matrix['combinations'],
                        'legacy_variants' => $matrix['legacy_variants'],
                        'colors'          => $matrix['colors'],
                        'sizes'           => $matrix['sizes'],
                    ],
                    'variant_combination_id' => $item->variant_combination_id,
                    'product_variant_id'     => $item->product_variant_id,
                    'color_id'               => $item->color_id,
                    'size_id'                => $item->size_id,
                    'variant_snapshot'       => $snapshot,
                    'variant_snapshot_text'  => $this->formatSnapshotText($snapshot),
                    'quantity'               => $item->quantity,
                    'unit_price'             => (float) ($item->unit_price ?? $basePrice),
                    'compare_at_price'       => (float) ($item->compare_at_price ?? ($product->price ?? $basePrice)),
                ];
            })
            ->filter()
            ->values();

        $pricingBreakdown    = $package->pricing_breakdown ?? [];
        $allowCompareOverride = isset($pricingBreakdown['compare_total'])
            ? (float) $pricingBreakdown['compare_total'] !== (float) $package->compare_at_price
            : false;

        $packageState = [
            'overview' => [
                'title'            => $package->title,
                'package_code'     => $package->package_code,
                'slug'             => $package->slug,
                'tagline'          => $package->tagline,
                'product_website_id' => $package->product_website_id,
                'hero_headline'    => $package->hero_section['headline'] ?? null,
                'hero_subheadline' => $package->hero_section['subheadline'] ?? null,
                'hero_cta_label'   => $package->hero_section['cta_label'] ?? null,
                'hero_cta_link'    => $package->hero_section['cta_link'] ?? null,
            ],
            'pricing' => [
                'package_price'        => (float) $package->package_price,
                'compare_at_price'     => (float) $package->compare_at_price,
                'allow_compare_override' => $allowCompareOverride ? 1 : 0,
            ],
            'media' => [
                'hero' => [
                    'id'      => $package->primary_media_id,
                    'preview' => $heroPreview,
                    'token'   => null,
                ],
                'gallery' => array_slice($gallery, 0, 4),
            ],
            'items' => $items,
            'info' => [
                'status'             => $package->status,
                'visibility'         => $package->visibility,
                'publish_at'         => optional($package->publish_at)?->format('Y-m-d\TH:i'),
                'category_id'        => $package->category_id,
                'short_description'  => $package->short_description,
                'description'        => $package->description,
                'highlights'         => $package->landing_settings['highlights'] ?? [],
                'content_blocks_raw' => $package->content_blocks
                    ? json_encode($package->content_blocks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                    : '',
            ],
            'seo' => [
                'meta_title'       => $package->meta_title,
                'meta_keywords'    => $package->meta_keywords,
                'meta_description' => $package->meta_description,
                'meta_image'       => [
                    'id'      => $package->meta_image_id,
                    'preview' => optional($package->metaImage)->url,
                    'token'   => null,
                ],
            ],
            'timestamp' => now()->toIso8601String(),
        ];

        return view('backend.package_product.edit', array_merge($this->formMasterData(), [
            'package'      => $package,
            'packageState' => $packageState,
        ]));
    }

    // -------------------------------------------------------------------------
    // UPDATE
    // -------------------------------------------------------------------------

    public function update(Request $request, $id): JsonResponse
    {
        $package = PackageProduct::findOrFail($id);

        $validator = $this->makeValidator($request->all(), $package->id);

        if ($validator->fails()) {
            return $this->validationFailed($validator);
        }

        $validated    = $validator->validated();
        $overview     = $validated['overview'];
        $pricing      = $validated['pricing'];
        $mediaPayload = $validated['media'] ?? [];
        $infoPayload  = $validated['info'] ?? [];
        $seoPayload   = $validated['seo'] ?? [];
        $itemsPayload = $validated['items'];

        // ── Hero image ────────────────────────────────────────────────────────
        $mediaHeroId = $mediaPayload['hero_image_id'] ?? $package->primary_media_id;
        if (!$mediaHeroId) {
            return response()->json(['success' => false, 'message' => 'Package hero image is required.'], 422);
        }

        $heroMedia = MediaFile::find($mediaHeroId);
        if (!$heroMedia) {
            return response()->json(['success' => false, 'message' => 'Hero image not found.'], 422);
        }

        $galleryMedia = collect($mediaPayload['gallery'] ?? [])->pluck('id')->filter()->values();

        // ── Items ─────────────────────────────────────────────────────────────
        [$items, $itemsTotal, $compareTotal, $itemError] = $this->resolveItems($itemsPayload);
        if ($itemError) {
            return response()->json(['success' => false, 'message' => $itemError], 422);
        }

        // ── Pricing ───────────────────────────────────────────────────────────
        $packagePrice = (float) ($pricing['package_price'] ?? 0);
        $comparePrice = $this->resolveComparePrice($pricing, $compareTotal);

        if ($comparePrice < $packagePrice) {
            return response()->json([
                'success' => false,
                'message' => 'Compare price must be ≥ package selling price.',
            ], 422);
        }

        // ── Package code ──────────────────────────────────────────────────────
        [$packageCode, $codeError] = $this->resolvePackageCode(
            $overview['package_code'] ?? $package->package_code,
            $package->id
        );
        if ($codeError) {
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => ['overview.package_code' => [$codeError]]], 422);
        }

        $packageSlug = $this->uniquePackageSlug(
            $overview['slug'] ?? $package->slug ?? $overview['title'] ?? 'package',
            $package->id
        );

        DB::beginTransaction();
        try {
            $savingsAmount  = max(0, $comparePrice - $packagePrice);
            $savingsPercent = $comparePrice > 0 ? round(($savingsAmount / $comparePrice) * 100, 2) : 0;

            $heroMedia->markAsPermanent();
            $galleryPaths = $this->persistGallery($galleryMedia);

            $package->update([
                'package_code'              => $packageCode,
                'title'                     => $overview['title'],
                'slug'                      => $packageSlug,
                'tagline'                   => $overview['tagline'] ?? null,
                'product_website_id'        => $overview['product_website_id'] ?? $package->product_website_id,
                'status'                    => $infoPayload['status'] ?? 'draft',
                'visibility'                => $infoPayload['visibility'] ?? 'private',
                'publish_at'                => !empty($infoPayload['publish_at']) ? Carbon::parse($infoPayload['publish_at']) : null,
                'category_id'               => $infoPayload['category_id'] ?? null,
                'short_description'         => $infoPayload['short_description'] ?? null,
                'description'               => $infoPayload['description'] ?? null,
                'image'                     => $heroMedia->file_path,
                'multiple_images'           => !empty($galleryPaths) ? json_encode($galleryPaths) : null,
                'package_price'             => $packagePrice,
                'compare_at_price'          => $comparePrice,
                'calculated_savings_amount' => $savingsAmount,
                'calculated_savings_percent' => $savingsPercent,
                'pricing_breakdown'         => [
                    'items_total'    => round($itemsTotal, 2),
                    'compare_total'  => round($compareTotal, 2),
                    'package_price'  => round($packagePrice, 2),
                    'savings_amount' => round($savingsAmount, 2),
                    'savings_percent' => $savingsPercent,
                    'items_count'    => count($items),
                ],
                'hero_section' => [
                    'headline'    => $overview['hero_headline'] ?? $overview['title'],
                    'subheadline' => $overview['hero_subheadline'] ?? null,
                    'cta_label'   => $overview['hero_cta_label'] ?? null,
                    'cta_link'    => $overview['hero_cta_link'] ?? null,
                    'media_id'    => $heroMedia->id,
                    'media_url'   => $heroMedia->url,
                ],
                'primary_media_id'    => $heroMedia->id,
                'gallery_media_ids'   => $galleryMedia->values()->toArray(),
                'content_blocks'      => $infoPayload['content_blocks'] ?? null,
                'landing_settings'    => [
                    'highlights' => $infoPayload['highlights'] ?? [],
                ],
                'meta_title'       => $seoPayload['meta_title'] ?? null,
                'meta_description' => $seoPayload['meta_description'] ?? null,
                'meta_keywords'    => $seoPayload['meta_keywords'] ?? null,
                'meta_image_id'    => $seoPayload['meta_image_id'] ?? null,
                'updated_by'       => Auth::id(),
            ]);

            // Replace all items (full sync)
            PackageProductItem::where('package_product_id', $package->id)->delete();
            $this->saveItems($package->id, $items);

            DB::commit();

            return response()->json([
                'success'  => true,
                'message'  => 'Package updated successfully.',
                'redirect' => route('PackageProducts.Edit', $package->id),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return $this->serverError($e);
        }
    }

    // -------------------------------------------------------------------------
    // DESTROY
    // -------------------------------------------------------------------------

    public function destroy($id)
    {
        $package = PackageProduct::findOrFail($id);

        PackageProductItem::where('package_product_id', $package->id)->delete();
        $package->delete();

        return response()->json(['success' => 'Package deleted successfully.']);
    }

    // -------------------------------------------------------------------------
    // PRODUCT SEARCH  (catalog picker)
    // -------------------------------------------------------------------------

    public function searchProducts(Request $request): JsonResponse
    {
        $term  = trim($request->get('q', ''));
        $limit = (int) $request->get('limit', 20);

        $query = Product::query()
            ->where('status', 1)
            ->where('is_package', 0);   // never surface packages in catalog

        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', '%' . $term . '%')
                    ->orWhere('sku', 'like', '%' . $term . '%')
                    ->orWhere('code', 'like', '%' . $term . '%')
                    ->orWhere('barcode', 'like', '%' . $term . '%');
            });
        }

        $products = $query->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(function (Product $product) {
                $hasCombos     = $product->variantCombinations()->active()->exists();
                $legacyVariants = $product->variants()->exists();
                $variantType   = $hasCombos ? 'combination' : ($legacyVariants ? 'legacy' : 'simple');

                $effectivePrice = $product->discount_price && $product->discount_price > 0
                    ? $product->discount_price
                    : $product->price;

                return [
                    'id'             => $product->id,
                    'name'           => $product->name,
                    'slug'           => $product->slug,
                    'sku'            => $product->sku,
                    'variant_type'   => $variantType,
                    'price'          => $product->price,
                    'discount_price' => $product->discount_price,
                    'effective_price' => $effectivePrice,
                    'image_url'      => get_file_url() . '/' . $product->image,
                    'total_stock'    => $product->totalStock() ?? 0,
                ];
            });

        return response()->json(['success' => true, 'results' => $products]);
    }

    // -------------------------------------------------------------------------
    // PRODUCT VARIANT MATRIX
    // -------------------------------------------------------------------------

    public function productMatrix($productId): JsonResponse
    {
        $product = Product::where('id', $productId)
            ->where('is_package', 0)
            ->firstOrFail();

        $matrix = $this->prepareProductVariantMatrix($product);

        return response()->json([
            'success'      => true,
            'variant_type' => $matrix['variant_type'],
            'product'      => $matrix['product'],
            'combinations' => $matrix['combinations'],
            'legacy_variants' => $matrix['legacy_variants'],
            'colors'       => $matrix['colors'],
            'sizes'        => $matrix['sizes'],
            'total_stock'  => $matrix['product']['total_stock'],
            'has_variants' => $matrix['variant_type'] !== 'simple',
        ]);
    }

    public function getProductVariants($productId)
    {
        return $this->productMatrix($productId);
    }

    public function getVariantStock(Request $request, $productId)
    {
        $query = DB::table('product_variants')->where('product_id', $productId);
        if ($request->color_id) {
            $query->where('color_id', $request->color_id);
        }
        if ($request->size_id) {
            $query->where('size_id', $request->size_id);
        }

        return response()->json(['stock' => $query->sum('stock')]);
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * Shared form master data (statuses, visibility, categories).
     */
    private function formMasterData(): array
    {
        return [
            'statuses' => [
                ['value' => 'draft',    'label' => 'Draft'],
                ['value' => 'active',   'label' => 'Active'],
                ['value' => 'inactive', 'label' => 'Inactive'],
            ],
            'visibilityOptions' => [
                ['value' => 'private',   'label' => 'Private (internal only)'],
                ['value' => 'public',    'label' => 'Public'],
                ['value' => 'scheduled', 'label' => 'Scheduled'],
            ],
            'categories' => Category::where('status', 1)
                ->select('id', 'name')
                ->orderBy('name')
                ->get(),
            'websites' => ProductWebsite::where('status', 'active')
                ->orderBy('title')
                ->get(['id', 'title as name']),
        ];
    }

    /**
     * Build the shared Validator for store and update.
     */
    private function makeValidator(array $data, ?int $ignorePackageId = null)
    {
        return Validator::make($data, [
            'overview.title'            => ['required', 'string', 'max:255'],
            'overview.slug'             => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/'],
            'overview.package_code'     => ['nullable', 'string', 'max:40'],
            'overview.tagline'          => ['nullable', 'string', 'max:255'],
            'overview.hero_headline'    => ['nullable', 'string', 'max:255'],
            'overview.hero_subheadline' => ['nullable', 'string', 'max:500'],
            'overview.hero_cta_label'   => ['nullable', 'string', 'max:120'],
            'overview.hero_cta_link'    => ['nullable', 'string', 'max:255'],
            // 'overview.product_website_id' => ['required', 'exists:product_websites,id'],
            'media.hero_image_id'       => ['nullable', 'exists:media_files,id'],
            'media.gallery'             => ['nullable', 'array'],
            'media.gallery.*.id'        => ['required_with:media.gallery', 'exists:media_files,id'],
            'pricing.package_price'     => ['required', 'numeric', 'min:0'],
            'pricing.compare_at_price'  => ['nullable', 'numeric', 'min:0'],
            'pricing.allow_compare_override' => ['nullable', 'boolean'],
            'items'                          => ['required', 'array', 'min:1'],
            'items.*.product_id'             => ['required', 'exists:products,id'],
            'items.*.quantity'               => ['required', 'integer', 'min:1'],
            'items.*.unit_price'             => ['nullable', 'numeric', 'min:0'],
            'items.*.compare_at_price'       => ['nullable', 'numeric', 'min:0'],
            'items.*.product_variant_id'     => ['nullable', 'exists:product_variants,id'],
            'items.*.variant_combination_id' => ['nullable', 'exists:product_variant_combinations,id'],
            // 'items.*.color_id'               => ['nullable', 'exists:colors,id'],
            // 'items.*.size_id'                => ['nullable', 'exists:product_sizes,id'],
            'items.*.title'                  => ['nullable', 'string', 'max:255'],
            'items.*.image'                  => ['nullable', 'string', 'max:255'],
            'info.status'           => ['required', 'in:draft,active,inactive'],
            'info.visibility'       => ['required', 'in:private,public,scheduled'],
            'info.publish_at'       => ['nullable', 'date'],
            'info.category_id'      => ['nullable', 'exists:categories,id'],
            'info.short_description' => ['nullable', 'string'],
            'info.description'      => ['nullable', 'string'],
            'info.highlights'       => ['nullable', 'array', 'max:6'],
            'seo.meta_title'        => ['nullable', 'string', 'max:255'],
            'seo.meta_keywords'     => ['nullable', 'string', 'max:500'],
            'seo.meta_description'  => ['nullable', 'string'],
            'seo.meta_image_id'     => ['nullable', 'exists:media_files,id'],
        ], [
            'overview.slug.regex' => 'Slug may only contain lowercase letters, numbers and hyphen.',
        ]);
    }

    /**
     * Resolve, validate, and enrich all package items.
     * Returns [$items, $itemsTotal, $compareTotal, $errorMessage|null]
     */
    private function resolveItems(array $itemsPayload): array
    {
        $items        = [];
        $itemsTotal   = 0;
        $compareTotal = 0;

        foreach ($itemsPayload as $itemPayload) {
            $childProduct = Product::where('id', $itemPayload['product_id'])
                ->where('is_package', 0)
                ->first();

            if (!$childProduct) {
                return [[], 0, 0, 'Selected product no longer exists or is not eligible for packages.'];
            }

            $variantSnapshot      = [];
            $variantCombinationId = $itemPayload['variant_combination_id'] ?? null;
            $productVariantId     = $itemPayload['product_variant_id'] ?? null;
            $combination          = null;

            if ($variantCombinationId) {
                $combination = ProductVariantCombination::where('id', $variantCombinationId)
                    ->where('product_id', $childProduct->id)
                    ->first();
                if (!$combination) {
                    return [[], 0, 0, "Invalid variant combination for {$childProduct->name}."];
                }
            }

            if ($productVariantId) {
                $legacyVariant = ProductVariant::where('id', $productVariantId)
                    ->where('product_id', $childProduct->id)
                    ->first();
                if (!$legacyVariant) {
                    return [[], 0, 0, "Invalid variant for {$childProduct->name}."];
                }
            }

            if (!empty($itemPayload['color_id'])) {
                $variantSnapshot['Color'] = optional(Color::find($itemPayload['color_id']))->name;
            }
            if (!empty($itemPayload['size_id'])) {
                $variantSnapshot['Size'] = optional(ProductSize::find($itemPayload['size_id']))->name;
            }
            if (!empty($combination?->variant_values)) {
                foreach ($combination->variant_values as $key => $value) {
                    $variantSnapshot[Str::title(str_replace('_', ' ', $key))] = $value;
                }
            }

            $unitPrice = isset($itemPayload['unit_price'])
                ? (float) $itemPayload['unit_price']
                : ($childProduct->discount_price > 0 ? $childProduct->discount_price : $childProduct->price);

            $compareAtPrice = isset($itemPayload['compare_at_price'])
                ? (float) $itemPayload['compare_at_price']
                : ($childProduct->price ?? $unitPrice);

            $quantity      = (int) $itemPayload['quantity'];
            $itemsTotal   += $unitPrice * $quantity;
            $compareTotal += $compareAtPrice * $quantity;

            $items[] = [
                'product'                => $childProduct,
                'quantity'               => $quantity,
                'unit_price'             => $unitPrice,
                'compare_at_price'       => $compareAtPrice,
                'product_variant_id'     => $productVariantId,
                'variant_combination_id' => $variantCombinationId,
                'color_id'               => $itemPayload['color_id'] ?? null,
                'size_id'                => $itemPayload['size_id'] ?? null,
                'variant_snapshot'       => array_filter($variantSnapshot),
                'title'                  => $itemPayload['title'] ?? null,
                'image'                  => $itemPayload['image'] ?? null,
            ];
        }

        return [$items, $itemsTotal, $compareTotal, null];
    }

    /**
     * Persist PackageProductItems.
     */
    private function saveItems(int $packageId, array $items): void
    {
        foreach ($items as $position => $item) {
            PackageProductItem::create([
                'package_product_id'     => $packageId,
                'package_id'             => $packageId,
                'product_id'             => $item['product']->id,
                'product_variant_id'     => $item['product_variant_id'],
                'variant_combination_id' => $item['variant_combination_id'],
                'color_id'               => $item['color_id'],
                'size_id'                => $item['size_id'],
                'quantity'               => $item['quantity'],
                'unit_price'             => $item['unit_price'],
                'compare_at_price'       => $item['compare_at_price'],
                'variant_snapshot'       => $item['variant_snapshot'],
                'title'                  => $item['title'] ?? null,
                'image'                  => $item['image'] ?? null,
                'position'               => $position + 1,
            ]);
        }
    }

    /**
     * Resolve compare price from pricing payload or fallback to compare_total.
     */
    private function resolveComparePrice(array $pricing, float $compareTotal): float
    {
        if (!empty($pricing['allow_compare_override']) && isset($pricing['compare_at_price'])) {
            return (float) $pricing['compare_at_price'];
        }
        return $compareTotal;
    }

    /**
     * Resolve/validate package code. Returns [$code, $errorMessage|null].
     */
    private function resolvePackageCode(?string $raw, ?int $ignoreId = null): array
    {
        if ($raw) {
            $code = strtoupper(trim($raw));
            if ($code === '') {
                return [$this->generatePackageCode(), null];
            }

            $exists = PackageProduct::where('package_code', $code)
                ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
                ->exists();

            if ($exists) {
                return [null, 'The package code has already been taken.'];
            }

            return [$code, null];
        }

        return [$this->generatePackageCode(), null];
    }

    /**
     * Mark gallery media as permanent and return file paths.
     */
    private function persistGallery($galleryMedia): array
    {
        if ($galleryMedia->isEmpty()) {
            return [];
        }

        $galleryFiles = MediaFile::whereIn('id', $galleryMedia)->get();
        MediaFile::whereIn('id', $galleryMedia)->update(['is_temp' => false, 'temp_token' => null]);

        return $galleryFiles->pluck('file_path')->filter()->values()->toArray();
    }

    public function prepareProductVariantMatrix(Product $product): array
    {
        $comboVariants = $product->variantCombinations()->active()->get();
        $final_stock = 0;
        $combinations = $comboVariants->map(function (ProductVariantCombination $combination) use ($product, &$final_stock) {
            $attributes = $combination->variant_values ?? [];

            $combinition_stock = ProductStock::where('product_id', $product->id)
                ->where('variant_combination_id', $combination->id)
                ->leftJoin('product_warehouses', 'product_stocks.product_warehouse_id', '=', 'product_warehouses.id')
                ->leftJoin('product_warehouse_rooms', 'product_stocks.product_warehouse_room_id', '=', 'product_warehouse_rooms.id')
                ->leftJoin('product_warehouse_room_cartoons', 'product_stocks.product_warehouse_room_cartoon_id', '=', 'product_warehouse_room_cartoons.id')
                ->select(
                    'product_stocks.qty as stock',

                    'product_warehouses.id as warehouse_id',
                    'product_warehouses.title as warehouse_name',

                    'product_warehouse_rooms.id as room_id',
                    'product_warehouse_rooms.title as room_name',

                    'product_warehouse_room_cartoons.id as cartoon_id',
                    'product_warehouse_room_cartoons.title as cartoon_name'
                )
                ->get();

            $totalStock = $combinition_stock->sum('stock');
            $final_stock += $totalStock;

            $warehouse_stocks = $combinition_stock
                ->groupBy(fn($r) => $r->warehouse_name ?? '')
                ->filter(fn($_, $name) => $name !== '')
                ->map(fn($group) => [
                    'id'    => $group->first()->warehouse_id,
                    'name'  => $group->first()->warehouse_name,
                    'stock' => (int) $group->sum('stock'),
                ])
                ->values()
                ->toArray();

            $room_stocks = $combinition_stock
                ->groupBy(fn($r) => ($r->warehouse_id ?? '') . '_' . ($r->room_id ?? ''))
                ->filter(fn($group) => $group->first()->warehouse_id && $group->first()->room_id)
                ->map(fn($group) => [
                    'id'           => $group->first()->room_id,
                    'warehouse_id' => $group->first()->warehouse_id,
                    'name'         => $group->first()->room_name,
                    'stock'        => (int) $group->sum('stock'),
                ])
                ->values()
                ->toArray();

            $cartoon_stocks = $combinition_stock
                ->groupBy(fn($r) => ($r->warehouse_id ?? '') . '_' . ($r->room_id ?? '') . '_' . ($r->cartoon_id ?? ''))
                ->filter(fn($group) => $group->first()->warehouse_id && $group->first()->room_id && $group->first()->cartoon_id)
                ->map(fn($group) => [
                    'id'           => $group->first()->cartoon_id,
                    'warehouse_id' => $group->first()->warehouse_id,
                    'room_id'      => $group->first()->room_id,
                    'stock'        => (int) $group->sum('stock'),
                ])
                ->values()
                ->toArray();

            return [
                'id'               => $combination->id,
                'combination_key'  => $combination->combination_key,
                'attributes'       => $attributes,
                'display'          => $this->formatVariantDisplay($attributes),
                'price'            => $combination->price ?? null,
                'discount_price'   => $combination->discount_price ?? null,
                'additional_price' => $combination->additional_price ?? 0,
                'sku'              => $combination->sku ?? null,
                'image_url'        => get_file_url() . '/' . ($combination->image ?? $product->image),
                'stock'            => $totalStock,
                'warehouse_stocks' => $warehouse_stocks,
                'room_stocks'      => $room_stocks,
                'cartoon_stocks'   => $cartoon_stocks,
            ];
        })
            ->values()
            ->toArray();

        $legacyVariantsCollection = $product->variants()->with(['color', 'size'])->get();

        $legacyVariants = $legacyVariantsCollection->map(fn(ProductVariant $v) => [
            'id'         => $v->id,
            'color_id'   => $v->color_id,
            'color_name' => optional($v->color)->name,
            'size_id'    => $v->size_id,
            'size_name'  => optional($v->size)->name,
            'stock'      => $v->stock ?? 0,
        ])->values()->toArray();

        $colors = [];
        $sizes = [];
        $otherVariantsMap = [];

        foreach ($comboVariants as $variant_item) {
            $values = $variant_item->variant_values ?? [];
            if (!empty($values['color'])) {
                $colors[] = $values['color'];
            }
            if (!empty($values['size'])) {
                $sizes[] = $values['size'];
            }

            // Collect all other attributes dynamically (e.g. ram, storage, etc.)
            foreach ($values as $attr => $val) {
                if (in_array($attr, ['color', 'size'], true)) {
                    continue;
                }
                if (!isset($otherVariantsMap[$attr])) {
                    $otherVariantsMap[$attr] = [];
                }
                if ($val !== null && $val !== '') {
                    $otherVariantsMap[$attr][] = $val;
                }
            }
        }

        $colors = array_values(array_unique($colors, SORT_REGULAR));
        $sizes  = array_values(array_unique($sizes, SORT_REGULAR));

        $other_variants = [];
        foreach ($otherVariantsMap as $attr => $vals) {
            $other_variants[] = [
                $attr => array_values(array_unique($vals, SORT_REGULAR)),
            ];
        }

        $variantType = !empty($combinations)
            ? 'combination'
            : (!empty($legacyVariants) ? 'legacy' : 'simple');

        return [
            'variant_type'    => $variantType,
            'product'         => [
                'id'             => $product->id,
                'name'           => $product->name,
                'slug'           => $product->slug,
                'price'          => $product->price,
                'discount_price' => $product->discount_price,
                'effective_price' => $product->discount_price && $product->discount_price > 0
                    ? $product->discount_price : $product->price,
                'image_url'      => get_file_url() . '/' . $product->image,
                // 'total_stock'    => $product->totalStock() ?? 0,
                'total_stock'    => $final_stock,
            ],
            'combinations'    => $combinations,
            'legacy_variants' => $legacyVariants,
            'colors'          => $colors,
            'sizes'           => $sizes,
            'other_variants'  => $other_variants,
            'stock'           => $final_stock,
        ];
    }

    private function formatVariantDisplay(?array $attributes): string
    {
        if (empty($attributes)) {
            return 'Variant';
        }
        return collect($attributes)->filter(fn($v) => filled($v))->values()->implode(' • ');
    }

    private function formatSnapshotText(?array $snapshot): string
    {
        if (empty($snapshot)) {
            return '';
        }
        return collect($snapshot)->filter(fn($v) => filled($v))->values()->implode(' • ');
    }

    private function generatePackageCode(): string
    {
        $prefix = 'PKG-' . now()->format('ym');
        do {
            $code = $prefix . '-' . Str::upper(Str::random(4));
        } while (PackageProduct::where('package_code', $code)->exists());

        return $code;
    }

    private function uniquePackageSlug(string $baseSlug, ?int $ignoreId = null): string
    {
        $slug = Str::slug($baseSlug) ?: ('package-' . Str::random(6));
        $original = $slug;
        $suffix   = 1;

        while (
            PackageProduct::where('slug', $slug)
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $slug = $original . '-' . $suffix++;
        }

        return $slug;
    }

    private function validationFailed($validator): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Validation error',
            'errors'  => $validator->errors(),
        ], 422);
    }

    private function serverError(\Throwable $e): JsonResponse
    {
        return response()->json([
            'success'      => false,
            'message'      => 'An unexpected error occurred.',
            'error_details' => [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ],
        ], 500);
    }
}
