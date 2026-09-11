@extends('backend.master')
@section('page_title', 'Create Delivery Zone')
@section('page_heading', 'Create Delivery Zone')
@section('content')
    @include('backend.delivery_management.partials.nav')

    <form method="post" action="{{ route('delivery-management.zones.store') }}">
        @csrf
        @include('backend.delivery_management.zones._form')
    </form>
@endsection
