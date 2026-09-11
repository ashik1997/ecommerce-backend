<?php

namespace App\Http\Controllers;

use App\Models\Flag;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Yajra\DataTables\DataTables;

class FlagController extends Controller
{
    // falg methods
    public function viewAllFlags(Request $request)
    {
        if ($request->ajax()) {

            $data = Flag::orderBy('id', 'desc');

            return Datatables::of($data)
                // ->editColumn('status', function ($data) {
                //     if ($data->status == 1) {
                //         return 'Active';
                //     } else {
                //         return 'Inactive';
                //     }
                // })
                ->editColumn('featured', function ($data) {
                    if ($data->featured == 0) {
                        return '<button class="btn btn-sm btn-danger rounded">Not Featured</button>';
                    } else {
                        return '<button class="btn btn-sm btn-success rounded">Featured</button>';
                    }
                })
                ->editColumn('created_at', function ($data) {
                    return date("Y-m-d h:i:s a", strtotime($data->created_at));
                })
                ->editColumn('icon', function ($data) {
                    return $data->icon;
                })
                ->addIndexColumn()
                ->addColumn('action', function ($data) {
                    $btn = ' <a href="' . url('edit/flag') . '/' . $data->slug . '" class="mb-1 btn-sm btn-warning rounded"><i class="fas fa-edit"></i></a>';
                    $btn .= ' <a href="javascript:void(0)" data-toggle="tooltip" data-id="' . $data->slug . '" data-original-title="Delete" class="btn-sm btn-danger rounded deleteBtn"><i class="fas fa-trash-alt"></i></a>';

                    if ($data->featured == 0) {
                        $btn .= ' <a href="javascript:void(0)" data-toggle="tooltip" data-id="' . $data->id . '" title="Featured" data-original-title="Featured" class="btn-sm btn-success rounded featureBtn"><i class="feather-chevrons-up"></i></a>';
                    } else {
                        $btn .= ' <a href="javascript:void(0)" data-toggle="tooltip" data-id="' . $data->id . '" title="Featured" data-original-title="Featured" class="btn-sm btn-danger rounded featureBtn"><i class="feather-chevrons-down"></i></a>';
                    }

                    return $btn;
                })
                ->rawColumns(['action', 'featured'])
                ->make(true);
        }
        return view('backend.flag.view');
    }

    public function addNewFlag()
    {
        return view('backend.flag.create');
    }

    public function editFlag($slug)
    {
        $data = Flag::where('slug', $slug)->firstOrFail();
        return view('backend.flag.update', compact('data'));
    }

    public function deleteFlag($slug)
    {
        Flag::where('slug', $slug)->delete();
        return response()->json(['success' => 'Deleted successfully.']);
    }

    public function getFlagInfo($slug)
    {
        $data = Flag::where('slug', $slug)->first();
        return response()->json($data);
    }

    public function updateFlagInfo(Request $request)
    {

        $clean = preg_replace('/[^a-zA-Z0-9\s]/', '', strtolower($request->name)); //remove all non alpha numeric
        $slug = preg_replace('!\s+!', '-', $clean);

        $icon = Flag::where('slug', $request->flag_slug)->first()->icon;
        if ($request->get('icon')) {
            $icon = $request->get('icon');
        }

        Flag::where('slug', $request->flag_slug)->update([
            'name' => $request->name,
            'icon' => $icon,
            'slug' => $slug . "-" . Str::random(5) . "-" . time(),
            'status' => $request->flag_status,
            'updated_at' => Carbon::now()
        ]);
        return redirect()->route('ViewAllFlags')->with('success', 'Flag updated successfully.');
    }

    public function createNewFlag(Request $request)
    {

        $request->validate([
            'name' => 'required|max:255',
        ]);

        $icon = $request->get('icon');

        $clean = preg_replace('/[^a-zA-Z0-9\s]/', '', strtolower($request->name)); //remove all non alpha numeric
        $slug = preg_replace('!\s+!', '-', $clean);

        Flag::insert([
            'name' => $request->name,
            'icon' => $icon,
            'slug' => $slug . "-" . Str::random(5) . "-" . time(),
            'status' => 1,
            'created_at' => Carbon::now()
        ]);
        return redirect()->route('ViewAllFlags')->with('success', 'Flag created successfully.');
    }

    public function featureFlag($id)
    {
        $data = Flag::where('id', $id)->first();
        if ($data->featured == 0) {
            $data->featured = 1;
            $data->save();
        } else {
            $data->featured = 0;
            $data->save();
        }
        return response()->json(['success' => 'Satatus Changed successfully.']);
    }
}
