@extends('backend.master')

@section('header_css')
    <link href="{{ url('dataTable') }}/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="{{ url('dataTable') }}/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <style>
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0px;
            border-radius: 4px;
        }
        table.dataTable tbody td {
            text-align: center !important;
        }
        .at-a-glance {
            margin-bottom: 30px;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .stat-card.purchase {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .stat-card.return {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        .stat-card.paid {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }
        .stat-card.old-due {
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
        }
        .stat-card.advance {
            background: linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%);
        }
        .stat-card.payable {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }
        .stat-card.net-payable {
            background: linear-gradient(135deg, #30cfd0 0%, #330867 100%);
        }
        .stat-card h5 {
            color: white;
            font-size: 14px;
            margin-bottom: 10px;
            opacity: 0.9;
        }
        .stat-card h3 {
            color: white;
            font-size: 28px;
            font-weight: bold;
            margin: 0;
        }
    </style>
@endsection

@section('page_title')
    Supplier Payments
@endsection

@section('page_heading')
    Supplier Payments Management
@endsection

@section('content')
    <!-- At a Glance Section -->
    <div class="row at-a-glance">
        <div class="col-md-4 col-lg-2">
            <div class="stat-card purchase">
                <h5>Total Purchase Amount</h5>
                <h3>৳{{ number_format($summary['total_purchase'], 2) }}</h3>
            </div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="stat-card return">
                <h5>Total Purchase Return Amount</h5>
                <h3>৳{{ number_format($summary['total_return'], 2) }}</h3>
            </div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="stat-card paid">
                <h5>Total Paid</h5>
                <h3>৳{{ number_format($summary['total_paid'], 2) }}</h3>
            </div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="stat-card old-due">
                <h5>Old Due</h5>
                <h3>৳{{ number_format($summary['total_old_due'], 2) }}</h3>
            </div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="stat-card advance">
                <h5>Available Advance</h5>
                <h3>৳{{ number_format($summary['total_advance'], 2) }}</h3>
            </div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="stat-card payable">
                <h5>Total Payable</h5>
                <h3>৳{{ number_format($summary['total_payable'], 2) }}</h3>
            </div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="stat-card net-payable">
                <h5>Net Payable</h5>
                <h3>৳{{ number_format($summary['total_net_payable'], 2) }}</h3>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12 col-xl-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-3">All Suppliers</h4>
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0 data-table">
                            <thead>
                                <tr>
                                    <th class="text-center">SL</th>
                                    <th class="text-center">Supplier Name</th>
                                    <th class="text-center">Total Purchase</th>
                                    <th class="text-center">Return Amount</th>
                                    <th class="text-center">Paid</th>
                                    <th class="text-center">Due</th>
                                    <th class="text-center">Advance</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('footer_js')
    <script src="{{ url('dataTable') }}/js/jquery.dataTables.min.js"></script>
    <script src="{{ url('dataTable') }}/js/dataTables.bootstrap4.min.js"></script>

    <script type="text/javascript">
        $(document).ready(function() {
            var table = $('.data-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('ViewAllSupplierPayments') }}",
                columns: [
                    {data: 'id', name: 'id', orderable: false, searchable: false},
                    {data: 'name', name: 'name'},
                    {data: 'total_purchase', name: 'total_purchase', orderable: false, searchable: false},
                    {data: 'return_amount', name: 'return_amount', orderable: false, searchable: false},
                    {data: 'paid', name: 'paid', orderable: false, searchable: false},
                    {data: 'due', name: 'due', orderable: false, searchable: false},
                    {data: 'advance', name: 'advance', orderable: false, searchable: false},
                    {data: 'action', name: 'action', orderable: false, searchable: false},
                ],
                order: [[1, 'asc']]
            });
        });
    </script>
@endsection
