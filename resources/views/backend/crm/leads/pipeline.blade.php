@extends('backend.master')

@section('header_css')
    <link href="{{ url('assets') }}/plugins/select2/select2.min.css" rel="stylesheet" type="text/css" />
    <style>
        .crm-pipeline-shell { border:1px solid #d7eeee; border-radius:14px; box-shadow:0 8px 20px rgba(15,118,110,.05); }
        .crm-pipeline-shell .card-title { color:#0f766e; font-weight:800; }
        .crm-pipeline-filter { background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:14px; }
        .crm-pipeline-board { display:flex; gap:14px; overflow-x:auto; padding:4px 2px 12px; align-items:flex-start; }
        .crm-pipeline-column { flex:0 0 300px; width:300px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden; }
        .crm-pipeline-column-header { padding:12px 13px; border-bottom:1px solid #e2e8f0; background:#ffffff; }
        .crm-pipeline-column-title { margin:0; font-weight:800; color:#334155; font-size:14px; }
        .crm-pipeline-column-body { padding:10px; min-height:160px; max-height:68vh; overflow-y:auto; }
        .crm-pipeline-card { background:#ffffff; border:1px solid #e2e8f0; border-radius:10px; padding:11px; margin-bottom:10px; box-shadow:0 4px 12px rgba(15,23,42,.04); }
        .crm-pipeline-card:last-child { margin-bottom:0; }
        .crm-pipeline-card-title { color:#0f766e; font-weight:800; line-height:1.3; }
        .crm-pipeline-muted { color:#64748b; font-size:12px; }
        .crm-pipeline-meta { border-top:1px dashed #e2e8f0; margin-top:8px; padding-top:8px; }
        .crm-pipeline-empty { color:#94a3b8; font-size:12px; text-align:center; padding:22px 8px; }
        .crm-pipeline-loading { color:#64748b; text-align:center; padding:28px 10px; }
        .crm-pipeline-limit { color:#92400e; background:#fffbeb; border-top:1px solid #fde68a; padding:8px 10px; font-size:11px; }
        .crm-pipeline-status-new { border-top:3px solid #64748b; }
        .crm-pipeline-status-contacted { border-top:3px solid #0ea5e9; }
        .crm-pipeline-status-qualified { border-top:3px solid #8b5cf6; }
        .crm-pipeline-status-converted { border-top:3px solid #10b981; }
        .crm-pipeline-status-lost { border-top:3px solid #ef4444; }
        .select2-container { width:100% !important; }
    </style>
@endsection

@section('page_title') CRM Lead Pipeline @endsection
@section('page_heading') CRM Lead Pipeline @endsection

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card crm-pipeline-shell">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                        <div>
                            <h4 class="card-title mb-1">Lead Pipeline Board</h4>
                            <small class="text-muted">Operational Kanban-style lead view. Status moves reuse the existing CRM lead workflow and do not create customers, quotations, orders, payments, dues, ledger entries, or accounting records.</small>
                        </div>
                        <div class="mt-2 mt-md-0">
                            <a href="{{ route('crm.leads.index') }}" class="btn btn-sm btn-outline-primary mr-1"><i class="feather-list"></i> Lead Worklist</a>
                            <button type="button" class="btn btn-sm btn-primary" id="refreshCrmLeadPipeline"><i class="feather-refresh-cw"></i> Refresh</button>
                        </div>
                    </div>

                    <div class="crm-pipeline-filter mb-3">
                        <div class="row">
                            <div class="col-lg-3 col-md-6 mb-2"><label>Search</label><input type="text" id="crmPipelineSearch" maxlength="255" class="form-control" placeholder="Lead, company, contact, customer..." /></div>
                            <div class="col-lg-3 col-md-6 mb-2"><label>Customer</label><select id="crmPipelineCustomer" class="form-control"></select></div>
                            <div class="col-lg-3 col-md-6 mb-2"><label>Assigned user</label><select id="crmPipelineUser" class="form-control"></select></div>
                            <div class="col-lg-3 col-md-6 mb-2"><label>Priority</label><select id="crmPipelinePriority" class="form-control"><option value="">All priorities</option>@foreach($filterOptions['priorities'] as $priority)<option value="{{ $priority }}">{{ ucfirst($priority) }}</option>@endforeach</select></div>
                            <div class="col-lg-3 col-md-6 mb-2"><label>Source</label><select id="crmPipelineSource" class="form-control"><option value="">All sources</option>@foreach($filterOptions['sources'] as $source)<option value="{{ $source }}">{{ ucfirst(str_replace('_', ' ', $source)) }}</option>@endforeach</select></div>
                            <div class="col-lg-3 col-md-6 mb-2"><label>Follow-up from</label><input type="date" id="crmPipelineFollowUpFrom" class="form-control" /></div>
                            <div class="col-lg-3 col-md-6 mb-2"><label>Follow-up to</label><input type="date" id="crmPipelineFollowUpTo" class="form-control" /></div>
                            <div class="col-lg-3 col-md-6 mb-2 d-flex align-items-end">
                                <button type="button" class="btn btn-outline-secondary btn-block" id="resetCrmLeadPipelineFilters">Reset filters</button>
                            </div>
                        </div>
                    </div>

                    <div id="crmLeadPipelineFeedback" class="alert alert-danger d-none"></div>
                    <div id="crmLeadPipelineBoard" class="crm-pipeline-board" aria-live="polite">
                        <div class="crm-pipeline-loading w-100">Loading CRM lead pipeline...</div>
                    </div>
                    <small class="text-muted" id="crmLeadPipelineGeneratedAt"></small>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="crmPipelineStatusModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form id="crmPipelineStatusForm" novalidate>
                    <div class="modal-header">
                        <h5 class="modal-title">Move CRM Lead</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="crmPipelineLeadId" />
                        <div class="form-group">
                            <label>Lead</label>
                            <input type="text" id="crmPipelineLeadName" class="form-control" readonly />
                        </div>
                        <div class="form-group">
                            <label>Status <span class="text-danger">*</span></label>
                            <select id="crmPipelineStatus" name="status" class="form-control">
                                @foreach($filterOptions['statuses'] as $status)
                                    <option value="{{ $status }}">{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" data-error-for="status"></div>
                        </div>
                        <div class="form-group d-none" id="crmPipelineStatusCustomerWrap">
                            <label>Existing customer for conversion <span class="text-danger">*</span></label>
                            <select id="crmPipelineStatusCustomer" name="customer_id" class="form-control"></select>
                            <small class="crm-pipeline-muted">Conversion links this lead to an existing customer only.</small>
                            <div class="invalid-feedback d-block" data-error-for="customer_id"></div>
                        </div>
                        <div class="form-group d-none" id="crmPipelineLostReasonWrap">
                            <label>Lost reason <span class="text-danger">*</span></label>
                            <textarea id="crmPipelineLostReason" name="lost_reason" class="form-control" rows="3" maxlength="2000"></textarea>
                            <div class="invalid-feedback" data-error-for="lost_reason"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="saveCrmPipelineStatus">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('footer_js')
    <script src="{{ url('assets') }}/plugins/select2/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        (function () {
            'use strict';

            const routes = {
                data: @json(route('crm.leads.pipeline.data')),
                customers: @json(route('crm.leads.options.customers')),
                users: @json(route('crm.leads.options.users')),
                leadBase: @json(url('/crm/leads'))
            };
            const canUpdate = @json($permissions['can_update']);
            const prefillCustomer = @json($prefillCustomer);
            let boardCards = {};
            let loadSequence = 0;
            let filtersMuted = false;
            let statusSubmitting = false;

            function http() { return window.AppAxios || null; }
            function escapeHtml(value) { return $('<div>').text(value == null ? '' : String(value)).html(); }
            function escapeAttr(value) { return String(value == null ? '' : value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;').replace(/`/g, '&#096;'); }
            function label(value) { return String(value || '-').replace(/_/g, ' ').replace(/\b\w/g, function (letter) { return letter.toUpperCase(); }); }
            function setBusy(button, busy) { if (busy) { button.data('original-text', button.html()).prop('disabled', true).html('Saving...'); } else { button.prop('disabled', false).html(button.data('original-text') || 'Update Status'); } }
            function notify(title, text, icon) { if (window.Swal) { Swal.fire({ title: title, text: text, icon: icon }); } else { alert(text || title); } }
            function showFeedback(message) { $('#crmLeadPipelineFeedback').removeClass('d-none').text(message); }
            function hideFeedback() { $('#crmLeadPipelineFeedback').addClass('d-none').text(''); }
            function resetErrors(form) { form.find('.is-invalid').removeClass('is-invalid'); form.find('[data-error-for]').text(''); }
            function applyErrors(form, errors) { Object.keys(errors || {}).forEach(function (name) { form.find('[name="' + name + '"]').addClass('is-invalid'); form.find('[data-error-for="' + name + '"]').text(errors[name][0]); }); }

            function select2Transport(params, success, failure) {
                const client = http();
                let cancelled = false;
                if (!client) {
                    failure();
                    return { abort: function () {} };
                }
                client.get(params.url, { params: params.data || {} }).then(function (response) {
                    if (!cancelled) success(response.data);
                }).catch(function (error) {
                    if (!cancelled) failure(error);
                });
                return { abort: function () { cancelled = true; } };
            }

            function initRemoteSelect(selector, url, placeholder) {
                $(selector).select2({
                    width: '100%',
                    allowClear: true,
                    placeholder: placeholder,
                    ajax: {
                        url: url,
                        delay: 250,
                        transport: select2Transport,
                        data: function (params) { return { q: params.term || '' }; },
                        processResults: function (data) { return data; }
                    }
                });
            }

            function selectedOption(select, option) {
                if (!option || !option.id) return;
                const element = $(select);
                element.find('option[value="' + String(option.id).replace(/"/g, '\\"') + '"]').remove();
                element.append(new Option(option.text || option.name || ('#' + option.id), option.id, true, true)).trigger('change.select2');
            }

            function collectFilters() {
                return {
                    search: $('#crmPipelineSearch').val(),
                    customer_id: $('#crmPipelineCustomer').val(),
                    assigned_user_id: $('#crmPipelineUser').val(),
                    priority: $('#crmPipelinePriority').val(),
                    source: $('#crmPipelineSource').val(),
                    follow_up_from: $('#crmPipelineFollowUpFrom').val(),
                    follow_up_to: $('#crmPipelineFollowUpTo').val()
                };
            }

            function renderCard(card) {
                boardCards[card.id] = card;
                const customer = card.customer
                    ? (card.customer.profile_url
                        ? '<a href="' + escapeAttr(card.customer.profile_url) + '">' + escapeHtml(card.customer.name || ('Customer #' + card.customer.id)) + '</a>'
                        : escapeHtml(card.customer.name || ('Customer #' + card.customer.id)))
                    : '-';
                const assigned = card.assigned_user ? escapeHtml(card.assigned_user.name || ('User #' + card.assigned_user.id)) : '-';
                const value = card.estimated_value == null || card.estimated_value === '' ? '-' : escapeHtml(card.estimated_value);
                const move = canUpdate && card.can_change_status
                    ? '<button type="button" class="btn btn-xs btn-outline-primary move-crm-pipeline-lead" data-id="' + escapeAttr(card.id) + '"><i class="feather-arrow-right-circle"></i> Move</button>'
                    : '';
                return '<div class="crm-pipeline-card">'
                    + '<div class="d-flex justify-content-between align-items-start">'
                    + '<div><div class="crm-pipeline-card-title">' + escapeHtml(card.name || ('Lead #' + card.id)) + '</div><small class="crm-pipeline-muted">' + escapeHtml(card.company_name || 'No company') + '</small></div>'
                    + '<span class="badge badge-light">#' + escapeHtml(card.id) + '</span></div>'
                    + '<div class="crm-pipeline-meta crm-pipeline-muted">'
                    + '<div><strong>Customer:</strong> ' + customer + '</div>'
                    + '<div><strong>Assigned:</strong> ' + assigned + '</div>'
                    + '<div><strong>Source:</strong> ' + escapeHtml(label(card.source)) + '</div>'
                    + '<div><strong>Priority:</strong> ' + escapeHtml(label(card.priority)) + '</div>'
                    + '<div><strong>Value:</strong> ' + value + '</div>'
                    + '<div><strong>Next follow-up:</strong> ' + escapeHtml(card.next_follow_up_at || '-') + '</div>'
                    + '</div>'
                    + (move ? '<div class="mt-2">' + move + '</div>' : '')
                    + '</div>';
            }

            function renderBoard(payload) {
                boardCards = {};
                const columns = payload.columns || [];
                let html = '';
                columns.forEach(function (column) {
                    const cards = (column.cards || []).map(renderCard).join('');
                    html += '<section class="crm-pipeline-column crm-pipeline-status-' + escapeAttr(column.status) + '">'
                        + '<div class="crm-pipeline-column-header d-flex justify-content-between align-items-center">'
                        + '<h5 class="crm-pipeline-column-title">' + escapeHtml(column.label) + '</h5>'
                        + '<span class="badge badge-secondary">' + escapeHtml(column.total) + '</span></div>'
                        + '<div class="crm-pipeline-column-body">' + (cards || '<div class="crm-pipeline-empty">No matching leads.</div>') + '</div>'
                        + (column.has_more ? '<div class="crm-pipeline-limit">Showing the first ' + escapeHtml(column.visible) + ' cards. Narrow the filters to load a smaller operational set.</div>' : '')
                        + '</section>';
                });
                $('#crmLeadPipelineBoard').html(html || '<div class="crm-pipeline-empty w-100">No pipeline columns are available.</div>');
                $('#crmLeadPipelineGeneratedAt').text(payload.meta && payload.meta.generated_at ? 'Generated: ' + payload.meta.generated_at : '');
            }

            function loadBoard() {
                const client = http();
                const currentSequence = ++loadSequence;
                if (!client) {
                    showFeedback('The shared window.AppAxios client is required to load the CRM lead pipeline.');
                    return;
                }
                hideFeedback();
                $('#crmLeadPipelineBoard').html('<div class="crm-pipeline-loading w-100">Loading CRM lead pipeline...</div>');
                $('#refreshCrmLeadPipeline').prop('disabled', true);
                client.get(routes.data, { params: collectFilters() }).then(function (response) {
                    if (currentSequence !== loadSequence) return;
                    renderBoard(response.data || {});
                }).catch(function (error) {
                    if (currentSequence !== loadSequence) return;
                    const validationErrors = error.response && error.response.data ? (error.response.data.errors || {}) : {};
                    const firstValidationGroup = Object.keys(validationErrors).length ? validationErrors[Object.keys(validationErrors)[0]] : [];
                    const message = error.response && error.response.status === 422
                        ? (firstValidationGroup[0] || 'Check the pipeline filters and try again.')
                        : 'Unable to load the CRM lead pipeline.';
                    showFeedback(message || 'Unable to load the CRM lead pipeline.');
                    $('#crmLeadPipelineBoard').html('<div class="crm-pipeline-empty w-100">Pipeline data could not be loaded.</div>');
                }).finally(function () {
                    if (currentSequence === loadSequence) $('#refreshCrmLeadPipeline').prop('disabled', false);
                });
            }

            function toggleStatusInputs() {
                const status = $('#crmPipelineStatus').val();
                $('#crmPipelineStatusCustomerWrap').toggleClass('d-none', status !== 'converted');
                $('#crmPipelineLostReasonWrap').toggleClass('d-none', status !== 'lost');
            }

            function openStatusModal(card) {
                const form = $('#crmPipelineStatusForm');
                form[0].reset();
                resetErrors(form);
                $('#crmPipelineLeadId').val(card.id);
                $('#crmPipelineLeadName').val(card.name || ('Lead #' + card.id));
                $('#crmPipelineStatus').val(card.status || 'new');
                $('#crmPipelineStatusCustomer').val(null).trigger('change.select2');
                if (card.customer) selectedOption('#crmPipelineStatusCustomer', card.customer);
                toggleStatusInputs();
                $('#crmPipelineStatusModal').modal('show');
            }

            initRemoteSelect('#crmPipelineCustomer', routes.customers, 'All customers');
            initRemoteSelect('#crmPipelineUser', routes.users, 'All assigned users');
            initRemoteSelect('#crmPipelineStatusCustomer', routes.customers, 'Select existing customer');
            if (prefillCustomer) selectedOption('#crmPipelineCustomer', prefillCustomer);

            $('#crmPipelineCustomer,#crmPipelineUser,#crmPipelinePriority,#crmPipelineSource,#crmPipelineFollowUpFrom,#crmPipelineFollowUpTo').on('change', function () {
                if (!filtersMuted) loadBoard();
            });
            $('#crmPipelineSearch').on('keydown', function (event) { if (event.key === 'Enter') { event.preventDefault(); loadBoard(); } });
            $('#refreshCrmLeadPipeline').on('click', loadBoard);
            $('#resetCrmLeadPipelineFilters').on('click', function () {
                filtersMuted = true;
                $('#crmPipelineSearch,#crmPipelinePriority,#crmPipelineSource,#crmPipelineFollowUpFrom,#crmPipelineFollowUpTo').val('');
                $('#crmPipelineCustomer,#crmPipelineUser').val(null).trigger('change.select2');
                filtersMuted = false;
                loadBoard();
            });
            $('#crmLeadPipelineBoard').on('click', '.move-crm-pipeline-lead', function () {
                const card = boardCards[$(this).data('id')];
                if (card) openStatusModal(card);
            });
            $('#crmPipelineStatus').on('change', toggleStatusInputs);
            $('#crmPipelineStatusForm').on('submit', function (event) {
                event.preventDefault();
                if (statusSubmitting) return;
                const form = $(this);
                const client = http();
                const leadId = $('#crmPipelineLeadId').val();
                const button = $('#saveCrmPipelineStatus');
                if (!client) {
                    notify('Axios unavailable', 'The shared window.AppAxios client is required.', 'error');
                    return;
                }
                resetErrors(form);
                statusSubmitting = true;
                setBusy(button, true);
                client.post(routes.leadBase + '/' + leadId + '/status', Object.fromEntries(new FormData(this).entries())).then(function (response) {
                    $('#crmPipelineStatusModal').modal('hide');
                    notify('Updated', response.data.message || 'CRM lead status updated.', 'success');
                    loadBoard();
                }).catch(function (error) {
                    if (error.response && error.response.status === 422) {
                        applyErrors(form, error.response.data.errors || {});
                    } else {
                        notify('Update failed', 'Unable to update the CRM lead status.', 'error');
                    }
                }).finally(function () {
                    statusSubmitting = false;
                    setBusy(button, false);
                });
            });

            loadBoard();
        })();
    </script>
@endsection
