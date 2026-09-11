<?php

namespace App\Console\Commands;

use App\Models\ProductOrder;
use App\Services\Commission\OrderCommissionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CommissionBackfillOrdersCommand extends Command
{
    protected $signature = 'commission:backfill-orders
        {--from= : Order created_at lower date, example 2026-06-01}
        {--to= : Order created_at upper date, example 2026-06-30}
        {--status=invoiced,delivered,accepted,processing : Comma separated eligible order statuses}
        {--order-id=* : Specific product_orders.id values}
        {--limit=0 : Maximum orders to process, 0 means no limit}
        {--dry-run : Preview eligible orders without writing commission entries}';

    protected $description = 'Backfill/recalculate commission entries for existing eligible product orders.';

    public function handle(OrderCommissionService $commissionService): int
    {
        $requiredTables = ['product_orders', 'sales_commission_entries', 'sales_commission_rules', 'affiliates'];
        foreach ($requiredTables as $table) {
            if (! Schema::hasTable($table)) {
                $this->error("Missing table: {$table}. Run migrations first.");
                return self::FAILURE;
            }
        }

        foreach (['salesman_id', 'affiliate_id', 'affiliate_code'] as $column) {
            if (! Schema::hasColumn('product_orders', $column)) {
                $this->error("Missing product_orders.{$column}. Run commission migrations first.");
                return self::FAILURE;
            }
        }

        $dryRun = (bool) $this->option('dry-run');
        $statuses = collect(explode(',', (string) $this->option('status')))
            ->map(fn ($status) => trim($status))
            ->filter()
            ->values()
            ->all();

        $query = ProductOrder::query()
            ->where(function ($q) {
                $q->whereNotNull('salesman_id')
                    ->orWhereNotNull('affiliate_id')
                    ->orWhere(function ($nested) {
                        $nested->whereNotNull('affiliate_code')->where('affiliate_code', '!=', '');
                    });
            });

        if (Schema::hasColumn('product_orders', 'order_status') && $statuses) {
            $query->whereIn('order_status', $statuses);
        }

        if ($this->option('from')) {
            $query->whereDate('created_at', '>=', $this->option('from'));
        }

        if ($this->option('to')) {
            $query->whereDate('created_at', '<=', $this->option('to'));
        }

        $orderIds = (array) $this->option('order-id');
        if (! empty($orderIds)) {
            $query->whereIn('id', $orderIds);
        }

        $limit = max(0, (int) $this->option('limit'));
        if ($limit > 0) {
            $query->limit($limit);
        }

        $eligibleCount = (clone $query)->count();
        $this->info('Eligible orders: ' . $eligibleCount);

        if ($dryRun) {
            $this->table(['Metric', 'Value'], [
                ['Eligible Orders', $eligibleCount],
                ['Statuses', implode(', ', $statuses)],
                ['From', $this->option('from') ?: '-'],
                ['To', $this->option('to') ?: '-'],
                ['Limit', $limit ?: 'No limit'],
            ]);
            $this->warn('Dry run only. No commission entries were created or recalculated.');
            return self::SUCCESS;
        }

        if ($eligibleCount === 0) {
            $this->info('No eligible orders found.');
            return self::SUCCESS;
        }

        if (! $this->confirm('This will calculate/recalculate commission for eligible orders. Continue?', false)) {
            $this->warn('Cancelled.');
            return self::SUCCESS;
        }

        $processed = 0;
        $failed = 0;

        $query->orderBy('id')->chunkById(100, function ($orders) use (&$processed, &$failed, $commissionService, $limit) {
            foreach ($orders as $order) {
                if ($limit > 0 && $processed >= $limit) {
                    return false;
                }

                try {
                    $commissionService->recalculateForOrder($order);
                    $processed++;
                    if ($processed % 50 === 0) {
                        $this->line("Processed {$processed} orders...");
                    }
                } catch (\Throwable $exception) {
                    $failed++;
                    $this->error("Order #{$order->id} failed: " . $exception->getMessage());
                    DB::table('sales_commission_entries')->where('product_order_id', $order->id)->update([
                        'note' => DB::raw("CONCAT(COALESCE(note, ''), '\nBackfill failed: " . addslashes(substr($exception->getMessage(), 0, 180)) . "')"),
                    ]);
                }
            }

            return true;
        });

        $this->info("Backfill completed. Processed: {$processed}, Failed: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
