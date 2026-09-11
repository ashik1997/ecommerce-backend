<?php

namespace App\Http\Controllers\ServiceManagement;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Customer\Models\Customer;
use App\Models\ServiceManagement\Service;
use App\Models\ServiceManagement\ServiceInstance;
use App\Models\ServiceManagement\ServiceInstanceProduct;
use App\Models\ServiceManagement\ServicePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServiceManagementController extends Controller
{
    public function dashboard()
    {
        $financialQuery = ServiceInstance::whereIn('status', ['billed', 'paid']);
        $statusCounts = ServiceInstance::select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $summary = [
            'active_services' => Service::where('is_active', true)->count(),
            'total_instances' => ServiceInstance::count(),
            'draft' => $statusCounts['draft'] ?? 0,
            'confirmed' => $statusCounts['confirmed'] ?? 0,
            'billed' => $statusCounts['billed'] ?? 0,
            'paid' => $statusCounts['paid'] ?? 0,
            'cancelled' => $statusCounts['cancelled'] ?? 0,
            'total_bill' => (clone $financialQuery)->sum('total_amount'),
            'total_paid' => (clone $financialQuery)->sum('paid_amount'),
            'total_due' => (clone $financialQuery)->sum('due_amount'),
            'due_invoices' => ServiceInstance::where('status', 'billed')->where('due_amount', '>', 0)->count(),
            'open_rentals' => ServiceInstance::join('srms_services', 'srms_service_instances.service_id', '=', 'srms_services.id')
                ->where('srms_services.type', 'rental')
                ->whereIn('srms_service_instances.status', ['confirmed', 'billed', 'paid'])
                ->whereNull('srms_service_instances.closed_at')
                ->count(),
            'today_collection' => ServicePayment::whereDate('payment_date', now()->toDateString())->sum('amount'),
            'month_collection' => ServicePayment::whereBetween('payment_date', [
                now()->startOfMonth()->toDateString(),
                now()->toDateString(),
            ])->sum('amount'),
        ];

        $recentInstances = ServiceInstance::with(['service', 'customer'])
            ->latest()
            ->limit(8)
            ->get();

        $dueInstances = ServiceInstance::with(['service', 'customer'])
            ->where('status', 'billed')
            ->where('due_amount', '>', 0)
            ->latest()
            ->limit(6)
            ->get();

        $openRentals = ServiceInstance::with(['service', 'customer'])
            ->whereHas('service', fn($query) => $query->where('type', 'rental'))
            ->whereIn('status', ['confirmed', 'billed', 'paid'])
            ->whereNull('closed_at')
            ->latest()
            ->limit(6)
            ->get();

        $recentPayments = ServicePayment::with(['serviceInstance.service', 'customer', 'paymentType'])
            ->latest()
            ->limit(6)
            ->get();

        return view('backend.service_management.dashboard', compact(
            'summary',
            'recentInstances',
            'dueInstances',
            'openRentals',
            'recentPayments'
        ));
    }

    public function billing()
    {
        $instances = ServiceInstance::with(['service', 'customer', 'products'])
            ->whereIn('status', ['confirmed', 'billed', 'paid'])
            ->latest()
            ->paginate(20);

        $summaryQuery = ServiceInstance::whereIn('status', ['confirmed', 'billed', 'paid']);

        $summary = [
            'total_billable' => (clone $summaryQuery)->sum('total_amount'),
            'total_paid' => (clone $summaryQuery)->sum('paid_amount'),
            'total_due' => (clone $summaryQuery)->sum('due_amount'),
            'billable_count' => (clone $summaryQuery)->count(),
        ];

        return view('backend.service_management.billing.index', compact('instances', 'summary'));
    }

    public function reports(Request $request)
    {
        $filters = [
            'date_from' => $request->input('date_from', now()->startOfMonth()->toDateString()),
            'date_to' => $request->input('date_to', now()->toDateString()),
            'service_id' => $request->input('service_id'),
            'customer_id' => $request->input('customer_id'),
        ];

        $instanceQuery = $this->filteredInstances($filters);

        $summary = [
            'instances' => (clone $instanceQuery)->count(),
            'total_bill' => (clone $instanceQuery)->sum('total_amount'),
            'service_amount' => (clone $instanceQuery)->sum('service_subtotal'),
            'product_amount' => (clone $instanceQuery)->sum('products_subtotal'),
            'paid' => (clone $instanceQuery)->sum('paid_amount'),
            'due' => (clone $instanceQuery)->sum('due_amount'),
        ];

        $serviceRows = $this->filteredInstances($filters)
            ->join('srms_services', 'srms_service_instances.service_id', '=', 'srms_services.id')
            ->select([
                'srms_services.id',
                'srms_services.name',
                'srms_services.type',
                DB::raw('COUNT(srms_service_instances.id) as instance_count'),
                DB::raw('SUM(srms_service_instances.service_subtotal) as service_amount'),
                DB::raw('SUM(srms_service_instances.products_subtotal) as product_amount'),
                DB::raw('SUM(srms_service_instances.total_amount) as total_amount'),
                DB::raw('SUM(srms_service_instances.paid_amount) as paid_amount'),
                DB::raw('SUM(srms_service_instances.due_amount) as due_amount'),
            ])
            ->groupBy('srms_services.id', 'srms_services.name', 'srms_services.type')
            ->orderByDesc('total_amount')
            ->get();

        $customerRows = $this->filteredInstances($filters)
            ->join('customers', 'srms_service_instances.customer_id', '=', 'customers.id')
            ->select([
                'customers.id',
                'customers.name',
                'customers.full_name',
                'customers.phone',
                DB::raw('COUNT(srms_service_instances.id) as instance_count'),
                DB::raw('SUM(srms_service_instances.total_amount) as total_amount'),
                DB::raw('SUM(srms_service_instances.paid_amount) as paid_amount'),
                DB::raw('SUM(srms_service_instances.due_amount) as due_amount'),
            ])
            ->groupBy('customers.id', 'customers.name', 'customers.full_name', 'customers.phone')
            ->orderByDesc('total_amount')
            ->limit(20)
            ->get();

        $productRows = $this->filteredInstanceProducts($filters)
            ->join('products', 'srms_service_instance_products.product_id', '=', 'products.id')
            ->select([
                'products.id',
                'products.name',
                'products.sku',
                DB::raw('COUNT(DISTINCT srms_service_instance_products.service_instance_id) as instance_count'),
                DB::raw('SUM(srms_service_instance_products.quantity_used) as quantity_used'),
                DB::raw('SUM(srms_service_instance_products.total_price) as total_amount'),
            ])
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderByDesc('total_amount')
            ->limit(20)
            ->get();

        $paymentRows = $this->filteredPayments($filters)
            ->join('db_paymenttypes', 'srms_service_payments.payment_type_id', '=', 'db_paymenttypes.id')
            ->select([
                'db_paymenttypes.id',
                'db_paymenttypes.payment_type',
                DB::raw('COUNT(srms_service_payments.id) as payment_count'),
                DB::raw('SUM(srms_service_payments.amount) as amount'),
            ])
            ->groupBy('db_paymenttypes.id', 'db_paymenttypes.payment_type')
            ->orderByDesc('amount')
            ->get();

        $services = Service::orderBy('name')->get(['id', 'name']);
        $customers = Customer::where('status', 'active')->orderBy('name')->get(['id', 'name', 'full_name', 'phone']);

        return view('backend.service_management.reports.index', compact(
            'filters',
            'summary',
            'serviceRows',
            'customerRows',
            'productRows',
            'paymentRows',
            'services',
            'customers'
        ));
    }

    private function filteredInstances(array $filters)
    {
        return ServiceInstance::query()
            ->whereIn('srms_service_instances.status', ['billed', 'paid'])
            ->when($filters['date_from'] ?? null, fn($query, $date) => $query->whereDate('srms_service_instances.created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn($query, $date) => $query->whereDate('srms_service_instances.created_at', '<=', $date))
            ->when($filters['service_id'] ?? null, fn($query, $serviceId) => $query->where('srms_service_instances.service_id', $serviceId))
            ->when($filters['customer_id'] ?? null, fn($query, $customerId) => $query->where('srms_service_instances.customer_id', $customerId));
    }

    private function filteredInstanceProducts(array $filters)
    {
        return ServiceInstanceProduct::query()
            ->join('srms_service_instances', 'srms_service_instance_products.service_instance_id', '=', 'srms_service_instances.id')
            ->whereIn('srms_service_instances.status', ['billed', 'paid'])
            ->when($filters['date_from'] ?? null, fn($query, $date) => $query->whereDate('srms_service_instances.created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn($query, $date) => $query->whereDate('srms_service_instances.created_at', '<=', $date))
            ->when($filters['service_id'] ?? null, fn($query, $serviceId) => $query->where('srms_service_instances.service_id', $serviceId))
            ->when($filters['customer_id'] ?? null, fn($query, $customerId) => $query->where('srms_service_instances.customer_id', $customerId));
    }

    private function filteredPayments(array $filters)
    {
        return ServicePayment::query()
            ->join('srms_service_instances', 'srms_service_payments.service_instance_id', '=', 'srms_service_instances.id')
            ->when($filters['date_from'] ?? null, fn($query, $date) => $query->whereDate('srms_service_payments.payment_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn($query, $date) => $query->whereDate('srms_service_payments.payment_date', '<=', $date))
            ->when($filters['service_id'] ?? null, fn($query, $serviceId) => $query->where('srms_service_instances.service_id', $serviceId))
            ->when($filters['customer_id'] ?? null, fn($query, $customerId) => $query->where('srms_service_instances.customer_id', $customerId));
    }
}
