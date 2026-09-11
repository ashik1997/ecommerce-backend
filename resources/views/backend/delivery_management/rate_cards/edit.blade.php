@extends('backend.master')
@section('page_title', 'Edit Rate Card')
@section('page_heading', 'Edit Rate Card')
@section('content')
    @include('backend.delivery_management.partials.nav')

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="post" action="{{ route('delivery-management.rate-cards.update', $rateCard) }}">
        @csrf
        @method('PUT')
        @include('backend.delivery_management.rate_cards._form')
    </form>
@endsection
