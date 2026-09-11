@extends('backend.master')
@section('page_title', $title)
@section('page_heading', $heading)
@section('content')
    <div class="card mb-3"><div class="card-body">
        <form method="POST" action="{{ $storeRoute }}">
            @csrf
            <div class="row">
                <div class="col-md-3 mb-2"><input name="name" class="form-control" placeholder="Name" required></div>
                <div class="col-md-2 mb-2"><input name="code" class="form-control" placeholder="Code"></div>
                @if (!empty($isBranch))
                    <div class="col-md-3 mb-2"><input name="address" class="form-control" placeholder="Address"></div>
                    <div class="col-md-2 mb-2"><input name="phone" class="form-control" placeholder="Phone"></div>
                @endif
                <div class="{{ !empty($isBranch) ? 'col-md-1' : 'col-md-6' }} mb-2"><input name="description" class="form-control" placeholder="Description"></div>
                <div class="col-md-1 mb-2"><button class="btn btn-success">Save</button></div>
            </div>
        </form>
    </div></div>

    <div class="card"><div class="card-body">
        <table class="table table-bordered table-sm">
            <tr>
                <th>Name</th><th>Code</th>
                @if (!empty($isBranch))<th>Address</th><th>Phone</th>@endif
                <th>Description</th><th>Status</th><th>Action</th>
            </tr>
            @foreach ($items as $item)
                <tr>
                    <form method="POST" action="{{ route($updateRouteName, $item) }}">
                        @csrf @method('PUT')
                        <td><input name="name" value="{{ $item->name }}" class="form-control" required></td>
                        <td><input name="code" value="{{ $item->code }}" class="form-control"></td>
                        @if (!empty($isBranch))
                            <td><input name="address" value="{{ $item->address }}" class="form-control"></td>
                            <td><input name="phone" value="{{ $item->phone }}" class="form-control"></td>
                        @endif
                        <td><input name="description" value="{{ $item->description }}" class="form-control"></td>
                        <td><label><input type="checkbox" name="is_active" value="1" {{ $item->is_active ? 'checked' : '' }}> Active</label></td>
                        <td>
                            <button class="btn btn-sm btn-info mb-1">Update</button>
                    </form>
                            <form method="POST" action="{{ route($destroyRouteName, $item) }}" onsubmit="return confirm('Delete this item?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                </tr>
            @endforeach
        </table>
        {{ $items->links() }}
    </div></div>
@endsection
