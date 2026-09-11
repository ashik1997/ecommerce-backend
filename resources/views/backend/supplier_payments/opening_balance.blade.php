@extends('backend.master')

@section('header_css')
    <link href="{{ url('assets') }}/plugins/select2/select2.min.css" rel="stylesheet" type="text/css" />
@endsection

@section('page_title')
    Supplier Old Due / Opening Balance
@endsection

@section('page_heading')
    Supplier Old Due / Opening Balance
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-body">
                    <h4 class="mb-3">Add Supplier Opening Balance</h4>

                    <form action="{{ route('StoreSupplierOpeningBalance') }}" method="POST">
                        @csrf

                        <div class="form-group">
                            <label>Supplier <span class="text-danger">*</span></label>
                            <select name="supplier_id" class="form-control select2" required>
                                <option value="">Select Supplier</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                        {{ $supplier->name }} - {{ $supplier->contact_number ?? 'N/A' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Entry Type <span class="text-danger">*</span></label>
                            <select name="entry_type" class="form-control" required>
                                <option value="due" {{ old('entry_type') === 'due' ? 'selected' : '' }}>Old Due</option>
                                <option value="advance" {{ old('entry_type') === 'advance' ? 'selected' : '' }}>Old Advance</option>
                            </select>
                            <small class="text-muted">Old due increases payable. Old advance increases supplier advance asset.</small>
                        </div>

                        <div class="form-group">
                            <label>Amount <span class="text-danger">*</span></label>
                            <input type="number" name="amount" class="form-control" step="0.01" min="0.01" value="{{ old('amount') }}" required>
                        </div>

                        <div class="form-group">
                            <label>Opening Date <span class="text-danger">*</span></label>
                            <input type="date" name="opening_date" class="form-control" value="{{ old('opening_date', now()->toDateString()) }}" required>
                        </div>

                        <div class="form-group">
                            <label>Invoice No</label>
                            <input type="text" name="invoice_no" class="form-control" value="{{ old('invoice_no') }}" placeholder="Old invoice / bill no">
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Invoice Date</label>
                                <input type="date" name="invoice_date" class="form-control" value="{{ old('invoice_date') }}">
                            </div>
                            <div class="form-group col-md-6">
                                <label>Due Date</label>
                                <input type="date" name="due_date" class="form-control" value="{{ old('due_date') }}">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Reference</label>
                            <input type="text" name="reference_no" class="form-control" value="{{ old('reference_no') }}" placeholder="Ledger page / note ref">
                        </div>

                        <div class="form-group">
                            <label>Note</label>
                            <textarea name="note" class="form-control" rows="3">{{ old('note') }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-success">Save Opening Balance</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-body">
                    <h4 class="mb-3">Recent Supplier Opening Balances</h4>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Supplier</th>
                                    <th>Type</th>
                                    <th>Invoice</th>
                                    <th class="text-right">Opening</th>
                                    <th class="text-right">Remaining</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($openingBalances as $opening)
                                    <tr>
                                        <td>{{ optional($opening->opening_date)->format('Y-m-d') }}</td>
                                        <td>{{ $opening->supplier->name ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge badge-{{ $opening->entry_type === 'due' ? 'danger' : 'info' }}">
                                                {{ ucfirst($opening->entry_type) }}
                                            </span>
                                        </td>
                                        <td>{{ $opening->invoice_no ?: ($opening->reference_no ?: '-') }}</td>
                                        <td class="text-right">৳{{ number_format($opening->opening_amount, 2) }}</td>
                                        <td class="text-right">৳{{ number_format($opening->remaining_amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center">No supplier opening balance found</td></tr>
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
