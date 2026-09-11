@extends('backend.master')

@section('header_css')
    <link href="{{ url('dataTable') }}/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="{{ url('dataTable') }}/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link href="{{ url('assets') }}/plugins/select2/select2.min.css" rel="stylesheet" type="text/css" />
    <style>
        .crm-activity-card { border:1px solid #d7eeee; border-radius:14px; box-shadow:0 8px 20px rgba(15,118,110,.05); }
        .crm-activity-card .card-title { color:#0f766e; font-weight:800; }
        .crm-activity-filter { background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:14px; }
        .crm-activity-summary { max-width:320px; white-space:normal; }
        .crm-activity-detail-label { color:#64748b; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.03em; }
        .crm-activity-detail-value { white-space:pre-wrap; word-break:break-word; }
        .select2-container { width:100% !important; }
        .dataTables_wrapper .dataTables_paginate .paginate_button { padding:0; }
        table.dataTable tbody td { vertical-align:middle; }
    </style>
@endsection

@section('page_title') CRM Activity History @endsection
@section('page_heading') CRM Activity History @endsection

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card crm-activity-card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                        <div>
                            <h4 class="card-title mb-1">CRM Activity Audit History</h4>
                            <small class="text-muted">Review immutable CRM audit events recorded by the existing customer, tag, task, communication, and lead flows.</small>
                        </div>
                        <span class="badge badge-light border mt-2 mt-md-0"><i class="feather-eye"></i> Read-only audit view</span>
                    </div>

                    <div class="alert alert-info border">
                        This worklist reads existing <code>crm_activities</code> records only. It does not create, edit, archive, delete, or backfill CRM activity records.
                    </div>

                    <div class="crm-activity-filter mb-3">
                        <div class="row">
                            <div class="col-lg-3 col-md-6 mb-2">
                                <label for="crmActivityFilterCustomer">Customer</label>
                                <select id="crmActivityFilterCustomer" class="form-control"></select>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-2">
                                <label for="crmActivityFilterPerformer">Performed by</label>
                                <select id="crmActivityFilterPerformer" class="form-control"></select>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-2">
                                <label for="crmActivityFilterType">Activity type</label>
                                <select id="crmActivityFilterType" class="form-control">
                                    <option value="">All activity types</option>
                                    @foreach ($filterOptions['activity_types'] as $activityType)
                                        <option value="{{ $activityType }}">{{ ucwords(str_replace('_', ' ', $activityType)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-2">
                                <label for="crmActivityFilterSource">Source module</label>
                                <select id="crmActivityFilterSource" class="form-control">
                                    <option value="">All source modules</option>
                                    @foreach ($filterOptions['source_modules'] as $sourceModule)
                                        <option value="{{ $sourceModule }}">{{ $sourceModule }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-2">
                                <label for="crmActivityOccurredFrom">Occurred from</label>
                                <input type="date" id="crmActivityOccurredFrom" class="form-control">
                            </div>
                            <div class="col-lg-3 col-md-6 mb-2">
                                <label for="crmActivityOccurredTo">Occurred to</label>
                                <input type="date" id="crmActivityOccurredTo" class="form-control">
                            </div>
                            <div class="col-lg-2 col-md-4 mb-2 d-flex align-items-end">
                                <button type="button" id="resetCrmActivityFilters" class="btn btn-outline-secondary btn-block">Reset filters</button>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped mb-0" id="crmActivitiesTable">
                            <thead><tr>
                                <th>Customer</th><th>Activity type</th><th>Subject</th><th>Description summary</th>
                                <th>Source module</th><th>Source ID</th><th>Performed by</th><th>Occurred at</th>
                                <th>Created at</th><th>Actions</th>
                            </tr></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="crmActivityDetailsModal" tabindex="-1" role="dialog" aria-labelledby="crmActivityDetailsModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="crmActivityDetailsModalTitle">CRM Activity Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body" id="crmActivityDetailsBody">
                    <div class="text-center text-muted py-4">Select an activity to view details.</div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Close</button></div>
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
        $crmActivityUrls = [
            'data' => route('crm.activities.data'),
            'show' => route('crm.activities.show', ['activity' => '__ACTIVITY__']),
            'customers' => route('crm.activities.options.customers'),
            'users' => route('crm.activities.options.users'),
        ];
    @endphp
    <script>
        (function ($) {
            'use strict';
            const urls = @json($crmActivityUrls);
            const prefillCustomer = @json($prefillCustomer);
            let detailsLoading = false;
            let tableErrorVisible = false;

            function client() { return window.AppAxios || null; }
            function endpoint(template, id) { return String(template || '').replace('__ACTIVITY__', String(id)); }
            function esc(value) { return $('<div>').text(value === null || value === undefined || value === '' ? '—' : String(value)).html(); }
            function attr(value) { return esc(value).replace(/`/g, '&#96;'); }
            function notify(title, text, icon) {
                if (typeof Swal !== 'undefined') return Swal.fire({title:title, text:text || '', icon:icon || 'info', confirmButtonColor:'#0f766e'});
                window.alert((title ? title + ': ' : '') + (text || ''));
            }
            function badge(value) { return '<span class="badge badge-light border">' + esc(value) + '</span>'; }
            function detailsAction(row) { return row.details_url ? '<button type="button" class="btn btn-xs btn-outline-primary js-view-activity" data-id="' + Number(row.id) + '"><i class="feather-eye"></i></button>' : '<span class="text-muted">—</span>'; }
            function emptyTable(draw) { return {draw:Number(draw || 0), recordsTotal:0, recordsFiltered:0, data:[]}; }

            function transport(url) {
                return {
                    delay:250,
                    transport:function (params, success, failure) {
                        const http = client();
                        if (!http) { failure(); return {abort:function(){}}; }
                        const request = http.get(url, {params:params.data || {}});
                        request.then(function (response) { success(response.data); }).catch(failure);
                        return {abort:function(){}};
                    },
                    data:function (params) { return {q:params.term || ''}; },
                    processResults:function (data) { return data; }
                };
            }
            function initSelect(selector, url, placeholder) {
                const select = $(selector);
                if (select.hasClass('select2-hidden-accessible')) select.select2('destroy');
                select.select2({width:'100%', placeholder:placeholder, allowClear:true, ajax:transport(url)});
            }
            function setSelected(selector, id, text) {
                const select = $(selector); select.empty();
                if (id) select.append(new Option(text || ('#' + id), String(id), true, true)).trigger('change');
                else select.val(null).trigger('change');
            }

            initSelect('#crmActivityFilterCustomer', urls.customers, 'All customers');
            initSelect('#crmActivityFilterPerformer', urls.users, 'All users');
            if (prefillCustomer) setSelected('#crmActivityFilterCustomer', prefillCustomer.id, prefillCustomer.text);

            const table = $('#crmActivitiesTable').DataTable({
                processing:true, serverSide:true, pageLength:25, order:[],
                ajax:function (data, callback) {
                    const http = client();
                    if (!http) {
                        callback(emptyTable(data.draw));
                        if (!tableErrorVisible) { tableErrorVisible = true; notify('Axios unavailable', 'The shared window.AppAxios client is required.', 'error'); }
                        return;
                    }
                    data.customer_id = $('#crmActivityFilterCustomer').val();
                    data.performed_by = $('#crmActivityFilterPerformer').val();
                    data.activity_type = $('#crmActivityFilterType').val();
                    data.source_module = $('#crmActivityFilterSource').val();
                    data.occurred_from = $('#crmActivityOccurredFrom').val();
                    data.occurred_to = $('#crmActivityOccurredTo').val();
                    http.get(urls.data, {params:data}).then(function (response) {
                        tableErrorVisible = false;
                        callback(response.data);
                    }).catch(function (error) {
                        callback(emptyTable(data.draw));
                        const response = error.response || {}, payload = response.data || {};
                        if (!tableErrorVisible) {
                            tableErrorVisible = true;
                            notify('Activity history load failed', payload.message || 'Unable to load CRM activity history.', 'error');
                        }
                    });
                },
                columns:[
                    {data:'activity.customer', name:'customer_id', orderable:false, searchable:false, render:function (value, type, row) { return row.activity.customer_profile_url ? '<a href="' + attr(row.activity.customer_profile_url) + '">' + esc(value) + '</a>' : esc(value); }},
                    {data:'activity.activity_type', name:'activity_type', render:function (value) { return badge(String(value || '').replace(/_/g, ' ')); }},
                    {data:'activity.subject', name:'subject', defaultContent:''},
                    {data:'activity.description_summary', name:'description', orderable:false, render:function (value) { return '<div class="crm-activity-summary">' + esc(value) + '</div>'; }},
                    {data:'activity.source_module', name:'source_module', defaultContent:''},
                    {data:'activity.source_id', name:'source_id', defaultContent:''},
                    {data:'activity.actor', name:'performed_by', orderable:false, searchable:false, defaultContent:''},
                    {data:'activity.occurred_at', name:'occurred_at', defaultContent:''},
                    {data:'activity.created_at', name:'created_at', defaultContent:''},
                    {data:'activity.id', name:'id', orderable:false, searchable:false, render:function (value, type, row) { return detailsAction(row.activity); }}
                ]
            });

            $('#crmActivityFilterCustomer,#crmActivityFilterPerformer,#crmActivityFilterType,#crmActivityFilterSource,#crmActivityOccurredFrom,#crmActivityOccurredTo').on('change', function () { table.ajax.reload(); });
            $('#resetCrmActivityFilters').on('click', function () {
                setSelected('#crmActivityFilterCustomer');
                setSelected('#crmActivityFilterPerformer');
                $('#crmActivityFilterType,#crmActivityFilterSource,#crmActivityOccurredFrom,#crmActivityOccurredTo').val('');
                table.ajax.reload();
            });

            function detailRow(label, value) { return '<div class="mb-3"><div class="crm-activity-detail-label">' + esc(label) + '</div><div class="crm-activity-detail-value">' + esc(value) + '</div></div>'; }
            function renderDetails(record) {
                return '<div class="row"><div class="col-md-6">' + detailRow('Customer', record.customer) + detailRow('Activity type', String(record.activity_type || '').replace(/_/g, ' ')) + detailRow('Source module', record.source_module) + detailRow('Source ID', record.source_id) + '</div>' +
                    '<div class="col-md-6">' + detailRow('Performed by', record.actor) + detailRow('Occurred at', record.occurred_at) + detailRow('Created at', record.created_at) + '</div></div>' +
                    detailRow('Subject', record.subject) + detailRow('Description', record.description);
            }
            $('#crmActivitiesTable').on('click', '.js-view-activity', function () {
                if (detailsLoading) return;
                const id = String($(this).data('id')), http = client();
                if (!http) { notify('Axios unavailable', 'The shared window.AppAxios client is required.', 'error'); return; }
                detailsLoading = true;
                $('#crmActivityDetailsBody').html('<div class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm mr-2"></span>Loading...</div>');
                $('#crmActivityDetailsModal').modal('show');
                http.get(endpoint(urls.show, id)).then(function (response) {
                    $('#crmActivityDetailsBody').html(renderDetails(response.data.activity || {}));
                }).catch(function (error) {
                    const payload = (error.response && error.response.data) || {};
                    $('#crmActivityDetailsBody').html('<div class="alert alert-danger mb-0">' + esc(payload.message || 'Unable to load CRM activity details.') + '</div>');
                }).finally(function () { detailsLoading = false; });
            });
        })(jQuery);
    </script>
@endsection
