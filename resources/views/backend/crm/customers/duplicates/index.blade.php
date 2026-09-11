@extends('backend.master')

@section('title')
    CRM Duplicate Customer Review
@endsection

@section('header_css')
    <link rel="stylesheet" href="{{ url('dataTable') }}/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="{{ url('assets') }}/plugins/select2/select2.min.css">
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title mb-1">CRM Duplicate Customer Review</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ url('/crm-home') }}">CRM</a></li>
                            <li class="breadcrumb-item active">Duplicate Customers</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="alert alert-info">
                This is a read-only review list. It does not merge, edit, delete, or recalculate customer records.
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="mb-3">
                        <div class="row">
                            <div class="col-lg-3 col-md-6 mb-2"><label for="crmDuplicateFilterCustomer">Customer</label><select id="crmDuplicateFilterCustomer" class="form-control"></select></div>
                            <div class="col-lg-3 col-md-6 mb-2"><label for="crmDuplicateFilterAssignedUser">Assigned user</label><select id="crmDuplicateFilterAssignedUser" class="form-control"></select></div>
                            <div class="col-lg-2 col-md-6 mb-2">
                                <label for="crmDuplicateFilterLifecycle">Lifecycle</label>
                                <select id="crmDuplicateFilterLifecycle" class="form-control">
                                    <option value="">All stages</option>
                                    @foreach ($filterOptions['lifecycle_stages'] as $stage)
                                        <option value="{{ $stage }}">{{ ucwords(str_replace('_', ' ', $stage)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-6 mb-2">
                                <label for="crmDuplicateFilterMatch">Match type</label>
                                <select id="crmDuplicateFilterMatch" class="form-control">
                                    <option value="any">Phone or email</option>
                                    <option value="phone">Phone only</option>
                                    <option value="email">Email only</option>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-6 mb-2">
                                <label for="crmDuplicateCandidateOnly">Candidate flag</label>
                                <select id="crmDuplicateCandidateOnly" class="form-control">
                                    <option value="1">Flagged only</option>
                                    <option value="0">Include unflagged matches</option>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-4 mb-2 d-flex align-items-end"><button type="button" id="resetCrmDuplicateFilters" class="btn btn-outline-secondary btn-block">Reset filters</button></div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped mb-0" id="crmDuplicateCustomerTable">
                            <thead><tr>
                                <th>Customer</th><th>Code</th><th>Phone</th><th>Email</th><th>Lifecycle</th><th>Assigned user</th><th>Flagged</th><th>Phone matches</th><th>Email matches</th><th>Action</th>
                            </tr></thead>
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
    <script src="{{ url('assets') }}/plugins/select2/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @php
        $crmDuplicateUrls = [
            'data' => route('crm.duplicate-customers.data'),
            'customers' => route('crm.duplicate-customers.options.customers'),
            'users' => route('crm.duplicate-customers.options.users'),
        ];
    @endphp
    <script>
        (function ($) {
            'use strict';
            const urls = @json($crmDuplicateUrls);
            const prefillCustomer = @json($prefillCustomer);
            let tableErrorVisible = false;

            function client() { return window.AppAxios || null; }
            function esc(value) { return $('<div>').text(value === null || value === undefined || value === '' ? '—' : String(value)).html(); }
            function attr(value) { return esc(value).replace(/`/g, '&#96;'); }
            function notify(title, text, icon) {
                if (typeof Swal !== 'undefined') return Swal.fire({title:title, text:text || '', icon:icon || 'info', confirmButtonColor:'#0f766e'});
                window.alert((title ? title + ': ' : '') + (text || ''));
            }
            function badge(value) { return '<span class="badge badge-light border">' + esc(value) + '</span>'; }
            function yesNo(value) { return value ? '<span class="badge badge-warning">Yes</span>' : '<span class="badge badge-light border">No</span>'; }
            function emptyTable(draw) { return {draw:Number(draw || 0), recordsTotal:0, recordsFiltered:0, data:[]}; }
            function transport(url) {
                return {delay:250, transport:function (params, success, failure) {
                    const http = client(); if (!http) { failure(); return {abort:function(){}}; }
                    const request = http.get(url, {params:params.data || {}}); request.then(function (response) { success(response.data); }).catch(failure); return {abort:function(){}};
                }, data:function (params) { return {q:params.term || ''}; }, processResults:function (data) { return data; }};
            }
            function initSelect(selector, url, placeholder) {
                const select = $(selector); if (select.hasClass('select2-hidden-accessible')) select.select2('destroy');
                select.select2({width:'100%', placeholder:placeholder, allowClear:true, ajax:transport(url)});
            }
            function setSelected(selector, option) {
                const select = $(selector); select.empty();
                if (option && option.id) select.append(new Option(option.text, option.id, true, true));
                select.trigger('change.select2');
            }

            initSelect('#crmDuplicateFilterCustomer', urls.customers, 'All customers');
            initSelect('#crmDuplicateFilterAssignedUser', urls.users, 'All assigned users');
            if (prefillCustomer && prefillCustomer.id) setSelected('#crmDuplicateFilterCustomer', prefillCustomer);

            const table = $('#crmDuplicateCustomerTable').DataTable({
                processing:true, serverSide:true, searching:true, ordering:false, pageLength:25,
                ajax:function (data, callback) {
                    const http = client();
                    if (!http) { if (!tableErrorVisible) { tableErrorVisible = true; notify('Axios unavailable', 'The shared window.AppAxios client is required.', 'error'); } callback(emptyTable(data.draw)); return; }
                    data.customer_id = $('#crmDuplicateFilterCustomer').val();
                    data.assigned_user_id = $('#crmDuplicateFilterAssignedUser').val();
                    data.lifecycle_stage = $('#crmDuplicateFilterLifecycle').val();
                    data.match_type = $('#crmDuplicateFilterMatch').val();
                    data.candidate_only = $('#crmDuplicateCandidateOnly').val();
                    http.get(urls.data, {params:data}).then(function (response) { tableErrorVisible = false; callback(response.data); }).catch(function (error) {
                        const payload = (error.response && error.response.data) || {}; callback(emptyTable(data.draw));
                        if (!tableErrorVisible) { tableErrorVisible = true; notify('Duplicate customer load failed', payload.message || 'Unable to load CRM duplicate customer review list.', 'error'); }
                    });
                },
                columns:[
                    {data:'duplicate.name', render:function (value, type, row) { return row.duplicate.profile_url ? '<a href="' + attr(row.duplicate.profile_url) + '">' + esc(value) + '</a>' : esc(value); }},
                    {data:'duplicate.customer_code', defaultContent:''},
                    {data:'duplicate.phone', defaultContent:''},
                    {data:'duplicate.email', defaultContent:''},
                    {data:'duplicate.lifecycle_stage', render:function (value) { return badge(String(value || '').replace(/_/g, ' ')); }},
                    {data:'duplicate.assigned_user', defaultContent:''},
                    {data:'duplicate.is_duplicate_candidate', render:function (value) { return yesNo(value); }},
                    {data:'duplicate.phone_match_count'},
                    {data:'duplicate.email_match_count'},
                    {data:'duplicate.profile_url', render:function (value) { return value ? '<a class="btn btn-xs btn-outline-primary" href="' + attr(value) + '"><i class="feather-eye"></i></a>' : '<span class="text-muted">—</span>'; }}
                ]
            });
            $('#crmDuplicateFilterCustomer,#crmDuplicateFilterAssignedUser,#crmDuplicateFilterLifecycle,#crmDuplicateFilterMatch,#crmDuplicateCandidateOnly').on('change', function () { table.ajax.reload(); });
            $('#resetCrmDuplicateFilters').on('click', function () {
                setSelected('#crmDuplicateFilterCustomer'); setSelected('#crmDuplicateFilterAssignedUser');
                $('#crmDuplicateFilterLifecycle').val(''); $('#crmDuplicateFilterMatch').val('any'); $('#crmDuplicateCandidateOnly').val('1');
                table.ajax.reload();
            });
        })(jQuery);
    </script>
@endsection
