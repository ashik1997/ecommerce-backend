<?php

namespace App\Http\Controllers\Report\Actions;

use App\Models\ProductOrderProduct;
use App\Http\Controllers\Inventory\Models\ProductPurchaseOrderProduct;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SingleProductSaleReportAction extends ReportAction
{
    public function run(array $filters): array
    {
        if (empty($filters['product_id'])) {
            return [
                'data' => [
                    'sales_data' => [],
                    'purchase_data' => [],
                    'summary' => [],
                ],
                'summary' => [],
            ];
        }

        $productId = $filters['product_id'];
        $dateFrom = !empty($filters['date_from']) ? Carbon::parse($filters['date_from'])->startOfDay() : null;
        $dateTo = !empty($filters['date_to']) ? Carbon::parse($filters['date_to'])->endOfDay() : null;

        /*
         * Profit is saved on each sold product row.
         * Do not subtract purchase entries from sales here, because purchase date
         * and sale date are different activities. For product sale profit, the
         * trusted source is product_order_products.net_profit.
         */
        $salesQuery = ProductOrderProduct::query()
            ->from('product_order_products as pop')
            ->join('product_orders as po', 'pop.product_order_id', '=', 'po.id')
            ->where('pop.product_id', $productId)
            ->where('pop.status', 'active')
            ->where('po.status', 'active')
            ->whereIn('po.order_status', ['invoiced', 'delivered']);

        if ($dateFrom) {
            $salesQuery->whereRaw('COALESCE(po.sale_date, DATE(po.created_at)) >= ?', [$dateFrom->toDateString()]);
        }
        if ($dateTo) {
            $salesQuery->whereRaw('COALESCE(po.sale_date, DATE(po.created_at)) <= ?', [$dateTo->toDateString()]);
        }

        $salesItems = $salesQuery->select(
                DB::raw('COALESCE(po.sale_date, DATE(po.created_at)) as date'),
                'po.order_code',
                'pop.qty',
                'pop.sale_price',
                'pop.discount_amount',
                'pop.total_price',
                'pop.purchase_price',
                'pop.net_profit'
            )
            ->orderByDesc(DB::raw('COALESCE(po.sale_date, DATE(po.created_at))'))
            ->get();

        $salesData = $salesItems->map(function($item) {
            $qty = (float) ($item->qty ?? 0);
            $total = (float) ($item->total_price ?? 0);
            $purchaseCost = $qty * (float) ($item->purchase_price ?? 0);

            return [
                'date' => $item->date,
                'order_code' => $item->order_code,
                'qty' => $qty,
                'total' => $total,
                'purchase_cost' => $purchaseCost,
                'profit' => (float) ($item->net_profit ?? 0),
            ];
        })->toArray();

        // Purchase data
        $purchaseQuery = ProductPurchaseOrderProduct::where('product_id', $productId)
            ->join('product_purchase_orders', 'product_purchase_order_products.product_purchase_order_id', '=', 'product_purchase_orders.id')
            ->where('product_purchase_orders.status', 'active')
            ->where('product_purchase_order_products.status', 'active')
            ->where('product_purchase_orders.order_status', 'received');

        if ($dateFrom) {
            $purchaseQuery->where('product_purchase_orders.date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $purchaseQuery->where('product_purchase_orders.date', '<=', $dateTo);
        }

        $purchaseItems = $purchaseQuery->select(
                'product_purchase_orders.code',
                'product_purchase_orders.date',
                'product_purchase_order_products.qty',
                'product_purchase_order_products.purchase_price'
            )
            ->orderBy('product_purchase_orders.date', 'desc')
            ->get();

        $purchaseData = $purchaseItems->map(function($item) {
            return [
                'date' => $item->date,
                'purchase_code' => $item->code,
                'qty' => $item->qty,
                'total' => $item->qty * ($item->purchase_price ?? 0),
            ];
        })->toArray();

        // Calculate summary metrics from sold product rows.
        $totalSold = array_sum(array_column($salesData, 'total'));
        $totalPurchaseActivity = array_sum(array_column($purchaseData, 'total'));
        $soldQty = array_sum(array_column($salesData, 'qty'));
        $soldPurchaseCost = array_sum(array_column($salesData, 'purchase_cost'));
        $totalDiscounts = $salesItems->sum('discount_amount') ?? 0;
        $profitLoss = array_sum(array_column($salesData, 'profit'));

        $productSummary = [
            'total_sold' => $totalSold,
            'total_purchased' => $totalPurchaseActivity,
            'sold_qty' => $soldQty,
            'purchase_value' => $soldPurchaseCost,
            'profit_loss' => $profitLoss,
            'total_discounts' => $totalDiscounts,
            'is_profit' => $profitLoss >= 0,
        ];

        $summary = [
            'sold_qty' => $soldQty,
            'sale_total' => $totalSold,
            'sold_purchase_cost' => $soldPurchaseCost,
            'net_profit' => $profitLoss,
            'purchase_activity_value' => $totalPurchaseActivity,
        ];

        return [
            'data' => [
                'sales_data' => $salesData,
                'purchase_data' => $purchaseData,
                'summary' => $productSummary,
            ],
            'summary' => $summary,
        ];
    }

    public function getTitle(): string
    {
        return 'Single Product Sale Report';
    }

    public function getCsvHeaders(): array
    {
        return ['Type', 'Date', 'Reference', 'Quantity', 'Sale Total', 'Purchase Cost', 'Profit'];
    }

    public function formatForCsv(array $data): array
    {
        $rows = [];
        
        // Add sales data
        foreach (($data['sales_data'] ?? []) as $item) {
            $rows[] = [
                'Sale',
                $item['date'] ?? '',
                $item['order_code'] ?? '',
                $item['qty'] ?? 0,
                $item['total'] ?? 0,
                $item['purchase_cost'] ?? 0,
                $item['profit'] ?? 0,
            ];
        }
        
        // Add purchase data
        foreach (($data['purchase_data'] ?? []) as $item) {
            $rows[] = [
                'Purchase',
                $item['date'] ?? '',
                $item['purchase_code'] ?? '',
                $item['qty'] ?? 0,
                $item['total'] ?? 0,
                '',
                '',
            ];
        }
        
        return $rows;
    }

    public function getFiltersConfig(): array
    {
        return array_merge(parent::getFiltersConfig(), [
            [
                'type' => 'select',
                'name' => 'product_id',
                'label' => 'Product',
                'required' => true,
            ],
        ]);
    }
}
