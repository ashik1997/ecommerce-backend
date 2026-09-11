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
                <h4>Maintenance Jobs</h4>
                <table class="table table-bordered table-sm align-middle">
                    <thead><tr><th>No</th><th>Asset</th><th>Type</th><th>Title</th><th>Status</th><th>Cost</th><th>Action</th></tr></thead>
                    <tbody>
                    @forelse($jobs as $j)
                        <tr>
                            <td>{{ $j->job_no }}</td>
                            <td>{{ $j->asset_id }}</td>
                            <td>{{ $j->type }}</td>
                            <td>{{ $j->title }}</td>
                            <td><span class="badge bg-{{ $j->status === 'completed' ? 'success' : ($j->status === 'cancelled' ? 'secondary' : 'warning') }}">{{ ucfirst(str_replace('_',' ', $j->status)) }}</span></td>
                            <td>{{ number_format($j->total_cost,2) }}</td>
                            <td>
                                <div class="d-flex gap-1 flex-wrap">
                                    @if($j->status === 'open')
                                        <form method="POST" action="{{ route('fixed-assets.maintenance.start',$j) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-primary">Start</button>
                                        </form>
                                    @endif
                                    @if(in_array($j->status, ['open','in_progress']))
                                        <form method="POST" action="{{ route('fixed-assets.maintenance.complete',$j) }}">
                                            @csrf
                                            <input type="hidden" name="completed_at" value="{{ now()->toDateString() }}">
                                            <input type="hidden" name="condition_status" value="good">
                                            <button class="btn btn-sm btn-success">Complete</button>
                                        </form>
                                        <form method="POST" action="{{ route('fixed-assets.maintenance.cancel',$j) }}" onsubmit="return confirm('Cancel this maintenance job?')">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-danger">Cancel</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">No maintenance job found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
                {{ $jobs->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
