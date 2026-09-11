@extends('backend.master')

@section('header_css')
    <link href="{{ url('dataTable') }}/css/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="{{ url('assets') }}/plugins/select2/select2.min.css" rel="stylesheet" type="text/css" />
    <style>
        .crm-segment-page { --crm-teal:#0f766e; --crm-border:#d7eeee; --crm-soft:#f0fdfa; }
        .crm-segment-card { border:1px solid var(--crm-border); border-radius:14px; box-shadow:0 6px 18px rgba(15,118,110,.05); }
        .crm-segment-filters { background:var(--crm-soft); border:1px solid var(--crm-border); border-radius:12px; }
        .crm-saved-segment-tools { background:#eef2ff; border:1px solid #e0e7ff; border-radius:12px; }
        .crm-segment-tag { display:inline-flex; align-items:center; padding:2px 7px; margin:1px 2px 1px 0; border:1px solid #dbeafe; border-radius:999px; background:#eff6ff; font-size:11px; }
        .crm-segment-tag.is-inactive { background:#f8fafc; border-color:#e2e8f0; color:#64748b; }
        #crmCustomerSegmentTable td { vertical-align:middle; }
        .select2-container { width:100% !important; }
    </style>
@endsection

@section('page_title')
    CRM Customer Portfolio Segments
@endsection

@section('page_heading')
    CRM Customer Portfolio Segments
@endsection

@section('content')
    <div class="crm-segment-page">
        <div class="card crm-segment-card">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
                    <div>
                        <h4 class="mb-1">Customer Portfolio Segmentation</h4>
                        <p class="text-muted mb-0">Read-only customer portfolio review using CRM tags and existing customer summary fields.</p>
                    </div>
                    <div class="mt-2 mt-md-0">
                        @if ($savedSegmentPermissions['can_view'])
                            <a href="{{ route('crm.saved-customer-segments.index') }}" class="btn btn-sm btn-outline-primary mr-1"><i class="feather-bookmark"></i> Saved Segments</a>
                        @endif
                        <span class="badge badge-light border">Read only</span>
                    </div>
                </div>

                <div id="crmSegmentValidation" class="alert alert-danger d-none" role="alert"></div>

                @if ($savedSegmentPermissions['can_view'])
                    <div class="crm-saved-segment-tools p-3 mb-3">
                        <div class="row align-items-end">
                            <div class="col-lg-5 col-md-7 mb-2">
                                <label for="crmSavedSegmentSelect">Apply saved segment</label>
                                <select id="crmSavedSegmentSelect" class="form-control"></select>
                            </div>
                            <div class="col-lg-2 col-md-5 mb-2">
                                <button type="button" id="applyCrmSavedSegment" class="btn btn-outline-primary btn-block"><i class="feather-filter"></i> Apply</button>
                            </div>
                            @if ($savedSegmentPermissions['can_create'])
                                <div class="col-lg-2 col-md-4 mb-2">
                                    <button type="button" id="saveCrmCurrentFilters" class="btn btn-primary btn-block"><i class="feather-save"></i> Save filters</button>
                                </div>
                            @endif
                            @if ($savedSegmentPermissions['can_update'])
                                <div class="col-lg-3 col-md-5 mb-2">
                                    <button type="button" id="updateCrmAppliedSegment" class="btn btn-outline-info btn-block d-none"><i class="feather-edit-2"></i> Update applied segment</button>
                                </div>
                            @endif
                        </div>
                        <small id="crmAppliedSegmentHint" class="text-muted">Saved definitions populate the filters below. Customer records remain read only.</small>
                    </div>
                @endif

                <div class="crm-segment-filters p-3 mb-3">
                    <div class="row">
                        <div class="col-lg-3 col-md-6 mb-2">
                            <label for="crmSegmentFilterCustomer">Customer</label>
                            <select id="crmSegmentFilterCustomer" class="form-control"></select>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-2">
                            <label for="crmSegmentFilterTags">CRM tags <small class="text-muted">(match any)</small></label>
                            <select id="crmSegmentFilterTags" class="form-control" multiple></select>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-2">
                            <label for="crmSegmentFilterAssignedUser">Assigned user</label>
                            <select id="crmSegmentFilterAssignedUser" class="form-control"></select>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-2">
                            <label for="crmSegmentFilterLifecycle">Lifecycle stage</label>
                            <select id="crmSegmentFilterLifecycle" class="form-control">
                                <option value="">All lifecycle stages</option>
                                @foreach ($filterOptions['lifecycle_stages'] as $stage)
                                    <option value="{{ $stage }}">{{ ucwords(str_replace('_', ' ', $stage)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-2">
                            <label for="crmSegmentFilterCredit">Credit status</label>
                            <select id="crmSegmentFilterCredit" class="form-control">
                                <option value="">All credit statuses</option>
                                @foreach ($filterOptions['credit_statuses'] as $status)
                                    <option value="{{ $status }}">{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-2">
                            <label for="crmSegmentFilterRisk">Risk bucket</label>
                            <select id="crmSegmentFilterRisk" class="form-control">
                                <option value="">All risk buckets</option>
                                <option value="overdue">Overdue amount</option>
                                <option value="due">Current due</option>
                                <option value="follow_up_due">Follow-up due</option>
                                <option value="duplicate">Duplicate candidate</option>
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-2">
                            <label for="crmSegmentFilterDuplicate">Duplicate candidate</label>
                            <select id="crmSegmentFilterDuplicate" class="form-control">
                                <option value="">All customers</option>
                                <option value="yes">Flagged only</option>
                                <option value="no">Not flagged</option>
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-2"><label for="crmSegmentContactFrom">Last contact from</label><input type="date" id="crmSegmentContactFrom" class="form-control"></div>
                        <div class="col-lg-3 col-md-6 mb-2"><label for="crmSegmentContactTo">Last contact to</label><input type="date" id="crmSegmentContactTo" class="form-control"></div>
                        <div class="col-lg-3 col-md-6 mb-2"><label for="crmSegmentFollowFrom">Follow-up from</label><input type="date" id="crmSegmentFollowFrom" class="form-control"></div>
                        <div class="col-lg-3 col-md-6 mb-2"><label for="crmSegmentFollowTo">Follow-up to</label><input type="date" id="crmSegmentFollowTo" class="form-control"></div>
                        <div class="col-lg-3 col-md-6 mb-2"><label for="crmSegmentOrderFrom">Last order from</label><input type="date" id="crmSegmentOrderFrom" class="form-control"></div>
                        <div class="col-lg-3 col-md-6 mb-2"><label for="crmSegmentOrderTo">Last order to</label><input type="date" id="crmSegmentOrderTo" class="form-control"></div>
                        <div class="col-lg-3 col-md-4 mb-2 d-flex align-items-end"><button type="button" id="resetCrmSegmentFilters" class="btn btn-outline-secondary btn-block">Reset filters</button></div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped mb-0" id="crmCustomerSegmentTable">
                        <thead><tr>
                            <th>Customer</th><th>Code</th><th>Tags</th><th>Assigned user</th><th>Lifecycle</th><th>Credit</th><th>Risk</th>
                            <th>Total order</th><th>Current due</th><th>Overdue</th><th>Last contact</th><th>Next follow-up</th><th>Last order</th><th>Duplicate</th><th>Action</th>
                        </tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @if ($savedSegmentPermissions['can_create'] || $savedSegmentPermissions['can_update'])
        <div class="modal fade" id="crmSavedSegmentFormModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form id="crmSavedSegmentForm">
                        <div class="modal-header">
                            <h5 class="modal-title" id="crmSavedSegmentFormTitle">Save Current Filters</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <div id="crmSavedSegmentFormErrors" class="alert alert-danger d-none"></div>
                            <div class="form-group">
                                <label for="crmSavedSegmentName">Segment name</label>
                                <input type="text" id="crmSavedSegmentName" class="form-control" maxlength="160" required>
                            </div>
                            <div class="form-group">
                                <label for="crmSavedSegmentDescription">Description</label>
                                <textarea id="crmSavedSegmentDescription" class="form-control" rows="3" maxlength="2000"></textarea>
                            </div>
                            <div class="form-group mb-0">
                                <label for="crmSavedSegmentVisibility">Visibility</label>
                                <select id="crmSavedSegmentVisibility" class="form-control">
                                    <option value="private">Private — only me</option>
                                    <option value="shared">Shared — reusable by permitted CRM users</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" id="crmSavedSegmentSubmit" class="btn btn-primary"><i class="feather-save"></i> Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection

@section('footer_js')
    <script src="{{ url('dataTable') }}/js/jquery.dataTables.min.js"></script>
    <script src="{{ url('dataTable') }}/js/dataTables.bootstrap4.min.js"></script>
    <script src="{{ url('assets') }}/plugins/select2/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @php
        $crmSegmentUrls = [
            'data' => route('crm.customer-segments.data'),
            'customers' => route('crm.customer-segments.options.customers'),
            'users' => route('crm.customer-segments.options.users'),
            'tags' => route('crm.customer-segments.options.tags'),
        ];
        if ($savedSegmentPermissions['can_view']) {
            $crmSegmentUrls['saved_options'] = route('crm.saved-customer-segments.options');
            $crmSegmentUrls['saved_apply'] = route('crm.saved-customer-segments.apply', ['segment' => '__SEGMENT__']);
        }
        if ($savedSegmentPermissions['can_create']) {
            $crmSegmentUrls['saved_store'] = route('crm.saved-customer-segments.store');
        }
        if ($savedSegmentPermissions['can_update']) {
            $crmSegmentUrls['saved_update'] = route('crm.saved-customer-segments.update', ['segment' => '__SEGMENT__']);
        }
    @endphp
    <script>
        (function ($) {
            'use strict';
            const urls = @json($crmSegmentUrls);
            const prefillCustomer = @json($prefillCustomer);
            const savedPermissions = @json($savedSegmentPermissions);
            const initialSavedSegmentId = Number(@json($savedSegmentId));
            const initialEditSavedSegmentId = Number(@json($editSavedSegmentId));
            let tableErrorVisible = false;
            let applyingSavedSegment = false;
            let savedSegmentBusy = false;
            let appliedSavedSegment = null;
            let modalMode = 'create';

            function client() { return window.AppAxios || null; }
            function esc(value) { return $('<div>').text(value === null || value === undefined || value === '' ? '—' : String(value)).html(); }
            function attr(value) { return esc(value).replace(/`/g, '&#96;'); }
            function notify(title, text, icon) {
                if (typeof Swal !== 'undefined') return Swal.fire({title:title, text:text || '', icon:icon || 'info', confirmButtonColor:'#0f766e'});
                window.alert((title ? title + ': ' : '') + (text || ''));
            }
            function badge(value) { return '<span class="badge badge-light border">' + esc(value || '—') + '</span>'; }
            function emptyTable(draw) { return {draw:Number(draw || 0), recordsTotal:0, recordsFiltered:0, data:[]}; }
            function validationText(payload) {
                const errors = payload && payload.errors ? payload.errors : {};
                const messages = [];
                Object.keys(errors).forEach(function (key) {
                    (Array.isArray(errors[key]) ? errors[key] : [errors[key]]).forEach(function (message) { messages.push(String(message)); });
                });
                return messages.join(' ');
            }
            function showValidation(message) {
                const alert = $('#crmSegmentValidation');
                if (message) alert.text(message).removeClass('d-none'); else alert.text('').addClass('d-none');
            }
            function showFormValidation(message) {
                const alert = $('#crmSavedSegmentFormErrors');
                if (message) alert.text(message).removeClass('d-none'); else alert.text('').addClass('d-none');
            }
            function transport(url) {
                return {delay:250, transport:function (params, success, failure) {
                    const http = client(); if (!http) { failure(); return {abort:function(){}}; }
                    const request = http.get(url, {params:params.data || {}}); request.then(function (response) { success(response.data); }).catch(failure); return {abort:function(){}};
                }, data:function (params) { return {q:params.term || ''}; }, processResults:function (data) { return data; }};
            }
            function initSelect(selector, url, placeholder, multiple) {
                const select = $(selector); if (!select.length || !url) return;
                if (select.hasClass('select2-hidden-accessible')) select.select2('destroy');
                select.select2({width:'100%', placeholder:placeholder, allowClear:true, multiple:!!multiple, ajax:transport(url)});
            }
            function setSelected(selector, option) {
                const select = $(selector); select.empty();
                if (option && option.id) select.append(new Option(option.text, option.id, true, true));
                select.trigger('change.select2');
            }
            function setSelectedMany(selector, options, fallbackIds) {
                const select = $(selector); select.empty();
                const values = Array.isArray(options) && options.length ? options : (Array.isArray(fallbackIds) ? fallbackIds.map(function (id) { return {id:id, text:'Tag #' + id}; }) : []);
                values.forEach(function (option) { if (option && option.id) select.append(new Option(option.text, option.id, true, true)); });
                select.trigger('change.select2');
            }
            function setPlainSelectValue(selector, value) {
                const select = $(selector); const normalized = value === null || value === undefined ? '' : String(value);
                if (normalized !== '' && !select.find('option').filter(function () { return $(this).val() === normalized; }).length) {
                    select.append(new Option(normalized.replace(/_/g, ' '), normalized, false, false));
                }
                select.val(normalized);
            }
            function renderTags(tags) {
                if (!Array.isArray(tags) || !tags.length) return '<span class="text-muted">—</span>';
                return tags.map(function (tag) {
                    const inactive = tag.status !== 'active';
                    return '<span class="crm-segment-tag' + (inactive ? ' is-inactive' : '') + '">' + esc(tag.name) + (inactive ? ' (inactive)' : '') + '</span>';
                }).join('');
            }
            function currentFilters() {
                return {
                    customer_id:$('#crmSegmentFilterCustomer').val() || null,
                    tag_ids:$('#crmSegmentFilterTags').val() || [],
                    assigned_user_id:$('#crmSegmentFilterAssignedUser').val() || null,
                    lifecycle_stage:$('#crmSegmentFilterLifecycle').val() || null,
                    credit_status:$('#crmSegmentFilterCredit').val() || null,
                    risk_bucket:$('#crmSegmentFilterRisk').val() || null,
                    duplicate_candidate:$('#crmSegmentFilterDuplicate').val() || null,
                    last_contact_from:$('#crmSegmentContactFrom').val() || null,
                    last_contact_to:$('#crmSegmentContactTo').val() || null,
                    follow_up_from:$('#crmSegmentFollowFrom').val() || null,
                    follow_up_to:$('#crmSegmentFollowTo').val() || null,
                    last_order_from:$('#crmSegmentOrderFrom').val() || null,
                    last_order_to:$('#crmSegmentOrderTo').val() || null
                };
            }
            function activeFilterCount(filters) {
                return Object.keys(filters || {}).filter(function (key) { const value = filters[key]; return Array.isArray(value) ? value.length > 0 : value !== null && value !== ''; }).length;
            }
            function savedApplyUrl(id) { return (urls.saved_apply || '').replace('__SEGMENT__', id); }
            function savedUpdateUrl(id) { return (urls.saved_update || '').replace('__SEGMENT__', id); }
            function selectSavedSegment(segment) {
                if (!segment || !segment.id || !$('#crmSavedSegmentSelect').length) return;
                setSelected('#crmSavedSegmentSelect', {id:segment.id, text:segment.name + (segment.visibility === 'private' ? ' (private)' : ' (shared)')});
            }
            function updateAppliedHint() {
                if (!$('#crmAppliedSegmentHint').length) return;
                if (!appliedSavedSegment) {
                    $('#crmAppliedSegmentHint').text('Saved definitions populate the filters below. Customer records remain read only.');
                    $('#updateCrmAppliedSegment').addClass('d-none');
                    return;
                }
                $('#crmAppliedSegmentHint').text('Applied: ' + appliedSavedSegment.name + '. You may adjust filters before saving or updating the definition.');
                $('#updateCrmAppliedSegment').toggleClass('d-none', !(savedPermissions.can_update && appliedSavedSegment.can_edit));
            }
            function applySavedFilters(segment) {
                const filters = segment.filters || {}; const selections = segment.filter_selections || {};
                applyingSavedSegment = true;
                setSelected('#crmSegmentFilterCustomer', selections.customer || (filters.customer_id ? {id:filters.customer_id, text:'Customer #' + filters.customer_id} : null));
                setSelected('#crmSegmentFilterAssignedUser', selections.assigned_user || (filters.assigned_user_id ? {id:filters.assigned_user_id, text:'User #' + filters.assigned_user_id} : null));
                setSelectedMany('#crmSegmentFilterTags', selections.tags || [], filters.tag_ids || []);
                setPlainSelectValue('#crmSegmentFilterLifecycle', filters.lifecycle_stage || '');
                setPlainSelectValue('#crmSegmentFilterCredit', filters.credit_status || '');
                $('#crmSegmentFilterRisk').val(filters.risk_bucket || '');
                $('#crmSegmentFilterDuplicate').val(filters.duplicate_candidate || '');
                $('#crmSegmentContactFrom').val(filters.last_contact_from || '');
                $('#crmSegmentContactTo').val(filters.last_contact_to || '');
                $('#crmSegmentFollowFrom').val(filters.follow_up_from || '');
                $('#crmSegmentFollowTo').val(filters.follow_up_to || '');
                $('#crmSegmentOrderFrom').val(filters.last_order_from || '');
                $('#crmSegmentOrderTo').val(filters.last_order_to || '');
                applyingSavedSegment = false;
                appliedSavedSegment = segment;
                selectSavedSegment(segment);
                updateAppliedHint();
                showValidation('');
                table.ajax.reload();
            }
            function loadSavedSegment(id, openEdit) {
                const http = client(); if (!http || !id || !urls.saved_apply) return notify('Saved segment unavailable', 'Unable to load the selected saved segment.', 'error');
                $('#applyCrmSavedSegment').prop('disabled', true);
                http.get(savedApplyUrl(id)).then(function (response) {
                    const segment = response.data.segment || {};
                    if (segment.status !== 'active') return notify('Saved segment unavailable', 'Archived saved segments cannot be applied.', 'warning');
                    if (!activeFilterCount(segment.filters || {})) return notify('Saved segment unavailable', 'This saved segment has no active portfolio filters.', 'warning');
                    applySavedFilters(segment);
                    if (openEdit && segment.can_edit) openSavedSegmentModal('update');
                }).catch(function (error) {
                    const payload = (error.response && error.response.data) || {}; notify('Saved segment load failed', validationText(payload) || payload.message || 'Unable to load the selected saved segment.', 'error');
                }).finally(function () { $('#applyCrmSavedSegment').prop('disabled', false); });
            }
            function openSavedSegmentModal(mode) {
                const filters = currentFilters();
                if (!activeFilterCount(filters)) return notify('Choose portfolio filters', 'Select at least one customer portfolio filter before saving a segment.', 'warning');
                if (mode === 'update' && (!appliedSavedSegment || !appliedSavedSegment.can_edit)) return notify('Update unavailable', 'Only the creator can update an active saved segment.', 'warning');
                modalMode = mode;
                showFormValidation('');
                $('#crmSavedSegmentFormTitle').text(mode === 'update' ? 'Update Saved Segment' : 'Save Current Filters');
                $('#crmSavedSegmentName').val(mode === 'update' ? (appliedSavedSegment.name || '') : '');
                $('#crmSavedSegmentDescription').val(mode === 'update' ? (appliedSavedSegment.description || '') : '');
                $('#crmSavedSegmentVisibility').val(mode === 'update' ? (appliedSavedSegment.visibility || 'private') : 'private');
                $('#crmSavedSegmentFormModal').modal('show');
            }

            initSelect('#crmSegmentFilterCustomer', urls.customers, 'All customers', false);
            initSelect('#crmSegmentFilterAssignedUser', urls.users, 'All assigned users', false);
            initSelect('#crmSegmentFilterTags', urls.tags, 'Any CRM tag', true);
            if (savedPermissions.can_view) initSelect('#crmSavedSegmentSelect', urls.saved_options, 'Select a saved segment', false);
            if (prefillCustomer && prefillCustomer.id) setSelected('#crmSegmentFilterCustomer', prefillCustomer);

            const table = $('#crmCustomerSegmentTable').DataTable({
                processing:true, serverSide:true, searching:true, ordering:false, pageLength:25,
                ajax:function (data, callback) {
                    const http = client();
                    showValidation('');
                    if (!http) { if (!tableErrorVisible) { tableErrorVisible = true; notify('Axios unavailable', 'The shared window.AppAxios client is required.', 'error'); } callback(emptyTable(data.draw)); return; }
                    Object.assign(data, currentFilters());
                    http.get(urls.data, {params:data}).then(function (response) { tableErrorVisible = false; callback(response.data); }).catch(function (error) {
                        const payload = (error.response && error.response.data) || {}; const details = validationText(payload); callback(emptyTable(data.draw));
                        if (error.response && error.response.status === 422) showValidation(details || payload.message || 'Please review the selected filters.');
                        if (!tableErrorVisible) { tableErrorVisible = true; notify('Customer segments load failed', details || payload.message || 'Unable to load CRM customer portfolio segments.', 'error'); }
                    });
                },
                columns:[
                    {data:'segment.name', render:function (value, type, row) { return row.segment.profile_url ? '<a href="' + attr(row.segment.profile_url) + '">' + esc(value) + '</a><br><small class="text-muted">' + esc(row.segment.phone || row.segment.email) + '</small>' : esc(value); }},
                    {data:'segment.customer_code', defaultContent:''},
                    {data:'segment.tags', render:function (value) { return renderTags(value); }},
                    {data:'segment.assigned_user', defaultContent:''},
                    {data:'segment.lifecycle_stage', render:function (value) { return badge(String(value || '').replace(/_/g, ' ')); }},
                    {data:'segment.credit_status', render:function (value) { return badge(String(value || '').replace(/_/g, ' ')); }},
                    {data:'segment.risk_label', render:function (value) { return badge(value); }},
                    {data:'segment.total_order_value'}, {data:'segment.current_due'}, {data:'segment.overdue_amount'},
                    {data:'segment.last_contact_at'}, {data:'segment.next_follow_up_at'}, {data:'segment.last_order_at'},
                    {data:'segment.is_duplicate_candidate', render:function (value) { return value ? '<span class="badge badge-warning">Flagged</span>' : '<span class="text-muted">—</span>'; }},
                    {data:'segment.profile_url', render:function (value) { return value ? '<a class="btn btn-xs btn-outline-primary" href="' + attr(value) + '"><i class="feather-eye"></i></a>' : '<span class="text-muted">—</span>'; }}
                ]
            });

            $('#crmSegmentFilterCustomer,#crmSegmentFilterTags,#crmSegmentFilterAssignedUser,#crmSegmentFilterLifecycle,#crmSegmentFilterCredit,#crmSegmentFilterRisk,#crmSegmentFilterDuplicate,#crmSegmentContactFrom,#crmSegmentContactTo,#crmSegmentFollowFrom,#crmSegmentFollowTo,#crmSegmentOrderFrom,#crmSegmentOrderTo').on('change', function () { if (!applyingSavedSegment) table.ajax.reload(); });
            $('#resetCrmSegmentFilters').on('click', function () {
                applyingSavedSegment = true;
                setSelected('#crmSegmentFilterCustomer'); setSelected('#crmSegmentFilterAssignedUser'); setSelectedMany('#crmSegmentFilterTags', [], []);
                $('#crmSegmentFilterLifecycle,#crmSegmentFilterCredit,#crmSegmentFilterRisk,#crmSegmentFilterDuplicate,#crmSegmentContactFrom,#crmSegmentContactTo,#crmSegmentFollowFrom,#crmSegmentFollowTo,#crmSegmentOrderFrom,#crmSegmentOrderTo').val('');
                if ($('#crmSavedSegmentSelect').length) setSelected('#crmSavedSegmentSelect');
                applyingSavedSegment = false; appliedSavedSegment = null; updateAppliedHint(); showValidation(''); table.ajax.reload();
            });
            $('#applyCrmSavedSegment').on('click', function () { const id = Number($('#crmSavedSegmentSelect').val()); if (!id) return notify('Select a saved segment', 'Choose a saved segment before applying it.', 'warning'); loadSavedSegment(id, false); });
            $('#saveCrmCurrentFilters').on('click', function () { openSavedSegmentModal('create'); });
            $('#updateCrmAppliedSegment').on('click', function () { openSavedSegmentModal('update'); });

            $('#crmSavedSegmentForm').on('submit', function (event) {
                event.preventDefault(); if (savedSegmentBusy) return;
                const http = client(); if (!http) return notify('Axios unavailable', 'The shared window.AppAxios client is required.', 'error');
                const payload = {name:$('#crmSavedSegmentName').val(), description:$('#crmSavedSegmentDescription').val(), visibility:$('#crmSavedSegmentVisibility').val(), filters:currentFilters()};
                const target = modalMode === 'update' && appliedSavedSegment ? savedUpdateUrl(appliedSavedSegment.id) : urls.saved_store;
                if (!target) return notify('Save unavailable', 'The requested saved segment action is not available.', 'error');
                savedSegmentBusy = true; showFormValidation(''); $('#crmSavedSegmentSubmit').prop('disabled', true);
                http.post(target, payload).then(function (response) {
                    appliedSavedSegment = response.data.segment || appliedSavedSegment; selectSavedSegment(appliedSavedSegment); updateAppliedHint(); $('#crmSavedSegmentFormModal').modal('hide'); notify('Saved', response.data.message || 'CRM saved segment stored successfully.', 'success');
                }).catch(function (error) {
                    const payload = (error.response && error.response.data) || {}; const details = validationText(payload) || payload.message || 'Unable to save the CRM segment.';
                    if (error.response && error.response.status === 422) showFormValidation(details); else notify('Save failed', details, 'error');
                }).finally(function () { savedSegmentBusy = false; $('#crmSavedSegmentSubmit').prop('disabled', false); });
            });

            if (initialSavedSegmentId > 0 && savedPermissions.can_view) loadSavedSegment(initialSavedSegmentId, initialEditSavedSegmentId === initialSavedSegmentId);
        })(jQuery);
    </script>
@endsection
