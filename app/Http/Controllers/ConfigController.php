<?php

namespace App\Http\Controllers;

use App\Models\ConfigSetup;
use App\Models\DeviceCondition;
use App\Models\Flag;
use App\Models\ProductWarrenty;
use Brian2694\Toastr\Facades\Toastr;
use App\Models\Sim;
use App\Models\ProductSize;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\DataTables;

class ConfigController extends Controller
{
    public function configSetup(){
        $techConfigs = ConfigSetup::where('industry', 'Tech')->orderBy('industry', 'desc')->get();
        $fashionConfigs = ConfigSetup::where('industry', 'Fashion')->orWhere('industry', 'Common')->orderBy('industry', 'desc')->get();
        return view('backend.config.setup', compact('techConfigs', 'fashionConfigs'));
    }

    public function updateConfigSetup(Request $request){

        $configArray = array();

        if(isset($request->config_setup)){
            foreach($request->config_setup as $configSetup){
                $configArray[] = $configSetup;
                ConfigSetup::where('code', $configSetup)->update([
                    'status' => 1,
                    'updated_at' => Carbon::now()
                ]);
            }
        }


        ConfigSetup::whereNotIn('code', $configArray)->update([
            'status' => 0,
            'updated_at' => Carbon::now()
        ]);

        Toastr::success('Config Setup Updated', 'Success');
        return back();
    }


    // unit methods
    public function viewAllUnits(Request $request){
        if ($request->ajax()) {

            $data = Unit::orderBy('id', 'desc')->get();

            return Datatables::of($data)
                    ->editColumn('status', function($data) {
                        if($data->status == 1){
                            return 'Active';
                        } else {
                            return 'Inactive';
                        }
                    })
                    ->editColumn('created_at', function($data) {
                        return date("Y-m-d h:i:s a", strtotime($data->created_at));
                    })
                    ->addIndexColumn()
                    ->addColumn('action', function($data){
                        $btn = ' <a href="javascript:void(0)" data-toggle="tooltip" data-id="'.$data->id.'" data-original-title="Edit" class="mb-1 btn-sm btn-warning rounded editBtn"><i class="fas fa-edit"></i></a>';
                        $btn .= ' <a href="javascript:void(0)" data-toggle="tooltip" data-id="'.$data->id.'" data-original-title="Delete" class="btn-sm btn-danger rounded deleteBtn"><i class="fas fa-trash-alt"></i></a>';
                        return $btn;
                    })
                    ->rawColumns(['action', 'icon'])
                    ->make(true);
        }
        return view('backend.config.unit');
    }

    public function deleteUnit($id){
        Unit::where('id', $id)->delete();
        return response()->json(['success' => 'Deleted successfully.']);
    }

    public function getUnitInfo($id){
        $data = Unit::where('id', $id)->first();
        return response()->json($data);
    }

    public function updateUnitInfo(Request $request){
        Unit::where('id', $request->flag_slug)->update([
            'name' => $request->name,
            'status' => $request->flag_status,
            'updated_at' => Carbon::now()
        ]);
        return response()->json(['success'=>'Updated successfully.']);
    }

    public function createNewUnit(Request $request){
        $request->validate([
            'name' => 'required|max:255',
        ]);

        Unit::insert([
            'name' => $request->name,
            'status' => 1,
            'created_at' => Carbon::now()
        ]);
        return response()->json(['success'=>'Updated successfully.']);
    }


    // sim methods
    public function viewAllSims(Request $request){
        if ($request->ajax()) {

            $data = Sim::orderBy('id', 'desc')->get();

            return Datatables::of($data)
                    ->editColumn('created_at', function($data) {
                        return date("Y-m-d h:i:s a", strtotime($data->created_at));
                    })
                    ->addIndexColumn()
                    ->addColumn('action', function($data){
                        $btn = ' <a href="javascript:void(0)" data-toggle="tooltip" data-id="'.$data->id.'" data-original-title="Edit" class="mb-1 btn-sm btn-warning rounded editBtn"><i class="fas fa-edit"></i></a>';
                        $btn .= ' <a href="javascript:void(0)" data-toggle="tooltip" data-id="'.$data->id.'" data-original-title="Delete" class="btn-sm btn-danger rounded deleteBtn"><i class="fas fa-trash-alt"></i></a>';
                        return $btn;
                    })
                    ->rawColumns(['action'])
                    ->make(true);
        }
        return view('backend.config.sim');
    }

    public function deleteSim($id){
        Sim::where('id', $id)->delete();
        return response()->json(['success' => 'Deleted successfully.']);
    }

    public function getSimInfo($id){
        $data = Sim::where('id', $id)->first();
        return response()->json($data);
    }

    public function updateSimInfo(Request $request){
        Sim::where('id', $request->sim_id)->update([
            'name' => $request->name,
            'updated_at' => Carbon::now()
        ]);
        return response()->json(['success'=>'Updated successfully.']);
    }

    public function createNewSim(Request $request){
        $request->validate([
            'name' => 'required|max:255',
        ]);

        Sim::insert([
            'name' => $request->name,
            'created_at' => Carbon::now()
        ]);
        return response()->json(['success'=>'Updated successfully.']);
    }


    // config route for device condition
    public function viewAllDeviceConditions(Request $request){
        if ($request->ajax()) {

            $data = DeviceCondition::orderBy('serial', 'asc')->get();

            return Datatables::of($data)
                    ->editColumn('created_at', function($data) {
                        return date("Y-m-d h:i:s a", strtotime($data->created_at));
                    })
                    ->addIndexColumn()
                    ->addColumn('action', function($data){
                        $btn = ' <a href="javascript:void(0)" data-toggle="tooltip" data-id="'.$data->id.'" data-original-title="Edit" class="mb-1 btn-sm btn-warning rounded editBtn"><i class="fas fa-edit"></i></a>';
                        $btn .= ' <a href="javascript:void(0)" data-toggle="tooltip" data-id="'.$data->id.'" data-original-title="Delete" class="btn-sm btn-danger rounded deleteBtn"><i class="fas fa-trash-alt"></i></a>';
                        return $btn;
                    })
                    ->rawColumns(['action'])
                    ->make(true);
        }
        return view('backend.config.device_condition');
    }

    public function deleteDeviceCondition($id){
        DeviceCondition::where('id', $id)->delete();
        return response()->json(['success' => 'Deleted successfully.']);
    }

    public function getDeviceConditionInfo($id){
        $data = DeviceCondition::where('id', $id)->first();
        return response()->json($data);
    }

    public function updateDeviceCondition(Request $request){
        DeviceCondition::where('id', $request->device_condition_id)->update([
            'name' => $request->name,
            'updated_at' => Carbon::now()
        ]);
        return response()->json(['success'=>'Updated successfully.']);
    }

    public function addNewDeviceCondition(Request $request){
        $request->validate([
            'name' => 'required|max:255',
        ]);

        DeviceCondition::insert([
            'name' => $request->name,
            'created_at' => Carbon::now()
        ]);
        return response()->json(['success'=>'Created successfully.']);
    }

    public function rearrangeDeviceCondition(){
        $conditions = DeviceCondition::orderBy('serial', 'asc')->get();
        return view('backend.config.rearrangeDeviceCondition', compact('conditions'));
    }

    public function saveRearrangeDeviceCondition(Request $request){
        $sl = 1;
        foreach($request->id as $id){
            DeviceCondition::where('id', $id)->update([
                'serial' => $sl
            ]);
            $sl++;
        }
        Toastr::success('Device Conditions are Rerranged', 'Success');
        return redirect('/view/all/device/conditions');
    }




    // config route for product warrenty
    public function viewAllProductWarrenties(Request $request){
        if ($request->ajax()) {

            $data = ProductWarrenty::orderBy('serial', 'asc')->get();

            return Datatables::of($data)
                    ->editColumn('created_at', function($data) {
                        return date("Y-m-d h:i:s a", strtotime($data->created_at));
                    })
                    ->addIndexColumn()
                    ->addColumn('action', function($data){
                        $btn = ' <a href="javascript:void(0)" data-toggle="tooltip" data-id="'.$data->id.'" data-original-title="Edit" class="mb-1 btn-sm btn-warning rounded editBtn"><i class="fas fa-edit"></i></a>';
                        $btn .= ' <a href="javascript:void(0)" data-toggle="tooltip" data-id="'.$data->id.'" data-original-title="Delete" class="btn-sm btn-danger rounded deleteBtn"><i class="fas fa-trash-alt"></i></a>';
                        return $btn;
                    })
                    ->rawColumns(['action'])
                    ->make(true);
        }
        return view('backend.config.product_warrenty');
    }

    public function deleteProductWarrenty($id){
        ProductWarrenty::where('id', $id)->delete();
        return response()->json(['success' => 'Deleted successfully.']);
    }

    public function getProductWarrentyInfo($id){
        $data = ProductWarrenty::where('id', $id)->first();
        return response()->json($data);
    }

    public function updateProductWarrenty(Request $request){
        ProductWarrenty::where('id', $request->product_warrenty_id)->update([
            'name' => $request->name,
            'updated_at' => Carbon::now()
        ]);
        return response()->json(['success'=>'Updated successfully.']);
    }

    public function addNewProductWarrenty(Request $request){
        $request->validate([
            'name' => 'required|max:255',
        ]);

        ProductWarrenty::insert([
            'name' => $request->name,
            'created_at' => Carbon::now()
        ]);
        return response()->json(['success'=>'Created successfully.']);
    }
    public function rearrangeWarrenty(){
        $warrenties = ProductWarrenty::orderBy('serial', 'asc')->get();
        return view('backend.config.rearrangeWarrenty', compact('warrenties'));
    }
    public function saveRearrangeWarrenties(Request $request){
        $sl = 1;
        foreach($request->id as $id){
            ProductWarrenty::where('id', $id)->update([
                'serial' => $sl
            ]);
            $sl++;
        }
        Toastr::success('Product Warrenties are Rerranged', 'Success');
        return redirect('/view/all/warrenties');
    }

    // product size
    public function viewAllSizes(Request $request){
        if ($request->ajax()) {

            $data = ProductSize::orderBy('serial', 'asc')->get();

            return Datatables::of($data)
                    ->editColumn('status', function($data) {
                        if($data->status == 1){
                            return 'Active';
                        } else {
                            return 'Inactive';
                        }
                    })
                    ->editColumn('created_at', function($data) {
                        return date("Y-m-d h:i:s a", strtotime($data->created_at));
                    })
                    ->addIndexColumn()
                    ->addColumn('action', function($data){
                        $btn = ' <a href="javascript:void(0)" data-toggle="tooltip" data-id="'.$data->id.'" data-original-title="Edit" class="mb-1 btn-sm btn-warning rounded editBtn"><i class="fas fa-edit"></i></a>';
                        $btn .= ' <a href="javascript:void(0)" data-toggle="tooltip" data-id="'.$data->id.'" data-original-title="Delete" class="btn-sm btn-danger rounded deleteBtn"><i class="fas fa-trash-alt"></i></a>';
                        return $btn;
                    })
                    ->rawColumns(['action', 'icon'])
                    ->make(true);
        }
        return view('backend.config.size');
    }

    public function deleteSize($id){
        ProductSize::where('id', $id)->delete();
        return response()->json(['success' => 'Deleted successfully.']);
    }

    public function getSizeInfo($id){
        $data = ProductSize::where('id', $id)->first();
        return response()->json($data);
    }

    public function updateSizeInfo(Request $request){
        ProductSize::where('id', $request->flag_slug)->update([
            'name' => $request->name,
            'status' => $request->flag_status,
            'updated_at' => Carbon::now()
        ]);
        return response()->json(['success' => 'Updated successfully.']);
    }

    public function createNewSize(Request $request){
        $request->validate([
            'name' => 'required|max:255',
        ]);

        ProductSize::insert([
            'name' => $request->name,
            'status' => 1,
            'slug' => time().str::random(5),
            'created_at' => Carbon::now()
        ]);
        return response()->json(['success' => 'Updated successfully.']);
    }

    public function rearrangeSize(Request $request){
        $data = ProductSize::orderBy('serial', 'asc')->get();
        return view('backend.config.rearrangeSize', compact('data'));
    }

    public function saveRearrangedSizes(Request $request){
        $sl = 1;
        foreach($request->slug as $slug){
            ProductSize::where('slug', $slug)->update([
                'serial' => $sl
            ]);
            $sl++;
        }
        Toastr::success('Product Sizes are Rerranged', 'Success');
        return redirect('/view/all/sizes');
    }

}
