@php
    $units = $order->order_products
        ->flatMap(fn($item) => ($item->allUnits ?? collect())->map(function ($unit) use ($item) {
            $unit->line_product_name = $item->product_name ?? ($item->product->name ?? '');
            return $unit;
        }))
        ->filter(fn($unit) => $unit->code || $unit->serial_no || $unit->imei_1 || $unit->imei_2 || $unit->supplier_warranty_end_date || $unit->customer_warranty_end_date);
@endphp

<div class="p-3 border-top bg-white">
    <h6 class="mb-2 font-weight-bold">Unit Tracking</h6>
    @if($units->isEmpty())
        <div class="text-muted">No unit tracking information found for this purchase.</div>
    @else
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Code</th>
                        <th>Status</th>
                        <th>Serial</th>
                        <th>IMEI</th>
                        <th>Supplier Warranty</th>
                        <th>Customer Warranty</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($units as $unit)
                        <tr>
                            <td>{{ $unit->line_product_name }}</td>
                            <td>{{ $unit->code }}</td>
                            <td>{{ $unit->unit_status }}</td>
                            <td>{{ $unit->serial_no }}</td>
                            <td>{{ collect([$unit->imei_1, $unit->imei_2])->filter()->implode(' / ') }}</td>
                            <td>{{ optional($unit->supplier_warranty_end_date)->format('Y-m-d') }}</td>
                            <td>{{ optional($unit->customer_warranty_end_date)->format('Y-m-d') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
