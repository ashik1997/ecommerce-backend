@extends('backend.master')
@section('page_title', 'Holiday Calendar')
@section('page_heading', 'Holiday Calendar')
@section('content')
    <div class="card mb-3"><div class="card-body">
        <form method="POST" action="{{ route('hrat.holidays.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-3 mb-2"><label>Title <span class="text-danger">*</span></label><input name="title" class="form-control" placeholder="Holiday title" required></div>
                <div class="col-md-2 mb-2"><label>Date <span class="text-danger">*</span></label><input type="date" name="holiday_date" class="form-control" required></div>
                <div class="col-md-2 mb-2">
                    <label>Type <span class="text-danger">*</span></label>
                    <select name="type" class="form-control" required>
                        <option value="company">Company Holiday</option>
                        <option value="public">Public Holiday</option>
                        <option value="special_working_day">Special Working Day</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2 d-flex align-items-end"><label><input type="checkbox" name="is_working_day_override" value="1"> Working Day</label></div>
                <div class="col-md-2 mb-2"><label>Description</label><input name="description" class="form-control" placeholder="Description"></div>
                <div class="col-md-1 mb-2 d-flex align-items-end"><button class="btn btn-success">Save</button></div>
            </div>
        </form>
    </div></div>

    <div class="card"><div class="card-body">
        <table class="table table-bordered table-sm">
            <tr><th>Date</th><th>Title</th><th>Type</th><th>Working Override</th><th>Description</th><th>Status</th><th>Action</th></tr>
            @foreach ($holidays as $holiday)
                <tr>
                    <form method="POST" action="{{ route('hrat.holidays.update', $holiday) }}">
                        @csrf @method('PUT')
                        <td><input type="date" name="holiday_date" value="{{ optional($holiday->holiday_date)->format('Y-m-d') }}" class="form-control" required></td>
                        <td><input name="title" value="{{ $holiday->title }}" class="form-control" required></td>
                        <td>
                            <select name="type" class="form-control" required>
                                @foreach (['company' => 'Company', 'public' => 'Public', 'special_working_day' => 'Special Working Day'] as $value => $label)
                                    <option value="{{ $value }}" {{ $holiday->type === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><label><input type="checkbox" name="is_working_day_override" value="1" {{ $holiday->is_working_day_override ? 'checked' : '' }}> Working</label></td>
                        <td><input name="description" value="{{ $holiday->description }}" class="form-control"></td>
                        <td><label><input type="checkbox" name="is_active" value="1" {{ $holiday->is_active ? 'checked' : '' }}> Active</label></td>
                        <td>
                            <button class="btn btn-sm btn-info mb-1">Update</button>
                    </form>
                            <form method="POST" action="{{ route('hrat.holidays.destroy', $holiday) }}" onsubmit="return confirm('Delete this holiday?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                </tr>
            @endforeach
        </table>
        {{ $holidays->links() }}
    </div></div>
@endsection
