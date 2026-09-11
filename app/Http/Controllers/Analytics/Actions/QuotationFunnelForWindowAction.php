<?php

namespace App\Http\Controllers\Analytics\Actions;

use App\Models\ProductOrder;
use App\Models\ProductOrderQuotation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Sales funnel metrics for product quotations in a date window.
 *
 * Uses Eloquent so the model's configured application connection is respected.
 *
 * Activity date: COALESCE(sale_date, DATE(created_at)) on product_order_quotations.
 *
 * "Converted" = not canceled and (order_status = 'invoiced' OR an active product_order references the quotation).
 *
 * "Pending" / "In review" = that order_status but not effectively converted (covers stuck status when an order exists).
 *
 * "Lost" = order_status = 'canceled'.
 */
class QuotationFunnelForWindowAction
{
    /**
     * @param  array{start: Carbon, end: Carbon}  $window
     * @return array<string, array{count: int, value: float}|array{count: int, value: float, pct_of_created: float}>
     */
    public function execute(array $window): array
    {
        $start = $window['start']->toDateString();
        $end = $window['end']->toDateString();

        $created = $this->baseQuotationWindow($start, $end)
            ->selectRaw('COUNT(*) as c')
            ->selectRaw('COALESCE(SUM(product_order_quotations.total), 0) as v')
            ->first();

        $converted = $this->baseQuotationWindow($start, $end)
            ->where('product_order_quotations.order_status', '!=', 'canceled')
            ->where(function ($q) {
                $this->whereQuotedAsInvoicedOrLinkedOrder($q);
            })
            ->selectRaw('COUNT(*) as c')
            ->selectRaw('COALESCE(SUM(product_order_quotations.total), 0) as v')
            ->first();

        $pending = $this->baseQuotationWindow($start, $end)
            ->where('product_order_quotations.order_status', 'pending');
        $this->whereQuotationNotEffectivelyConverted($pending);
        $pending = $pending
            ->selectRaw('COUNT(*) as c')
            ->selectRaw('COALESCE(SUM(product_order_quotations.total), 0) as v')
            ->first();

        $inReview = $this->baseQuotationWindow($start, $end)
            ->where('product_order_quotations.order_status', 'in_review');
        $this->whereQuotationNotEffectivelyConverted($inReview);
        $inReview = $inReview
            ->selectRaw('COUNT(*) as c')
            ->selectRaw('COALESCE(SUM(product_order_quotations.total), 0) as v')
            ->first();

        $lost = $this->baseQuotationWindow($start, $end)
            ->where('product_order_quotations.order_status', 'canceled')
            ->selectRaw('COUNT(*) as c')
            ->selectRaw('COALESCE(SUM(product_order_quotations.total), 0) as v')
            ->first();

        $createdCount = (int) ($created->c ?? 0);
        $createdValue = (float) ($created->v ?? 0);
        $denom = max($createdCount, 1);

        return [
            'created' => [
                'count' => $createdCount,
                'value' => round($createdValue, 2),
            ],
            'pending' => $this->bucket($pending, $denom),
            'in_review' => $this->bucket($inReview, $denom),
            'converted' => $this->bucket($converted, $denom),
            'lost' => $this->bucket($lost, $denom),
        ];
    }

    protected function baseQuotationWindow(string $start, string $end): Builder
    {
        $t = (new ProductOrderQuotation())->getTable();

        return ProductOrderQuotation::query()
            ->where("{$t}.status", 'active')
            ->whereRaw("COALESCE({$t}.sale_date, DATE({$t}.created_at)) >= ?", [$start])
            ->whereRaw("COALESCE({$t}.sale_date, DATE({$t}.created_at)) <= ?", [$end]);
    }

    /** Invoiced OR linked active order. */
    protected function whereQuotedAsInvoicedOrLinkedOrder(Builder $q): void
    {
        $qt = (new ProductOrderQuotation())->getTable();
        $po = (new ProductOrder())->getTable();

        $q->where("{$qt}.order_status", 'invoiced')
            ->orWhereExists(function ($sub) use ($qt, $po) {
                $sub->from($po)
                    ->selectRaw('1')
                    ->whereColumn("{$po}.product_order_quotation_id", "{$qt}.id")
                    ->where("{$po}.status", 'active');
            });
    }

    /**
     * NOT (treated as invoiced OR linked active order).
     * Uses whereNotExists instead of whereNot(closure) so SQL is not compiled as a bogus `not` column.
     * De Morgan: NOT (invoiced OR exists) => (order_status <> 'invoiced') AND NOT EXISTS (…); for pending/in_review rows the first clause is already true.
     */
    protected function whereQuotationNotEffectivelyConverted(Builder $query): void
    {
        $qt = (new ProductOrderQuotation())->getTable();
        $po = (new ProductOrder())->getTable();

        $query->where("{$qt}.order_status", '<>', 'invoiced')
            ->whereNotExists(function ($sub) use ($qt, $po) {
                $sub->from($po)
                    ->selectRaw('1')
                    ->whereColumn("{$po}.product_order_quotation_id", "{$qt}.id")
                    ->where("{$po}.status", 'active');
            });
    }

    /**
     * @param  object{c?: mixed, v?: mixed}  $row
     * @return array{count: int, value: float, pct_of_created: float}
     */
    protected function bucket(object $row, int $denom): array
    {
        $count = (int) ($row->c ?? 0);
        $value = (float) ($row->v ?? 0);

        return [
            'count' => $count,
            'value' => round($value, 2),
            'pct_of_created' => round(($count / $denom) * 100, 1),
        ];
    }
}
