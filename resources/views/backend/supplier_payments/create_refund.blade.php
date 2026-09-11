@extends('backend.master')

@section('header_css')
    <link href="{{ url('assets') }}/plugins/select2/select2.min.css" rel="stylesheet" type="text/css" />
@endsection

@section('page_title')
    Supplier Advance Refund
@endsection

@section('page_heading')
    Receive Supplier Advance Refund
@endsection

@section('content')
    <div class="container" style="max-width: 700px;">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4>Receive Advance Refund</h4>
                            <a href="{{ route('ViewAllSupplierPayments') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                        </div>

                        <form id="refundForm" action="{{ route('ProcessSupplierAdvanceRefund') }}" method="POST">
                            @csrf

                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="supplier_id">Select Supplier <span class="text-danger">*</span></label>
                                    <select id="supplier_id" class="form-control select2" name="supplier_id" required>
                                        <option value="">-- Select Supplier --</option>
                                        @foreach ($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}"
                                                {{ $selectedSupplier && $selectedSupplier->id == $supplier->id ? 'selected' : '' }}>
                                                {{ $supplier->name }} - {{ $supplier->contact_number ?? 'N/A' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div id="advanceInfo" class="alert alert-info">
                                <strong>Available Advance:</strong> <span id="availableAdvanceText">Select supplier to see balance</span>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="refund_amount">Refund Amount <span class="text-danger">*</span></label>
                                    <input type="number" id="refund_amount" class="form-control" name="refund_amount"
                                        step="0.01" min="0.01" required>
                                    <small class="form-text text-muted">Amount supplier is returning to you.</small>
                                </div>
                                <div class="col-md-6">
                                    <label for="refund_date">Refund Date <span class="text-danger">*</span></label>
                                    <input type="date" id="refund_date" class="form-control" name="refund_date"
                                        value="{{ now()->toDateString() }}" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="payment_mode">Receive Into <span class="text-danger">*</span></label>
                                    <select id="payment_mode" class="form-control" name="payment_mode" required>
                                        <option value="">-- Select Payment Mode --</option>
                                        @foreach ($paymentTypes as $paymentType)
                                            <option value="{{ $paymentType->id }}">{{ $paymentType->payment_type }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label>Target Account</label>
                                    <div class="form-control" style="background-color: #f8f9fa;height: unset;">
                                        <span id="accountBalanceText">Select payment mode to see target account</span>
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" id="account_id" name="account_id" value="">

                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="payment_note">Note</label>
                                    <textarea id="payment_note" class="form-control" name="payment_note" rows="3"
                                        placeholder="Optional refund notes"></textarea>
                                </div>
                            </div>

                            <div id="refundNote" class="alert alert-warning">
                                <i class="fas fa-info-circle"></i>
                                <strong>Note:</strong> <span id="refundNoteText">Select supplier and enter refund amount.</span>
                            </div>

                            <div class="row mt-4">
                                <div class="col-md-12 text-right">
                                    <button type="submit" id="submitRefundBtn" class="btn btn-success btn-lg" disabled>
                                        <i class="fas fa-check"></i> Confirm Refund
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('footer_js')
    <script src="{{ url('assets') }}/plugins/select2/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const supplierAdvanceUrlTemplate = @json(route('GetSupplierAdvanceBalance', ['supplier_id' => '__SUPPLIER_ID__']));
        const accountBalanceUrlTemplate = @json(route('GetSupplierPaymentAccountBalance', ['payment_type_id' => '__PAYMENT_TYPE_ID__']));

        $(document).ready(function() {
            $('.select2').select2();

            let availableAdvance = 0;
            let accountId = '';

            function formatAmount(value) {
                return (parseFloat(value) || 0).toFixed(2);
            }

            function validateRefund() {
                const amount = parseFloat($('#refund_amount').val()) || 0;
                const supplierId = $('#supplier_id').val();

                if (!supplierId) {
                    $('#refundNoteText').html('Please select supplier first.');
                    $('#submitRefundBtn').prop('disabled', true);
                    return false;
                }

                if (availableAdvance <= 0) {
                    $('#refundNoteText').html('<span class="text-danger">This supplier has no refundable advance.</span>');
                    $('#submitRefundBtn').prop('disabled', true);
                    return false;
                }

                if (amount <= 0) {
                    $('#refundNoteText').html('Enter refund amount.');
                    $('#submitRefundBtn').prop('disabled', true);
                    return false;
                }

                if (amount > availableAdvance) {
                    $('#refundNoteText').html('<span class="text-danger">Refund amount exceeds available advance. Available: ৳' + formatAmount(availableAdvance) + '</span>');
                    $('#submitRefundBtn').prop('disabled', true);
                    return false;
                }

                if (!accountId || !$('#payment_mode').val()) {
                    $('#refundNoteText').html('Select the payment mode where money will be received.');
                    $('#submitRefundBtn').prop('disabled', true);
                    return false;
                }

                $('#refundNoteText').html('৳' + formatAmount(amount) + ' will be received and supplier advance will be reduced.');
                $('#submitRefundBtn').prop('disabled', false);
                return true;
            }

            async function loadSupplierAdvance() {
                const supplierId = $('#supplier_id').val();
                availableAdvance = 0;
                $('#refund_amount').val('');

                if (!supplierId) {
                    $('#availableAdvanceText').text('Select supplier to see balance');
                    validateRefund();
                    return;
                }

                try {
                    const response = await $.get(supplierAdvanceUrlTemplate.replace('__SUPPLIER_ID__', supplierId));
                    if (response.success) {
                        availableAdvance = parseFloat(response.available_advance) || 0;
                        $('#availableAdvanceText').html('৳' + formatAmount(availableAdvance));
                    } else {
                        $('#availableAdvanceText').html('<span class="text-danger">' + response.message + '</span>');
                    }
                } catch (error) {
                    $('#availableAdvanceText').html('<span class="text-danger">Failed to load supplier advance.</span>');
                }

                validateRefund();
            }

            async function loadAccountBalance() {
                const paymentTypeId = $('#payment_mode').val();
                accountId = '';
                $('#account_id').val('');

                if (!paymentTypeId) {
                    $('#accountBalanceText').text('Select payment mode to see target account');
                    validateRefund();
                    return;
                }

                try {
                    const response = await $.get(accountBalanceUrlTemplate.replace('__PAYMENT_TYPE_ID__', paymentTypeId));
                    if (response.success) {
                        accountId = response.account_id;
                        $('#account_id').val(response.account_id);
                        $('#accountBalanceText').html(
                            '<strong>Account:</strong> ' + response.account_name +
                            '<br><strong>Current Balance:</strong> ৳' + formatAmount(response.balance)
                        );
                    } else {
                        $('#accountBalanceText').html('<span class="text-danger">' + response.message + '</span>');
                    }
                } catch (error) {
                    $('#accountBalanceText').html('<span class="text-danger">Failed to load target account.</span>');
                }

                validateRefund();
            }

            $('#supplier_id').on('change select2:select', loadSupplierAdvance);
            $('#payment_mode').on('change', loadAccountBalance);
            $('#refund_amount').on('input', validateRefund);

            if ($('#supplier_id').val()) {
                loadSupplierAdvance();
            }

            $('#refundForm').on('submit', function(e) {
                e.preventDefault();

                if (!validateRefund()) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: 'Please check supplier advance, refund amount, and target account.'
                    });
                    return;
                }

                const amount = formatAmount($('#refund_amount').val());
                Swal.fire({
                    title: 'Confirm advance refund?',
                    text: 'Receive ৳' + amount + ' from supplier and reduce supplier advance.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Confirm Refund'
                }).then((result) => {
                    if (!result.isConfirmed) {
                        return;
                    }

                    $.ajax({
                        url: $(this).attr('action'),
                        method: 'POST',
                        data: $(this).serialize(),
                        success: function(response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.message
                            }).then(() => {
                                window.location.href = response.redirect;
                            });
                        },
                        error: function(xhr) {
                            if (xhr.responseJSON && xhr.responseJSON.errors) {
                                let errorMsg = '';
                                $.each(xhr.responseJSON.errors, function(key, value) {
                                    errorMsg += value[0] + '\n';
                                });
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Validation Error',
                                    text: errorMsg
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: xhr.responseJSON?.message || 'Something went wrong!'
                                });
                            }
                        }
                    });
                });
            });
        });
    </script>
@endsection
