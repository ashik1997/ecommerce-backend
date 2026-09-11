@extends('backend.master')
@section('header_css')
    <link href="{{ url('assets') }}/plugins/select2/select2.min.css" rel="stylesheet" type="text/css" />
@endsection
@section('page_title', 'Employee Salary Assignments')
@section('page_heading', 'Employee Salary Assignments')
@section('content')
    @php
        $componentAmount = function ($assignment, $component) {
            if (optional($component->salaryComponent)->calculation_type === 'percentage') {
                return ((float) $assignment->basic_salary * (float) $component->amount) / 100;
            }

            return (float) $component->amount;
        };
    @endphp
    <div class="card mb-3"><div class="card-body">
        <form method="POST" action="{{ route('hrat.employee-salary-assignments.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-3 mb-2">
                    <label class="form-label">Employee <span class="text-danger">*</span></label>
                    @include('backend.hrat.partials.employee-select', ['employees' => $employees, 'required' => true])
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label">Salary Grade <span class="text-danger">*</span></label>
                    <select name="salary_grade_id" class="form-control" required><option value="">Grade</option>@foreach($grades as $grade)<option value="{{ $grade->id }}">{{ $grade->grade_name }}</option>@endforeach</select>
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label">Basic Salary <span class="text-danger">*</span></label>
                    <input name="basic_salary" type="number" step="0.01" class="form-control" placeholder="Basic salary" required>
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label">Effective From <span class="text-danger">*</span></label>
                    <input name="effective_from" type="date" class="form-control" required>
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label">Effective To</label>
                    <input name="effective_to" type="date" class="form-control">
                </div>
                <div class="col-md-1 mb-2 d-flex align-items-end"><button class="btn btn-success">Save</button></div>
            </div>
        </form>
    </div></div>
    <div class="card"><div class="card-body">
        <table class="table table-bordered table-sm">
            <tr><th>Employee</th><th>Grade</th><th>Basic</th><th>Components</th><th>Package Net</th><th>Effective</th><th>Status</th><th>Add Component</th></tr>
            @foreach ($assignments as $item)
                @php
                    $activeComponents = $item->components->filter(fn($row) => $row->is_active && $row->salaryComponent);
                    $allowances = $activeComponents->filter(fn($row) => optional($row->salaryComponent)->type === 'allowance')->sum(fn($row) => $componentAmount($item, $row));
                    $deductions = $activeComponents->filter(fn($row) => optional($row->salaryComponent)->type === 'deduction')->sum(fn($row) => $componentAmount($item, $row));
                    $net = $item->basic_salary + $allowances - $deductions;
                @endphp
                <tr>
                    <td>{{ $item->employee->name ?? 'N/A' }}</td>
                    <td>{{ $item->salaryGrade->grade_name ?? 'N/A' }}</td>
                    <td>৳{{ number_format($item->basic_salary, 2) }}</td>
                    <td>
                        @foreach ($item->components as $component)
                            <div class="mb-1">
                                {{ $component->salaryComponent->name ?? 'N/A' }}:
                                @if(optional($component->salaryComponent)->calculation_type === 'percentage')
                                    {{ number_format($component->amount, 2) }}% = ৳{{ number_format($componentAmount($item, $component), 2) }}
                                @else
                                    ৳{{ number_format($component->amount, 2) }}
                                @endif
                                <form method="POST" action="{{ route('hrat.employee-salary-assignments.components.destroy', [$item, $component]) }}" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-xs btn-danger">x</button>
                                </form>
                            </div>
                        @endforeach
                    </td>
                    <td>
                        <div>Allowance: ৳{{ number_format($allowances, 2) }}</div>
                        <div>Deduction: ৳{{ number_format($deductions, 2) }}</div>
                        <strong>Net: ৳{{ number_format($net, 2) }}</strong>
                    </td>
                    <td>{{ optional($item->effective_from)->format('Y-m-d') }} - {{ optional($item->effective_to)->format('Y-m-d') ?: 'Current' }}</td>
                    <td>{{ $item->is_active ? 'Active' : 'Inactive' }}</td>
                    <td style="min-width: 280px;">
                        <form method="POST" action="{{ route('hrat.employee-salary-assignments.components.store', $item) }}">
                            @csrf
                            <div class="input-group input-group-sm">
                                <select name="salary_component_id" class="form-control" required>
                                    <option value="">Component</option>
                                    @foreach ($salaryComponents as $component)
                                        <option value="{{ $component->id }}">{{ $component->name }} ({{ ucfirst($component->type) }} / {{ ucfirst($component->calculation_type) }})</option>
                                    @endforeach
                                </select>
                                <input type="number" step="0.01" name="amount" class="form-control" placeholder="Amount / %" required>
                                <div class="input-group-append"><button class="btn btn-info">Add</button></div>
                            </div>
                        </form>
                    </td>
                </tr>
            @endforeach
        </table>
        {{ $assignments->links() }}
    </div></div>
@endsection
@section('footer_js')
    <script src="{{ url('assets') }}/plugins/select2/select2.min.js"></script>
    <script>$('.select2').select2({ width: '100%' });</script>
@endsection
