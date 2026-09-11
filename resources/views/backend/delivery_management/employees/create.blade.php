@extends('backend.master')
@section('page_title', 'Create Delivery Employee')
@section('page_heading', 'Create Delivery Employee')
@section('content')
    @include('backend.delivery_management.partials.nav')

    <form method="post" action="{{ route('delivery-management.employees.store') }}">
        @csrf
        @include('backend.delivery_management.employees._form')
    </form>
@endsection
