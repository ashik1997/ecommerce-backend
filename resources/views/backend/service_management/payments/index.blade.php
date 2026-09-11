@extends('backend.master')
@section('header_css')
    <link href="{{ url('assets') }}/plugins/select2/select2.min.css" rel="stylesheet" type="text/css" />
@endsection
@section('page_title', 'Service Payments')
@section('page_heading', 'Service Payments')
@section('content')
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-3">Receive Service Payment</h5>
            <form method="POST" action="{{ route('service-management.payments.store') }}">
                @csrf
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Service Instance <span class="text-danger">*</span></label>
                        <select name="service_instance_id" id="service_instance_id" class="form-control select2 @error('service_instance_id') is-invalid @enderror" required>
                            <option value="">Select Billed Due Instance</option>
                            @foreach ($dueInstances as $instance)
                                <option
                                    value="{{ $instance->id }}"
                                    data-due="{{ $instance->due_amount }}"
                                    {{ old('service_instance_id') == $instance->id ? 'selected' : '' }}
                                >
                                    {{ $instance->instance_no }} - {{ $instance->customer->name ?? $instance->customer->full_name ?? 'N/A' }} - Due {{ number_format((float) $instance->due_amount, 2) }}
                                </option>
                            @endforeach
                        </select>
                        @error('service_instance_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                        <select name="payment_type_id" class="form-control select2 @error('payment_type_id') is-invalid @enderror" required>
                            <option value="">Select Method</option>
                            @foreach ($paymentTypes as $paymentType)
                                <option value="{{ $paymentType->id }}" {{ old('payment_type_id') == $paymentType->id ? 'selected' : '' }}>{{ $paymentType->payment_type }}</option>
                            @endforeach
                        </select>
                        @error('payment_type_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">Amount <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" name="amount" id="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount') }}" required>
                        @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                        <input type="date" name="payment_date" class="form-control @error('payment_date') is-invalid @enderror" value="{{ old('payment_date', now()->toDateString()) }}" required>
                        @error('payment_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-2 mb-3 d-flex align-items-end">
                        <button class="btn btn-success btn-block">Receive Payment</button>
                    </div>
                    <div class="col-md-12 mb-2">
                        <input type="text" name="note" class="form-control @error('note') is-invalid @enderror" value="{{ old('note') }}" placeholder="Payment note">
                        @error('note')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </form>
            <p class="mb-0 text-muted">
                Missing service revenue, receivable, cash/bank, or payment method account heads are created automatically before posting.
            </p>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="mb-3">Service Payment History</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th>Payment No</th>
                            <th>Date</th>
                            <th>Instance</th>
                            <th>Customer</th>
                            <th>Method</th>
                            <th>Account</th>
                            <th>Amount</th>
                            <th>Note</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($payments as $payment)
                            <tr>
                                <td>{{ $payment->payment_no }}</td>
                                <td>{{ optional($payment->payment_date)->format('Y-m-d') }}</td>
                                <td>
                                    {{ $payment->serviceInstance->instance_no ?? 'N/A' }}
                                    <div class="small text-muted">{{ $payment->serviceInstance->service->name ?? '' }}</div>
                                </td>
                                <td>{{ $payment->customer->name ?? $payment->customer->full_name ?? 'N/A' }}</td>
                                <td>{{ $payment->paymentType->payment_type ?? 'N/A' }}</td>
                                <td>{{ $payment->account->account_name ?? 'N/A' }}</td>
                                <td>{{ number_format((float) $payment->amount, 2) }}</td>
                                <td>{{ $payment->note }}</td>
                                <td>
                                    <a href="{{ route('service-management.payments.receipt', $payment) }}" target="_blank" class="btn btn-sm btn-info">Receipt</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted">No service payments found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $payments->links() }}
        </div>
    </div>
@endsection

@section('footer_js')
    <script src="{{ url('assets') }}/plugins/select2/select2.min.js"></script>
    <script>
        $('.select2').select2({ width: '100%' });

        $('#service_instance_id').on('change', function () {
            var due = $('option:selected', this).data('due');

            if (due) {
                $('#amount').val(Number(due).toFixed(2));
            }
        });
    </script>
@endsection
