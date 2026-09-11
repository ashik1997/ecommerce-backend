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
    Area Base Courier Charges Management
@endsection
@section('page_heading')
    View All Area Base Courier Charges
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-12 col-xl-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-3">Area Base Courier Charges</h4>
                    <div class="table-responsive">

                        <label id="customFilter">
                            <a href="{{route('area-base-courier-charges.create')}}" class="btn btn-success btn-sm" id="addNewFlag"
                                style="margin-left: 5px"><i class="feather-plus"></i> Add New Area Base Courier Charge</a>
                        </label>

                        <table class="table table-bordered mb-0 data-table">
                            <thead>
                                <tr>
                                    <th class="text-center">SL</th>
                                    <th class="text-center">Courier Name</th>
                                    <th class="text-center">Area Name</th>
                                    <th class="text-center">Shipping Cost</th>
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
            stateSave: true,
            ajax: "{{ route('area-base-courier-charges.index') }}",
            order: [[0, 'desc']],
            columns: [
                {
                    data: 'id',
                    name: 'id'
                },
                {
                    data: 'courier_name',
                    name: 'courier_name'
                },
                {
                    data: 'area_name',
                    name: 'area_name'
                },
                { data: 'shipping_cost', name: 'shipping_cost' },
                { data: 'status', name: 'status' },
                { data: 'action', name: 'action', orderable: false, searchable: false },
            ],
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
            var chargeId = $(this).data("id");
            if (confirm("Are you sure you want to delete this Area Base Courier Charge?")) {
                if (check_demo_user()) {
                    return false;
                }
                $.ajax({
                    type: "DELETE",
                    url: "{{ route('area-base-courier-charges.destroy', ':id') }}".replace(':id', chargeId),
                    success: function (data) {
                        table.draw(false);
                        toastr.success("Area Base Courier Charge has been Deleted", "Deleted Successfully");
                    },
                    error: function (data) {
                        console.log('Error:', data);
                    }
                });
            }
        });
    </script>
@endsection