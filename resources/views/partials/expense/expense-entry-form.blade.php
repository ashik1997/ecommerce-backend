@php
    $expenseFormId = $expenseFormId ?? 'expense_form_' . uniqid();
    $productWebsiteId = $productWebsiteId ?? null;
    $storeId = $storeId ?? (auth()->user()->store_id ?? 1);
    $submitUrl = $submitUrl ?? route('expenses.store');
    $showPaymentSection = $showPaymentSection ?? true;
    $modalMode = $modalMode ?? false;
    $expenseCategoryModalId = $expenseFormId . '_expense_category_modal';

    $expenseCategories = $expenseCategories ?? \App\Http\Controllers\Account\Models\DbExpenseCategory::where('status', 'active')->orderBy('category_name')->get();
    $paymentTypes = $paymentTypes ?? \App\Http\Controllers\Account\Models\DbPaymentType::where('status', 'active')->orderBy('payment_type')->get();
    $paymentAccounts = $paymentAccounts ?? \App\Http\Controllers\Account\Models\AcAccount::where('status', 'active')->orderBy('account_name')->get();
    $quickCategoryCreditAccountIds = $paymentTypes
        ->flatMap(function ($paymentType) {
            return [$paymentType->credit_account_id, $paymentType->debit_account_id];
        })
        ->filter()
        ->unique()
        ->values();
    $quickCategoryCreditAccounts = $quickCategoryCreditAccounts ?? \App\Http\Controllers\Account\Models\AcAccount::where('status', 'active')
            ->whereIn('id', $quickCategoryCreditAccountIds)
            ->orderBy('account_name')
            ->get();
    $quickCategoryDebitAccounts = $quickCategoryDebitAccounts ?? \App\Http\Controllers\Account\Models\AcAccount::where('status', 'active')
            ->where('account_type', 'expense')
            ->orderBy('account_name')
            ->get();

    if ($quickCategoryCreditAccounts->isEmpty()) {
        $quickCategoryCreditAccounts = $paymentAccounts->sortBy('account_name')->values();
    }

    if ($quickCategoryDebitAccounts->isEmpty()) {
        $quickCategoryDebitAccounts = $paymentAccounts->sortBy('account_name')->values();
    }
    $taxes = $taxes ?? \App\Http\Controllers\Account\Models\DbTax::where('status', 'active')->orderBy('tax_name')->get();
    $stores = $stores ?? \App\Http\Controllers\Outlet\Models\Outlet::where('status', 'active')->orderBy('title')->get();
    $paymentTypeAccounts = $paymentTypes->mapWithKeys(function ($paymentType) {
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
    $categoryPaymentTypes = $expenseCategories->mapWithKeys(function ($category) use ($paymentTypes) {
        $paymentType = $category->credit_id
            ? $paymentTypes->first(function ($paymentType) use ($category) {
                $accountId = $paymentType->credit_account_id ?: $paymentType->debit_account_id;
                if (!$accountId) {
                    $accountId = optional(\App\Http\Controllers\Account\Models\AcAccount::where('paymenttypes_id', $paymentType->id)->where('status', 'active')->first())->id;
                }

                return (int) $accountId === (int) $category->credit_id;
            })
            : null;

        return [$category->id => [
            'credit_account_id' => $category->credit_id,
            'debit_account_id' => $category->debit_id,
            'payment_type_id' => $paymentType->id ?? null,
        ]];
    });
@endphp

@once
    @push('header_css')
        <link href="{{ url('assets') }}/plugins/select2/select2.min.css" rel="stylesheet" type="text/css" />
        <link href="{{ url('assets') }}/plugins/selecttree/select2totree.css" rel="stylesheet" type="text/css" />
    @endpush
@endonce

<div id="{{ $expenseFormId }}" class="expense-entry-form-wrapper">
    <form @submit.prevent="submitExpense">
        <div class="row">
            <div class="col-lg-8">
                <h5 class="mb-3">Expense Information</h5>
                <div class="row">
                    <div class="col-md-6 d-none">
                        <div class="form-group">
                            <label>Business Location / Store <span class="text-danger">*</span></label>
                            @if($stores->count())
                                <select class="form-control select2 store-select" v-model="form.store_id">
                                    <option value="">Select Store</option>
                                    @foreach($stores as $store)
                                        <option value="{{ $store->id }}">{{ $store->title }}</option>
                                    @endforeach
                                </select>
                            @else
                                <input type="text" class="form-control" v-model="form.store_id" placeholder="Store ID">
                            @endif
                            <span class="text-danger" v-if="errors.store_id">@{{ errors.store_id[0] }}</span>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <div class="d-flex justify-content-between align-items-center">
                                <label>Expense Category <span class="text-danger">*</span></label>
                                <button type="button" class="btn btn-sm btn-primary mb-2" data-toggle="modal" data-target="#{{ $expenseCategoryModalId }}" title="Add Category">
                                    <i class="fas fa-plus"></i> Add Category
                                </button>
                            </div>
                            <div class="input-group">
                                <select class="form-control select2 expense-category-select">
                                    <option value="">Select Category</option>
                                    @foreach($expenseCategories as $category)
                                        <option value="{{ $category->id }}">{{ $category->category_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <span class="text-danger" v-if="errors.category_id">@{{ errors.category_id[0] }}</span>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Reference No</label>
                            <input type="text" class="form-control" v-model="form.reference_no" placeholder="Reference no">
                            <span class="text-danger" v-if="errors.reference_no">@{{ errors.reference_no[0] }}</span>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Expense Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" v-model="form.expense_date">
                            <span class="text-danger" v-if="errors.expense_date">@{{ errors.expense_date[0] }}</span>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Expense reason </label>
                            <input type="text" class="form-control" v-model="form.expense_for" placeholder="Expense reason">
                            <span class="text-danger" v-if="errors.expense_for">@{{ errors.expense_for[0] }}</span>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Applicable Tax</label>
                            <select class="form-control select2 tax-select">
                                <option value="" data-tax="0">No Tax</option>
                                @foreach($taxes as $tax)
                                    <option value="{{ $tax->id }}" data-tax="{{ $tax->tax }}">
                                        {{ $tax->tax_name }} ({{ number_format((float) $tax->tax, 2) }}%)
                                    </option>
                                @endforeach
                            </select>
                            <span class="text-danger" v-if="errors.tax_id">@{{ errors.tax_id[0] }}</span>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Total Amount <span class="text-danger">*</span></label>
                            <input type="number" min="0" step="0.01" class="form-control" v-model="form.expense_amt" @input="calculateExpenseSummary">
                            <span class="text-danger" v-if="errors.expense_amt">@{{ errors.expense_amt[0] }}</span>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Expense Note</label>
                            <textarea class="form-control" rows="3" v-model="form.note" placeholder="Expense note"></textarea>
                            <span class="text-danger" v-if="errors.note">@{{ errors.note[0] }}</span>
                        </div>
                    </div>
                </div>

                <div v-if="showPaymentSection">
                    <h5 class="mb-3 mt-3">Payment Information</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Amount Paid</label>
                                <input type="number" min="0" step="0.01" class="form-control" v-model="form.paid_amount" @input="calculateExpenseSummary">
                                <span class="text-danger" v-if="errors.paid_amount">@{{ errors.paid_amount[0] }}</span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Paid On <span class="text-danger" v-if="isPaymentRequired()">*</span></label>
                                <input type="datetime-local" class="form-control" v-model="form.paid_on" :disabled="!isPaymentRequired()">
                                <span class="text-danger" v-if="errors.paid_on">@{{ errors.paid_on[0] }}</span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Payment Method <span class="text-danger" v-if="isPaymentRequired()">*</span></label>
                                <select class="form-control select2 payment-type-select" :disabled="!isPaymentRequired()">
                                    <option value="">Select Payment Method</option>
                                    @foreach($paymentTypes as $paymentType)
                                        <option value="{{ $paymentType->id }}">{{ $paymentType->payment_type }}</option>
                                    @endforeach
                                </select>
                                <span class="text-danger" v-if="errors.payment_type_id">@{{ errors.payment_type_id[0] }}</span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Payment Account <span class="text-danger" v-if="isPaymentRequired()">*</span></label>
                                <select class="form-control select2 payment-account-select" :disabled="!isPaymentRequired()">
                                    <option value="">Select Payment Account</option>
                                    @foreach($paymentAccounts as $account)
                                        <option value="{{ $account->id }}">{{ $account->account_name }}</option>
                                    @endforeach
                                </select>
                                <span class="text-danger" v-if="errors.account_id">@{{ errors.account_id[0] }}</span>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Payment Note</label>
                                <textarea class="form-control" rows="2" v-model="form.payment_note" :disabled="!isPaymentRequired()" placeholder="Payment note"></textarea>
                                <span class="text-danger" v-if="errors.payment_note">@{{ errors.payment_note[0] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border">
                    <div class="card-body">
                        <h5 class="mb-3">Summary</h5>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Total Amount</span>
                            <strong>@{{ money(form.expense_amt) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Tax Amount</span>
                            <strong>@{{ money(form.tax_amount) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Final Amount</span>
                            <strong>@{{ money(form.final_amount) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Paid Amount</span>
                            <strong>@{{ money(form.paid_amount) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Payment Due</span>
                            <strong class="text-danger">@{{ money(form.due_amount) }}</strong>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Payment Status</span>
                            <span class="badge" :class="statusBadgeClass">@{{ form.payment_status }}</span>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block" :disabled="loading">
                    <span v-if="loading">Saving...</span>
                    <span v-else><i class="fas fa-save"></i> Save Expense</span>
                </button>
                <button type="button" class="btn btn-light btn-block" @click="resetForm" :disabled="loading">Reset</button>
            </div>
        </div>
    </form>
</div>

<div class="modal fade" id="{{ $expenseCategoryModalId }}" tabindex="-1" role="dialog" aria-labelledby="{{ $expenseCategoryModalId }}_label" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form class="quick-expense-category-form">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="{{ $expenseCategoryModalId }}_label">Add Expense Category</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Category Name <span class="text-danger">*</span></label>
                                <input type="text" name="category_name" class="form-control" placeholder="Enter category name" required>
                                <small class="text-danger category-error" data-field="category_name"></small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Category Code <span class="text-danger">*</span></label>
                                <input type="text" name="category_code" class="form-control" placeholder="Enter category code" required>
                                <small class="text-danger category-error" data-field="category_code"></small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Paid From (Payment Method) <span class="text-danger">*</span></label>
                                <select name="credit_id" class="form-control quick-credit-account" required>
                                    <option value="">Select From Account</option>
                                    @foreach($quickCategoryCreditAccounts as $account)
                                        <option value="{{ $account->id }}">{{ $account->account_name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-danger category-error" data-field="credit_id"></small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Paid To (Expense Head) <span class="text-danger">*</span></label>
                                <select name="debit_id" class="form-control quick-debit-account" required>
                                    <option value="">Select To Account</option>
                                    @foreach($quickCategoryDebitAccounts as $account)
                                        <option value="{{ $account->id }}">{{ $account->account_name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-danger category-error" data-field="debit_id"></small>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group mb-0">
                                <label>Description</label>
                                <textarea name="description" class="form-control" rows="3" placeholder="Enter description"></textarea>
                                <small class="text-danger category-error" data-field="description"></small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary quick-category-save-btn">
                        <i class="fas fa-save"></i> Save Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@once
    @push('footer_js')
        <script src="{{ versioned_asset('assets/js/vue.min.js') }}"></script>
        <script src="{{ versioned_url('assets/plugins/select2/select2.min.js') }}"></script>
        <script src="{{ url('assets') }}/plugins/selecttree/select2totree.js"></script>
    @endpush
@endonce

@push('footer_js')
    <script>
        (function () {
            if (typeof Vue === 'undefined') {
                console.error('Vue 2 is required for expense entry form.');
                return;
            }

            var csrf = document.querySelector('meta[name="csrf-token"]');
            if (csrf && window.axios) {
                axios.defaults.headers.common['X-CSRF-TOKEN'] = csrf.getAttribute('content');
            }

            var expenseVue = new Vue({
                el: '#{{ $expenseFormId }}',
                data: {
                    loading: false,
                    errors: {},
                    submitUrl: @json($submitUrl),
                    showPaymentSection: @json($showPaymentSection),
                    modalMode: @json($modalMode),
                    taxPercent: 0,
                    paymentTypeAccounts: @json($paymentTypeAccounts),
                    categoryPaymentTypes: @json($categoryPaymentTypes),
                    form: {
                        product_website_id: @json($productWebsiteId),
                        store_id: @json($storeId),
                        category_id: '',
                        expense_date: @json(date('Y-m-d')),
                        reference_no: '',
                        expense_for: '',
                        tax_id: '',
                        expense_amt: 0,
                        tax_amount: 0,
                        final_amount: 0,
                        paid_amount: 0,
                        due_amount: 0,
                        payment_status: 'due',
                        expense_status: 'unresolved',
                        payment_type_id: '',
                        account_id: '',
                        paid_on: '',
                        note: '',
                        payment_note: ''
                    }
                },
                computed: {
                    statusBadgeClass: function () {
                        if (this.form.payment_status === 'paid') return 'badge-success';
                        if (this.form.payment_status === 'partial') return 'badge-warning';
                        if (this.form.payment_status === 'cancelled') return 'badge-secondary';
                        return 'badge-danger';
                    },
                    selectedPaymentAccountName: function () {
                        if (!this.isPaymentRequired()) {
                            return 'No payment account needed';
                        }

                        var account = this.paymentTypeAccounts[this.form.payment_type_id];
                        return account ? account.account_name : 'Select payment method';
                    }
                },
                watch: {
                    'form.paid_amount': function () {
                        this.calculateExpenseSummary();
                        if (!this.isPaymentRequired()) {
                            this.form.payment_type_id = '';
                            this.form.account_id = '';
                            this.form.paid_on = '';
                            this.form.payment_note = '';

                            var wrapper = $('#{{ $expenseFormId }}');
                            wrapper.find('.payment-type-select').val('').trigger('change.select2');
                            wrapper.find('.payment-account-select').val('').trigger('change.select2');
                        }
                    }
                },
                mounted: function () {
                    var vm = this;
                    var wrapper = $('#{{ $expenseFormId }}');

                    wrapper.find('.select2').select2({ width: '100%' });

                    wrapper.find('.store-select').val(vm.form.store_id).trigger('change.select2');
                    wrapper.find('.store-select').on('change', function () {
                        vm.form.store_id = $(this).val();
                    });

                    wrapper.find('.expense-category-select').on('change', function () {
                        vm.form.category_id = $(this).val();
                        vm.applyCategoryPaymentDefault();
                    });

                    wrapper.find('.payment-type-select').on('change', function () {
                        vm.form.payment_type_id = $(this).val();
                        vm.syncPaymentAccount(true);
                    });

                    wrapper.find('.payment-account-select').on('change', function () {
                        vm.form.account_id = $(this).val();
                    });

                    wrapper.find('.tax-select').on('change', function () {
                        var option = $(this).find('option:selected');
                        vm.form.tax_id = $(this).val();
                        vm.taxPercent = parseFloat(option.data('tax') || 0);
                        vm.calculateExpenseSummary();
                    });

                    vm.calculateExpenseSummary();
                },
                methods: {
                    money: function (n) {
                        var x = parseFloat(n || 0);
                        return '৳' + x.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                    },
                    isPaymentRequired: function () {
                        return parseFloat(this.form.paid_amount || 0) > 0;
                    },
                    calculateExpenseSummary: function () {
                        var expenseAmount = parseFloat(this.form.expense_amt || 0);
                        var taxAmount = (expenseAmount * (parseFloat(this.taxPercent || 0) / 100));
                        var paidAmount = parseFloat(this.form.paid_amount || 0);
                        var finalAmount = expenseAmount + taxAmount;

                        if (paidAmount > finalAmount) {
                            paidAmount = finalAmount;
                            this.form.paid_amount = finalAmount.toFixed(2);
                        }

                        var dueAmount = finalAmount - paidAmount;
                        if (dueAmount < 0) dueAmount = 0;

                        this.form.tax_amount = taxAmount.toFixed(2);
                        this.form.final_amount = finalAmount.toFixed(2);
                        this.form.due_amount = dueAmount.toFixed(2);

                        if (paidAmount <= 0) {
                            this.form.payment_status = 'due';
                            this.form.expense_status = 'unresolved';
                        } else if (paidAmount < finalAmount) {
                            this.form.payment_status = 'partial';
                            this.form.expense_status = 'partial';
                        } else {
                            this.form.payment_status = 'paid';
                            this.form.expense_status = 'resolved';
                        }
                        this.syncPaymentAccount(false);
                    },
                    syncPaymentAccount: function (force) {
                        if (!this.isPaymentRequired()) {
                            this.form.account_id = '';
                            return;
                        }

                        if (!force && this.form.account_id) {
                            return;
                        }

                        var account = this.paymentTypeAccounts[this.form.payment_type_id];
                        this.form.account_id = account ? account.account_id : '';
                        $('#{{ $expenseFormId }}').find('.payment-account-select').val(this.form.account_id).trigger('change.select2');
                    },
                    applyCategoryPaymentDefault: function () {
                        var wrapper = $('#{{ $expenseFormId }}');
                        var category = this.categoryPaymentTypes[this.form.category_id];

                        if (!category) {
                            return;
                        }

                        this.form.account_id = category.credit_account_id ? String(category.credit_account_id) : '';
                        wrapper.find('.payment-account-select').val(this.form.account_id).trigger('change.select2');

                        if (category.payment_type_id) {
                            this.form.payment_type_id = String(category.payment_type_id);
                            wrapper.find('.payment-type-select').val(this.form.payment_type_id).trigger('change.select2');
                        }
                    },
                    addCreatedCategory: function (category) {
                        var wrapper = $('#{{ $expenseFormId }}');
                        var select = wrapper.find('.expense-category-select');
                        var categoryId = String(category.id);

                        if (!select.find('option[value="' + categoryId + '"]').length) {
                            select.append(new Option(category.category_name, categoryId, true, true));
                        }

                        this.$set(this.categoryPaymentTypes, categoryId, {
                            credit_account_id: category.credit_id,
                            debit_account_id: category.debit_id,
                            payment_type_id: this.resolvePaymentTypeId(category.credit_id)
                        });

                        this.form.category_id = categoryId;
                        select.val(categoryId).trigger('change.select2');
                        this.applyCategoryPaymentDefault();
                    },
                    resolvePaymentTypeId: function (creditAccountId) {
                        creditAccountId = String(creditAccountId || '');

                        for (var paymentTypeId in this.paymentTypeAccounts) {
                            if (!Object.prototype.hasOwnProperty.call(this.paymentTypeAccounts, paymentTypeId)) {
                                continue;
                            }

                            if (String(this.paymentTypeAccounts[paymentTypeId].account_id || '') === creditAccountId) {
                                return paymentTypeId;
                            }
                        }

                        return null;
                    },
                    submitExpense: function () {
                        var vm = this;
                        vm.loading = true;
                        vm.errors = {};
                        vm.calculateExpenseSummary();

                        axios.post(vm.submitUrl, vm.form)
                            .then(function (response) {
                                vm.loading = false;
                                if (response.data && response.data.success) {
                                    toastr.success(response.data.message || 'Expense saved successfully.');
                                    vm.resetForm();

                                    if (typeof window.onExpenseSaved === 'function') {
                                        window.onExpenseSaved(response.data.data);
                                    }

                                    if (vm.modalMode) {
                                        $('#{{ $expenseFormId }}').closest('.modal').modal('hide');
                                    }
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
                    },
                    resetForm: function () {
                        this.form = {
                            product_website_id: @json($productWebsiteId),
                            store_id: @json($storeId),
                            category_id: '',
                            expense_date: @json(date('Y-m-d')),
                            reference_no: '',
                            expense_for: '',
                            tax_id: '',
                            expense_amt: 0,
                            tax_amount: 0,
                            final_amount: 0,
                            paid_amount: 0,
                            due_amount: 0,
                            payment_status: 'due',
                            expense_status: 'unresolved',
                            payment_type_id: '',
                            account_id: '',
                            paid_on: '',
                            note: '',
                            payment_note: ''
                        };
                        this.taxPercent = 0;
                        this.errors = {};
                        this.$nextTick(function () {
                            var wrapper = $('#{{ $expenseFormId }}');
                            wrapper.find('.select2').val('').trigger('change.select2');
                            if (this.form.store_id) {
                                wrapper.find('.store-select').val(this.form.store_id).trigger('change.select2');
                            }
                        });
                    }
                }
            });

            var categoryModal = $('#{{ $expenseCategoryModalId }}');
            var categoryForm = categoryModal.find('.quick-expense-category-form');
            var modalSelectsReady = false;

            function initModalSelects() {
                if (modalSelectsReady) {
                    return;
                }

                categoryModal.find('.quick-credit-account').select2({
                    placeholder: 'Select From Account',
                    allowClear: true,
                    width: '100%',
                    dropdownParent: categoryModal
                });

                categoryModal.find('.quick-debit-account').select2({
                    placeholder: 'Select To Account',
                    allowClear: true,
                    width: '100%',
                    dropdownParent: categoryModal
                });

                modalSelectsReady = true;
            }

            function clearCategoryErrors() {
                categoryForm.find('.category-error').text('');
            }

            categoryModal.on('shown.bs.modal', function () {
                initModalSelects();
                categoryForm.find('input[name="category_name"]').trigger('focus');
            });

            categoryModal.on('hidden.bs.modal', function () {
                categoryForm[0].reset();
                categoryModal.find('.quick-credit-account, .quick-debit-account').val('').trigger('change');
                clearCategoryErrors();
            });

            categoryForm.on('submit', function (event) {
                event.preventDefault();
                clearCategoryErrors();

                var saveButton = categoryForm.find('.quick-category-save-btn');
                saveButton.prop('disabled', true).html('<span>Saving...</span>');

                $.ajax({
                    url: "{{ route('SaveNewExpenseCategory') }}",
                    type: "POST",
                    data: categoryForm.serialize(),
                    dataType: "json",
                    success: function (response) {
                        if (response.success && response.data) {
                            expenseVue.addCreatedCategory(response.data);
                            toastr.success(response.message || 'Expense category saved successfully.');
                            categoryModal.modal('hide');
                        } else {
                            toastr.error(response.message || 'Something went wrong.');
                        }
                    },
                    error: function (xhr) {
                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            $.each(xhr.responseJSON.errors, function (field, messages) {
                                categoryForm.find('.category-error[data-field="' + field + '"]').text(messages[0]);
                            });
                            toastr.error(xhr.responseJSON.message || 'Validation failed.');
                            return;
                        }

                        toastr.error('Something went wrong. Please try again.');
                    },
                    complete: function () {
                        saveButton.prop('disabled', false).html('<i class="fas fa-save"></i> Save Category');
                    }
                });
            });
        })();
    </script>
@endpush
