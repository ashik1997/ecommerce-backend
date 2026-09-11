@extends('backend.master')

@section('header_css')
    <link href="{{ url('dataTable') }}/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="{{ url('dataTable') }}/css/dataTables.bootstrap4.min.css" rel="stylesheet">
@endsection

@section('page_title')
    Product Order Refunds
@endsection

@section('page_heading')
    All Product Order Refunds
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-3">All Refunds</h4>
                    <div class="mb-3">
                        <a href="{{ route('ReturnRefundDashboard') }}" class="btn btn-success btn-sm">
                            <i class="fas fa-chart-line"></i> Dashboard
                        </a>
                        <a href="{{ route('ViewAllProductOrderReturns') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-list"></i> Order Returns
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0 data-table">
                            <thead>
                                <tr>
                                    <th>SL</th>
                                    <th>Refund Code</th>
                                    <th>Return Code</th>
                                    <th>Order Code</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Payment Type</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
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
    <script>
        $(function () {
            $('.data-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('ViewAllProductOrderRefunds') }}",
                columns: [
                    { data: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'refund_code', name: 'refund_code' },
                    { data: 'return_code', name: 'return.return_code' },
                    { data: 'order_code', name: 'order.order_code' },
                    { data: 'customer_name', name: 'customer.name' },
                    { data: 'refund_date', name: 'refund_date' },
                    { data: 'payment_type', name: 'payment_type_snapshot' },
                    {
                        data: 'refund_amount',
                        name: 'refund_amount',
                        render: function (data) {
                            return '৳' + parseFloat(data || 0).toFixed(2);
                        }
                    },
                    { data: 'refund_status', name: 'refund_status' },
                    { data: 'action', orderable: false, searchable: false },
                ]
            });
        });
    </script>
@endsection
