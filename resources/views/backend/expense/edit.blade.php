@extends('backend.master')

@section('header_css')
    <link href="{{ url('assets') }}/plugins/select2/select2.min.css" rel="stylesheet" type="text/css" />
@endsection

@section('page_title')
    Edit Expense
@endsection

@section('page_heading')
    Edit Expense
@endsection

@php
    $categoryMappings = $expense_categories->mapWithKeys(function ($category) use ($payment_types) {
        $debitAccount = $category->debit_id ? \App\Http\Controllers\Account\Models\AcAccount::find($category->debit_id) : null;
        $creditAccount = $category->credit_id ? \App\Http\Controllers\Account\Models\AcAccount::find($category->credit_id) : null;
        $paymentType = $category->credit_id
            ? $payment_types->first(function ($paymentType) use ($category) {
                $accountId = $paymentType->credit_account_id ?: $paymentType->debit_account_id;
                if (!$accountId) {
                    $accountId = optional(\App\Http\Controllers\Account\Models\AcAccount::where('paymenttypes_id', $paymentType->id)->where('status', 'active')->first())->id;
                }

                return (int) $accountId === (int) $category->credit_id;
            })
            : null;

        return [$category->id => [
            'debit_account_id' => $category->debit_id,
            'debit_account_name' => $debitAccount->account_name ?? 'Expense account not configured',
            'credit_account_id' => $category->credit_id,
            'credit_account_name' => $creditAccount->account_name ?? 'Payment account not configured',
            'payment_type_id' => $paymentType->id ?? null,
            'payment_type_name' => $paymentType->payment_type ?? 'Payment method not configured',
        ]];
    });
    $hasPayment = $data->payments()->where('status', 'active')->exists();
@endphp

@section('content')
    <div id="expense-edit-app" class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title mb-0">Update Expense</h4>
                        <a href="{{ route('ViewAllExpense') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                    </div>

                    @if($hasPayment)
                        <div class="alert alert-warning">
                            This expense already has payment history. Amount changes are locked to keep payment history and accounting balanced.
                        </div>
                    @endif

                    <form method="POST" action="{{ route('UpdateExpense') }}">
                        @csrf
                        <input type="hidden" name="expense_id" value="{{ $data->id }}">
                        <input type="hidden" name="payment_type_id" v-bind:value="selectedMapping.payment_type_id || ''">

                        <div class="row">
                            <div class="col-lg-8">
                                <h5 class="mb-3">Expense Information</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Expense Category <span class="text-danger">*</span></label>
                                            <select id="expense_category_id" name="expense_category_id" class="form-control select2" v-model="form.category_id">
                                                <option value="">Select Category</option>
                                                @foreach($expense_categories as $category)
                                                    <option value="{{ $category->id }}">{{ $category->category_name }}</option>
                                                @endforeach
                                            </select>
                                            @error('expense_category_id')<small class="text-danger">{{ $message }}</small>@enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Expense Date <span class="text-danger">*</span></label>
                                            <input type="date" name="expense_date" class="form-control" v-model="form.expense_date">
                                            @error('expense_date')<small class="text-danger">{{ $message }}</small>@enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Expense Reason <span class="text-danger">*</span></label>
                                            <input type="text" name="expense_for" class="form-control" v-model="form.expense_for" placeholder="Expense reason">
                                            @error('expense_for')<small class="text-danger">{{ $message }}</small>@enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Reference No</label>
                                            <input type="text" name="reference_no" class="form-control" v-model="form.reference_no" placeholder="Reference no">
                                            @error('reference_no')<small class="text-danger">{{ $message }}</small>@enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Total Amount <span class="text-danger">*</span></label>
                                            <input type="number" name="expense_amt" min="0" step="0.01" class="form-control" v-model.number="form.expense_amt" v-bind:readonly="hasPayment">
                                            @error('expense_amt')<small class="text-danger">{{ $message }}</small>@enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Status</label>
                                            <select name="status" class="form-control" v-model="form.status">
                                                <option value="active">Active</option>
                                                <option value="inactive">Inactive</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>Expense Note</label>
                                            <textarea name="note" rows="3" class="form-control" v-model="form.note" placeholder="Expense note"></textarea>
                                            @error('note')<small class="text-danger">{{ $message }}</small>@enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <div class="card border">
                                    <div class="card-body">
                                        <h5 class="mb-3">Configured Accounts</h5>
                                        <div class="mb-2">
                                            <span class="text-muted">Expense Account</span>
                                            <div><strong>@{{ selectedMapping.debit_account_name || 'Select category' }}</strong></div>
                                        </div>
                                        <div class="mb-2">
                                            <span class="text-muted">Payment Method</span>
                                            <div><strong>@{{ selectedMapping.payment_type_name || 'Select category' }}</strong></div>
                                        </div>
                                        <div class="mb-2">
                                            <span class="text-muted">Payment Account</span>
                                            <div><strong>@{{ selectedMapping.credit_account_name || 'Select category' }}</strong></div>
                                        </div>
                                        <hr>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Total Amount</span>
                                            <strong>@{{ money(form.expense_amt) }}</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Paid Amount</span>
                                            <strong>@{{ money(paidAmount) }}</strong>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span>Due Amount</span>
                                            <strong class="text-danger">@{{ money(dueAmount) }}</strong>
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-save"></i> Update Expense
                                </button>
                                <a href="{{ route('ViewAllExpense') }}" class="btn btn-light btn-block">Cancel</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('footer_js')
    <script src="{{ url('assets') }}/plugins/select2/select2.min.js"></script>
    <script src="{{ versioned_asset('assets/js/vue.min.js') }}"></script>
    <script>
        (function () {
            var categoryMappings = @json($categoryMappings);

            new Vue({
                el: '#expense-edit-app',
                data: {
                    hasPayment: @json($hasPayment),
                    paidAmount: @json((float) ($data->paid_amount ?? 0)),
                    form: {
                        category_id: @json((string) old('expense_category_id', $data->category_id)),
                        expense_date: @json(old('expense_date', $data->expense_date ?? date('Y-m-d'))),
                        expense_for: @json(old('expense_for', $data->expense_for)),
                        reference_no: @json(old('reference_no', $data->reference_no)),
                        expense_amt: @json((float) old('expense_amt', $data->expense_amt)),
                        note: @json(old('note', $data->note)),
                        status: @json(old('status', $data->status ?? 'active')),
                    }
                },
                computed: {
                    selectedMapping: function () {
                        return categoryMappings[this.form.category_id] || {};
                    },
                    dueAmount: function () {
                        var due = parseFloat(this.form.expense_amt || 0) - parseFloat(this.paidAmount || 0);
                        return due > 0 ? due : 0;
                    }
                },
                mounted: function () {
                    var vm = this;
                    $('.select2').select2({ width: '100%' });
                    $('#expense_category_id').val(vm.form.category_id).trigger('change.select2');
                    $('#expense_category_id').on('change', function () {
                        vm.form.category_id = $(this).val();
                    });
                },
                methods: {
                    money: function (n) {
                        var x = parseFloat(n || 0);
                        return '৳' + x.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                    }
                }
            });
        })();
    </script>
@endsection
