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
            width: 180px;
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

        tfoot {
            display: table-header-group !important;
        }

        tfoot th {
            text-align: center;
        }
    </style>
@endsection

@section('page_title')
    Flag
@endsection
@section('page_heading')
    View All Flags
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-12 col-xl-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-3">Flag Lists</h4>
                    <div class="table-responsive">

                        <label id="customFilter">
                            <button class="btn btn-success btn-sm" id="addNewFlag" style="margin-left: 5px"><i
                                    class="feather-plus"></i> Add New Flag</button>
                        </label>

                        <table class="table table-bordered mb-0 data-table">
                            <thead>
                                <tr>
                                    <th class="text-center">SL</th>
                                    <th class="text-center">Icon</th>
                                    <th class="text-center">Name</th>
                                    {{-- <th class="text-center">Status</th> --}}
                                    <th class="text-center">Featured</th>
                                    <th class="text-center">Created At</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tfoot>
                                <tr>
                                    {{-- <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th> --}}
                                </tr>
                            </tfoot>
                            <tbody>

                            </tbody>
                        </table>
                    </div>
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
        const fileUrl = "{{ get_file_url() }}";
        var table = $(".data-table").DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ url('view/all/flags') }}",
            order: [[0, 'desc']],
            columns: [
                {
                    data: 'id',
                    name: 'id'
                }, //orderable: true, searchable: true
                {
                    data: 'icon',
                    name: 'icon',
                    render: function (data, type, full, meta) {
                        if (data) {
                            return "<img src=\"" + fileUrl + "/" + data + "\" width=\"60\"/>";
                        } else {
                            return '';
                        }
                    }
                },
                {
                    data: 'name',
                    name: 'name'
                },
                // { data: 'status', name: 'status' },
                { data: 'featured', name: 'featured' },
                { data: 'created_at', name: 'created_at' },
                { data: 'action', name: 'action', orderable: false, searchable: false },
            ],
            initComplete: function () {
                // this.api().columns([2]).every(function() {
                //     var column = this;
                //     var input = document.createElement("input");
                //     $(input).appendTo($(column.footer()).empty())
                //         .on('change', function() {
                //             var val = $.fn.dataTable.util.escapeRegex($(this).val());
                //             column.search(val ? val : '', true, false).draw();
                //         });
                // });

                // this.api().columns([3]).every(function () {
                //     var column = this;
                //     var select = $('<select style="width:100%"><option value="">All</option></select>')
                //         .appendTo($(column.footer()).empty())
                //         .on('change', function () {
                //             var val = $.fn.dataTable.util.escapeRegex(
                //                 $(this).val()
                //             );
                //             column
                //                 .search(val ? '^' + val + '$' : '', true, false)
                //                 .draw();
                //         });
                //     column.each(function () {
                //         select.append('<option value="1">' + 'Active' + '</option>')
                //         select.append('<option value="0">' + 'Inactive' + '</option>')

                //     });
                // });
            }
        });

        $(".dataTables_filter").append($("#customFilter"));
    </script>

    {{-- js code for user crud --}}
    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $('body').on('click', '.deleteBtn', function () {
            var slug = $(this).data("id");
            if (confirm("Are You sure want to delete !")) {
                if (check_demo_user()) {
                    return false;
                }
                $.ajax({
                    type: "GET",
                    url: "{{ url('delete/flag') }}" + '/' + slug,
                    success: function (data) {
                        table.draw(false);
                        toastr.error("Flag has been Deleted", "Deleted Successfully");
                    },
                    error: function (data) {
                        console.log('Error:', data);
                    }
                });
            }
        });

        $('body').on('click', '.featureBtn', function () {
            var id = $(this).data("id");
            if (confirm("Are You sure to Change the Feature Status !")) {
                $.ajax({
                    type: "GET",
                    url: "{{ url('feature/flag') }}" + '/' + id,
                    success: function (data) {

                        table.draw(false);
                        toastr.success("SubCategory has been Featured", "Featured Successfully");

                    },
                    error: function (data) {
                        console.log('Error:', data);
                    }
                });
            }
        });
    </script>
@endsection