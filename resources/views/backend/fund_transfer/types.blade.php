@extends('backend.master')

@section('header_css')
    <link href="{{ url('dataTable') }}/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="{{ url('dataTable') }}/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <style>table.dataTable tbody td { text-align: center !important; }</style>
@endsection

@section('page_title')
    Transfer Types
@endsection
@section('page_heading')
    Transfer Types
@endsection

@section('content')
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Transfer Type</h4>
                    <form method="POST" action="{{ route('StoreFundTransferType') }}">
                        @csrf
                        <input type="hidden" name="id" id="type_id">
                        <div class="form-group">
                            <label>Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="type_name" class="form-control" placeholder="Cash to Bank">
                            @error('name') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="form-group">
                            <label>Note</label>
                            <textarea name="note" id="type_note" class="form-control" rows="3"></textarea>
                        </div>
                        <button class="btn btn-primary" type="submit"><i class="fas fa-save"></i> Save</button>
                        <button class="btn btn-light" type="button" id="resetForm">Reset</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title mb-1">All Transfer Types</h4>
                        <a href="{{ route('ViewAllFundTransfer') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i></a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered data-table">
                            <thead>
                                <tr>
                                    <th>SL</th>
                                    <th>Name</th>
                                    <th>Note</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('footer_js')
    <script src="{{ url('dataTable') }}/js/jquery.dataTables.min.js"></script>
    <script src="{{ url('dataTable') }}/js/dataTables.bootstrap4.min.js"></script>
    <script>
        var table = $(".data-table").DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('ViewAllFundTransferType') }}",
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'name', name: 'name'},
                {data: 'note', name: 'note'},
                {data: 'action', name: 'action', orderable: false, searchable: false}
            ]
        });

        $('body').on('click', '.edit-type', function() {
            $('#type_id').val($(this).data('id'));
            $('#type_name').val($(this).data('name'));
            $('#type_note').val($(this).data('note'));
        });

        $('#resetForm').on('click', function() {
            $('#type_id, #type_name, #type_note').val('');
        });

        $('body').on('click', '.delete-type', function(e) {
            e.preventDefault();
            if (!confirm('Are you sure want to delete?')) {
                return;
            }
            $.get($(this).attr('href'), function() {
                table.draw(false);
                toastr.error('Transfer type deleted', 'Deleted');
            });
        });
    </script>
@endsection
