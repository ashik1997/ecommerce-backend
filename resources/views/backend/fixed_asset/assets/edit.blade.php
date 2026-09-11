@extends('backend.master')
@section('page_title','Edit Fixed Asset')
@section('page_heading','Edit Fixed Asset')
@section('header_css')@include('backend.fixed_asset._style')@endsection
@section('content')
<div class="fa-page-wrap">
    @include('backend.fixed_asset.partials.nav')<div class="card"><div class="card-body"><h4 class="fa-card-title mb-3">Edit Asset: {{ $asset->asset_code }}</h4><form method="post" action="{{ route('fixed-assets.assets.update',$asset) }}">@include('backend.fixed_asset.assets._form')</form></div></div></div>
@endsection
