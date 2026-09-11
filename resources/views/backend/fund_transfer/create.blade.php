@extends('backend.master')

@section('header_css')
    <link href="{{ url('assets') }}/plugins/select2/select2.min.css" rel="stylesheet" type="text/css" />
    <style>
        .transfer-card { border: 1px solid #e5e9f2; border-radius: 6px; padding: 18px; height: 100%; }
        .transfer-card.source { background: #f7fbff; border-color: #cfe2ff; }
        .transfer-card.destination { background: #f6fff9; border-color: #cdeed8; }
        .balance-row { display: flex; justify-content: space-between; padding: 7px 0; border-bottom: 1px solid #edf1f7; }
        .balance-row:last-child { border-bottom: 0; font-size: 20px; font-weight: 700; }
        .select2 { width: 100% !important; }
    </style>
@endsection

@section('page_title')
    Create Fund Transfer
@endsection
@section('page_heading')
    Create Fund Transfer
@endsection

@section('content')
    <form method="POST" action="{{ route('StoreFundTransfer') }}" enctype="multipart/form-data">
        @csrf
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="card-title mb-1"><i class="fas fa-exchange-alt text-primary"></i> Create Fund Transfer</h4>
                        <small class="text-muted">Transfer money from one payment account to another account.</small>
                    </div>
                    <a href="{{ route('ViewAllFundTransfer') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i></a>
                </div>

                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Transfer Date <span class="text-danger">*</span></label>
                            <input type="date" id="transfer_date" name="transfer_date" value="{{ old('transfer_date', date('Y-m-d')) }}" class="form-control">
                            @error('transfer_date') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Transfer No <span class="text-danger">*</span></label>
                            <input type="text" name="transfer_code" value="{{ old('transfer_code', $transferNo) }}" class="form-control" readonly>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Reference No</label>
                            <input type="text" name="reference_no" value="{{ old('reference_no') }}" class="form-control" placeholder="Bank slip / ref no">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Transfer Type</label>
                            <select name="transfer_type_id" class="form-control select2">
                                <option value="">Select Transfer Type</option>
                                @foreach($transferTypes as $type)
                                    <option value="{{ $type->id }}" {{ old('transfer_type_id') == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-5">
                        <div class="transfer-card source">
                            <h5 class="text-primary mb-3"><i class="fas fa-wallet"></i> From Account (Source)</h5>
                            <div class="form-group">
                                <label>Select From Account <span class="text-danger">*</span></label>
                                <select id="from_payment_type_id" name="from_payment_type_id" class="form-control select2">
                                    <option value="">Select From Account</option>
                                    @foreach($paymentTypes as $paymentType)
                                        <option value="{{ $paymentType->id }}" {{ (string) old('from_payment_type_id', $selectedFrom) === (string) $paymentType->id ? 'selected' : '' }}>{{ $paymentType->payment_type }}</option>
                                    @endforeach
                                </select>
                                @error('from_payment_type_id') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="balance-row"><span>Current Balance</span><strong id="from_current">0.00</strong></div>
                            <div class="balance-row"><span>Closing Balance on Date</span><strong id="from_closing">0.00</strong></div>
                            <div class="balance-row"><span>Amount to Transfer</span><strong class="text-danger transfer_amount_text">0.00</strong></div>
                            <div class="balance-row"><span>Balance After Transfer</span><strong id="from_after" class="text-primary">0.00</strong></div>
                        </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-center justify-content-center">
                        <div class="btn btn-light rounded-circle"><i class="fas fa-arrow-right text-primary"></i></div>
                    </div>
                    <div class="col-md-5">
                        <div class="transfer-card destination">
                            <h5 class="text-success mb-3"><i class="fas fa-university"></i> To Account (Destination)</h5>
                            <div class="form-group">
                                <label>Select To Account <span class="text-danger">*</span></label>
                                <select id="to_payment_type_id" name="to_payment_type_id" class="form-control select2">
                                    <option value="">Select To Account</option>
                                    @foreach($paymentTypes as $paymentType)
                                        <option value="{{ $paymentType->id }}" {{ old('to_payment_type_id') == $paymentType->id ? 'selected' : '' }}>{{ $paymentType->payment_type }}</option>
                                    @endforeach
                                </select>
                                @error('to_payment_type_id') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="balance-row"><span>Current Balance</span><strong id="to_current">0.00</strong></div>
                            <div class="balance-row"><span>Closing Balance on Date</span><strong id="to_closing">0.00</strong></div>
                            <div class="balance-row"><span>Amount to Receive</span><strong class="text-success transfer_amount_text">0.00</strong></div>
                            <div class="balance-row"><span>Balance After Transfer</span><strong id="to_after" class="text-success">0.00</strong></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <h5 class="mb-3"><i class="fas fa-clipboard-list text-info"></i> Transfer Details</h5>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Transfer Amount <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text">BDT</span></div>
                                <input type="number" step="0.01" min="0.01" id="amount" name="amount" value="{{ old('amount') }}" class="form-control text-right">
                            </div>
                            @error('amount') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="form-group">
                            <label>Transfer Method</label>
                            <input type="text" name="transfer_method" value="{{ old('transfer_method') }}" class="form-control" placeholder="Cash Deposit / Online Transfer">
                        </div>
                        <div class="form-group">
                            <label>Note / Description</label>
                            <textarea name="note" class="form-control" rows="3">{{ old('note') }}</textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Attachment</label>
                            <input type="file" name="attachment" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Transfer Charge</label>
                            <input type="number" step="0.01" min="0" id="transfer_charge" name="transfer_charge" value="{{ old('transfer_charge', 0) }}" class="form-control text-right">
                        </div>
                        <div class="form-group">
                            <label>Charge Account</label>
                            <select name="charge_account_id" class="form-control select2">
                                <option value="">Select Charge Account</option>
                                @foreach($accounts as $account)
                                    <option value="{{ $account->id }}" {{ old('charge_account_id') == $account->id ? 'selected' : '' }}>{{ $account->account_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <h5 class="mb-3"><i class="fas fa-check-circle text-warning"></i> Additional Information</h5>
                <div class="row">
                    <div class="col-md-6">
                        <label>Approved By</label>
                        <select name="approved_by" class="form-control select2">
                            <option value="">Select Approver</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ old('approved_by') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label>Status</label>
                        <select name="approval_status" class="form-control">
                            <option value="approved" {{ old('approval_status', 'approved') === 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="pending" {{ old('approval_status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="alert alert-warning">
            <i class="fas fa-info-circle"></i> After saving, accounting entries will be created as: Debit To Account and Credit From Account.
        </div>

        <div class="text-right mb-4">
            <a href="{{ route('ViewAllFundTransfer') }}" class="btn btn-light">Cancel</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Transfer</button>
            <button type="submit" name="save_print" value="1" class="btn btn-success"><i class="fas fa-print"></i> Save & Print Receipt</button>
        </div>
    </form>
@endsection

@section('footer_js')
    <script src="{{ url('assets') }}/plugins/select2/select2.min.js"></script>
    <script>
        var balances = {from: {current: 0, closing: 0}, to: {current: 0, closing: 0}};

        function money(value) {
            return Number(value || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }

        function refreshCards() {
            var amount = parseFloat($('#amount').val()) || 0;
            var charge = parseFloat($('#transfer_charge').val()) || 0;
            $('.transfer_amount_text').text(money(amount));
            $('#from_current').text(money(balances.from.current));
            $('#from_closing').text(money(balances.from.closing));
            $('#from_after').text(money(balances.from.closing - amount - charge));
            $('#to_current').text(money(balances.to.current));
            $('#to_closing').text(money(balances.to.closing));
            $('#to_after').text(money(balances.to.closing + amount));
        }

        function loadBalance(side) {
            var selector = side === 'from' ? '#from_payment_type_id' : '#to_payment_type_id';
            var paymentTypeId = $(selector).val();
            if (!paymentTypeId) {
                balances[side] = {current: 0, closing: 0};
                refreshCards();
                return;
            }

            $.get("{{ route('GetFundTransferPaymentTypeBalance') }}", {
                payment_type_id: paymentTypeId,
                date: $('#transfer_date').val()
            }, function(response) {
                balances[side] = {
                    current: parseFloat(response.balance || 0),
                    closing: parseFloat(response.closing_balance || 0)
                };
                refreshCards();
            });
        }

        $(document).ready(function() {
            $('.select2').select2({width: '100%', allowClear: true});
            $('#from_payment_type_id, #transfer_date').on('change', function() { loadBalance('from'); });
            $('#to_payment_type_id, #transfer_date').on('change', function() { loadBalance('to'); });
            $('#amount, #transfer_charge').on('input', refreshCards);
            loadBalance('from');
            loadBalance('to');
        });
    </script>
@endsection
