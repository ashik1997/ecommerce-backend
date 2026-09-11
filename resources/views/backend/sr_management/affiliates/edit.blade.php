@extends('backend.master')
@section('page_title','Edit Affiliate')
@section('page_heading','Edit Affiliate')
@section('content')
<div class="card"><div class="card-body"><div class="d-flex justify-content-between mb-3"><h4>Edit Affiliate Partner</h4><a href="{{ route('sr.affiliates.index') }}" class="btn btn-secondary btn-sm">Back</a></div>
<form method="POST" action="{{ route('sr.affiliates.update',$affiliate->id) }}">@csrf @method('PUT') @include('backend.sr_management.affiliates._form')<button class="btn btn-primary">Update</button></form>
</div></div>
@endsection
