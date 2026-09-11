<?php

namespace App\Http\Controllers\Courier;

use App\Http\Controllers\Controller;

use App\Models\AreaBaseCourier;
use App\Models\AreaBaseCourierName;
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Str;
use Yajra\DataTables\DataTables;

class AreaBaseCourierManagementController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            $data = AreaBaseCourierName::latest();

            return DataTables::of($data)
                ->addIndexColumn()

                ->addColumn('name', function ($row) {
                    return $row->name ?? '-';
                })

                ->addColumn('status', function ($row) {
                    return $row->status == 1
                        ? '<span class="badge badge-success">Active</span>'
                        : '<span class="badge badge-danger">Inactive</span>';
                })

                ->addColumn('action', function ($row) {
                    return '
                        <a class="btn btn-sm btn-primary editBtn" data-id="'.$row->id.'" href="'.route('area-base-courier-names.edit', $row->id).'">Edit</a>
                        <a href="javascript:void(0)" data-toggle="tooltip" data-id="' . $row->id . '" data-original-title="Delete" class="btn-sm btn-danger rounded deleteBtn">Delete</a>
                    ';
                })

                ->rawColumns(['status', 'action'])
                ->make(true);
        }

        return view('backend.area_base_courier_name.index');
    }

    public function create(){

        return view('backend.area_base_courier_name.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:area_base_courier_names,name',
        ]);

        AreaBaseCourierName::create([
            'name' => $request->name,
            'creator' => auth()->id(),
            'slug' => Str::slug($request->name . '-' . time()),
            'status' => $request->status ?? 1,
        ]);

        Toastr::success('Courier Name has been Added', 'Success');
        return redirect()->route('area-base-courier-names.index');
    }

    public function edit($id)
    {
        $data = AreaBaseCourierName::findOrFail($id);

        return view('backend.area_base_courier_name.edit', compact('data'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:area_base_courier_names,name,' . $id,
        ]);


        $data = AreaBaseCourierName::findOrFail($id);

        $data->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name . '-' . time()),
            'status' => $request->status ?? $data->status,
        ]);

        Toastr::success('Courier Name has been Updated', 'Success');
        return redirect()->route('area-base-courier-names.index');
    }

    public function destroy($id)
    {
        AreaBaseCourierName::findOrFail($id)->delete();

        return response()->json(['success' => 'Courier Name has been deleted.']);
    }

}