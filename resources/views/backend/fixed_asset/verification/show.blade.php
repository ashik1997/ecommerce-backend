@extends('backend.master')
@section('page_title','Verification Details')
@section('page_heading','Verification Details')
@section('header_css')@include('backend.fixed_asset._style')@endsection
@section('content')
<div class="fa-page-wrap">
    @include('backend.fixed_asset.partials.nav')
@include('backend.fixed_asset.partials.errors')
<div class="row mb-3">
    @foreach(['expected','found','missing','wrong_location','damaged'] as $key)
    <div class="col-md"><div class="fa-kpi p-3"><div class="label">{{ str_replace('_',' ', $key) }}</div><div class="value">{{ $summary[$key] ?? 0 }}</div></div></div>
    @endforeach
</div>
<div class="card mb-3"><div class="card-body">
    <div class="d-flex justify-content-between"><div><h4 class="fa-card-title">{{ $session->session_no }}</h4><p class="mb-0">{{ $session->title }} — {{ optional($session->warehouse)->title }}</p></div><span class="fa-badge {{ $session->status === 'completed' ? 'green' : 'amber' }}">{{ $session->status }}</span></div>
    @if($session->status === 'open')
    <hr><form class="row" method="post" action="{{ route('fixed-assets.verification.scan',$session) }}">@csrf
        <div class="col-md-4"><input name="asset_code" class="form-control" placeholder="Scan / type asset code" required autofocus></div>
        <div class="col-md-3"><select name="result" class="form-control"><option value="found">Found</option><option value="damaged">Damaged</option><option value="wrong_location">Wrong Location</option></select></div>
        <div class="col-md-3"><input name="note" class="form-control" placeholder="Optional note"></div>
        <div class="col-md-2"><button class="btn btn-fa-primary btn-block">Record</button></div>
    </form>
    <form method="post" action="{{ route('fixed-assets.verification.complete',$session) }}" class="mt-3" onsubmit="return confirm('Complete this verification session?')">@csrf<button class="btn btn-success btn-sm">Complete Session</button></form>
    @endif
</div></div>
<div class="card"><div class="card-body"><div class="table-responsive"><table class="table table-bordered table-hover"><thead><tr><th>Asset</th><th>Category</th><th>Location</th><th>Result</th><th>Scanned</th><th>Note / Update</th></tr></thead><tbody>
@forelse($items as $item)<tr><td><strong>{{ optional($item->asset)->asset_code }}</strong><br>{{ optional($item->asset)->asset_name }}</td><td>{{ optional(optional($item->asset)->category)->name }}</td><td>{{ optional(optional($item->asset)->location)->name }}</td><td><span class="fa-badge {{ $item->result === 'found' ? 'green' : ($item->result === 'missing' || $item->result === 'damaged' ? 'red' : 'amber') }}">{{ str_replace('_',' ', $item->result) }}</span></td><td>{{ optional($item->scanned_at)->format('d M Y h:i A') }}</td><td>@if($session->status === 'open')<form method="post" action="{{ route('fixed-assets.verification.items.update',$item) }}" class="row">@csrf<div class="col-md-4"><select name="result" class="form-control form-control-sm">@foreach(['expected','found','missing','wrong_location','damaged'] as $r)<option value="{{ $r }}" {{ $item->result === $r ? 'selected' : '' }}>{{ str_replace('_',' ', $r) }}</option>@endforeach</select></div><div class="col-md-5"><input name="note" value="{{ $item->note }}" class="form-control form-control-sm"></div><div class="col-md-3"><button class="btn btn-dark btn-sm">Update</button></div></form>@else {{ $item->note }} @endif</td></tr>@empty<tr><td colspan="6" class="text-center text-muted">No asset loaded for this session.</td></tr>@endforelse
</tbody></table></div>{{ $items->links() }}</div></div>
</div>
@endsection
