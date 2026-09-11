@extends('backend.master')

@section('header_css')
    <link href="{{ url('dataTable') }}/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="{{ url('dataTable') }}/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link href="{{ url('assets') }}/plugins/select2/select2.min.css" rel="stylesheet" type="text/css" />
    <style>
        .crm-task-card { border:1px solid #d7eeee; border-radius:14px; box-shadow:0 8px 20px rgba(15,118,110,.05); }
        .crm-task-card .card-title { color:#0f766e; font-weight:800; }
        .crm-task-filter { background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:14px; }
        .crm-task-overdue { background:#fff7ed !important; }
        .crm-task-muted { color:#64748b; font-size:12px; }
        .select2-container { width:100% !important; }
        .dataTables_wrapper .dataTables_paginate .paginate_button { padding:0; }
        table.dataTable tbody td { vertical-align:middle; }
    </style>
@endsection

@section('page_title') CRM Tasks @endsection
@section('page_heading') CRM Tasks @endsection

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card crm-task-card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                        <div>
                            <h4 class="card-title mb-1">CRM Task and Follow-up Worklist</h4>
                            <small class="text-muted">Manage customer follow-ups without replacing legacy scheduled-contact records.</small>
                        </div>
                        <div>
                            @if ($permissions['can_view_calendar'])
                                <a href="{{ route('crm.tasks.calendar', request()->only('customer_id')) }}" class="btn btn-sm btn-outline-primary mr-1"><i class="feather-calendar"></i> Task Calendar</a>
                            @endif
                            @if ($permissions['can_create'])
                                <button type="button" class="btn btn-sm btn-primary" id="openCreateCrmTask"><i class="feather-plus-circle"></i> Add CRM Task</button>
                            @endif
                        </div>
                    </div>

                    <div class="crm-task-filter mb-3">
                        <div class="row">
                            <div class="col-lg-3 col-md-6 mb-2">
                                <label for="crmTaskFilterCustomer">Customer</label>
                                <select id="crmTaskFilterCustomer" class="form-control"></select>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-2">
                                <label for="crmTaskFilterUser">Assigned user</label>
                                <select id="crmTaskFilterUser" class="form-control"></select>
                            </div>
                            <div class="col-lg-2 col-md-4 mb-2">
                                <label for="crmTaskFilterStatus">Status</label>
                                <select id="crmTaskFilterStatus" class="form-control">
                                    <option value="">All statuses</option>
                                    <option value="overdue">Overdue</option>
                                    <option value="pending">Pending</option>
                                    <option value="in_progress">In progress</option>
                                    <option value="completed">Completed</option>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-4 mb-2">
                                <label for="crmTaskFilterPriority">Priority</label>
                                <select id="crmTaskFilterPriority" class="form-control">
                                    <option value="">All priorities</option>
                                    <option value="low">Low</option>
                                    <option value="normal">Normal</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-4 mb-2 d-flex align-items-end">
                                <button type="button" id="resetCrmTaskFilters" class="btn btn-outline-secondary btn-block">Reset filters</button>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-2">
                                <label for="crmTaskDueFrom">Due from</label>
                                <input type="date" id="crmTaskDueFrom" class="form-control">
                            </div>
                            <div class="col-lg-3 col-md-6 mb-2">
                                <label for="crmTaskDueTo">Due to</label>
                                <input type="date" id="crmTaskDueTo" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped mb-0" id="crmTasksTable">
                            <thead><tr>
                                <th>Task</th><th>Customer</th><th>Assigned user</th><th>Priority</th><th>Status</th>
                                <th>Due date</th><th>Completed at</th><th>Created by</th><th>Created at</th><th>Actions</th>
                            </tr></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="crmTaskModal" tabindex="-1" role="dialog" aria-labelledby="crmTaskModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <form id="crmTaskForm" novalidate>
                    <div class="modal-header">
                        <h5 class="modal-title" id="crmTaskModalTitle">Add CRM Task</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="crmTaskId">
                        <div class="row">
                            <div class="col-md-6"><div class="form-group">
                                <label for="crmTaskCustomer">Customer</label>
                                <select id="crmTaskCustomer" name="customer_id" class="form-control"></select>
                                <div class="invalid-feedback" id="error-customer_id"></div>
                            </div></div>
                            <div class="col-md-6"><div class="form-group">
                                <label for="crmTaskAssignedUser">Assigned user</label>
                                <select id="crmTaskAssignedUser" name="assigned_user_id" class="form-control"></select>
                                <div class="invalid-feedback" id="error-assigned_user_id"></div>
                            </div></div>
                        </div>
                        <div class="form-group">
                            <label for="crmTaskTitle">Title <span class="text-danger">*</span></label>
                            <input type="text" id="crmTaskTitle" name="title" class="form-control" maxlength="255" required>
                            <div class="invalid-feedback" id="error-title"></div>
                        </div>
                        <div class="form-group">
                            <label for="crmTaskDescription">Description</label>
                            <textarea id="crmTaskDescription" name="description" class="form-control" rows="4" maxlength="5000"></textarea>
                            <div class="invalid-feedback" id="error-description"></div>
                        </div>
                        <div class="row">
                            <div class="col-md-4"><div class="form-group">
                                <label for="crmTaskPriority">Priority</label>
                                <select id="crmTaskPriority" name="priority" class="form-control">
                                    <option value="">Not set</option><option value="low">Low</option><option value="normal">Normal</option><option value="high">High</option><option value="urgent">Urgent</option>
                                </select><div class="invalid-feedback" id="error-priority"></div>
                            </div></div>
                            <div class="col-md-4"><div class="form-group">
                                <label for="crmTaskStatus">Status <span class="text-danger">*</span></label>
                                <select id="crmTaskStatus" name="status" class="form-control">
                                    <option value="pending">Pending</option><option value="in_progress">In progress</option>
                                </select><div class="invalid-feedback" id="error-status"></div>
                            </div></div>
                            <div class="col-md-4"><div class="form-group">
                                <label for="crmTaskDueAt">Due date</label>
                                <input type="datetime-local" id="crmTaskDueAt" name="due_at" class="form-control">
                                <div class="invalid-feedback" id="error-due_at"></div>
                            </div></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="saveCrmTask"><span class="js-save-task-label">Save Task</span></button>
                    </div>
                </form>
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
        $crmTaskUrls = [
            'data' => route('crm.tasks.data'),
            'store' => route('crm.tasks.store'),
            'update' => route('crm.tasks.update', ['task' => '__TASK__']),
            'complete' => route('crm.tasks.complete', ['task' => '__TASK__']),
            'archive' => route('crm.tasks.archive', ['task' => '__TASK__']),
            'customers' => route('crm.tasks.options.customers'),
            'users' => route('crm.tasks.options.users'),
        ];
    @endphp
    <script>
        (function ($) {
            'use strict';
            const urls = @json($crmTaskUrls);
            const permissions = @json($permissions);
            const prefillCustomer = @json($prefillCustomer);
            const shouldOpenCreate = @json($openCreateModal);
            const rowCache = {};
            const completionRequests = new Set();
            const archiveRequests = new Set();
            let taskSubmitting = false;
            let tableErrorVisible = false;
            let filtersMuted = false;

            function client() { return window.AppAxios || null; }
            function emptyTable(draw) { return {draw:Number(draw || 0), recordsTotal:0, recordsFiltered:0, data:[]}; }
            function endpoint(template, id) { return String(template || '').replace('__TASK__', String(id)); }
            function esc(value) { return $('<div>').text(value === null || value === undefined || value === '' ? '—' : String(value)).html(); }
            function attr(value) { return esc(value).replace(/`/g, '&#96;'); }
            function notify(title, text, icon) { return Swal.fire({title:title, text:text || '', icon:icon || 'info', confirmButtonColor:'#0f766e'}); }
            function firstError(payload) { const errors = payload.errors || {}; const key = Object.keys(errors)[0]; return key && Array.isArray(errors[key]) ? errors[key][0] : null; }
            function badge(text, css) { return '<span class="badge ' + attr(css || 'badge-light border') + '">' + esc(text) + '</span>'; }
            function statusBadge(task) {
                if (task.is_overdue) return badge('Overdue', 'badge-danger');
                if (task.status === 'completed') return badge('Completed', 'badge-success');
                if (task.status === 'in_progress') return badge('In progress', 'badge-info');
                return badge('Pending', 'badge-warning');
            }
            function priorityBadge(value) {
                const map = {urgent:'badge-danger', high:'badge-warning', normal:'badge-info', low:'badge-light border'};
                return badge(value || 'Not set', map[value] || 'badge-light border');
            }
            function actions(task) {
                let html = '';
                if (task.can_edit) html += '<button type="button" class="btn btn-xs btn-outline-primary js-edit-task mr-1" data-id="' + Number(task.id) + '"><i class="feather-edit-2"></i></button>';
                if (task.can_complete) html += '<button type="button" class="btn btn-xs btn-outline-success js-complete-task mr-1" data-id="' + Number(task.id) + '"><i class="feather-check-circle"></i></button>';
                if (task.can_archive) html += '<button type="button" class="btn btn-xs btn-outline-danger js-archive-task" data-id="' + Number(task.id) + '"><i class="feather-archive"></i></button>';
                return html || '<span class="text-muted">—</span>';
            }

            function transport(url) {
                return {
                    delay:250,
                    transport:function (params, success, failure) {
                        const http = client(); if (!http) { failure(); return {abort:function(){}}; }
                        http.get(url, {params:params.data || {}}).then(function (response) { success(response.data); }).catch(failure);
                        return {abort:function(){}};
                    },
                    data:function (params) { return {q:params.term || ''}; }, processResults:function (data) { return data; }
                };
            }
            function initSelect(selector, url, placeholder, allowClear) {
                const select = $(selector);
                if (select.hasClass('select2-hidden-accessible')) select.select2('destroy');
                select.select2({width:'100%', placeholder:placeholder, allowClear:allowClear !== false, ajax:transport(url)});
            }
            function setSelected(selector, id, text) {
                const select = $(selector); select.empty();
                if (id) select.append(new Option(text || ('#' + id), String(id), true, true)).trigger('change');
                else select.val(null).trigger('change');
            }
            initSelect('#crmTaskFilterCustomer', urls.customers, 'All customers');
            initSelect('#crmTaskFilterUser', urls.users, 'All users');
            initSelect('#crmTaskCustomer', urls.customers, 'Select customer');
            initSelect('#crmTaskAssignedUser', urls.users, 'Select assigned user');

            const table = $('#crmTasksTable').DataTable({
                processing:true, serverSide:true, pageLength:25,
                ajax:function (data, callback) {
                    const http = client();
                    data.customer_id = $('#crmTaskFilterCustomer').val(); data.assigned_user_id = $('#crmTaskFilterUser').val();
                    data.status = $('#crmTaskFilterStatus').val(); data.priority = $('#crmTaskFilterPriority').val();
                    data.due_from = $('#crmTaskDueFrom').val(); data.due_to = $('#crmTaskDueTo').val();
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
                        if (!tableErrorVisible) { tableErrorVisible = true; notify('Task list load failed', firstError(payload) || payload.message || 'Unable to load CRM tasks.', 'error'); }
                    });
                },
                createdRow:function (row, data) { if (data.task && data.task.is_overdue) $(row).addClass('crm-task-overdue'); },
                columns:[
                    {data:'task.title', name:'title', render:function (value, type, row) { rowCache[String(row.task.id)] = row.task; return '<strong>' + esc(value) + '</strong>' + (row.task.description ? '<div class="crm-task-muted">' + esc(row.task.description) + '</div>' : ''); }},
                    {data:'task.customer', name:'customer_id', orderable:false, searchable:false, render:function (value, type, row) { return row.task.customer_profile_url ? '<a href="' + attr(row.task.customer_profile_url) + '">' + esc(value) + '</a>' : esc(value); }},
                    {data:'task.assigned_user', name:'assigned_user_id', orderable:false, searchable:false, defaultContent:''},
                    {data:'task.priority', name:'priority', render:function (value) { return priorityBadge(value); }},
                    {data:'task.status', name:'status', render:function (value, type, row) { return statusBadge(row.task); }},
                    {data:'task.due_at', name:'due_at', defaultContent:''}, {data:'task.completed_at', name:'completed_at', defaultContent:''},
                    {data:'task.creator', name:'created_by', orderable:false, searchable:false, defaultContent:''}, {data:'task.created_at', name:'created_at'},
                    {data:'task.id', name:'id', orderable:false, searchable:false, render:function (value, type, row) { return actions(row.task); }}
                ]
            });
            $('#crmTaskFilterCustomer,#crmTaskFilterUser,#crmTaskFilterStatus,#crmTaskFilterPriority,#crmTaskDueFrom,#crmTaskDueTo').on('change', function () { if (!filtersMuted) table.ajax.reload(); });
            $('#resetCrmTaskFilters').on('click', function () {
                filtersMuted = true;
                setSelected('#crmTaskFilterCustomer'); setSelected('#crmTaskFilterUser'); $('#crmTaskFilterStatus,#crmTaskFilterPriority,#crmTaskDueFrom,#crmTaskDueTo').val('');
                filtersMuted = false;
                table.ajax.reload();
            });

            function resetErrors() { $('#crmTaskForm .is-invalid').removeClass('is-invalid'); $('#crmTaskForm .invalid-feedback').text(''); }
            function applyErrors(errors) { Object.keys(errors || {}).forEach(function (key) { const field = key.split('.')[0], input = $('#crmTaskForm [name="' + field + '"]'); input.addClass('is-invalid'); $('#error-' + field).text(Array.isArray(errors[key]) ? errors[key][0] : errors[key]); }); }
            function taskLoading(state) { taskSubmitting = state; $('#saveCrmTask').prop('disabled', state); $('.js-save-task-label').text(state ? 'Saving...' : 'Save Task'); }
            function resetForm(usePrefill) {
                document.getElementById('crmTaskForm').reset(); $('#crmTaskId').val(''); resetErrors(); taskLoading(false); $('#crmTaskModalTitle').text('Add CRM Task');
                setSelected('#crmTaskCustomer'); setSelected('#crmTaskAssignedUser');
                if (usePrefill && prefillCustomer) setSelected('#crmTaskCustomer', prefillCustomer.id, prefillCustomer.text);
            }
            $('#openCreateCrmTask').on('click', function () { resetForm(true); $('#crmTaskModal').modal('show'); });
            $('#crmTasksTable').on('click', '.js-edit-task', function () {
                const task = rowCache[String($(this).data('id'))]; if (!task) return;
                resetForm(false); $('#crmTaskId').val(task.id); $('#crmTaskTitle').val(task.title || ''); $('#crmTaskDescription').val(task.description || '');
                $('#crmTaskPriority').val(task.priority || ''); $('#crmTaskStatus').val(task.status || 'pending'); $('#crmTaskDueAt').val(task.due_at_input || '');
                setSelected('#crmTaskCustomer', task.customer_id, task.customer); setSelected('#crmTaskAssignedUser', task.assigned_user_id, task.assigned_user);
                $('#crmTaskModalTitle').text('Edit CRM Task'); $('#crmTaskModal').modal('show');
            });
            $('#crmTaskForm').on('submit', function (event) {
                event.preventDefault(); if (taskSubmitting) return;
                const http = client(); if (!http) { notify('Axios unavailable', 'The shared window.AppAxios client is required.', 'error'); return; }
                resetErrors(); taskLoading(true); const id = $('#crmTaskId').val();
                const payload = {customer_id:$('#crmTaskCustomer').val(), assigned_user_id:$('#crmTaskAssignedUser').val(), title:$('#crmTaskTitle').val(), description:$('#crmTaskDescription').val(), priority:$('#crmTaskPriority').val(), status:$('#crmTaskStatus').val(), due_at:$('#crmTaskDueAt').val()};
                http.post(id ? endpoint(urls.update, id) : urls.store, payload).then(function (response) { $('#crmTaskModal').modal('hide'); table.ajax.reload(null, false); notify('Saved', response.data.message || 'CRM task saved successfully.', 'success'); })
                    .catch(function (error) { const response = error.response || {}, data = response.data || {}; if (response.status === 422 && data.errors) applyErrors(data.errors); notify('Save failed', data.message || 'Unable to save the CRM task.', 'error'); })
                    .finally(function () { taskLoading(false); });
            });
            $('#crmTasksTable').on('click', '.js-complete-task', function () {
                const button = $(this), id = String(button.data('id')); if (completionRequests.has(id)) return;
                Swal.fire({title:'Complete CRM task?', text:'Add an optional completion note.', input:'textarea', inputPlaceholder:'Completion note', icon:'question', showCancelButton:true, confirmButtonText:'Yes, complete', confirmButtonColor:'#0f766e'})
                    .then(function (result) { if (!result.isConfirmed) return; const http = client(); if (!http) return notify('Axios unavailable', 'The shared window.AppAxios client is required.', 'error'); completionRequests.add(id); button.prop('disabled', true);
                        http.post(endpoint(urls.complete, id), {completion_note:result.value || null}).then(function (response) { table.ajax.reload(null, false); notify('Completed', response.data.message || 'CRM task completed.', 'success'); })
                            .catch(function (error) { const data = (error.response && error.response.data) || {}; notify('Completion failed', data.message || 'Unable to complete the CRM task.', 'error'); }).finally(function () { completionRequests.delete(id); button.prop('disabled', false); }); });
            });
            $('#crmTasksTable').on('click', '.js-archive-task', function () {
                const button = $(this), id = String(button.data('id')); if (archiveRequests.has(id)) return;
                Swal.fire({title:'Archive CRM task?', text:'The task will be softly archived. No task record will be hard deleted.', icon:'warning', showCancelButton:true, confirmButtonText:'Yes, archive', confirmButtonColor:'#0f766e'})
                    .then(function (result) { if (!result.isConfirmed) return; const http = client(); if (!http) return notify('Axios unavailable', 'The shared window.AppAxios client is required.', 'error'); archiveRequests.add(id); button.prop('disabled', true);
                        http.post(endpoint(urls.archive, id), {}).then(function (response) { table.ajax.reload(null, false); notify('Archived', response.data.message || 'CRM task archived.', 'success'); })
                            .catch(function (error) { const data = (error.response && error.response.data) || {}; notify('Archive failed', data.message || 'Unable to archive the CRM task.', 'error'); }).finally(function () { archiveRequests.delete(id); button.prop('disabled', false); }); });
            });
            $('#crmTaskModal').on('hidden.bs.modal', function () { resetForm(false); });
            if (shouldOpenCreate && permissions.can_create) { resetForm(true); $('#crmTaskModal').modal('show'); }
        })(jQuery);
    </script>
@endsection
