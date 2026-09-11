@extends('backend.master')
@section('page_title','Create Commission Rule')
@section('page_heading','Create Commission Rule')
@section('content')
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between mb-3">
            <h4>Add Commission Rule</h4>
            <a href="{{ route('sr.commission-rules.index') }}" class="btn btn-secondary btn-sm">Back</a>
        </div>
        @if(session('warning'))<div class="alert alert-warning">{{ session('warning') }}</div>@endif
        <form method="POST" action="{{ route('sr.commission-rules.store') }}">
            @csrf
            @include('backend.sr_management.commission_rules._form')
            <button class="btn btn-primary">Save</button>
        </form>
    </div>
</div>
@endsection
