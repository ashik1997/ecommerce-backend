<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Account\LedgerController;
use App\Http\Controllers\Account\AccountController;
use App\Http\Controllers\Account\ExpenseController;
use App\Http\Controllers\Account\ExpensePaymentController;
use App\Http\Controllers\Account\PaymenttypeController;
use App\Http\Controllers\Account\TransactionController;
use App\Http\Controllers\Account\ExpenseCategoryController;
use App\Http\Controllers\Account\AccountIncomeCategoryController;
use App\Http\Controllers\Account\AccountIncomeController;
use App\Http\Controllers\Account\AdjustmentController;
use App\Http\Controllers\Account\InvestorController;
use App\Http\Controllers\Account\FundTransferController;


Route::group(['middleware' => ['auth', 'CheckUserType', 'DemoMode']], function () {
    // Payment Type
    Route::get('/add/new/payment-type', [PaymenttypeController::class, 'addNewPaymentType'])->name('AddNewPaymentType');
    Route::post('/save/new/payment-type', [PaymenttypeController::class, 'saveNewPaymentType'])->name('SaveNewPaymentType');
    Route::get('/view/all/payment-type', [PaymenttypeController::class, 'viewAllPaymentType'])->name('ViewAllPaymentType');
    Route::get('/payment-type/{slug}/history', [PaymenttypeController::class, 'paymentTypeHistory'])->name('PaymentTypeHistory');
    Route::get('/payment-type/{slug}/history/details', [PaymenttypeController::class, 'paymentTypeHistoryDetails'])->name('PaymentTypeHistoryDetails');
    Route::get('/delete/payment-type/{slug}', [PaymenttypeController::class, 'deletePaymentType'])->name('DeletePaymentType');
    Route::get('/edit/payment-type/{slug}', [PaymenttypeController::class, 'editPaymentType'])->name('EditPaymentType');
    Route::post('/update/payment-type', [PaymenttypeController::class, 'updatePaymentType'])->name('UpdatePaymentType');

    // Expense Category
    Route::get('/add/new/expense-category', [ExpenseCategoryController::class, 'addNewExpenseCategory'])->name('AddNewExpenseCategory');
    Route::post('/save/new/expense-category', [ExpenseCategoryController::class, 'saveNewExpenseCategory'])->name('SaveNewExpenseCategory');
    Route::get('/view/all/expense-category', [ExpenseCategoryController::class, 'viewAllExpenseCategory'])->name('ViewAllExpenseCategory');
    Route::get('/delete/expense-category/{slug}', [ExpenseCategoryController::class, 'deleteExpenseCategory'])->name('DeleteExpenseCategory');
    Route::get('/edit/expense-category/{slug}', [ExpenseCategoryController::class, 'editExpenseCategory'])->name('EditExpenseCategory');
    Route::post('/update/expense-category', [ExpenseCategoryController::class, 'updateExpenseCategory'])->name('UpdateExpenseCategory');

    // Income Category
    Route::get('/add/new/income-category', [AccountIncomeCategoryController::class, 'addNewIncomeCategory'])->name('AddNewIncomeCategory');
    Route::post('/save/new/income-category', [AccountIncomeCategoryController::class, 'saveNewIncomeCategory'])->name('SaveNewIncomeCategory');
    Route::get('/view/all/income-category', [AccountIncomeCategoryController::class, 'viewAllIncomeCategory'])->name('ViewAllIncomeCategory');
    Route::get('/delete/income-category/{slug}', [AccountIncomeCategoryController::class, 'deleteIncomeCategory'])->name('DeleteIncomeCategory');
    Route::get('/edit/income-category/{slug}', [AccountIncomeCategoryController::class, 'editIncomeCategory'])->name('EditIncomeCategory');
    Route::post('/update/income-category', [AccountIncomeCategoryController::class, 'updateIncomeCategory'])->name('UpdateIncomeCategory');

    // Income
    Route::get('/add/new/income', [AccountIncomeController::class, 'addNewIncome'])->name('AddNewIncome');
    Route::post('/save/new/income', [AccountIncomeController::class, 'saveNewIncome'])->name('SaveNewIncome');
    Route::get('/view/all/income', [AccountIncomeController::class, 'viewAllIncome'])->name('ViewAllIncome');
    Route::get('/view/income/{id}', [AccountIncomeController::class, 'showIncome'])->name('ViewIncomeDetails');
    Route::get('/print/income/{id}', [AccountIncomeController::class, 'printIncome'])->name('PrintIncome');
    Route::get('/get/income-category-details', [AccountIncomeController::class, 'getIncomeCategoryDetails'])->name('GetIncomeCategoryDetails');

    // Account
    Route::get('/add/new/ac-account', [AccountController::class, 'addNewAcAccount'])->name('AddNewAcAccount');
    Route::post('/save/new/ac-account', [AccountController::class, 'saveNewAcAccount'])->name('SaveNewAcAccount');
    Route::get('/view/all/ac-account', [AccountController::class, 'viewAllAcAccount'])->name('ViewAllAcAccount');
    Route::get('/delete/ac-account/{slug}', [AccountController::class, 'deleteAcAccount'])->name('DeleteAcAccount');
    Route::get('/edit/ac-account/{slug}', [AccountController::class, 'editAcAccount'])->name('EditAcAccount');
    Route::post('/update/ac-account', [AccountController::class, 'updateAcAccount'])->name('UpdateAcAccount');
    Route::get('/get/ac-account/json', [AccountController::class, 'getJsonAcAccount'])->name('GetJsonAcAccount');
    Route::get('/get/ac-account-expense/json', [AccountController::class, 'getJsonAcAccountExpense'])->name('GetJsonAcAccountExpense');
    Route::get('/get/ac-account-revenue/json', [AccountController::class, 'getJsonAcAccountRevenue'])->name('GetJsonAcAccountRevenue');
    Route::get('/get/ac-account-from-payment-types/json', [AccountController::class, 'getJsonAcAccountFromPaymentTypes'])->name('GetJsonAcAccountFromPaymentTypes');


    // Expense 
    Route::get('/add/new/expense', [ExpenseController::class, 'addNewExpense'])->name('AddNewExpense');
    Route::post('/save/new/expense', [ExpenseController::class, 'saveNewExpense'])->name('SaveNewExpense');
    Route::get('/view/all/expense', [ExpenseController::class, 'viewAllExpense'])->name('ViewAllExpense');
    Route::get('/view/expense/{id}', [ExpenseController::class, 'showExpense'])->name('ViewExpenseDetails');
    Route::get('/print/expense/{id}', [ExpenseController::class, 'printExpense'])->name('PrintExpense');
    Route::get('/edit/expense/{slug}', [ExpenseController::class, 'editExpense'])->name('EditExpense');
    Route::post('/update/expense', [ExpenseController::class, 'updateExpense'])->name('UpdateExpense');
    Route::get('/get/expense-category-details', [ExpenseController::class, 'getExpenseCategoryDetails'])->name('GetExpenseCategoryDetails');
    Route::get('/get/account-balance', [ExpenseController::class, 'getAccountBalance'])->name('GetAccountBalance');

    Route::get('/expenses', [ExpenseController::class, 'viewAllExpense'])->name('expenses.index');
    Route::get('/expenses/create', [ExpenseController::class, 'create'])->name('expenses.create');
    Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
    Route::get('/expenses/{id}', [ExpenseController::class, 'showExpense'])->name('expenses.show');
    Route::get('/expenses/{id}/edit', [ExpenseController::class, 'edit'])->name('expenses.edit');
    Route::put('/expenses/{id}', [ExpenseController::class, 'update'])->name('expenses.update');
    Route::get('/expenses/{id}/payments', [ExpensePaymentController::class, 'index'])->name('expenses.payments.index');
    Route::post('/expenses/{id}/payments', [ExpensePaymentController::class, 'store'])->name('expenses.payments.store');
    Route::post('/expenses/{id}/cancel', [ExpenseController::class, 'cancel'])->name('expenses.cancel');

    // Deposit 
    Route::get('/add/new/deposit', [TransactionController::class, 'addNewDeposit'])->name('AddNewDeposit');
    Route::post('/save/new/deposit', [TransactionController::class, 'saveNewDeposit'])->name('SaveNewDeposit');
    Route::get('/view/all/deposit', [TransactionController::class, 'viewAllDeposit'])->name('ViewAllDeposit');
    Route::get('/print/deposit/{id}', [TransactionController::class, 'printDeposit'])->name('PrintDeposit');
    Route::get('/delete/deposit/{slug}', [TransactionController::class, 'deleteDeposit'])->name('DeleteDeposit');
    Route::get('/edit/deposit/{slug}', [TransactionController::class, 'editDeposit'])->name('EditDeposit');
    Route::post('/update/deposit', [TransactionController::class, 'updateDeposit'])->name('UpdateDeposit');

    // Withdraw
    Route::get('/add/new/withdraw', [TransactionController::class, 'addNewWithdraw'])->name('AddNewWithdraw');
    Route::post('/store/withdraw', [TransactionController::class, 'saveNewWithdraw'])->name('StoreWithdraw');
    Route::get('/view/all/withdraw', [TransactionController::class, 'viewAllWithdraw'])->name('ViewAllWithdraw');
    Route::get('/print/withdraw/{id}', [TransactionController::class, 'printWithdraw'])->name('PrintWithdraw');
    Route::get('/get/investor-balance', [TransactionController::class, 'getInvestorBalance'])->name('GetInvestorBalance');
    Route::get('/get/payment-type-balance', [TransactionController::class, 'getPaymentTypeBalance'])->name('GetPaymentTypeBalance');

    // Account Adjustment
    Route::get('/view/all/adjustment', [AdjustmentController::class, 'index'])->name('ViewAllAdjustment');
    Route::get('/create/adjustment', [AdjustmentController::class, 'create'])->name('CreateAdjustment');
    Route::post('/store/adjustment', [AdjustmentController::class, 'store'])->name('StoreAdjustment');

    // Fund Transfer
    Route::get('/view/all/fund-transfer', [FundTransferController::class, 'index'])->name('ViewAllFundTransfer');
    Route::get('/create/fund-transfer', [FundTransferController::class, 'create'])->name('CreateFundTransfer');
    Route::post('/store/fund-transfer', [FundTransferController::class, 'store'])->name('StoreFundTransfer');
    Route::get('/print/fund-transfer/{id}', [FundTransferController::class, 'print'])->name('PrintFundTransfer');
    Route::get('/get/fund-transfer/payment-type-balance', [FundTransferController::class, 'balance'])->name('GetFundTransferPaymentTypeBalance');
    Route::get('/view/all/fund-transfer-type', [FundTransferController::class, 'transferTypes'])->name('ViewAllFundTransferType');
    Route::post('/store/fund-transfer-type', [FundTransferController::class, 'storeTransferType'])->name('StoreFundTransferType');
    Route::get('/delete/fund-transfer-type/{id}', [FundTransferController::class, 'deleteTransferType'])->name('DeleteFundTransferType');

    // Investor Management
    Route::get('/view/all/investor', [InvestorController::class, 'index'])->name('ViewAllInvestor');
    Route::get('/create/investor', [InvestorController::class, 'create'])->name('CreateInvestor');
    Route::post('/store/investor', [InvestorController::class, 'store'])->name('StoreInvestor');
    Route::get('/view/investor/{id}', [InvestorController::class, 'show'])->name('ViewInvestorDetails');
    Route::get('/edit/investor/{id}', [InvestorController::class, 'edit'])->name('EditInvestor');
    Route::post('/update/investor/{id}', [InvestorController::class, 'update'])->name('UpdateInvestor');

    // Ledger 
    Route::get('/ledger', [LedgerController::class, 'index'])->name('ledger.index');
    Route::get('/ledger/journal', [LedgerController::class, 'journal'])->name('journal.index');
    Route::get('/ledger/suppliers/search', [LedgerController::class, 'searchSuppliers'])->name('ledger.suppliers.search');
    Route::get('/ledger/trial-balance', [LedgerController::class, 'trialBalance'])->name('ledger.trial_balance');
    Route::get('/ledger/income-statement', [LedgerController::class, 'incomeStatement'])->name('ledger.income_statement');
    Route::get('/ledger/balance-sheet', [LedgerController::class, 'balanceSheet'])->name('ledger.balance_sheet');
    Route::get('/ledger/cash-bank-book', [LedgerController::class, 'cashBankBook'])->name('ledger.cash_bank_book');
    Route::get('/ledger/supplier-ledger', [LedgerController::class, 'supplierLedger'])->name('ledger.supplier_ledger');
    Route::get('/ledger/supplier-due', [LedgerController::class, 'supplierDue'])->name('ledger.supplier_due');
    Route::get('/ledger/customer-ledger', [LedgerController::class, 'customerLedger'])->name('ledger.customer_ledger');
    Route::get('/ledger/customer-due', [LedgerController::class, 'customerDue'])->name('ledger.customer_due');
    Route::get('/ledger/expense-report', [LedgerController::class, 'expenseReport'])->name('ledger.expense_report');
    Route::get('/ledger/day-book', [LedgerController::class, 'dayBook'])->name('ledger.day_book');

    
});
