<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use DataTables;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Outlet\Models\CustomerSourceType;
use App\Http\Controllers\Customer\Models\CustomerCategory;
use App\Http\Controllers\Customer\Models\Customer;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\User;
use App\Services\RoleSidebarPermissionService;

class CustomerController extends Controller
{
    public function addNewCustomer()
    {
        $customer_categories = CustomerCategory::where('status', 'active')->get();
        $customer_source_types = CustomerSourceType::where('status', 'active')->get();
        $users = User::where('status', 1)->get();
        return view('backend.customer.create', compact('customer_categories', 'customer_source_types', 'users'));
    }

    public function customers($customer_id = null)
    {

        $query = Customer::query();

        if ($customer_id) {
            $query->where(function($q) use ($customer_id) {
                $q->where('id', $customer_id)
                ->orWhere('slug', $customer_id);
            });
            $data = $query->first();
        } else {
            if (request()->q) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . request()->q . '%');
                    $q->orWhere('phone', 'like', '%' . request()->q . '%');
                });
            }
            $data = $query->limit(10)->get();
        }
        return response()->json($data);
    }

    // csv, excel import file upload and store in public folder and return file path and foreach row data in json format
    public function importCustomerData(Request $request)
    {
        DB::beginTransaction();

        try {

            $request->validate([
                'file' => 'required|mimes:csv,excel,xlsx|max:2048',
            ]);

            $file = $request->file('file');

            $rows = [];

            if (($handle = fopen($file->getRealPath(), 'r')) !== false) {

                // Skip header row
                fgetcsv($handle);

                while (($data = fgetcsv($handle, 1000, ',')) !== false) {

                    /*
                    CSV Format:
                    0 => reference_no
                    1 => name
                    2 => phone
                    3 => email
                    4 => opening_amount
                    5 => paid_amount
                    6 => due
                    7 => created_at
                    */

                    $rows[] = $data;

                    // Skip empty row
                    if (empty($data[1])) {
                        continue;
                    }

                    $openingAmount = (float) ($data[4] ?? 0);
                    $paidAmount    = (float) ($data[5] ?? 0);
                    $remaining     = (float) ($data[6] ?? 0);

                    // Create customer
                    $customer = Customer::create([
                        'name'    => $data[1] ?? null,
                        'phone'   => $data[2] ?? null,
                        'email'   => $data[3] ?? null,
                    ]);

                    // Insert opening balance
                    DB::table('customer_opening_balances')->insert([
                        'product_website_id' => auth()->user()->product_website_id ?? null,
                        'store_id'           => auth()->user()->store_id ?? null,
                        'customer_id'        => $customer->id,

                        'entry_type'         => $remaining > 0 ? 'due' : 'advance',

                        'opening_date'       => !empty($data[7])
                            ? Carbon::parse($data[7])->format('Y-m-d')
                            : now()->format('Y-m-d'),

                        'reference_no'       => $data[0] ?? null,

                        'opening_amount'     => $openingAmount,
                        'paid_amount'        => $paidAmount,
                        'remaining_amount'   => $remaining,

                        'note'               => 'Imported from CSV',

                        'posted_to_accounts' => 0,
                        'creator'            => auth()->id(),
                        'slug'               => Str::uuid(),
                        'status'             => 1,

                        'created_at'         => now(),
                        'updated_at'         => now(),
                    ]);
                }

                fclose($handle);
            }

            DB::commit();

            // return response()->json([
            //     'success' => true,
            //     'message' => 'Customer data imported successfully',
            //     'total_rows' => count($rows),
            //     'rows' => $rows
            // ]);
            Toastr::success('Customer data imported successfully', 'Success');
            return back();

        } catch (\Throwable $th) {

            DB::rollBack();

            // return response()->json([
            //     'success' => false,
            //     'error' => $th->getMessage()
            // ], 500);
            Toastr::error('Error importing customer data', 'Error');
            return back();
        }
    }


    public function customer_store()
    {
        request()->validate([
            'name' => ['required'],
            'phone' => ['required'],

        ], [
            'name.required' => 'name is required.',
        ]);

        $clean = preg_replace('/[^a-zA-Z0-9\s]/', '', strtolower(request()->name)); //remove all non alpha numeric
        $slug = preg_replace('!\s+!', '-', $clean);

        $data = Customer::create([
            "name" => request()->name,
            "phone" => request()->phone,
            "address" => request()->address,
            'slug' => $slug . time(),
        ]);

        return response()->json($data);
    }

    public function saveNewCustomer(Request $request)
    {
        // dd(request()->all());
        $request->validate([
            'name' => ['required'],
            'phone' => ['required'],

        ], [
            'name.required' => 'name is required.',
        ]);

        $clean = preg_replace('/[^a-zA-Z0-9\s]/', '', strtolower(request()->name)); //remove all non alpha numeric
        $slug = preg_replace('!\s+!', '-', $clean);

        // $customer_category = CustomerCategory::where('id', request()->customer_category_id)->first();
        // $customer_source_type = CustomerSourceType::where('id', request()->customer_source_type_id)->first();
        // dd(5);

        Customer::insert([
            'customer_category_id' => request()->customer_category_id ?? '',
            'customer_source_type_id' => request()->customer_source_type_id ?? '',
            'reference_by' => request()->reference_id ?? '',
            'name' => request()->name,
            'phone' => request()->phone,
            'email' => request()->email,
            'address' => request()->address,

            'creator' => auth()->user()->id,
            'slug' => $slug . time(),
            'status' => 'active',
            'created_at' => Carbon::now()
        ]);

        Toastr::success('Added successfully!', 'Success');
        return back();
    }

    public function viewAllCustomer(Request $request)
    {
        if ($request->ajax()) {
            $canViewCrmProfile = app(RoleSidebarPermissionService::class)
                ->userCan($request->user(), 'crm.customers.profile', 'read');
            $data = Customer::where('status', 'active')
                ->with(['customerCategory', 'customerSourceType', 'referenceBy'])
                ->withSum(['orders as direct_order_due' => function ($query) {
                    $query->where('status', 'active')
                        ->directCustomerReceivable();
                }], 'due_amount')
                ->withSum(['openingBalances as old_due' => function ($query) {
                    $query->where('status', 'active')
                        ->where('entry_type', 'due');
                }], 'remaining_amount')
                ->orderBy('id', 'DESC')
                ->get();

            return Datatables::of($data)
                // ->editColumn('status', function ($data) {
                //     return $data->status == "active" ? 'Active' : 'Inactive';
                // })
                // ->editColumn('created_at', function ($data) {
                //     return date("Y-m-d", strtotime($data->created_at));
                // })
                ->addIndexColumn()
                ->addColumn('customer_category', function ($data) {
                    return $data->customerCategory ? $data->customerCategory->title : 'N/A';
                })
                ->addColumn('customer_source_type', function ($data) {
                    return $data->customerSourceType ? $data->customerSourceType->title : 'N/A';
                })
                ->addColumn('reference_by', function ($data) {
                    return $data->referenceBy ? $data->referenceBy->name : 'N/A';
                })
                ->addColumn('customer_due', function ($data) {
                    $due = (float) ($data->direct_order_due ?? 0) + (float) ($data->old_due ?? 0);
                    return '৳ ' . number_format($due, 2);
                })
                ->addColumn('customer_advance', function ($data) {
                    return '৳ ' . number_format((float) ($data->available_advance ?? 0), 2);
                })
                ->addColumn('action', function ($data) use ($canViewCrmProfile) {
                    $due = (float) ($data->direct_order_due ?? 0) + (float) ($data->old_due ?? 0);
                    $ledgerUrl = route('ledger.customer_ledger', [
                        'customer_id' => $data->id,
                        'start_date' => Carbon::now()->subMonth()->toDateString(),
                        'end_date' => Carbon::now()->toDateString(),
                        'store_id' => '',
                    ]);

                    $btn = '';
                    if ($canViewCrmProfile) {
                        $btn .= '<a href="' . route('crm.customers.profile', ['customer' => $data->id]) . '" class="btn-sm btn-primary rounded mr-1" title="Customer 360 Profile"><i class="fas fa-address-card"></i> 360</a>';
                    }
                    $btn .= '<a href="' . url('edit/customers') . '/' . $data->slug . '" class="btn-sm btn-warning rounded editBtn"><i class="fas fa-edit"></i></a>';
                    if ($due > 0) {
                        $btn .= ' <a href="' . route('CreateCustomerDuePayment', ['customer_id' => $data->id]) . '" class="btn-sm btn-success rounded"><i class="fas fa-money-bill"></i> Pay Due</a>';
                    }
                    $btn .= ' <a href="' . $ledgerUrl . '" class="btn-sm btn-info rounded"><i class="fas fa-book"></i> Ledger</a>';
                    $btn .= ' <a href="javascript:void(0)" data-toggle="tooltip" data-id="' . $data->slug . '" data-original-title="Delete" class="btn-sm btn-danger rounded deleteBtn"><i class="fas fa-trash-alt"></i></a>';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('backend.customer.view');
    }


    public function editCustomer($slug)
    {
        $data = Customer::where('slug', $slug)->first();
        $customer_categories = CustomerCategory::where('status', 'active')->get();
        $customer_source_types = CustomerSourceType::where('status', 'active')->get();
        $users = User::where('status', 1)->get();
        return view('backend.customer.edit', compact('data', 'customer_categories', 'customer_source_types', 'users'));
    }

    public function updateCustomer(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'name' => ['required'],
            'phone' => ['required'],

        ], [
            'name.required' => 'name is required.',
        ]);

        // Check if the selected product_warehouse_room_id exists for the selected product_warehouse_id        
        $data = Customer::where('id', request()->customer_id)->first();

        $clean = preg_replace('/[^a-zA-Z0-9\s]/', '', strtolower($request->name)); //remove all non alpha numeric
        $slug = preg_replace('!\s+!', '-', $clean);

        $data->customer_category_id = request()->customer_category_id ?? $data->customer_category_id;
        $data->customer_source_type_id = request()->customer_source_type_id ?? $data->customer_source_type_id;
        $data->reference_by = request()->reference_id ?? $data->reference_by;
        $data->name = request()->name ?? $data->name;
        $data->phone = request()->phone ?? $data->phone;
        $data->email = request()->email ?? $data->email;
        $data->address = request()->address ?? $data->address;

        if ($data->name != $request->name) {
            $data->slug = $slug . time();
        }

        $data->creator = auth()->user()->id;
        $data->status = request()->status ?? $data->status;
        $data->updated_at = Carbon::now();
        $data->save();

        Toastr::success('Successfully Updated', 'Success!');
        return redirect()->route('ViewAllCustomer');
    }


    public function deleteCustomer($slug)
    {
        $data = Customer::where('slug', $slug)->first();

        // $data->delete();
        $data->status = 'inactive';
        $data->save();
        return response()->json([
            'success' => 'Deleted successfully!',
            'data' => 1
        ]);
    }
}
