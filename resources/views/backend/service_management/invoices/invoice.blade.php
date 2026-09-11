<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Invoice {{ $instance->instance_no }}</title>
    <style>
        body { color: #222; font-family: Arial, sans-serif; font-size: 13px; margin: 0; padding: 24px; }
        .page { margin: 0 auto; max-width: 900px; }
        .header { border-bottom: 2px solid #222; display: table; margin-bottom: 24px; padding-bottom: 16px; width: 100%; }
        .company, .title { display: table-cell; vertical-align: top; width: 50%; }
        .title { text-align: right; }
        .logo { max-height: 70px; max-width: 140px; }
        h1, h2, h3 { margin: 0; }
        h1 { font-size: 28px; letter-spacing: 1px; }
        h3 { font-size: 16px; margin-bottom: 8px; }
        p { margin: 3px 0; }
        .box-row { display: table; margin-bottom: 20px; width: 100%; }
        .box { background: #f7f7f7; display: table-cell; padding: 12px; vertical-align: top; width: 50%; }
        .box:first-child { border-right: 12px solid #fff; }
        table { border-collapse: collapse; margin-bottom: 16px; width: 100%; }
        th { background: #222; color: #fff; text-align: left; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        .right { text-align: right; }
        .summary { margin-left: auto; width: 340px; }
        .summary td:first-child { font-weight: bold; }
        .badge { border: 1px solid #222; display: inline-block; font-size: 11px; margin-top: 8px; padding: 4px 8px; text-transform: uppercase; }
        .actions { margin: 20px auto; max-width: 900px; text-align: right; }
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
        <a href="javascript:window.print()" class="btn">Print Invoice</a>
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
                <p>{{ $company['website'] }}</p>
            </div>
            <div class="title">
                <h1>INVOICE</h1>
                <p><strong>{{ $instance->instance_no }}</strong></p>
                <p>Date: {{ optional($instance->created_at)->format('Y-m-d') }}</p>
                <span class="badge">{{ $instance->status }}</span>
            </div>
        </div>

        <div class="box-row">
            <div class="box">
                <h3>Bill To</h3>
                <p><strong>{{ $instance->customer->name ?? $instance->customer->full_name ?? 'N/A' }}</strong></p>
                <p>{{ $instance->customer->phone ?? '' }}</p>
                <p>{{ $instance->customer->email ?? '' }}</p>
                <p>{{ $instance->customer->address ?? '' }}</p>
            </div>
            <div class="box">
                <h3>Service Details</h3>
                <p><strong>{{ $instance->service->name ?? 'N/A' }}</strong></p>
                <p>Type: {{ ucfirst($instance->service->type ?? '') }}</p>
                <p>Billing Unit: {{ ucfirst($instance->service->billing_unit ?? '') }}</p>
                <p>Period: {{ optional($instance->start_date)->format('Y-m-d') ?: 'N/A' }} - {{ optional($instance->end_date)->format('Y-m-d') ?: 'Open' }}</p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="right">Qty</th>
                    <th class="right">Unit Price</th>
                    <th class="right">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $instance->service->name ?? 'Service Charge' }}</td>
                    <td class="right">{{ $instance->billing_unit_qty }}</td>
                    <td class="right">{{ number_format((float) $instance->service_unit_price, 2) }}</td>
                    <td class="right">{{ number_format((float) $instance->service_subtotal, 2) }}</td>
                </tr>
                @foreach ($instance->products as $product)
                    <tr>
                        <td>
                            {{ $product->product->name ?? 'Product' }}
                            @if ($product->note)
                                <br><small>{{ $product->note }}</small>
                            @endif
                        </td>
                        <td class="right">{{ $product->quantity_used }}</td>
                        <td class="right">{{ number_format((float) $product->unit_price, 2) }}</td>
                        <td class="right">{{ number_format((float) $product->total_price, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="summary">
            <tr><td>Service Subtotal</td><td class="right">{{ number_format((float) $instance->service_subtotal, 2) }}</td></tr>
            <tr><td>Products Subtotal</td><td class="right">{{ number_format((float) $instance->products_subtotal, 2) }}</td></tr>
            <tr><td>Total</td><td class="right">{{ number_format((float) $instance->total_amount, 2) }}</td></tr>
            <tr><td>Paid</td><td class="right">{{ number_format((float) $instance->paid_amount, 2) }}</td></tr>
            <tr><td>Due</td><td class="right">{{ number_format((float) $instance->due_amount, 2) }}</td></tr>
        </table>
    </div>
</body>
</html>
