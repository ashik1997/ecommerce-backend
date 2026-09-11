@extends('backend.master')

@section('page_title')
    Supplier Advance Refund Invoice
@endsection

@section('page_heading')
    Supplier Advance Refund Invoice
@endsection

@section('content')
    <div class="container" style="max-width: 850px;">
        <div class="card">
            <div class="card-body" id="invoiceArea">
                <div class="d-flex justify-content-between align-items-start mb-4">
                    <div>
                        <h3 class="mb-1">Supplier Advance Refund</h3>
                        <p class="text-muted mb-0">Invoice #SAR-{{ $payments->pluck('id')->implode('-') }}</p>
                    </div>
                    <div class="text-right">
                        <p class="mb-1"><strong>Date:</strong> {{ date('Y-m-d', strtotime($refundDate)) }}</p>
                        <p class="mb-0"><strong>Status:</strong> <span class="badge badge-success">Posted</span></p>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5>Supplier</h5>
                        <p class="mb-1"><strong>{{ $supplier->name ?? 'N/A' }}</strong></p>
                        <p class="mb-0">{{ $supplier->contact_number ?? '' }}</p>
                    </div>
                    <div class="col-md-6 text-md-right">
                        <h5>Received Into</h5>
                        <p class="mb-1"><strong>{{ $receiveAccount->account_name ?? 'N/A' }}</strong></p>
                        <p class="mb-0">{{ $receiveAccount->account_code ?? '' }}</p>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>SL</th>
                                <th>Reference</th>
                                <th>Note</th>
                                <th class="text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payments as $payment)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>Refund #{{ $payment->id }}</td>
                                    <td>{{ $payment->payment_note ?: 'Supplier advance refund received' }}</td>
                                    <td class="text-right">৳{{ number_format(abs($payment->payment), 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-right">Total Received</th>
                                <th class="text-right">৳{{ number_format($totalRefund, 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="alert alert-info mb-0">
                    This refund reduces supplier available advance and posts accounting to the selected receive account.
                </div>
            </div>
        </div>

        <div class="text-right mt-3 no-print">
            <a href="{{ route('ViewSupplierPayments', $supplier->id ?? 'all') }}" class="btn btn-secondary">
                <i class="fas fa-list"></i> Transactions
            </a>
            <button type="button" class="btn btn-primary" onclick="window.print()">
                <i class="fas fa-print"></i> Print Invoice
            </button>
        </div>
    </div>
@endsection

@section('footer_js')
    <style media="print">
        .no-print, .navbar, .left-side-menu, .page-title-box, footer {
            display: none !important;
        }
        .content-page, .content, .container {
            margin: 0 !important;
            padding: 0 !important;
            max-width: 100% !important;
        }
        .card {
            border: none !important;
            box-shadow: none !important;
        }
    </style>
@endsection
