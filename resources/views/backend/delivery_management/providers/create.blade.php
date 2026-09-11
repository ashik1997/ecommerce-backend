@extends('backend.master')
@section('page_title', 'Create Delivery Provider')
@section('page_heading', 'Create Delivery Provider')
@section('content')
    @include('backend.delivery_management.partials.nav')

    <form method="post" action="{{ route('delivery-management.providers.store') }}">
        @csrf
        @include('backend.delivery_management.providers._form')
    </form>
@endsection
