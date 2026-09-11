<?php

namespace App\Http\Controllers\SrManagement;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\CommissionAdjustment;
use App\Models\CommissionSettlement;
use App\Models\CommissionSettlementItem;
use App\Models\SalesCommissionEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Services\Commission\CommissionAccountingService;

class CommissionSettlementController extends Controller
{
    public function index(Request $request)
    {
        $query = CommissionSettlement::with(['user', 'affiliate']);

        if ($request->filled('settlement_for')) {
            $query->where('settlement_for', $request->settlement_for);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('from')) {
            $query->whereDate('period_start', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('period_end', '<=', $request->to);
        }

        $summaryQuery = clone $query;
        $summary = [
            'payable' => (clone $summaryQuery)->sum('payable_amount'),
            'paid' => (clone $summaryQuery)->sum('paid_amount'),
            'due' => (clone $summaryQuery)->sum('due_amount'),
        ];

        $settlements = $query->latest()->paginate(20)->appends($request->query());

        return view('backend.sr_management.commission_settlements.index', compact('settlements', 'summary'));
    }

    public function create(Request $request)
    {
        $users = User::orderBy('name')->limit(500)->get(['id', 'name']);
        $affiliates = Affiliate::where('status', 'active')->orderBy('name')->get(['id', 'name', 'code']);
        $preview = null;

        if ($request->filled(['settlement_for', 'period_start', 'period_end'])) {
            $preview = $this->previewData($request);
        }

        return view('backend.sr_management.commission_settlements.create', compact('users', 'affiliates', 'preview'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'settlement_for' => ['required', Rule::in(['salesman', 'affiliate'])],
            'user_id' => ['nullable', 'integer'],
            'affiliate_id' => ['nullable', 'integer'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'adjustment_amount' => ['nullable', 'numeric'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'submit_action' => ['nullable', Rule::in(['draft', 'approve', 'pay'])],
            'payment_method_id' => ['nullable', 'integer'],
            'note' => ['nullable', 'string'],
        ]);

        if ($data['settlement_for'] === 'salesman' && empty($data['user_id'])) {
            return back()->withInput()->with('error', 'Please select a salesman.');
        }
        if ($data['settlement_for'] === 'affiliate' && empty($data['affiliate_id'])) {
            return back()->withInput()->with('error', 'Please select an affiliate.');
        }

        $preview = $this->previewData(new Request($data));
        if ($preview['entries']->isEmpty() && $preview['adjustments']->isEmpty()) {
            return back()->withInput()->with('error', 'No approved unpaid commission found for this period.');
        }

        $settlementId = null;

        DB::transaction(function () use ($data, $preview, &$settlementId) {
            $totalCommission = round((float) $preview['total_commission'], 2);
            $adjustments = round((float) $preview['adjustment_total'] + (float) ($data['adjustment_amount'] ?? 0), 2);
            $payable = max(0, $totalCommission - $adjustments);
            $action = $data['submit_action'] ?? 'draft';
            $paid = $action === 'pay' ? min($payable, (float) ($data['paid_amount'] ?? 0)) : 0;
            $due = round($payable - $paid, 2);
            $status = $this->initialStatus($action, $paid, $due);

            $settlement = CommissionSettlement::create([
                'settlement_no' => 'CS-' . date('Ymd-His') . '-' . random_int(100, 999),
                'settlement_for' => $data['settlement_for'],
                'user_id' => $data['settlement_for'] === 'salesman' ? ($data['user_id'] ?? null) : null,
                'affiliate_id' => $data['settlement_for'] === 'affiliate' ? ($data['affiliate_id'] ?? null) : null,
                'period_start' => $data['period_start'],
                'period_end' => $data['period_end'],
                'total_commission' => $totalCommission,
                'previous_due' => 0,
                'adjustment_amount' => $adjustments,
                'payable_amount' => $payable,
                'paid_amount' => $paid,
                'due_amount' => $due,
                'payment_method_id' => $data['payment_method_id'] ?? null,
                'status' => $status,
                'settled_by' => in_array($status, ['approved', 'paid', 'partially_paid'], true) ? Auth::id() : null,
                'settled_at' => in_array($status, ['approved', 'paid', 'partially_paid'], true) ? Carbon::now() : null,
                'note' => $data['note'] ?? null,
            ]);

            foreach ($preview['entries'] as $entry) {
                CommissionSettlementItem::create([
                    'commission_settlement_id' => $settlement->id,
                    'sales_commission_entry_id' => $entry->id,
                    'commission_amount' => $entry->commission_amount,
                    'settled_amount' => $entry->commission_amount,
                ]);

                if ($status !== 'draft') {
                    $entry->update([
                        'settlement_id' => $settlement->id,
                        'status' => $status === 'paid' ? 'paid' : ($status === 'partially_paid' ? 'partially_paid' : 'settled'),
                        'paid_at' => in_array($status, ['paid', 'partially_paid'], true) ? Carbon::now() : null,
                    ]);
                }
            }

            if ($status !== 'draft') {
                foreach ($preview['adjustments'] as $adjustment) {
                    $adjustment->update(['status' => 'applied']);
                }
            }

            $settlementId = $settlement->id;
        });

        if ($settlementId) {
            $settlement = CommissionSettlement::find($settlementId);
            if ($settlement && in_array($settlement->status, ['approved', 'paid', 'partially_paid'], true)) {
                app(CommissionAccountingService::class)->postSettlementApproval($settlement);
                if ($settlement->status === 'paid') {
                    app(CommissionAccountingService::class)->postSettlementPayment($settlement);
                }
            }
        }

        return redirect()->route('sr.commission-settlements.show', $settlementId)->with('success', 'Commission settlement created successfully.');
    }

    public function show($id)
    {
        $settlement = CommissionSettlement::with(['user', 'affiliate', 'items.commissionEntry.order'])->findOrFail($id);
        return view('backend.sr_management.commission_settlements.show', compact('settlement'));
    }

    public function approve($id)
    {
        $settlement = CommissionSettlement::with(['items.commissionEntry'])->findOrFail($id);
        if ($settlement->status !== 'draft') {
            return back()->with('error', 'Only draft settlement can be approved.');
        }

        DB::transaction(function () use ($settlement) {
            $settlement->update([
                'status' => 'approved',
                'settled_by' => Auth::id(),
                'settled_at' => Carbon::now(),
            ]);

            foreach ($settlement->items as $item) {
                if ($item->commissionEntry && $item->commissionEntry->status === 'approved') {
                    $item->commissionEntry->update([
                        'settlement_id' => $settlement->id,
                        'status' => 'settled',
                    ]);
                }
            }
        });

        app(CommissionAccountingService::class)->postSettlementApproval($settlement);

        return back()->with('success', 'Settlement approved.');
    }

    public function markPaid(Request $request, $id)
    {
        $data = $request->validate([
            'paid_amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method_id' => ['nullable', 'integer'],
            'note' => ['nullable', 'string'],
        ]);

        $settlement = CommissionSettlement::with(['items.commissionEntry'])->findOrFail($id);
        if (! in_array($settlement->status, ['approved', 'partially_paid'], true)) {
            return back()->with('error', 'Only approved or partially paid settlement can receive payment.');
        }

        $paymentAmount = (float) $data['paid_amount'];

        DB::transaction(function () use ($settlement, $data) {
            $newPaid = min((float) $settlement->payable_amount, (float) $settlement->paid_amount + (float) $data['paid_amount']);
            $due = round((float) $settlement->payable_amount - $newPaid, 2);
            $status = $due > 0 ? 'partially_paid' : 'paid';

            $note = trim(($settlement->note ? $settlement->note . "\n" : '') . ($data['note'] ?? ''));

            $settlement->update([
                'paid_amount' => round($newPaid, 2),
                'due_amount' => $due,
                'payment_method_id' => $data['payment_method_id'] ?? $settlement->payment_method_id,
                'status' => $status,
                'settled_by' => $settlement->settled_by ?: Auth::id(),
                'settled_at' => $settlement->settled_at ?: Carbon::now(),
                'note' => $note ?: $settlement->note,
            ]);

            foreach ($settlement->items as $item) {
                if ($item->commissionEntry && in_array($item->commissionEntry->status, ['approved', 'settled', 'partially_paid'], true)) {
                    $item->commissionEntry->update([
                        'settlement_id' => $settlement->id,
                        'status' => $status === 'paid' ? 'paid' : 'partially_paid',
                        'paid_at' => Carbon::now(),
                    ]);
                }
            }
        });

        $settlement->refresh();
        app(CommissionAccountingService::class)->postSettlementPayment($settlement, $paymentAmount);

        return back()->with('success', 'Settlement payment updated.');
    }

    public function cancel($id)
    {
        $settlement = CommissionSettlement::with(['items.commissionEntry'])->findOrFail($id);
        if (in_array($settlement->status, ['paid', 'partially_paid'], true) || (float) $settlement->paid_amount > 0) {
            return back()->with('error', 'Paid settlement cannot be cancelled. Create an adjustment instead.');
        }

        DB::transaction(function () use ($settlement) {
            foreach ($settlement->items as $item) {
                if ($item->commissionEntry && in_array($item->commissionEntry->status, ['settled'], true)) {
                    $item->commissionEntry->update([
                        'status' => 'approved',
                        'settlement_id' => null,
                    ]);
                }
            }
            $settlement->update(['status' => 'cancelled']);
        });

        app(CommissionAccountingService::class)->reverseSettlementAccounting($settlement);

        return redirect()->route('sr.commission-settlements.index')->with('success', 'Settlement cancelled.');
    }

    protected function initialStatus(string $action, float $paid, float $due): string
    {
        if ($action === 'pay') {
            return $due > 0 ? 'partially_paid' : 'paid';
        }

        if ($action === 'approve') {
            return 'approved';
        }

        return 'draft';
    }

    protected function previewData(Request $request): array
    {
        $query = SalesCommissionEntry::with(['order'])
            ->where('commission_for', $request->settlement_for)
            ->whereIn('status', ['approved'])
            ->whereDate('created_at', '>=', $request->period_start)
            ->whereDate('created_at', '<=', $request->period_end);

        if ($request->settlement_for === 'salesman') {
            $query->where('user_id', $request->user_id);
        } else {
            $query->where('affiliate_id', $request->affiliate_id);
        }

        $entries = $query->get();

        $adjustments = CommissionAdjustment::where('adjustment_for', $request->settlement_for)
            ->where('status', 'pending')
            ->when($request->settlement_for === 'salesman', fn ($q) => $q->where('user_id', $request->user_id))
            ->when($request->settlement_for === 'affiliate', fn ($q) => $q->where('affiliate_id', $request->affiliate_id))
            ->get();

        return [
            'entries' => $entries,
            'adjustments' => $adjustments,
            'total_commission' => $entries->sum('commission_amount'),
            'adjustment_total' => $adjustments->sum('amount'),
        ];
    }
}
