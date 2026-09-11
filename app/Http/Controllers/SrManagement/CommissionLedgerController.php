<?php

namespace App\Http\Controllers\SrManagement;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\CommissionAdjustment;
use App\Models\CommissionSettlement;
use App\Models\SalesCommissionEntry;
use App\Models\User;
use Illuminate\Http\Request;

class CommissionLedgerController extends Controller
{
    public function index(Request $request)
    {
        $users = User::orderBy('name')->limit(500)->get(['id', 'name']);
        $affiliates = Affiliate::orderBy('name')->get(['id', 'name', 'code']);
        $ledger = null;

        if ($request->filled('ledger_for')) {
            $entries = SalesCommissionEntry::query()->where('commission_for', $request->ledger_for);
            $settlements = CommissionSettlement::query()->where('settlement_for', $request->ledger_for);
            $adjustments = CommissionAdjustment::query()->where('adjustment_for', $request->ledger_for);

            if ($request->ledger_for === 'salesman') {
                $entries->where('user_id', $request->user_id);
                $settlements->where('user_id', $request->user_id);
                $adjustments->where('user_id', $request->user_id);
            } else {
                $entries->where('affiliate_id', $request->affiliate_id);
                $settlements->where('affiliate_id', $request->affiliate_id);
                $adjustments->where('affiliate_id', $request->affiliate_id);
            }

            $earned = (clone $entries)->whereIn('status', ['pending', 'approved', 'settled', 'paid', 'partially_paid'])->sum('commission_amount');
            $paid = (clone $settlements)->whereIn('status', ['paid', 'partially_paid'])->sum('paid_amount');
            $deduction = (clone $adjustments)->where('type', 'deduction')->sum('amount');
            $addition = (clone $adjustments)->where('type', 'addition')->sum('amount');

            $ledger = [
                'earned' => $earned,
                'paid' => $paid,
                'deduction' => $deduction,
                'addition' => $addition,
                'closing_due' => $earned + $addition - $deduction - $paid,
                'recent_entries' => $entries->latest()->limit(20)->get(),
                'recent_settlements' => $settlements->latest()->limit(20)->get(),
            ];
        }

        return view('backend.sr_management.commission_ledger.index', compact('users', 'affiliates', 'ledger'));
    }
}
