@extends('backend.master')
@section('header_css')
@include('backend.fixed_asset._style')
@endsection

@section('content')
<div class="page-content">
    <div class="container-fluid">
        @include('backend.fixed_asset.partials.nav')

        <div class="card mb-3">
            <div class="card-body">
                <h4>Run Depreciation</h4>
                @include('backend.fixed_asset.partials.errors')
                <form method="POST" action="{{ route('fixed-assets.depreciation.preview') }}" class="row g-2">
                    @csrf
                    <div class="col-md-3">
                        <input type="month" name="period" class="form-control" value="{{ now()->format('Y-m') }}" required>
                    </div>
                    <div class="col-md-4">
                        <select name="warehouse_id" class="form-control">
                            <option value="">All Warehouses</option>
                            @foreach($warehouses as $w)
                                <option value="{{ $w->id }}">{{ $w->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-fa-primary">Preview</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h4>Depreciation Runs</h4>
                <table class="table table-bordered table-sm align-middle">
                    <thead>
                    <tr>
                        <th>No</th>
                        <th>Period</th>
                        <th>Warehouse</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($runs as $run)
                        <tr>
                            <td>{{ $run->run_no }}</td>
                            <td>{{ $run->period }}</td>
                            <td>{{ $run->warehouse_id ?: 'All' }}</td>
                            <td>{{ number_format($run->total_depreciation,2) }}</td>
                            <td><span class="badge bg-{{ $run->status === 'posted' ? 'success' : ($run->status === 'reversed' ? 'warning' : 'secondary') }}">{{ ucfirst($run->status) }}</span></td>
                            <td>
                                @if($run->status === 'posted')
                                    <form method="POST" action="{{ route('fixed-assets.depreciation.reverse', $run) }}" onsubmit="return confirm('Reverse this depreciation run?')">
                                        @csrf
                                        <button class="btn btn-sm btn-warning">Reverse</button>
                                    </form>
                                @else
                                    <span class="text-muted">No action</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted">No depreciation run found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
                {{ $runs->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
