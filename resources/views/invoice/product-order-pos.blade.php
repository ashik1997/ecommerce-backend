<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>POS Invoice #{{ $order->order_code }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        @page {
            size: 80mm auto;
            margin: 5mm;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 11px;
            margin: 0;
            padding: 0;
        }

        .pos-container {
            width: 100%;
            max-width: 80mm;
            margin: 0 auto;
        }

        .pos-header {
            text-align: center;
            margin-bottom: 6px;
        }

        .pos-header h4 {
            margin: 0;
            font-size: 14px;
            font-weight: 700;
        }

        .pos-header p {
            margin: 2px 0;
        }

        .pos-qr {
            text-align: center;
            margin: 6px 0;
        }

        .pos-qr img {
            width: 70px;
            height: 70px;
        }

        .pos-meta,
        .pos-customer {
            margin-bottom: 6px;
        }

        .pos-meta table,
        .pos-customer table {
            width: 100%;
            border-collapse: collapse;
        }

        .pos-meta td,
        .pos-customer td {
            padding: 1px 0;
            vertical-align: top;
        }

        .pos-products {
            margin-top: 4px;
        }

        .pos-products table {
            width: 100%;
            border-collapse: collapse;
        }

        .pos-products th,
        .pos-products td {
            padding: 2px 0;
            border-bottom: 1px dashed #ccc;
        }

        .pos-products th {
            font-weight: 700;
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .pos-totals {
            margin-top: 6px;
        }

        .pos-totals table {
            width: 100%;
            border-collapse: collapse;
        }

        .pos-totals td {
            padding: 2px 0;
        }

        .pos-footer {
            margin-top: 8px;
            text-align: center;
            border-top: 1px dashed #ccc;
            padding-top: 4px;
            font-size: 10px;
        }

        .no-print {
            margin: 5px;
            text-align: center;
        }

        .no-print button {
            padding: 4px 8px;
            font-size: 11px;
        }

        @media print {
            .no-print {
                display: none !important;
            }
        }
        .p-5px{
            padding: 5px !important;
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">Print</button>
        <a href="{{ $qrData }}" style="display: inline-block; margin-left: 8px; padding: 4px 8px; background:#4a90e2; color:#fff; border-radius:4px; text-decoration:none;">
            View Full Invoice
        </a>
    </div>

    <div class="pos-container">
        @include('invoice.product-order-pos-content', ['order' => $order, 'company' => $company, 'qrData' => $qrData])
    </div>
</body>
</html>


