<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductWebsite;
use Illuminate\Support\Facades\DB;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Yajra\DataTables\DataTables;

class ProductWebsiteManagementController extends Controller
{
    public function viewAllProductWebsites(Request $request)
    {
        if ($request->ajax()) {
            $data = DB::table('product_websites')
                ->orderBy('id', 'desc')
                ->get();

            return Datatables::of($data)
                ->editColumn('status', function ($data) {
                    if ($data->status == 'active') {
                        return '<span class="btn btn-sm btn-success rounded" style="padding: 0.1rem .5rem;">Active</span>';
                    } else {
                        return '<span class="btn btn-sm btn-warning rounded" style="padding: 0.1rem .5rem;">Inactive</span>';
                    }
                })
                ->editColumn('logo', function ($data) {
                    if (!$data->logo) {
                        return '<span class="text-muted">—</span>';
                    }
                    $baseUrl = rtrim(get_file_url(), '/');
                    $imagePath = ltrim($data->logo, '/');
                    $logoUrl = str_starts_with($data->logo, 'http') ? $data->logo : ($baseUrl . '/' . $imagePath);
                    return '<img src="' . e($logoUrl) . '" alt="Logo" style="max-height: 40px; max-width: 80px; object-fit: contain;">';
                })
                ->addIndexColumn()
                ->addColumn('action', function ($data) {
                    $btn = ' <a href="' . url('edit/product-website') . '/' . $data->slug . '" class="mb-1 btn-sm btn-warning rounded"><i class="fas fa-edit"></i></a>';
                    $btn .= ' <a href="javascript:void(0)" data-toggle="tooltip" data-id="' . $data->id . '" data-original-title="Delete" class="btn-sm btn-danger rounded deleteBtn"><i class="fas fa-trash-alt"></i></a>';
                    return $btn;
                })
                ->rawColumns(['action', 'status', 'logo'])
                ->make(true);
        }
        return view('backend.product_website.view');
    }

    public function addNewProductWebsite()
    {
        return view('backend.product_website.create');
    }

    public function saveNewProductWebsite(Request $request)
    {
        $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'url'   => ['nullable', 'string', 'max:200'],
        ]);

        $clean = preg_replace('/[^a-zA-Z0-9\s]/', '', strtolower($request->title));
        $slug = preg_replace('!\s+!', '-', $clean);

        ProductWebsite::create([
            'title'      => $request->title,
            'url'        => $request->url,
            'logo'       => $request->logo,
            'creator'    => auth()->id(),
            'slug'       => $slug . '-' . time(),
            'status'     => 'active',
            'created_at' => Carbon::now(),
        ]);

        Toastr::success('Product Website Inserted', 'Success');
        return redirect()->route('ViewAllProductWebsites');
    }

    public function deleteProductWebsite($id)
    {
        $has_product = Product::where('product_website_id', $id)->exists();
        if ($has_product) {
            return response()->json(['error' => 'Product Website has products.']);
        }
        ProductWebsite::where('id', $id)->delete();
        return response()->json(['success' => 'Product Website deleted successfully.']);
    }

    public function editProductWebsite($slug)
    {
        $data = ProductWebsite::where('slug', $slug)->firstOrFail();
        return view('backend.product_website.update', compact('data'));
    }

    public function updateProductWebsite(Request $request)
    {
        $request->validate([
            'title'  => ['required', 'string', 'max:200'],
            'url'    => ['nullable', 'string', 'max:200'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $clean = preg_replace('/[^a-zA-Z0-9\s]/', '', strtolower($request->title));
        $slug = preg_replace('!\s+!', '-', $clean);

        ProductWebsite::where('id', $request->id)->update([
            'title'      => $request->title,
            'url'        => $request->url,
            'logo'       => $request->logo ?? null,
            'status'     => $request->status,
            'updated_at' => Carbon::now(),
        ]);

        Toastr::success('Product Website Updated', 'Success');
        return redirect()->route('ViewAllProductWebsites');
    }
}
