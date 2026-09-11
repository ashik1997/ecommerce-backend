@extends('backend.master')

@section('header_css')
    <link href="{{ url('dataTable') }}/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="{{ url('dataTable') }}/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link href="{{ url('assets') }}/plugins/select2/select2.min.css" rel="stylesheet" type="text/css" />
    <style>
        .crm-lead-card { border:1px solid #d7eeee; border-radius:14px; box-shadow:0 8px 20px rgba(15,118,110,.05); }
        .crm-lead-card .card-title { color:#0f766e; font-weight:800; }
        .crm-lead-filter { background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:14px; }
        .crm-lead-muted { color:#64748b; font-size:12px; }
        .crm-lead-detail-label { color:#64748b; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.03em; }
        .crm-lead-detail-value { white-space:pre-wrap; word-break:break-word; }
        .select2-container { width:100% !important; }
        .dataTables_wrapper .dataTables_paginate .paginate_button { padding:0; }
        table.dataTable tbody td { vertical-align:middle; }
    </style>
@endsection

@section('page_title') CRM Leads @endsection
@section('page_heading') CRM Leads @endsection

@section('content')
    <div class="row"><div class="col-lg-12"><div class="card crm-lead-card"><div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            <div>
                <h4 class="card-title mb-1">Lead Management Foundation</h4>
                <small class="text-muted">Capture, assign, qualify, convert, or archive CRM leads without replacing customer, order, SMS, due, or accounting flows.</small>
            </div>
            <div>
                <a href="{{ route('crm.leads.pipeline') }}" class="btn btn-sm btn-outline-primary mr-1"><i class="feather-columns"></i> Pipeline Board</a>
                @if ($permissions['can_create'])
                    <button type="button" class="btn btn-sm btn-primary" id="openCreateCrmLead"><i class="feather-plus-circle"></i> New Lead</button>
                @endif
            </div>
        </div>

        <div class="crm-lead-filter mb-3"><div class="row">
            <div class="col-lg-3 col-md-6 mb-2"><label>Customer</label><select id="crmLeadFilterCustomer" class="form-control"></select></div>
            <div class="col-lg-3 col-md-6 mb-2"><label>Assigned user</label><select id="crmLeadFilterUser" class="form-control"></select></div>
            <div class="col-lg-2 col-md-4 mb-2"><label>Status</label><select id="crmLeadFilterStatus" class="form-control"><option value="">All statuses</option>@foreach($filterOptions['statuses'] as $status)<option value="{{ $status }}">{{ ucfirst(str_replace('_',' ',$status)) }}</option>@endforeach</select></div>
            <div class="col-lg-2 col-md-4 mb-2"><label>Priority</label><select id="crmLeadFilterPriority" class="form-control"><option value="">All priorities</option>@foreach($filterOptions['priorities'] as $priority)<option value="{{ $priority }}">{{ ucfirst($priority) }}</option>@endforeach</select></div>
            <div class="col-lg-2 col-md-4 mb-2"><label>Source</label><select id="crmLeadFilterSource" class="form-control"><option value="">All sources</option>@foreach($filterOptions['sources'] as $source)<option value="{{ $source }}">{{ ucfirst(str_replace('_',' ',$source)) }}</option>@endforeach</select></div>
            <div class="col-lg-3 col-md-6 mb-2"><label>Follow-up from</label><input type="date" id="crmLeadFollowUpFrom" class="form-control"></div>
            <div class="col-lg-3 col-md-6 mb-2"><label>Follow-up to</label><input type="date" id="crmLeadFollowUpTo" class="form-control"></div>
            <div class="col-lg-2 col-md-4 mb-2 d-flex align-items-end"><button type="button" id="resetCrmLeadFilters" class="btn btn-outline-secondary btn-block">Reset filters</button></div>
        </div></div>

        <div class="table-responsive"><table class="table table-bordered table-striped mb-0" id="crmLeadsTable">
            <thead><tr><th>Lead</th><th>Contact</th><th>Status</th><th>Priority</th><th>Source</th><th>Score</th><th>Value</th><th>Assigned</th><th>Customer</th><th>Next follow-up</th><th>Created</th><th>Actions</th></tr></thead><tbody></tbody>
        </table></div>
    </div></div></div></div>

    <div class="modal fade" id="crmLeadModal" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered" role="document"><div class="modal-content"><form id="crmLeadForm" novalidate>
        <div class="modal-header"><h5 class="modal-title" id="crmLeadModalTitle">New CRM Lead</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
        <div class="modal-body"><input type="hidden" id="crmLeadId">
            <div class="row">
                <div class="col-md-6 form-group"><label>Name <span class="text-danger">*</span></label><input name="name" id="leadName" class="form-control"><div class="invalid-feedback" data-error-for="name"></div></div>
                <div class="col-md-6 form-group"><label>Company</label><input name="company_name" id="leadCompany" class="form-control"><div class="invalid-feedback" data-error-for="company_name"></div></div>
                <div class="col-md-6 form-group"><label>Phone</label><input name="phone" id="leadPhone" class="form-control"><div class="invalid-feedback" data-error-for="phone"></div></div>
                <div class="col-md-6 form-group"><label>Email</label><input name="email" id="leadEmail" class="form-control"><div class="invalid-feedback" data-error-for="email"></div></div>
                <div class="col-md-6 form-group"><label>Existing customer</label><select name="customer_id" id="leadCustomer" class="form-control"></select><div class="invalid-feedback d-block" data-error-for="customer_id"></div></div>
                <div class="col-md-6 form-group"><label>Assigned user</label><select name="assigned_user_id" id="leadAssignedUser" class="form-control"></select><div class="invalid-feedback d-block" data-error-for="assigned_user_id"></div></div>
                <div class="col-md-4 form-group"><label>Source</label><select name="source" id="leadSource" class="form-control"><option value="">Select source</option>@foreach($filterOptions['sources'] as $source)<option value="{{ $source }}">{{ ucfirst(str_replace('_',' ',$source)) }}</option>@endforeach</select><div class="invalid-feedback" data-error-for="source"></div></div>
                <div class="col-md-4 form-group"><label>Priority</label><select name="priority" id="leadPriority" class="form-control">@foreach($filterOptions['priorities'] as $priority)<option value="{{ $priority }}" @if($priority === 'normal') selected @endif>{{ ucfirst($priority) }}</option>@endforeach</select><div class="invalid-feedback" data-error-for="priority"></div></div>
                <div class="col-md-4 form-group"><label>Score</label><input type="number" min="0" max="100" name="score" id="leadScore" class="form-control" value="0"><div class="invalid-feedback" data-error-for="score"></div></div>
                <div class="col-md-6 form-group"><label>Estimated value</label><input type="number" min="0" step="0.01" name="estimated_value" id="leadEstimatedValue" class="form-control"><div class="invalid-feedback" data-error-for="estimated_value"></div></div>
                <div class="col-md-6 form-group"><label>Next follow-up</label><input type="datetime-local" name="next_follow_up_at" id="leadNextFollowUp" class="form-control"><div class="invalid-feedback" data-error-for="next_follow_up_at"></div></div>
                <div class="col-md-12 form-group"><label>Requirement</label><textarea name="requirement" id="leadRequirement" rows="4" class="form-control"></textarea><div class="invalid-feedback" data-error-for="requirement"></div></div>
            </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary" id="saveCrmLead">Save Lead</button></div>
    </form></div></div></div>

    <div class="modal fade" id="crmLeadStatusModal" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-dialog modal-dialog-centered" role="document"><div class="modal-content"><form id="crmLeadStatusForm" novalidate>
        <div class="modal-header"><h5 class="modal-title">Update Lead Status</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
        <div class="modal-body"><input type="hidden" id="statusLeadId"><div class="form-group"><label>Status</label><select id="leadStatus" name="status" class="form-control">@foreach($filterOptions['statuses'] as $status)<option value="{{ $status }}">{{ ucfirst($status) }}</option>@endforeach</select><div class="invalid-feedback" data-error-for="status"></div></div><div class="form-group"><label>Existing customer for conversion</label><select id="statusCustomer" name="customer_id" class="form-control"></select><small class="crm-lead-muted">Required only when status is converted.</small><div class="invalid-feedback d-block" data-error-for="customer_id"></div></div><div class="form-group"><label>Lost reason</label><textarea id="leadLostReason" name="lost_reason" class="form-control" rows="3"></textarea><div class="invalid-feedback" data-error-for="lost_reason"></div></div></div>
        <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary" id="saveCrmLeadStatus">Update Status</button></div>
    </form></div></div></div>

    <div class="modal fade" id="crmLeadDetailModal" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered" role="document"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Lead Details</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div><div class="modal-body" id="crmLeadDetailBody"></div></div></div></div>
@endsection

@section('footer_js')
    <script src="{{ url('dataTable') }}/js/jquery.dataTables.min.js"></script>
    <script src="{{ url('dataTable') }}/js/dataTables.bootstrap4.min.js"></script>
    <script src="{{ url('assets') }}/plugins/select2/select2.min.js"></script>
    <script>
        (function ($) {
            'use strict';

            const routes = {
                data: @json(route('crm.leads.data')),
                store: @json(route('crm.leads.store')),
                customers: @json(route('crm.leads.options.customers')),
                users: @json(route('crm.leads.options.users')),
                base: @json(url('/crm/leads'))
            };
            const canCreate = @json($permissions['can_create']);
            const canUpdate = @json($permissions['can_update']);
            const canDelete = @json($permissions['can_delete']);
            const prefillCustomer = @json($prefillCustomer);
            const openCreateOnLoad = @json($openCreate);
            const archiveRequests = new Set();
            let filtersMuted = false;
            let tableErrorVisible = false;
            let leadSubmitting = false;
            let statusSubmitting = false;
            let detailsLoading = false;

            function http() { return window.AppAxios || null; }
            function notify(title, text, icon) { if (window.Swal) Swal.fire({title:title, text:text || '', icon:icon || 'info'}); else window.alert(text || title); }
            function escapeHtml(value) { return $('<div>').text(value == null ? '' : String(value)).html(); }
            function escapeAttr(value) { return escapeHtml(value).replace(/`/g, '&#96;'); }
            function emptyTable(draw) { return {draw:Number(draw || 0), recordsTotal:0, recordsFiltered:0, data:[]}; }
            function firstError(payload) { const errors = payload.errors || {}; const key = Object.keys(errors)[0]; return key && Array.isArray(errors[key]) ? errors[key][0] : null; }
            function resetErrors(form) { form.find('.is-invalid').removeClass('is-invalid'); form.find('[data-error-for]').text(''); }
            function applyErrors(form, errors) { Object.keys(errors || {}).forEach(function (key) { const field = key.split('.')[0]; form.find('[name="' + field + '"]').addClass('is-invalid'); form.find('[data-error-for="' + field + '"]').text(Array.isArray(errors[key]) ? errors[key][0] : errors[key]); }); }
            function setBusy(button, busy, label) { if (busy) button.data('original-text', button.html()).prop('disabled', true).html(label || 'Please wait...'); else button.prop('disabled', false).html(button.data('original-text') || label || 'Save'); }

            function select2Transport(params, success, failure) {
                const client = http();
                let cancelled = false;
                if (!client) { failure(); return {abort:function () {}}; }
                client.get(params.url, {params:params.data || {}}).then(function (response) {
                    if (!cancelled) success(response.data);
                }).catch(function (error) {
                    if (!cancelled) failure(error);
                });
                return {abort:function () { cancelled = true; }};
            }
            function initSelect(selector, url, placeholder) {
                const select = $(selector);
                if (select.hasClass('select2-hidden-accessible')) select.select2('destroy');
                select.select2({
                    width:'100%', placeholder:placeholder, allowClear:true,
                    ajax:{url:url, delay:250, transport:select2Transport, data:function (params) { return {q:params.term || ''}; }, processResults:function (data) { return {results:data.results || []}; }}
                });
            }
            function setSelected(selector, option) {
                const select = $(selector); select.empty();
                if (option && option.id) select.append(new Option(option.text || option.name || ('#' + option.id), String(option.id), true, true)).trigger('change.select2');
                else select.val(null).trigger('change.select2');
            }

            initSelect('#crmLeadFilterCustomer,#leadCustomer,#statusCustomer', routes.customers, 'Search customer');
            initSelect('#crmLeadFilterUser,#leadAssignedUser', routes.users, 'Search user');
            if (prefillCustomer) setSelected('#crmLeadFilterCustomer', prefillCustomer);

            const table = $('#crmLeadsTable').DataTable({
                processing:true, serverSide:true, pageLength:25,
                ajax:function (data, callback) {
                    const client = http();
                    data.customer_id = $('#crmLeadFilterCustomer').val();
                    data.assigned_user_id = $('#crmLeadFilterUser').val();
                    data.status = $('#crmLeadFilterStatus').val();
                    data.priority = $('#crmLeadFilterPriority').val();
                    data.source = $('#crmLeadFilterSource').val();
                    data.follow_up_from = $('#crmLeadFollowUpFrom').val();
                    data.follow_up_to = $('#crmLeadFollowUpTo').val();
                    if (!client) {
                        callback(emptyTable(data.draw));
                        if (!tableErrorVisible) { tableErrorVisible = true; notify('Axios unavailable', 'The shared window.AppAxios client is required.', 'error'); }
                        return;
                    }
                    client.get(routes.data, {params:data}).then(function (response) {
                        tableErrorVisible = false; callback(response.data);
                    }).catch(function (error) {
                        callback(emptyTable(data.draw));
                        const payload = (error.response && error.response.data) || {};
                        if (!tableErrorVisible) { tableErrorVisible = true; notify('Lead list load failed', firstError(payload) || payload.message || 'Unable to load CRM leads.', 'error'); }
                    });
                },
                columns:[
                    {data:'lead', name:'name', render:function (lead) { return '<strong>' + escapeHtml(lead.name) + '</strong><br><small class="crm-lead-muted">' + escapeHtml(lead.company_name || 'No company') + '</small>'; }},
                    {data:'lead', name:'phone', render:function (lead) { return escapeHtml(lead.phone || '-') + '<br><small>' + escapeHtml(lead.email || '') + '</small>'; }},
                    {data:'lead', name:'status', render:function (lead) { return '<span class="badge badge-info">' + escapeHtml((lead.status || '').replace(/_/g, ' ')) + '</span>'; }},
                    {data:'lead', name:'priority', render:function (lead) { return escapeHtml(lead.priority || '-'); }},
                    {data:'lead', name:'source', render:function (lead) { return escapeHtml((lead.source || '-').replace(/_/g, ' ')); }},
                    {data:'lead', name:'score', render:function (lead) { return escapeHtml(lead.score); }},
                    {data:'lead', name:'estimated_value', render:function (lead) { return escapeHtml(lead.estimated_value || '-'); }},
                    {data:'lead', name:'assigned_user', render:function (lead) { return escapeHtml(lead.assigned_user ? lead.assigned_user.name : '-'); }},
                    {data:'lead', name:'customer', render:function (lead) { if (!lead.customer) return '-'; return lead.customer.profile_url ? '<a href="' + escapeAttr(lead.customer.profile_url) + '">' + escapeHtml(lead.customer.name) + '</a>' : escapeHtml(lead.customer.name); }},
                    {data:'lead', name:'next_follow_up_at', render:function (lead) { return escapeHtml(lead.next_follow_up_at || '-'); }},
                    {data:'lead', name:'created_at', render:function (lead) { return escapeHtml(lead.created_at || '-'); }},
                    {data:'lead', orderable:false, searchable:false, render:function (lead) { let html = '<button type="button" class="btn btn-xs btn-outline-info view-lead" data-id="' + Number(lead.id) + '">View</button> '; if (canUpdate && lead.can_update) html += '<button type="button" class="btn btn-xs btn-outline-primary edit-lead" data-id="' + Number(lead.id) + '">Edit</button> '; if (canUpdate && lead.can_change_status) html += '<button type="button" class="btn btn-xs btn-outline-success status-lead" data-id="' + Number(lead.id) + '" data-status="' + escapeAttr(lead.status) + '">Status</button> '; if (canDelete && lead.can_archive) html += '<button type="button" class="btn btn-xs btn-outline-danger archive-lead" data-id="' + Number(lead.id) + '">Archive</button>'; return html; }}
                ]
            });

            $('#crmLeadFilterCustomer,#crmLeadFilterUser,#crmLeadFilterStatus,#crmLeadFilterPriority,#crmLeadFilterSource,#crmLeadFollowUpFrom,#crmLeadFollowUpTo').on('change', function () { if (!filtersMuted) table.ajax.reload(); });
            $('#resetCrmLeadFilters').on('click', function () {
                filtersMuted = true;
                setSelected('#crmLeadFilterCustomer'); setSelected('#crmLeadFilterUser');
                $('#crmLeadFilterStatus,#crmLeadFilterPriority,#crmLeadFilterSource,#crmLeadFollowUpFrom,#crmLeadFollowUpTo').val('');
                filtersMuted = false;
                table.ajax.reload();
            });

            function openCreateLead() {
                $('#crmLeadForm')[0].reset(); $('#crmLeadId').val('');
                setSelected('#leadCustomer'); setSelected('#leadAssignedUser');
                if (prefillCustomer) setSelected('#leadCustomer', prefillCustomer);
                resetErrors($('#crmLeadForm')); $('#crmLeadModalTitle').text('New CRM Lead'); $('#crmLeadModal').modal('show');
            }
            function fillLeadForm(lead) {
                $('#crmLeadId').val(lead.id); $('#leadName').val(lead.name); $('#leadCompany').val(lead.company_name); $('#leadPhone').val(lead.phone); $('#leadEmail').val(lead.email);
                $('#leadSource').val(lead.source); $('#leadPriority').val(lead.priority || 'normal'); $('#leadScore').val(lead.score || 0); $('#leadEstimatedValue').val(lead.estimated_value);
                $('#leadRequirement').val(lead.requirement); $('#leadNextFollowUp').val(lead.next_follow_up_at ? lead.next_follow_up_at.replace(' ', 'T') : '');
                setSelected('#leadCustomer', lead.customer); setSelected('#leadAssignedUser', lead.assigned_user);
            }
            $('#openCreateCrmLead').on('click', openCreateLead);
            if (openCreateOnLoad && canCreate) openCreateLead();

            $('#crmLeadsTable').on('click', '.edit-lead,.view-lead', function () {
                if (detailsLoading) return;
                const id = String($(this).data('id')); const isEdit = $(this).hasClass('edit-lead'); const client = http();
                if (!client) { notify('Axios unavailable', 'The shared window.AppAxios client is required.', 'error'); return; }
                detailsLoading = true;
                client.get(routes.base + '/' + id).then(function (response) {
                    const lead = response.data.lead || {};
                    if (isEdit) { resetErrors($('#crmLeadForm')); fillLeadForm(lead); $('#crmLeadModalTitle').text('Edit CRM Lead'); $('#crmLeadModal').modal('show'); return; }
                    $('#crmLeadDetailBody').html('<div class="row"><div class="col-md-6"><div class="crm-lead-detail-label">Lead</div><div class="crm-lead-detail-value">' + escapeHtml(lead.name) + '</div></div><div class="col-md-6"><div class="crm-lead-detail-label">Status</div><div>' + escapeHtml(lead.status) + '</div></div><div class="col-md-12 mt-3"><div class="crm-lead-detail-label">Requirement</div><div class="crm-lead-detail-value">' + escapeHtml(lead.requirement || '-') + '</div></div><div class="col-md-12 mt-3"><div class="crm-lead-detail-label">Lost reason</div><div class="crm-lead-detail-value">' + escapeHtml(lead.lost_reason || '-') + '</div></div></div>');
                    $('#crmLeadDetailModal').modal('show');
                }).catch(function () { notify('Load failed', 'Unable to load CRM lead details.', 'error'); }).finally(function () { detailsLoading = false; });
            });

            $('#crmLeadForm').on('submit', function (event) {
                event.preventDefault(); if (leadSubmitting) return;
                const form = $(this); const id = $('#crmLeadId').val(); const client = http(); const button = $('#saveCrmLead');
                if (!client) { notify('Axios unavailable', 'The shared window.AppAxios client is required.', 'error'); return; }
                resetErrors(form); leadSubmitting = true; setBusy(button, true, 'Saving...');
                const payload = Object.fromEntries(new FormData(this).entries());
                const request = id ? client.post(routes.base + '/' + id, payload) : client.post(routes.store, payload);
                request.then(function (response) { $('#crmLeadModal').modal('hide'); table.ajax.reload(null, false); notify('Saved', response.data.message || 'CRM lead saved.', 'success'); })
                    .catch(function (error) { const payload = (error.response && error.response.data) || {}; if (error.response && error.response.status === 422) applyErrors(form, payload.errors || {}); else notify('Save failed', payload.message || 'Unable to save CRM lead.', 'error'); })
                    .finally(function () { leadSubmitting = false; setBusy(button, false, 'Save Lead'); });
            });

            $('#crmLeadsTable').on('click', '.status-lead', function () {
                $('#crmLeadStatusForm')[0].reset(); resetErrors($('#crmLeadStatusForm')); $('#statusLeadId').val($(this).data('id')); $('#leadStatus').val($(this).data('status')); setSelected('#statusCustomer'); $('#crmLeadStatusModal').modal('show');
            });
            $('#crmLeadStatusForm').on('submit', function (event) {
                event.preventDefault(); if (statusSubmitting) return;
                const form = $(this); const id = $('#statusLeadId').val(); const client = http(); const button = $('#saveCrmLeadStatus');
                if (!client) { notify('Axios unavailable', 'The shared window.AppAxios client is required.', 'error'); return; }
                resetErrors(form); statusSubmitting = true; setBusy(button, true, 'Updating...');
                client.post(routes.base + '/' + id + '/status', Object.fromEntries(new FormData(this).entries())).then(function (response) {
                    $('#crmLeadStatusModal').modal('hide'); table.ajax.reload(null, false); notify('Updated', response.data.message || 'Status updated.', 'success');
                }).catch(function (error) {
                    const payload = (error.response && error.response.data) || {};
                    if (error.response && error.response.status === 422) applyErrors(form, payload.errors || {}); else notify('Update failed', payload.message || 'Unable to update lead status.', 'error');
                }).finally(function () { statusSubmitting = false; setBusy(button, false, 'Update Status'); });
            });

            $('#crmLeadsTable').on('click', '.archive-lead', function () {
                const button = $(this); const id = String(button.data('id')); const client = http();
                if (!client) { notify('Axios unavailable', 'The shared window.AppAxios client is required.', 'error'); return; }
                if (archiveRequests.has(id)) return;
                const run = function () {
                    if (archiveRequests.has(id)) return;
                    archiveRequests.add(id); button.prop('disabled', true);
                    client.post(routes.base + '/' + id + '/archive').then(function (response) { table.ajax.reload(null, false); notify('Archived', response.data.message || 'CRM lead archived.', 'success'); })
                        .catch(function (error) { const payload = (error.response && error.response.data) || {}; notify('Archive failed', payload.message || 'Unable to archive CRM lead.', 'error'); })
                        .finally(function () { archiveRequests.delete(id); button.prop('disabled', false); });
                };
                if (window.Swal) Swal.fire({title:'Archive lead?', text:'This soft-archives the CRM lead and does not delete customers or accounting data.', icon:'warning', showCancelButton:true, confirmButtonText:'Archive'}).then(function (result) { if (result.isConfirmed) run(); });
                else if (window.confirm('Archive this CRM lead?')) run();
            });
        })(jQuery);
    </script>
@endsection
