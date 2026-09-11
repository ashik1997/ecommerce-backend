@extends('backend.master')
@section('page_title', 'Salary Grades')
@section('page_heading', 'Salary Grades')
@section('content')
    <div class="card mb-3"><div class="card-body">
        <form method="POST" action="{{ route('hrat.salary-grades.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-3"><input name="grade_name" class="form-control" placeholder="Grade name" required></div>
                <div class="col-md-2"><input name="grade_code" class="form-control" placeholder="Code"></div>
                <div class="col-md-2"><input name="basic_salary" type="number" step="0.01" class="form-control" placeholder="Basic salary" required></div>
                <div class="col-md-4"><input name="description" class="form-control" placeholder="Description"></div>
                <div class="col-md-1"><button class="btn btn-success">Save</button></div>
            </div>
        </form>
    </div></div>
    <div class="card"><div class="card-body">
        <table class="table table-bordered table-sm">
            <tr><th>Name</th><th>Code</th><th>Basic Salary</th><th>Status</th><th>Action</th></tr>
            @foreach ($grades as $grade)
                <tr>
                    <form method="POST" action="{{ route('hrat.salary-grades.update', $grade) }}">
                        @csrf @method('PUT')
                        <td><input name="grade_name" value="{{ $grade->grade_name }}" class="form-control"></td>
                        <td><input name="grade_code" value="{{ $grade->grade_code }}" class="form-control"></td>
                        <td><input name="basic_salary" value="{{ $grade->basic_salary }}" type="number" step="0.01" class="form-control"></td>
                        <td><label><input type="checkbox" name="is_active" value="1" {{ $grade->is_active ? 'checked' : '' }}> Active</label></td>
                        <td><input type="hidden" name="description" value="{{ $grade->description }}"><button class="btn btn-sm btn-info">Update</button></td>
                    </form>
                </tr>
            @endforeach
        </table>
        {{ $grades->links() }}
    </div></div>
@endsection
