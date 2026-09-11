@extends('backend.master')

@section('page_title')
    Courier Management
@endsection

@section('page_heading')
    Courier Order
@endsection

@section('content')
    <style>
        .order-track-timeline { position: relative; padding-left: 0; }
        .order-track-item { position: relative; padding-left: 3rem; padding-bottom: 1.5rem; }
        .order-track-item:last-child { padding-bottom: 0; }
        .order-track-item::before {
            content: '';
            position: absolute;
            left: 1rem;
            top: 1.75rem;
            bottom: -0.5rem;
            width: 2px;
            background: #dee2e6;
        }
        .order-track-item:last-child::before { display: none; }
        .order-track-dot {
            position: absolute;
            left: 0;
            top: 0.15rem;
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 0.7rem;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        }
        .order-track-content { background: #f8f9fa; border-radius: 8px; padding: 0.6rem 0.9rem; border-left: 3px solid rgba(0,0,0,0.1); }
        .order-track-title { font-weight: 600; color: #333; margin-bottom: 0.2rem; }
        .order-track-meta { margin-bottom: 0.2rem; }
        .order-track-time { font-size: 0.8rem; color: #6c757d; }
    </style>
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="card-title mb-1">Courier Management</h4>
                        <a href="{{ route('ViewAllProductOrder') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Orders
                        </a>
                    </div>

                    <!-- Order Information Card -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-info-circle"></i> Order Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    {{-- @dump($order->toArray()) --}}
                                    <table class="table table-borderless">
                                        <tr>
                                            <th width="40%">Order Code:</th>
                                            <td><strong>{{ $order->order_code }}</strong></td>
                                        </tr>
                                        <tr>
                                            <th>Order Date:</th>
                                            <td>{{ date('d M Y', strtotime($order->sale_date)) }}</td>
                                        </tr>
                                        <tr>
                                            <th>Reference:</th>
                                            <td>{{ $order->reference ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Order Status:</th>
                                            <td>
                                                <span class="badge badge-{{ $order->order_status == 'invoiced' ? 'success' : ($order->order_status == 'pending' ? 'warning' : 'info') }}">
                                                    {{ ucfirst($order->order_status) }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Courier Status:</th>
                                            <td>
                                                <span class="badge badge-{{ $order->is_couriered == 1 ? 'success' : 'warning' }}">
                                                    {{ $order->is_couriered == 1 ? 'Couriered' : 'Not Couriered' }}
                                                </span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <th width="40%">Customer:</th>
                                            <td>{{ $order->customer->name ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Customer Phone:</th>
                                            <td>{{ $order->customer->phone ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Warehouse:</th>
                                            <td>{{ $order->warehouse->title ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Subtotal:</th>
                                            <td>৳ {{ number_format($order->subtotal, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <th>Total Amount:</th>
                                            <td><strong class="text-primary">৳ {{ number_format($order->total, 2) }}</strong></td>
                                        </tr>
                                        <tr>
                                            <th>Paid Amount:</th>
                                            <td>৳ {{ number_format($order->paid_amount, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <th>Due Amount:</th>
                                            <td>
                                                <span class="badge badge-{{ $order->due_amount > 0 ? 'danger' : 'success' }}">
                                                    ৳ {{ number_format($order->due_amount, 2) }}
                                                </span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Products Card -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-box"></i> Order Products</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>SL</th>
                                            <th>Product Name</th>
                                            <th class="text-center">Quantity</th>
                                            <th class="text-right">Unit Price</th>
                                            <th class="text-right">Total Price</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($order->order_products as $index => $product)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>{{ $product->product_name }}</td>
                                                <td class="text-center">{{ $product->qty }}</td>
                                                <td class="text-right">৳ {{ number_format($product->sale_price, 2) }}</td>
                                                <td class="text-right">৳ {{ number_format($product->total_price, 2) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center text-muted">No products found</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="4" class="text-right">Subtotal:</th>
                                            <th class="text-right">৳ {{ number_format($order->subtotal, 2) }}</th>
                                        </tr>
                                        @if($order->calculated_discount_amount > 0)
                                            <tr>
                                                <th colspan="4" class="text-right">Discount:</th>
                                                <th class="text-right text-danger">-৳ {{ number_format($order->calculated_discount_amount, 2) }}</th>
                                            </tr>
                                        @endif
                                        @include('invoice.partials.order-extra-charge-lines', ['order' => $order, 'layout' => 'backend-table', 'labelColspan' => 4])
                                        <tr>
                                            <th colspan="4" class="text-right">Grand Total:</th>
                                            <th class="text-right text-primary">৳ {{ number_format($order->total, 2) }}</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Order tracking timeline -->
                    @php
                        $trackLabels = [
                            'updated' => ['title' => 'Updated', 'icon' => 'fa-sync', 'color' => 'secondary'],
                            'pickup-requested' => ['title' => 'Pickup Requested', 'icon' => 'fa-hand-holding-box', 'color' => 'info'],
                            'assigned-for-pickup' => ['title' => 'Assigned for Pickup', 'icon' => 'fa-user-tag', 'color' => 'info'],
                            'picked' => ['title' => 'Picked', 'icon' => 'fa-box-open', 'color' => 'primary'],
                            'pickup-failed' => ['title' => 'Pickup Failed', 'icon' => 'fa-times-circle', 'color' => 'danger'],
                            'pickup-cancelled' => ['title' => 'Pickup Cancelled', 'icon' => 'fa-ban', 'color' => 'warning'],
                            'at-the-sorting-hub' => ['title' => 'At Sorting Hub', 'icon' => 'fa-warehouse', 'color' => 'primary'],
                            'assigned-for-delivery' => ['title' => 'Assigned for Delivery', 'icon' => 'fa-truck', 'color' => 'info'],
                            'delivered' => ['title' => 'Delivered', 'icon' => 'fa-check-circle', 'color' => 'success'],
                            'partial-delivery' => ['title' => 'Partial Delivery', 'icon' => 'fa-clipboard-list', 'color' => 'warning'],
                            'returned' => ['title' => 'Returned', 'icon' => 'fa-undo', 'color' => 'warning'],
                            'delivery-failed' => ['title' => 'Delivery Failed', 'icon' => 'fa-exclamation-triangle', 'color' => 'danger'],
                            'on-hold' => ['title' => 'On Hold', 'icon' => 'fa-pause-circle', 'color' => 'secondary'],
                            'paid' => ['title' => 'Paid', 'icon' => 'fa-money-bill-wave', 'color' => 'success'],
                            'paid-return' => ['title' => 'Paid Return', 'icon' => 'fa-hand-holding-usd', 'color' => 'success'],
                            'exchanged' => ['title' => 'Exchanged', 'icon' => 'fa-exchange-alt', 'color' => 'info'],
                        ];
                    @endphp

                    @dump($order->toJson())

                    <div class="card mt-4 border-info">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-route text-info"></i> Order Tracking</h5>
                        </div>
                        <div class="card-body">
                            @if(!empty($order->order_tracks) && is_array($order->order_tracks) && count($order->order_tracks) > 0)
                                <div class="order-track-timeline">
                                    @foreach($order->order_tracks as $index => $track)
                                        @php
                                            $status = $track['status'] ?? '';
                                            $info = $trackLabels[$status] ?? ['title' => ucfirst(str_replace('-', ' ', $status)), 'icon' => 'fa-circle', 'color' => 'secondary'];
                                            $time = isset($track['updated_at']) ? \Carbon\Carbon::parse($track['updated_at'])->format('d M Y, h:i A') : '';
                                        @endphp
                                        <div class="order-track-item">
                                            <div class="order-track-dot bg-{{ $info['color'] }}">
                                                <i class="fas {{ $info['icon'] }}"></i>
                                            </div>
                                            <div class="order-track-content">
                                                <div class="order-track-title">{{ $info['title'] }}</div>
                                                @if(!empty($track['consignment_id']))
                                                    <div class="order-track-meta text-muted small">Consignment: {{ $track['consignment_id'] }}</div>
                                                @endif
                                                <div class="order-track-time">{{ $time }}</div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-4 text-muted">
                                    <i class="fas fa-truck fa-3x text-info mb-3"></i>
                                    <p class="mb-0">No tracking events yet for this order.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('footer_js')
    <script>
        // Placeholder for future JavaScript functionality
    </script>
@endsection

