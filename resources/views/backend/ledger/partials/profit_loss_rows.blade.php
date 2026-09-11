<table class="table table-bordered">
    <thead>
        <tr>
            <th>Account Code</th>
            <th>Account Name</th>
            <th class="text-right">Amount</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($rows as $row)
            <tr>
                <td>{{ $row['account']->account_code }}</td>
                <td>{{ $row['account']->account_name }}</td>
                <td class="text-right">৳ {{ number_format($row['amount'], 2) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="3" class="text-center text-muted">No data found.</td>
            </tr>
        @endforelse
        <tr class="font-weight-bold table-secondary">
            <td colspan="2">{{ $totalLabel }}</td>
            <td class="text-right">৳ {{ number_format($totalAmount, 2) }}</td>
        </tr>
    </tbody>
</table>
