@extends('backend.master')

@section('header_css')
    <style>
        .info-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            background: #fff;
        }
        .info-card h5 {
            color: #495057;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .info-row {
            display: flex;
            padding: 10px 0;
            border-bottom: 1px dotted #dee2e6;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #6c757d;
            width: 200px;
            flex-shrink: 0;
        }
        .info-value {
            color: #212529;
            flex: 1;
        }
    </style>
@endsection

@section('page_title')
    Expense Details
@endsection

@section('page_heading')
    Expense Details
@endsection

@section('content')
    @php
        $paymentTypeAccounts = $payment_types->mapWithKeys(function ($paymentType) {
            $accountId = $paymentType->credit_account_id ?: $paymentType->debit_account_id;
            if (!$accountId) {
                $accountId = optional(\App\Http\Controllers\Account\Models\AcAccount::where('paymenttypes_id', $paymentType->id)->where('status', 'active')->first())->id;
            }
            $account = $accountId ? \App\Http\Controllers\Account\Models\AcAccount::find($accountId) : null;

            return [$paymentType->id => [
                'account_id' => $accountId,
                'account_name' => $account->account_name ?? 'Source account not configured',
            ]];
        });
        $expenseCreditAccountId = $expense->expense_category->credit_id ?? $expense->credit_account_id;
        $defaultPaymentType = $expenseCreditAccountId
            ? $payment_types->first(function ($paymentType) use ($expenseCreditAccountId) {
                $accountId = $paymentType->credit_account_id ?: $paymentType->debit_account_id;
                if (!$accountId) {
                    $accountId = optional(\App\Http\Controllers\Account\Models\AcAccount::where('paymenttypes_id', $paymentType->id)->where('status', 'active')->first())->id;
                }

                return (int) $accountId === (int) $expenseCreditAccountId;
            })
            : null;
    @endphp
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title mb-1">Expense Details</h4>
                        <div>
                            <a href="{{ route('ViewAllExpense') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to List
                            </a>
                            <a href="{{ route('PrintExpense', $expense->id) }}" target="_blank" class="btn btn-primary">
                                <i class="fas fa-print"></i> Print Voucher
                            </a>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-card">
                                <h5><i class="fas fa-info-circle"></i> Basic Information</h5>
                                <div class="info-row">
                                    <span class="info-label">Expense Code:</span>
                                    <span class="info-value"><strong>{{ $expense->expense_code }}</strong></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Expense For:</span>
                                    <span class="info-value">{{ $expense->expense_for }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Expense Date:</span>
                                    <span class="info-value">{{ \Carbon\Carbon::parse($expense->expense_date)->format('d M Y') }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Amount:</span>
                                    <span class="info-value"><strong style="color: #dc3545; font-size: 18px;">৳ {{ number_format($expense->final_amount ?: $expense->expense_amt, 2) }}</strong></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Paid Amount:</span>
                                    <span class="info-value">৳ {{ number_format((float) ($expense->paid_amount ?? 0), 2) }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Due Amount:</span>
                                    <span class="info-value"><strong>৳ {{ number_format((float) ($expense->due_amount ?? 0), 2) }}</strong></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Payment Status:</span>
                                    <span class="info-value">
                                        @php
                                            $paymentStatus = $expense->payment_status ?? 'due';
                                            $paymentBadge = ['paid' => 'success', 'partial' => 'warning', 'due' => 'danger', 'cancelled' => 'secondary'][$paymentStatus] ?? 'secondary';
                                        @endphp
                                        <span class="badge badge-{{ $paymentBadge }}">{{ ucfirst($paymentStatus) }}</span>
                                    </span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Reference No:</span>
                                    <span class="info-value">{{ $expense->reference_no ?: 'N/A' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="info-card">
                                <h5><i class="fas fa-building"></i> Account Information</h5>
                                <div class="info-row">
                                    <span class="info-label">Expense Category:</span>
                                    <span class="info-value">{{ $expense->expense_category ? $expense->expense_category->category_name : 'N/A' }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">From Account:</span>
                                    <span class="info-value">
                                        @if($expense->expense_category && $expense->expense_category->creditAccount)
                                            {{ $expense->expense_category->creditAccount->account_name }}
                                        @else
                                            N/A
                                        @endif
                                    </span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">To Account:</span>
                                    <span class="info-value">
                                        @if($expense->expense_category && $expense->expense_category->debitAccount)
                                            {{ $expense->expense_category->debitAccount->account_name }}
                                        @else
                                            N/A
                                        @endif
                                    </span>
                                </div>
                                {{-- <div class="info-row">
                                    <span class="info-label">Payment Type:</span>
                                    <span class="info-value">{{ $expense->payment_type ? $expense->payment_type->payment_type : 'N/A' }}</span>
                                </div> --}}
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="info-card">
                                <h5><i class="fas fa-file-alt"></i> Additional Information</h5>
                                <div class="info-row">
                                    <span class="info-label">Note:</span>
                                    <span class="info-value">{{ $expense->note ?: 'N/A' }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Created By:</span>
                                    <span class="info-value">{{ $expense->user ? $expense->user->name : 'N/A' }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Created At:</span>
                                    <span class="info-value">{{ \Carbon\Carbon::parse($expense->created_at)->format('d M Y, h:i A') }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Status:</span>
                                    <span class="info-value">
                                        @if($expense->status == 'active')
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-danger">Inactive</span>
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="info-card">
                                <h5><i class="fas fa-history"></i> Payment History</h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Payment Date</th>
                                                <th>Amount</th>
                                                <th>Method</th>
                                                <th>Account</th>
                                                <th>Note</th>
                                                <th>Created By</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($expense->payments as $payment)
                                                <tr>
                                                    <td>{{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d M Y, h:i A') : 'N/A' }}</td>
                                                    <td>৳ {{ number_format((float) $payment->payment_amount, 2) }}</td>
                                                    <td>{{ $payment->payment_type ? $payment->payment_type->payment_type : 'N/A' }}</td>
                                                    <td>{{ $payment->account ? $payment->account->account_name : 'N/A' }}</td>
                                                    <td>{{ $payment->payment_note ?: 'N/A' }}</td>
                                                    <td>{{ $payment->user ? $payment->user->name : ($payment->created_by ?: 'N/A') }}</td>
                                                    <td><span class="badge badge-{{ $payment->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($payment->status) }}</span></td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="7" class="text-center text-muted">No payment yet.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if(in_array($expense->payment_status, ['due', 'partial', null], true) && (float) ($expense->due_amount ?? 0) > 0)
                        <div class="row" id="add-payment">
                            <div class="col-md-12">
                                <div class="info-card" id="expense-payment-form">
                                    <h5><i class="fas fa-money-bill"></i> Add Payment / Resolve Expense</h5>
                                    <form @submit.prevent="submitPayment">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>Payment Amount *</label>
                                                    <input type="number" step="0.01" min="0.01" class="form-control" v-model="form.payment_amount">
                                                    <small class="text-danger" v-if="errors.payment_amount">@{{ errors.payment_amount[0] }}</small>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>Payment Date *</label>
                                                    <input type="datetime-local" class="form-control" v-model="form.payment_date">
                                                    <small class="text-danger" v-if="errors.payment_date">@{{ errors.payment_date[0] }}</small>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>Payment Method *</label>
                                                    <select class="form-control" v-model="form.payment_type_id">
                                                        <option value="">Select Method</option>
                                                        @foreach($payment_types as $paymentType)
                                                            <option value="{{ $paymentType->id }}">{{ $paymentType->payment_type }}</option>
                                                        @endforeach
                                                    </select>
                                                    <small class="text-danger" v-if="errors.payment_type_id">@{{ errors.payment_type_id[0] }}</small>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>Payment Account *</label>
                                                    <select class="form-control" v-model="form.account_id">
                                                        <option value="">Select Account</option>
                                                        @foreach($accounts as $account)
                                                            <option value="{{ $account->id }}">{{ $account->account_name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <small class="text-danger" v-if="errors.account_id">@{{ errors.account_id[0] }}</small>
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label>Payment Note</label>
                                                    <textarea class="form-control" rows="2" v-model="form.payment_note"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-success" :disabled="loading">
                                            <span v-if="loading">Saving...</span>
                                            <span v-else>Save Payment</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('footer_js')
    <script src="{{ versioned_asset('assets/js/vue.min.js') }}"></script>
    <script>
        (function () {
            if (!document.getElementById('expense-payment-form') || typeof Vue === 'undefined') return;

            var csrf = document.querySelector('meta[name="csrf-token"]');
            if (csrf && window.axios) {
                axios.defaults.headers.common['X-CSRF-TOKEN'] = csrf.getAttribute('content');
            }

            new Vue({
                el: '#expense-payment-form',
                data: {
                    loading: false,
                    errors: {},
                    submitUrl: @json(route('expenses.payments.store', $expense->id)),
                    dueAmount: @json((float) ($expense->due_amount ?? 0)),
                    paymentTypeAccounts: @json($paymentTypeAccounts),
                    form: {
                        payment_amount: '',
                        payment_date: @json(now('Asia/Dhaka')->format('Y-m-d\TH:i')),
                        payment_type_id: @json($defaultPaymentType->id ?? ''),
                        account_id: @json($expenseCreditAccountId ?? ''),
                        payment_note: ''
                    }
                },
                watch: {
                    'form.payment_type_id': function () {
                        this.syncPaymentAccount(true);
                    }
                },
                mounted: function () {
                    this.syncPaymentAccount(false);
                },
                methods: {
                    syncPaymentAccount: function (force) {
                        if (!force && this.form.account_id) {
                            return;
                        }

                        var account = this.paymentTypeAccounts[this.form.payment_type_id];
                        this.form.account_id = account ? account.account_id : '';
                    },
                    submitPayment: function () {
                        var vm = this;
                        vm.loading = true;
                        vm.errors = {};
                        vm.syncPaymentAccount();

                        axios.post(vm.submitUrl, vm.form)
                            .then(function (response) {
                                vm.loading = false;
                                if (response.data && response.data.success) {
                                    toastr.success(response.data.message || 'Payment saved successfully.');
                                    window.location.reload();
                                } else {
                                    toastr.error((response.data && response.data.message) || 'Something went wrong.');
                                }
                            })
                            .catch(function (error) {
                                vm.loading = false;
                                if (error.response && error.response.status === 422) {
                                    vm.errors = error.response.data.errors || {};
                                    toastr.error(error.response.data.message || 'Validation failed.');
                                } else {
                                    toastr.error('Something went wrong. Please try again.');
                                }
                            });
                    }
                }
            });
        })();
    </script>
@endsection
