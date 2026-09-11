<div class="row mb-3">
    <div class="col-md-3"><strong>Deposit Account:</strong><br>{{ optional($settlement->account)->account_name ?? '-' }}</div>
    <div class="col-md-3"><strong>Total Orders:</strong><br>{{ $settlement->total_orders }}</div>
    <div class="col-md-3"><strong>Courier Expense:</strong><br>৳ {{ number_format($settlement->courier_expense_total, 2) }}</div>
    <div class="col-md-3"><strong>Received:</strong><br>৳ {{ number_format($settlement->received_total, 2) }}</div>
</div>

<div class="row mb-3">
    <div class="col-md-3"><strong>Receivable Closed:</strong><br>৳ {{ number_format($settlement->receivable_total, 2) }}</div>
    <div class="col-md-3"><strong>Settlement Amount:</strong><br>৳ {{ number_format($settlement->settlement_amount, 2) }}</div>
    <div class="col-md-6"><strong>Note:</strong><br>{{ $settlement->note ?: '-' }}</div>
</div>

<div class="table-responsive">
    <table class="table table-bordered table-sm">
        <thead>
            <tr>
                <th>#</th>
                <th>Order</th>
                <th>Customer</th>
                <th>Tracking</th>
                <th>Status</th>
                <th class="text-end">COD</th>
                <th class="text-end">Courier Cost</th>
                <th class="text-end">Received</th>
                <th class="text-end">Restocked Qty</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($settlement->orders as $idx => $row)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>
                        <strong>{{ $row->order_code }}</strong><br>
                        <small>ID: {{ $row->product_order_id }}</small>
                    </td>
                    <td>
                        {{ optional(optional($row->order)->customer)->name ?? optional($row->order)->customer_name ?? '-' }}<br>
                        <small>{{ optional($row->order)->customer_phone ?? '-' }}</small>
                    </td>
                    <td>{{ $row->tracking_id ?: '-' }}</td>
                    <td>
                        <strong>{{ str_replace('_', ' ', ucfirst($row->normalized_status)) }}</strong><br>
                        <small>{{ $row->raw_status }}</small>
                    </td>
                    <td class="text-end">৳ {{ number_format($row->cod_amount, 2) }}</td>
                    <td class="text-end">৳ {{ number_format($row->courier_cost, 2) }}</td>
                    <td class="text-end">৳ {{ number_format($row->received_amount, 2) }}</td>
                    <td class="text-end">{{ number_format($row->restocked_qty, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
