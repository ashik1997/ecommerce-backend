@extends('backend.master')
@section('page_title','Commission Entries')
@section('page_heading','Commission Entries')
@section('content')
<div class="row mb-3">
    <div class="col-md-3"><div class="card"><div class="card-body"><small>Total</small><h4>{{ number_format($summary['total'] ?? 0, 2) }}</h4></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><small>Pending</small><h4>{{ number_format($summary['pending'] ?? 0, 2) }}</h4></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><small>Approved</small><h4>{{ number_format($summary['approved'] ?? 0, 2) }}</h4></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><small>Paid</small><h4>{{ number_format($summary['paid'] ?? 0, 2) }}</h4></div></div></div>
</div>
<div class="card"><div class="card-body">
    <h4 class="card-title mb-3">Commission Entries</h4>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    <div class="alert alert-info"><strong>Safety note:</strong> Paid/settled commission is never deleted. If reversed after payment, the system creates a pending deduction adjustment for the next settlement.</div>
    <form class="row mb-3" method="GET">
        <div class="col-md-2"><input type="date" name="from" class="form-control" value="{{ request('from') }}"></div>
        <div class="col-md-2"><input type="date" name="to" class="form-control" value="{{ request('to') }}"></div>
        <div class="col-md-2"><select name="commission_for" class="form-control"><option value="">All Type</option><option value="salesman" {{ request('commission_for')=='salesman'?'selected':'' }}>Salesman</option><option value="affiliate" {{ request('commission_for')=='affiliate'?'selected':'' }}>Affiliate</option></select></div>
        <div class="col-md-2"><select name="status" class="form-control"><option value="">All Status</option>@foreach(['pending','approved','settled','paid','partially_paid','reversed'] as $s)<option value="{{ $s }}" {{ request('status')==$s?'selected':'' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</select></div>
        <div class="col-md-2"><button class="btn btn-info btn-block">Filter</button></div>
        <div class="col-md-2"><a href="{{ route('sr.commission-entries.index') }}" class="btn btn-secondary btn-block">Reset</a></div>
    </form>
    <form method="POST" action="{{ route('sr.commission-entries.bulk-approve') }}">@csrf
    <div class="mb-2"><button class="btn btn-success btn-sm" onclick="return confirm('Approve selected pending entries?')">Bulk Approve Selected</button></div>
    <div class="table-responsive"><table class="table table-bordered table-striped">
        <thead><tr><th width="35"><input type="checkbox" onclick="document.querySelectorAll('.entry-check').forEach(cb=>cb.checked=this.checked)"></th><th>Date</th><th>Order</th><th>Type</th><th>Person</th><th>Base</th><th>Rule</th><th>Commission</th><th>Settlement</th><th>Status</th><th width="160">Action</th></tr></thead>
        <tbody>@forelse($entries as $entry)<tr>
            <td>@if($entry->status=='pending')<input class="entry-check" type="checkbox" name="entry_ids[]" value="{{ $entry->id }}">@endif</td>
            <td>{{ $entry->created_at ? $entry->created_at->format('Y-m-d') : '' }}</td>
            <td>{{ $entry->order->order_code ?? ('#'.$entry->product_order_id) }}<br><small>{{ $entry->source_status }}</small></td>
            <td>{{ ucfirst($entry->commission_for) }}</td>
            <td>{{ $entry->commission_for == 'salesman' ? ($entry->user->name ?? '—') : (($entry->affiliate->name ?? '—') . ' ' . ($entry->affiliate ? '('.$entry->affiliate->code.')' : '')) }}</td>
            <td>{{ ucfirst(str_replace('_',' ',$entry->commission_base)) }}<br>{{ number_format($entry->base_amount,2) }}</td>
            <td>{{ $entry->commission_type }} {{ number_format($entry->commission_value,2) }}</td>
            <td>{{ number_format($entry->commission_amount,2) }}</td>
            <td>@if($entry->settlement)<a href="{{ route('sr.commission-settlements.show',$entry->settlement->id) }}">{{ $entry->settlement->settlement_no }}</a>@else — @endif</td>
            <td><span class="badge badge-{{ $entry->status=='paid'?'success':($entry->status=='reversed'?'danger':($entry->status=='pending'?'warning':'info')) }}">{{ ucfirst(str_replace('_',' ',$entry->status)) }}</span></td>
            <td>@if($entry->status=='pending')<form class="d-inline" method="POST" action="{{ route('sr.commission-entries.approve',$entry->id) }}">@csrf<button type="submit" class="btn btn-success btn-sm">Approve</button></form>@endif @if($entry->status!='reversed')<form class="d-inline" method="POST" action="{{ route('sr.commission-entries.reverse',$entry->id) }}" onsubmit="return confirm('Reverse this commission? Paid/settled entries will create an adjustment instead of delete.')">@csrf<button type="submit" class="btn btn-danger btn-sm">Reverse</button></form>@endif @if($entry->order)<form class="d-inline" method="POST" action="{{ route('sr.commission-entries.order-recalculate',$entry->order->id) }}" onsubmit="return confirm('Recalculate commission for this order?')">@csrf<button type="submit" class="btn btn-warning btn-sm mt-1">Recalc Order</button></form>@endif</td>
        </tr>@empty<tr><td colspan="11" class="text-center">No commission entry found.</td></tr>@endforelse</tbody>
    </table></div></form>{{ $entries->links() }}
</div></div>
@endsection
