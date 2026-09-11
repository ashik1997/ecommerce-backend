@extends('backend.master')

@section('header_css')
    <link href="{{ url('assets') }}/plugins/select2/select2.min.css" rel="stylesheet" type="text/css" />
@endsection

@section('page_title')
    Customer Old Due / Opening Balance
@endsection

@section('page_heading')
    Customer Old Due / Opening Balance
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-body">
                    <h4 class="mb-3">Add Opening Balance</h4>

                    <form action="{{ route('StoreCustomerOpeningBalance') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label>Customer <span class="text-danger">*</span></label>
                            <select name="customer_id" class="form-control select2" required>
                                <option value="">Select Customer</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}">{{ $customer->name }} - {{ $customer->phone }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Entry Type <span class="text-danger">*</span></label>
                            <select name="entry_type" class="form-control" required>
                                <option value="due">Old Due</option>
                                <option value="advance">Old Advance</option>
                            </select>
                            <small class="text-muted">Old due increases receivable. Old advance increases customer liability.</small>
                        </div>

                        <div class="form-group">
                            <label>Amount <span class="text-danger">*</span></label>
                            <input type="number" name="amount" class="form-control" step="0.01" min="0.01" required>
                        </div>

                        <div class="form-group">
                            <label>Opening Date <span class="text-danger">*</span></label>
                            <input type="date" name="opening_date" class="form-control" value="{{ now()->toDateString() }}" required>
                        </div>

                        <div class="form-group">
                            <label>Reference</label>
                            <input type="text" name="reference_no" class="form-control" placeholder="Old ledger ref / note no">
                        </div>

                        <div class="form-group">
                            <label>Note</label>
                            <textarea name="note" class="form-control" rows="3"></textarea>
                        </div>

                        <button type="submit" class="btn btn-success">Save Opening Balance</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-body">
                    <h4 class="mb-3">Recent Opening Balances</h4>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Customer</th>
                                    <th>Type</th>
                                    <th>Reference</th>
                                    <th class="text-right">Opening</th>
                                    <th class="text-right">Remaining</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($openingBalances as $opening)
                                    <tr>
                                        <td>{{ optional($opening->opening_date)->format('Y-m-d') }}</td>
                                        <td>{{ $opening->customer->name ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge badge-{{ $opening->entry_type === 'due' ? 'danger' : 'info' }}">
                                                {{ ucfirst($opening->entry_type) }}
                                            </span>
                                        </td>
                                        <td>{{ $opening->reference_no ?: '-' }}</td>
                                        <td class="text-right">৳{{ number_format($opening->opening_amount, 2) }}</td>
                                        <td class="text-right">৳{{ number_format($opening->remaining_amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center">No opening balance found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('footer_js')
    <script src="{{ url('assets') }}/plugins/select2/select2.min.js"></script>
    <script>
        $('.select2').select2();
    </script>
@endsection
