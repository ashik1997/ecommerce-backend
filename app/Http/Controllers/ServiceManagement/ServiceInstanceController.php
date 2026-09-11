<?php

namespace App\Http\Controllers\ServiceManagement;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Customer\Models\Customer;
use App\Models\GeneralInfo;
use App\Models\ServiceManagement\Service;
use App\Models\ServiceManagement\ServiceInstance;
use App\Services\ServiceManagement\ServiceBillingService;
use App\Services\ServiceManagement\ServiceInventoryService;
use App\Services\ServiceManagement\ServiceTransactionService;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RuntimeException;

class ServiceInstanceController extends Controller
{
    public function __construct(
        private ServiceBillingService $billingService,
        private ServiceInventoryService $inventoryService,
        private ServiceTransactionService $transactionService
    )
    {
    }

    public function index()
    {
        $instances = ServiceInstance::with(['service', 'customer', 'products.product'])
            ->latest()
            ->paginate(20);

        return view('backend.service_management.instances.index', compact('instances'));
    }

    public function create()
    {
        $customers = Customer::where('status', 'active')->orderBy('name')->get();
        $services = Service::where('is_active', true)->orderBy('name')->get();

        return view('backend.service_management.instances.create', [
            'customers' => $customers,
            'services' => $services,
            'statuses' => ['draft', 'confirmed'],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $service = Service::findOrFail($data['service_id']);
        $billing = $this->billingService->calculate($service, $data, $data['products'] ?? []);

        try {
            DB::transaction(function () use ($data, $billing) {
                $instance = ServiceInstance::create([
                    'instance_no' => $this->generateInstanceNo(),
                    'service_id' => $data['service_id'],
                    'customer_id' => $data['customer_id'],
                    'start_date' => $data['start_date'] ?? null,
                    'end_date' => $data['end_date'] ?? null,
                    'billing_unit_qty' => $billing['billing_unit_qty'],
                    'service_unit_price' => $billing['service_unit_price'],
                    'service_subtotal' => $billing['service_subtotal'],
                    'products_subtotal' => $billing['products_subtotal'],
                    'total_amount' => $billing['total_amount'],
                    'paid_amount' => 0,
                    'due_amount' => $billing['due_amount'],
                    'status' => $data['status'],
                    'confirmed_at' => $data['status'] === 'confirmed' ? now() : null,
                    'note' => $data['note'] ?? null,
                    'created_by' => auth()->id(),
                ]);

                foreach ($billing['products'] as $product) {
                    $instance->products()->create([
                        'product_id' => $product['product_id'],
                        'quantity_used' => $product['quantity_used'],
                        'unit_price' => $product['unit_price'],
                        'total_price' => $product['total_price'],
                        'is_required' => $product['is_required'],
                        'note' => $product['note'] ?? null,
                    ]);
                }

                $this->inventoryService->applyForInstance($instance);
            });
        } catch (RuntimeException $exception) {
            Toastr::error($exception->getMessage(), 'Stock Error');

            return back()->withInput()->withErrors(['products' => $exception->getMessage()]);
        }

        Toastr::success('Service instance saved successfully.', 'Success');

        return redirect()->route('service-management.instances.index');
    }

    public function serviceProducts(Service $service)
    {
        $service->load(['serviceProducts.product']);

        return response()->json([
            'service' => [
                'id' => $service->id,
                'name' => $service->name,
                'type' => $service->type,
                'base_price' => (float) $service->base_price,
                'billing_unit' => $service->billing_unit,
            ],
            'products' => $service->serviceProducts->map(function ($serviceProduct) {
                $product = $serviceProduct->product;
                $fallbackPrice = $product ? ($product->discount_price ?: $product->price) : 0;

                return [
                    'product_id' => $serviceProduct->product_id,
                    'name' => $product->name ?? 'Product not found',
                    'sku' => $product->sku ?? null,
                    'quantity_used' => (float) $serviceProduct->quantity_required,
                    'unit_price' => (float) ($serviceProduct->default_unit_price ?? $fallbackPrice ?? 0),
                    'is_required' => (bool) $serviceProduct->is_required,
                    'note' => $serviceProduct->note,
                ];
            })->values(),
        ]);
    }

    public function invoice(ServiceInstance $instance)
    {
        $instance->load(['service', 'customer', 'products.product', 'payments.paymentType']);

        return view('backend.service_management.invoices.invoice', [
            'instance' => $instance,
            'company' => $this->companyInfo(),
        ]);
    }

    public function confirm(ServiceInstance $instance)
    {
        try {
            DB::transaction(function () use ($instance) {
                $instance = ServiceInstance::with(['service', 'products.product'])
                    ->lockForUpdate()
                    ->findOrFail($instance->id);

                if (!$instance->canConfirm()) {
                    throw new RuntimeException('Only draft service instances can be confirmed.');
                }

                $instance->update([
                    'status' => 'confirmed',
                    'confirmed_at' => now(),
                    'updated_by' => auth()->id(),
                ]);

                $this->inventoryService->applyForInstance($instance->fresh(['service', 'products.product']));
            });

            Toastr::success('Service instance confirmed successfully.', 'Success');
        } catch (RuntimeException $exception) {
            Toastr::error($exception->getMessage(), 'Status Error');
        }

        return back();
    }

    public function bill(ServiceInstance $instance)
    {
        try {
            DB::transaction(function () use ($instance) {
                $instance = ServiceInstance::with(['service'])
                    ->lockForUpdate()
                    ->findOrFail($instance->id);

                if (!in_array($instance->status, ['confirmed', 'billed'], true)) {
                    throw new RuntimeException('Only confirmed service instances can be billed.');
                }

                $this->transactionService->postInstanceAccountingIfMissing($instance);

                $instance->update([
                    'status' => (float) $instance->due_amount <= 0 ? 'paid' : 'billed',
                    'billed_at' => $instance->billed_at ?: now(),
                    'updated_by' => auth()->id(),
                ]);
            });

            Toastr::success('Service instance billed successfully.', 'Success');
        } catch (\Throwable $exception) {
            Toastr::error($exception->getMessage(), 'Billing Error');
        }

        return back();
    }

    public function close(Request $request, ServiceInstance $instance)
    {
        $data = $request->validate([
            'end_date' => ['nullable', 'date'],
        ]);

        try {
            DB::transaction(function () use ($instance, $data) {
                $instance = ServiceInstance::with(['service', 'products.product'])
                    ->lockForUpdate()
                    ->findOrFail($instance->id);

                if (!$instance->canClose()) {
                    throw new RuntimeException('This service instance cannot be closed.');
                }

                $endDate = $data['end_date'] ?? optional($instance->end_date)->toDateString() ?? now()->toDateString();

                if ($instance->start_date && $endDate < $instance->start_date->toDateString()) {
                    throw new RuntimeException('Close date cannot be before start date.');
                }

                if ($instance->status === 'confirmed' && !$instance->accounting_posted_at) {
                    $this->refreshBillingForClose($instance, $endDate);
                }

                $this->transactionService->postInstanceAccountingIfMissing($instance->fresh(['service']));
                $this->inventoryService->returnRentalForInstance($instance->fresh(['service', 'products.product']));

                $instance->refresh();
                $instance->update([
                    'end_date' => $endDate,
                    'status' => (float) $instance->due_amount <= 0 ? 'paid' : 'billed',
                    'billed_at' => $instance->billed_at ?: now(),
                    'closed_at' => now(),
                    'updated_by' => auth()->id(),
                ]);
            });

            Toastr::success('Service instance closed successfully.', 'Success');
        } catch (\Throwable $exception) {
            Toastr::error($exception->getMessage(), 'Close Error');
        }

        return back();
    }

    public function cancel(ServiceInstance $instance)
    {
        try {
            DB::transaction(function () use ($instance) {
                $instance = ServiceInstance::with(['service', 'products.product'])
                    ->lockForUpdate()
                    ->findOrFail($instance->id);

                if (!$instance->canCancel() || $instance->payments()->exists()) {
                    throw new RuntimeException('This service instance cannot be cancelled.');
                }

                $this->inventoryService->returnStockForCancelledInstance($instance);

                $instance->refresh();
                $instance->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'closed_at' => $instance->closed_at ?: now(),
                    'due_amount' => 0,
                    'updated_by' => auth()->id(),
                ]);
            });

            Toastr::success('Service instance cancelled successfully.', 'Success');
        } catch (RuntimeException $exception) {
            Toastr::error($exception->getMessage(), 'Cancel Error');
        }

        return back();
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'service_id' => ['required', 'exists:srms_services,id'],
            'customer_id' => ['required', 'exists:customers,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'billing_unit_qty' => ['required', 'numeric', 'min:0.0001'],
            'service_unit_price' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['draft', 'confirmed'])],
            'note' => ['nullable', 'string'],
            'products' => ['nullable', 'array'],
            'products.*.product_id' => ['required_with:products', 'exists:products,id', 'distinct'],
            'products.*.quantity_used' => ['required_with:products', 'integer', 'min:1'],
            'products.*.unit_price' => ['required_with:products', 'numeric', 'min:0'],
            'products.*.is_required' => ['nullable', 'boolean'],
            'products.*.note' => ['nullable', 'string'],
        ]);
    }

    private function generateInstanceNo(): string
    {
        do {
            $instanceNo = 'SRV-' . now()->format('ymd') . '-' . Str::upper(Str::random(5));
        } while (ServiceInstance::where('instance_no', $instanceNo)->exists());

        return $instanceNo;
    }

    private function refreshBillingForClose(ServiceInstance $instance, string $endDate): void
    {
        $billingUnitQty = $this->billingService->billingUnitQuantity(
            $instance->service,
            optional($instance->start_date)->toDateString(),
            $endDate,
            (float) $instance->billing_unit_qty
        );

        $serviceSubtotal = round($billingUnitQty * (float) $instance->service_unit_price, 2);
        $totalAmount = round($serviceSubtotal + (float) $instance->products_subtotal, 2);
        $paidAmount = round((float) $instance->paid_amount, 2);

        $instance->update([
            'end_date' => $endDate,
            'billing_unit_qty' => $billingUnitQty,
            'service_subtotal' => $serviceSubtotal,
            'total_amount' => $totalAmount,
            'due_amount' => round(max(0, $totalAmount - $paidAmount), 2),
            'updated_by' => auth()->id(),
        ]);
    }

    private function companyInfo(): array
    {
        $generalInfo = GeneralInfo::where('id', 1)->first();

        return [
            'name' => $generalInfo->company_name ?? config('app.name', 'Company Name'),
            'address' => $generalInfo->address ?? '',
            'phone' => $generalInfo->contact ?? '',
            'email' => $generalInfo->email ?? '',
            'website' => $generalInfo->website ?? str_replace(['http://', 'https://'], '', url('/')),
            'logo' => $generalInfo && $generalInfo->logo ? get_file_url() . '/' . $generalInfo->logo : null,
        ];
    }
}
