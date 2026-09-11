{{-- Inner content of one full invoice (used by single and bulk). Expects: $order, $company, $qrData --}}
<!-- Compact Header: Logo | Company Address | QR Code -->
<div class="invoice-header">
    <div class="company-logo">
        <img src="{{ $company['logo'] }}" alt="{{ $company['name'] }}" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2290%22 height=%2290%22%3E%3Crect fill=%22%234A90E2%22 width=%2290%22 height=%2290%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 fill=%22white%22 font-family=%22Arial%22 font-size=%2218%22 font-weight=%22bold%22%3EBME%3C/text%3E%3C/svg%3E'">
    </div>
    <div class="company-info">
        <h4>{{ $company['name'] }}</h4>
        <p>
            {{ $company['address'] }}<br>
            <i class="fas fa-phone"></i> {{ $company['phone'] }} |
            <i class="fas fa-envelope"></i> {{ $company['email'] }}<br>
            <i class="fas fa-globe"></i> {{ $company['website'] }}
        </p>
    </div>
    <div class="qr-code-header">
        @php
            $qrUrl = $qrData;
            if (!filter_var($qrUrl, FILTER_VALIDATE_URL)) {
                $qrUrl = url($qrUrl);
            }
        @endphp
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=90x90&data={{ urlencode($qrUrl) }}" alt="QR Code">
    </div>
</div>

<!-- Invoice Info and Customer Info Row -->
<div class="info-row">
    <div class="info-section">
        <h6>INVOICE DETAILS</h6>
        <p><strong>Invoice No:</strong> {{ $order->order_code }}</p>
        <p><strong>Date:</strong> {{ \Carbon\Carbon::parse($order->sale_date)->format('d M, Y') }}</p>
        @if($order->due_date)
        <p><strong>Due Date:</strong> {{ \Carbon\Carbon::parse($order->due_date)->format('d M, Y') }}</p>
        @endif
        <p><strong>Status:</strong> <span class="status-badge status-{{ strtolower($order->order_status) }}">{{ ucfirst($order->order_status) }}</span></p>
        @if($order->reference)
        <p><strong>Reference:</strong> {{ $order->reference }}</p>
        @endif
        @if($order->warehouse)
        <p><strong>Warehouse:</strong> {{ $order->warehouse->name }}</p>
        @endif
    </div>
    <div class="info-section">
        <h6>CUSTOMER INFORMATION</h6>
        <p><strong>Name:</strong> {{ $order->customer->name ?? 'N/A' }}</p>
        <p><strong>Phone:</strong> {{ $order->customer->phone ?? 'N/A' }}</p>
        @if($order->customer && $order->customer->email)
        <p><strong>Email:</strong> {{ $order->customer->email }}</p>
        @endif
        @if($order->customer && $order->customer->address)
        <p><strong>Address:</strong> {{ $order->customer->address }}</p>
        @endif
    </div>
</div>

<!-- Products Section -->
<div class="products-section">
    <table class="products-table">
        <thead>
            <tr>
                <th style="width: 4%;">#</th>
                <th style="width: 38%;">Product Name</th>
                <th style="width: 12%;">Unit Price</th>
                <th style="width: 8%;">Qty</th>
                {{-- <th style="width: 10%;">Disc (%)</th> --}}
                {{-- <th style="width: 10%;">Tax (%)</th> --}}
                <th style="width: 18%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->order_products as $index => $product)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>
                    <strong>{{ $product->product_name }}</strong>
                    @if($product->variant)
                        @php
                            $variantValues = $product->variant->variant_values ?? [];
                            $variantName = $product->variant->combination_key ?? '';
                            if (is_array($variantValues) && !empty($variantValues)) {
                                $formattedValues = collect($variantValues)->map(function($value, $key) {
                                    return ucfirst(str_replace('_', ' ', $key)) . ': ' . $value;
                                })->implode(' | ');
                                if ($formattedValues) {
                                    $variantName = $formattedValues;
                                }
                            }
                        @endphp
                        <br><small class="text-muted">{{ $variantName ?: $product->variant->name }}</small>
                    @elseif($product->unitPrice)
                        <br><small class="text-muted">Unit: {{ $product->unitPrice->unit_label }}</small>
                    @endif
                </td>
                <td>৳{{ number_format($product->sale_price, 2) }}</td>
                <td>{{ $product->qty }}</td>
                {{-- <td>{{ $product->discount_amount }}%</td> --}}
                {{-- <td>{{ $product->tax }}%</td> --}}
                <td>৳{{ number_format($product->total_price, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- Totals Section -->
<div class="totals-section">
    <div class="grand-total-text">
        <strong>Grand Total (in words):</strong>
        {{ numberToWords($order->total) }} Taka Only
    </div>
    <div class="totals-box">
        <div class="total-row subtotal">
            <span>Subtotal:</span>
            <span>৳{{ number_format($order->subtotal, 2) }}</span>
        </div>
        @include('invoice.partials.order-extra-charge-lines', ['order' => $order])
        @if($order->calculated_discount_amount > 0)
        <div class="total-row" style="color: #dc3545;">
            <span>Discount:</span>
            <span>- ৳{{ number_format($order->calculated_discount_amount, 2) }}</span>
        </div>
        @endif
        @if($order->decimal_round_off != 0)
        <div class="total-row">
            <span>Round Off:</span>
            <span>৳{{ number_format($order->decimal_round_off, 2) }}</span>
        </div>
        @endif
        <div class="total-row grand-total">
            <span>Grand Total:</span>
            <span>৳{{ number_format($order->total, 2) }}</span>
        </div>
    </div>
</div>

<!-- Payment Information -->
@if($order->payments && is_array($order->payments))
<div class="payment-section">
    <h6><i class="fas fa-money-bill-wave"></i> Payment Information</h6>
    <ul class="payment-list">
        @foreach($order->payments as $method => $amount)
            @if($amount > 0 && !in_array($method, ['total_paid', 'total_due']))
            <li>
                <span><i class="fas fa-check-circle" style="color: #28a745;"></i> {{ ucfirst($method) }}</span>
                <strong>৳{{ number_format($amount, 2) }}</strong>
            </li>
            @endif
        @endforeach
    </ul>
    <div class="payment-totals">
        <div>
            <span>Total Paid:</span>
            <span style="color: #28a745;">৳{{ number_format($order->paid_amount, 2) }}</span>
        </div>
        @if($order->due_amount > 0)
        <div>
            <span>Amount Due:</span>
            <span style="color: #dc3545;">৳{{ number_format($order->due_amount, 2) }}</span>
        </div>
        @endif
    </div>
</div>
@endif

@if($order->note)
<div class="note-section">
    <p><strong>Note:</strong> {{ $order->note }}</p>
</div>
@endif

<div class="footer-note">
    <p>Thank you for your business!</p>
    <p>This is a computer-generated invoice and does not require a signature.</p>
    <p>Generated on {{ now()->format('d M, Y h:i A') }}</p>
</div>
