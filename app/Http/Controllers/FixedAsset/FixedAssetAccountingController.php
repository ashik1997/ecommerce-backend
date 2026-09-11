<?php

namespace App\Http\Controllers\FixedAsset;

use App\Http\Controllers\Controller;
use App\Services\FixedAsset\FixedAssetAccountingPostingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FixedAssetAccountingController extends Controller
{
    public function ledger(Request $request, FixedAssetAccountingPostingService $postingService)
    {
        $query = $this->filteredLedgerQuery($request, $postingService);

        $transactions = (clone $query)
            ->latest('id')
            ->paginate(50)
            ->withQueryString();

        $summary = (clone $query)
            ->select('event_type', DB::raw('COUNT(*) as total_rows'), DB::raw('SUM(debit_amt) as total_amount'))
            ->groupBy('event_type')
            ->orderBy('event_type')
            ->get();

        $totals = [
            'rows' => (clone $query)->count(),
            'debit' => (float) (clone $query)->sum('debit_amt'),
            'credit' => (float) (clone $query)->sum('credit_amt'),
        ];

        return view('backend.fixed_asset.accounting.ledger', compact('transactions', 'summary', 'totals'));
    }

    public function export(Request $request, FixedAssetAccountingPostingService $postingService)
    {
        $rows = $this->filteredLedgerQuery($request, $postingService)
            ->latest('id')
            ->get();

        $filename = 'fixed_asset_accounting_ledger_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'Event', 'Debit Account', 'Credit Account', 'Debit', 'Credit', 'Asset ID', 'Source Type', 'Source ID', 'Note']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->transaction_date,
                    $row->event_type,
                    optional($row->debitAccount)->account_name ?: $row->debit_account_id,
                    optional($row->creditAccount)->account_name ?: $row->credit_account_id,
                    number_format((float) $row->debit_amt, 4, '.', ''),
                    number_format((float) $row->credit_amt, 4, '.', ''),
                    $row->ref_fixed_asset_id,
                    $row->ref_fixed_asset_source_type,
                    $row->ref_fixed_asset_source_id,
                    $row->note,
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function filteredLedgerQuery(Request $request, FixedAssetAccountingPostingService $postingService)
    {
        $query = $postingService->fixedAssetLedgerQuery();

        if ($request->filled('event_type')) {
            $query->where('event_type', $request->event_type);
        }

        if ($request->filled('asset_id')) {
            $query->where('ref_fixed_asset_id', $request->asset_id);
        }

        if ($request->filled('source_type')) {
            $query->where('ref_fixed_asset_source_type', $request->source_type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('transaction_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('transaction_date', '<=', $request->date_to);
        }

        return $query;
    }
}
