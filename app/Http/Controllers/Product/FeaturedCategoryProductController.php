<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\FeaturedCategoryProduct;
use App\Models\Product;
use App\Models\Subcategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeaturedCategoryProductController extends Controller
{
    public function index()
    {
        $this->normalizeFeaturedCategoryOrder();

        $categories = Category::where('featured', 1)
            ->whereHas('featuredProducts')
            ->select('id', 'name', 'featured_order')
            ->orderBy('featured_order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        return view('backend.featured_category_products.index')->with([
            'categories' => $categories,
        ]);
    }

    public function featuredCategories(Request $request)
    {
        $search = $request->search;

        $query = Category::where('featured', 1)
            ->where('status', 1);

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        $items = $query->paginate(10);

        return response()->json([
            'data' => collect($items->items())->map(function ($item) {
                return [
                    'id' => $item->id,
                    'text' => $item->name
                ];
            }),
            'pagination' => [
                'more' => $items->hasMorePages()
            ]
        ]);
    }

    public function featuredSubCategories(Request $request)
    {
        $search = $request->search;

        $query = Subcategory::where('featured', 1)
            ->where('category_id', $request->category_id)
            ->where('status', 1);

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        $items = $query->paginate(10);

        return response()->json([
            'data' => collect($items->items())->map(function ($item) {
                return [
                    'id' => $item->id,
                    'category_id' => $item->category_id,
                    'text' => $item->name
                ];
            }),
            'pagination' => [
                'more' => $items->hasMorePages()
            ]
        ]);
    }

    public function categoryProducts(Request $request)
    {
        $search = $request->search;

        $query = Product::where('status', 1);

        // child category
        if ($request->filled('child_category_id')) {
            $query->where('child_category_id', $request->child_category_id);
        }

        // subcategory
        elseif ($request->filled('subcategory_id')) {
            $query->where('subcategory_id', $request->subcategory_id);
        }

        //category
        elseif ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        $items = $query->paginate(10);

        return response()->json([
            'data' => collect($items->items())->map(function ($item) {
                return [
                    'id' => $item->id,
                    'text' => $item->name
                ];
            }),
            'pagination' => [
                'more' => $items->hasMorePages()
            ]
        ]);
    }



    public function mappedProducts(Request $request)
    {
        $query = FeaturedCategoryProduct::with([
            'product:id,name',
            'category:id,name',
            'subcategory:id,name',
            'child_category:id,name'
        ]);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('product_ids')) {
            $query->whereIn('product_id', $request->product_ids);
        }

        $data = $query->get();

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'products' => ['required', 'array'],
            'products.*.product_id' => ['required', 'exists:products,id'],
        ]);

        DB::transaction(function () use ($request) {

            $deleteQuery = FeaturedCategoryProduct::where(
                'category_id',
                $request->category_id
            );

            // child category id thakle delete hobe
            if ($request->filled('child_category_id')) {
                $deleteQuery->where('child_category_id', $request->child_category_id);
            }

            // subcategory selected
            elseif ($request->filled('subcategory_id')) {
                $deleteQuery->where('subcategory_id', $request->subcategory_id)
                    ->whereNull('child_category_id');
            }

            // only category selected
            else {
                $deleteQuery->whereNull('subcategory_id')
                    ->whereNull('child_category_id');
            }

            $deleteQuery->delete();

            $rows = [];

            foreach ($request->products as $item) {
                $rows[] = [
                    'category_id'       => $request->category_id,
                    'subcategory_id'    => $request->subcategory_id ?: null,
                    'child_category_id' => $request->child_category_id ?: null,
                    'product_id'        => $item['product_id'],
                    'status'            => 1,
                    'creator'           => auth()->id() ?? 1,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ];
            }

            FeaturedCategoryProduct::insert($rows);
        });

        return response()->json([
            'status' => true,
            'message' => 'Saved Successfully'
        ]);
    }

    public function searchFeaturedProducts(Request $request)
    {
        if ($request->input('scope') === 'all') {
            $search = $request->search;
            $query = Product::query()
                ->where('status', 1)
                ->where(function ($q) {
                    $q->where('is_package', 0)->orWhereNull('is_package');
                })
                ->select('id', 'name', 'price');

            if ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            }

            $items = $query->orderBy('name')->paginate(15);

            return response()->json([
                'results' => collect($items->items())->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'text' => $product->name,
                        'name' => $product->name,
                        'price' => (float) ($product->price ?? 0),
                    ];
                })->values(),
                'pagination' => [
                    'more' => $items->hasMorePages(),
                ],
            ]);
        }

        $search = $request->search;
        $query = FeaturedCategoryProduct::with('product:id,name')
            ->whereHas('product', function ($q) use ($search) {
                if ($search) {
                    $q->where('name', 'like', "%{$search}%");
                }
            });

        $items = $query->paginate(10);
        return response()->json([
            'results' => collect($items->items())
                ->filter(fn($item) => $item->product)
                ->map(function ($item) {
                    return [
                        'id' => $item->product->id,
                        'text' => $item->product->name,
                    ];
                })
                ->values(),

            'pagination' => [
                'more' => $items->hasMorePages()
            ]
        ]);
    }

    public function delete($id)
    {
        $item = FeaturedCategoryProduct::findOrFail($id);
        $item->delete();

        return response()->json([
            'status' => true,
            'message' => 'Deleted Successfully'
        ]);
    }

    public function order()
    {
        $this->normalizeFeaturedCategoryOrder();

        $categories = Category::where('featured', 1)
            ->whereHas('featuredProducts')
            ->select('id', 'name', 'featured_order')
            ->orderBy('featured_order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        return view('backend.featured_category_products.order')->with([
            'categories' => $categories,
        ]);
    }

    public function changeOrder(Request $request, Category $category)
    {
        $this->normalizeFeaturedCategoryOrder();

        // dd($request->direction, $category->id, $category->featured_order);
        if ($request->direction === 'up') {
            $swapCategory = Category::where('featured', 1)
                ->whereHas('featuredProducts')
                ->where('featured_order', '<', $category->featured_order)
                ->orderBy('featured_order', 'desc')
                ->orderBy('id', 'desc')
                ->first();
        } else {
            $swapCategory = Category::where('featured', 1)
                ->whereHas('featuredProducts')
                ->where('featured_order', '>', $category->featured_order)
                ->orderBy('featured_order', 'asc')
                ->orderBy('id', 'asc')
                ->first();
        }
        if ($swapCategory) {
            $currentOrder = $category->featured_order;

            $category->featured_order = $swapCategory->featured_order;
            $category->save();

            $swapCategory->featured_order = $currentOrder;
            $swapCategory->save();

            // dd($category->featured_order, $swapCategory->featured_order);
        } else {
            return back()->with('error', 'It\'s can\'t be Ordered!. Try another one');
        }

        return back()->with('success', 'swap category succussfully completed!');
    }

    public function normalizeFeaturedCategoryOrder()
    {

        $categories = Category::where('featured', 1)
            ->whereHas('featuredProducts')
            ->select('id', 'name', 'featured_order')
            ->orderBy('featured_order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        foreach ($categories as $index => $category) {
            $category->featured_order = $index + 1;
            $category->save();
        }
    }
}
