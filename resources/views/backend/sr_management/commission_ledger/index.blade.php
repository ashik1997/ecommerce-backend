@extends('backend.master')
@section('page_title','Commission Ledger')
@section('page_heading','Commission Ledger')
@section('content')
<div class="card mb-3"><div class="card-body"><h4>Commission Ledger</h4>
<form method="GET" class="row">
    <div class="col-md-3 form-group"><label>Ledger For</label><select name="ledger_for" class="form-control"><option value="salesman" {{ request('ledger_for')=='salesman'?'selected':'' }}>Salesman</option><option value="affiliate" {{ request('ledger_for')=='affiliate'?'selected':'' }}>Affiliate</option></select></div>
    <div class="col-md-4 form-group"><label>Salesman</label><select name="user_id" class="form-control"><option value="">Select</option>@foreach($users as $user)<option value="{{ $user->id }}" {{ request('user_id')==$user->id?'selected':'' }}>{{ $user->name }}</option>@endforeach</select></div>
    <div class="col-md-4 form-group"><label>Affiliate</label><select name="affiliate_id" class="form-control"><option value="">Select</option>@foreach($affiliates as $affiliate)<option value="{{ $affiliate->id }}" {{ request('affiliate_id')==$affiliate->id?'selected':'' }}>{{ $affiliate->name }} ({{ $affiliate->code }})</option>@endforeach</select></div>
    <div class="col-md-1 form-group d-flex align-items-end"><button class="btn btn-info">View</button></div>
</form></div></div>
@if($ledger)
<div class="row mb-3">
    <div class="col-md-3"><div class="card border-info"><div class="card-body"><small>Earned</small><h4>{{ number_format($ledger['earned'],2) }}</h4></div></div></div>
    <div class="col-md-3"><div class="card border-success"><div class="card-body"><small>Paid</small><h4>{{ number_format($ledger['paid'],2) }}</h4></div></div></div>
    <div class="col-md-3"><div class="card border-warning"><div class="card-body"><small>Adjustments</small><h4>{{ number_format($ledger['addition'] - $ledger['deduction'],2) }}</h4></div></div></div>
    <div class="col-md-3"><div class="card border-primary"><div class="card-body"><small>Closing Due</small><h4>{{ number_format($ledger['closing_due'],2) }}</h4></div></div></div>
</div>
<div class="card"><div class="card-body"><h5>Recent Commission Entries</h5><table class="table table-bordered"><thead><tr><th>Date</th><th>Order</th><th>Amount</th><th>Status</th></tr></thead><tbody>@foreach($ledger['recent_entries'] as $entry)<tr><td>{{ $entry->created_at->format('Y-m-d') }}</td><td>#{{ $entry->product_order_id }}</td><td>{{ number_format($entry->commission_amount,2) }}</td><td>{{ $entry->status }}</td></tr>@endforeach</tbody></table></div></div>
@endif
@endsection
