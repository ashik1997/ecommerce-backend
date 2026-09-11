<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Controller;
use App\Models\Delivery\DeliveryEmployee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeliveryEmployeeController extends Controller
{
    public function index()
    {
        $query = DeliveryEmployee::query()->with('user')->orderBy('name');

        if (request()->filled('q')) {
            $search = request('q');
            $query->where(function ($employeeQuery) use ($search) {
                $employeeQuery->where('name', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%')
                    ->orWhere('vehicle_number', 'like', '%' . $search . '%');
            });
        }

        if (request()->filled('current_status')) {
            $query->where('current_status', request('current_status'));
        }

        if (request()->filled('status')) {
            $query->where('status', request('status'));
        }

        return view('backend.delivery_management.employees.index', [
            'employees' => $query->paginate(25)->appends(request()->query()),
            'currentStatuses' => $this->currentStatuses(),
            'salaryTypes' => $this->salaryTypes(),
            'commissionTypes' => $this->commissionTypes(),
        ]);
    }

    public function create()
    {
        return view('backend.delivery_management.employees.create', [
            'employee' => new DeliveryEmployee([
                'current_status' => 'available',
                'status' => 'active',
            ]),
            'users' => $this->deliveryUserOptions(),
            'currentStatuses' => $this->currentStatuses(),
            'salaryTypes' => $this->salaryTypes(),
            'commissionTypes' => $this->commissionTypes(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $data['creator'] = auth()->id();

        $employee = DeliveryEmployee::create($data);

        return redirect()
            ->route('delivery-management.employees.edit', $employee)
            ->with('success', 'Delivery employee created successfully.');
    }

    public function show(DeliveryEmployee $employee)
    {
        return view('backend.delivery_management.employees.show', [
            'employee' => $employee->load('user'),
        ]);
    }

    public function edit(DeliveryEmployee $employee)
    {
        return view('backend.delivery_management.employees.edit', [
            'employee' => $employee,
            'users' => $this->deliveryUserOptions(),
            'currentStatuses' => $this->currentStatuses(),
            'salaryTypes' => $this->salaryTypes(),
            'commissionTypes' => $this->commissionTypes(),
        ]);
    }

    public function update(Request $request, DeliveryEmployee $employee)
    {
        $employee->update($this->validatedData($request));

        return redirect()
            ->route('delivery-management.employees.edit', $employee)
            ->with('success', 'Delivery employee updated successfully.');
    }

    public function destroy(DeliveryEmployee $employee)
    {
        $employee->update([
            'status' => 'inactive',
            'current_status' => 'inactive',
        ]);

        return redirect()
            ->route('delivery-management.employees.index')
            ->with('success', 'Delivery employee deactivated.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'product_website_id' => ['nullable', 'integer'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'employee_profile_id' => ['nullable', 'integer'],
            'branch_id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:120'],
            'vehicle_type' => ['nullable', 'string', 'max:60'],
            'vehicle_number' => ['nullable', 'string', 'max:80'],
            'nid' => ['nullable', 'string', 'max:80'],
            'joining_date' => ['nullable', 'date'],
            'salary_type' => ['nullable', Rule::in(array_keys($this->salaryTypes()))],
            'salary_amount' => ['nullable', 'numeric', 'min:0'],
            'commission_type' => ['nullable', Rule::in(array_keys($this->commissionTypes()))],
            'commission_amount' => ['nullable', 'numeric', 'min:0'],
            'cash_collection_limit' => ['nullable', 'numeric', 'min:0'],
            'current_status' => ['nullable', Rule::in(array_keys($this->currentStatuses()))],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);
    }

    private function deliveryUserOptions()
    {
        return User::query()
            ->select('id', 'name', 'phone', 'email')
            ->orderBy('name')
            ->limit(300)
            ->get();
    }

    private function currentStatuses(): array
    {
        return [
            'available' => 'Available',
            'assigned' => 'Assigned',
            'on_delivery' => 'On Delivery',
            'on_leave' => 'On Leave',
            'inactive' => 'Inactive',
        ];
    }

    private function salaryTypes(): array
    {
        return [
            'monthly' => 'Monthly',
            'daily' => 'Daily',
            'per_delivery' => 'Per Delivery',
            'none' => 'None',
        ];
    }

    private function commissionTypes(): array
    {
        return [
            'none' => 'None',
            'fixed' => 'Fixed',
            'percentage' => 'Percentage',
            'per_delivery' => 'Per Delivery',
        ];
    }
}
