@extends('backend.master')
@section('page_title','Create Maintenance Job')
@section('page_heading','Create Maintenance Job')
@section('header_css')@include('backend.fixed_asset._style')@endsection
@section('content')
<div class="fa-page-wrap">
    @include('backend.fixed_asset.partials.nav')<div class="card"><div class="card-body"><h4 class="fa-card-title mb-3">Maintenance: {{ $asset->asset_code }} — {{ $asset->asset_name }}</h4><form method="post" action="{{ route('fixed-assets.maintenance.store',$asset) }}">@csrf<div class="row"><div class="col-md-3 mb-3"><label class="required">Type</label><select name="type" class="form-control" required>@foreach(['preventive','corrective','warranty','inspection','calibration'] as $s)<option value="{{ $s }}">{{ $s }}</option>@endforeach</select></div><div class="col-md-6 mb-3"><label class="required">Title</label><input name="title" class="form-control" required></div><div class="col-md-3 mb-3"><label class="required">Reported Date</label><input type="date" name="reported_at" class="form-control" value="{{ date('Y-m-d') }}" required></div><div class="col-md-12 mb-3"><label>Problem Description</label><textarea name="problem_description" class="form-control"></textarea></div><div class="col-md-12 mb-3"><label>Notes</label><textarea name="notes" class="form-control"></textarea></div></div><button class="btn btn-fa-primary">Create Job</button></form></div></div></div>
@endsection
