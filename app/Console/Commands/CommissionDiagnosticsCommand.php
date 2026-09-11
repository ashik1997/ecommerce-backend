<?php

namespace App\Console\Commands;

use App\Models\ProductOrder;
use App\Models\SalesCommissionEntry;
use App\Services\Commission\OrderCommissionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class CommissionDiagnosticsCommand extends Command
{
    protected $signature = 'commission:diagnose
        {--from= : Order created_at lower date}
        {--to= : Order created_at upper date}
        {--fix-summary : Refresh commission/profit summaries for orders with commission entries}';

    protected $description = 'Run health checks for Sales Commission & Affiliate module.';

    public function handle(OrderCommissionService $commissionService): int
    {
        $tables = [
            'affiliates',
            'sales_commission_rules',
            'sales_commission_entries',
            'commission_settlements',
            'commission_settlement_items',
            'commission_adjustments',
            'order_profit_costs',
        ];

        $tableRows = [];
        $missingTables = [];
        foreach ($tables as $table) {
            $exists = Schema::hasTable($table);
            if (! $exists) {
                $missingTables[] = $table;
            }
            $tableRows[] = [$table, $exists ? 'OK' : 'MISSING'];
        }

        $this->info('Commission module table check');
        $this->table(['Table', 'Status'], $tableRows);

        if (! empty($missingTables)) {
            $this->error('Some commission tables are missing. Run migrations before using the module.');
            return self::FAILURE;
        }

        $orderColumns = [
            'salesman_id',
            'affiliate_id',
            'affiliate_code',
            'commission_status',
            'salesman_commission_total',
            'affiliate_commission_total',
            'commission_total',
            'direct_expense_total',
            'contribution_profit',
            'management_net_profit',
        ];

        $columnRows = [];
        foreach ($orderColumns as $column) {
            $columnRows[] = [$column, Schema::hasColumn('product_orders', $column) ? 'OK' : 'MISSING'];
        }
        $this->info('product_orders column check');
        $this->table(['Column', 'Status'], $columnRows);

        $orderQuery = ProductOrder::query();
        if ($this->option('from')) {
            $orderQuery->whereDate('created_at', '>=', $this->option('from'));
        }
        if ($this->option('to')) {
            $orderQuery->whereDate('created_at', '<=', $this->option('to'));
        }

        $ordersWithReference = (clone $orderQuery)->where(function ($q) {
            $q->whereNotNull('salesman_id')
                ->orWhereNotNull('affiliate_id')
                ->orWhere(function ($nested) {
                    $nested->whereNotNull('affiliate_code')->where('affiliate_code', '!=', '');
                });
        })->count();

        $ordersWithCommission = SalesCommissionEntry::query()
            ->when($this->option('from'), fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($this->option('to'), fn ($q, $date) => $q->whereDate('created_at', '<=', $date))
            ->distinct('product_order_id')
            ->count('product_order_id');

        $statusSummary = SalesCommissionEntry::query()
            ->selectRaw('status, COUNT(*) as total, SUM(commission_amount) as amount')
            ->groupBy('status')
            ->orderBy('status')
            ->get()
            ->map(fn ($row) => [$row->status ?: '-', $row->total, number_format((float) $row->amount, 2)])
            ->all();

        $this->info('Commission health summary');
        $this->table(['Metric', 'Value'], [
            ['Orders with salesman/affiliate reference', $ordersWithReference],
            ['Orders with commission entries', $ordersWithCommission],
            ['Unpaid approved entries', SalesCommissionEntry::where('status', 'approved')->count()],
            ['Requires review orders', ProductOrder::where('commission_status', 'requires_review')->count()],
        ]);

        $this->info('Commission entry status summary');
        $this->table(['Status', 'Entries', 'Amount'], $statusSummary ?: [['-', 0, '0.00']]);

        if ($this->option('fix-summary')) {
            $this->warn('Refreshing order commission/profit summaries...');
            $ids = SalesCommissionEntry::query()
                ->whereNotNull('product_order_id')
                ->distinct()
                ->pluck('product_order_id');

            $fixed = 0;
            foreach ($ids as $orderId) {
                $order = ProductOrder::find($orderId);
                if ($order) {
                    $commissionService->refreshForOrder($order);
                    $fixed++;
                }
            }
            $this->info("Summary refresh completed for {$fixed} orders.");
        }

        return self::SUCCESS;
    }
}
