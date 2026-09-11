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
                <h4>Transfers</h4>
                <table class="table table-bordered table-sm align-middle">
                    <thead><tr><th>No</th><th>Asset</th><th>From</th><th>To</th><th>Date</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody>
                    @forelse($transfers as $t)
                        <tr>
                            <td>{{ $t->transfer_no }}</td>
                            <td>{{ $t->asset_id }}</td>
                            <td>{{ $t->from_warehouse_id }}</td>
                            <td>{{ $t->to_warehouse_id }}</td>
                            <td>{{ $t->transfer_date }}</td>
                            <td><span class="badge bg-{{ $t->status === 'received' ? 'success' : ($t->status === 'cancelled' ? 'secondary' : 'warning') }}">{{ ucfirst($t->status) }}</span></td>
                            <td>
                                @if($t->status === 'dispatched')
                                    <div class="d-flex gap-1 flex-wrap">
                                        <form method="POST" action="{{ route('fixed-assets.transfers.receive',$t) }}">
                                            @csrf
                                            <input type="hidden" name="received_date" value="{{ now()->toDateString() }}">
                                            <input type="hidden" name="condition_at_receive" value="good">
                                            <button class="btn btn-sm btn-success">Receive Good</button>
                                        </form>
                                        <form method="POST" action="{{ route('fixed-assets.transfers.cancel',$t) }}" onsubmit="return confirm('Cancel this transfer?')">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-danger">Cancel</button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-muted">No action</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">No transfer found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
                {{ $transfers->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
