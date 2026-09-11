@extends('backend.master')

@section('header_css')
    <link href="{{ url('assets') }}/plugins/select2/select2.min.css" rel="stylesheet" type="text/css" />
    @if(request('pos_modal'))
    <style>
        .vertical-menu,
        #page-topbar,
        .page-title-box,
        footer, .btn-secondary {
            display: none !important;
        }

        .main-content,
        .page-content {
            margin-left: 0 !important;
            padding: 0 !important;
        }

        body {
            background: #fff;
        }
    </style>
    @endif
    <style>
        .order-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
            border-left: 4px solid #ffc107;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .order-card:hover {
            background: #e9ecef;
            border-left-color: #17a2b8;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .order-card.selected {
            background: #d1ecf1;
            border-left-color: #0c5460;
            border-left-width: 6px;
        }
        .due-orders-section {
            margin-top: 20px;
            display: none;
        }
        .payment-allocation-row {
            background: #fff;
        }
        .payment-allocation-row.full-payment {
            background: #d4edda;
        }
        .payment-allocation-row.partial-payment {
            background: #fff3cd;
        }
    </style>
@endsection

@section('page_title')
    {{ $dueOnly ?? false ? 'Customer Due Payment' : 'Customer Payment' }}
@endsection

@section('page_heading')
    {{ $dueOnly ?? false ? 'Collect Customer Due' : 'Create Customer Payment / Advance' }}
@endsection

@section('content')
    <div class="container" style="max-width: 900px;">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4>{{ $dueOnly ?? false ? 'Collect Due Payment' : 'Create Payment / Advance' }}</h4>
                            <a href="{{ route('ViewAllCustomerPayments') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                        </div>

                        <form id="paymentForm" action="{{ route('StoreCustomerPayment') }}" method="POST">
                            @csrf
                            
                            <!-- Customer Selection -->
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="customer_id">Select Customer <span class="text-danger">*</span></label>
                                    <select id="customer_id" class="form-control select2" name="customer_id" required>
                                        <option value="">-- Select Customer --</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}" {{ ($selectedCustomerId ?? null) == $customer->id ? 'selected' : '' }}>{{ $customer->name }} - {{ $customer->phone }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- Customer Info Display -->
                            <div id="customerInfo" class="alert alert-info" style="display: none;">
                                <strong>Available Advance:</strong> <span id="availableAdvance">৳0.00</span><br>
                                <strong>Total Due:</strong> <span id="totalDue">৳0.00</span>
                            </div>

                            <!-- Due Orders Section -->
                            <div class="due-orders-section" id="dueOrdersSection">
                                <h5>Customer Dues <small class="text-muted">(Old dues first, then order dues)</small></h5>
                                <div id="dueOrdersList"></div>
                            </div>

                            <!-- Payment Allocation Table -->
                            <div id="paymentAllocationSection" style="display: none;" class="mb-3">
                                <h5>Payment Allocation</h5>
                                <table class="table table-bordered table-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Source</th>
                                            <th>Reference</th>
                                            <th>Due Amount</th>
                                            <th>Paying Now</th>
                                            <th>Remaining</th>
                                        </tr>
                                    </thead>
                                    <tbody id="paymentAllocationBody"></tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <th colspan="3" class="text-right">Total Payment:</th>
                                            <th colspan="2" id="totalPaymentAllocated">৳0.00</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <!-- Hidden field to track payment allocations -->
                            <input type="hidden" id="payment_allocations" name="payment_allocations" value="">

                            <!-- Payment Details -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="payment_amount">Cash/Bank Payment Amount</label>
                                    <input type="number" id="payment_amount" class="form-control" name="payment_amount" step="0.01" min="0">
                                    <small class="form-text text-muted">Cash/bank amount. Extra amount becomes advance.</small>
                                </div>
                                <div class="col-md-6">
                                    <label for="payment_date">Payment Date <span class="text-danger">*</span></label>
                                    <input type="date" id="payment_date" class="form-control" name="payment_date" value="{{ now()->toDateString() }}" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="advance_amount">Use Available Advance</label>
                                    <input type="number" id="advance_amount" class="form-control" name="advance_amount" step="0.01" min="0" value="0">
                                    <small class="form-text text-muted">Advance can only be applied to old/order due.</small>
                                </div>
                                <div class="col-md-6">
                                    <label for="payment_mode">Payment Mode</label>
                                    <select id="payment_mode" class="form-control" name="payment_mode">
                                        <option value="">-- Select when cash/bank amount is used --</option>
                                        @foreach($paymentMethods as $paymentMethod)
                                            <option value="{{ $paymentMethod->id }}">{{ $paymentMethod->payment_type }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="payment_note">Note</label>
                                    <textarea id="payment_note" class="form-control" name="payment_note" rows="3" placeholder="Optional payment notes"></textarea>
                                </div>
                            </div>

                            <div id="paymentNote" class="alert alert-warning">
                                <i class="fas fa-info-circle"></i> <strong>Note:</strong> <span id="paymentNoteText">Enter payment amount to see allocation.</span>
                            </div>

                            <div class="row mt-4">
                                <div class="col-md-12 text-right">
                                    <button type="submit" class="btn btn-success btn-lg">
                                        <i class="fas fa-check"></i> Submit Payment
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
        $(document).ready(function() {
            $('.select2').select2();
            
            let dueItems = [];
            let paymentAllocations = [];
            let selectedOrderId = null;
            let availableAdvanceValue = 0;
            let totalDueValue = 0;
            const selectedCustomerId = @json($selectedCustomerId ?? null);

            // Load customer due orders when customer selected
            $('#customer_id').on('change', function() {
                const customerId = $(this).val();
                if (customerId) {
                    $.ajax({
                        url: '{{ url("/api/customer-due-orders") }}/' + customerId,
                        method: 'GET',
                        success: function(response) {
                            if (response.success) {
                                availableAdvanceValue = parseFloat(response.available_advance) || 0;
                                totalDueValue = parseFloat(response.total_due) || 0;

                                $('#availableAdvance').text('৳' + availableAdvanceValue.toFixed(2));
                                $('#totalDue').text('৳' + totalDueValue.toFixed(2));
                                $('#customerInfo').show();

                                dueItems = response.due_items || [];

                                // Show old dues and order dues if any
                                if (dueItems.length > 0) {
                                    let html = '';
                                    dueItems.forEach(function(item, index) {
                                        html += '<div class="order-card" data-order-index="' + index + '" data-order-id="' + item.id + '" onclick="selectOrder(' + index + ')">';
                                        html += '<div class="row">';
                                        html += '<div class="col-md-2"><strong>' + item.label + '</strong></div>';
                                        html += '<div class="col-md-3"><strong>Ref:</strong> ' + item.code + '</div>';
                                        html += '<div class="col-md-3"><strong>Date:</strong> ' + (item.date || '-') + '</div>';
                                        html += '<div class="col-md-2"><strong>Total:</strong> ৳' + parseFloat(item.total).toFixed(2) + '</div>';
                                        html += '<div class="col-md-2"><strong class="text-danger">Due:</strong> ৳' + parseFloat(item.due_amount).toFixed(2) + '</div>';
                                        html += '</div></div>';
                                    });
                                    $('#dueOrdersList').html(html);
                                    $('#dueOrdersSection').show();
                                } else {
                                    $('#dueOrdersSection').hide();
                                }
                                
                                // Reset payment fields
                                resetPaymentFields();
                            }
                        }
                    });
                } else {
                    $('#customerInfo').hide();
                    $('#dueOrdersSection').hide();
                    dueItems = [];
                    availableAdvanceValue = 0;
                    totalDueValue = 0;
                    resetPaymentFields();
                }
            });

            if (selectedCustomerId) {
                $('#customer_id').val(String(selectedCustomerId)).trigger('change');
            }

            // Handle payment amount input change for FIFO allocation
            $('#payment_amount, #advance_amount').on('input', refreshAllocation);

            // Form submission
            $('#paymentForm').on('submit', function(e) {
                e.preventDefault();

                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.message
                            }).then(() => {
                                window.location.href = response.redirect;
                            });
                        }
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

            // Function to select a specific order for payment
            window.selectOrder = function(orderIndex) {
                const item = dueItems[orderIndex];
                selectedOrderId = item.id;
                
                // Highlight selected order
                $('.order-card').removeClass('selected');
                $('.order-card[data-order-index="' + orderIndex + '"]').addClass('selected');
                
                // Set payment amount to order's due amount
                $('#payment_amount').val(parseFloat(item.due_amount).toFixed(2));
                $('#advance_amount').val('0.00');

                // Trigger allocation
                refreshAllocation();
            };

            function refreshAllocation() {
                const cashAmount = parseFloat($('#payment_amount').val()) || 0;
                let advanceAmount = parseFloat($('#advance_amount').val()) || 0;

                if (advanceAmount > availableAdvanceValue) {
                    advanceAmount = availableAdvanceValue;
                    $('#advance_amount').val(advanceAmount.toFixed(2));
                }

                if (advanceAmount > totalDueValue) {
                    advanceAmount = totalDueValue;
                    $('#advance_amount').val(advanceAmount.toFixed(2));
                }

                const totalSettlement = cashAmount + advanceAmount;
                if (totalSettlement > 0 && (dueItems.length > 0 || cashAmount > 0)) {
                    allocatePaymentFIFO(totalSettlement);
                } else {
                    resetPaymentAllocation();
                }
            }

            // FIFO Payment Allocation
            function allocatePaymentFIFO(paymentAmount) {
                paymentAllocations = [];
                let remainingAmount = paymentAmount;
                
                // Allocate to dues in FIFO order: old dues first, then order dues.
                for (let i = 0; i < dueItems.length; i++) {
                    if (remainingAmount <= 0) break;
                    
                    const item = dueItems[i];
                    const itemDue = parseFloat(item.due_amount);
                    const amountForThisItem = Math.min(remainingAmount, itemDue);
                    
                    if (amountForThisItem > 0) {
                        let allocation = {
                            source_type: item.source_type,
                            reference_code: item.code,
                            source_label: item.label,
                            due_amount: itemDue,
                            payment_amount: amountForThisItem,
                            remaining: itemDue - amountForThisItem,
                            is_full_payment: amountForThisItem >= itemDue
                        };
                        if (item.source_type === 'opening_due') {
                            allocation.opening_balance_id = item.opening_balance_id;
                        } else {
                            allocation.order_id = item.order_id;
                        }
                        paymentAllocations.push(allocation);
                        
                        remainingAmount -= amountForThisItem;
                    }
                }
                
                // If there's remaining cash amount, it will be advance
                if (remainingAmount > 0) {
                    paymentAllocations.push({
                        order_id: null,
                        reference_code: 'ADVANCE',
                        source_label: 'Advance',
                        due_amount: 0,
                        payment_amount: remainingAmount,
                        remaining: 0,
                        is_full_payment: false,
                        is_advance: true
                    });
                }
                
                renderPaymentAllocation();
            }

            // Render payment allocation table
            function renderPaymentAllocation() {
                if (paymentAllocations.length === 0) {
                    $('#paymentAllocationSection').hide();
                    return;
                }
                
                let html = '';
                let totalPayment = 0;
                
                paymentAllocations.forEach(function(allocation) {
                    const rowClass = allocation.is_advance ? 'table-info' : 
                                    (allocation.is_full_payment ? 'payment-allocation-row full-payment' : 'payment-allocation-row partial-payment');
                    
                    html += '<tr class="' + rowClass + '">';
                    html += '<td>' + (allocation.is_advance ? '<strong>Advance</strong>' : allocation.source_label) + '</td>';
                    html += '<td>' + (allocation.is_advance ? '<strong>Advance Payment</strong>' : allocation.reference_code) + '</td>';
                    html += '<td>৳' + parseFloat(allocation.due_amount).toFixed(2) + '</td>';
                    html += '<td class="text-success"><strong>৳' + parseFloat(allocation.payment_amount).toFixed(2) + '</strong></td>';
                    html += '<td>' + (allocation.is_advance ? '-' : '৳' + parseFloat(allocation.remaining).toFixed(2)) + '</td>';
                    html += '</tr>';
                    
                    totalPayment += parseFloat(allocation.payment_amount);
                });
                
                $('#paymentAllocationBody').html(html);
                $('#totalPaymentAllocated').text('৳' + totalPayment.toFixed(2));
                $('#paymentAllocationSection').show();
                
                // Store allocations in hidden field
                $('#payment_allocations').val(JSON.stringify(paymentAllocations));
                
                // Update note based on allocation
                const orderPayments = paymentAllocations.filter(a => !a.is_advance);
                const advancePayment = paymentAllocations.find(a => a.is_advance);
                const advanceApplied = parseFloat($('#advance_amount').val()) || 0;

                let noteText = '';
                if (orderPayments.length > 0 && advancePayment) {
                    noteText = 'Payment will be allocated to ' + orderPayments.length + ' due item(s). Excess ৳' + advancePayment.payment_amount.toFixed(2) + ' will be saved as advance.';
                    $('#paymentNote').removeClass('alert-warning').addClass('alert-success');
                } else if (orderPayments.length > 0) {
                    noteText = 'Payment will be allocated to ' + orderPayments.length + ' due item(s).';
                    if (advanceApplied > 0) {
                        noteText += ' ৳' + advanceApplied.toFixed(2) + ' will be applied from available advance.';
                    }
                    $('#paymentNote').removeClass('alert-warning').addClass('alert-success');
                } else if (advancePayment) {
                    noteText = 'This payment will be recorded as an advance payment for the selected customer.';
                    $('#paymentNote').removeClass('alert-success').addClass('alert-warning');
                }
                $('#paymentNoteText').text(noteText);
            }

            // Reset payment allocation
            function resetPaymentAllocation() {
                paymentAllocations = [];
                $('#paymentAllocationSection').hide();
                $('#payment_allocations').val('');
                $('.order-card').removeClass('selected');
                selectedOrderId = null;
            }

            // Reset payment fields
            function resetPaymentFields() {
                $('#payment_amount').val('');
                $('#advance_amount').val('0');
                resetPaymentAllocation();
            }
        });
    </script>
@endsection

