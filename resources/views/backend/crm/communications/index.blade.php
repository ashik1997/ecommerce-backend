@extends('backend.master')

@section('header_css')
    <link href="{{ url('dataTable') }}/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="{{ url('dataTable') }}/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link href="{{ url('assets') }}/plugins/select2/select2.min.css" rel="stylesheet" type="text/css" />
    <style>
        .crm-communication-card { border:1px solid #d7eeee; border-radius:14px; box-shadow:0 8px 20px rgba(15,118,110,.05); }
        .crm-communication-card .card-title { color:#0f766e; font-weight:800; }
        .crm-communication-filter { background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:14px; }
        .crm-communication-muted { color:#64748b; font-size:12px; }
        .crm-communication-summary { max-width:280px; white-space:normal; }
        .crm-communication-detail-label { color:#64748b; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.03em; }
        .crm-communication-detail-value { white-space:pre-wrap; word-break:break-word; }
        .select2-container { width:100% !important; }
        .dataTables_wrapper .dataTables_paginate .paginate_button { padding:0; }
        table.dataTable tbody td { vertical-align:middle; }
    </style>
@endsection

@section('page_title') CRM Communications @endsection
@section('page_heading') CRM Communications @endsection

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card crm-communication-card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                        <div>
                            <h4 class="card-title mb-1">CRM Communications History</h4>
                            <small class="text-muted">Review audit-safe customer communication history. Manual logging records an interaction only; it never sends a message.</small>
                        </div>
                        @if ($permissions['can_create'])
                            <button type="button" class="btn btn-sm btn-primary" id="openCreateCrmCommunication"><i class="feather-plus-circle"></i> Log Communication</button>
                        @endif
                    </div>

                    <div class="crm-communication-filter mb-3">
                        <div class="row">
                            <div class="col-lg-3 col-md-6 mb-2">
                                <label for="crmCommunicationFilterCustomer">Customer</label>
                                <select id="crmCommunicationFilterCustomer" class="form-control"></select>
                            </div>
                            <div class="col-lg-2 col-md-4 mb-2">
                                <label for="crmCommunicationFilterChannel">Channel</label>
                                <select id="crmCommunicationFilterChannel" class="form-control">
                                    <option value="">All channels</option>
                                    @foreach ($filterOptions['channels'] as $channel)
                                        <option value="{{ $channel }}">{{ ucfirst($channel) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-4 mb-2">
                                <label for="crmCommunicationFilterDirection">Direction</label>
                                <select id="crmCommunicationFilterDirection" class="form-control">
                                    <option value="">All directions</option>
                                    @foreach ($filterOptions['directions'] as $direction)
                                        <option value="{{ $direction }}">{{ ucfirst($direction) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-4 mb-2">
                                <label for="crmCommunicationFilterStatus">Status</label>
                                <select id="crmCommunicationFilterStatus" class="form-control">
                                    <option value="">All statuses</option>
                                    @foreach ($filterOptions['statuses'] as $status)
                                        <option value="{{ $status }}">{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-2">
                                <label for="crmCommunicationFilterSource">Source module</label>
                                <select id="crmCommunicationFilterSource" class="form-control">
                                    <option value="">All source modules</option>
                                    @foreach ($filterOptions['source_modules'] as $sourceModule)
                                        <option value="{{ $sourceModule }}">{{ $sourceModule }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-2">
                                <label for="crmCommunicationSentFrom">Sent or logged from</label>
                                <input type="date" id="crmCommunicationSentFrom" class="form-control">
                            </div>
                            <div class="col-lg-3 col-md-6 mb-2">
                                <label for="crmCommunicationSentTo">Sent or logged to</label>
                                <input type="date" id="crmCommunicationSentTo" class="form-control">
                            </div>
                            <div class="col-lg-2 col-md-4 mb-2 d-flex align-items-end">
                                <button type="button" id="resetCrmCommunicationFilters" class="btn btn-outline-secondary btn-block">Reset filters</button>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped mb-0" id="crmCommunicationsTable">
                            <thead><tr>
                                <th>Customer</th><th>Channel</th><th>Direction</th><th>Status</th><th>Subject</th>
                                <th>Message summary</th><th>Source module</th><th>Provider</th><th>Logged by</th>
                                <th>Sent or logged date</th><th>Created at</th><th>Actions</th>
                            </tr></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="crmCommunicationModal" tabindex="-1" role="dialog" aria-labelledby="crmCommunicationModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <form id="crmCommunicationForm" novalidate>
                    <div class="modal-header">
                        <h5 class="modal-title" id="crmCommunicationModalTitle">Log CRM Communication</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info border">
                            <strong>History-only action:</strong> this form records an interaction. It does not send an SMS, email, WhatsApp message, or provider request.
                        </div>
                        <div class="form-group">
                            <label for="crmCommunicationCustomer">Customer <span class="text-danger">*</span></label>
                            <select id="crmCommunicationCustomer" name="customer_id" class="form-control"></select>
                            <div class="invalid-feedback" id="error-customer_id"></div>
                        </div>
                        <div class="row">
                            <div class="col-md-4"><div class="form-group">
                                <label for="crmCommunicationChannel">Channel <span class="text-danger">*</span></label>
                                <select id="crmCommunicationChannel" name="channel" class="form-control">
                                    @foreach ($manualChannels as $channel)
                                        <option value="{{ $channel }}">{{ ucfirst($channel) }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="error-channel"></div>
                            </div></div>
                            <div class="col-md-4"><div class="form-group">
                                <label for="crmCommunicationDirection">Direction <span class="text-danger">*</span></label>
                                <select id="crmCommunicationDirection" name="direction" class="form-control">
                                    @foreach ($directions as $direction)
                                        <option value="{{ $direction }}">{{ ucfirst($direction) }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="error-direction"></div>
                            </div></div>
                            <div class="col-md-4"><div class="form-group">
                                <label for="crmCommunicationInteractionAt">Interaction time</label>
                                <input type="datetime-local" id="crmCommunicationInteractionAt" name="interaction_at" class="form-control">
                                <div class="invalid-feedback" id="error-interaction_at"></div>
                            </div></div>
                        </div>
                        <div class="form-group">
                            <label for="crmCommunicationSubject">Subject</label>
                            <input type="text" id="crmCommunicationSubject" name="subject" class="form-control" maxlength="255">
                            <div class="invalid-feedback" id="error-subject"></div>
                        </div>
                        <div class="form-group">
                            <label for="crmCommunicationMessage">Message or interaction summary <span class="text-danger">*</span></label>
                            <textarea id="crmCommunicationMessage" name="message" class="form-control" rows="6" maxlength="5000" required></textarea>
                            <div class="invalid-feedback" id="error-message"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="saveCrmCommunication"><span class="js-save-communication-label">Log History</span></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="crmCommunicationDetailsModal" tabindex="-1" role="dialog" aria-labelledby="crmCommunicationDetailsModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="crmCommunicationDetailsModalTitle">Communication Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body" id="crmCommunicationDetailsBody">
                    <div class="text-center text-muted py-4">Select a communication to view details.</div>
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
        $crmCommunicationUrls = [
            'data' => route('crm.communications.data'),
            'store' => route('crm.communications.store'),
            'show' => route('crm.communications.show', ['communication' => '__COMMUNICATION__']),
            'customers' => route('crm.communications.options.customers'),
        ];
    @endphp
    <script>
        (function ($) {
            'use strict';
            const urls = @json($crmCommunicationUrls);
            const permissions = @json($permissions);
            const prefillCustomer = @json($prefillCustomer);
            const shouldOpenCreate = @json($openCreateModal);
            let communicationSubmitting = false;
            let detailsLoading = false;
            let tableErrorVisible = false;
            let filtersMuted = false;

            function client() { return window.AppAxios || null; }
            function emptyTable(draw) { return {draw:Number(draw || 0), recordsTotal:0, recordsFiltered:0, data:[]}; }
            function endpoint(template, id) { return String(template || '').replace('__COMMUNICATION__', String(id)); }
            function esc(value) { return $('<div>').text(value === null || value === undefined || value === '' ? '—' : String(value)).html(); }
            function attr(value) { return esc(value).replace(/`/g, '&#96;'); }
            function notify(title, text, icon) { return Swal.fire({title:title, text:text || '', icon:icon || 'info', confirmButtonColor:'#0f766e'}); }
            function firstError(payload) { const errors = payload.errors || {}; const key = Object.keys(errors)[0]; return key && Array.isArray(errors[key]) ? errors[key][0] : null; }
            function badge(value, css) { return '<span class="badge ' + attr(css || 'badge-light border') + '">' + esc(value) + '</span>'; }
            function statusBadge(value) {
                if (value === 'logged') return badge('Logged', 'badge-info');
                if (value === 'sent' || value === 'delivered') return badge(value, 'badge-success');
                if (value === 'failed') return badge('Failed', 'badge-danger');
                return badge(value || 'Not set', 'badge-light border');
            }
            function directionBadge(value) { return value === 'inbound' ? badge('Inbound', 'badge-primary') : badge('Outbound', 'badge-secondary'); }
            function detailsAction(row) { return row.details_url ? '<button type="button" class="btn btn-xs btn-outline-primary js-view-communication" data-id="' + Number(row.id) + '"><i class="feather-eye"></i></button>' : '<span class="text-muted">—</span>'; }

            function transport(url) {
                return {
                    delay:250,
                    transport:function (params, success, failure) {
                        const http = client(); if (!http) { failure(); return {abort:function(){}}; }
                        http.get(url, {params:params.data || {}}).then(function (response) { success(response.data); }).catch(failure);
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
            initSelect('#crmCommunicationFilterCustomer', urls.customers, 'All customers');
            initSelect('#crmCommunicationCustomer', urls.customers, 'Select customer');

            const table = $('#crmCommunicationsTable').DataTable({
                processing:true, serverSide:true, pageLength:25, order:[],
                ajax:function (data, callback) {
                    const http = client();
                    data.customer_id = $('#crmCommunicationFilterCustomer').val();
                    data.channel = $('#crmCommunicationFilterChannel').val();
                    data.direction = $('#crmCommunicationFilterDirection').val();
                    data.status = $('#crmCommunicationFilterStatus').val();
                    data.source_module = $('#crmCommunicationFilterSource').val();
                    data.sent_from = $('#crmCommunicationSentFrom').val();
                    data.sent_to = $('#crmCommunicationSentTo').val();
                    if (!http) {
                        callback(emptyTable(data.draw));
                        if (!tableErrorVisible) { tableErrorVisible = true; notify('Axios unavailable', 'The shared window.AppAxios client is required.', 'error'); }
                        return;
                    }
                    http.get(urls.data, {params:data}).then(function (response) {
                        tableErrorVisible = false; callback(response.data);
                    }).catch(function (error) {
                        callback(emptyTable(data.draw));
                        const payload = (error.response && error.response.data) || {};
                        if (!tableErrorVisible) { tableErrorVisible = true; notify('Communication list load failed', firstError(payload) || payload.message || 'Unable to load CRM communications.', 'error'); }
                    });
                },
                columns:[
                    {data:'communication.customer', name:'customer_id', orderable:false, searchable:false, render:function (value, type, row) { return row.communication.customer_profile_url ? '<a href="' + attr(row.communication.customer_profile_url) + '">' + esc(value) + '</a>' : esc(value); }},
                    {data:'communication.channel', name:'channel', render:function (value) { return badge(value); }},
                    {data:'communication.direction', name:'direction', render:function (value) { return directionBadge(value); }},
                    {data:'communication.status', name:'status', render:function (value) { return statusBadge(value); }},
                    {data:'communication.subject', name:'subject', defaultContent:''},
                    {data:'communication.message_summary', name:'message', orderable:false, render:function (value) { return '<div class="crm-communication-summary">' + esc(value) + '</div>'; }},
                    {data:'communication.source_module', name:'source_module', defaultContent:''},
                    {data:'communication.provider', name:'provider', defaultContent:''},
                    {data:'communication.actor', name:'sent_by', orderable:false, searchable:false, defaultContent:''},
                    {data:'communication.logged_at', name:'sent_at', defaultContent:''},
                    {data:'communication.created_at', name:'created_at', defaultContent:''},
                    {data:'communication.id', name:'id', orderable:false, searchable:false, render:function (value, type, row) { return detailsAction(row.communication); }}
                ]
            });
            $('#crmCommunicationFilterCustomer,#crmCommunicationFilterChannel,#crmCommunicationFilterDirection,#crmCommunicationFilterStatus,#crmCommunicationFilterSource,#crmCommunicationSentFrom,#crmCommunicationSentTo').on('change', function () { if (!filtersMuted) table.ajax.reload(); });
            $('#resetCrmCommunicationFilters').on('click', function () {
                filtersMuted = true;
                setSelected('#crmCommunicationFilterCustomer');
                $('#crmCommunicationFilterChannel,#crmCommunicationFilterDirection,#crmCommunicationFilterStatus,#crmCommunicationFilterSource,#crmCommunicationSentFrom,#crmCommunicationSentTo').val('');
                filtersMuted = false;
                table.ajax.reload();
            });

            function resetErrors() { $('#crmCommunicationForm .is-invalid').removeClass('is-invalid'); $('#crmCommunicationForm .invalid-feedback').text(''); }
            function applyErrors(errors) { Object.keys(errors || {}).forEach(function (key) { const field = key.split('.')[0], input = $('#crmCommunicationForm [name="' + field + '"]'); input.addClass('is-invalid'); $('#error-' + field).text(Array.isArray(errors[key]) ? errors[key][0] : errors[key]); }); }
            function saveLoading(state) { communicationSubmitting = state; $('#saveCrmCommunication').prop('disabled', state); $('.js-save-communication-label').text(state ? 'Logging...' : 'Log History'); }
            function resetForm(usePrefill) {
                document.getElementById('crmCommunicationForm').reset(); resetErrors(); saveLoading(false); setSelected('#crmCommunicationCustomer');
                if (usePrefill && prefillCustomer) setSelected('#crmCommunicationCustomer', prefillCustomer.id, prefillCustomer.text);
            }
            $('#openCreateCrmCommunication').on('click', function () { resetForm(true); $('#crmCommunicationModal').modal('show'); });
            $('#crmCommunicationForm').on('submit', function (event) {
                event.preventDefault(); if (communicationSubmitting) return;
                const http = client(); if (!http) { notify('Axios unavailable', 'The shared window.AppAxios client is required.', 'error'); return; }
                resetErrors(); saveLoading(true);
                const payload = {
                    customer_id:$('#crmCommunicationCustomer').val(), channel:$('#crmCommunicationChannel').val(), direction:$('#crmCommunicationDirection').val(),
                    interaction_at:$('#crmCommunicationInteractionAt').val(), subject:$('#crmCommunicationSubject').val(), message:$('#crmCommunicationMessage').val()
                };
                http.post(urls.store, payload).then(function (response) {
                    $('#crmCommunicationModal').modal('hide'); table.ajax.reload(null, false);
                    notify('History logged', response.data.message || 'CRM communication history logged. No message was sent.', 'success');
                }).catch(function (error) {
                    const response = error.response || {}, data = response.data || {};
                    if (response.status === 422 && data.errors) applyErrors(data.errors);
                    notify('Logging failed', data.message || 'Unable to log the CRM communication history.', 'error');
                }).finally(function () { saveLoading(false); });
            });
            $('#crmCommunicationModal').on('hidden.bs.modal', function () { resetForm(false); });

            function detailRow(label, value) { return '<div class="mb-3"><div class="crm-communication-detail-label">' + esc(label) + '</div><div class="crm-communication-detail-value">' + esc(value) + '</div></div>'; }
            function renderDetails(record) {
                let html = '<div class="row"><div class="col-md-6">' + detailRow('Customer', record.customer) + detailRow('Channel', record.channel) + detailRow('Direction', record.direction) + detailRow('Status', record.status) + detailRow('Source module', record.source_module) + '</div>' +
                    '<div class="col-md-6">' + detailRow('Logged by', record.actor) + detailRow('Sent or logged date', record.logged_at) + detailRow('Created at', record.created_at) + detailRow('Provider', record.provider) + detailRow('Provider reference', record.provider_reference) + '</div></div>' +
                    detailRow('Subject', record.subject) + detailRow('Full message or interaction summary', record.message);
                if (record.failed_at || record.failure_reason) html += '<hr>' + detailRow('Failed at', record.failed_at) + detailRow('Failure reason', record.failure_reason);
                return html;
            }
            $('#crmCommunicationsTable').on('click', '.js-view-communication', function () {
                if (detailsLoading) return;
                const id = String($(this).data('id')), http = client();
                if (!http) { notify('Axios unavailable', 'The shared window.AppAxios client is required.', 'error'); return; }
                detailsLoading = true; $('#crmCommunicationDetailsBody').html('<div class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm mr-2"></span>Loading...</div>'); $('#crmCommunicationDetailsModal').modal('show');
                http.get(endpoint(urls.show, id)).then(function (response) { $('#crmCommunicationDetailsBody').html(renderDetails(response.data.communication || {})); })
                    .catch(function (error) { const data = (error.response && error.response.data) || {}; $('#crmCommunicationDetailsBody').html('<div class="alert alert-danger mb-0">' + esc(data.message || 'Unable to load communication details.') + '</div>'); })
                    .finally(function () { detailsLoading = false; });
            });

            if (shouldOpenCreate && permissions.can_create) { resetForm(true); $('#crmCommunicationModal').modal('show'); }
        })(jQuery);
    </script>
@endsection
