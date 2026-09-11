@extends('backend.master')

@section('header_css')
    <link href="{{ url('assets') }}/plugins/select2/select2.min.css" rel="stylesheet" type="text/css" />
    <style>
        .summary-box { display:none; background:#f8f9fa; border-left:4px solid #17a2b8; padding:14px; border-radius:4px; }
        .summary-box.active { display:block; }
    </style>
@endsection

@section('page_title')
    {{ $cheque->exists ? 'Edit Supplier Cheque Payment' : 'Create Supplier Cheque Payment' }}
@endsection

@section('page_heading')
    {{ $cheque->exists ? 'Edit Supplier Cheque Payment' : 'Create Supplier Cheque Payment' }}
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="card-title mb-0">{{ $cheque->exists ? 'Edit Cheque Payment' : 'Create Cheque Payment' }}</h4>
                <a href="{{ route('supplier-cheque-payments.index') }}" class="btn btn-secondary">Back</a>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" enctype="multipart/form-data"
                  action="{{ $cheque->exists ? route('supplier-cheque-payments.update', $cheque->id) : route('supplier-cheque-payments.store') }}">
                @csrf
                @if ($cheque->exists)
                    @method('PUT')
                @endif

                <h5>Supplier & Purchase</h5>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Supplier <span class="text-danger">*</span></label>
                        <select name="supplier_id" id="supplier_id" class="form-control select2" required>
                            <option value="">-- Select Supplier --</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ old('supplier_id', $cheque->supplier_id) == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->name }} - {{ $supplier->contact_number ?? 'N/A' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Due Purchase / Invoice <span class="text-danger">*</span></label>
                        <select name="purchase_id" id="purchase_id" class="form-control select2" required>
                            @if (old('purchase_id', $cheque->purchase_id) && $cheque->purchase)
                                <option value="{{ $cheque->purchase_id }}" selected>{{ $cheque->purchase->code }}</option>
                            @else
                                <option value="">-- Select supplier first --</option>
                            @endif
                        </select>
                    </div>
                </div>

                <div id="purchase_summary" class="summary-box mb-3">
                    <div class="row">
                        <div class="col-md-3"><strong>Purchase:</strong> <span data-field="purchase_no"></span></div>
                        <div class="col-md-3"><strong>Date:</strong> <span data-field="purchase_date"></span></div>
                        <div class="col-md-2"><strong>Total:</strong> <span data-field="total"></span></div>
                        <div class="col-md-2"><strong>Paid:</strong> <span data-field="paid"></span></div>
                        <div class="col-md-2"><strong>Due:</strong> <span data-field="due"></span></div>
                    </div>
                </div>

                <h5>Payment Information</h5>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label>Payment Amount <span class="text-danger">*</span></label>
                        <input type="number" name="amount" id="amount" value="{{ old('amount', $cheque->amount) }}" step="0.01" min="0.01" class="form-control" required>
                        <small class="text-muted">Amount cannot exceed selected purchase due.</small>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Payment Type <span class="text-danger">*</span></label>
                        <select name="payment_type_id" id="payment_type_id" class="form-control select2" required>
                            <option value="">-- Select Payment Type --</option>
                            @foreach ($paymentTypes as $paymentType)
                                <option value="{{ $paymentType->id }}" {{ old('payment_type_id', $cheque->payment_type_id) == $paymentType->id ? 'selected' : '' }}>
                                    {{ $paymentType->payment_type }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Source Account</label>
                        <div class="form-control" id="source_account_text" style="background:#f8f9fa;">Select payment type</div>
                    </div>
                </div>

                <h5>Cheque Information</h5>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label>Cheque Number <span class="text-danger">*</span></label>
                        <input type="text" name="cheque_number" value="{{ old('cheque_number', $cheque->cheque_number) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Bank Name</label>
                        <input type="text" name="bank_name" value="{{ old('bank_name', $cheque->bank_name) }}" class="form-control">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Branch Name</label>
                        <input type="text" name="branch_name" value="{{ old('branch_name', $cheque->branch_name) }}" class="form-control">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Account Name</label>
                        <input type="text" name="account_name" value="{{ old('account_name', $cheque->account_name) }}" class="form-control">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Account Number</label>
                        <input type="text" name="account_number" value="{{ old('account_number', $cheque->account_number) }}" class="form-control">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Cheque Type</label>
                        <select name="cheque_type" class="form-control">
                            <option value="">-- Select Type --</option>
                            <option value="cash_cheque" {{ old('cheque_type', $cheque->cheque_type) === 'cash_cheque' ? 'selected' : '' }}>Cash Cheque</option>
                            <option value="account_payee" {{ old('cheque_type', $cheque->cheque_type) === 'account_payee' ? 'selected' : '' }}>Account Payee Cheque</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Cheque Issue Date <span class="text-danger">*</span></label>
                        <input type="date" name="issue_date" value="{{ old('issue_date', optional($cheque->issue_date)->format('Y-m-d') ?: now()->toDateString()) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Cheque Execution Date <span class="text-danger">*</span></label>
                        <input type="date" name="execution_date" value="{{ old('execution_date', optional($cheque->execution_date)->format('Y-m-d') ?: now()->toDateString()) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Attachment</label>
                        <input type="file" name="attachment" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                    </div>
                    <div class="col-md-12 mb-3">
                        <label>Note</label>
                        <textarea name="note" rows="3" class="form-control">{{ old('note', $cheque->note) }}</textarea>
                    </div>
                </div>

                <div class="text-right">
                    <button type="submit" class="btn btn-success">
                        {{ $cheque->exists ? 'Update Cheque Payment' : 'Save Cheque Payment' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('footer_js')
    <script src="{{ url('assets') }}/plugins/select2/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        $(function () {
            var paymentTypeAccounts = @json($paymentTypeAccounts);
            var selectedPurchaseId = @json(old('purchase_id', $cheque->purchase_id));
            var currentDueAmount = 0;

            $('.select2').select2({ width: '100%' });

            function updateSourceAccount() {
                var paymentTypeId = $('#payment_type_id').val();
                var item = paymentTypeAccounts[paymentTypeId] || null;
                $('#source_account_text').text(item ? item.account_name : 'Source account not found');
            }

            function loadDuePurchases() {
                var supplierId = $('#supplier_id').val();
                var purchaseSelect = $('#purchase_id');

                purchaseSelect.empty().append(new Option('Loading...', '', false, false)).trigger('change.select2');
                $('#purchase_summary').removeClass('active');

                if (!supplierId) {
                    purchaseSelect.empty().append(new Option('-- Select supplier first --', '', false, false)).trigger('change.select2');
                    return;
                }

                axios.get('{{ url('/supplier-cheque-payments/supplier') }}/' + supplierId + '/due-purchases')
                    .then(function (response) {
                        purchaseSelect.empty().append(new Option('-- Select Due Purchase --', '', false, false));
                        response.data.forEach(function (item) {
                            var option = new Option(item.text, item.id, false, String(item.id) === String(selectedPurchaseId));
                            $(option).attr('data-due', item.due_amount);
                            purchaseSelect.append(option);
                        });
                        purchaseSelect.trigger('change.select2');

                        if (selectedPurchaseId) {
                            loadPurchaseSummary(selectedPurchaseId);
                        }
                    });
            }

            function loadPurchaseSummary(purchaseId) {
                if (!purchaseId) {
                    $('#purchase_summary').removeClass('active');
                    return;
                }

                axios.get('{{ url('/supplier-cheque-payments/purchase') }}/' + purchaseId + '/summary')
                    .then(function (response) {
                        if (!response.data.success) {
                            return;
                        }

                        currentDueAmount = parseFloat(response.data.due_amount || 0);
                        $('#purchase_summary [data-field="purchase_no"]').text(response.data.purchase_no || 'N/A');
                        $('#purchase_summary [data-field="purchase_date"]').text(response.data.purchase_date || 'N/A');
                        $('#purchase_summary [data-field="total"]').text(response.data.formatted_total_amount);
                        $('#purchase_summary [data-field="paid"]').text(response.data.formatted_paid_amount);
                        $('#purchase_summary [data-field="due"]').text(response.data.formatted_due_amount);
                        $('#purchase_summary').addClass('active');

                        if (!$('#amount').val()) {
                            $('#amount').val(currentDueAmount.toFixed(2));
                        }
                    });
            }

            $('#supplier_id').on('change', function () {
                selectedPurchaseId = null;
                $('#amount').val('');
                loadDuePurchases();
            });

            $('#purchase_id').on('change', function () {
                selectedPurchaseId = $(this).val();
                $('#amount').val('');
                loadPurchaseSummary(selectedPurchaseId);
            });

            $('#payment_type_id').on('change', updateSourceAccount);
            $('#amount').on('input', function () {
                var amount = parseFloat($(this).val() || 0);
                if (currentDueAmount > 0 && amount > currentDueAmount) {
                    alert('Payment amount cannot exceed due amount.');
                    $(this).val(currentDueAmount.toFixed(2));
                }
            });

            updateSourceAccount();
            if ($('#supplier_id').val()) {
                loadDuePurchases();
            }
        });
    </script>
@endsection
