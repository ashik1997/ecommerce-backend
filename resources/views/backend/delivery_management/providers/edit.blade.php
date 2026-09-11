@extends('backend.master')
@section('page_title', 'Edit Delivery Provider')
@section('page_heading', 'Edit Delivery Provider')
@section('content')
    @include('backend.delivery_management.partials.nav')

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="post" action="{{ route('delivery-management.providers.update', $provider) }}">
        @csrf
        @method('PUT')
        @include('backend.delivery_management.providers._form')
    </form>
@endsection
