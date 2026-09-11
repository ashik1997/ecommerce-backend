@extends('backend.master')

@section('header_css')
    <link href="{{ url('dataTable') }}/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="{{ url('dataTable') }}/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0px;
            border-radius: 4px;
        }

        table.dataTable tbody td:nth-child(1) {
            font-weight: 600;
        }

        table.dataTable tbody td {
            text-align: center !important;
        }

        tfoot {
            display: table-header-group !important;
        }

        tfoot th {
            text-align: center;
        }

        .disabled {
            pointer-events: none; //This makes it not clickable
            opacity: 0.6; //This grays it out to look disabled
        }
    </style>
@endsection

@section('page_title')
    Featured Category Products
@endsection
@section('page_heading')
    View All Featured Category Products
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-12 col-xl-12">
            <div class="card">
                <div class="card-body">

                    <h2>Featured Category Product Mapping</h2>

                    {{-- Category Dropdown --}}
                    {{-- <div class="mb-3 col-md-4">
                        <label>Select Featured Category</label>
                        <select id="category_id" class="form-control"></select>
                    </div> --}}

                    <div class="row mb-3">

                        <div class="col-md-4">
                            <label>Select Featured Category</label>
                            <select id="category_id" class="form-control"></select>
                        </div>

                        <div class="col-md-4 d-none" id="subcategoryWrap">
                            <label>Select Subcategory</label>
                            <select id="subcategory_id" class="form-control"></select>
                        </div>

                        <div class="col-md-4 d-none" id="childcategoryWrap">
                            <label>Select Child Category</label>
                            <select id="child_category_id" class="form-control"></select>
                        </div>

                    </div>

                    <div class="row align-items-end mb-3">

                        <div class="col-md-2">
                            <button id="addNewBtn" class="btn btn-primary w-100">
                                Add New
                            </button>
                        </div>

                        <div class="col-md-4">
                            {{-- <label>Select Products</label> --}}
                            <select id="product_ids" name="product_ids[]" multiple class="form-control"></select>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-12 col-xl-12">
            <div class="card">
                <div class="card-body">

                    <h4 class="mt-4">Featured Category Products</h4>

                    {{-- Table --}}
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Sub Category</th>
                                <th>Child Category</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="mappingTable"></tbody>
                    </table>



                </div>
            </div>
        </div>
        {{-- <div class="col-lg-12 col-xl-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="mt-4">Featured Categories</h4>

                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Serial No:</th>
                                <th>Featured Category</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($categories as $category)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $category->name }}</td>
                                    <td>
                                        <form action="{{ route('FeaturedCategoriesChangeOrder', $category) }}"
                                            method="POST" style="display:inline-block">
                                            @csrf
                                            <input type="hidden" name="direction" value="up">
                                            <button
                                                class="btn btn-sm btn-success 
                                                {{ $loop->first ? 'disabled' : '' }}
                                                 ">↑</button>
                                        </form>

                                        <form action="{{ route('FeaturedCategoriesChangeOrder', $category) }}"
                                            method="POST" style="display:inline-block">
                                            @csrf
                                            <input type="hidden" name="direction" value="down">
                                            <button
                                                class="btn btn-sm btn-danger 
                                                {{ $loop->last ? 'disabled' : '' }}
                                                 ">↓</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div> --}}
    </div>

    {{-- Modal --}}
    <div class="modal fade" id="addModal">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <h5>Add Products</h5>
                </div>

                <div class="modal-body">
                    <select id="products" class="form-control" multiple></select>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-success" id="saveBtn">Save</button>
                </div>

            </div>
        </div>
    </div>
@endsection


@section('footer_js')
    {{-- js code for data table --}}
    <script src="{{ url('dataTable') }}/js/jquery.validate.js"></script>
    <script src="{{ url('dataTable') }}/js/jquery.dataTables.min.js"></script>
    <script src="{{ url('dataTable') }}/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {

            function getSelectedFilters() {
                return {
                    category_id: $('#category_id').val(),
                    subcategory_id: $('#subcategory_id').val(),
                    child_category_id: $('#child_category_id').val()
                };
            }

            // Select2 Category AJAX
            $('#category_id').select2({
                placeholder: "Select Category",
                width: '100%',
                ajax: {
                    url: '/api/featured-categories',
                    dataType: 'json',
                    delay: 250,

                    data: function(params) {
                        return {
                            search: params.term,
                            page: params.page || 1
                        };
                    },

                    processResults: function(data) {
                        return {
                            results: data.data,
                            pagination: {
                                more: data.pagination.more
                            }
                        };
                    },

                    cache: true
                }
            });

            // category select na korle modal open hobe na 
            $('#addNewBtn').click(function() {

                if (!$('#category_id').val()) {
                    console.log('Please select category first');
                    return;
                }

                $('#addModal').modal('show');
            });



            // Table Load hobe Category Select korle
            $('#category_id').on('change', function() {
                loadTable();
            });

            // subcategory select korle table load hobe
            $('#subcategory_id').on('change', function() {
                loadTable();
            });

            // Category ar Subcategory thakle subcategory select2 load hobe 

            $('#category_id').on('change', function() {
                let categoryId = $(this).val();

                // reset
                if ($('#subcategory_id').hasClass('select2-hidden-accessible')) {
                    $('#subcategory_id').select2('destroy');
                }

                $('#subcategory_id').empty();
                $('#subcategoryWrap').addClass('d-none');

                if (!categoryId) return;

                // first check if any subcategory exists
                $.get('/api/featured-subcategories', {
                    category_id: categoryId,
                    page: 1
                }, function(response) {

                    if (response.data && response.data.length > 0) {

                        // show only when data found
                        $('#subcategoryWrap').removeClass('d-none');

                        $('#subcategory_id').select2({
                            width: '100%',
                            placeholder: 'Select Subcategory',
                            ajax: {
                                url: '/api/featured-subcategories',
                                dataType: 'json',
                                delay: 250,
                                data: function(params) {
                                    return {
                                        category_id: categoryId,
                                        search: params.term || '',
                                        page: params.page || 1
                                    };
                                },
                                processResults: function(data) {
                                    return {
                                        results: data.data,
                                        pagination: {
                                            more: data.pagination.more
                                        }
                                    };
                                },
                                cache: true
                            }
                        });

                    } else {
                        // no subcategory
                        $('#subcategoryWrap').addClass('d-none');
                    }
                });
            });


            // Modal Product Load
            $('#addModal').on('shown.bs.modal', function() {

                let categoryId = $('#category_id').val();

                if (!categoryId) {
                    // $('#addModal').modal('hide');
                    return;
                }

                if ($('#products').hasClass('select2-hidden-accessible')) {
                    $('#products').select2('destroy');
                }

                $('#products').empty();

                $('#products').select2({
                    width: '100%',
                    dropdownParent: $('#addModal'),
                    placeholder: 'Select Products',
                    multiple: true,
                    allowClear: true,
                    ajax: {
                        url: '/api/category-products',
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            let filters = getSelectedFilters();
                            return {
                                category_id: filters.category_id,
                                subcategory_id: filters.subcategory_id,
                                child_category_id: filters.child_category_id,
                                search: params.term || '',
                                page: params.page || 1
                            };
                        },
                        processResults: function(data) {
                            return {
                                results: data.data,
                                pagination: {
                                    more: data.pagination.more
                                }
                            };
                        },
                        cache: true
                    },
                    minimumInputLength: 0
                });

                $('#products').select2('open');

            });


            $('#saveBtn').off('click').on('click', function() {
                let categoryId = $('#category_id').val();
                let selectedProducts = $('#products').val();

                if (!categoryId) {
                    console.log('Please select category first');
                    return;
                }

                if (!selectedProducts || selectedProducts.lenght === 0) {
                    console.log('Please select at least one product');
                    return;
                }

                let products = selectedProducts.map(id => ({
                    product_id: id
                }));

                $('#saveBtn').prop('disabled', true).text('Saving...');

                $.ajax({
                    url: '/api/featured-category-products',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        category_id: $('#category_id').val(),
                        subcategory_id: $('#subcategory_id').val(),
                        child_category_id: $('#child_category_id').val(),
                        products: products,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(res) {
                        console.log(res.message || 'Saved successfully');

                        $('#addModal').modal('hide');
                        $('#category_id').trigger('change');
                        $('#products').val(null).trigger('change');
                    },
                    error: function(xhr) {
                        console.log(xhr.responseText);
                        console.log('An error occurred while saving. Please try again.');
                    },
                    complete: function() {
                        $('#saveBtn').prop('disabled', false).text('Save');
                    }
                });
            });


            // search products by name in select2
            $('#product_ids').select2({
                placeholder: 'search products by name',
                width: '100%',
                allowClear: true,

                ajax: {
                    url: '/api/products-search',
                    dataType: 'json',
                    delay: 250,

                    data: function(params) {
                        return {
                            search: params.term,
                            page: params.page || 1
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.results,
                            pagination: {
                                more: data.pagination.more
                            }
                        };
                    },
                    cache: true

                }
            });


            // load products in table when product_ids select2 change
            $('#product_ids').on('change', function() {
                let productIds = $(this).val();
                console.log('Selected Product IDs:', productIds);

                if (!productIds || productIds.length === 0) {
                    $('#mappingTable').html('');
                    return;
                }

                $.get('/api/featured-category-products', {
                    // category_id: $('#category_id').val(),
                    product_ids: $('#product_ids').val()
                }, function(response) {
                    console.log(response);

                    let html = '';
                    response.forEach(function(row) {
                        html += `
                        <tr>
                            <td>${row.product?.name ?? ''}</td>
                            <td>${row.category?.name ?? ''}</td>
                            <td>${row.subcategory?.name ?? ''}</td>
                            <td>${row.child_category?.name ?? ''}</td>
                            <td>
                                <button class="btn btn-sm btn-danger deleteBtn" data-id="${row.id}">Delete</button>
                            </td>

                        </tr>
                        `;
                    });
                    $('#mappingTable').html(html);
                });

            });

            $(document).on('click', '.deleteBtn', function() {
                let id = $(this).data('id');

                if (!confirm('Are you sure you want to delete this product')) {
                    return;
                }
                $.ajax({
                    url: `/api/featured-category-products-delete/${id}`,
                    type: 'DELETE',
                    dataType: 'json',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(res) {
                        console.log(res.message || 'Deleted successfully');
                        $('#category_id').trigger('change');
                    },
                    error: function(xhr) {
                        console.log(xhr.responseText);
                        console.log('An error occurred while deleting. Please try again.');
                    }
                })


            });

            function loadTable() {

                let filters = getSelectedFilters();

                $.get('/api/featured-category-products', filters, function(response) {

                    let html = '';

                    response.forEach(function(row) {
                        html += `
                <tr>
                    <td>${row.product?.name ?? ''}</td>
                    <td>${row.category?.name ?? ''}</td>
                    <td>${row.subcategory?.name ?? ''}</td>
                    <td>${row.child_category?.name ?? ''}</td>
                    <td>
                        <button class="btn btn-sm btn-danger deleteBtn" data-id="${row.id}">
                            Delete
                        </button>
                    </td>
                </tr>
            `;
                    });

                    $('#mappingTable').html(html);
                });
            }

        });
    </script>
@endsection
