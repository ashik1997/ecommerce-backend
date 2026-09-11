@extends('backend.master')

@section('header_css')
    <link href="{{ url('dataTable') }}/css/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <style>
        .crm-saved-segment-page { --crm-indigo:#4338ca; --crm-border:#e0e7ff; --crm-soft:#eef2ff; }
        .crm-saved-segment-card { border:1px solid var(--crm-border); border-radius:14px; box-shadow:0 6px 18px rgba(67,56,202,.06); }
        .crm-saved-segment-filters { background:var(--crm-soft); border:1px solid var(--crm-border); border-radius:12px; }
        .crm-filter-pill { display:inline-block; padding:2px 7px; margin:1px 2px 1px 0; border:1px solid #dbeafe; border-radius:999px; background:#eff6ff; font-size:11px; }
        #crmSavedSegmentTable td { vertical-align:middle; }
    </style>
@endsection

@section('page_title')
    CRM Saved Customer Segments
@endsection

@section('page_heading')
    CRM Saved Customer Segments
@endsection

@section('content')
    <div class="crm-saved-segment-page">
        <div class="card crm-saved-segment-card">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
                    <div>
                        <h4 class="mb-1">Saved Customer Segments</h4>
                        <p class="text-muted mb-0">Reusable private or shared filter definitions for the read-only Portfolio Segments worklist.</p>
                    </div>
                    <div class="mt-2 mt-md-0">
                        @if ($permissions['can_view_portfolio'])
                            <a href="{{ route('crm.customer-segments.index') }}" class="btn btn-outline-primary btn-sm mr-1"><i class="feather-filter"></i> Portfolio Segments</a>
                        @endif
                        @if ($permissions['can_create'] && $permissions['can_view_portfolio'])
                            <a href="{{ route('crm.customer-segments.index') }}" class="btn btn-primary btn-sm"><i class="feather-plus-circle"></i> Define New Segment</a>
                        @endif
                    </div>
                </div>

                <div id="crmSavedSegmentValidation" class="alert alert-danger d-none" role="alert"></div>

                <div class="crm-saved-segment-filters p-3 mb-3">
                    <div class="row">
                        <div class="col-md-4 mb-2">
                            <label for="crmSavedSegmentStatus">Status</label>
                            <select id="crmSavedSegmentStatus" class="form-control">
                                <option value="active">Active</option>
                                <option value="archived">Archived</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-2">
                            <label for="crmSavedSegmentVisibility">Visibility</label>
                            <select id="crmSavedSegmentVisibility" class="form-control">
                                <option value="">Private and shared</option>
                                <option value="private">Private</option>
                                <option value="shared">Shared</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-2 d-flex align-items-end">
                            <button type="button" id="resetCrmSavedSegmentFilters" class="btn btn-outline-secondary btn-block">Reset filters</button>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped mb-0" id="crmSavedSegmentTable">
                        <thead><tr>
                            <th>Name</th><th>Visibility</th><th>Status</th><th>Filters</th><th>Creator</th><th>Updated</th><th>Action</th>
                        </tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="crmSavedSegmentDetailsModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Saved Segment Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body" id="crmSavedSegmentDetailsBody"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('footer_js')
    <script src="{{ url('dataTable') }}/js/jquery.dataTables.min.js"></script>
    <script src="{{ url('dataTable') }}/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @php
        $crmSavedSegmentUrls = [
            'data' => route('crm.saved-customer-segments.data'),
            'archive' => route('crm.saved-customer-segments.archive', ['segment' => '__SEGMENT__']),
        ];
    @endphp
    <script>
        (function ($) {
            'use strict';
            const urls = @json($crmSavedSegmentUrls);
            let tableErrorVisible = false;
            let archiveBusy = false;

            function client() { return window.AppAxios || null; }
            function esc(value) { return $('<div>').text(value === null || value === undefined || value === '' ? '—' : String(value)).html(); }
            function attr(value) { return esc(value).replace(/`/g, '&#96;'); }
            function notify(title, text, icon) {
                if (typeof Swal !== 'undefined') return Swal.fire({title:title, text:text || '', icon:icon || 'info', confirmButtonColor:'#4338ca'});
                window.alert((title ? title + ': ' : '') + (text || ''));
            }
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
                const alert = $('#crmSavedSegmentValidation');
                if (message) alert.text(message).removeClass('d-none'); else alert.text('').addClass('d-none');
            }
            function pill(value) { return '<span class="crm-filter-pill">' + esc(value) + '</span>'; }
            function renderSummary(items) {
                if (!Array.isArray(items) || !items.length) return '<span class="text-muted">—</span>';
                return items.slice(0, 4).map(function (item) { return pill(item.label + ': ' + item.value); }).join('')
                    + (items.length > 4 ? '<small class="text-muted"> +' + (items.length - 4) + ' more</small>' : '');
            }
            function actionButtons(segment) {
                let html = '<button type="button" class="btn btn-xs btn-outline-secondary js-segment-details mr-1" data-url="' + attr(segment.details_url) + '"><i class="feather-eye"></i></button>';
                if (segment.apply_url) html += '<a class="btn btn-xs btn-outline-primary mr-1" href="' + attr(segment.apply_url) + '" title="Apply"><i class="feather-filter"></i></a>';
                if (segment.edit_url) html += '<a class="btn btn-xs btn-outline-info mr-1" href="' + attr(segment.edit_url) + '" title="Edit filters"><i class="feather-edit-2"></i></a>';
                if (segment.can_archive) html += '<button type="button" class="btn btn-xs btn-outline-warning js-segment-archive" data-id="' + Number(segment.id) + '" data-name="' + attr(segment.name) + '"><i class="feather-archive"></i></button>';
                return html;
            }
            function detailsHtml(segment) {
                const invalidWarning = segment.filters_valid === false
                    ? '<div class="alert alert-warning">' + esc(segment.filters_validation_message || 'This saved segment has an invalid stored filter definition. It cannot be applied. The creator may archive it.') + '</div>'
                    : '';
                const summary = Array.isArray(segment.filters_summary) && segment.filters_summary.length
                    ? '<ul class="mb-0">' + segment.filters_summary.map(function (item) { return '<li><strong>' + esc(item.label) + ':</strong> ' + esc(item.value) + '</li>'; }).join('') + '</ul>'
                    : '<span class="text-muted">No saved filters.</span>';
                return invalidWarning + '<dl class="row mb-3">'
                    + '<dt class="col-sm-3">Name</dt><dd class="col-sm-9">' + esc(segment.name) + '</dd>'
                    + '<dt class="col-sm-3">Description</dt><dd class="col-sm-9">' + esc(segment.description) + '</dd>'
                    + '<dt class="col-sm-3">Visibility</dt><dd class="col-sm-9">' + esc(segment.visibility) + '</dd>'
                    + '<dt class="col-sm-3">Status</dt><dd class="col-sm-9">' + esc(segment.status) + '</dd>'
                    + '<dt class="col-sm-3">Creator</dt><dd class="col-sm-9">' + esc(segment.creator) + '</dd>'
                    + '<dt class="col-sm-3">Updated</dt><dd class="col-sm-9">' + esc(segment.updated_at) + '</dd>'
                    + '</dl><h6>Saved filters</h6>' + summary;
            }

            const table = $('#crmSavedSegmentTable').DataTable({
                processing:true, serverSide:true, searching:true, ordering:false, pageLength:25,
                ajax:function (data, callback) {
                    const http = client();
                    showValidation('');
                    if (!http) { if (!tableErrorVisible) { tableErrorVisible = true; notify('Axios unavailable', 'The shared window.AppAxios client is required.', 'error'); } callback(emptyTable(data.draw)); return; }
                    data.status = $('#crmSavedSegmentStatus').val();
                    data.visibility = $('#crmSavedSegmentVisibility').val();
                    http.get(urls.data, {params:data}).then(function (response) { tableErrorVisible = false; callback(response.data); }).catch(function (error) {
                        const payload = (error.response && error.response.data) || {}; const details = validationText(payload); callback(emptyTable(data.draw));
                        if (error.response && error.response.status === 422) showValidation(details || payload.message || 'Please review the selected filters.');
                        if (!tableErrorVisible) { tableErrorVisible = true; notify('Saved segments load failed', details || payload.message || 'Unable to load CRM saved segments.', 'error'); }
                    });
                },
                columns:[
                    {data:'segment.name'},
                    {data:'segment.visibility', render:function (value) { return '<span class="badge badge-light border">' + esc(value) + '</span>'; }},
                    {data:'segment.status', render:function (value) { return value === 'active' ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-secondary">Archived</span>'; }},
                    {data:'segment.filters_summary', render:function (value) { return renderSummary(value); }},
                    {data:'segment.creator', defaultContent:''},
                    {data:'segment.updated_at', defaultContent:''},
                    {data:'segment', render:function (segment) { return actionButtons(segment); }}
                ]
            });

            $('#crmSavedSegmentStatus,#crmSavedSegmentVisibility').on('change', function () { table.ajax.reload(); });
            $('#resetCrmSavedSegmentFilters').on('click', function () { $('#crmSavedSegmentStatus').val('active'); $('#crmSavedSegmentVisibility').val(''); showValidation(''); table.ajax.reload(); });

            $(document).on('click', '.js-segment-details', function () {
                const http = client(); const url = $(this).data('url');
                if (!http || !url) return notify('Unable to load details', 'The saved segment details endpoint is unavailable.', 'error');
                $('#crmSavedSegmentDetailsBody').html('<p class="text-muted mb-0">Loading...</p>'); $('#crmSavedSegmentDetailsModal').modal('show');
                http.get(url).then(function (response) { $('#crmSavedSegmentDetailsBody').html(detailsHtml(response.data.segment || {})); }).catch(function (error) {
                    const payload = (error.response && error.response.data) || {}; $('#crmSavedSegmentDetailsBody').html('<div class="alert alert-danger mb-0">' + esc(payload.message || 'Unable to load saved segment details.') + '</div>');
                });
            });

            $(document).on('click', '.js-segment-archive', function () {
                if (archiveBusy) return;
                const button = $(this); const id = button.data('id'); const name = button.data('name');
                const confirm = typeof Swal !== 'undefined'
                    ? Swal.fire({title:'Archive saved segment?', text:String(name || ''), icon:'warning', showCancelButton:true, confirmButtonText:'Archive', confirmButtonColor:'#b45309'})
                    : Promise.resolve({isConfirmed:window.confirm('Archive this saved segment?')});
                confirm.then(function (result) {
                    if (!result.isConfirmed) return;
                    const http = client(); if (!http) return notify('Axios unavailable', 'The shared window.AppAxios client is required.', 'error');
                    archiveBusy = true; button.prop('disabled', true);
                    http.post(urls.archive.replace('__SEGMENT__', id), {}).then(function (response) { notify('Archived', response.data.message, 'success'); table.ajax.reload(null, false); }).catch(function (error) {
                        const payload = (error.response && error.response.data) || {}; notify('Archive failed', validationText(payload) || payload.message || 'Unable to archive this saved segment.', 'error');
                    }).finally(function () { archiveBusy = false; button.prop('disabled', false); });
                });
            });
        })(jQuery);
    </script>
@endsection
