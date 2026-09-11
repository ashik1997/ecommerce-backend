<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Print Full Invoices ({{ count($orders) }})</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; padding: 10px; }
            .invoice-container {
                box-shadow: none !important;
                margin: 0 !important;
                padding: 15px !important;
            }
            .page-break { page-break-inside: avoid; }
            thead { display: table-header-group; }
            tfoot { display: table-footer-group; }
        }
        @page { size: A4; margin: 10mm; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            padding: 15px;
            font-size: 13px;
        }
        .invoice-container {
            max-width: 210mm;
            margin: 0 auto;
            background: white;
            padding: 20px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .invoice-header {
            display: grid;
            grid-template-columns: 100px 1fr 100px;
            gap: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #333;
            margin-bottom: 10px;
            align-items: start;
        }
        .company-logo img { width: 90px; height: auto; }
        .company-info { padding: 0 10px; }
        .company-info h4 { margin: 0 0 5px 0; font-size: 16px; color: #333; font-weight: 700; }
        .company-info p { margin: 0; font-size: 11px; line-height: 1.4; color: #555; }
        .qr-code-header { text-align: center; }
        .qr-code-header img { width: 90px; height: 90px; }
        .info-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 12px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        .info-section { font-size: 12px; }
        .info-section h6 { font-size: 13px; font-weight: 700; color: #333; margin: 0 0 6px 0; padding-bottom: 4px; border-bottom: 1px solid #eee; }
        .info-section p { margin: 3px 0; line-height: 1.5; color: #555; }
        .info-section strong { color: #333; font-weight: 600; }
        .status-badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 10px; font-weight: 700; text-transform: uppercase; }
        .status-invoiced, .status-completed { background: #d4edda; color: #155724; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-delivered { background: #d1ecf1; color: #0c5460; }
        .products-section { margin-bottom: 12px; }
        .products-table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .products-table thead { background: #333; color: white; }
        .products-table thead th { padding: 6px 8px; text-align: left; font-weight: 600; font-size: 11px; border: 1px solid #222; }
        .products-table tbody tr { border-bottom: 1px solid #e0e0e0; }
        .products-table tbody tr:nth-child(even) { background: #f9f9f9; }
        .products-table tbody td { padding: 5px 8px; color: #555; border: 1px solid #e0e0e0; }
        .products-table tbody td:last-child, .products-table thead th:last-child { text-align: right; }
        .products-table thead th:first-child, .products-table tbody td:first-child { text-align: center; }
        .totals-section { display: grid; grid-template-columns: 1fr 300px; gap: 15px; margin-top: 15px; }
        .grand-total-text { font-size: 13px; padding: 10px; background: #f8f9fa; border-radius: 4px; }
        .grand-total-text strong { display: block; font-size: 11px; color: #666; margin-bottom: 3px; }
        .totals-box { border: 2px solid #333; padding: 10px; }
        .total-row { display: flex; justify-content: space-between; padding: 4px 0; font-size: 12px; }
        .total-row.subtotal { border-bottom: 1px solid #ddd; padding-bottom: 5px; margin-bottom: 5px; }
        .total-row.grand-total { border-top: 2px solid #333; padding-top: 6px; margin-top: 6px; font-size: 14px; font-weight: 700; color: #333; }
        .payment-section { margin-top: 12px; padding: 10px; background: #f8f9fa; border: 1px solid #ddd; border-radius: 4px; }
        .payment-section h6 { font-size: 13px; font-weight: 700; color: #333; margin: 0 0 8px 0; }
        .payment-list { list-style: none; padding: 0; margin: 0; font-size: 12px; }
        .payment-list li { padding: 4px 0; border-bottom: 1px dotted #ddd; display: flex; justify-content: space-between; }
        .payment-list li:last-child { border-bottom: none; }
        .payment-totals { margin-top: 8px; padding-top: 8px; border-top: 2px solid #ddd; }
        .payment-totals div { display: flex; justify-content: space-between; padding: 3px 0; font-size: 12px; font-weight: 600; }
        .footer-note { margin-top: 15px; padding-top: 10px; border-top: 1px solid #ddd; text-align: center; color: #666; font-size: 10px; }
        .footer-note p { margin: 3px 0; }
        .note-section { margin-top: 10px; padding: 8px; background: #fff9e6; border-left: 3px solid #ffc107; font-size: 11px; }
        .note-section p { margin: 0; }
        .text-muted { color: #6c757d; }
        .action-buttons { margin-bottom: 15px; display: flex; gap: 10px; justify-content: flex-end; }
        .btn-custom { padding: 8px 15px; border: none; border-radius: 4px; font-weight: 600; cursor: pointer; font-size: 13px; }
        .btn-print { background: #4A90E2; color: white; }
        .btn-print:hover { background: #357ABD; color: white; }
        .invoice-page { page-break-after: always; }
        .invoice-page:last-child { page-break-after: auto; }
    </style>
</head>
<body>
    <div class="action-buttons no-print">
        <button type="button" onclick="window.print()" class="btn-custom btn-print">
            <i class="fas fa-print"></i> Print all full invoices
        </button>
    </div>

    @foreach($orders as $order)
        @php
            $qrData = url(route('order.invoice', $order->slug));
        @endphp
        <div class="invoice-page">
            <div class="invoice-container">
                @include('invoice.product-order-content', ['order' => $order, 'company' => $company, 'qrData' => $qrData])
            </div>
        </div>
    @endforeach
</body>
</html>
