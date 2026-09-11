@extends('backend.master')

@section('header_css')
    <link href="{{ url('dataTable') }}/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="{{ url('dataTable') }}/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link href="{{ url('assets') }}/plugins/select2/select2.min.css" rel="stylesheet" type="text/css" />
    <style>
        .crm-health-card { border:1px solid #d7eeee; border-radius:14px; box-shadow:0 8px 20px rgba(15,118,110,.05); }
        .crm-health-card .card-title { color:#0f766e; font-weight:800; }
        .crm-health-filter { background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:14px; }
        .select2-container { width:100% !important; }
        .dataTables_wrapper .dataTables_paginate .paginate_button { padding:0; }
        table.dataTable tbody td { vertical-align:middle; }
    </style>
@endsection

@section('page_title') CRM Customer Health @endsection
@section('page_heading') CRM Customer Health @endsection

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card crm-health-card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                        <div>
                            <h4 class="card-title mb-1">CRM Customer Health & Risk Worklist</h4>
                            <small class="text-muted">Read-only view of customer CRM summary fields, due exposure, follow-up timing, lifecycle, and credit risk.</small>
                        </div>
                        <span class="badge badge-light border mt-2 mt-md-0"><i class="feather-eye"></i> Read-only risk view</span>
                    </div>

                    <div class="alert alert-info border">
                        This worklist reads existing <code>customers</code> CRM summary fields only. It does not create, edit, archive, delete, recalculate dues, or touch accounting records.
                    </div>

                    <div class="crm-health-filter mb-3">
                        <div class="row">
                            <div class="col-lg-3 col-md-6 mb-2">
                                <label for="crmHealthFilterCustomer">Customer</label>
                                <select id="crmHealthFilterCustomer" class="form-control"></select>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-2">
                                <label for="crmHealthFilterAssignedUser">Assigned user</label>
                                <select id="crmHealthFilterAssignedUser" class="form-control"></select>
                            </div>
                            <div class="col-lg-2 col-md-6 mb-2">
                                <label for="crmHealthFilterLifecycle">Lifecycle</label>
                                <select id="crmHealthFilterLifecycle" class="form-control">
                                    <option value="">All stages</option>
                                    @foreach ($filterOptions['lifecycle_stages'] as $stage)
                                        <option value="{{ $stage }}">{{ ucwords(str_replace('_', ' ', $stage)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-6 mb-2">
                                <label for="crmHealthFilterCredit">Credit status</label>
                                <select id="crmHealthFilterCredit" class="form-control">
                                    <option value="">All statuses</option>
                                    @foreach ($filterOptions['credit_statuses'] as $status)
                                        <option value="{{ $status }}">{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-6 mb-2">
                                <label for="crmHealthFilterRisk">Risk bucket</label>
                                <select id="crmHealthFilterRisk" class="form-control">
                                    <option value="">All risk buckets</option>
                                    <option value="overdue">Overdue amount</option>
                                    <option value="due">Current due</option>
                                    <option value="follow_up_due">Follow-up due</option>
                                    <option value="duplicate">Duplicate candidate</option>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-6 mb-2"><label for="crmHealthFollowFrom">Follow-up from</label><input type="date" id="crmHealthFollowFrom" class="form-control"></div>
                            <div class="col-lg-2 col-md-6 mb-2"><label for="crmHealthFollowTo">Follow-up to</label><input type="date" id="crmHealthFollowTo" class="form-control"></div>
                            <div class="col-lg-2 col-md-6 mb-2"><label for="crmHealthContactFrom">Last contact from</label><input type="date" id="crmHealthContactFrom" class="form-control"></div>
                            <div class="col-lg-2 col-md-6 mb-2"><label for="crmHealthContactTo">Last contact to</label><input type="date" id="crmHealthContactTo" class="form-control"></div>
                            <div class="col-lg-2 col-md-4 mb-2 d-flex align-items-end"><button type="button" id="resetCrmHealthFilters" class="btn btn-outline-secondary btn-block">Reset filters</button></div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped mb-0" id="crmCustomerHealthTable">
                            <thead><tr>
                                <th>Customer</th><th>Code</th><th>Lifecycle</th><th>Assigned user</th><th>Credit</th><th>Risk</th>
                                <th>Current due</th><th>Overdue</th><th>Total order</th><th>Total paid</th><th>Last contact</th><th>Next follow-up</th><th>Last order</th><th>Action</th>
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
        $crmHealthUrls = [
            'data' => route('crm.customer-health.data'),
            'customers' => route('crm.customer-health.options.customers'),
            'users' => route('crm.customer-health.options.users'),
        ];
    @endphp
    <script>
        (function ($) {
            'use strict';
            const urls = @json($crmHealthUrls);
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

            initSelect('#crmHealthFilterCustomer', urls.customers, 'All customers');
            initSelect('#crmHealthFilterAssignedUser', urls.users, 'All assigned users');
            if (prefillCustomer && prefillCustomer.id) setSelected('#crmHealthFilterCustomer', prefillCustomer);

            const table = $('#crmCustomerHealthTable').DataTable({
                processing:true, serverSide:true, searching:true, ordering:false, pageLength:25,
                ajax:function (data, callback) {
                    const http = client();
                    if (!http) { if (!tableErrorVisible) { tableErrorVisible = true; notify('Axios unavailable', 'The shared window.AppAxios client is required.', 'error'); } callback(emptyTable(data.draw)); return; }
                    data.customer_id = $('#crmHealthFilterCustomer').val();
                    data.assigned_user_id = $('#crmHealthFilterAssignedUser').val();
                    data.lifecycle_stage = $('#crmHealthFilterLifecycle').val();
                    data.credit_status = $('#crmHealthFilterCredit').val();
                    data.risk_bucket = $('#crmHealthFilterRisk').val();
                    data.follow_up_from = $('#crmHealthFollowFrom').val();
                    data.follow_up_to = $('#crmHealthFollowTo').val();
                    data.last_contact_from = $('#crmHealthContactFrom').val();
                    data.last_contact_to = $('#crmHealthContactTo').val();
                    http.get(urls.data, {params:data}).then(function (response) { tableErrorVisible = false; callback(response.data); }).catch(function (error) {
                        const payload = (error.response && error.response.data) || {}; callback(emptyTable(data.draw));
                        if (!tableErrorVisible) { tableErrorVisible = true; notify('Customer health load failed', payload.message || 'Unable to load CRM customer health worklist.', 'error'); }
                    });
                },
                columns:[
                    {data:'health.name', render:function (value, type, row) { return row.health.profile_url ? '<a href="' + attr(row.health.profile_url) + '">' + esc(value) + '</a><br><small class="text-muted">' + esc(row.health.phone || row.health.email) + '</small>' : esc(value); }},
                    {data:'health.customer_code', defaultContent:''},
                    {data:'health.lifecycle_stage', render:function (value) { return badge(String(value || '').replace(/_/g, ' ')); }},
                    {data:'health.assigned_user', defaultContent:''},
                    {data:'health.credit_status', render:function (value) { return badge(String(value || '').replace(/_/g, ' ')); }},
                    {data:'health.risk_label', render:function (value) { return badge(value); }},
                    {data:'health.current_due'}, {data:'health.overdue_amount'}, {data:'health.total_order_value'}, {data:'health.total_paid'},
                    {data:'health.last_contact_at'}, {data:'health.next_follow_up_at'}, {data:'health.last_order_at'},
                    {data:'health.profile_url', render:function (value) { return value ? '<a class="btn btn-xs btn-outline-primary" href="' + attr(value) + '"><i class="feather-eye"></i></a>' : '<span class="text-muted">—</span>'; }}
                ]
            });
            $('#crmHealthFilterCustomer,#crmHealthFilterAssignedUser,#crmHealthFilterLifecycle,#crmHealthFilterCredit,#crmHealthFilterRisk,#crmHealthFollowFrom,#crmHealthFollowTo,#crmHealthContactFrom,#crmHealthContactTo').on('change', function () { table.ajax.reload(); });
            $('#resetCrmHealthFilters').on('click', function () {
                setSelected('#crmHealthFilterCustomer'); setSelected('#crmHealthFilterAssignedUser');
                $('#crmHealthFilterLifecycle,#crmHealthFilterCredit,#crmHealthFilterRisk,#crmHealthFollowFrom,#crmHealthFollowTo,#crmHealthContactFrom,#crmHealthContactTo').val('');
                table.ajax.reload();
            });
        })(jQuery);
    </script>
@endsection
