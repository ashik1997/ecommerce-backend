<?php

namespace App\Http\Controllers\ServiceManagement;

use App\Http\Controllers\Controller;
use App\Models\ServiceManagement\Service;
use App\Models\ServiceManagement\ServiceProduct;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::latest()->paginate(20);

        return view('backend.service_management.services.index', compact('services'));
    }

    public function create()
    {
        return view('backend.service_management.services.create', [
            'types' => Service::TYPES,
            'billingUnits' => Service::BILLING_UNITS,
        ]);
    }

    public function store(Request $request)
    {
        Service::create($this->validated($request) + [
            'is_active' => true,
            'created_by' => auth()->id(),
        ]);

        Toastr::success('Service saved successfully.', 'Success');

        return redirect()->route('service-management.services.index');
    }

    public function edit(Service $service)
    {
        $service->load(['serviceProducts.product']);

        return view('backend.service_management.services.edit', [
            'service' => $service,
            'types' => Service::TYPES,
            'billingUnits' => Service::BILLING_UNITS,
        ]);
    }

    public function update(Request $request, Service $service)
    {
        $service->update($this->validated($request, $service->id) + [
            'is_active' => $request->boolean('is_active'),
            'updated_by' => auth()->id(),
        ]);

        Toastr::success('Service updated successfully.', 'Success');

        return redirect()->route('service-management.services.index');
    }

    public function destroy(Service $service)
    {
        $service->update([
            'is_active' => false,
            'updated_by' => auth()->id(),
        ]);

        Toastr::success('Service archived successfully.', 'Success');

        return back();
    }

    public function storeProduct(Request $request, Service $service)
    {
        ServiceProduct::create($this->validatedProduct($request, $service) + [
            'service_id' => $service->id,
            'created_by' => auth()->id(),
        ]);

        Toastr::success('Product attached to service.', 'Success');

        return back();
    }

    public function updateProduct(Request $request, Service $service, ServiceProduct $serviceProduct)
    {
        $this->ensureServiceProductBelongsToService($service, $serviceProduct);

        $serviceProduct->update($this->validatedProduct($request, $service, $serviceProduct->id) + [
            'updated_by' => auth()->id(),
        ]);

        Toastr::success('Service product updated.', 'Success');

        return back();
    }

    public function destroyProduct(Service $service, ServiceProduct $serviceProduct)
    {
        $this->ensureServiceProductBelongsToService($service, $serviceProduct);

        $serviceProduct->delete();

        Toastr::success('Product removed from service.', 'Success');

        return back();
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('srms_services', 'name')
                    ->where(fn($query) => $query->where('type', $request->type))
                    ->ignore($ignoreId),
            ],
            'type' => ['required', Rule::in(Service::TYPES)],
            'base_price' => ['required', 'numeric', 'min:0'],
            'billing_unit' => ['required', Rule::in(Service::BILLING_UNITS)],
            'description' => ['nullable', 'string'],
        ]);
    }

    private function validatedProduct(Request $request, Service $service, ?int $ignoreId = null): array
    {
        return $request->validate([
            'product_id' => [
                'required',
                'exists:products,id',
                Rule::unique('srms_service_products', 'product_id')
                    ->where(fn($query) => $query->where('service_id', $service->id))
                    ->ignore($ignoreId),
            ],
            'quantity_required' => ['required', 'integer', 'min:1'],
            'default_unit_price' => ['nullable', 'numeric', 'min:0'],
            'is_required' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string'],
        ]) + [
            'is_required' => $request->boolean('is_required'),
        ];
    }

    private function ensureServiceProductBelongsToService(Service $service, ServiceProduct $serviceProduct): void
    {
        abort_if((int) $serviceProduct->service_id !== (int) $service->id, 404);
    }
}
