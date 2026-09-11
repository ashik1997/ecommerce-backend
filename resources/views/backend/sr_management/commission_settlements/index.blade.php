@extends('backend.master')
@section('page_title','Commission Settlements')
@section('page_heading','Commission Settlements')
@section('content')
<div class="row mb-3">
    <div class="col-md-4"><div class="card"><div class="card-body"><small>Total Payable</small><h4>{{ number_format($summary['payable'] ?? 0, 2) }}</h4></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-body"><small>Total Paid</small><h4>{{ number_format($summary['paid'] ?? 0, 2) }}</h4></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-body"><small>Total Due</small><h4>{{ number_format($summary['due'] ?? 0, 2) }}</h4></div></div></div>
</div>
<div class="alert alert-info py-2"><strong>Accounting:</strong> Approved settlement posts <code>Dr Sales Commission Expense</code> and <code>Cr Commission Payable</code>. Fully paid settlement posts <code>Dr Commission Payable</code> and <code>Cr Cash/Bank</code>.</div>
<div class="card"><div class="card-body"><div class="d-flex justify-content-between mb-3"><h4>Commission Settlements</h4><a href="{{ route('sr.commission-settlements.create') }}" class="btn btn-primary btn-sm">Create Settlement</a></div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
<form class="row mb-3" method="GET">
    <div class="col-md-2"><input type="date" name="from" value="{{ request('from') }}" class="form-control"></div>
    <div class="col-md-2"><input type="date" name="to" value="{{ request('to') }}" class="form-control"></div>
    <div class="col-md-2"><select name="settlement_for" class="form-control"><option value="">All Type</option><option value="salesman" {{ request('settlement_for')=='salesman'?'selected':'' }}>Salesman</option><option value="affiliate" {{ request('settlement_for')=='affiliate'?'selected':'' }}>Affiliate</option></select></div>
    <div class="col-md-2"><select name="status" class="form-control"><option value="">All Status</option>@foreach(['draft','approved','paid','partially_paid','cancelled'] as $s)<option value="{{ $s }}" {{ request('status')==$s?'selected':'' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</select></div>
    <div class="col-md-2"><button class="btn btn-info btn-block">Filter</button></div>
    <div class="col-md-2"><a href="{{ route('sr.commission-settlements.index') }}" class="btn btn-secondary btn-block">Reset</a></div>
</form>
<div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>No</th><th>For</th><th>Person</th><th>Period</th><th>Total</th><th>Adjustment</th><th>Payable</th><th>Paid</th><th>Due</th><th>Status</th><th>Accounting</th><th>Action</th></tr></thead><tbody>
@forelse($settlements as $settlement)<tr><td>{{ $settlement->settlement_no }}</td><td>{{ ucfirst($settlement->settlement_for) }}</td><td>{{ $settlement->settlement_for=='salesman' ? ($settlement->user->name ?? '—') : ($settlement->affiliate->name ?? '—') }}</td><td>{{ $settlement->period_start->format('Y-m-d') }} to {{ $settlement->period_end->format('Y-m-d') }}</td><td>{{ number_format($settlement->total_commission,2) }}</td><td>{{ number_format($settlement->adjustment_amount,2) }}</td><td>{{ number_format($settlement->payable_amount,2) }}</td><td>{{ number_format($settlement->paid_amount,2) }}</td><td>{{ number_format($settlement->due_amount,2) }}</td><td><span class="badge badge-{{ $settlement->status=='paid'?'success':($settlement->status=='cancelled'?'danger':($settlement->status=='draft'?'warning':'info')) }}">{{ ucfirst(str_replace('_',' ',$settlement->status)) }}</span></td><td><span class="badge badge-light">{{ ucfirst(str_replace('_',' ',$settlement->accounting_status ?? 'pending')) }}</span></td><td><a href="{{ route('sr.commission-settlements.show',$settlement->id) }}" class="btn btn-info btn-sm">View</a></td></tr>
@empty<tr><td colspan="12" class="text-center">No settlement found.</td></tr>@endforelse
</tbody></table></div>{{ $settlements->links() }}</div></div>
@endsection
