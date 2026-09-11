<?php

namespace App\Http\Controllers\SrManagement;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\SalesCommissionEntry;
use App\Models\User;
use App\Services\Commission\OrderCommissionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommissionEntryController extends Controller
{
    public function index(Request $request)
    {
        $query = SalesCommissionEntry::with(['order', 'user', 'affiliate', 'rule', 'settlement']);

        if ($request->filled('commission_for')) {
            $query->where('commission_for', $request->commission_for);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('affiliate_id')) {
            $query->where('affiliate_id', $request->affiliate_id);
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $summaryQuery = clone $query;
        $summary = [
            'total' => (clone $summaryQuery)->sum('commission_amount'),
            'pending' => (clone $summaryQuery)->where('status', 'pending')->sum('commission_amount'),
            'approved' => (clone $summaryQuery)->where('status', 'approved')->sum('commission_amount'),
            'paid' => (clone $summaryQuery)->whereIn('status', ['paid', 'partially_paid'])->sum('commission_amount'),
            'reversed' => (clone $summaryQuery)->where('status', 'reversed')->sum('commission_amount'),
        ];

        $entries = $query->latest()->paginate(25)->appends($request->query());
        $users = User::orderBy('name')->limit(500)->get(['id', 'name']);
        $affiliates = Affiliate::orderBy('name')->get(['id', 'name', 'code']);

        return view('backend.sr_management.commission_entries.index', compact('entries', 'users', 'affiliates', 'summary'));
    }

    public function approve($id, OrderCommissionService $commissionService)
    {
        $entry = SalesCommissionEntry::with('order')->findOrFail($id);
        if ($entry->status === 'pending') {
            $entry->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => Carbon::now(),
            ]);

            if ($entry->order) {
                $commissionService->refreshForOrder($entry->order);
            }
        }

        return back()->with('success', 'Commission entry approved.');
    }

    public function bulkApprove(Request $request, OrderCommissionService $commissionService)
    {
        $ids = array_filter((array) $request->input('entry_ids', []));
        if (empty($ids)) {
            return back()->with('error', 'Please select at least one pending commission entry.');
        }

        $entries = SalesCommissionEntry::with('order')->whereIn('id', $ids)->where('status', 'pending')->get();
        $count = 0;
        foreach ($entries as $entry) {
            $entry->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => Carbon::now(),
            ]);
            if ($entry->order) {
                $commissionService->refreshForOrder($entry->order);
            }
            $count++;
        }

        return back()->with('success', $count . ' commission entries approved.');
    }

    public function reverse($id, OrderCommissionService $commissionService)
    {
        $entry = SalesCommissionEntry::findOrFail($id);
        if ($entry->status === 'reversed') {
            return back()->with('error', 'This commission entry is already reversed.');
        }

        $commissionService->reverseEntry($entry);

        return back()->with('success', 'Commission entry reversed or adjustment queued successfully.');
    }

    public function recalculateOrder($orderId, OrderCommissionService $commissionService)
    {
        $order = \App\Models\ProductOrder::findOrFail($orderId);
        $commissionService->recalculateForOrder($order);

        return back()->with('success', 'Order commission recalculated. If protected paid entries exist, the order may require manual review.');
    }
}
