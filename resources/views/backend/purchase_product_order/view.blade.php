@extends('backend.master')

@section('header_css')
    <link href="{{ url('dataTable') }}/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="{{ url('dataTable') }}/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <style>
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0px;
            border-radius: 4px;
        }

        table.dataTable tbody td:nth-child(1) {
            text-align: center !important;
            font-weight: 600;
        }

        table.dataTable tbody td:nth-child(2) {
            text-align: center !important;
        }

        table.dataTable tbody td:nth-child(3) {
            text-align: center !important;
        }

        table.dataTable tbody td:nth-child(4) {
            text-align: center !important;
        }

        table.dataTable tbody td:nth-child(5) {
            text-align: center !important;
        }

        table.dataTable tbody td:nth-child(6) {
            text-align: center !important;
        }

        table.dataTable tbody td:nth-child(7) {
            text-align: center !important;
        }

        table.dataTable tbody td:nth-child(8) {
            text-align: center !important;
        }

        tfoot {
            display: table-header-group !important;
        }

        tfoot th {
            text-align: center;
        }

        table#DataTables_Table_0 img {
            transition: all .2s linear;
        }

        table.dataTable tbody td:nth-child(5) {
            text-align: left !important;
            min-width: 520px;
        }

        .data-table thead th {
            white-space: nowrap;
            vertical-align: middle;
        }

        .po-product-cell {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 12px;
            align-items: start;
        }

        .po-product-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            max-height: 170px;
            overflow-y: auto;
            padding-right: 4px;
        }

        .po-product-item {
            width: 96px;
            min-height: 118px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            gap: 5px;
            padding: 7px;
            border: 1px solid #edf1f5;
            border-radius: 6px;
            background: #fbfcfe;
        }

        .po-product-img {
            width: 54px;
            height: 54px;
            border-radius: 6px;
            object-fit: cover;
            background: #eef2f6;
            border: 1px solid #dde5ee;
        }

        .po-product-variants {
            width: 100%;
            color: #637083;
            font-size: 10px;
            line-height: 1.25;
            text-align: center;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .po-product-meta {
            margin-top: auto;
            color: #0b7b83;
            font-size: 11px;
            font-weight: 700;
        }

        .po-details-btn {
            white-space: nowrap;
            border-radius: 5px;
        }

        .po-total-box {
            line-height: 1.7;
            white-space: nowrap;
        }

        img.gridProductImage:hover {
            transform: scale(1.7);
            cursor: pointer;
            position: relative;
            z-index: 2;
        }

        .po-invoice-modal .modal-dialog {
            max-width: 1120px;
        }

        .po-invoice-frame {
            width: 100%;
            min-height: 76vh;
            border: 0;
            background: #f5f7fa;
        }

        .po-invoice-loading {
            min-height: 220px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #637083;
            font-weight: 600;
        }

        @media (max-width: 767.98px) {
            table.dataTable tbody td:nth-child(5) {
                min-width: 360px;
            }

            .po-product-cell {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection

@section('page_title')
    Purchase Product Order
@endsection
@section('page_heading')
    View All Purchase Product Orders
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-12 col-xl-12">
            <div class="card">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-box"></i> View All Purchase Orders
                        </h5>
                        <a href="{{ url('/add/new/purchase-product/order') }}" class="btn btn-success">
                            <i class="fas fa-plus"></i> New Purchase
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0 data-table">
                            <thead>
                                <tr>
                                    <th class="text-center">SL</th>
                                    <th class="text-center">Order Date</th>
                                    <th class="text-center">Code</th>
                                    <th class="text-center">Reference</th>
                                    <th class="text-center">Product</th>
                                    <th class="text-center">Total</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade po-invoice-modal" id="purchaseInvoiceModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Purchase Invoice <span id="purchaseInvoiceCode"></span></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-0" id="purchaseInvoiceBody">
                    <div class="po-invoice-loading">Loading invoice...</div>
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

    <script type="text/javascript">
    var table = $(".data-table").DataTable({
        processing: true,
        serverSide: true,

        ajax: {
            url: "{{ url('view/all/purchase-product/order') }}",
            type: "GET",
            data: function (d) {
                d.order_id = "{{ request('order_id') }}";
            }
        },

        order: [[0, 'desc']],
        language: {
            search: "Search product/order:",
            searchPlaceholder: "Product name, code, reference"
        },

        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },

            {
                data: 'created_at',
                name: 'created_at',
                render: function (data) {
                    if (!data) return '';

                    return new Date(data).toLocaleDateString('en-GB', {
                        timeZone: 'Asia/Dhaka',
                        day: '2-digit',
                        month: 'short',
                        year: 'numeric'
                    });
                }
            },

            { data: 'code', name: 'code' },
            { data: 'reference', name: 'reference' },

            {
                data: 'product',
                name: 'product',
                orderable: false,
                searchable: true
            },

            { data: 'total', name: 'total' },
            { data: 'order_status', name: 'order_status' },

            {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false
            }
        ]
    });

    $('body').on('click', '.js-po-details', function () {
        var slug = $(this).data('slug');
        var code = $(this).data('code') || '';
        var modal = $('#purchaseInvoiceModal');
        var body = $('#purchaseInvoiceBody');

        $('#purchaseInvoiceCode').text(code ? '#' + code : '');
        body.html('<div class="po-invoice-loading">Loading invoice...</div>');
        modal.modal('show');

        $.ajax({
            type: "GET",
            url: "{{ url('/api/purchase-product/order') }}/" + slug + "/invoice-modal",
            success: function (response) {
                var frame = $('<iframe class="po-invoice-frame" title="Purchase invoice preview"></iframe>');
                body.empty().append(frame);
                frame[0].srcdoc = response.html || '<p style="padding:20px;">Invoice not found.</p>';
                if (response.tracking_html) {
                    body.append(response.tracking_html);
                }
            },
            error: function () {
                body.html('<div class="po-invoice-loading text-danger">Unable to load invoice. Please try again.</div>');
            }
        });
    });
</script>


    {{-- js code for user crud --}}
    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $('body').on('click', '.deleteBtn', function() {
            var productProductQuotationSlug = $(this).data("id");
            if (confirm("Are You sure want to delete !")) {
                if (check_demo_user()) {
                    return false;
                }
                $.ajax({
                    type: "GET",
                    url: "{{ url('delete/purchase-product/order') }}" + '/' +
                        productProductQuotationSlug,
                    success: function(data) {
                        table.draw(false);
                        toastr.error("Item has been Deleted",
                            "Deleted Successfully");
                    },
                    error: function(xhr) {
                        // Ensure you're handling the error response properly
                        console.log('Error 11:', xhr.responseJSON.error);
                        // Assuming error message is returned as part of the response JSON
                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            toastr.error(xhr.responseJSON.error, "Error");
                        } else {
                            toastr.error("An unexpected error occurred", "Error");
                        }
                    }
                });
            }
        });
    </script>
@endsection
