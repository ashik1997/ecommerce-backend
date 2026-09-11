@extends('backend.master')
@section('page_title','Create Commission Settlement')
@section('page_heading','Create Commission Settlement')
@section('content')
<div class="card mb-3"><div class="card-body"><h4>Generate Settlement Preview</h4>
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
<form method="GET" class="row">
    <div class="col-md-2 form-group"><label>For</label><select name="settlement_for" class="form-control" required><option value="salesman" {{ request('settlement_for')=='salesman'?'selected':'' }}>Salesman</option><option value="affiliate" {{ request('settlement_for')=='affiliate'?'selected':'' }}>Affiliate</option></select></div>
    <div class="col-md-3 form-group"><label>Salesman</label><select name="user_id" class="form-control"><option value="">Select</option>@foreach($users as $user)<option value="{{ $user->id }}" {{ request('user_id')==$user->id?'selected':'' }}>{{ $user->name }}</option>@endforeach</select></div>
    <div class="col-md-3 form-group"><label>Affiliate</label><select name="affiliate_id" class="form-control"><option value="">Select</option>@foreach($affiliates as $affiliate)<option value="{{ $affiliate->id }}" {{ request('affiliate_id')==$affiliate->id?'selected':'' }}>{{ $affiliate->name }} ({{ $affiliate->code }})</option>@endforeach</select></div>
    <div class="col-md-2 form-group"><label>From</label><input type="date" name="period_start" class="form-control" value="{{ request('period_start') }}" required></div>
    <div class="col-md-2 form-group"><label>To</label><input type="date" name="period_end" class="form-control" value="{{ request('period_end') }}" required></div>
    <div class="col-md-12"><button class="btn btn-info">Preview</button><a href="{{ route('sr.commission-settlements.index') }}" class="btn btn-secondary">Back</a></div>
</form></div></div>
@if($preview)
@php $netPayable = max(0, $preview['total_commission'] - $preview['adjustment_total']); @endphp
<div class="card"><div class="card-body"><h4>Preview</h4><p>Total commission: <strong>{{ number_format($preview['total_commission'],2) }}</strong> | Pending adjustment: <strong>{{ number_format($preview['adjustment_total'],2) }}</strong> | Net payable: <strong>{{ number_format($netPayable,2) }}</strong></p>
<form method="POST" action="{{ route('sr.commission-settlements.store') }}">@csrf
<input type="hidden" name="settlement_for" value="{{ request('settlement_for') }}"><input type="hidden" name="user_id" value="{{ request('user_id') }}"><input type="hidden" name="affiliate_id" value="{{ request('affiliate_id') }}"><input type="hidden" name="period_start" value="{{ request('period_start') }}"><input type="hidden" name="period_end" value="{{ request('period_end') }}">
<div class="row"><div class="col-md-3 form-group"><label>Manual Deduction / Adjustment</label><input type="number" step="0.01" name="adjustment_amount" class="form-control" value="0"></div><div class="col-md-3 form-group"><label>Paid Amount</label><input type="number" step="0.01" name="paid_amount" class="form-control" value="{{ $netPayable }}"></div><div class="col-md-2 form-group"><label>Payment Method ID</label><input type="number" name="payment_method_id" class="form-control"></div><div class="col-md-4 form-group"><label>Note</label><input type="text" name="note" class="form-control"></div></div>
<button name="submit_action" value="draft" class="btn btn-warning">Save Draft</button>
<button name="submit_action" value="approve" class="btn btn-primary">Approve Settlement</button>
<button name="submit_action" value="pay" class="btn btn-success" onclick="return confirm('Create and mark this settlement paid?')">Create & Pay</button>
</form>
<hr><table class="table table-bordered"><thead><tr><th>Order</th><th>Date</th><th>Amount</th></tr></thead><tbody>@foreach($preview['entries'] as $entry)<tr><td>{{ $entry->order->order_code ?? '#'.$entry->product_order_id }}</td><td>{{ $entry->created_at->format('Y-m-d') }}</td><td>{{ number_format($entry->commission_amount,2) }}</td></tr>@endforeach</tbody></table>
@if($preview['entries']->isEmpty())<p class="text-muted">No approved unpaid commission entry found.</p>@endif
</div></div>
@endif
@endsection
