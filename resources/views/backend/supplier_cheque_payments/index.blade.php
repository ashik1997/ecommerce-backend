@extends('backend.master')

@section('header_css')
    <style>
        .cheque-card { background:#fff; border:1px solid #eef0f4; border-radius:6px; padding:14px; margin-bottom:16px; }
        .cheque-card h5 { margin:0 0 6px; font-size:13px; color:#6c757d; }
        .cheque-card strong { font-size:20px; }
        .badge-upcoming { background:#ffc107; color:#212529; }
        .badge-due_today { background:#fd7e14; color:#fff; }
        .badge-overdue { background:#dc3545; color:#fff; }
    </style>
@endsection

@section('page_title')
    Supplier Cheque Payments
@endsection

@section('page_heading')
    Supplier Cheque Payments
@endsection

@section('content')
    <div class="row">
        @foreach ([
            'pending' => 'Total Pending',
            'upcoming' => 'Upcoming 7 Days',
            'due_today' => 'Due Today',
            'overdue' => 'Overdue',
            'cleared' => 'Cleared',
        ] as $key => $label)
            <div class="col-md">
                <div class="cheque-card">
                    <h5>{{ $label }}</h5>
                    <strong>৳{{ number_format($cards[$key] ?? 0, 2) }}</strong>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="card-title mb-0">All Supplier Cheques</h4>
                <a href="{{ route('supplier-cheque-payments.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create Cheque Payment
                </a>
            </div>

            <div class="mb-3">
                @foreach ([
                    '' => 'All',
                    'pending' => 'Pending',
                    'upcoming' => 'Upcoming 7 Days',
                    'due_today' => 'Due Today',
                    'overdue' => 'Overdue',
                    'cleared' => 'Cleared',
                    'cancelled' => 'Cancelled',
                    'bounced' => 'Bounced',
                ] as $filter => $label)
                    <a href="{{ route('supplier-cheque-payments.index', array_filter(['filter' => $filter])) }}"
                       class="btn btn-sm {{ request('filter') === $filter || (!request('filter') && $filter === '') ? 'btn-info' : 'btn-outline-info' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <form method="GET" class="border rounded p-3 mb-3">
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <label>Supplier</label>
                        <select name="supplier_id" class="form-control">
                            <option value="">All Suppliers</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <label>Payment Type</label>
                        <select name="payment_type_id" class="form-control">
                            <option value="">All</option>
                            @foreach ($paymentTypes as $paymentType)
                                <option value="{{ $paymentType->id }}" {{ request('payment_type_id') == $paymentType->id ? 'selected' : '' }}>
                                    {{ $paymentType->payment_type }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="">All</option>
                            @foreach (['pending', 'cleared', 'cancelled', 'bounced'] as $status)
                                <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <label>Execution From</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
                    </div>
                    <div class="col-md-2 mb-2">
                        <label>Execution To</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
                    </div>
                    <div class="col-md-1 mb-2 d-flex align-items-end">
                        <button class="btn btn-secondary btn-block">Filter</button>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th>SL</th>
                            <th>Supplier</th>
                            <th>Purchase</th>
                            <th>Payment Type</th>
                            <th>Source Account</th>
                            <th>Cheque No</th>
                            <th>Bank</th>
                            <th>Amount</th>
                            <th>Issue Date</th>
                            <th>Execution Date</th>
                            <th>Days Left</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($cheques as $cheque)
                            <tr>
                                <td>{{ $cheques->firstItem() + $loop->index }}</td>
                                <td>{{ $cheque->supplier->name ?? 'N/A' }}</td>
                                <td>{{ $cheque->purchase->code ?? 'N/A' }}</td>
                                <td>{{ $cheque->paymentType->payment_type ?? 'N/A' }}</td>
                                <td>{{ $cheque->sourceAccount->account_name ?? 'N/A' }}</td>
                                <td>{{ $cheque->cheque_number }}</td>
                                <td>{{ $cheque->bank_name ?? 'N/A' }}</td>
                                <td>৳{{ number_format($cheque->amount, 2) }}</td>
                                <td>{{ optional($cheque->issue_date)->format('Y-m-d') }}</td>
                                <td>{{ optional($cheque->execution_date)->format('Y-m-d') }}</td>
                                <td>{{ $cheque->days_left }}</td>
                                <td>{!! supplier_cheque_status_badge($cheque->cheque_state) !!}</td>
                                <td style="min-width:180px;">
                                    @if ($cheque->status === 'pending')
                                        <a href="{{ route('supplier-cheque-payments.edit', $cheque->id) }}" class="btn btn-sm btn-info">Edit</a>
                                        <form action="{{ route('supplier-cheque-payments.mark-cleared', $cheque->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn btn-sm btn-success" onclick="return confirm('Mark this cheque as cleared and post accounting entries?')">Clear</button>
                                        </form>
                                        <form action="{{ route('supplier-cheque-payments.mark-bounced', $cheque->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn btn-sm btn-warning" onclick="return confirm('Mark this cheque as bounced?')">Bounce</button>
                                        </form>
                                        <form action="{{ route('supplier-cheque-payments.mark-cancelled', $cheque->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn btn-sm btn-secondary" onclick="return confirm('Cancel this cheque?')">Cancel</button>
                                        </form>
                                    @endif
                                    @if ($cheque->status !== 'cleared')
                                        <form action="{{ route('supplier-cheque-payments.destroy', $cheque->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-danger" onclick="return confirm('Delete this cheque record?')">Delete</button>
                                        </form>
                                    @else
                                        <span class="text-muted">Processed</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="text-center">No supplier cheque payments found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $cheques->links() }}
        </div>
    </div>
@endsection
