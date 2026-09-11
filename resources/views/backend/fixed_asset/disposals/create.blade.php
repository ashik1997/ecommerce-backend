@extends('backend.master')
@section('page_title','Dispose Asset')
@section('page_heading','Dispose Asset')
@section('header_css')@include('backend.fixed_asset._style')@endsection
@section('content')
<div class="fa-page-wrap">
    @include('backend.fixed_asset.partials.nav')<div class="card"><div class="card-body"><h4 class="fa-card-title mb-3">Dispose: {{ $asset->asset_code }} — {{ $asset->asset_name }}</h4><div class="alert alert-warning">Book value: <strong>{{ number_format($asset->carrying_amount,2) }}</strong>. Gain/Loss will be calculated from proceeds amount.</div><form method="post" action="{{ route('fixed-assets.disposals.store',$asset) }}">@csrf<div class="row"><div class="col-md-4 mb-3"><label>Reason</label><select name="disposal_reason_id" class="form-control"><option value="">Select</option>@foreach($reasons as $r)<option value="{{ $r->id }}">{{ $r->name }}</option>@endforeach</select></div><div class="col-md-4 mb-3"><label class="required">Disposal Date</label><input type="date" name="disposal_date" value="{{ date('Y-m-d') }}" class="form-control" required></div><div class="col-md-4 mb-3"><label>Proceeds Amount</label><input type="number" step="0.01" min="0" name="proceeds_amount" class="form-control" value="0"></div><div class="col-md-12 mb-3"><label>Notes</label><textarea name="notes" class="form-control"></textarea></div></div><button class="btn btn-danger">Dispose Asset</button></form></div></div></div>
@endsection
