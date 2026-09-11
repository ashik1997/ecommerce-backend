{{-- Inner content of one POS invoice (used by single and bulk print). Expects: $order, $company, $qrData --}}
<div class="pos-header">
    <h4>{{ $company['name'] }}</h4>
    <p>{{ $company['address'] }}</p>
    <p>{{ $company['phone'] }}</p>
</div>

<div class="pos-qr">
    <img src="https://api.qrserver.com/v1/create-qr-code/?size=90x90&data={{ urlencode($qrData) }}" alt="QR">
</div>

<div class="pos-meta">
    <table>
        <tr>
            <td>Invoice</td>
            <td class="text-right">#{{ $order->order_code }}</td>
        </tr>
        <tr>
            <td>Date</td>
            <td class="text-right">{{ \Carbon\Carbon::parse($order->created_at)->format('d M Y h:i A') }}</td>
        </tr>
    </table>
</div>

<div class="pos-customer">
    <table>
        <tr>
            <td>Customer</td>
            <td class="text-right">{{ $order->customer_name ?? '-' }}</td>
        </tr>
        @if($order->customer_phone)
            <tr>
                <td>Phone</td>
                <td class="text-right">{{ $order->customer_phone ?? '-' }}</td>
            </tr>
        @endif
    </table>
</div>

<div class="pos-products">
    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th class="text-right p-5px">Qty</th>
                <th class="text-right p-5px">Price</th>
                <th class="text-right p-5px">Total</th>
            </tr>
        </thead>
        <tbody>
            @php
                $main_total = 0;
                $sale_price_total = 0;
            @endphp
            @foreach($order->order_products as $item)
                @php
                    $main_total += $item->product_price * $item->qty;
                    $sale_price_total += $item->sale_price * $item->qty;
                @endphp
                <tr>
                    <td>
                        {{ $item->product_name }}
                        @if($item->variant)
                            <br><small>{{ $item->variant->name }}</small>
                        @endif
                        @if($item->product_note)
                            <br><small>{{ $item->product_note }}</small>
                        @endif
                    </td>
                    <td class="text-right p-5px">{{ $item->qty }}</td>
                    <td class="text-right p-5px">{{ number_format(($item->sale_price), 2) }}</td>
                    <td class="text-right p-5px">{{ number_format($item->total_price, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="pos-totals">
    <table>
        <tr>
            <td>Subtotal</td>
            <td class="text-right">{{ number_format($order->subtotal, 2) }}</td>
        </tr>
        @include('invoice.partials.order-extra-charge-lines', ['order' => $order, 'layout' => 'pos-table'])
        @if($order->calculated_discount_amount > 0)
            <tr>
                <td>Discount</td>
                <td class="text-right">-{{ number_format($order->calculated_discount_amount, 2) }}</td>
            </tr>
        @endif
        @if($order->decimal_round_off != 0)
            <tr>
                <td>Round Off</td>
                <td class="text-right">{{ number_format($order->decimal_round_off, 2) }}</td>
            </tr>
        @endif
        <tr>
            <td><strong>Grand Total</strong></td>
            <td class="text-right"><strong>{{ number_format($order->total, 2) }}</strong></td>
        </tr>
        <tr>
            <td>Paid</td>
            <td class="text-right">{{ number_format($order->paid_amount, 2) }}</td>
        </tr>
        <tr>
            <td>Due</td>
            <td class="text-right">{{ number_format($order->due_amount, 2) }}</td>
        </tr>
        
        @php
            $total_dis = ($calculated_discount_amount ?? 0) + ($decimal_round_off ?? 0);
        @endphp
        @if ($total_dis>0)
        <tr>
            <td colspan="2">
                <hr style="margin: 0; border: 0; border-top: 1px dashed #000;">
            </td>
        </tr>
        <tr style="color: #777777;">
            <td>Discount On Products</td>
            <th class="text-right">{{ number_format($total_dis, 0) }}</th>
        </tr>   
        @endif
        
    </table>
</div>

<div class="pos-footer">
    <p>Scan the QR to view this invoice online.</p>
    <p>Thank you for your purchase.</p>
    <p>
        <a href="{{ config('app.app_frontend_url') . '/order-invoice/' . $order->slug }}" target="_blank" style="text-decoration: none;">
            View Full Invoice
        </a>
    </p>
    <p style="margin-top: 8px; font-size: 11px; color: #777;">
        Powered by <strong>{{ config('app.name') }}</strong><br>
        a product by <strong>{{ config('app.company_name') }}</strong>
    </p>
</div>
