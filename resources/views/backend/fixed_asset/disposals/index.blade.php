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
                <h4>Disposals</h4>
                <table class="table table-bordered table-sm align-middle">
                    <thead>
                    <tr>
                        <th>No</th>
                        <th>Asset</th>
                        <th>Warehouse</th>
                        <th>Date</th>
                        <th>Carrying</th>
                        <th>Proceeds</th>
                        <th>Gain/Loss</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($disposals as $d)
                        <tr>
                            <td>{{ $d->disposal_no }}</td>
                            <td>{{ $d->asset_id }}</td>
                            <td>{{ $d->warehouse_id }}</td>
                            <td>{{ $d->disposal_date }}</td>
                            <td>{{ number_format($d->carrying_amount,2) }}</td>
                            <td>{{ number_format($d->proceeds_amount,2) }}</td>
                            <td>{{ number_format($d->gain_loss_amount,2) }}</td>
                            <td><span class="badge bg-{{ $d->status === 'completed' ? 'success' : ($d->status === 'cancelled' ? 'secondary' : 'warning') }}">{{ ucfirst($d->status) }}</span></td>
                            <td>
                                @if($d->status === 'pending')
                                    <div class="d-flex gap-1">
                                        <form method="POST" action="{{ route('fixed-assets.disposals.approve', $d) }}" onsubmit="return confirm('Approve disposal and retire this asset?')">
                                            @csrf
                                            <button class="btn btn-sm btn-success">Approve</button>
                                        </form>
                                        <form method="POST" action="{{ route('fixed-assets.disposals.cancel', $d) }}" onsubmit="return confirm('Cancel this disposal request?')">
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
                        <tr><td colspan="9" class="text-center text-muted">No disposal found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
                {{ $disposals->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
