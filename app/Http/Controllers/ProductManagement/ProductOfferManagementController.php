<?php

namespace App\Http\Controllers\ProductManagement;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductOffer;
use App\Models\ProductOfferItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductOfferManagementController extends Controller
{
    public function index()
    {
        $offers = ProductOffer::query()
            ->withCount('items')
            ->orderByDesc('id')
            ->paginate(20);

        return view('backend.product_offers.index', compact('offers'));
    }

    public function create()
    {
        return view('backend.product_offers.create');
    }

    public function store(Request $request)
    {
        $data = $this->validatedOffer($request);
        $data['creator'] = auth()->id();
        $data['slug'] = null;

        // if ($request->hasFile('background_image')) {
        //     $data['background_image'] = $this->storeBackgroundImage($request->file('background_image'));
        // }

        $offer = ProductOffer::create($data);
        $offer->slug = Str::limit(Str::slug($offer->title) . '-' . $offer->id, 100, '');
        $offer->save();

        return redirect()
            ->route('product-management.product-offers.show', $offer)
            ->with('success', 'Offer created. Add products from this page.');
    }

    public function show(ProductOffer $productOffer)
    {
        $productOffer->loadCount('items');
        $items = $productOffer->items()
            ->with('product:id,name,price')
            ->orderBy('id')
            ->paginate(25);

        return view('backend.product_offers.show', compact('productOffer', 'items'));
    }

    public function edit(ProductOffer $productOffer)
    {
        return view('backend.product_offers.edit', compact('productOffer'));
    }

    public function update(Request $request, ProductOffer $productOffer)
    {
        $data = $this->validatedOffer($request);

        // if ($request->hasFile('background_image')) {
        //     $this->deletePublicImageIfExists($productOffer->background_image);
        //     $data['background_image'] = $this->storeBackgroundImage($request->file('background_image'));
        // }

        $productOffer->fill($data);
        $productOffer->slug = Str::limit(Str::slug($productOffer->title) . '-' . $productOffer->id, 100, '');
        $productOffer->save();

        return redirect()
            ->route('product-management.product-offers.show', $productOffer)
            ->with('success', 'Offer updated successfully.');
    }

    public function addProduct(Request $request, ProductOffer $productOffer)
    {
        $request->validate([
            'product_id' => ['required', 'exists:products,id'],
        ]);

        if (
            ProductOfferItem::where('product_offer_id', $productOffer->id)
                ->where('product_id', $request->product_id)
                ->exists()
        ) {
            return response()->json(['message' => 'This product is already in the offer.'], 422);
        }

        $product = Product::query()->findOrFail($request->product_id);

        if ((int) ($product->is_package ?? 0) === 1) {
            return response()->json(['message' => 'Package products cannot be added to an offer.'], 422);
        }

        if ((int) ($product->status ?? 0) !== 1) {
            return response()->json(['message' => 'Only active products can be added.'], 422);
        }

        $snapshot = (float) ($product->price ?? 0);

        $item = ProductOfferItem::create([
            'product_offer_id' => $productOffer->id,
            'product_id' => $product->id,
            'price' => $snapshot,
            'discount_price' => null,
            'discount_percent' => null,
            'status' => 1,
            'slug' => null,
            'creator' => auth()->id(),
        ]);

        $item->load('product:id,name,price');

        return response()->json([
            'success' => true,
            'message' => 'Product added to offer.',
            'item' => [
                'id' => $item->id,
                'product_name' => $item->product->name,
                'price' => (float) $item->price,
                'discount_price' => $item->discount_price !== null ? (float) $item->discount_price : null,
                'discount_percent' => $item->discount_percent,
            ],
        ]);
    }

    public function removeProduct(ProductOffer $productOffer, ProductOfferItem $item)
    {
        if ((int) $item->product_offer_id !== (int) $productOffer->id) {
            abort(404);
        }

        $item->delete();

        return response()->json(['success' => true, 'message' => 'Product removed from offer.']);
    }

    public function updateItemDiscount(Request $request, ProductOffer $productOffer, ProductOfferItem $item)
    {
        if ((int) $item->product_offer_id !== (int) $productOffer->id) {
            abort(404);
        }

        $request->validate([
            'mode' => ['required', Rule::in(['discount_price', 'discount_percent'])],
            'discount_price' => ['required_if:mode,discount_price', 'nullable', 'numeric', 'min:0'],
            'discount_percent' => ['required_if:mode,discount_percent', 'nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $price = (float) $item->price;
        if ($price <= 0) {
            return response()->json(['message' => 'Base price is zero; discount cannot be calculated.'], 422);
        }

        if ($request->mode === 'discount_price') {
            $discountPrice = round((float) $request->input('discount_price'), 2);
            if ($discountPrice > $price) {
                return response()->json(['message' => 'Discount price cannot exceed the original price.'], 422);
            }
            $percent = (int) round((($price - $discountPrice) / $price) * 100);
            $item->discount_price = $discountPrice;
            $item->discount_percent = $percent;
        } else {
            $percent = (int) $request->input('discount_percent');
            $discountPrice = round($price - ($price * $percent / 100), 2);
            $item->discount_percent = $percent;
            $item->discount_price = $discountPrice;
        }

        $item->save();

        return response()->json([
            'success' => true,
            'message' => 'Discount updated.',
            'discount_price' => (float) $item->discount_price,
            'discount_percent' => (int) $item->discount_percent,
        ]);
    }

    protected function validatedOffer(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'custom_style' => ['nullable', 'string'],
            'status' => ['nullable', 'integer', 'in:0,1'],
            'background_image' => ['nullable', 'string'],
        ]);

        $validated['status'] = (int) ($validated['status'] ?? 1);

        return $validated;
    }

    protected function storeBackgroundImage($file): string
    {
        $dir = public_path('uploads/product_offers');
        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $name = time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
        $file->move($dir, $name);

        return 'uploads/product_offers/' . $name;
    }

    protected function deletePublicImageIfExists(?string $relativePath): void
    {
        if (!$relativePath) {
            return;
        }
        $full = public_path($relativePath);
        if (is_file($full)) {
            @unlink($full);
        }
    }
}
