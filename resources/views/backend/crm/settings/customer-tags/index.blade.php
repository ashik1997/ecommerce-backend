@extends('backend.master')

@section('header_css')
    <link href="{{ url('dataTable') }}/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="{{ url('dataTable') }}/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <style>
        .crm-tag-card { border: 1px solid #e5f3f3; border-radius: 10px; }
        .crm-tag-card .card-title { color: #0f766e; font-weight: 700; }
        .dataTables_wrapper .dataTables_paginate .paginate_button { padding: 0; }
        table.dataTable tbody td { vertical-align: middle; }
        .crm-tag-loading { display: none; }
        .crm-tag-loading.is-visible { display: inline-block; }
    </style>
@endsection

@section('page_title')
    CRM Customer Tags
@endsection

@section('page_heading')
    CRM Customer Tags
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card crm-tag-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h4 class="card-title mb-1">CRM Customer Tag Settings</h4>
                            <small class="text-muted">Manage reusable active and inactive tags. Existing customer assignments remain preserved when a tag is deactivated.</small>
                        </div>
                        <button type="button" class="btn btn-sm btn-primary" id="openCreateTagModal">
                            <i class="feather-plus-circle"></i> Add Customer Tag
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped mb-0" id="crmCustomerTagsTable">
                            <thead>
                                <tr>
                                    <th width="65" class="text-center">SL</th>
                                    <th>Name</th>
                                    <th>Slug</th>
                                    <th width="155">Color</th>
                                    <th width="100" class="text-center">Status</th>
                                    <th width="120" class="text-center">Assignments</th>
                                    <th width="170" class="text-center">Created At</th>
                                    <th width="225" class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="crmCustomerTagModal" tabindex="-1" role="dialog" aria-labelledby="crmCustomerTagModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form id="crmCustomerTagForm" novalidate>
                    <div class="modal-header">
                        <h5 class="modal-title" id="crmCustomerTagModalTitle">Add CRM Customer Tag</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="tagId" value="">

                        <div class="form-group">
                            <label for="tagName">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="tagName" name="name" maxlength="120" autocomplete="off" required>
                            <div class="invalid-feedback" id="error-name"></div>
                        </div>

                        <div class="form-group">
                            <label for="tagSlug">Slug</label>
                            <input type="text" class="form-control" id="tagSlug" name="slug" maxlength="160" autocomplete="off" placeholder="Generated from name when left blank">
                            <div class="invalid-feedback" id="error-slug"></div>
                        </div>

                        <div class="form-group">
                            <label for="tagColor">Color</label>
                            <input type="text" class="form-control" id="tagColor" name="color" maxlength="7" autocomplete="off" placeholder="#0f766e">
                            <div class="invalid-feedback" id="error-color"></div>
                            <small class="form-text text-muted">Use a 3-digit or 6-digit hex color, such as #0f766e.</small>
                        </div>

                        <div class="form-group mb-0">
                            <label for="tagStatus">Status <span class="text-danger">*</span></label>
                            <select class="form-control" id="tagStatus" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                            <div class="invalid-feedback" id="error-status"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="saveCrmCustomerTag">
                            <span class="spinner-border spinner-border-sm crm-tag-loading mr-1" role="status" aria-hidden="true"></span>
                            <span class="js-save-label">Save Tag</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('footer_js')
    <script src="{{ url('dataTable') }}/js/jquery.dataTables.min.js"></script>
    <script src="{{ url('dataTable') }}/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        (function ($) {
            const urls = {
                data: @json(route('crm.settings.customer-tags.data')),
                store: @json(route('crm.settings.customer-tags.store')),
                update: @json(route('crm.settings.customer-tags.update', ['tag' => '__TAG__'])),
                status: @json(route('crm.settings.customer-tags.status', ['tag' => '__TAG__']))
            };
            const statusRequests = new Set();
            let isSubmitting = false;
            let tableErrorVisible = false;

            const table = $('#crmCustomerTagsTable').DataTable({
                processing: true,
                serverSide: true,
                pageLength: 25,
                ajax:function (data, callback) {
                    const http = client();
                    if (!http) {
                        callback(emptyTable(data.draw));
                        if (!tableErrorVisible) {
                            tableErrorVisible = true;
                            notify('Axios unavailable', 'The shared window.AppAxios client is required.', 'error');
                        }
                        return;
                    }
                    http.get(urls.data, {params:data}).then(function (response) {
                        tableErrorVisible = false;
                        callback(response.data);
                    }).catch(function (error) {
                        callback(emptyTable(data.draw));
                        const payload = (error.response && error.response.data) || {};
                        if (!tableErrorVisible) {
                            tableErrorVisible = true;
                            notify('Tag list load failed', payload.message || 'Unable to load CRM customer tags.', 'error');
                        }
                    });
                },
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center'},
                    {data: 'name', name: 'name'},
                    {data: 'slug', name: 'slug', defaultContent: ''},
                    {data: 'color_preview', name: 'color', orderable: false, className: 'text-nowrap'},
                    {data: 'status_badge', name: 'status', orderable: false, searchable: false, className: 'text-center'},
                    {data: 'customers_count', name: 'customers_count', searchable: false, className: 'text-center'},
                    {data: 'created_at', name: 'created_at', className: 'text-center'},
                    {data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-center text-nowrap'}
                ]
            });

            function client() {
                return window.AppAxios || null;
            }

            function emptyTable(draw) {
                return {draw:Number(draw || 0), recordsTotal:0, recordsFiltered:0, data:[]};
            }

            function endpoint(template, id) {
                return template.replace('__TAG__', String(id));
            }

            function notify(title, text, icon) {
                return Swal.fire({
                    title: title,
                    text: text || '',
                    icon: icon || 'info',
                    confirmButtonColor: '#0f766e'
                });
            }

            function resetErrors() {
                $('#crmCustomerTagForm .is-invalid').removeClass('is-invalid');
                $('#crmCustomerTagForm .invalid-feedback').text('');
            }

            function applyErrors(errors) {
                Object.keys(errors || {}).forEach(function (key) {
                    const field = key.split('.')[0];
                    const input = $('#crmCustomerTagForm [name="' + field + '"]');
                    const message = Array.isArray(errors[key]) ? errors[key][0] : errors[key];
                    input.addClass('is-invalid');
                    $('#error-' + field).text(message || 'Invalid value.');
                });
            }

            function setSubmitLoading(loading) {
                isSubmitting = loading;
                $('#saveCrmCustomerTag').prop('disabled', loading);
                $('#saveCrmCustomerTag .crm-tag-loading').toggleClass('is-visible', loading);
                $('#saveCrmCustomerTag .js-save-label').text(loading ? 'Saving...' : 'Save Tag');
            }

            function resetForm() {
                document.getElementById('crmCustomerTagForm').reset();
                $('#tagId').val('');
                $('#tagStatus').val('active');
                $('#crmCustomerTagModalTitle').text('Add CRM Customer Tag');
                resetErrors();
                setSubmitLoading(false);
            }

            $('#openCreateTagModal').on('click', function () {
                resetForm();
                $('#crmCustomerTagModal').modal('show');
            });

            $('#crmCustomerTagsTable').on('click', '.js-edit-tag', function () {
                resetForm();
                let tag = {};

                try {
                    tag = JSON.parse($(this).attr('data-tag') || '{}');
                } catch (error) {
                    notify('Unable to edit', 'The selected tag data could not be read.', 'error');
                    return;
                }

                $('#tagId').val(tag.id || '');
                $('#tagName').val(tag.name || '');
                $('#tagSlug').val(tag.slug || '');
                $('#tagColor').val(tag.color || '');
                $('#tagStatus').val(tag.status || 'active');
                $('#crmCustomerTagModalTitle').text('Edit CRM Customer Tag');
                $('#crmCustomerTagModal').modal('show');
            });

            $('#crmCustomerTagForm').on('submit', function (event) {
                event.preventDefault();

                if (isSubmitting) return;

                const http = client();
                if (!http) {
                    notify('Axios unavailable', 'The shared window.AppAxios client is required.', 'error');
                    return;
                }

                resetErrors();
                setSubmitLoading(true);

                const tagId = $('#tagId').val();
                const requestUrl = tagId ? endpoint(urls.update, tagId) : urls.store;
                const payload = {
                    name: $('#tagName').val(),
                    slug: $('#tagSlug').val(),
                    color: $('#tagColor').val(),
                    status: $('#tagStatus').val()
                };

                http.post(requestUrl, payload)
                    .then(function (response) {
                        $('#crmCustomerTagModal').modal('hide');
                        table.ajax.reload(null, false);
                        notify('Saved', response.data.message || 'CRM customer tag saved successfully.', 'success');
                    })
                    .catch(function (error) {
                        const response = error.response || {};
                        const data = response.data || {};

                        if (response.status === 422 && data.errors) {
                            applyErrors(data.errors);
                        }

                        notify('Save failed', data.message || 'Unable to save the CRM customer tag.', 'error');
                    })
                    .finally(function () {
                        setSubmitLoading(false);
                    });
            });

            $('#crmCustomerTagsTable').on('click', '.js-toggle-tag', function () {
                const button = $(this);
                const tagId = String(button.data('id'));
                const status = String(button.data('status'));
                const name = String(button.data('name') || 'this tag');

                if (statusRequests.has(tagId)) return;

                Swal.fire({
                    title: status === 'active' ? 'Activate customer tag?' : 'Deactivate customer tag?',
                    text: status === 'active'
                        ? 'This will allow the tag to be assigned again.'
                        : 'Existing customer assignments will be preserved, but new assignments will be blocked.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: status === 'active' ? 'Yes, activate' : 'Yes, deactivate',
                    confirmButtonColor: '#0f766e'
                }).then(function (result) {
                    if (!result.isConfirmed) return;

                    const http = client();
                    if (!http) {
                        notify('Axios unavailable', 'The shared window.AppAxios client is required.', 'error');
                        return;
                    }

                    statusRequests.add(tagId);
                    button.prop('disabled', true);

                    http.post(endpoint(urls.status, tagId), {status: status})
                        .then(function (response) {
                            table.ajax.reload(null, false);
                            notify('Updated', response.data.message || name + ' updated successfully.', 'success');
                        })
                        .catch(function (error) {
                            const data = (error.response && error.response.data) || {};
                            notify('Update failed', data.message || 'Unable to update the CRM customer tag status.', 'error');
                        })
                        .finally(function () {
                            statusRequests.delete(tagId);
                            button.prop('disabled', false);
                        });
                });
            });

            $('#crmCustomerTagModal').on('hidden.bs.modal', resetForm);
        })(jQuery);
    </script>
@endsection
