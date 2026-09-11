@extends('backend.master')
@section('page_title','Affiliate Partners')
@section('page_heading','Affiliate Partners')
@section('content')
<div class="card"><div class="card-body">
    <div class="d-flex justify-content-between mb-3">
        <h4 class="card-title">Affiliate Partners</h4>
        <a href="{{ route('sr.affiliates.create') }}" class="btn btn-primary btn-sm">Add Affiliate</a>
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <form class="row mb-3" method="GET">
        <div class="col-md-5"><input type="text" name="q" class="form-control" placeholder="Search name, phone, email, code" value="{{ request('q') }}"></div>
        <div class="col-md-3"><select name="status" class="form-control"><option value="">All Status</option><option value="active" {{ request('status')=='active'?'selected':'' }}>Active</option><option value="inactive" {{ request('status')=='inactive'?'selected':'' }}>Inactive</option></select></div>
        <div class="col-md-2"><button class="btn btn-info btn-block">Filter</button></div>
    </form>
    <div class="table-responsive"><table class="table table-bordered table-striped">
        <thead><tr><th>Name</th><th>Code</th><th>Contact</th><th>Default Commission</th><th>Status</th><th width="150">Action</th></tr></thead>
        <tbody>@forelse($affiliates as $affiliate)<tr>
            <td>{{ $affiliate->name }}</td><td><span class="badge badge-dark">{{ $affiliate->code }}</span></td>
            <td>{{ $affiliate->phone }}<br><small>{{ $affiliate->email }}</small></td>
            <td>{{ ucfirst(str_replace('_',' ', $affiliate->default_commission_base)) }} / {{ $affiliate->default_commission_type }}: {{ number_format($affiliate->default_commission_value,2) }}</td>
            <td><span class="badge badge-{{ $affiliate->status=='active'?'success':'secondary' }}">{{ ucfirst($affiliate->status) }}</span></td>
            <td><a href="{{ route('sr.affiliates.edit',$affiliate->id) }}" class="btn btn-warning btn-sm">Edit</a>
                <form action="{{ route('sr.affiliates.destroy',$affiliate->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this affiliate?')">@csrf @method('DELETE')<button class="btn btn-danger btn-sm">Delete</button></form></td>
        </tr>@empty<tr><td colspan="6" class="text-center">No affiliate found.</td></tr>@endforelse</tbody>
    </table></div>{{ $affiliates->links() }}
</div></div>
@endsection
