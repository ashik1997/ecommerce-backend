@if($payroll->accountingTransactions->isNotEmpty())
    <div class="mt-3">
        <h6 class="mb-2">Accounting Entries</h6>
        <table class="table table-bordered table-sm mb-0">
            <tr>
                <th>Date</th><th>Event</th><th>Reference</th><th>Debit Account</th><th>Debit</th><th>Credit Account</th><th>Credit</th><th>Note</th>
            </tr>
            @foreach($payroll->accountingTransactions as $transaction)
                @php($transactionDate = $transaction->transaction_date ? \Carbon\Carbon::parse($transaction->transaction_date)->format('Y-m-d') : '-')
                <tr>
                    <td>{{ $transactionDate }}</td>
                    <td>{{ ucwords(str_replace('_', ' ', $transaction->event_type)) }}</td>
                    <td>{{ $transaction->payment_code }}</td>
                    <td>{{ $transaction->debitAccount->account_name ?? '-' }}</td>
                    <td>{{ $transaction->debit_amt ? number_format($transaction->debit_amt, 2) : '-' }}</td>
                    <td>{{ $transaction->creditAccount->account_name ?? '-' }}</td>
                    <td>{{ $transaction->credit_amt ? number_format($transaction->credit_amt, 2) : '-' }}</td>
                    <td>{{ $transaction->note }}</td>
                </tr>
            @endforeach
        </table>
    </div>
@else
    <div class="mt-3 text-muted small">No accounting entries posted yet.</div>
@endif
