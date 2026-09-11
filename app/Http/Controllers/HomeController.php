<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Order;
use App\Models\ShippingInfo;
use App\Models\OrderPayment;
use App\Models\User;
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Yajra\DataTables\DataTables;
use App\Http\Controllers\Customer\Models\Customer;
use App\Http\Controllers\Customer\Models\CustomerCategory;
use App\Http\Controllers\Customer\Models\CustomerContactHistory;
use App\Http\Controllers\Customer\Models\CustomerNextContactDate;
use App\Http\Controllers\Outlet\Models\CustomerSourceType;
use App\Http\Controllers\Account\Models\AcAccount;
use App\Http\Controllers\Account\Models\AcTransaction;
use App\Http\Controllers\Inventory\Models\ProductPurchaseOrder;
use App\Services\Supplier\SupplierTransactionService;
use App\Services\AccountingReportService;
use Illuminate\Support\Facades\Schema;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {

        $recentCustomers = User::where('user_type', 3)->orderBy('id', 'desc')->skip(0)->limit(5)->get();
        $orderPayments = OrderPayment::orderBy('id', 'desc')->skip(0)->limit(5)->get();

        // for upper graph start
        $countOrders = array();
        for ($i = 0; $i <= 8; $i++) {
            $orderStartDate = date("Y-m", strtotime("-$i month", strtotime(date("Y-m")))) . "-01 00:00:00";
            $orderEndDate = date("Y-m-t", strtotime("-$i month", strtotime(date("Y-m")))) . " 23:59:59";
            $countOrders[$i] = Order::whereBetween('order_date', [$orderStartDate, $orderEndDate])->count();
        }

        $totalOrderAmount = array();
        for ($i = 0; $i <= 8; $i++) {
            $orderStartDate = date("Y-m", strtotime("-$i month", strtotime(date("Y-m")))) . "-01 00:00:00";
            $orderEndDate = date("Y-m-t", strtotime("-$i month", strtotime(date("Y-m")))) . " 23:59:59";
            $totalOrderAmount[$i] = Order::whereBetween('order_date', [$orderStartDate, $orderEndDate])->where('order_status', '!=', 4)->sum('total');
        }

        $todaysOrder = array();
        for ($i = 0; $i <= 8; $i++) {
            $orderPlaceDate = date("Y-m-d", strtotime("-$i day", strtotime(date("Y-m-d"))));
            $todaysOrder[$i] = Order::where('created_at', 'LIKE', $orderPlaceDate . '%')->count();
        }

        $registeredUsers = array();
        for ($i = 0; $i <= 8; $i++) {
            $orderStartDate = date("Y-m", strtotime("-$i month", strtotime(date("Y-m")))) . "-01 00:00:00";
            $orderEndDate = date("Y-m-t", strtotime("-$i month", strtotime(date("Y-m")))) . " 23:59:59";
            $registeredUsers[$i] = User::whereBetween('created_at', [$orderStartDate, $orderEndDate])->count();
        }
        // for upper graph end


        // all time best product graph start
        $queryStartDate = date("Y-m", strtotime("-6 month", strtotime(date("Y-m")))) . "-01 00:00:00";
        $queryEndDate = date("Y-m-d") . " 23:59:59";

        $bestSelling = DB::table('order_details')
            ->join('products', 'order_details.product_id', '=', 'products.id')
            ->selectRaw('products.name, SUM(order_details.qty) as total_qty')
            ->whereBetween('order_details.created_at', [$queryStartDate, $queryEndDate])
            ->groupBy('order_details.product_id')
            ->orderBy('total_qty', 'desc')
            ->skip(0)
            ->limit(3)
            ->get();
        // all time best product graph end


        // success and failed order ratio start
        $countOrdersRatioSuccess = array();
        $countOrdersRatioFailed = array();
        $countOrdersRatioDate = array();

        for ($i = 0; $i <= 9; $i++) {
            $orderRatioStartDate = date("Y-m", strtotime("-$i month", strtotime(date("Y-m")))) . "-01 00:00:00";
            $orderRatioEndDate = date("Y-m-t", strtotime("-$i month", strtotime(date("Y-m")))) . " 23:59:59";

            $countOrdersRatioDate[$i] = date("M-y", strtotime("-$i month", strtotime(date("Y-m"))));
            $countOrdersRatioSuccess[$i] = Order::whereBetween('order_date', [$orderRatioStartDate, $orderRatioEndDate])->where('order_status', 4)->count();
            $countOrdersRatioFailed[$i] = Order::whereBetween('order_date', [$orderRatioStartDate, $orderRatioEndDate])->where('order_status', '!=', 4)->count();
        }
        // success and failed order ratio end

        $thanaOrders = ShippingInfo::with('order')
            ->whereHas('order', function ($query) {
                $query->where('created_at', '>=', now()->subMonths(2));
            })
            ->select('thana', DB::raw('COUNT(DISTINCT order_id) as order_count'))
            ->groupBy('thana')
            ->having('order_count', '>', 0)
            ->get();

        $cityOrders = ShippingInfo::with('order')
            ->whereHas('order', function ($query) {
                $query->where('created_at', '>=', now()->subMonths(2));
            })
            ->select('city', DB::raw('COUNT(DISTINCT order_id) as order_count'))
            ->groupBy('city')
            ->having('order_count', '>', 0)
            ->get();

        $ordersBySource = Order::with('customerSourceType')
            ->select('customer_src_type_id', DB::raw('COUNT(*) as order_count'))
            ->groupBy('customer_src_type_id')
            ->get();


        return view(
            'backend.dashboard',
            compact(
                'recentCustomers',
                'orderPayments',
                'countOrders',
                'totalOrderAmount',
                'todaysOrder',
                'registeredUsers',
                'bestSelling',
                'queryStartDate',
                'countOrdersRatioDate',
                'countOrdersRatioSuccess',
                'countOrdersRatioFailed',
                'thanaOrders',
                'cityOrders',
                'ordersBySource'
            )
        );
    }

    public function crm_index()
    {
        // Existing data queries
        $recentCustomers = Customer::where('status', 'active')->orderBy('id', 'desc')->skip(0)->limit(10)->get();
        $orderPayments = OrderPayment::orderBy('id', 'desc')->skip(0)->limit(5)->get();

        $totalCustomerSourceTypes = CustomerSourceType::where('status', 'active')->count();
        $totalCustomerCategory = CustomerCategory::where('status', 'active')->count();
        $totalCustomerContactHistory = CustomerContactHistory::where('status', 'active')->count();
        $totalPendingCustomerNextContactDate = CustomerNextContactDate::where('status', 'active')->count();

        $totalCustomers = Customer::where('status', 'active')->count();
        $totalLastThirtyDaysCustomers = Customer::where('status', 'active')
            ->where('created_at', '>=', Carbon::now('Asia/Dhaka')->subDays(30))
            ->count();
        $totalLastSixMonthsCustomers = Customer::where('status', 'active')
            ->whereDate('created_at', '>=', Carbon::now('Asia/Dhaka')->subMonths(6))
            ->count();

        $startOfMonth = Carbon::now('Asia/Dhaka')->startOfMonth();
        $today = Carbon::now('Asia/Dhaka')->endOfDay();
        $totalThisMonthCustomers = Customer::where('status', 'active')
            ->whereBetween('created_at', [$startOfMonth, $today])
            ->count();

        // Customer growth for the last 30 days
        $customerGrowth = Customer::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', Carbon::now('Asia/Dhaka')->subDays(30))
            ->groupBy('date')
            ->get();

        // Customer growth for the last 6 months
        $customerGrowthSixMonths = Customer::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
            ->where('created_at', '>=', Carbon::now('Asia/Dhaka')->subMonths(6))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // dd($customerGrowthSixMonths);
        $upcomingContactCustomers = CustomerNextContactDate::where('next_date', '>=', now())->count();
        $pendingContactCustomers = CustomerNextContactDate::where('next_date', '>=', now())->where('contact_status', 'pending')->count();
        $missedContactCustomers = CustomerNextContactDate::where('next_date', '>=', now())->where('contact_status', 'missed')->count();
        $doneContactCustomers = CustomerNextContactDate::where('next_date', '>=', now())->where('contact_status', 'done')->count();


        $customerSourceDistribution = CustomerSourceType::withCount('customers')->get();

        return view(
            'backend.crm-dashboard',
            compact(
                'totalCustomerSourceTypes',
                'totalCustomerCategory',
                'totalCustomerContactHistory',
                'totalPendingCustomerNextContactDate',
                'totalCustomers',
                'totalLastThirtyDaysCustomers',
                'totalLastSixMonthsCustomers',
                'totalThisMonthCustomers',
                'recentCustomers',
                'orderPayments',
                'customerGrowth',
                'customerGrowthSixMonths',
                'customerSourceDistribution',
                'upcomingContactCustomers',
                'pendingContactCustomers',
                'missedContactCustomers',
                'doneContactCustomers'
            )
        );
    }
    public function inventory_dashboard()
    {
        return view('backend.inventory-dashboard');
    }

    // public function accounts_index()
    // {

    //     $recentCustomers = User::where('user_type', 3)->orderBy('id', 'desc')->skip(0)->limit(5)->get();
    //     $orderPayments = OrderPayment::orderBy('id', 'desc')->skip(0)->limit(5)->get();

    //     // for upper graph start
    //     $countOrders = array();
    //     for ($i = 0; $i <= 8; $i++) {
    //         $orderStartDate = date("Y-m", strtotime("-$i month", strtotime(date("Y-m")))) . "-01 00:00:00";
    //         $orderEndDate = date("Y-m-t", strtotime("-$i month", strtotime(date("Y-m")))) . " 23:59:59";
    //         $countOrders[$i] = Order::whereBetween('order_date', [$orderStartDate, $orderEndDate])->count();
    //     }

    //     $totalOrderAmount = array();
    //     for ($i = 0; $i <= 8; $i++) {
    //         $orderStartDate = date("Y-m", strtotime("-$i month", strtotime(date("Y-m")))) . "-01 00:00:00";
    //         $orderEndDate = date("Y-m-t", strtotime("-$i month", strtotime(date("Y-m")))) . " 23:59:59";
    //         $totalOrderAmount[$i] = Order::whereBetween('order_date', [$orderStartDate, $orderEndDate])->where('order_status', '!=', 4)->sum('total');
    //     }

    //     $todaysOrder = array();
    //     for ($i = 0; $i <= 8; $i++) {
    //         $orderPlaceDate = date("Y-m-d", strtotime("-$i day", strtotime(date("Y-m-d"))));
    //         $todaysOrder[$i] = Order::where('created_at', 'LIKE', $orderPlaceDate . '%')->count();
    //     }

    //     $registeredUsers = array();
    //     for ($i = 0; $i <= 8; $i++) {
    //         $orderStartDate = date("Y-m", strtotime("-$i month", strtotime(date("Y-m")))) . "-01 00:00:00";
    //         $orderEndDate = date("Y-m-t", strtotime("-$i month", strtotime(date("Y-m")))) . " 23:59:59";
    //         $registeredUsers[$i] = User::whereBetween('created_at', [$orderStartDate, $orderEndDate])->count();
    //     }
    //     // for upper graph end


    //     // all time best product graph start
    //     $queryStartDate = date("Y-m", strtotime("-6 month", strtotime(date("Y-m")))) . "-01 00:00:00";
    //     $queryEndDate = date("Y-m-d") . " 23:59:59";

    //     $bestSelling = DB::table('order_details')
    //         ->join('products', 'order_details.product_id', '=', 'products.id')
    //         ->selectRaw('products.name, SUM(order_details.qty) as total_qty')
    //         ->whereBetween('order_details.created_at', [$queryStartDate, $queryEndDate])
    //         ->groupBy('order_details.product_id')
    //         ->orderBy('total_qty', 'desc')
    //         ->skip(0)
    //         ->limit(3)
    //         ->get();
    //     // all time best product graph end


    //     // success and failed order ratio start
    //     $countOrdersRatioSuccess = array();
    //     $countOrdersRatioFailed = array();
    //     $countOrdersRatioDate = array();

    //     for ($i = 0; $i <= 9; $i++) {
    //         $orderRatioStartDate = date("Y-m", strtotime("-$i month", strtotime(date("Y-m")))) . "-01 00:00:00";
    //         $orderRatioEndDate = date("Y-m-t", strtotime("-$i month", strtotime(date("Y-m")))) . " 23:59:59";

    //         $countOrdersRatioDate[$i] = date("M-y", strtotime("-$i month", strtotime(date("Y-m"))));
    //         $countOrdersRatioSuccess[$i] = Order::whereBetween('order_date', [$orderRatioStartDate, $orderRatioEndDate])->where('order_status', 4)->count();
    //         $countOrdersRatioFailed[$i] = Order::whereBetween('order_date', [$orderRatioStartDate, $orderRatioEndDate])->where('order_status', '!=', 4)->count();
    //     }
    //     // success and failed order ratio end


    //     // return 5;
    //     return view('backend.accounts-dashboard', compact(
    //             'recentCustomers',
    //             'orderPayments',
    //             'countOrders',
    //             'totalOrderAmount',
    //             'todaysOrder',
    //             'registeredUsers',
    //             'bestSelling',
    //             'queryStartDate',
    //             'countOrdersRatioDate',
    //             'countOrdersRatioSuccess',
    //             'countOrdersRatioFailed',
    //         )
    //     );
    // }

    public function accounts_index()
    {
        $accountingReports = app(AccountingReportService::class);
        $startDate12Months = Carbon::now()->subMonths(11)->startOfMonth();
        $endDate12Months = Carbon::now()->endOfMonth();
        $startDate30Days = Carbon::now()->subDays(29)->startOfDay();
        $endDate30Days = Carbon::now()->endOfDay();
        $today = Carbon::now('Asia/Dhaka')->toDateString();
        $monthStart = Carbon::now('Asia/Dhaka')->startOfMonth()->toDateString();
        $monthEnd = Carbon::now('Asia/Dhaka')->toDateString();

        $revenueAccounts = $this->accountIdsByType('revenue');
        $expenseAccounts = $this->accountIdsByType('expense');
        $cashAccounts = $this->cashAccountIds();

        $totalRevenue12Months = $this->netAmountForAccounts($revenueAccounts, $startDate12Months, $endDate12Months, 'credit');
        $totalExpenses12Months = $this->netAmountForAccounts($expenseAccounts, $startDate12Months, $endDate12Months, 'debit');
        $netProfit12Months = $totalRevenue12Months - $totalExpenses12Months;

        $totalRevenue30Days = $this->netAmountForAccounts($revenueAccounts, $startDate30Days, $endDate30Days, 'credit');
        $totalExpenses30Days = $this->netAmountForAccounts($expenseAccounts, $startDate30Days, $endDate30Days, 'debit');
        $netProfit30Days = $totalRevenue30Days - $totalExpenses30Days;

        $dailySeries = $this->dailyAccountingSeries($startDate30Days, $endDate30Days, $revenueAccounts, $expenseAccounts, $cashAccounts);
        $cashFlowLabels30Days = $dailySeries['labels'];
        $cashFlowIncome30Days = $dailySeries['revenue'];
        $cashFlowExpenses30Days = $dailySeries['expenses'];
        $cashFlowNet30Days = $dailySeries['cash_flow'];

        $monthlySeries = $this->monthlyAccountingSeries($startDate12Months, $endDate12Months, $revenueAccounts, $expenseAccounts, $cashAccounts);
        $cashFlowLabels12Months = $monthlySeries['labels'];
        $cashFlowIncome12Months = $monthlySeries['revenue'];
        $cashFlowExpenses12Months = $monthlySeries['expenses'];
        $cashFlowNet12Months = $monthlySeries['cash_flow'];

        // Fetch recent transactions (last 10 transactions)
        $recentTransactions = AcTransaction::with(['debitAccount', 'creditAccount'])
            ->where('status', 'active')
            ->whereBetween('transaction_date', [$startDate30Days, $endDate30Days])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        $dashboardCards = [
            'cash_bank_balance' => $this->dashboardAccountBalance($accountingReports, $accountingReports->cashBankAccountIds(), ['date_to' => $today]),
            'accounts_receivable' => $this->dashboardAccountBalance($accountingReports, $accountingReports->accountsReceivableAccountIds(), ['date_to' => $today]),
            'accounts_payable' => $this->dashboardAccountBalance($accountingReports, $accountingReports->accountsPayableAccountIds(), ['date_to' => $today]),
            'inventory_value' => $this->dashboardAccountBalance($accountingReports, $this->inventoryAccountIds($accountingReports), ['date_to' => $today]),
        ];
        $todayBook = $accountingReports->dayBook([
            'date_from' => $today,
            'date_to' => $today,
        ]);
        $monthProfitLoss = $accountingReports->profitLoss([
            'date_from' => $monthStart,
            'date_to' => $monthEnd,
        ]);
        $dashboardCards += [
            'today_sales' => $todayBook['totals']['sales'] ?? 0,
            'today_purchase' => $todayBook['totals']['purchase'] ?? 0,
            'today_expense' => $todayBook['totals']['expense'] ?? 0,
            'net_profit_this_month' => $monthProfitLoss['netProfit'] ?? 0,
        ];

        // Pass data to the view
        return view(
            'backend.accounts-dashboard',
            compact(
                'dashboardCards',
                'totalRevenue12Months',
                'totalExpenses12Months',
                'netProfit12Months',
                'cashFlowLabels12Months',
                'cashFlowIncome12Months',
                'cashFlowExpenses12Months',
                'cashFlowNet12Months',
                'totalRevenue30Days',
                'totalExpenses30Days',
                'netProfit30Days',
                'cashFlowLabels30Days',
                'cashFlowIncome30Days',
                'cashFlowExpenses30Days',
                'cashFlowNet30Days',
                'recentTransactions'
            )
        );
    }

    private function dashboardAccountBalance(AccountingReportService $accountingReports, $accountIds, array $filters = []): float
    {
        if ($accountIds->isEmpty()) {
            return 0;
        }

        return $accountingReports->activeAccountQuery()
            ->whereIn('id', $accountIds)
            ->get()
            ->sum(function ($account) use ($accountingReports, $filters) {
                return $accountingReports->balanceForAccount($account, $filters);
            });
    }

    private function inventoryAccountIds(AccountingReportService $accountingReports)
    {
        return $accountingReports->activeAccountQuery()
            ->where(function ($query) {
                $query->where('account_selection_name', 'inventory')
                    ->orWhere('account_name', 'like', '%Inventory%');
            })
            ->pluck('id');
    }

    private function accountIdsByType(string $accountType)
    {
        return AcAccount::where('account_type', $accountType)
            ->where('status', 'active')
            ->pluck('id');
    }

    private function cashAccountIds()
    {
        $cashAccounts = AcAccount::where('account_type', 'asset')
            ->where('status', 'active')
            ->whereNotNull('paymenttypes_id')
            ->pluck('id');

        if ($cashAccounts->isNotEmpty()) {
            return $cashAccounts;
        }

        return AcAccount::where('account_type', 'asset')
            ->where('status', 'active')
            ->where(function ($query) {
                $query->where('account_selection_name', 'like', '%cash%')
                    ->orWhere('account_selection_name', 'like', '%bank%')
                    ->orWhere('account_name', 'like', '%Cash%')
                    ->orWhere('account_name', 'like', '%Bank%');
            })
            ->pluck('id');
    }

    private function netAmountForAccounts($accountIds, Carbon $dateFrom, Carbon $dateTo, string $normalBalance): float
    {
        if ($accountIds->isEmpty()) {
            return 0;
        }

        $baseQuery = AcTransaction::where('status', 'active')
            ->whereBetween('transaction_date', [$dateFrom->toDateString(), $dateTo->toDateString()]);

        $debitTotal = (float) (clone $baseQuery)
            ->whereIn('debit_account_id', $accountIds)
            ->sum('debit_amt');

        $creditTotal = (float) (clone $baseQuery)
            ->whereIn('credit_account_id', $accountIds)
            ->sum('credit_amt');

        return $normalBalance === 'credit'
            ? $creditTotal - $debitTotal
            : $debitTotal - $creditTotal;
    }

    private function dailyAccountingSeries(Carbon $startDate, Carbon $endDate, $revenueAccounts, $expenseAccounts, $cashAccounts): array
    {
        $labels = [];
        $revenue = [];
        $expenses = [];
        $cashFlow = [];

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dayStart = $date->copy()->startOfDay();
            $dayEnd = $date->copy()->endOfDay();

            $labels[] = $date->format('M d');
            $revenue[] = $this->netAmountForAccounts($revenueAccounts, $dayStart, $dayEnd, 'credit');
            $expenses[] = $this->netAmountForAccounts($expenseAccounts, $dayStart, $dayEnd, 'debit');
            $cashFlow[] = $this->cashFlowForPeriod($cashAccounts, $dayStart, $dayEnd);
        }

        return [
            'labels' => $labels,
            'revenue' => $revenue,
            'expenses' => $expenses,
            'cash_flow' => $cashFlow,
        ];
    }

    private function monthlyAccountingSeries(Carbon $startDate, Carbon $endDate, $revenueAccounts, $expenseAccounts, $cashAccounts): array
    {
        $labels = [];
        $revenue = [];
        $expenses = [];
        $cashFlow = [];

        for ($month = $startDate->copy(); $month->lte($endDate); $month->addMonth()) {
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();

            $labels[] = $month->format('M Y');
            $revenue[] = $this->netAmountForAccounts($revenueAccounts, $monthStart, $monthEnd, 'credit');
            $expenses[] = $this->netAmountForAccounts($expenseAccounts, $monthStart, $monthEnd, 'debit');
            $cashFlow[] = $this->cashFlowForPeriod($cashAccounts, $monthStart, $monthEnd);
        }

        return [
            'labels' => $labels,
            'revenue' => $revenue,
            'expenses' => $expenses,
            'cash_flow' => $cashFlow,
        ];
    }

    private function cashFlowForPeriod($cashAccounts, Carbon $dateFrom, Carbon $dateTo): float
    {
        return $this->netAmountForAccounts($cashAccounts, $dateFrom, $dateTo, 'debit');
    }

    public function dummy_index()
    {

        $recentCustomers = User::where('user_type', 3)->orderBy('id', 'desc')->skip(0)->limit(5)->get();
        $orderPayments = OrderPayment::orderBy('id', 'desc')->skip(0)->limit(5)->get();

        // for upper graph start
        $countOrders = array();
        for ($i = 0; $i <= 8; $i++) {
            $orderStartDate = date("Y-m", strtotime("-$i month", strtotime(date("Y-m")))) . "-01 00:00:00";
            $orderEndDate = date("Y-m-t", strtotime("-$i month", strtotime(date("Y-m")))) . " 23:59:59";
            $countOrders[$i] = Order::whereBetween('order_date', [$orderStartDate, $orderEndDate])->count();
        }

        $totalOrderAmount = array();
        for ($i = 0; $i <= 8; $i++) {
            $orderStartDate = date("Y-m", strtotime("-$i month", strtotime(date("Y-m")))) . "-01 00:00:00";
            $orderEndDate = date("Y-m-t", strtotime("-$i month", strtotime(date("Y-m")))) . " 23:59:59";
            $totalOrderAmount[$i] = Order::whereBetween('order_date', [$orderStartDate, $orderEndDate])->where('order_status', '!=', 4)->sum('total');
        }

        $todaysOrder = array();
        for ($i = 0; $i <= 8; $i++) {
            $orderPlaceDate = date("Y-m-d", strtotime("-$i day", strtotime(date("Y-m-d"))));
            $todaysOrder[$i] = Order::where('created_at', 'LIKE', $orderPlaceDate . '%')->count();
        }

        $registeredUsers = array();
        for ($i = 0; $i <= 8; $i++) {
            $orderStartDate = date("Y-m", strtotime("-$i month", strtotime(date("Y-m")))) . "-01 00:00:00";
            $orderEndDate = date("Y-m-t", strtotime("-$i month", strtotime(date("Y-m")))) . " 23:59:59";
            $registeredUsers[$i] = User::whereBetween('created_at', [$orderStartDate, $orderEndDate])->count();
        }
        // for upper graph end


        // all time best product graph start
        $queryStartDate = date("Y-m", strtotime("-6 month", strtotime(date("Y-m")))) . "-01 00:00:00";
        $queryEndDate = date("Y-m-d") . " 23:59:59";

        $bestSelling = DB::table('order_details')
            ->join('products', 'order_details.product_id', '=', 'products.id')
            ->selectRaw('products.name, SUM(order_details.qty) as total_qty')
            ->whereBetween('order_details.created_at', [$queryStartDate, $queryEndDate])
            ->groupBy('order_details.product_id')
            ->orderBy('total_qty', 'desc')
            ->skip(0)
            ->limit(3)
            ->get();
        // all time best product graph end


        // success and failed order ratio start
        $countOrdersRatioSuccess = array();
        $countOrdersRatioFailed = array();
        $countOrdersRatioDate = array();

        for ($i = 0; $i <= 9; $i++) {
            $orderRatioStartDate = date("Y-m", strtotime("-$i month", strtotime(date("Y-m")))) . "-01 00:00:00";
            $orderRatioEndDate = date("Y-m-t", strtotime("-$i month", strtotime(date("Y-m")))) . " 23:59:59";

            $countOrdersRatioDate[$i] = date("M-y", strtotime("-$i month", strtotime(date("Y-m"))));
            $countOrdersRatioSuccess[$i] = Order::whereBetween('order_date', [$orderRatioStartDate, $orderRatioEndDate])->where('order_status', 4)->count();
            $countOrdersRatioFailed[$i] = Order::whereBetween('order_date', [$orderRatioStartDate, $orderRatioEndDate])->where('order_status', '!=', 4)->count();
        }
        // success and failed order ratio end

        $thanaOrders = ShippingInfo::with('order')
            ->whereHas('order', function ($query) {
                $query->where('created_at', '>=', now()->subMonths(2));
            })
            ->select('thana', \DB::raw('COUNT(DISTINCT order_id) as order_count'))
            ->groupBy('thana')
            ->having('order_count', '>', 0)
            ->get();

        $cityOrders = ShippingInfo::with('order')
            ->whereHas('order', function ($query) {
                $query->where('created_at', '>=', now()->subMonths(2));
            })
            ->select('city', \DB::raw('COUNT(DISTINCT order_id) as order_count'))
            ->groupBy('city')
            ->having('order_count', '>', 0)
            ->get();

        $ordersBySource = Order::with('customerSourceType')
            ->select('customer_src_type_id', \DB::raw('COUNT(*) as order_count'))
            ->groupBy('customer_src_type_id')
            ->get();


        return view(
            'backend.accounts-dashboard',
            compact(
                'recentCustomers',
                'orderPayments',
                'countOrders',
                'totalOrderAmount',
                'todaysOrder',
                'registeredUsers',
                'bestSelling',
                'queryStartDate',
                'countOrdersRatioDate',
                'countOrdersRatioSuccess',
                'countOrdersRatioFailed',
                'thanaOrders',
                'cityOrders',
                'ordersBySource'
            )
        );
    }






    public function changePasswordPage()
    {
        return view('backend.change_password');
    }

    public function inventoryDashboardOverview(Request $request)
    {
        [$start, $end, $label] = $this->inventoryDashboardDateRange($request);

        $receivedPurchases = $this->receivedPurchaseQuery($start, $end);
        $purchaseReturns = $this->purchaseReturnQuery($start, $end);
        $sales = $this->salesQuery($start, $end);
        $salesReturns = $this->salesReturnQuery($start, $end);

        $purchaseTotal = (float) (clone $receivedPurchases)->sum('total');
        $purchaseReturnTotal = (float) (clone $purchaseReturns)->sum('total');
        $salesTotal = (float) (clone $sales)->sum('total');
        $salesReturnTotal = (float) (clone $salesReturns)->sum('total');
        $inventoryValue = $this->currentInventoryValue();
        $supplierSummary = app(SupplierTransactionService::class)->supplierDashboardSummary()['summary'] ?? [];
        $customerSummary = $this->customerRelationSummary();
        $cashBankBalance = $this->cashBankBalance();

        return response()->json([
            'filter' => [
                'label' => $label,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
            ],
            'cards' => [
                ['key' => 'inventory_value', 'label' => 'Current Inventory Value', 'value' => $inventoryValue, 'format' => 'money', 'tone' => 'primary'],
                ['key' => 'purchase_total', 'label' => 'Received Purchase', 'value' => $purchaseTotal, 'format' => 'money', 'tone' => 'success'],
                ['key' => 'purchase_returns', 'label' => 'Purchase Returns', 'value' => $purchaseReturnTotal, 'meta' => (clone $purchaseReturns)->count() . ' returns', 'format' => 'money', 'tone' => 'warning'],
                ['key' => 'net_purchase', 'label' => 'Net Purchase', 'value' => $purchaseTotal - $purchaseReturnTotal, 'format' => 'money', 'tone' => 'info'],
                ['key' => 'sales_total', 'label' => 'Sales Value', 'value' => $salesTotal, 'format' => 'money', 'tone' => 'success'],
                ['key' => 'sales_returns', 'label' => 'Sales Returns', 'value' => $salesReturnTotal, 'meta' => (clone $salesReturns)->count() . ' returns', 'format' => 'money', 'tone' => 'danger'],
                ['key' => 'supplier_payable', 'label' => 'Supplier Payable', 'value' => (float) ($supplierSummary['total_net_payable'] ?? 0), 'format' => 'money', 'tone' => 'danger'],
                ['key' => 'supplier_advance', 'label' => 'Supplier Advance', 'value' => (float) ($supplierSummary['total_advance'] ?? 0), 'format' => 'money', 'tone' => 'info'],
                ['key' => 'customer_receivable', 'label' => 'Customer Receivable', 'value' => (float) ($customerSummary['total_net_receivable'] ?? 0), 'format' => 'money', 'tone' => 'danger'],
                ['key' => 'customer_advance', 'label' => 'Customer Advance', 'value' => (float) ($customerSummary['total_advance'] ?? 0), 'format' => 'money', 'tone' => 'info'],
                ['key' => 'cash_bank', 'label' => 'Cash & Bank', 'value' => $cashBankBalance, 'format' => 'money', 'tone' => 'primary'],
                ['key' => 'present_status', 'label' => 'Present Status', 'value' => $this->inventoryPresentStatus($supplierSummary, $customerSummary, $cashBankBalance), 'format' => 'status', 'tone' => 'dark'],
            ],
            'pipeline' => [
                'pending_purchase_orders' => DB::table('product_purchase_orders')->where('status', 'active')->where('order_status', 'pending')->count(),
                'received_purchase_orders' => (clone $receivedPurchases)->count(),
                'pending_quotations' => DB::table('product_purchase_quotations')->where('is_ordered', 0)->count(),
                'active_warehouses' => DB::table('product_warehouses')->where('status', 'active')->count(),
                'active_suppliers' => DB::table('product_suppliers')->where('status', 'active')->count(),
            ],
        ]);
    }

    public function inventoryDashboardRelationships(Request $request)
    {
        $supplier = app(SupplierTransactionService::class)->supplierDashboardSummary()['summary'] ?? [];
        $customer = $this->customerRelationSummary();

        return response()->json([
            'supplier' => [
                ['label' => 'Purchase', 'value' => (float) ($supplier['total_purchase'] ?? 0)],
                ['label' => 'Return', 'value' => (float) ($supplier['total_return'] ?? 0)],
                ['label' => 'Paid', 'value' => (float) ($supplier['total_paid'] ?? 0)],
                ['label' => 'Old Due', 'value' => (float) ($supplier['total_old_due'] ?? 0)],
                ['label' => 'Advance', 'value' => (float) ($supplier['total_advance'] ?? 0)],
                ['label' => 'Net Payable', 'value' => (float) ($supplier['total_net_payable'] ?? 0)],
            ],
            'customer' => [
                ['label' => 'Order Due', 'value' => (float) ($customer['order_due'] ?? 0)],
                ['label' => 'Old Due', 'value' => (float) ($customer['old_due'] ?? 0)],
                ['label' => 'Received', 'value' => (float) ($customer['total_received'] ?? 0)],
                ['label' => 'Advance', 'value' => (float) ($customer['total_advance'] ?? 0)],
                ['label' => 'Net Receivable', 'value' => (float) ($customer['total_net_receivable'] ?? 0)],
            ],
        ]);
    }

    public function inventoryDashboardTrends(Request $request)
    {
        [$start, $end] = $this->inventoryDashboardDateRange($request);

        $daily = DB::table('product_purchase_orders')
            ->selectRaw('DATE(date) as period, COUNT(*) as count, COALESCE(SUM(total), 0) as total')
            ->where('status', 'active')
            ->where('order_status', 'received')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        return response()->json([
            'labels' => $daily->pluck('period')->map(fn($date) => Carbon::parse($date)->format('M d'))->values(),
            'counts' => $daily->pluck('count')->map(fn($value) => (int) $value)->values(),
            'values' => $daily->pluck('total')->map(fn($value) => (float) $value)->values(),
        ]);
    }

    public function inventoryDashboardRecent(Request $request)
    {
        [$start, $end] = $this->inventoryDashboardDateRange($request);

        $purchases = ProductPurchaseOrder::query()
            ->leftJoin('product_suppliers', 'product_purchase_orders.product_supplier_id', '=', 'product_suppliers.id')
            ->select('product_purchase_orders.id', 'product_purchase_orders.code', 'product_purchase_orders.date', 'product_purchase_orders.subtotal', 'product_purchase_orders.other_charge_amount', 'product_purchase_orders.calculated_discount_amount', 'product_purchase_orders.total', 'product_purchase_orders.order_status', 'product_suppliers.name as supplier_name')
            ->where('product_purchase_orders.status', 'active')
            ->where('product_purchase_orders.order_status', 'received')
            ->whereBetween('product_purchase_orders.date', [$start->toDateString(), $end->toDateString()])
            ->orderByDesc('product_purchase_orders.date')
            ->limit(10)
            ->get();

        $returns = DB::table('product_purchase_returns')
            ->leftJoin('product_suppliers', 'product_purchase_returns.product_supplier_id', '=', 'product_suppliers.id')
            ->select('product_purchase_returns.code', 'product_purchase_returns.date', 'product_purchase_returns.total', 'product_purchase_returns.order_status', 'product_suppliers.name as supplier_name')
            ->where('product_purchase_returns.status', 'active')
            ->whereBetween('product_purchase_returns.date', [$start->toDateString(), $end->toDateString()])
            ->orderByDesc('product_purchase_returns.date')
            ->limit(8)
            ->get();

        return response()->json([
            'purchases' => $purchases,
            'returns' => $returns,
        ]);
    }

    private function inventoryDashboardDateRange(Request $request): array
    {
        $preset = $request->query('preset', 'today');
        $now = Carbon::now('Asia/Dhaka');

        if ($preset === 'lifetime') {
            $start = Carbon::create(1970, 1, 1, 0, 0, 0, 'Asia/Dhaka');
            $end = $now->copy()->endOfDay();
            $label = 'Lifetime';
        } elseif ($preset === 'yesterday') {
            $start = $now->copy()->subDay()->startOfDay();
            $end = $now->copy()->subDay()->endOfDay();
            $label = 'Yesterday';
        } elseif ($preset === 'last_week') {
            $start = $now->copy()->subWeek()->startOfWeek();
            $end = $now->copy()->subWeek()->endOfWeek();
            $label = 'Last Week';
        } elseif ($preset === 'this_month') {
            $start = $now->copy()->startOfMonth();
            $end = $now->copy()->endOfMonth();
            $label = 'This Month';
        } elseif ($preset === 'custom') {
            $start = Carbon::parse($request->query('start_date', $now->toDateString()), 'Asia/Dhaka')->startOfDay();
            $end = Carbon::parse($request->query('end_date', $now->toDateString()), 'Asia/Dhaka')->endOfDay();
            $label = 'Custom';
        } else {
            $start = $now->copy()->startOfDay();
            $end = $now->copy()->endOfDay();
            $label = 'Today';
        }

        return [$start, $end, $label];
    }

    private function receivedPurchaseQuery(Carbon $start, Carbon $end)
    {
        return DB::table('product_purchase_orders')
            ->where('status', 'active')
            ->where('order_status', 'received')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()]);
    }

    private function purchaseReturnQuery(Carbon $start, Carbon $end)
    {
        return DB::table('product_purchase_returns')
            ->where('status', 'active')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()]);
    }

    private function salesQuery(Carbon $start, Carbon $end)
    {
        return DB::table('product_orders')
            ->where('status', 'active')
            ->whereBetween('sale_date', [$start->toDateString(), $end->toDateString()]);
    }

    private function salesReturnQuery(Carbon $start, Carbon $end)
    {
        return DB::table('product_order_returns')
            ->where('status', 'active')
            ->where('return_status', 'approved')
            ->whereBetween('return_date', [$start->toDateString(), $end->toDateString()]);
    }

    private function currentInventoryValue(): float
    {
        if (Schema::hasTable('product_purchase_order_product_units')) {
            $unitValue = (float) DB::table('product_purchase_order_product_units')
                ->where('unit_status', 'instock')
                ->sum('price');

            if ($unitValue > 0) {
                return $unitValue;
            }
        }

        if (!Schema::hasTable('product_stocks')) {
            return 0;
        }

        return (float) DB::table('product_stocks')
            ->where('status', 'active')
            ->selectRaw('COALESCE(SUM(COALESCE(qty, 0) * COALESCE(purchase_price, 0)), 0) as value')
            ->value('value');
    }

    private function customerRelationSummary(): array
    {
        $orderDue = (float) DB::table('product_orders')
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('order_source')
                    ->orWhere('order_source', '!=', 'ecommerce');
            })
            ->sum('due_amount');
        $oldDue = Schema::hasTable('customer_opening_balances')
            ? (float) DB::table('customer_opening_balances')->where('entry_type', 'due')->where('status', 'active')->sum('remaining_amount')
            : 0;
        $openingAdvance = Schema::hasTable('customer_opening_balances')
            ? (float) DB::table('customer_opening_balances')->where('entry_type', 'advance')->where('status', 'active')->sum('remaining_amount')
            : 0;
        $advancePayments = (float) DB::table('db_customer_payments')->where('status', 'active')->where('payment_type', 'advance')->sum('payment');
        $advanceOut = abs((float) DB::table('db_customer_payments')->where('status', 'active')->whereIn('payment_type', ['adjustment', 'refund'])->sum('payment'));
        $received = (float) DB::table('db_customer_payments')->where('status', 'active')->where('payment_type', 'received')->sum('payment');
        $advance = max(0, $openingAdvance + $advancePayments - $advanceOut);

        return [
            'order_due' => $orderDue,
            'old_due' => $oldDue,
            'total_received' => $received,
            'total_advance' => $advance,
            'total_receivable' => $orderDue + $oldDue,
            'total_net_receivable' => ($orderDue + $oldDue) - $advance,
        ];
    }

    private function cashBankBalance(): float
    {
        $accountIds = AcAccount::whereIn('account_selection_name', ['cash_bank', 'cash_on_hand'])
            ->orWhere('account_name', 'like', '%Cash%')
            ->orWhere('account_name', 'like', '%Bank%')
            ->pluck('id');

        if ($accountIds->isEmpty()) {
            return 0;
        }

        $debits = (float) AcTransaction::whereIn('debit_account_id', $accountIds)->where('status', 'active')->sum('debit_amt');
        $credits = (float) AcTransaction::whereIn('credit_account_id', $accountIds)->where('status', 'active')->sum('credit_amt');

        return $debits - $credits;
    }

    private function inventoryPresentStatus(array $supplier, array $customer, float $cashBankBalance): array
    {
        $supplierPayable = (float) ($supplier['total_net_payable'] ?? 0);
        $customerReceivable = (float) ($customer['total_net_receivable'] ?? 0);

        if ($supplierPayable > $customerReceivable && $supplierPayable > 0) {
            return ['text' => 'Supplier payable needs attention', 'amount' => $supplierPayable, 'tone' => 'danger'];
        }

        if ($customerReceivable > 0) {
            return ['text' => 'Customer collection opportunity', 'amount' => $customerReceivable, 'tone' => 'warning'];
        }

        if ($cashBankBalance > 0) {
            return ['text' => 'Cash position is positive', 'amount' => $cashBankBalance, 'tone' => 'success'];
        }

        return ['text' => 'No major due pressure', 'amount' => 0, 'tone' => 'success'];
    }

    public function changePassword(Request $request)
    {

        $currentPassword = $request->prev_password;
        $newPassword = $request->new_password;
        $userId = $request->user_id;
        $userInfo = User::where('id', $userId)->first();

        if (Hash::check($currentPassword, $userInfo->password)) {
            User::where('id', $userId)->update([
                'name' => $request->name,
                'password' => Hash::make($newPassword),
            ]);

            Toastr::success('Password Changed', 'Successfully');
            return back();
        } else {
            Toastr::error('Current Password is Wrong', 'Failed');
            return back();
        }
    }

    public function clearCache()
    {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('view:clear');
        Artisan::call('route:clear');

        Toastr::success('Cache Cleared', 'Successfully');
        return back();
    }

    public function viewPaymentHistory(Request $request)
    {
        if ($request->ajax()) {
            $data = OrderPayment::orderBy('id', 'desc')->get();
            return Datatables::of($data)
                ->addIndexColumn()
                ->make(true);
        }
        return view('backend.payment_histories');
    }
}
