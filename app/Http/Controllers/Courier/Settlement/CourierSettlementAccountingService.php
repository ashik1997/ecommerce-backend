<?php

namespace App\Http\Controllers\Courier\Settlement;

use App\Http\Controllers\Account\Models\AcAccount;
use App\Models\ProductOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class CourierSettlementAccountingService
{
    public function postMoneySettlement(ProductOrder $order, int $cashOrBankAccountId, float $receivedAmount, float $courierCost, float $receivableReduction, string $note): void
    {
        $accounts = ensure_ecommerce_account_heads();
        $date = Carbon::now()->toDateString();
        $user = Auth::user();

        if ($receivedAmount > 0) {
            ecommerce_account_line($order, 'ecommerce_courier_settlement_cash', 'ECOMMERCE_COURIER_SETTLEMENT', $cashOrBankAccountId, null, $receivedAmount, 0, $note . ' - cash/bank received', $date, $user);
        }

        if ($courierCost > 0) {
            ecommerce_account_line($order, 'ecommerce_courier_settlement_expense', 'ECOMMERCE_COURIER_SETTLEMENT', $accounts['courier_delivery_expense']->id ?? null, null, $courierCost, 0, $note . ' - courier cost', $date, $user);
        }

        if ($receivableReduction > 0) {
            ecommerce_account_line($order, 'ecommerce_courier_settlement_receivable', 'ECOMMERCE_COURIER_SETTLEMENT', null, $accounts['courier_receivable']->id ?? null, 0, $receivableReduction, $note . ' - receivable reduced', $date, $user);
        }
    }

    public function postReturnAdjustments(ProductOrder $order, float $returnRatio, string $note): void
    {
        $returnRatio = max(0, min(1, $returnRatio));
        if ($returnRatio <= 0) {
            return;
        }

        $accounts = ensure_ecommerce_account_heads();
        $date = Carbon::now()->toDateString();
        $user = Auth::user();
        $order->loadMissing('order_products');

        $productRevenue = (float) $order->order_products->sum(fn($item) => (float) ($item->total_price ?? 0));
        $deliveryIncome = (float) ($order->delivery_fee ?? 0);
        $extraIncome = (float) ($order->other_charge_amount ?? 0);
        $cogs = (float) ($order->total_purchase_price ?? 0);
        if ($cogs <= 0) {
            $cogs = (float) $order->order_products->sum(fn($item) => (float) ($item->purchase_price ?? 0) * (float) ($item->qty ?? 0));
        }

        $returnedRevenue = round($productRevenue * $returnRatio, 2);
        $returnedDeliveryIncome = $returnRatio >= 1 ? $deliveryIncome : 0;
        $returnedExtraIncome = round($extraIncome * $returnRatio, 2);
        $returnedCogs = round($cogs * $returnRatio, 2);

        if ($returnedRevenue > 0) {
            ecommerce_account_line($order, 'ecommerce_settlement_sales_return', 'ECOMMERCE_SETTLEMENT_RETURN', $accounts['sales_revenue']->id ?? null, null, $returnedRevenue, 0, $note . ' - sales revenue reversed', $date, $user);
        }
        if ($returnedDeliveryIncome > 0) {
            ecommerce_account_line($order, 'ecommerce_settlement_delivery_income_return', 'ECOMMERCE_SETTLEMENT_RETURN', $accounts['delivery_charge_income']->id ?? null, null, $returnedDeliveryIncome, 0, $note . ' - delivery income reversed', $date, $user);
        }
        if ($returnedExtraIncome > 0) {
            ecommerce_account_line($order, 'ecommerce_settlement_extra_income_return', 'ECOMMERCE_SETTLEMENT_RETURN', $accounts['extra_charge_income']->id ?? null, null, $returnedExtraIncome, 0, $note . ' - extra income reversed', $date, $user);
        }
        if ($returnedCogs > 0) {
            ecommerce_account_line($order, 'ecommerce_settlement_inventory_return', 'ECOMMERCE_SETTLEMENT_RETURN', $accounts['inventory']->id ?? null, null, $returnedCogs, 0, $note . ' - inventory restored', $date, $user);
            ecommerce_account_line($order, 'ecommerce_settlement_cogs_return', 'ECOMMERCE_SETTLEMENT_RETURN', null, $accounts['cogs']->id ?? null, 0, $returnedCogs, $note . ' - COGS reversed', $date, $user);
        }
    }
}
