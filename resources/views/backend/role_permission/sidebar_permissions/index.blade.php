@extends('backend.master')

@section('header_css')
    <link href="{{ url('dataTable') }}/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="{{ url('dataTable') }}/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <style>
        .role-permission-card { border: 1px solid #e5f3f3; border-radius: 10px; }
        .role-permission-card .card-title { color: #0f766e; font-weight: 700; }
        .dataTables_wrapper .dataTables_paginate .paginate_button { padding: 0; }
        table.dataTable tbody td { vertical-align: middle; }
    </style>
@endsection

@section('page_title')
    Sidebar Role Permissions
@endsection

@section('page_heading')
    Sidebar Role Permissions
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card role-permission-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h4 class="card-title mb-1">Role Based Sidebar Permission</h4>
                            <small class="text-muted">Manage role-wise sidebar access with create, read, update and delete permissions.</small>
                        </div>
                        <a href="{{ url('/view/user/roles') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="feather-users"></i> User Roles
                        </a>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped mb-0 data-table">
                            <thead>
                                <tr>
                                    <th width="70" class="text-center">SL</th>
                                    <th>Role Name</th>
                                    <th>Description</th>
                                    <th width="150" class="text-center">Status</th>
                                    <th width="170" class="text-center">Created At</th>
                                    <th width="220" class="text-center">Action</th>
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
        $(function () {
            $('.data-table').DataTable({
                processing: true,
                serverSide: true,
                pageLength: 25,
                ajax: "{{ url('/role-sidebar-permissions') }}",
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center'},
                    {data: 'name', name: 'name'},
                    {data: 'description', name: 'description', defaultContent: ''},
                    {data: 'permission_status', name: 'permission_status', orderable: false, searchable: false, className: 'text-center'},
                    {data: 'created_at', name: 'created_at', className: 'text-center'},
                    {data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center'},
                ]
            });
        });
    </script>
@endsection
