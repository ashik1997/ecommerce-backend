@extends('backend.master')

@section('header_css')
    <link href="{{ url('dataTable') }}/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="{{ url('dataTable') }}/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <style>
        table.dataTable tbody td { text-align: center !important; vertical-align: middle; }
        .dataTables_wrapper .dataTables_paginate .paginate_button { padding: 0; border-radius: 4px; }
    </style>
@endsection

@section('page_title')
    Fund Transfer
@endsection
@section('page_heading')
    Fund Transfer
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title mb-1">All Fund Transfers</h4>
                        <div>
                            <a href="{{ route('ViewAllFundTransferType') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-tags"></i> Transfer Types</a>
                            <a href="{{ route('CreateFundTransfer') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Create Transfer</a>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0 data-table">
                            <thead>
                                <tr>
                                    <th>SL</th>
                                    <th>Date</th>
                                    <th>Transfer No</th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Closing Balance</th>
                                    <th>Creator</th>
                                    <th>Action</th>
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
    <script>
        $(".data-table").DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('ViewAllFundTransfer') }}",
            order: [[0, 'desc']],
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'date', name: 'transfer_date'},
                {data: 'transfer_code', name: 'transfer_code'},
                {data: 'from_account', name: 'fromPaymentType.payment_type'},
                {data: 'to_account', name: 'toPaymentType.payment_type'},
                {data: 'transfer_type', name: 'transferType.name'},
                {data: 'amount', name: 'amount', searchable: false},
                {data: 'balances', name: 'balances', orderable: false, searchable: false},
                {data: 'creator_name', name: 'creator_info.name'},
                {data: 'action', name: 'action', orderable: false, searchable: false}
            ]
        });
    </script>
@endsection
