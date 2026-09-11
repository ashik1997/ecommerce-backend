
@extends('backend.master')

@section('header_css')
    <link href="{{ url('dataTable') }}/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="{{ url('dataTable') }}/css/dataTables.bootstrap4.min.css" rel="stylesheet">
@endsection

@section('page_title')
    View Purchase History
@endsection
@section('page_heading')
    View Purchase History
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-12 col-xl-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-3">{{ $product->product_name??'Unknown Product' }} Purchase History</h4>
                    <div class="table-responsive">
                        
                        <table class="table table-bordered table-striped" id="purchase-history-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Product Name</th>
                                    <th>Code</th>
                                    <th>Qty</th>
                                    <th>Previous Stock</th>
                                    <th>Purchase Price</th>
                                    <th>Discount</th>
                                    <th>Tax</th>
                                    <th>Created At</th>
                                    <th>Action</th>
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
        $(document).ready(function () {
            $('#purchase-history-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('product-management.product.purchase.history') }}", // update route if needed
                    data: {
                        product_id: "{{ request()->product_id ?? 1 }}"
                    }
                },
                columns: [
                    { data: 'id', name: 'id' },
                    { data: 'product_name', name: 'product_name' },
                    { data: 'units', name: 'code' },
                    { data: 'qty', name: 'qty' },
                    { data: 'previous_stock', name: 'previous_stock' },
                    { data: 'purchase_price', name: 'purchase_price' },
                    { 
                        data: 'discount_amount',
                        name: 'discount_amount',
                        render: function(data, type, row) {
                            return row.discount_type == 'percent' 
                                ? data + '%' 
                                : data;
                        }
                    },
                    { data: 'tax', name: 'tax' },
                    { data: 'created_at', name: 'created_at' },
                    { 
                        data: 'action', 
                        name: 'action', 
                        orderable: false, 
                        searchable: false 
                    }

                ]
            });
        });
    </script>
@endsection
