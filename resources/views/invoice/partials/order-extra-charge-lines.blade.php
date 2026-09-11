@php
    $layout = $layout ?? 'div';
    $labelColspan = $labelColspan ?? 1;
    $otherCharges = $order->other_charges ?? [];
    if (is_string($otherCharges)) {
        $otherCharges = json_decode($otherCharges, true) ?: [];
    }
    $extraChargeLines = $otherCharges['extra_charge_lines'] ?? [];
    $extraChargeLines = is_array($extraChargeLines) ? array_values(array_filter($extraChargeLines, function ($line) {
        return is_array($line) && ((float)($line['amount'] ?? 0)) > 0;
    })) : [];
    $extraChargeTotal = count($extraChargeLines)
        ? array_sum(array_map(fn ($line) => (float)($line['amount'] ?? 0), $extraChargeLines))
        : (float)($order->other_charge_amount ?? 0);
@endphp

@if ($extraChargeTotal > 0)
    @if ($layout === 'pos-table')
        <tr>
            <td>Other Charges</td>
            <td class="text-right">{{ number_format($extraChargeTotal, 2) }}</td>
        </tr>
        @foreach ($extraChargeLines as $line)
            <tr>
                <td style="padding-left: 10px; color: #555;">- {{ $line['title'] ?? 'Extra Charge' }}</td>
                <td class="text-right" style="color: #555;">{{ number_format((float)($line['amount'] ?? 0), 2) }}</td>
            </tr>
        @endforeach
    @elseif ($layout === 'backend-table')
        <tr>
            <th colspan="{{ $labelColspan }}" class="text-right">Other Charges:</th>
            <th class="text-right">৳ {{ number_format($extraChargeTotal, 2) }}</th>
        </tr>
        @foreach ($extraChargeLines as $line)
            <tr>
                <td colspan="{{ $labelColspan }}" class="text-right text-muted">- {{ $line['title'] ?? 'Extra Charge' }}</td>
                <td class="text-right text-muted">৳ {{ number_format((float)($line['amount'] ?? 0), 2) }}</td>
            </tr>
        @endforeach
    @else
        <div class="total-row">
            <span>Other Charges:</span>
            <span>৳{{ number_format($extraChargeTotal, 2) }}</span>
        </div>
        @foreach ($extraChargeLines as $line)
            <div class="total-row" style="font-size: 12px; color: #666;">
                <span style="padding-left: 12px;">- {{ $line['title'] ?? 'Extra Charge' }}</span>
                <span>৳{{ number_format((float)($line['amount'] ?? 0), 2) }}</span>
            </div>
        @endforeach
    @endif
@endif
