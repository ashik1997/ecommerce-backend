<?php

namespace App\Services\Commission;

use App\Models\Affiliate;
use App\Models\CommissionAdjustment;
use App\Models\ProductOrder;
use App\Models\OrderProfitCost;
use App\Models\SalesCommissionEntry;
use App\Models\SalesCommissionRule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OrderCommissionService
{
    public function calculateForOrder(ProductOrder $order): void
    {
        DB::transaction(function () use ($order) {
            $order = ProductOrder::lockForUpdate()->find($order->id);
            if (! $order) {
                return;
            }

            // Recalculation is safe for unpaid/unsettled entries. Paid entries are preserved and adjusted on reversal.
            SalesCommissionEntry::where('product_order_id', $order->id)
                ->whereIn('status', ['pending', 'approved', 'reversed'])
                ->delete();

            $created = [];

            if (! $order->salesman_id && $order->creator) {
                $order->salesman_id = $order->creator;
            }

            if (! $order->affiliate_id && $order->affiliate_code) {
                $affiliate = Affiliate::where('code', $order->affiliate_code)->where('status', 'active')->first();
                if ($affiliate) {
                    $order->affiliate_id = $affiliate->id;
                }
            }

            $requiresManualReview = false;

            if ($order->salesman_id) {
                if ($this->hasProtectedCommissionEntry($order, 'salesman')) {
                    $requiresManualReview = true;
                } else {
                    $entry = $this->buildEntry($order, 'salesman');
                    if ($entry) {
                        $created[] = $entry;
                    }
                }
            }

            if ($order->affiliate_id) {
                if ($this->hasProtectedCommissionEntry($order, 'affiliate')) {
                    $requiresManualReview = true;
                } else {
                    $entry = $this->buildEntry($order, 'affiliate');
                    if ($entry) {
                        $created[] = $entry;
                    }
                }
            }

            if ($requiresManualReview) {
                $order->commission_status = 'requires_review';
                $order->save();
            }

            foreach ($created as $entry) {
                $this->syncCommissionProfitCost($entry);
            }

            $this->refreshOrderCommissionSummary($order);
        });
    }

    public function reverseForOrder(ProductOrder $order): void
    {
        DB::transaction(function () use ($order) {
            $order = ProductOrder::lockForUpdate()->find($order->id);
            if (! $order) {
                return;
            }

            $entries = SalesCommissionEntry::where('product_order_id', $order->id)->get();

            foreach ($entries as $entry) {
                if (in_array($entry->status, ['paid', 'partially_paid', 'settled'], true)) {
                    CommissionAdjustment::firstOrCreate([
                        'sales_commission_entry_id' => $entry->id,
                        'type' => 'deduction',
                    ], [
                        'adjustment_for' => $entry->commission_for,
                        'user_id' => $entry->user_id,
                        'affiliate_id' => $entry->affiliate_id,
                        'product_order_id' => $order->id,
                        'amount' => abs((float) $entry->commission_amount),
                        'reason' => 'Order commission reversed after order status change / return / cancel.',
                        'status' => 'pending',
                        'created_by' => Auth::id(),
                    ]);
                } else {
                    $entry->status = 'reversed';
                    $entry->reversed_at = Carbon::now();
                    $entry->note = trim(($entry->note ? $entry->note . "\n" : '') . 'Reversed by order lifecycle rollback.');
                    $entry->save();
                }

                $this->reverseCommissionProfitCost($entry);
            }

            $order->commission_status = 'reversed';
            $order->commission_reversed_at = Carbon::now();
            $this->refreshOrderCommissionSummary($order);
        });
    }

    public function recalculateForOrder(ProductOrder $order): void
    {
        $this->reverseForOrder($order);
        $this->calculateForOrder($order->fresh());
    }

    public function refreshForOrder(ProductOrder $order): void
    {
        DB::transaction(function () use ($order) {
            $lockedOrder = ProductOrder::lockForUpdate()->find($order->id);
            if ($lockedOrder) {
                $this->refreshOrderCommissionSummary($lockedOrder);
            }
        });
    }

    public function reverseEntry(SalesCommissionEntry $entry, ?string $reason = null): void
    {
        DB::transaction(function () use ($entry, $reason) {
            $entry = SalesCommissionEntry::lockForUpdate()->find($entry->id);
            if (! $entry) {
                return;
            }

            if (in_array($entry->status, ['paid', 'partially_paid', 'settled'], true)) {
                CommissionAdjustment::firstOrCreate([
                    'sales_commission_entry_id' => $entry->id,
                    'type' => 'deduction',
                ], [
                    'adjustment_for' => $entry->commission_for,
                    'user_id' => $entry->user_id,
                    'affiliate_id' => $entry->affiliate_id,
                    'product_order_id' => $entry->product_order_id,
                    'amount' => abs((float) $entry->commission_amount),
                    'reason' => $reason ?: 'Protected commission reversed manually; adjustment will be applied in the next settlement.',
                    'status' => 'pending',
                    'created_by' => Auth::id(),
                ]);
            } else {
                $entry->status = 'reversed';
                $entry->reversed_at = Carbon::now();
                $entry->note = trim(($entry->note ? $entry->note . "\n" : '') . ($reason ?: 'Reversed manually from commission entry screen.'));
                $entry->save();
            }

            $this->reverseCommissionProfitCost($entry);

            if ($entry->order) {
                $this->refreshOrderCommissionSummary($entry->order);
            }
        });
    }

    protected function hasProtectedCommissionEntry(ProductOrder $order, string $type): bool
    {
        return SalesCommissionEntry::where('product_order_id', $order->id)
            ->where('commission_for', $type)
            ->whereIn('status', ['settled', 'paid', 'partially_paid'])
            ->exists();
    }

    protected function buildEntry(ProductOrder $order, string $type): ?SalesCommissionEntry
    {
        $rule = $this->getApplicableRule($order, $type);
        $commissionBase = $rule->commission_base ?? 'sale_amount';
        $commissionType = $rule->commission_type ?? 'percentage';
        $commissionValue = (float) ($rule->commission_value ?? 0);

        if (! $rule && $type === 'affiliate' && $order->affiliate) {
            $commissionBase = $order->affiliate->default_commission_base ?: 'sale_amount';
            $commissionType = $order->affiliate->default_commission_type ?: 'percentage';
            $commissionValue = (float) $order->affiliate->default_commission_value;
        }

        if ($commissionValue <= 0) {
            return null;
        }

        $baseAmount = $this->baseAmount($order, $commissionBase);
        if ($baseAmount <= 0) {
            return null;
        }

        if ($rule && (float) $rule->min_order_amount > 0 && (float) $order->total < (float) $rule->min_order_amount) {
            return null;
        }

        $amount = $this->calculateAmount($baseAmount, $commissionType, $commissionValue);
        if ($amount <= 0) {
            return null;
        }

        return SalesCommissionEntry::create([
            'product_order_id' => $order->id,
            'sales_commission_rule_id' => $rule->id ?? null,
            'user_id' => $type === 'salesman' ? $order->salesman_id : null,
            'affiliate_id' => $type === 'affiliate' ? $order->affiliate_id : null,
            'commission_for' => $type,
            'commission_base' => $commissionBase,
            'base_amount' => round($baseAmount, 2),
            'commission_type' => $commissionType,
            'commission_value' => $commissionValue,
            'commission_amount' => $amount,
            'status' => 'pending',
            'source_status' => $order->order_status,
            'created_by' => Auth::id(),
        ]);
    }

    protected function getApplicableRule(ProductOrder $order, string $type): ?SalesCommissionRule
    {
        $date = $order->sale_date ?: now()->toDateString();

        $query = SalesCommissionRule::where('status', 'active')
            ->where('commission_for', $type)
            ->where(function ($q) use ($date) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', $date);
            })
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $date);
            });

        if ($type === 'salesman') {
            $query->where(function ($q) use ($order) {
                $q->whereNull('user_id')->orWhere('user_id', $order->salesman_id);
            });
        }

        if ($type === 'affiliate') {
            $query->where(function ($q) use ($order) {
                $q->whereNull('affiliate_id')->orWhere('affiliate_id', $order->affiliate_id);
            });
        }

        if ($order->product_website_id) {
            $query->where(function ($q) use ($order) {
                $q->whereNull('website_id')->orWhere('website_id', $order->product_website_id);
            });
        } else {
            $query->whereNull('website_id');
        }

        return $query->orderBy('priority')->orderByDesc('id')->first();
    }

    protected function baseAmount(ProductOrder $order, string $base): float
    {
        if ($base === 'gross_profit') {
            return max(0, (float) ($order->gross_profit ?? 0));
        }

        return max(0, (float) ($order->subtotal ?? $order->total ?? 0));
    }


    protected function syncCommissionProfitCost(SalesCommissionEntry $entry): void
    {
        if (! Schema::hasTable('order_profit_costs')) {
            return;
        }

        OrderProfitCost::updateOrCreate([
            'source_type' => SalesCommissionEntry::class,
            'source_id' => $entry->id,
            'cost_type' => $entry->commission_for === 'affiliate' ? 'affiliate_commission' : 'salesman_commission',
        ], [
            'product_order_id' => $entry->product_order_id,
            'cost_group' => 'direct',
            'amount' => abs((float) $entry->commission_amount),
            'is_estimated' => false,
            'status' => in_array($entry->status, ['reversed'], true) ? 'reversed' : 'active',
            'created_by' => Auth::id(),
            'reversed_at' => $entry->status === 'reversed' ? Carbon::now() : null,
            'reversed_by' => $entry->status === 'reversed' ? Auth::id() : null,
            'note' => ucfirst($entry->commission_for) . ' commission cost generated from commission entry #' . $entry->id,
        ]);
    }

    protected function reverseCommissionProfitCost(SalesCommissionEntry $entry): void
    {
        if (! Schema::hasTable('order_profit_costs')) {
            return;
        }

        OrderProfitCost::where('source_type', SalesCommissionEntry::class)
            ->where('source_id', $entry->id)
            ->whereIn('status', ['active', 'pending'])
            ->update([
                'status' => 'reversed',
                'reversed_at' => Carbon::now(),
                'reversed_by' => Auth::id(),
            ]);
    }

    protected function refreshOrderCommissionSummary(ProductOrder $order): void
    {
        $order = ProductOrder::lockForUpdate()->find($order->id);
        if (! $order) {
            return;
        }

        $activeStatuses = ['pending', 'approved', 'settled', 'paid', 'partially_paid'];

        $salesmanTotal = SalesCommissionEntry::where('product_order_id', $order->id)
            ->where('commission_for', 'salesman')
            ->whereIn('status', $activeStatuses)
            ->sum('commission_amount');

        $affiliateTotal = SalesCommissionEntry::where('product_order_id', $order->id)
            ->where('commission_for', 'affiliate')
            ->whereIn('status', $activeStatuses)
            ->sum('commission_amount');

        $commissionTotal = round(((float) $salesmanTotal) + ((float) $affiliateTotal), 2);
        $directExpenseTotal = $commissionTotal;
        $estimatedExpenseTotal = 0;

        if (Schema::hasTable('order_profit_costs')) {
            $directExpenseTotal = (float) OrderProfitCost::where('product_order_id', $order->id)
                ->where('status', 'active')
                ->where('is_estimated', false)
                ->sum('amount');

            $estimatedExpenseTotal = (float) OrderProfitCost::where('product_order_id', $order->id)
                ->where('status', 'active')
                ->where('is_estimated', true)
                ->sum('amount');
        }

        $grossProfit = (float) ($order->gross_profit ?? 0);
        $contributionProfit = round($grossProfit - $directExpenseTotal, 2);
        $managementNetProfit = round($grossProfit - $directExpenseTotal - $estimatedExpenseTotal, 2);

        $order->salesman_commission_total = round((float) $salesmanTotal, 2);
        $order->affiliate_commission_total = round((float) $affiliateTotal, 2);
        $order->commission_total = $commissionTotal;
        if ($order->commission_status !== 'requires_review') {
            $order->commission_status = $commissionTotal > 0 ? 'calculated' : ($order->commission_status ?: 'no_commission');
        }
        $order->commission_calculated_at = $commissionTotal > 0 ? Carbon::now() : $order->commission_calculated_at;

        if (Schema::hasColumn('product_orders', 'direct_expense_total')) {
            $order->direct_expense_total = round($directExpenseTotal, 2);
        }
        if (Schema::hasColumn('product_orders', 'estimated_expense_total')) {
            $order->estimated_expense_total = round($estimatedExpenseTotal, 2);
        }
        if (Schema::hasColumn('product_orders', 'contribution_profit')) {
            $order->contribution_profit = $contributionProfit;
        }
        if (Schema::hasColumn('product_orders', 'management_net_profit')) {
            $order->management_net_profit = $managementNetProfit;
        }

        // Backward compatibility: existing dashboards already read product_orders.net_profit.
        if (! is_null($order->gross_profit)) {
            $order->net_profit = $contributionProfit;
        }

        $order->save();
    }

    protected function calculateAmount(float $baseAmount, string $type, float $value): float
    {
        if ($type === 'fixed') {
            return round($value, 2);
        }

        return round(($baseAmount * $value) / 100, 2);
    }
}
