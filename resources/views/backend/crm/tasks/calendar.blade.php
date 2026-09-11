@extends('backend.master')

@section('header_css')
    <link href="{{ url('assets') }}/plugins/select2/select2.min.css" rel="stylesheet" type="text/css" />
    <style>
        .crm-calendar-card { border:1px solid #d7eeee; border-radius:14px; box-shadow:0 8px 20px rgba(15,118,110,.05); }
        .crm-calendar-card .card-title { color:#0f766e; font-weight:800; }
        .crm-calendar-filter { background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:14px; }
        .crm-calendar-toolbar { gap:8px; }
        .crm-calendar-wrap { overflow-x:auto; border:1px solid #e2e8f0; border-radius:12px; background:#fff; }
        .crm-calendar-grid { display:grid; grid-template-columns:repeat(7,minmax(150px,1fr)); min-width:1120px; transition:opacity .2s ease; }
        .crm-calendar-grid.is-loading { opacity:.55; }
        .crm-calendar-weekday { padding:10px; text-align:center; background:#0f766e; color:#fff; font-size:12px; font-weight:800; letter-spacing:.04em; text-transform:uppercase; }
        .crm-calendar-day { min-height:172px; padding:8px; border-right:1px solid #e2e8f0; border-bottom:1px solid #e2e8f0; background:#fff; }
        .crm-calendar-day:nth-child(7n) { border-right:0; }
        .crm-calendar-day.is-outside { background:#f8fafc; }
        .crm-calendar-day.is-today { box-shadow:inset 0 0 0 2px #0f766e; }
        .crm-calendar-day-number { color:#334155; font-size:12px; font-weight:800; }
        .crm-calendar-day.is-outside .crm-calendar-day-number { color:#94a3b8; }
        .crm-calendar-event { margin-top:7px; padding:7px; border:1px solid #dbeafe; border-left:4px solid #0f766e; border-radius:8px; background:#f8fafc; font-size:11px; line-height:1.35; }
        .crm-calendar-event.is-overdue { border-left-color:#dc2626; background:#fff7ed; }
        .crm-calendar-event.is-completed { border-left-color:#64748b; background:#f1f5f9; }
        .crm-calendar-event-title { color:#0f172a; font-weight:800; overflow-wrap:anywhere; }
        .crm-calendar-muted { color:#64748b; }
        .crm-calendar-badges { display:flex; flex-wrap:wrap; gap:4px; margin-top:5px; }
        .crm-calendar-badges .badge { font-size:10px; }
        .crm-calendar-empty { color:#94a3b8; font-size:12px; padding-top:8px; }
        .crm-calendar-loading { padding:18px; color:#0f766e; font-weight:700; }
        .crm-calendar-meta { color:#64748b; font-size:12px; }
        .select2-container { width:100% !important; }
        .select2-selection.is-invalid { border-color:#dc3545 !important; }
    </style>
@endsection

@section('page_title') CRM Task Calendar @endsection
@section('page_heading') CRM Task Calendar @endsection

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card crm-calendar-card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                        <div>
                            <h4 class="card-title mb-1">Read-only CRM Task Calendar</h4>
                            <small class="text-muted">Review existing CRM task due dates without creating parallel write flows or changing legacy scheduled-contact records.</small>
                        </div>
                        <div class="mt-2 mt-md-0">
                            @if ($permissions['can_view_worklist'])
                                <a href="{{ route('crm.tasks.index') }}" class="btn btn-sm btn-outline-primary mr-1"><i class="feather-list"></i> Task Worklist</a>
                            @endif
                            @if ($permissions['can_view_leads'])
                                <a href="{{ route('crm.leads.index') }}" class="btn btn-sm btn-outline-secondary mr-1"><i class="feather-target"></i> Lead Worklist</a>
                            @endif
                            <a href="{{ route('crm.home') }}" class="btn btn-sm btn-light"><i class="feather-home"></i> CRM Dashboard</a>
                        </div>
                    </div>

                    <div class="crm-calendar-filter mb-3">
                        <div class="row">
                            <div class="col-lg-3 col-md-6 mb-2">
                                <label for="crmTaskCalendarCustomer">Customer</label>
                                <select id="crmTaskCalendarCustomer" class="form-control"></select>
                                <div class="invalid-feedback" id="error-calendar-customer_id"></div>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-2">
                                <label for="crmTaskCalendarUser">Assigned user</label>
                                <select id="crmTaskCalendarUser" class="form-control"></select>
                                <div class="invalid-feedback" id="error-calendar-assigned_user_id"></div>
                            </div>
                            <div class="col-lg-2 col-md-4 mb-2">
                                <label for="crmTaskCalendarStatus">Status</label>
                                <select id="crmTaskCalendarStatus" class="form-control">
                                    <option value="">All statuses</option>
                                    <option value="overdue">Overdue</option>
                                    <option value="pending">Pending</option>
                                    <option value="in_progress">In progress</option>
                                    <option value="completed">Completed</option>
                                </select>
                                <div class="invalid-feedback" id="error-calendar-status"></div>
                            </div>
                            <div class="col-lg-2 col-md-4 mb-2">
                                <label for="crmTaskCalendarPriority">Priority</label>
                                <select id="crmTaskCalendarPriority" class="form-control">
                                    <option value="">All priorities</option>
                                    <option value="low">Low</option>
                                    <option value="normal">Normal</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                                <div class="invalid-feedback" id="error-calendar-priority"></div>
                            </div>
                            <div class="col-lg-2 col-md-4 mb-2 d-flex align-items-end">
                                <button type="button" id="resetCrmTaskCalendarFilters" class="btn btn-outline-secondary btn-block">Reset filters</button>
                            </div>
                        </div>
                    </div>

                    <div id="crmTaskCalendarFeedback" class="alert alert-danger d-none" role="alert"></div>
                    <div id="crmTaskCalendarLimit" class="alert alert-warning d-none" role="alert"></div>

                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-2 crm-calendar-toolbar">
                        <div>
                            <button type="button" id="previousCrmTaskCalendar" class="btn btn-sm btn-outline-secondary"><i class="feather-chevron-left"></i> Previous</button>
                            <button type="button" id="todayCrmTaskCalendar" class="btn btn-sm btn-outline-secondary">Today</button>
                            <button type="button" id="nextCrmTaskCalendar" class="btn btn-sm btn-outline-secondary">Next <i class="feather-chevron-right"></i></button>
                        </div>
                        <h5 id="crmTaskCalendarTitle" class="mb-0 text-center"></h5>
                        <div>
                            <button type="button" id="refreshCrmTaskCalendar" class="btn btn-sm btn-primary"><i class="feather-refresh-cw"></i> <span class="js-calendar-refresh-label">Refresh</span></button>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-2 crm-calendar-meta">
                        <span id="crmTaskCalendarSummary">Loading CRM task calendar...</span>
                        <span id="crmTaskCalendarGeneratedAt"></span>
                    </div>

                    <div class="crm-calendar-wrap">
                        <div id="crmTaskCalendarGrid" class="crm-calendar-grid">
                            <div class="crm-calendar-loading">Loading CRM task calendar...</div>
                        </div>
                    </div>

                    <div class="crm-calendar-meta mt-3">
                        <span class="badge badge-danger mr-1">Overdue</span>
                        <span class="badge badge-secondary mr-1">Completed</span>
                        Calendar cards are read-only. Use the existing Task Worklist for permitted task actions.
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('footer_js')
    <script src="{{ url('assets') }}/plugins/select2/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @php
        $crmTaskCalendarUrls = [
            'data' => route('crm.tasks.calendar.data'),
            'customers' => route('crm.tasks.calendar.options.customers'),
            'users' => route('crm.tasks.calendar.options.users'),
        ];
    @endphp
    <script>
        (function ($) {
            'use strict';

            const routes = @json($crmTaskCalendarUrls);
            const permissions = @json($permissions);
            const prefillCustomer = @json($prefillCustomer);
            const initialMonth = @json($initialMonth);
            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
            const weekDays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            let currentMonth = parseMonth(initialMonth);
            let loadSequence = 0;
            let filtersMuted = false;

            function http() { return window.AppAxios || null; }
            function escapeHtml(value) { return $('<div>').text(value == null ? '' : String(value)).html(); }
            function escapeAttr(value) { return escapeHtml(value).replace(/`/g, '&#96;'); }
            function label(value) { return String(value || '-').replace(/_/g, ' ').replace(/\b\w/g, function (letter) { return letter.toUpperCase(); }); }
            function pad(value) { return String(value).padStart(2, '0'); }
            function dateKey(date) { return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate()); }
            function cloneDate(date) { return new Date(date.getFullYear(), date.getMonth(), date.getDate()); }
            function parseMonth(value) {
                const parts = String(value || '').split('-');
                const year = parseInt(parts[0], 10), month = parseInt(parts[1], 10);
                return new Date(Number.isFinite(year) ? year : new Date().getFullYear(), Number.isFinite(month) ? month - 1 : new Date().getMonth(), 1);
            }
            function monthStart() { return new Date(currentMonth.getFullYear(), currentMonth.getMonth(), 1); }
            function monthEnd() { return new Date(currentMonth.getFullYear(), currentMonth.getMonth() + 1, 0); }
            function gridStart() { const date = monthStart(); date.setDate(date.getDate() - date.getDay()); return date; }
            function gridEnd() { const date = monthEnd(); date.setDate(date.getDate() + (6 - date.getDay())); return date; }

            function notify(title, text, icon) {
                if (window.Swal) Swal.fire({ title:title, text:text, icon:icon || 'info' });
            }

            function selectTransport(url) {
                return {
                    transport:function (params, success, failure) {
                        const client = http();
                        if (!client) { failure(); return { abort:function () {} }; }
                        client.get(url, { params:params.data || {} }).then(function (response) {
                            success(response.data);
                        }).catch(failure);
                        return { abort:function () {} };
                    },
                    data:function (params) { return { q:params.term || '' }; },
                    processResults:function (data) { return data; }
                };
            }

            function initRemoteSelect(selector, url, placeholder) {
                $(selector).select2({ width:'100%', allowClear:true, placeholder:placeholder, ajax:selectTransport(url) });
            }

            function selectedOption(selector, option) {
                const select = $(selector);
                select.empty();
                if (option && option.id) select.append(new Option(option.text || ('#' + option.id), String(option.id), true, true));
                select.trigger('change.select2');
            }

            function resetFilterErrors() {
                $('#crmTaskCalendarCustomer,#crmTaskCalendarUser,#crmTaskCalendarStatus,#crmTaskCalendarPriority').removeClass('is-invalid');
                $('.select2-selection').removeClass('is-invalid');
                $('[id^="error-calendar-"]').text('');
            }

            function applyFilterErrors(errors) {
                const fields = {
                    customer_id:'#crmTaskCalendarCustomer', assigned_user_id:'#crmTaskCalendarUser',
                    status:'#crmTaskCalendarStatus', priority:'#crmTaskCalendarPriority'
                };
                Object.keys(errors || {}).forEach(function (key) {
                    const field = key.split('.')[0], selector = fields[field];
                    if (selector) {
                        $(selector).addClass('is-invalid');
                        if ($(selector).hasClass('select2-hidden-accessible')) $(selector).next('.select2-container').find('.select2-selection').addClass('is-invalid');
                    }
                    $('#error-calendar-' + field).text(Array.isArray(errors[key]) ? errors[key][0] : errors[key]);
                });
            }

            function showFeedback(message) { $('#crmTaskCalendarFeedback').text(message).removeClass('d-none'); }
            function hideFeedback() { $('#crmTaskCalendarFeedback').addClass('d-none').text(''); }
            function setLoading(state) {
                $('#previousCrmTaskCalendar,#todayCrmTaskCalendar,#nextCrmTaskCalendar,#refreshCrmTaskCalendar,#resetCrmTaskCalendarFilters').prop('disabled', state);
                $('.js-calendar-refresh-label').text(state ? 'Loading...' : 'Refresh');
                $('#crmTaskCalendarGrid').toggleClass('is-loading', state);
            }

            function collectFilters() {
                return {
                    start:dateKey(gridStart()), end:dateKey(gridEnd()),
                    customer_id:$('#crmTaskCalendarCustomer').val(), assigned_user_id:$('#crmTaskCalendarUser').val(),
                    status:$('#crmTaskCalendarStatus').val(), priority:$('#crmTaskCalendarPriority').val()
                };
            }

            function badge(value, badgeClass) { return '<span class="badge ' + badgeClass + '">' + escapeHtml(label(value)) + '</span>'; }
            function priorityBadge(priority) {
                const classes = { urgent:'badge-danger', high:'badge-warning', normal:'badge-info', low:'badge-light' };
                return badge(priority || 'Not set', classes[priority] || 'badge-light');
            }
            function statusBadge(status) {
                const classes = { completed:'badge-secondary', in_progress:'badge-primary', pending:'badge-light' };
                return badge(status || 'Not set', classes[status] || 'badge-light');
            }

            function renderEvent(event) {
                const customer = event.customer
                    ? (event.customer.profile_url
                        ? '<a href="' + escapeAttr(event.customer.profile_url) + '">' + escapeHtml(event.customer.name) + '</a>'
                        : escapeHtml(event.customer.name))
                    : '-';
                const assigned = event.assigned_user ? escapeHtml(event.assigned_user.name) : '-';
                const worklist = permissions.can_view_worklist && event.worklist_url
                    ? '<div class="mt-1"><a href="' + escapeAttr(event.worklist_url) + '" class="crm-calendar-muted">Open worklist</a></div>'
                    : '';
                return '<article class="crm-calendar-event' + (event.is_overdue ? ' is-overdue' : '') + (event.status === 'completed' ? ' is-completed' : '') + '">'
                    + '<div class="crm-calendar-muted">' + escapeHtml(event.time || '-') + '</div>'
                    + '<div class="crm-calendar-event-title">' + escapeHtml(event.title || ('Task #' + event.id)) + '</div>'
                    + '<div class="crm-calendar-muted"><strong>Customer:</strong> ' + customer + '</div>'
                    + '<div class="crm-calendar-muted"><strong>Assigned:</strong> ' + assigned + '</div>'
                    + '<div class="crm-calendar-badges">' + priorityBadge(event.priority) + statusBadge(event.status) + (event.is_overdue ? badge('Overdue', 'badge-danger') : '') + '</div>'
                    + worklist + '</article>';
            }

            function renderCalendar(payload) {
                const grouped = {};
                (payload.events || []).forEach(function (event) {
                    if (!grouped[event.date]) grouped[event.date] = [];
                    grouped[event.date].push(event);
                });

                let html = weekDays.map(function (day) { return '<div class="crm-calendar-weekday">' + escapeHtml(day) + '</div>'; }).join('');
                const start = gridStart(), end = gridEnd(), today = dateKey(new Date());
                for (let date = cloneDate(start); date <= end; date.setDate(date.getDate() + 1)) {
                    const key = dateKey(date), outside = date.getMonth() !== currentMonth.getMonth();
                    const events = (grouped[key] || []).map(renderEvent).join('');
                    html += '<section class="crm-calendar-day' + (outside ? ' is-outside' : '') + (key === today ? ' is-today' : '') + '">'
                        + '<div class="d-flex justify-content-between"><span class="crm-calendar-day-number">' + escapeHtml(date.getDate()) + '</span>'
                        + ((grouped[key] || []).length ? '<span class="badge badge-light">' + escapeHtml((grouped[key] || []).length) + '</span>' : '') + '</div>'
                        + (events || '<div class="crm-calendar-empty">No due tasks</div>') + '</section>';
                }
                $('#crmTaskCalendarGrid').html(html);
                $('#crmTaskCalendarTitle').text(monthNames[currentMonth.getMonth()] + ' ' + currentMonth.getFullYear());
                const meta = payload.meta || {};
                $('#crmTaskCalendarSummary').text((meta.total || 0) + ' task card(s) loaded for ' + (meta.start || '-') + ' to ' + (meta.end || '-') + '.');
                $('#crmTaskCalendarGeneratedAt').text(meta.generated_at ? 'Generated: ' + meta.generated_at : '');
                $('#crmTaskCalendarLimit').toggleClass('d-none', !meta.has_more).text(meta.has_more ? 'Showing the first ' + (meta.limit || 500) + ' matching tasks. Narrow the calendar filters to review a smaller operational set.' : '');
            }

            function loadCalendar() {
                const client = http(), sequence = ++loadSequence;
                if (!client) {
                    showFeedback('The shared window.AppAxios client is required to load the CRM task calendar.');
                    notify('Axios unavailable', 'The shared window.AppAxios client is required.', 'error');
                    return;
                }
                hideFeedback(); resetFilterErrors(); setLoading(true);
                $('#crmTaskCalendarSummary').text('Loading CRM task calendar...');
                client.get(routes.data, { params:collectFilters() }).then(function (response) {
                    if (sequence !== loadSequence) return;
                    renderCalendar(response.data || {});
                }).catch(function (error) {
                    if (sequence !== loadSequence) return;
                    const response = error.response || {}, data = response.data || {}, errors = data.errors || {};
                    if (response.status === 422) applyFilterErrors(errors);
                    const firstField = Object.keys(errors)[0], firstGroup = firstField ? errors[firstField] : [];
                    const message = response.status === 422
                        ? ((Array.isArray(firstGroup) ? firstGroup[0] : firstGroup) || 'Check the calendar filters and try again.')
                        : (data.message || 'Unable to load the CRM task calendar.');
                    showFeedback(message);
                    $('#crmTaskCalendarGrid').html('<div class="crm-calendar-loading">Calendar data could not be loaded.</div>');
                    $('#crmTaskCalendarSummary').text('Calendar feed unavailable.');
                }).finally(function () {
                    if (sequence === loadSequence) setLoading(false);
                });
            }

            initRemoteSelect('#crmTaskCalendarCustomer', routes.customers, 'All customers');
            initRemoteSelect('#crmTaskCalendarUser', routes.users, 'All assigned users');
            if (prefillCustomer) selectedOption('#crmTaskCalendarCustomer', prefillCustomer);

            $('#crmTaskCalendarCustomer,#crmTaskCalendarUser,#crmTaskCalendarStatus,#crmTaskCalendarPriority').on('change', function () {
                if (!filtersMuted) loadCalendar();
            });
            $('#previousCrmTaskCalendar').on('click', function () { currentMonth = new Date(currentMonth.getFullYear(), currentMonth.getMonth() - 1, 1); loadCalendar(); });
            $('#todayCrmTaskCalendar').on('click', function () { const today = new Date(); currentMonth = new Date(today.getFullYear(), today.getMonth(), 1); loadCalendar(); });
            $('#nextCrmTaskCalendar').on('click', function () { currentMonth = new Date(currentMonth.getFullYear(), currentMonth.getMonth() + 1, 1); loadCalendar(); });
            $('#refreshCrmTaskCalendar').on('click', loadCalendar);
            $('#resetCrmTaskCalendarFilters').on('click', function () {
                filtersMuted = true;
                selectedOption('#crmTaskCalendarCustomer'); selectedOption('#crmTaskCalendarUser');
                $('#crmTaskCalendarStatus,#crmTaskCalendarPriority').val('');
                filtersMuted = false;
                loadCalendar();
            });

            loadCalendar();
        })(jQuery);
    </script>
@endsection
