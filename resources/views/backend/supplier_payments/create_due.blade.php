@extends('backend.master')

@section('header_css')
    <link href="{{ url('assets') }}/plugins/select2/select2.min.css" rel="stylesheet" type="text/css" />
    <style>
        .purchase-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
            border-left: 4px solid #ffc107;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .purchase-card:hover {
            background: #e9ecef;
            border-left-color: #17a2b8;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .purchase-card.selected {
            background: #d1ecf1;
            border-left-color: #0c5460;
            border-left-width: 6px;
        }
    </style>
@endsection

@section('page_title')
    Supplier Payment - Pay Due
@endsection

@section('page_heading')
    Pay Supplier Due Amount
@endsection

@section('content')
    <div id="paymentApp" class="container" style="max-width: 900px;">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4>Pay Due Amount</h4>
                            <a href="{{ route('ViewAllSupplierPayments') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                        </div>

                        <form @submit.prevent="submitPayment" id="paymentForm" action="{{ route('StoreSupplierPayment') }}"
                            enctype="multipart/form-data" method="POST">
                            @csrf
                            <input type="hidden" name="payment_type" value="due">

                            <!-- Supplier Selection -->
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="supplier_id">Select Supplier <span class="text-danger">*</span></label>
                                    <select id="supplier_id" class="form-control select2" name="supplier_id"
                                        v-model="selectedSupplierId" @change="loadSupplierDuePurchases" required>
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

                            <!-- Supplier Info Display -->
                            <div v-show="showSupplierInfo" class="alert alert-info">
                                <strong>Available Advance:</strong> <span>৳@{{ formatNumber(availableAdvance) }}</span><br>
                                <strong>Total Due:</strong> <span class="font-weight-bold">৳@{{ formatNumber(totalDue) }}</span>
                            </div>

                            <!-- Due Purchases Section -->
                            <div v-show="duePurchases.length > 0" class="mt-4 mb-3">
                                <h5>Due Items <small class="text-muted">(Old due first, then purchase due)</small></h5>
                                <div v-for="(purchase, index) in duePurchases" :key="purchase.id"
                                    :class="['purchase-card', { 'selected': selectedPurchaseIndex === index }]"
                                    @click="selectPurchase(index)">
                                    <div class="row">
                                        <div class="col-md-3"><strong>Source:</strong> @{{ purchase.label || 'Purchase Due' }} -
                                            @{{ purchase.code }}</div>
                                        <div class="col-md-3"><strong>Date:</strong> @{{ purchase.purchase_date }}</div>
                                        <div class="col-md-3"><strong>Total:</strong> ৳@{{ formatNumber(purchase.total) }}</div>
                                        <div class="col-md-3"><strong class="text-danger">Due:</strong>
                                            ৳@{{ formatNumber(purchase.due_amount) }}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Allocation Table -->
                            <div v-show="paymentAllocations.length > 0" class="mb-3">
                                <h5>Payment Allocation</h5>
                                <table class="table table-bordered table-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Reference</th>
                                            <th>Due Amount</th>
                                            <th>Paying Now</th>
                                            <th>Remaining</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(allocation, index) in paymentAllocations" :key="index"
                                            :class="['payment-allocation-row',
                                                allocation.is_advance ? 'table-info' :
                                                (allocation.is_full_payment ? 'full-payment' : 'partial-payment')
                                            ]">
                                            <td>@{{ allocation.purchase_code }}</td>
                                            <td>৳@{{ formatNumber(allocation.due_amount) }}</td>
                                            <td><strong>৳@{{ formatNumber(allocation.payment_amount) }}</strong></td>
                                            <td>৳@{{ formatNumber(allocation.remaining) }}</td>
                                        </tr>
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <th colspan="2" class="text-right">Total Settlement:</th>
                                            <th colspan="2">৳@{{ formatNumber(totalPaymentAllocated) }}</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <!-- Hidden field to track payment allocations -->
                            <input type="hidden" name="payment_allocations" :value="JSON.stringify(paymentAllocations)">
                            <input type="hidden" name="advance_amount" :value="advanceAmount">

                            <!-- Payment Details -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="payment_amount">Cash/Bank Payment</label>
                                    <input type="number" id="payment_amount" class="form-control" name="payment_amount"
                                        v-model.number="paymentAmount" step="0.01" min="0">
                                    <small class="form-text text-muted">Enter cash/bank amount. Allocation uses old due
                                        first.</small>
                                </div>
                                <div class="col-md-6">
                                    <label for="advance_amount">Use Supplier Advance</label>
                                    <input type="number" id="advance_amount" class="form-control"
                                        v-model.number="advanceAmount" step="0.01" min="0">
                                    <small class="form-text text-muted">Available: ৳@{{ formatNumber(availableAdvance) }}</small>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="payment_date">Payment Date <span class="text-danger">*</span></label>
                                    <input type="date" id="payment_date" class="form-control" name="payment_date"
                                        v-model="paymentDate" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="payment_mode">Payment Mode</label>
                                    <select id="payment_mode" class="form-control" name="payment_mode"
                                        v-model="selectedPaymentModeId" @change="loadAccountBalance"
                                        :required="paymentAmount > 0">
                                        <option value="">-- Select Payment Mode --</option>
                                        @foreach ($paymentTypes as $paymentType)
                                            <option value="{{ $paymentType->id }}">
                                                {{ $paymentType->payment_type }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label>Account Balance</label>
                                    <div class="form-control" style="background-color: #f8f9fa; height: unset;">
                                        <span v-html="accountBalanceText"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Hidden field for account_id -->
                            <input type="hidden" name="account_id" :value="accountId">

                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="payment_note">Note</label>
                                    <textarea id="payment_note" class="form-control" name="payment_note" v-model="paymentNote" rows="3"
                                        placeholder="Optional payment notes"></textarea>
                                </div>
                            </div>

                            <div class="alert alert-warning">
                                <i class="fas fa-info-circle"></i> <strong>Note:</strong> <span
                                    v-html="paymentNoteText"></span>
                            </div>

                            <div class="row mt-4">
                                <div class="col-md-12 text-right">
                                    <button type="submit" class="btn btn-success btn-lg" :disabled="!canSubmitPayment"
                                        v-show="canSubmitPayment">
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vue/2.7.15/vue.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        const supplierDuePurchasesUrlTemplate = @json(route('GetSupplierDuePurchases', ['supplier_id' => '__SUPPLIER_ID__']));
        const accountBalanceUrlTemplate = @json(route('GetSupplierPaymentAccountBalance', ['payment_type_id' => '__PAYMENT_TYPE_ID__']));

        new Vue({
            el: '#paymentApp',
            data: {
                selectedSupplierId: '{{ $selectedSupplier ? $selectedSupplier->id : '' }}',
                duePurchases: [],
                paymentAllocations: [],
                selectedPurchaseIndex: null,
                availableAdvance: 0,
                totalDue: 0,
                showSupplierInfo: false,

                paymentAmount: 0,
                advanceAmount: 0,
                paymentDate: '{{ now()->toDateString() }}',
                selectedPaymentModeId: '',
                accountId: '',
                accountBalance: 0,
                accountName: '',
                accountBalanceText: 'Select payment mode to see balance',

                paymentNote: '',
                paymentNoteText: 'Enter payment amount to see allocation.',

                loading: false
            },
            computed: {
                totalPaymentAllocated() {
                    return this.paymentAllocations.reduce((total, allocation) => {
                        return total + (parseFloat(allocation.payment_amount) || 0);
                    }, 0);
                },
                canSubmitPayment() {
                    const cashAmount = parseFloat(this.paymentAmount) || 0;
                    const advanceAmount = parseFloat(this.advanceAmount) || 0;
                    const settlementAmount = cashAmount + advanceAmount;

                    if (settlementAmount <= 0) {
                        return false;
                    }
                    if (cashAmount > 0 && (!this.accountId || !this.selectedPaymentModeId)) {
                        return false;
                    }
                    if (cashAmount > this.accountBalance) {
                        return false;
                    }
                    if (advanceAmount > this.availableAdvance) {
                        return false;
                    }
                    if (settlementAmount > this.totalDue) {
                        return false;
                    }
                    return true;
                }
            },
            watch: {
                paymentAmount(newVal) {
                    this.recalculateAllocation();
                },
                advanceAmount() {
                    this.recalculateAllocation();
                },
                paymentAllocations() {
                    this.updatePaymentNote();
                }
            },
            mounted() {
                // Initialize Select2 for supplier dropdown
                $('#supplier_id').select2();

                // Sync Select2 with Vue
                $('#supplier_id').on('select2:select', (e) => {
                    this.selectedSupplierId = e.params.data.id;
                    this.loadSupplierDuePurchases();
                });

                // Load supplier data if pre-selected
                if (this.selectedSupplierId) {
                    this.$nextTick(() => {
                        this.loadSupplierDuePurchases();
                    });
                }
            },
            methods: {
                formatNumber(value) {
                    return parseFloat(value || 0).toFixed(2);
                },

                recalculateAllocation() {
                    const settlementAmount = (parseFloat(this.paymentAmount) || 0) + (parseFloat(this
                        .advanceAmount) || 0);
                    if (settlementAmount > 0 && this.duePurchases.length > 0) {
                        this.allocatePaymentFIFO(settlementAmount);
                    } else {
                        this.resetPaymentAllocation();
                    }
                    this.updatePaymentNote();
                },

                async loadSupplierDuePurchases() {
                    if (!this.selectedSupplierId) {
                        this.showSupplierInfo = false;
                        this.duePurchases = [];
                        this.resetPaymentFields();
                        return;
                    }

                    try {
                        this.loading = true;
                        const response = await axios.get(supplierDuePurchasesUrlTemplate.replace(
                            '__SUPPLIER_ID__', this.selectedSupplierId));

                        if (response.data.success) {
                            this.totalDue = parseFloat(response.data.total_due) || 0;
                            this.availableAdvance = parseFloat(response.data.available_advance) || 0;
                            this.duePurchases = response.data.due_purchases || [];
                            this.showSupplierInfo = true;
                            this.resetPaymentFields();
                        }
                    } catch (error) {
                        console.error('Error loading supplier data:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to load supplier data'
                        });
                    } finally {
                        this.loading = false;
                    }
                },

                async loadAccountBalance() {
                    if (!this.selectedPaymentModeId) {
                        this.accountBalanceText = 'Select payment mode to see balance';
                        this.accountId = '';
                        this.accountBalance = 0;
                        this.accountName = '';
                        return;
                    }

                    try {
                        const response = await axios.get(accountBalanceUrlTemplate.replace(
                            '__PAYMENT_TYPE_ID__', this.selectedPaymentModeId));

                        if (response.data.success) {
                            this.accountId = response.data.account_id;
                            this.accountBalance = parseFloat(response.data.balance) || 0;
                            this.accountName = response.data.account_name;
                            const balanceClass = this.accountBalance >= 0 ? 'text-success' : 'text-danger';
                            this.accountBalanceText =
                                `<strong>Account:</strong> ${this.accountName}<br><strong>Balance:</strong> <span class="${balanceClass}">৳${this.formatNumber(this.accountBalance)}</span>`;
                        } else {
                            this.accountBalanceText =
                                `<span class="text-danger">${response.data.message}</span>`;
                            this.accountId = '';
                            this.accountBalance = 0;
                        }
                    } catch (error) {
                        console.error('Error loading account balance:', error);
                        this.accountBalanceText =
                            '<span class="text-danger">Error loading account balance</span>';
                        this.accountId = '';
                        this.accountBalance = 0;
                    }
                },

                selectPurchase(index) {
                    this.selectedPurchaseIndex = index;
                    const purchase = this.duePurchases[index];
                    this.paymentAmount = parseFloat(purchase.due_amount);
                    this.advanceAmount = 0;
                    this.allocatePaymentFIFO(this.paymentAmount);
                },

                allocatePaymentFIFO(paymentAmount) {
                    this.paymentAllocations = [];
                    let remainingAmount = paymentAmount;

                    // Allocate to purchases in FIFO order (first purchase first)
                    for (let i = 0; i < this.duePurchases.length; i++) {
                        if (remainingAmount <= 0) break;

                        const purchase = this.duePurchases[i];
                        const purchaseDue = parseFloat(purchase.due_amount);
                        const amountForThisPurchase = Math.min(remainingAmount, purchaseDue);

                        if (amountForThisPurchase > 0) {
                            this.paymentAllocations.push({
                                purchase_id: purchase.purchase_id || null,
                                opening_balance_id: purchase.opening_balance_id || null,
                                purchase_code: purchase.code,
                                due_amount: purchaseDue,
                                payment_amount: amountForThisPurchase,
                                remaining: purchaseDue - amountForThisPurchase,
                                is_full_payment: amountForThisPurchase >= purchaseDue
                            });

                            remainingAmount -= amountForThisPurchase;
                        }
                    }

                },

                updatePaymentNote() {
                    const cashAmount = parseFloat(this.paymentAmount) || 0;
                    const advanceAmount = parseFloat(this.advanceAmount) || 0;
                    const settlementAmount = cashAmount + advanceAmount;

                    if (cashAmount > 0 && !this.accountId) {
                        this.paymentNoteText = 'Please select a payment mode for cash/bank payment.';
                        return;
                    }

                    if (settlementAmount > 0) {
                        if (cashAmount > this.accountBalance) {
                            this.paymentNoteText =
                                `<span class="text-danger">Insufficient balance! Available: ৳${this.formatNumber(this.accountBalance)}</span>`;
                            return;
                        }
                        if (advanceAmount > this.availableAdvance) {
                            this.paymentNoteText =
                                `<span class="text-danger">Advance amount exceeds available advance! Available: ৳${this.formatNumber(this.availableAdvance)}</span>`;
                            return;
                        }
                        if (settlementAmount > this.totalDue) {
                            this.paymentNoteText =
                                `<span class="text-danger">Payment amount exceeds total due! Total due: ৳${this.formatNumber(this.totalDue)}</span>`;
                            return;
                        }

                        this.paymentNoteText =
                            `Total settlement ৳${this.formatNumber(settlementAmount)} will be allocated to old due/purchase due in order.`;
                    } else {
                        this.paymentNoteText = 'Enter payment amount to see allocation.';
                    }
                },

                resetPaymentAllocation() {
                    this.paymentAllocations = [];
                },

                resetPaymentFields() {
                    this.paymentAmount = 0;
                    this.advanceAmount = 0;
                    this.resetPaymentAllocation();
                    this.selectedPurchaseIndex = null;
                },

                async submitPayment() {
                    if (!this.canSubmitPayment) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Validation Error',
                            text: 'Please check payment amount and account balance.'
                        });
                        return;
                    }

                    try {
                        this.loading = true;
                        const formData = new FormData(document.querySelector('#paymentForm'));

                        const response = await axios.post('{{ route('StoreSupplierPayment') }}', formData, {
                            headers: {
                                'Content-Type': 'multipart/form-data'
                            }
                        });

                        if (response.data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.data.message
                            }).then(() => {
                                window.location.href = response.data.redirect;
                            });
                        }
                    } catch (error) {
                        if (error.response && error.response.data && error.response.data.errors) {
                            let errorMsg = '';
                            Object.values(error.response.data.errors).forEach(errors => {
                                errors.forEach(err => {
                                    errorMsg += err + '\n';
                                });
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
                                text: error.response?.data?.message || 'Something went wrong!'
                            });
                        }
                    } finally {
                        this.loading = false;
                    }
                }
            }
        });
    </script>
@endsection
