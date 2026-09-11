@extends('backend.master')

@section('header_css')
    <link href="{{ url('assets') }}/plugins/select2/select2.min.css" rel="stylesheet" type="text/css" />
    <style>
        .select2-selection {
            min-height: 38px !important;
            border: 1px solid #ced4da !important;
        }
        .select2 {
            width: 100% !important;
        }
    </style>
@endsection

@section('page_title')
    Expense
@endsection

@section('page_heading')
    Add Expense
@endsection

@section('content')
    @php
        $quickCategoryCreditAccountIds = $payment_types
            ->flatMap(function ($paymentType) {
                return [$paymentType->credit_account_id, $paymentType->debit_account_id];
            })
            ->filter()
            ->unique()
            ->values();

        $quickCategoryCreditAccounts = $accounts
            ->whereIn('id', $quickCategoryCreditAccountIds)
            ->sortBy('account_name')
            ->values();

        if ($quickCategoryCreditAccounts->isEmpty()) {
            $quickCategoryCreditAccounts = $accounts->sortBy('account_name')->values();
        }

        $quickCategoryDebitAccounts = $accounts
            ->where('account_type', 'expense')
            ->sortBy('account_name')
            ->values();

        if ($quickCategoryDebitAccounts->isEmpty()) {
            $quickCategoryDebitAccounts = $accounts->sortBy('account_name')->values();
        }
    @endphp

    <div class="row">
        <div class="col-lg-12 col-xl-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title mb-0">Expense Entry</h4>
                        <a href="{{ route('ViewAllExpense') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                    </div>

                    @include('partials.expense.expense-entry-form', [
                        'expenseFormId' => 'expense_create_form',
                        'productWebsiteId' => null,
                        'storeId' => auth()->user()->store_id ?? 1,
                        'submitUrl' => route('expenses.store'),
                        'expenseCategories' => $expense_categories,
                        'paymentTypes' => $payment_types,
                        'paymentAccounts' => $accounts,
                        'quickCategoryCreditAccounts' => $quickCategoryCreditAccounts,
                        'quickCategoryDebitAccounts' => $quickCategoryDebitAccounts,
                        'taxes' => $taxes,
                        'stores' => $stores,
                        'showPaymentSection' => true,
                        'modalMode' => false,
                    ])
                </div>
            </div>
        </div>
    </div>
@endsection
