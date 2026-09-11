<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt {{ $payment->payment_no }}</title>
    <style>
        body { color: #222; font-family: Arial, sans-serif; font-size: 13px; margin: 0; padding: 24px; }
        .page { margin: 0 auto; max-width: 760px; }
        .header { border-bottom: 2px solid #222; display: table; margin-bottom: 24px; padding-bottom: 16px; width: 100%; }
        .company, .title { display: table-cell; vertical-align: top; width: 50%; }
        .title { text-align: right; }
        .logo { max-height: 70px; max-width: 140px; }
        h1, h3 { margin: 0; }
        h1 { font-size: 28px; letter-spacing: 1px; }
        h3 { font-size: 16px; margin-bottom: 8px; }
        p { margin: 3px 0; }
        .box { background: #f7f7f7; margin-bottom: 20px; padding: 14px; }
        table { border-collapse: collapse; margin-bottom: 16px; width: 100%; }
        th { background: #222; color: #fff; text-align: left; }
        th, td { border: 1px solid #ddd; padding: 9px; }
        .right { text-align: right; }
        .amount { font-size: 22px; font-weight: bold; }
        .actions { margin: 20px auto; max-width: 760px; text-align: right; }
        .btn { background: #222; color: #fff; display: inline-block; padding: 8px 14px; text-decoration: none; }
        @media print {
            body { padding: 0; }
            .actions { display: none; }
            .page { max-width: none; }
        }
    </style>
</head>
<body>
    <div class="actions">
        <a href="javascript:window.print()" class="btn">Print Receipt</a>
    </div>

    <div class="page">
        <div class="header">
            <div class="company">
                @if ($company['logo'])
                    <img src="{{ $company['logo'] }}" class="logo" alt="Logo">
                @endif
                <h3>{{ $company['name'] }}</h3>
                <p>{{ $company['address'] }}</p>
                <p>{{ $company['phone'] }}</p>
                <p>{{ $company['email'] }}</p>
            </div>
            <div class="title">
                <h1>RECEIPT</h1>
                <p><strong>{{ $payment->payment_no }}</strong></p>
                <p>Date: {{ optional($payment->payment_date)->format('Y-m-d') }}</p>
            </div>
        </div>

        <div class="box">
            <p>Received from <strong>{{ $payment->customer->name ?? $payment->customer->full_name ?? 'N/A' }}</strong></p>
            <p>Against service instance <strong>{{ $payment->serviceInstance->instance_no ?? 'N/A' }}</strong></p>
            <p>Service: {{ $payment->serviceInstance->service->name ?? 'N/A' }}</p>
        </div>

        <table>
            <tr>
                <th>Payment Method</th>
                <td>{{ $payment->paymentType->payment_type ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Account</th>
                <td>{{ $payment->account->account_name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Note</th>
                <td>{{ $payment->note }}</td>
            </tr>
            <tr>
                <th>Amount Received</th>
                <td class="right amount">{{ number_format((float) $payment->amount, 2) }}</td>
            </tr>
        </table>

        <table>
            <tr><th>Invoice Total</th><td class="right">{{ number_format((float) ($payment->serviceInstance->total_amount ?? 0), 2) }}</td></tr>
            <tr><th>Total Paid</th><td class="right">{{ number_format((float) ($payment->serviceInstance->paid_amount ?? 0), 2) }}</td></tr>
            <tr><th>Remaining Due</th><td class="right">{{ number_format((float) ($payment->serviceInstance->due_amount ?? 0), 2) }}</td></tr>
        </table>
    </div>
</body>
</html>
