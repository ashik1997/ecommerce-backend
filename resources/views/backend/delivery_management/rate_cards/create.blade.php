@extends('backend.master')
@section('page_title', 'Create Rate Card')
@section('page_heading', 'Create Rate Card')
@section('content')
    @include('backend.delivery_management.partials.nav')

    <form method="post" action="{{ route('delivery-management.rate-cards.store') }}">
        @csrf
        @include('backend.delivery_management.rate_cards._form')
    </form>
@endsection
