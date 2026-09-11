<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Fund Transfer Receipt - {{ $transfer->transfer_code }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Times New Roman', serif; font-size: 14px; line-height: 1.6; color: #000; background: #fff; padding: 20px; }
        .receipt-container { max-width: 850px; margin: 0 auto; border: 2px solid #000; padding: 30px; }
        .header { text-align: center; border-bottom: 3px double #000; padding-bottom: 18px; margin-bottom: 22px; }
        .header img { max-width: 150px; max-height: 80px; margin-bottom: 10px; }
        .company-info { font-size: 12px; margin-top: 8px; }
        .receipt-title { text-align: center; font-size: 24px; font-weight: bold; margin: 22px 0; text-decoration: underline; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px 30px; margin-bottom: 22px; }
        .detail-row { display: flex; border-bottom: 1px dotted #666; padding-bottom: 7px; }
        .detail-label { width: 170px; font-weight: bold; flex-shrink: 0; }
        .amount-section { border: 2px solid #000; padding: 18px; margin: 22px 0; background: #f9f9f9; }
        .amount-row { display: flex; justify-content: space-between; font-size: 18px; margin-bottom: 10px; }
        .amount-value { font-size: 24px; font-weight: bold; }
        .balance-table { width: 100%; border-collapse: collapse; margin-top: 18px; }
        .balance-table th, .balance-table td { border: 1px solid #000; padding: 8px; text-align: right; }
        .balance-table th:first-child, .balance-table td:first-child { text-align: left; }
        .note-section { margin-top: 20px; padding: 12px; border: 1px solid #000; min-height: 70px; }
        .signature-section { margin-top: 45px; display: flex; justify-content: space-between; }
        .signature-box { width: 240px; text-align: center; }
        .signature-line { border-top: 1px solid #000; padding-top: 5px; font-weight: bold; }
        .footer { margin-top: 25px; text-align: center; font-size: 11px; color: #666; border-top: 1px solid #ccc; padding-top: 12px; }
        @media print { body { padding: 0; } @page { margin: 0.5cm; } }
    </style>
</head>
<body onload="window.print();">
    <div class="receipt-container">
        <div class="header">
            @if($generalInfo && $generalInfo->logo && file_exists(public_path($generalInfo->logo)))
                <img src="{{ url($generalInfo->logo) }}" alt="Logo">
            @endif
            <div class="company-info">
                @if($generalInfo && $generalInfo->address)
                    {{ $generalInfo->address }}<br>
                @endif
                @if($generalInfo && $generalInfo->contact)
                    Phone: {{ $generalInfo->contact }}
                @endif
                @if($generalInfo && $generalInfo->email)
                    | Email: {{ $generalInfo->email }}
                @endif
            </div>
        </div>

        <div class="receipt-title">FUND TRANSFER RECEIPT</div>

        <div class="grid">
            <div class="detail-row"><div class="detail-label">Transfer No:</div><div><strong>{{ $transfer->transfer_code }}</strong></div></div>
            <div class="detail-row"><div class="detail-label">Date:</div><div>{{ \Carbon\Carbon::parse($transfer->transfer_date)->format('d F Y') }}</div></div>
            <div class="detail-row"><div class="detail-label">Reference No:</div><div>{{ $transfer->reference_no ?? 'N/A' }}</div></div>
            <div class="detail-row"><div class="detail-label">Transfer Type:</div><div>{{ optional($transfer->transferType)->name ?? 'N/A' }}</div></div>
            <div class="detail-row"><div class="detail-label">From Account:</div><div>{{ optional($transfer->fromPaymentType)->payment_type ?? 'N/A' }}</div></div>
            <div class="detail-row"><div class="detail-label">To Account:</div><div>{{ optional($transfer->toPaymentType)->payment_type ?? 'N/A' }}</div></div>
            <div class="detail-row"><div class="detail-label">Method:</div><div>{{ $transfer->transfer_method ?? 'N/A' }}</div></div>
            <div class="detail-row"><div class="detail-label">Approved By:</div><div>{{ optional($transfer->approver)->name ?? 'N/A' }}</div></div>
        </div>

        <div class="amount-section">
            <div class="amount-row">
                <span><strong>Transfer Amount</strong></span>
                <span class="amount-value">৳ {{ number_format($transfer->amount ?? 0, 2) }}</span>
            </div>
            <div class="amount-row">
                <span>Transfer Charge</span>
                <span>৳ {{ number_format($transfer->transfer_charge ?? 0, 2) }}</span>
            </div>
        </div>

        <table class="balance-table">
            <thead>
                <tr>
                    <th>Account</th>
                    <th>Closing Before</th>
                    <th>Transfer</th>
                    <th>Closing After</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ optional($transfer->fromPaymentType)->payment_type ?? 'From Account' }}</td>
                    <td>৳ {{ number_format($transfer->from_balance_before ?? 0, 2) }}</td>
                    <td>- ৳ {{ number_format(($transfer->amount ?? 0) + ($transfer->transfer_charge ?? 0), 2) }}</td>
                    <td>৳ {{ number_format($transfer->from_balance_after ?? 0, 2) }}</td>
                </tr>
                <tr>
                    <td>{{ optional($transfer->toPaymentType)->payment_type ?? 'To Account' }}</td>
                    <td>৳ {{ number_format($transfer->to_balance_before ?? 0, 2) }}</td>
                    <td>+ ৳ {{ number_format($transfer->amount ?? 0, 2) }}</td>
                    <td>৳ {{ number_format($transfer->to_balance_after ?? 0, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="note-section">
            <strong>Note:</strong><br>
            {{ $transfer->note ?? 'N/A' }}
        </div>

        <div class="signature-section">
            <div class="signature-box"><div class="signature-line">Prepared By</div></div>
            <div class="signature-box"><div class="signature-line">Authorized Signature</div></div>
        </div>

        <div class="footer">
            Generated on {{ now('Asia/Dhaka')->format('d F Y h:i A') }} by {{ optional($transfer->creator_info)->name ?? 'System' }}
        </div>
    </div>
</body>
</html>
