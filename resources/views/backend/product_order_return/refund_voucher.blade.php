<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Refund Voucher #{{ $refund->refund_code }}</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            padding: 15px;
            font-size: 13px;
        }

        .voucher {
            max-width: 210mm;
            margin: 0 auto;
            background: #fff;
            padding: 22px;
            box-shadow: 0 0 20px rgba(15, 23, 42, .10);
        }

        .voucher-header {
            border-bottom: 2px solid #111827;
            padding-bottom: 16px;
            margin-bottom: 20px;
        }

        .company-info h4 {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
        }

        .company-info p {
            margin: 2px 0;
            color: #4b5563;
            font-size: 12px;
        }

        .voucher-badge {
            display: inline-block;
            background: #16a34a;
            color: #fff;
            padding: 6px 14px;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 700;
        }

        .info-box {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 14px;
            height: 100%;
        }

        .info-box h6 {
            font-weight: 700;
            margin-bottom: 10px;
            color: #111827;
        }

        .amount-box {
            margin-top: 22px;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            background: #f0fdf4;
            padding: 16px;
            text-align: right;
        }

        .amount-label {
            color: #166534;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 12px;
        }

        .amount-value {
            font-size: 28px;
            font-weight: 800;
            color: #15803d;
        }

        .products-table th {
            background: #f8fafc;
            color: #475569;
            font-size: 12px;
        }

        .no-print {
            margin-top: 20px;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .voucher {
                box-shadow: none;
                max-width: 100%;
            }

            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="voucher">
        <div class="voucher-header">
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
                    <div class="voucher-badge">REFUND VOUCHER</div>
                    <p class="mt-2 mb-1"><strong>Voucher:</strong> {{ $refund->refund_code }}</p>
                    <p class="mb-0"><strong>Date:</strong> {{ $refund->refund_date }}</p>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <div class="info-box">
                    <h6>Customer</h6>
                    <p class="mb-1"><strong>Name:</strong> {{ optional($refund->customer)->name ?? 'N/A' }}</p>
                    <p class="mb-1"><strong>Phone:</strong> {{ optional($refund->customer)->phone ?? 'N/A' }}</p>
                    <p class="mb-0"><strong>Address:</strong> {{ optional($refund->customer)->address ?? 'N/A' }}</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="info-box">
                    <h6>Reference</h6>
                    <p class="mb-1"><strong>Return Code:</strong> {{ optional($refund->return)->return_code ?? 'N/A' }}</p>
                    <p class="mb-1"><strong>Order Code:</strong> {{ optional($refund->order)->order_code ?? 'N/A' }}</p>
                    <p class="mb-1"><strong>Payment Type:</strong> {{ $refund->payment_type_snapshot ?? optional($refund->paymentType)->payment_type ?? 'N/A' }}</p>
                    <p class="mb-0"><strong>Account:</strong> {{ optional($refund->account)->account_name ?? 'N/A' }}</p>
                </div>
            </div>
        </div>

        <div class="amount-box">
            <div class="amount-label">Refund Paid</div>
            <div class="amount-value">৳{{ number_format($refund->refund_amount, 2) }}</div>
        </div>

        @if($refund->return && $refund->return->return_products && $refund->return->return_products->count())
            <div class="mt-4">
                <h6><strong>Returned Products</strong></h6>
                <table class="table table-bordered table-sm products-table">
                    <thead>
                        <tr>
                            <th>SL</th>
                            <th>Product</th>
                            <th class="text-end">Qty</th>
                            <th class="text-end">Return Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($refund->return->return_products as $index => $product)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $product->product_name }}</td>
                                <td class="text-end">{{ number_format($product->qty) }}</td>
                                <td class="text-end">৳{{ number_format($product->total_price, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if($refund->note)
            <div class="mt-4">
                <strong>Note:</strong>
                <p>{{ $refund->note }}</p>
            </div>
        @endif

        <div class="mt-5 pt-3 border-top text-center">
            <p class="text-muted small mb-1">This is a computer-generated refund voucher.</p>
            <p class="text-muted small mb-0">Status: <strong class="text-success">{{ ucfirst($refund->refund_status) }}</strong></p>
        </div>

        <div class="no-print text-center">
            @if($refund->status === 'active')
                <form action="{{ route('ReverseProductOrderRefund', $refund->slug) }}" method="POST" class="d-inline" onsubmit="return confirm('Reverse this refund voucher?');">
                    @csrf
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-undo"></i> Reverse Refund
                    </button>
                </form>
            @endif
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Print
            </button>
            <a href="{{ route('PrintProductOrderRefund', $refund->slug) }}" target="_blank" class="btn btn-success">
                <i class="fas fa-file-invoice"></i> Print View
            </a>
            @if($refund->return)
                <a href="{{ route('ShowProductOrderReturn', $refund->return->slug) }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Return Invoice
                </a>
            @endif
        </div>
    </div>

    @if($printMode)
        <script>
            window.addEventListener('load', function () {
                window.print();
            });
        </script>
    @endif
</body>
</html>
