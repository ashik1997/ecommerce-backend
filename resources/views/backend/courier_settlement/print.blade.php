<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $settlement->settlement_code }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #111; font-size: 12px; }
        .head { display: flex; justify-content: space-between; border-bottom: 1px solid #333; padding-bottom: 10px; margin-bottom: 14px; }
        h2, h4 { margin: 0 0 4px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #bbb; padding: 6px; vertical-align: top; }
        th { background: #f1f1f1; }
        .text-end { text-align: right; }
        .summary { margin-bottom: 12px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()">Print</button>
    <div class="head">
        <div>
            <h2>Courier Settlement Report</h2>
            <h4>{{ $settlement->settlement_code }}</h4>
        </div>
        <div>
            <strong>Courier:</strong> {{ ucfirst($settlement->courier) }}<br>
            <strong>Date:</strong> {{ optional($settlement->settlement_date)->format('Y-m-d') }}<br>
            <strong>Printed:</strong> {{ now()->format('Y-m-d H:i') }}
        </div>
    </div>

    @include('backend.courier_settlement.partials.report_table', ['settlement' => $settlement])
</body>
</html>
