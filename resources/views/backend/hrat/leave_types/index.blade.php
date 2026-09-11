@extends('backend.master')
@section('page_title', 'Leave Types')
@section('page_heading', 'Leave Types')
@section('content')
    <div class="card mb-3"><div class="card-body">
        <form method="POST" action="{{ route('hrat.leave-types.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-3 mb-2"><input name="name" class="form-control" placeholder="Leave type name" required></div>
                <div class="col-md-2 mb-2"><input name="code" class="form-control" placeholder="Code"></div>
                <div class="col-md-2 mb-2"><input type="number" name="annual_days" value="0" min="0" class="form-control" placeholder="Annual days" required></div>
                <div class="col-md-3 mb-2"><input name="description" class="form-control" placeholder="Description"></div>
                <div class="col-md-1 mb-2 d-flex align-items-center"><label><input type="checkbox" name="is_paid" value="1" checked> Paid</label></div>
                <div class="col-md-1 mb-2"><button class="btn btn-success">Save</button></div>
            </div>
        </form>
    </div></div>

    <div class="card"><div class="card-body">
        <table class="table table-bordered table-sm">
            <tr><th>Name</th><th>Code</th><th>Annual Days</th><th>Paid</th><th>Description</th><th>Status</th><th>Action</th></tr>
            @foreach ($leaveTypes as $type)
                <tr>
                    <form method="POST" action="{{ route('hrat.leave-types.update', $type) }}">
                        @csrf @method('PUT')
                        <td><input name="name" value="{{ $type->name }}" class="form-control" required></td>
                        <td><input name="code" value="{{ $type->code }}" class="form-control"></td>
                        <td><input type="number" name="annual_days" value="{{ $type->annual_days }}" min="0" class="form-control" required></td>
                        <td><label><input type="checkbox" name="is_paid" value="1" {{ $type->is_paid ? 'checked' : '' }}> Paid</label></td>
                        <td><input name="description" value="{{ $type->description }}" class="form-control"></td>
                        <td><label><input type="checkbox" name="is_active" value="1" {{ $type->is_active ? 'checked' : '' }}> Active</label></td>
                        <td>
                            <button class="btn btn-sm btn-info mb-1">Update</button>
                    </form>
                            <form method="POST" action="{{ route('hrat.leave-types.destroy', $type) }}" onsubmit="return confirm('Delete this leave type?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                </tr>
            @endforeach
        </table>
        {{ $leaveTypes->links() }}
    </div></div>
@endsection
