@extends('backend.master')
@section('page_title', 'Edit Delivery Employee')
@section('page_heading', 'Edit Delivery Employee')
@section('content')
    @include('backend.delivery_management.partials.nav')

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="post" action="{{ route('delivery-management.employees.update', $employee) }}">
        @csrf
        @method('PUT')
        @include('backend.delivery_management.employees._form')
    </form>
@endsection
