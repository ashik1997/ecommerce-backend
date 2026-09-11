@extends('backend.master')
@section('page_title','New Verification Session')
@section('page_heading','New Verification Session')
@section('header_css')@include('backend.fixed_asset._style')@endsection
@section('content')
<div class="fa-page-wrap">
    @include('backend.fixed_asset.partials.nav')
@include('backend.fixed_asset.partials.errors')
<div class="card"><div class="card-body"><h4 class="fa-card-title mb-3">Open Physical Verification</h4>
<form method="post" action="{{ route('fixed-assets.verification.store') }}">@csrf
    <div class="row">
        <div class="col-md-4 mb-3"><label class="required">Warehouse / Branch</label><select name="warehouse_id" class="form-control" required><option value="">Select Warehouse</option>@foreach($warehouses as $w)<option value="{{ $w->id }}" {{ old('warehouse_id') == $w->id ? 'selected' : '' }}>{{ $w->title }}</option>@endforeach</select></div>
        <div class="col-md-5 mb-3"><label class="required">Title</label><input name="title" class="form-control" value="{{ old('title') }}" placeholder="June 2026 Dhaka Branch Verification" required></div>
        <div class="col-md-3 mb-3"><label class="required">Start Date</label><input type="date" name="started_at" class="form-control" value="{{ old('started_at', now()->toDateString()) }}" required></div>
        <div class="col-md-12 mb-3"><label>Notes</label><textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea></div>
    </div>
    <button class="btn btn-fa-primary">Create Session</button><a href="{{ route('fixed-assets.verification.index') }}" class="btn btn-light">Cancel</a>
</form></div></div>
</div>
@endsection
