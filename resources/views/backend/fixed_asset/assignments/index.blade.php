@extends('backend.master')
@section('header_css')
@include('backend.fixed_asset._style')
@endsection

@section('content')
<div class="page-content">
    <div class="container-fluid">
        @include('backend.fixed_asset.partials.nav')
        <div class="card">
            <div class="card-body">
                <h4>Assignments</h4>
                <table class="table table-bordered table-sm align-middle">
                    <thead><tr><th>No</th><th>Asset ID</th><th>Warehouse</th><th>Assign Type</th><th>Status</th><th>Assigned</th><th>Action</th></tr></thead>
                    <tbody>
                    @forelse($assignments as $a)
                        <tr>
                            <td>{{ $a->assignment_no }}</td>
                            <td>{{ $a->asset_id }}</td>
                            <td>{{ $a->warehouse_id }}</td>
                            <td>{{ ucfirst($a->assigned_to_type) }}</td>
                            <td><span class="badge bg-{{ $a->status === 'active' ? 'success' : ($a->status === 'returned' ? 'info' : 'secondary') }}">{{ ucfirst($a->status) }}</span></td>
                            <td>{{ $a->assigned_at }}</td>
                            <td>
                                @if($a->status === 'active')
                                    <div class="d-flex gap-1 flex-wrap">
                                        <form method="POST" action="{{ route('fixed-assets.assignments.return',$a) }}">
                                            @csrf
                                            <input type="hidden" name="returned_at" value="{{ now()->toDateString() }}">
                                            <input type="hidden" name="return_condition" value="good">
                                            <button class="btn btn-sm btn-success">Return Good</button>
                                        </form>
                                        <form method="POST" action="{{ route('fixed-assets.assignments.cancel',$a) }}" onsubmit="return confirm('Cancel this assignment?')">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-danger">Cancel</button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-muted">Closed</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">No assignment found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
                {{ $assignments->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
