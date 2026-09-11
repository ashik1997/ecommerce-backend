@php
    $isPublic    = $isPublic ?? false;
    $publicUrl   = route('PublicPurchaseInvoice', $order->slug);
    $emailRoute  = route('SendPurchaseInvoiceEmail', $order->slug);

    $supplier    = $order->supplier;
    $warehouse   = $order->warehouse;
    $createdBy   = $order->creator;

    $otherCharges = is_array($order->other_charge_type) ? $order->other_charge_type : [];
    $subtotal     = (float)($order->subtotal ?? 0);
    $discount     = (float)($order->calculated_discount_amount ?? 0);
    $otherTotal   = (float)($order->other_charge_amount ?? 0);
    $roundOff     = (float)($order->round_off ?? 0);
    $grandTotal   = (float)($order->total ?? 0);

    $statusClass = match($order->order_status ?? 'pending') {
        'received'  => 'status-received',
        'pending'   => 'status-pending',
        'cancelled' => 'status-cancelled',
        default     => 'status-pending',
    };

    $logoPath = $generalInfo->logo ?? null;
    $logoUrl  = $logoPath ? get_file_url() . '/' . $logoPath : null;
    $company  = $generalInfo->company_name  ?? config('app.name', 'Company');
    $address  = $generalInfo->address       ?? '';
    $phone    = $generalInfo->phone         ?? '';
    $email    = $generalInfo->email         ?? '';
    $website  = $generalInfo->website       ?? '';
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Invoice #{{ $order->code ?? '' }}</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
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

        /* Header */
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

        /* Info Row */
        .info-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 12px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        .info-section { font-size: 12px; }
        .info-section h6 {
            font-size: 13px; font-weight: 700; color: #333;
            margin: 0 0 6px 0; padding-bottom: 4px;
            border-bottom: 1px solid #eee;
        }
        .info-section p { margin: 3px 0; line-height: 1.5; color: #555; }
        .info-section strong { color: #333; font-weight: 600; }

        /* Status badges */
        .status-badge {
            display: inline-block; padding: 2px 8px;
            border-radius: 3px; font-size: 10px;
            font-weight: 700; text-transform: uppercase;
        }
        .status-received  { background: #d4edda; color: #155724; }
        .status-pending   { background: #fff3cd; color: #856404; }
        .status-cancelled { background: #f8d7da; color: #721c24; }

        /* Products */
        .products-section { margin-bottom: 12px; }
        .products-table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .products-table thead { background: #333; color: white; }
        .products-table thead th {
            padding: 6px 8px; text-align: left;
            font-weight: 600; font-size: 11px;
            border: 1px solid #222; white-space: nowrap;
        }
        .products-table tbody tr { border-bottom: 1px solid #e0e0e0; }
        .products-table tbody tr:nth-child(even) { background: #f9f9f9; }
        .products-table tbody td {
            padding: 5px 8px; color: #555;
            border: 1px solid #e0e0e0;
        }
        .products-table tbody td:last-child,
        .products-table thead th:last-child { text-align: right; }
        .products-table thead th:first-child,
        .products-table tbody td:first-child { text-align: center; }
        .text-center { text-align: center !important; }
        .unit-tracking {
            margin-top: 5px;
            font-size: 10px;
            line-height: 1.45;
            color: #666;
        }
        .unit-tracking div { margin-top: 2px; }
        .unit-tracking code { color: #333; font-size: 10px; }

        /* Totals */
        .totals-section {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 15px;
            margin-top: 15px;
        }
        .grand-total-text {
            font-size: 13px; padding: 10px;
            background: #f8f9fa; border-radius: 4px;
        }
        .grand-total-text strong {
            display: block; font-size: 11px;
            color: #666; margin-bottom: 3px;
        }
        .totals-box { border: 2px solid #333; padding: 10px; }
        .total-row {
            display: flex; justify-content: space-between;
            padding: 4px 0; font-size: 12px;
        }
        .total-row.subtotal {
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px; margin-bottom: 5px;
        }
        .total-row.grand-total {
            border-top: 2px solid #333;
            padding-top: 6px; margin-top: 6px;
            font-size: 14px; font-weight: 700; color: #333;
        }

        /* Payment */
        .payment-section {
            margin-top: 12px; padding: 10px;
            background: #f8f9fa;
            border: 1px solid #ddd; border-radius: 4px;
        }
        .payment-section h6 {
            font-size: 13px; font-weight: 700;
            color: #333; margin: 0 0 8px 0;
        }
        .payment-list { list-style: none; padding: 0; margin: 0; font-size: 12px; }
        .payment-list li {
            padding: 4px 0; border-bottom: 1px dotted #ddd;
            display: flex; justify-content: space-between;
        }
        .payment-list li:last-child { border-bottom: none; }
        .payment-totals { margin-top: 8px; padding-top: 8px; border-top: 2px solid #ddd; }
        .payment-totals div {
            display: flex; justify-content: space-between;
            padding: 3px 0; font-size: 12px; font-weight: 600;
        }

        /* Note */
        .note-section {
            margin-top: 10px; padding: 8px;
            background: #fff9e6;
            border-left: 3px solid #ffc107; font-size: 11px;
        }
        .note-section p { margin: 0; }

        /* Footer */
        .footer-note {
            margin-top: 15px; padding-top: 10px;
            border-top: 1px solid #ddd;
            text-align: center; color: #666; font-size: 10px;
        }
        .footer-note p { margin: 3px 0; }

        /* Action Buttons */
        .action-buttons {
            margin-bottom: 15px;
            display: flex; gap: 10px; justify-content: flex-end;
            flex-wrap: wrap; align-items: center;
        }
        .btn-custom {
            padding: 8px 15px; border: none; border-radius: 4px;
            font-weight: 600; cursor: pointer; transition: all 0.3s;
            text-decoration: none; display: inline-flex;
            align-items: center; gap: 6px; font-size: 13px;
        }
        .btn-print  { background: #4A90E2; color: white; }
        .btn-print:hover  { background: #357ABD; }
        .btn-email  { background: #28a745; color: white; }
        .btn-email:hover  { background: #218838; }
        .btn-copy   { background: #6c757d; color: white; }
        .btn-copy:hover   { background: #5a6268; }

        @media (max-width: 575.9px) {
            .totals-section, .invoice-header, .info-row { grid-template-columns: 1fr; }
            .products-section { overflow-x: auto; }
        }

        @media print {
            .no-print { display: none !important; }
            body { margin: 0; padding: 10px; background: white; }
            .invoice-container { box-shadow: none !important; margin: 0 !important; padding: 15px !important; }
            .page-break { page-break-inside: avoid; }
            thead { display: table-header-group; }
            tfoot { display: table-footer-group; }
            .products-table tbody td { color: black; border: 1px solid black; }
            .products-table thead { background: #e1e1e1 !important; color: #000 !important; }
        }
    </style>
</head>
<body>

{{-- ── ACTION BUTTONS (no-print) ── --}}
<div class="action-buttons no-print">
    <button onclick="window.print()" class="btn-custom btn-print">
        <i class="fas fa-print"></i> Print
    </button>
    @if(!$isPublic)
    <button onclick="emailPurchaseInvoice()" class="btn-custom btn-email">
        <i class="fas fa-envelope"></i> Email Invoice
    </button>
    <button onclick="copyPublicLink()" class="btn-custom btn-copy">
        <i class="fas fa-link"></i> Copy Public Link
    </button>
    @endif
</div>

<div class="invoice-container">

    {{-- ── HEADER: Logo | Company | QR ── --}}
    <div class="invoice-header">
        <div class="company-logo">
            @if($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $company }}"
                    onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2290%22 height=%2290%22%3E%3Crect fill=%22%23333%22 width=%2290%22 height=%2290%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 fill=%22white%22 font-family=%22Arial%22 font-size=%2216%22 font-weight=%22bold%22%3EPO%3C/text%3E%3C/svg%3E'">
            @else
                <div style="width:90px;height:90px;background:#333;display:flex;align-items:center;justify-content:center;border-radius:4px;">
                    <span style="color:white;font-size:20px;font-weight:800;">PO</span>
                </div>
            @endif
        </div>

        <div class="company-info">
            <h4>{{ $company }}</h4>
            <p>
                @if($address){{ $address }}<br>@endif
                @if($phone)<i class="fas fa-phone"></i> {{ $phone }}@endif
                @if($phone && $email) | @endif
                @if($email)<i class="fas fa-envelope"></i> {{ $email }}@endif
                @if($website)<br><i class="fas fa-globe"></i> {{ $website }}@endif
            </p>
        </div>

        <div class="qr-code-header">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=90x90&data={{ urlencode($publicUrl) }}"
                alt="QR Code">
        </div>
    </div>

    {{-- ── PURCHASE DETAILS | SUPPLIER INFO ── --}}
    <div class="info-row">
        <div class="info-section">
            <h6>PURCHASE DETAILS</h6>
            <p><strong>PO Number:</strong> {{ $order->code ?? 'N/A' }}</p>
            <p><strong>Date:</strong>
                {{ $order->date ? \Carbon\Carbon::parse($order->date)->format('d M, Y') : '—' }}
            </p>
            <p><strong>Status:</strong>
                <span class="status-badge {{ $statusClass }}">
                    {{ ucfirst($order->order_status ?? 'pending') }}
                </span>
            </p>
            @if($order->reference)
                <p><strong>Reference:</strong> {{ $order->reference }}</p>
            @endif
            @if($warehouse)
                <p><strong>Warehouse:</strong> {{ $warehouse->name ?? '' }}</p>
            @endif
            @if($createdBy)
                <p><strong>Created By:</strong> {{ $createdBy->name ?? '' }}</p>
            @endif
        </div>

        <div class="info-section">
            <h6>SUPPLIER INFORMATION</h6>
            @if($supplier)
                <p><strong>Name:</strong> {{ $supplier->name ?? '—' }}</p>
                @if(!empty($supplier->phone))
                    <p><strong>Phone:</strong> {{ $supplier->phone }}</p>
                @endif
                @if(!empty($supplier->email))
                    <p><strong>Email:</strong> {{ $supplier->email }}</p>
                @endif
                @if(!empty($supplier->address))
                    <p><strong>Address:</strong> {{ $supplier->address }}</p>
                @endif
            @else
                <p>—</p>
            @endif
        </div>
    </div>

    {{-- ── PRODUCTS TABLE ── --}}
    <div class="products-section">
        <table class="products-table">
            <thead>
                <tr>
                    <th style="width:4%;">#</th>
                    <th style="width:34%;">Product Name</th>
                    <th style="width:12%;" class="text-center">Unit Price</th>
                    <th style="width:7%;" class="text-center">Qty</th>
                    <th style="width:10%;" class="text-center">Disc (%)</th>
                    <th style="width:10%;" class="text-center">Tax (%)</th>
                    <th style="width:14%;">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($order->order_products as $i => $item)
                    @php
                        $lineTotal    = (float)$item->purchase_price * (float)$item->qty;
                        $variantLabel = null;
                        if (!empty($item->variantCombination)) {
                            $vals = $item->variantCombination->variant_values ?? [];
                            if (is_array($vals) && count($vals)) {
                                $variantLabel = collect($vals)
                                    ->map(fn($v,$k) => ucfirst(str_replace('_',' ',$k)).': '.$v)
                                    ->implode(' | ');
                            } else {
                                $variantLabel = $item->variantCombination->combination_key ?? null;
                            }
                        }
                        $trackedUnits = collect($item->allUnits ?? [])->filter(function ($unit) {
                            return !empty($unit->serial_no)
                                || !empty($unit->imei_1)
                                || !empty($unit->imei_2)
                                || !empty($unit->supplier_warranty_end_date)
                                || !empty($unit->customer_warranty_end_date)
                                || !empty($unit->warranty_note);
                        });
                    @endphp
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>
                            <strong>{{ $item->product_name ?? ($item->product->name ?? '—') }}</strong>
                            @if($variantLabel)
                                <br><small style="color:#777;">{{ $variantLabel }}</small>
                            @endif
                            @if($trackedUnits->isNotEmpty())
                                <div class="unit-tracking">
                                    @foreach($trackedUnits as $unit)
                                        @php
                                            $warrantyEnd = $unit->supplier_warranty_end_date ?? $unit->customer_warranty_end_date ?? null;
                                        @endphp
                                        <div>
                                            @if($unit->code)
                                                <code>{{ $unit->code }}</code>
                                            @endif
                                            @if($unit->serial_no)
                                                <span>Serial: {{ $unit->serial_no }}</span>
                                            @endif
                                            @if($unit->imei_1 || $unit->imei_2)
                                                <span>IMEI: {{ collect([$unit->imei_1, $unit->imei_2])->filter()->implode(' / ') }}</span>
                                            @endif
                                            @if($warrantyEnd)
                                                <span>Warranty: {{ \Carbon\Carbon::parse($warrantyEnd)->format('Y-m-d') }}</span>
                                            @endif
                                            @if($unit->warranty_note)
                                                <span>Note: {{ $unit->warranty_note }}</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="text-center">{{ number_format((float)$item->product_price, 2) }}</td>
                        <td class="text-center">{{ $item->qty }}</td>
                        <td class="text-center">{{ number_format((float)$item->discount_amount, 2) }}%</td>
                        <td class="text-center">{{ number_format((float)$item->tax, 2) }}%</td>
                        <td>{{ number_format($lineTotal, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center;padding:20px;color:#999;">
                            No products found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ── TOTALS ── --}}
    <div class="totals-section">
        <div class="grand-total-text">
            <strong>Grand Total (in words):</strong>
            @if(function_exists('numberToWords'))
                {{ numberToWords($grandTotal) }} Only
            @else
                {{ strtoupper(number_format($grandTotal, 2)) }}
            @endif
        </div>

        <div class="totals-box">
            <div class="total-row subtotal">
                <span>Subtotal:</span>
                <span>{{ number_format($subtotal, 2) }}</span>
            </div>

            @if($discount > 0)
            <div class="total-row" style="color:#dc3545;">
                <span>Discount:</span>
                <span>- {{ number_format($discount, 2) }}</span>
            </div>
            @endif

            @if($otherTotal > 0)
            <div class="total-row">
                <span>Other Charges:</span>
                <span>{{ number_format($otherTotal, 2) }}</span>
            </div>
            @endif

            @if($roundOff != 0)
            <div class="total-row">
                <span>Round Off:</span>
                <span>{{ $roundOff >= 0 ? '+' : '' }}{{ number_format($roundOff, 2) }}</span>
            </div>
            @endif

            <div class="total-row grand-total">
                <span>Grand Total:</span>
                <span>{{ number_format($grandTotal, 2) }}</span>
            </div>
        </div>
    </div>

    {{-- ── PAYMENT INFORMATION ── --}}
    <div class="payment-section">
        <h6><i class="fas fa-money-bill-wave"></i> Payment Information</h6>
        @if(!empty($otherCharges))
        <ul class="payment-list">
            @foreach($otherCharges as $charge)
                @if(isset($charge['name']) && isset($charge['amount']) && (float)$charge['amount'] > 0)
                <li>
                    <span>
                        <i class="fas fa-tag" style="color:#555;"></i>
                        {{ $charge['name'] }}
                        @if(isset($charge['type']) && $charge['type'] === 'percent')
                            ({{ $charge['amount'] }}%)
                        @endif
                    </span>
                    <strong>{{ number_format((float)$charge['amount'], 2) }}</strong>
                </li>
                @endif
            @endforeach
        </ul>
        @endif

        <div class="payment-totals">
            <div>
                <span>Total Amount:</span>
                <span>{{ number_format($grandTotal, 2) }}</span>
            </div>
            <div>
                <span>Amount Due:</span>
                <span style="color:#dc3545;">{{ number_format($grandTotal, 2) }}</span>
            </div>
        </div>
    </div>

    {{-- ── NOTE ── --}}
    @if($order->note)
    <div class="note-section">
        <p><strong>Note:</strong> {{ $order->note }}</p>
    </div>
    @endif

    {{-- ── FOOTER ── --}}
    <div class="footer-note">
        <p>This is a computer-generated purchase invoice and does not require a signature.</p>
        <p>{{ $company }}@if($email) &nbsp;|&nbsp; {{ $email }}@endif</p>
        <p>Generated on {{ now()->format('d M, Y h:i A') }}</p>
    </div>

</div>{{-- end .invoice-container --}}

@if(!$isPublic)
<script>
    function emailPurchaseInvoice() {
        const defaultEmail = '{{ $supplier->email ?? '' }}';
        const email = prompt('Enter email address to send invoice:', defaultEmail);
        if (email) {
            fetch('{{ $emailRoute }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ email: email })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('Invoice sent successfully to ' + email);
                } else {
                    alert('Error: ' + (data.message ?? 'Unknown error'));
                }
            })
            .catch(err => alert('Error sending email: ' + err.message));
        }
    }

    function copyPublicLink() {
        navigator.clipboard.writeText('{{ $publicUrl }}')
            .then(() => alert('Public link copied to clipboard!'))
            .catch(() => {
                prompt('Copy this link:', '{{ $publicUrl }}');
            });
    }
</script>
@endif

</body>
</html>
