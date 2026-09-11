<?php

namespace App\Http\Controllers\SrManagement;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\CommissionSettlement;
use App\Models\SalesCommissionEntry;
use App\Models\ProductOrder;
use App\Models\OrderProfitCost;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CommissionReportController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $entryQuery = $this->filteredEntries($filters);
        $settlementQuery = $this->filteredSettlements($filters);

        $summary = [
            'earned' => (clone $entryQuery)->whereIn('status', ['pending', 'approved', 'settled', 'paid', 'partially_paid'])->sum('commission_amount'),
            'pending' => (clone $entryQuery)->where('status', 'pending')->sum('commission_amount'),
            'approved' => (clone $entryQuery)->where('status', 'approved')->sum('commission_amount'),
            'settled' => (clone $entryQuery)->whereIn('status', ['settled', 'paid', 'partially_paid'])->sum('commission_amount'),
            'paid' => (clone $settlementQuery)->whereIn('status', ['paid', 'partially_paid'])->sum('paid_amount'),
            'due' => 0,
            'reversed' => (clone $entryQuery)->where('status', 'reversed')->sum('commission_amount'),
        ];
        $summary['due'] = max(0, $summary['earned'] - $summary['paid']);

        $byPerson = $this->personSummary($filters);
        $profitSummary = $this->profitImpactSummary($filters);
        $recentEntries = (clone $entryQuery)->with(['order', 'user', 'affiliate'])->latest()->limit(50)->get();
        $recentSettlements = (clone $settlementQuery)->with(['user', 'affiliate'])->latest()->limit(30)->get();

        $users = User::orderBy('name')->limit(500)->get(['id', 'name']);
        $affiliates = Affiliate::orderBy('name')->get(['id', 'name', 'code']);

        return view('backend.sr_management.commission_reports.index', compact(
            'summary', 'byPerson', 'profitSummary', 'recentEntries', 'recentSettlements', 'users', 'affiliates'
        ));
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);
        $entries = $this->filteredEntries($filters)->with(['order', 'user', 'affiliate'])->latest()->get();

        $filename = 'commission-report-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($entries) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Order ID', 'Order Code', 'Type', 'Person', 'Order Gross Profit', 'Order Net Profit', 'Base', 'Base Amount', 'Commission Type', 'Commission Value', 'Commission Amount', 'Status']);

            foreach ($entries as $entry) {
                $person = $entry->commission_for === 'salesman'
                    ? optional($entry->user)->name
                    : trim((optional($entry->affiliate)->name ?: '') . ' ' . (optional($entry->affiliate)->code ? '(' . optional($entry->affiliate)->code . ')' : ''));

                fputcsv($out, [
                    optional($entry->created_at)->format('Y-m-d'),
                    $entry->product_order_id,
                    optional($entry->order)->order_code,
                    $entry->commission_for,
                    $person,
                    optional($entry->order)->gross_profit,
                    optional($entry->order)->net_profit,
                    $entry->commission_base,
                    $entry->base_amount,
                    $entry->commission_type,
                    $entry->commission_value,
                    $entry->commission_amount,
                    $entry->status,
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    protected function filters(Request $request): array
    {
        return [
            'from' => $request->input('from'),
            'to' => $request->input('to'),
            'commission_for' => $request->input('commission_for'),
            'status' => $request->input('status'),
            'user_id' => $request->input('user_id'),
            'affiliate_id' => $request->input('affiliate_id'),
        ];
    }

    protected function filteredEntries(array $filters)
    {
        return SalesCommissionEntry::query()
            ->when($filters['from'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['to'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->when($filters['commission_for'] ?? null, fn ($q, $v) => $q->where('commission_for', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['user_id'] ?? null, fn ($q, $v) => $q->where('user_id', $v))
            ->when($filters['affiliate_id'] ?? null, fn ($q, $v) => $q->where('affiliate_id', $v));
    }

    protected function filteredSettlements(array $filters)
    {
        return CommissionSettlement::query()
            ->when($filters['from'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['to'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->when($filters['commission_for'] ?? null, fn ($q, $v) => $q->where('settlement_for', $v))
            ->when($filters['user_id'] ?? null, fn ($q, $v) => $q->where('user_id', $v))
            ->when($filters['affiliate_id'] ?? null, fn ($q, $v) => $q->where('affiliate_id', $v));
    }


    protected function profitImpactSummary(array $filters): array
    {
        $entries = $this->filteredEntries($filters)
            ->whereIn('status', ['pending', 'approved', 'settled', 'paid', 'partially_paid'])
            ->pluck('product_order_id')
            ->filter()
            ->unique()
            ->values();

        if ($entries->isEmpty()) {
            return [
                'orders' => 0,
                'gross_profit' => 0,
                'commission_cost' => 0,
                'direct_expense_total' => 0,
                'contribution_profit' => 0,
                'management_net_profit' => 0,
            ];
        }

        $orderQuery = ProductOrder::whereIn('id', $entries);

        $grossProfit = (clone $orderQuery)->sum('gross_profit');
        $netProfit = (clone $orderQuery)->sum('net_profit');
        $commissionCost = (clone $this->filteredEntries($filters))
            ->whereIn('status', ['pending', 'approved', 'settled', 'paid', 'partially_paid'])
            ->sum('commission_amount');

        $directExpenseTotal = $commissionCost;
        if (Schema::hasTable('order_profit_costs')) {
            $directExpenseTotal = OrderProfitCost::whereIn('product_order_id', $entries)
                ->where('status', 'active')
                ->where('is_estimated', false)
                ->sum('amount');
        }

        return [
            'orders' => $entries->count(),
            'gross_profit' => (float) $grossProfit,
            'commission_cost' => (float) $commissionCost,
            'direct_expense_total' => (float) $directExpenseTotal,
            'contribution_profit' => (float) $netProfit,
            'management_net_profit' => (float) $netProfit,
        ];
    }

    protected function personSummary(array $filters)
    {
        $query = $this->filteredEntries($filters)->whereIn('status', ['pending', 'approved', 'settled', 'paid', 'partially_paid']);

        return $query->with(['user', 'affiliate'])
            ->selectRaw('commission_for, user_id, affiliate_id, SUM(base_amount) as base_total, SUM(commission_amount) as commission_total, COUNT(*) as entries_count')
            ->groupBy('commission_for', 'user_id', 'affiliate_id')
            ->orderByDesc('commission_total')
            ->limit(100)
            ->get();
    }
}
