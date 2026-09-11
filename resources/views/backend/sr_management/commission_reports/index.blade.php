@extends('backend.master')
@section('page_title','Commission Report')
@section('page_heading','Commission Report')
@section('content')
<div class="card mb-3"><div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Commission Report</h4>
        <a href="{{ route('sr.commission-reports.export', request()->query()) }}" class="btn btn-success btn-sm">Export CSV</a>
    </div>
    <form method="GET" class="row">
        <div class="col-md-2 form-group"><label>From</label><input type="date" name="from" value="{{ request('from') }}" class="form-control"></div>
        <div class="col-md-2 form-group"><label>To</label><input type="date" name="to" value="{{ request('to') }}" class="form-control"></div>
        <div class="col-md-2 form-group"><label>Type</label><select name="commission_for" class="form-control"><option value="">All</option><option value="salesman" {{ request('commission_for')=='salesman'?'selected':'' }}>Salesman</option><option value="affiliate" {{ request('commission_for')=='affiliate'?'selected':'' }}>Affiliate</option></select></div>
        <div class="col-md-2 form-group"><label>Status</label><select name="status" class="form-control"><option value="">All</option>@foreach(['pending','approved','settled','paid','partially_paid','reversed'] as $s)<option value="{{ $s }}" {{ request('status')==$s?'selected':'' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</select></div>
        <div class="col-md-2 form-group"><label>Salesman</label><select name="user_id" class="form-control"><option value="">All</option>@foreach($users as $user)<option value="{{ $user->id }}" {{ request('user_id')==$user->id?'selected':'' }}>{{ $user->name }}</option>@endforeach</select></div>
        <div class="col-md-2 form-group"><label>Affiliate</label><select name="affiliate_id" class="form-control"><option value="">All</option>@foreach($affiliates as $affiliate)<option value="{{ $affiliate->id }}" {{ request('affiliate_id')==$affiliate->id?'selected':'' }}>{{ $affiliate->name }} ({{ $affiliate->code }})</option>@endforeach</select></div>
        <div class="col-md-2 form-group"><button class="btn btn-info btn-block">Filter</button></div>
        <div class="col-md-2 form-group"><a href="{{ route('sr.commission-reports.index') }}" class="btn btn-secondary btn-block">Reset</a></div>
    </form>
</div></div>

<div class="row mb-3">
    <div class="col-md-2"><div class="card border-info"><div class="card-body"><small>Total Earned</small><h4>{{ number_format($summary['earned'],2) }}</h4></div></div></div>
    <div class="col-md-2"><div class="card border-warning"><div class="card-body"><small>Pending</small><h4>{{ number_format($summary['pending'],2) }}</h4></div></div></div>
    <div class="col-md-2"><div class="card border-primary"><div class="card-body"><small>Approved</small><h4>{{ number_format($summary['approved'],2) }}</h4></div></div></div>
    <div class="col-md-2"><div class="card border-secondary"><div class="card-body"><small>Settled</small><h4>{{ number_format($summary['settled'],2) }}</h4></div></div></div>
    <div class="col-md-2"><div class="card border-success"><div class="card-body"><small>Paid</small><h4>{{ number_format($summary['paid'],2) }}</h4></div></div></div>
    <div class="col-md-2"><div class="card border-danger"><div class="card-body"><small>Due</small><h4>{{ number_format($summary['due'],2) }}</h4></div></div></div>
</div>


<div class="row mb-3">
    <div class="col-md-2"><div class="card border-dark"><div class="card-body"><small>Orders Impacted</small><h4>{{ number_format($profitSummary['orders'] ?? 0) }}</h4></div></div></div>
    <div class="col-md-2"><div class="card border-info"><div class="card-body"><small>Gross Profit</small><h4>{{ number_format($profitSummary['gross_profit'] ?? 0,2) }}</h4></div></div></div>
    <div class="col-md-2"><div class="card border-danger"><div class="card-body"><small>Commission Cost</small><h4>{{ number_format($profitSummary['commission_cost'] ?? 0,2) }}</h4></div></div></div>
    <div class="col-md-2"><div class="card border-warning"><div class="card-body"><small>Total Direct Cost</small><h4>{{ number_format($profitSummary['direct_expense_total'] ?? 0,2) }}</h4></div></div></div>
    <div class="col-md-2"><div class="card border-success"><div class="card-body"><small>Contribution Profit</small><h4>{{ number_format($profitSummary['contribution_profit'] ?? 0,2) }}</h4></div></div></div>
    <div class="col-md-2"><div class="card border-primary"><div class="card-body"><small>Mgmt Net Profit</small><h4>{{ number_format($profitSummary['management_net_profit'] ?? 0,2) }}</h4></div></div></div>
</div>

<div class="card mb-3"><div class="card-body">
    <h5>Salesman / Affiliate Summary</h5>
    <div class="table-responsive"><table class="table table-bordered table-striped">
        <thead><tr><th>Type</th><th>Person</th><th>Total Base</th><th>Total Commission</th><th>Entries</th></tr></thead>
        <tbody>@forelse($byPerson as $row)<tr>
            <td>{{ ucfirst($row->commission_for) }}</td>
            <td>{{ $row->commission_for == 'salesman' ? ($row->user->name ?? '—') : (($row->affiliate->name ?? '—') . ' ' . ($row->affiliate ? '('.$row->affiliate->code.')' : '')) }}</td>
            <td>{{ number_format($row->base_total, 2) }}</td>
            <td>{{ number_format($row->commission_total, 2) }}</td>
            <td>{{ $row->entries_count }}</td>
        </tr>@empty<tr><td colspan="5" class="text-center">No summary found.</td></tr>@endforelse</tbody>
    </table></div>
</div></div>

<div class="row">
    <div class="col-md-7"><div class="card"><div class="card-body"><h5>Recent Commission Entries</h5>
        <div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Date</th><th>Order</th><th>Type</th><th>Person</th><th>Gross Profit</th><th>Commission Cost</th><th>Net Profit</th><th>Status</th></tr></thead><tbody>
            @forelse($recentEntries as $entry)<tr>
                <td>{{ optional($entry->created_at)->format('Y-m-d') }}</td>
                <td>{{ $entry->order->order_code ?? ('#'.$entry->product_order_id) }}</td>
                <td>{{ ucfirst($entry->commission_for) }}</td>
                <td>{{ $entry->commission_for == 'salesman' ? ($entry->user->name ?? '—') : (($entry->affiliate->name ?? '—') . ' ' . ($entry->affiliate ? '('.$entry->affiliate->code.')' : '')) }}</td>
                <td>{{ number_format($entry->order->gross_profit ?? 0,2) }}</td>
                <td>{{ number_format($entry->commission_amount,2) }}</td>
                <td>{{ number_format($entry->order->net_profit ?? 0,2) }}</td>
                <td>{{ ucfirst(str_replace('_',' ',$entry->status)) }}</td>
            </tr>@empty<tr><td colspan="8" class="text-center">No entries found.</td></tr>@endforelse
        </tbody></table></div>
    </div></div></div>
    <div class="col-md-5"><div class="card"><div class="card-body"><h5>Recent Settlements</h5>
        <div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Date</th><th>No</th><th>Person</th><th>Paid</th><th>Due</th><th>Status</th></tr></thead><tbody>
            @forelse($recentSettlements as $settlement)<tr>
                <td>{{ optional($settlement->created_at)->format('Y-m-d') }}</td>
                <td><a href="{{ route('sr.commission-settlements.show',$settlement->id) }}">{{ $settlement->settlement_no }}</a></td>
                <td>{{ $settlement->settlement_for == 'salesman' ? ($settlement->user->name ?? '—') : (($settlement->affiliate->name ?? '—') . ' ' . ($settlement->affiliate ? '('.$settlement->affiliate->code.')' : '')) }}</td>
                <td>{{ number_format($settlement->paid_amount,2) }}</td>
                <td>{{ number_format($settlement->due_amount,2) }}</td>
                <td>{{ ucfirst(str_replace('_',' ',$settlement->status)) }}</td>
            </tr>@empty<tr><td colspan="6" class="text-center">No settlements found.</td></tr>@endforelse
        </tbody></table></div>
    </div></div></div>
</div>
@endsection
