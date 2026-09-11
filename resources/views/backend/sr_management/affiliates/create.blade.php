@extends('backend.master')
@section('page_title','Create Affiliate')
@section('page_heading','Create Affiliate')
@section('content')
<div class="card"><div class="card-body"><div class="d-flex justify-content-between mb-3"><h4>Add Affiliate Partner</h4><a href="{{ route('sr.affiliates.index') }}" class="btn btn-secondary btn-sm">Back</a></div>
<form method="POST" action="{{ route('sr.affiliates.store') }}">@csrf @include('backend.sr_management.affiliates._form')<button class="btn btn-primary">Save</button></form>
</div></div>
@endsection
