@extends('backend.master')
@section('page_title','Commission Rules')
@section('page_heading','Commission Rules')
@section('content')
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between mb-3">
            <div>
                <h4 class="mb-1">Commission Rules</h4>
                <small class="text-muted">Lower priority number wins when more than one active rule can match the same order.</small>
            </div>
            <a href="{{ route('sr.commission-rules.create') }}" class="btn btn-primary btn-sm">Add Rule</a>
        </div>

        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if(session('warning'))<div class="alert alert-warning">{{ session('warning') }}</div>@endif
        @if(($activeConflictCount ?? 0) > 0)
            <div class="alert alert-warning">
                <strong>{{ $activeConflictCount }}</strong> active rule(s) may overlap with another active rule. This is allowed, but please confirm priority order.
            </div>
        @endif

        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Name</th><th>For</th><th>Scope</th><th>Base</th><th>Commission</th><th>Priority</th><th>Status</th><th width="150">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rules as $rule)
                    <tr>
                        <td>{{ $rule->rule_name }}</td>
                        <td>{{ ucfirst($rule->commission_for) }}</td>
                        <td>{{ $rule->user->name ?? $rule->affiliate->name ?? 'All' }}</td>
                        <td>{{ ucfirst(str_replace('_',' ',$rule->commission_base)) }}</td>
                        <td>{{ $rule->commission_type }}: {{ number_format($rule->commission_value,2) }}</td>
                        <td><span class="badge badge-info">{{ $rule->priority }}</span></td>
                        <td><span class="badge badge-{{ $rule->status=='active'?'success':'secondary' }}">{{ $rule->status }}</span></td>
                        <td>
                            <a class="btn btn-warning btn-sm" href="{{ route('sr.commission-rules.edit',$rule->id) }}">Edit</a>
                            <form method="POST" action="{{ route('sr.commission-rules.destroy',$rule->id) }}" class="d-inline" onsubmit="return confirm('Delete rule?')">
                                @csrf @method('DELETE')<button class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center">No rule found.</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $rules->links() }}
    </div>
</div>
@endsection
