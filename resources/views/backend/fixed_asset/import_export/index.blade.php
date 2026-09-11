@extends('backend.master')
@section('page_title','Fixed Asset Import & Export')
@section('page_heading','Fixed Asset Import & Export')
@section('header_css')@include('backend.fixed_asset._style')@endsection
@section('content')
<div class="fa-page-wrap">
    @include('backend.fixed_asset.partials.nav')
<div class="row">
    <div class="col-lg-6">
        <div class="card"><div class="card-body">
            <h4 class="fa-card-title">Import Asset Register</h4>
            <p class="text-muted">CSV import supports new assets only. Warehouse is mandatory and must match existing <code>product_warehouses.id</code>.</p>
            <a href="{{ route('fixed-assets.import.template') }}" class="btn btn-outline-dark btn-sm mb-3">Download CSV Template</a>
            <form method="post" action="{{ route('fixed-assets.import.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label>CSV File</label>
                    <input type="file" name="file" class="form-control" accept=".csv,.txt" required>
                </div>
                <button class="btn btn-fa-primary">Import Assets</button>
            </form>
            @if(session('fixed_asset_import_errors'))
                <hr>
                <h6 class="text-danger">Import Warnings</h6>
                <ul class="text-danger small mb-0">
                    @foreach(session('fixed_asset_import_errors') as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif
        </div></div>
    </div>
    <div class="col-lg-6">
        <div class="card"><div class="card-body">
            <h4 class="fa-card-title">Export Asset Register</h4>
            <p class="text-muted">Exports the current fixed asset register as CSV for audit, reconciliation, and offline review.</p>
            <form method="get" action="{{ route('fixed-assets.export') }}">
                <div class="form-group">
                    <label>Warehouse ID <small class="text-muted">optional</small></label>
                    <input type="number" name="warehouse_id" class="form-control" placeholder="Example: 1">
                </div>
                <div class="form-group">
                    <label>Category ID <small class="text-muted">optional</small></label>
                    <input type="number" name="category_id" class="form-control" placeholder="Example: 3">
                </div>
                <div class="form-group">
                    <label>Status <small class="text-muted">optional</small></label>
                    <select name="status" class="form-control">
                        <option value="">All</option>
                        @foreach(['available','assigned','in_transfer','under_maintenance','idle','lost','damaged','retired'] as $status)
                            <option value="{{ $status }}">{{ ucwords(str_replace('_',' ',$status)) }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn btn-success">Export CSV</button>
            </form>
        </div></div>
    </div>
</div>
</div>
@endsection
