<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\ProductOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CustomerBillingController extends Controller
{
    public function index(Request $request)
    {
        $this->ensureBillingSchemaExists();

        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'status' => trim((string) $request->input('status', '')),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ];

        $query = $this->billingQuery($filters);

        $billings = (clone $query)
            ->orderByDesc(DB::raw('COALESCE(product_orders.sale_date, DATE(product_orders.created_at))'))
            ->orderByDesc('product_orders.id')
            ->paginate(25)
            ->withQueryString();

        $summary = [
            'invoice_count' => (clone $query)->count(),
            'total_amount' => (clone $query)->sum('product_orders.total'),
            'paid_amount' => (clone $query)->sum('product_orders.paid_amount'),
            'due_amount' => (clone $query)->sum('product_orders.due_amount'),
        ];

        return view('backend.customer_billing.index', compact('billings', 'summary', 'filters'));
    }

    private function ensureBillingSchemaExists(): void
    {
        if (! Schema::hasTable('product_orders')) {
            abort(503, 'Customer billing storage is not available.');
        }
    }

    private function billingQuery(array $filters)
    {
        $orderTable = (new ProductOrder())->getTable();
        $hasInvoiceNo = Schema::hasColumn($orderTable, 'invoice_no');
        $hasCustomers = Schema::hasTable('customers');
        $invoiceExpression = $hasInvoiceNo
            ? 'COALESCE(product_orders.invoice_no, product_orders.order_code)'
            : 'product_orders.order_code';
        $customerNameExpression = $hasCustomers
            ? "COALESCE(customers.name, customers.full_name, product_orders.customer_name, 'Guest')"
            : "COALESCE(product_orders.customer_name, 'Guest')";

        $query = ProductOrder::query()
            ->where('product_orders.status', 'active')
            ->where(function ($billingStatusQuery) {
                $billingStatusQuery
                    ->whereRaw('COALESCE(product_orders.due_amount, 0) > 0')
                    ->orWhereIn(DB::raw('LOWER(COALESCE(product_orders.payment_status, ""))'), ['due', 'unpaid']);
            })
            ->whereNotIn(DB::raw('LOWER(COALESCE(product_orders.payment_status, ""))'), ['paid', 'completed', 'complete', 'cancelled', 'canceled'])
            ->whereNotIn(DB::raw('LOWER(COALESCE(product_orders.order_status, ""))'), ['paid', 'completed', 'complete', 'cancelled', 'canceled'])
            ->select([
                'product_orders.id',
                'product_orders.slug',
                DB::raw($customerNameExpression . ' as customer_name'),
                DB::raw($invoiceExpression . ' as invoice_no'),
                DB::raw('COALESCE(product_orders.total, 0) as total_amount'),
                DB::raw('COALESCE(product_orders.paid_amount, 0) as paid_amount'),
                DB::raw('COALESCE(product_orders.due_amount, 0) as due_amount'),
                DB::raw('COALESCE(NULLIF(product_orders.payment_status, ""), product_orders.order_status) as status'),
                DB::raw('COALESCE(product_orders.sale_date, DATE(product_orders.created_at)) as billing_date'),
            ]);

        if ($hasCustomers) {
            $query->leftJoin('customers', 'customers.id', '=', 'product_orders.customer_id');
        }

        if ($filters['search'] !== '') {
            $search = $filters['search'];
            $query->where(function ($searchQuery) use ($search, $hasInvoiceNo, $hasCustomers) {
                $searchQuery
                    ->where('product_orders.order_code', 'like', '%' . $search . '%')
                    ->orWhere('product_orders.customer_name', 'like', '%' . $search . '%')
                    ->orWhere('product_orders.customer_phone', 'like', '%' . $search . '%');

                if ($hasCustomers) {
                    $searchQuery
                        ->orWhere('customers.name', 'like', '%' . $search . '%')
                        ->orWhere('customers.full_name', 'like', '%' . $search . '%');
                }

                if ($hasInvoiceNo) {
                    $searchQuery->orWhere('product_orders.invoice_no', 'like', '%' . $search . '%');
                }
            });
        }

        if ($filters['status'] !== '') {
            $query->where(function ($statusQuery) use ($filters) {
                $statusQuery
                    ->where('product_orders.payment_status', $filters['status'])
                    ->orWhere('product_orders.order_status', $filters['status']);
            });
        }

        if ($filters['date_from']) {
            $query->whereDate(DB::raw('COALESCE(product_orders.sale_date, product_orders.created_at)'), '>=', $filters['date_from']);
        }

        if ($filters['date_to']) {
            $query->whereDate(DB::raw('COALESCE(product_orders.sale_date, product_orders.created_at)'), '<=', $filters['date_to']);
        }

        return $query;
    }
}
