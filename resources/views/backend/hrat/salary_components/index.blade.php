@extends('backend.master')
@section('page_title', 'Salary Components')
@section('page_heading', 'Salary Components')
@section('content')
    <div class="card mb-3"><div class="card-body">
        <form method="POST" action="{{ route('hrat.salary-components.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-3 mb-2"><input name="name" class="form-control" placeholder="Component name" required></div>
                <div class="col-md-2 mb-2"><input name="code" class="form-control" placeholder="Code"></div>
                <div class="col-md-2 mb-2">
                    <select name="type" class="form-control" required>
                        <option value="allowance">Allowance</option>
                        <option value="deduction">Deduction</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <select name="calculation_type" class="form-control" required>
                        <option value="fixed">Fixed</option>
                        <option value="percentage">Percentage</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2"><input type="number" step="0.01" name="default_amount" value="0" class="form-control" placeholder="Default amount" required></div>
                <div class="col-md-1 mb-2 d-flex align-items-center"><label><input type="checkbox" name="is_taxable" value="1"> Tax</label></div>
                <div class="col-md-11 mb-2"><input name="description" class="form-control" placeholder="Description"></div>
                <div class="col-md-1 mb-2"><button class="btn btn-success">Save</button></div>
            </div>
        </form>
    </div></div>

    <div class="card"><div class="card-body">
        <table class="table table-bordered table-sm">
            <tr><th>Name</th><th>Code</th><th>Type</th><th>Calc</th><th>Default</th><th>Taxable</th><th>Status</th><th>Action</th></tr>
            @foreach ($components as $component)
                <tr>
                    <form method="POST" action="{{ route('hrat.salary-components.update', $component) }}">
                        @csrf @method('PUT')
                        <td><input name="name" value="{{ $component->name }}" class="form-control" required></td>
                        <td><input name="code" value="{{ $component->code }}" class="form-control"></td>
                        <td><select name="type" class="form-control"><option value="allowance" {{ $component->type === 'allowance' ? 'selected' : '' }}>Allowance</option><option value="deduction" {{ $component->type === 'deduction' ? 'selected' : '' }}>Deduction</option></select></td>
                        <td><select name="calculation_type" class="form-control"><option value="fixed" {{ $component->calculation_type === 'fixed' ? 'selected' : '' }}>Fixed</option><option value="percentage" {{ $component->calculation_type === 'percentage' ? 'selected' : '' }}>Percentage</option></select></td>
                        <td><input type="number" step="0.01" name="default_amount" value="{{ $component->default_amount }}" class="form-control" required></td>
                        <td><label><input type="checkbox" name="is_taxable" value="1" {{ $component->is_taxable ? 'checked' : '' }}> Tax</label></td>
                        <td><label><input type="checkbox" name="is_active" value="1" {{ $component->is_active ? 'checked' : '' }}> Active</label></td>
                        <td>
                            <input type="hidden" name="description" value="{{ $component->description }}">
                            <button class="btn btn-sm btn-info mb-1">Update</button>
                    </form>
                            <form method="POST" action="{{ route('hrat.salary-components.destroy', $component) }}" onsubmit="return confirm('Delete this salary component?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                </tr>
            @endforeach
        </table>
        {{ $components->links() }}
    </div></div>
@endsection
