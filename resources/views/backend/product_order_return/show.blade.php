<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Return Invoice #{{ $return->return_code }}</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
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
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .company-info h4 {
            margin: 0;
            font-size: 20px;
            color: #333;
            font-weight: 700;
        }

        .company-info p {
            margin: 2px 0;
            font-size: 12px;
            color: #555;
        }

        .return-badge {
            background: #dc3545;
            color: white;
            padding: 5px 15px;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
        }

        .info-section {
            margin: 20px 0;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .info-label {
            font-weight: 600;
            color: #333;
        }

        .products-table {
            width: 100%;
            margin: 20px 0;
        }

        .products-table th {
            background: #495057;
            color: white;
            padding: 10px;
            text-align: left;
        }

        .products-table td {
            padding: 8px;
            border-bottom: 1px solid #dee2e6;
        }

        .totals-section {
            margin-top: 20px;
            text-align: right;
        }

        .total-row {
            display: flex;
            justify-content: flex-end;
            margin: 5px 0;
            font-size: 14px;
        }

        .total-label {
            margin-right: 20px;
            min-width: 120px;
            text-align: right;
        }

        .total-amount {
            min-width: 150px;
            text-align: right;
            font-weight: 600;
        }

        .grand-total {
            font-size: 16px;
            font-weight: 700;
            color: #333;
            border-top: 2px solid #333;
            padding-top: 8px;
            margin-top: 8px;
        }

        .no-print {
            margin: 20px 0;
        }

        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: white;
                padding: 0;
            }
            .invoice-container {
                box-shadow: none;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <!-- Header -->
        <div class="invoice-header">
            <div class="row">
                <div class="col-md-8">
                    <div class="company-info">
                        <h4>{{ $company['name'] }}</h4>
                        <p><i class="fas fa-map-marker-alt"></i> {{ $company['address'] }}</p>
                        <p><i class="fas fa-phone"></i> {{ $company['phone'] }}</p>
                        <p><i class="fas fa-envelope"></i> {{ $company['email'] }}</p>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <div class="return-badge">RETURN INVOICE</div>
                    <p class="mt-2"><strong>Return Code:</strong> {{ $return->return_code }}</p>
                    <p><strong>Return Date:</strong> {{ $return->return_date }}</p>
                </div>
            </div>
        </div>

        <!-- Customer and Order Information -->
        <div class="info-section">
            <div class="row">
                <div class="col-md-6">
                    <h6><strong>Customer Information:</strong></h6>
                    <p><strong>Name:</strong> {{ $return->customer->name ?? 'N/A' }}</p>
                    @if($return->customer)
                        <p><strong>Phone:</strong> {{ $return->customer->phone ?? 'N/A' }}</p>
                        <p><strong>Address:</strong> {{ $return->customer->address ?? 'N/A' }}</p>
                    @endif
                </div>
                <div class="col-md-6">
                    <h6><strong>Original Order Information:</strong></h6>
                    <p><strong>Order Code:</strong> {{ $return->originalOrder->order_code ?? 'N/A' }}</p>
                    <p><strong>Order Date:</strong> {{ $return->originalOrder->sale_date ?? 'N/A' }}</p>
                    <p><strong>Refund Method:</strong> <span class="badge bg-success">{{ ucfirst(str_replace('_', ' ', $return->refund_method)) }}</span></p>
                </div>
            </div>
        </div>

        @if($return->return_reason)
        <div class="alert alert-warning">
            <strong>Return Reason:</strong> {{ $return->return_reason }}
        </div>
        @endif

        <!-- Products Table -->
        <table class="products-table table">
            <thead>
                <tr>
                    <th>SL</th>
                    <th>Product Name</th>
                    <th>Return Qty</th>
                    <th>Unit Price</th>
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($return->return_products as $index => $product)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $product->product_name }}</td>
                    <td>{{ $product->qty }}</td>
                    <td>৳{{ number_format($product->sale_price, 2) }}</td>
                    <td class="text-end">৳{{ number_format($product->total_price, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals-section">
            <div class="total-row">
                <div class="total-label">Subtotal:</div>
                <div class="total-amount">৳{{ number_format($return->subtotal, 2) }}</div>
            </div>
            <div class="total-row grand-total">
                <div class="total-label">Total Refund Amount:</div>
                <div class="total-amount">৳{{ number_format($return->total, 2) }}</div>
            </div>
            <div class="total-row">
                <div class="total-label">Refunded:</div>
                <div class="total-amount text-success">৳{{ number_format($return->refunded_amount ?? 0, 2) }}</div>
            </div>
            <div class="total-row">
                <div class="total-label">Payable:</div>
                <div class="total-amount text-danger">৳{{ number_format(max(0, ($return->total ?? 0) - ($return->refunded_amount ?? 0)), 2) }}</div>
            </div>
        </div>

        @if($return->refunds && $return->refunds->count())
            <div class="mt-4">
                <h6><strong>Refund History:</strong></h6>
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>Refund Code</th>
                            <th>Date</th>
                            <th>Payment Type</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($return->refunds as $refund)
                            <tr>
                                <td>
                                    <a href="{{ route('ShowProductOrderRefund', $refund->slug) }}" target="_blank">
                                        {{ $refund->refund_code }}
                                    </a>
                                </td>
                                <td>{{ $refund->refund_date }}</td>
                                <td>{{ $refund->payment_type_snapshot ?? optional($refund->paymentType)->payment_type ?? 'N/A' }}</td>
                                <td class="text-end">৳{{ number_format($refund->refund_amount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @php
            $payableAmount = max(0, ($return->total ?? 0) - ($return->refunded_amount ?? 0));
        @endphp
        @if($payableAmount > 0)
            <div class="no-print mt-4 p-3 border rounded">
                <h6><strong>Post Refund</strong></h6>
                @if($errors->any())
                    <div class="alert alert-danger py-2">
                        {{ $errors->first() }}
                    </div>
                @endif
                <form action="{{ route('RefundProductOrderReturn', $return->slug) }}" method="POST">
                    @csrf
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Refund Date</label>
                            <input type="date" name="refund_date" value="{{ old('refund_date', now()->toDateString()) }}" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Amount</label>
                            <input type="number" name="refund_amount" value="{{ old('refund_amount', number_format($payableAmount, 2, '.', '')) }}" max="{{ number_format($payableAmount, 2, '.', '') }}" step="0.01" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Payment Type</label>
                            <select name="payment_type_id" class="form-control" required>
                                <option value="">Select payment type</option>
                                @foreach($paymentMethods as $paymentMethod)
                                    <option value="{{ $paymentMethod->id }}" {{ old('payment_type_id') == $paymentMethod->id ? 'selected' : '' }}>{{ $paymentMethod->payment_type }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Note</label>
                            <input type="text" name="note" value="{{ old('note') }}" class="form-control">
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="fas fa-money-bill-wave"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        @endif

        @if($return->note)
        <div class="mt-4">
            <strong>Note:</strong>
            <p>{{ $return->note }}</p>
        </div>
        @endif

        <!-- Footer Note -->
        <div class="mt-5 pt-3 border-top text-center">
            <p class="text-muted small">This is a computer-generated return invoice. No signature is required.</p>
            <p class="text-muted small">Return Status: <strong class="text-success">{{ ucfirst($return->return_status) }}</strong></p>
        </div>

        <!-- Action Buttons -->
        <div class="no-print text-center mt-4">
            @if($return->status === 'active')
                <form action="{{ route('ReverseProductOrderReturn', $return->slug) }}" method="POST" class="d-inline" onsubmit="return confirm('Reverse this return? Active refunds must be reversed first.');">
                    @csrf
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-undo"></i> Reverse Return
                    </button>
                </form>
            @endif
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Print
            </button>
            <a href="{{ route('ViewAllProductOrderReturns') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Returns
            </a>
        </div>
    </div>
</body>
</html>

