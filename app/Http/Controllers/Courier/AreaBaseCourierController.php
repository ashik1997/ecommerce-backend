<?php

namespace App\Http\Controllers\Courier;

use App\Http\Controllers\Controller;

use App\Models\AreaBaseCourier;
use App\Models\AreaBaseCourierName;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Yajra\DataTables\DataTables;

class AreaBaseCourierController extends Controller
{
    /**
     * Display listing page / DataTable
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = AreaBaseCourier::with('courierName')->latest()->get();

            return DataTables::of($data)
                ->addIndexColumn()

                ->addColumn('courier_name', function ($row) {
                    return $row->courierName->name ?? '-';
                })


                ->editColumn('status', function ($row) {
                    return $row->status == 1
                        ? '<span class="badge bg-success">Active</span>'
                        : '<span class="badge bg-danger">Inactive</span>';
                })

                ->addColumn('action', function ($row) {
                    return '
                        <a class="btn btn-sm btn-primary editBtn" data-id="'.$row->id.'" href="'.route('area-base-courier-charges.edit', $row->id).'">Edit</a>
                        <a href="javascript:void(0)" data-toggle="tooltip" data-id="' . $row->id . '" data-original-title="Delete" class="btn-sm btn-danger rounded deleteBtn">Delete</a>
                    ';
                })

                ->rawColumns(['courier_name', 'status', 'action'])
                ->make(true);
        }

        return view('backend.area_base_courier_charge.index');
    }

    /**
     * Show create form
     */
    public function create()
    {
        $courierNames = AreaBaseCourierName::get();
        return view('backend.area_base_courier_charge.create', compact('courierNames'));
    }

    /**
     * Store new data
     */
    public function store(Request $request)
    {
        $request->validate([
            'area_base_courier_id' => 'required',
            'area_name'            => 'required|string|max:255',
            'shipping_cost'        => 'required|numeric',
            'status'               => 'required',
        ]);

    // return $request->all();exit;

        AreaBaseCourier::create([
            'area_base_courier_id' => $request->area_base_courier_id,
            'area_name'            => $request->area_name,
            'shipping_cost'        => $request->shipping_cost,
            'slug'                 => Str::slug($request->area_name),
            'creator'              => auth()->id(),
            'status'               => $request->status,
        ]);

        Toastr::success('Area Base Courier Created Successfully');
        return redirect()->route('area-base-courier-charges.index');
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        $courierNames = AreaBaseCourierName::where('status', 1)->get();
        $data = AreaBaseCourier::findOrFail($id);
        return view('backend.area_base_courier_charge.edit', compact('data', 'courierNames'));
    }

    /**
     * Update data
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'area_base_courier_id' => 'required',
            'area_name'            => 'required|string|max:255',
            'shipping_cost'        => 'required|numeric',
            'status'               => 'required',
        ]);

        $data = AreaBaseCourier::findOrFail($id);

        $data->update([
            'area_base_courier_id' => $request->area_base_courier_id,
            'area_name'            => $request->area_name,
            'shipping_cost'        => $request->shipping_cost,
            'slug'                 => Str::slug($request->area_name),
            'status'               => $request->status,
        ]);

        Toastr::success('Area Base Courier Updated Successfully');
        return redirect()->route('area-base-courier-charges.index');
    }

    /**
     * Delete data
     */
    public function destroy($id)
    {
        $data = AreaBaseCourier::findOrFail($id);
        $data->delete();
    
        return response()->json(['success' => 'Courier Name has been deleted.']);
    }
}
