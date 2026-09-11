@extends('backend.master')
@section('page_title','Asset Tag')
@section('page_heading','Asset Tag')
@section('header_css')@include('backend.fixed_asset._style')@endsection
@section('content')
<div class="fa-page-wrap">
    @include('backend.fixed_asset.partials.nav')
<div class="card"><div class="card-body">
    <div class="d-flex justify-content-between mb-3">
        <h4 class="fa-card-title">Printable Asset Tag</h4>
        <button onclick="window.print()" class="btn btn-dark btn-sm">Print</button>
    </div>
    <div class="text-center p-4 border">
        <img src="{{ route('fixed-assets.assets.qr.svg', $asset) }}" alt="{{ $asset->asset_code }}" style="max-width:360px;width:100%;height:auto;">
    </div>
    <hr>
    <p><b>Asset:</b> {{ $asset->asset_code }} — {{ $asset->asset_name }}</p>
    <p><b>Warehouse:</b> {{ optional($asset->warehouse)->title ?? $asset->warehouse_id }}</p>
    <a href="{{ route('fixed-assets.assets.show',$asset) }}" class="btn btn-secondary btn-sm">Back to Asset</a>
</div></div>
</div>
@endsection
