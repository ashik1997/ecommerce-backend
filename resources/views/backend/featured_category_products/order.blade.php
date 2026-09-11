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
    </style>
@endsection

@section('page_title')
    Featured Categories
@endsection
@section('page_heading')
    View All Featured Categories
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-12 col-xl-12">
            <div class="card">
                <div class="card-body">

                    <h2>Featured Category Order</h2>

                    {{-- Table --}}
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
                                    {{-- @dd() --}}
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


            // Table Load hobe Category Select korle
            $('#category_id').on('change', function() {
                loadTable();
            });

            // subcategory select korle table load hobe
            $('#subcategory_id').on('change', function() {
                loadTable();
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
