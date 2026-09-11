@extends('backend.master')

@section('page_title', 'Customer Billing')
@section('page_heading', 'Customer Billing')

@section('content')
    <div class="row mb-3">
        <div class="col-md-3 mb-2">
            <div class="card">
                <div class="card-body">
                    <div class="text-muted">Invoices</div>
                    <h4 class="mb-0">{{ number_format($summary['invoice_count']) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="card">
                <div class="card-body">
                    <div class="text-muted">Total Amount</div>
                    <h4 class="mb-0">{{ number_format((float) $summary['total_amount'], 2) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="card">
                <div class="card-body">
                    <div class="text-muted">Paid Amount</div>
                    <h4 class="mb-0 text-success">{{ number_format((float) $summary['paid_amount'], 2) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="card">
                <div class="card-body">
                    <div class="text-muted">Due Amount</div>
                    <h4 class="mb-0 text-danger">{{ number_format((float) $summary['due_amount'], 2) }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="mb-1">Customer Billing Records</h5>
                    <p class="mb-0 text-muted">Billing records generated from sales invoices.</p>
                </div>
                <a href="{{ route('CreateCustomerDuePayment') }}" class="btn btn-success mt-2 mt-md-0">Receive Due Payment</a>
            </div>

            <form method="GET" action="{{ route('CustomerBillingIndex') }}" class="row mb-3">
                <div class="col-md-4 mb-2">
                    <input type="text" name="search" class="form-control" value="{{ $filters['search'] }}"
                        placeholder="Search customer, phone, invoice">
                </div>
                <div class="col-md-2 mb-2">
                    <select name="status" class="form-control">
                        <option value="">All statuses</option>
                        @foreach (['pending', 'paid', 'partial', 'cod', 'invoiced', 'delivered'] as $status)
                            <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] }}">
                </div>
                <div class="col-md-2 mb-2">
                    <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] }}">
                </div>
                <div class="col-md-2 mb-2">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="{{ route('CustomerBillingIndex') }}" class="btn btn-light">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th>Customer Name</th>
                            <th>Invoice No</th>
                            <th class="text-right">Total Amount</th>
                            <th class="text-right">Paid Amount</th>
                            <th class="text-right">Due Amount</th>
                            <th>Status</th>
                            <th>Billing Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($billings as $billing)
                            @php
                                $status = strtolower((string) $billing->status);
                                $badgeClass = [
                                    'paid' => 'badge-success',
                                    'delivered' => 'badge-success',
                                    'partial' => 'badge-warning',
                                    'pending' => 'badge-secondary',
                                    'cod' => 'badge-info',
                                    'invoiced' => 'badge-primary',
                                ][$status] ?? 'badge-light';
                            @endphp
                            <tr>
                                <td>{{ $billing->customer_name }}</td>
                                <td>{{ $billing->invoice_no ?: '-' }}</td>
                                <td class="text-right">{{ number_format((float) $billing->total_amount, 2) }}</td>
                                <td class="text-right">{{ number_format((float) $billing->paid_amount, 2) }}</td>
                                <td class="text-right text-danger">{{ number_format((float) $billing->due_amount, 2) }}</td>
                                <td><span class="badge {{ $badgeClass }}">{{ ucfirst($billing->status ?: 'N/A') }}</span></td>
                                <td>{{ $billing->billing_date ? \Carbon\Carbon::parse($billing->billing_date)->format('Y-m-d') : '-' }}</td>
                                <td>
                                    @if ($billing->slug)
                                        <a href="{{ route('ShowProductOrder', $billing->slug) }}" class="btn btn-sm btn-info">View</a>
                                    @endif
                                    @if ($billing->slug && (float) $billing->due_amount > 0)
                                        <a href="{{ route('PayDueProductOrder', $billing->slug) }}" class="btn btn-sm btn-success">Pay</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">No customer billing records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $billings->links() }}
        </div>
    </div>
@endsection
