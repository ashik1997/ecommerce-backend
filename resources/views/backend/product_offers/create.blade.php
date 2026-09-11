@extends('backend.master')

@section('header_css')
    <link rel="stylesheet" href="{{ versioned_url('codeMirror/css/codemirror.css') }}">
    <link rel="stylesheet" href="{{ versioned_url('codeMirror/css/themes/material.css') }}">
@endsection

@section('header_js')
    <script src="{{ versioned_url('codeMirror/js/codemirror.js') }}"></script>
    <script src="{{ versioned_url('codeMirror/js/xml.js') }}"></script>
    <script src="{{ versioned_url('codeMirror/js/php.js') }}"></script>
    <script src="{{ versioned_url('codeMirror/js/javascript.js') }}"></script>
    <script src="{{ versioned_url('codeMirror/js/python.js') }}"></script>
    <script src="{{ versioned_url('codeMirror/js/addons/closetag.js') }}"></script>
    <script src="{{ versioned_url('codeMirror/js/addons/closebrackets.js') }}"></script>
@endsection

@section('content')
    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Create product offer</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a
                                        href="{{ route('product-management.product-offers.index') }}">Product
                                        offers</a></li>
                                <li class="breadcrumb-item active">Create</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="alert alert-info">
                After creating the offer, open its details page to add products and set discounts.
            </div>

            <div class="row">
                <div class="col-lg-10">
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('product-management.product-offers.store') }}" method="POST"
                                enctype="multipart/form-data">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">Title <span class="text-danger">*</span></label>
                                    <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                                        value="{{ old('title') }}" required>
                                    @error('title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Start date <span class="text-danger">*</span></label>
                                        <input type="date" name="start_date"
                                            class="form-control @error('start_date') is-invalid @enderror"
                                            value="{{ old('start_date') }}" required>
                                        @error('start_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">End date <span class="text-danger">*</span></label>
                                        <input type="date" name="end_date"
                                            class="form-control @error('end_date') is-invalid @enderror"
                                            value="{{ old('end_date') }}" required>
                                        @error('end_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="form-group">
                                        @include('backend.components.image_upload_v2', [
                                            'inputName' => 'background_image',
                                            'label' => 'Background Image',
                                            'required' => true,
                                            'width' => 1920,
                                            'height' => 1080,
                                            'maxWidth' => '300px',
                                            'previewHeight' => '150px',
                                            'directory' => 'product_offers'
                                        ])
                                    </div>
                                    {{-- <label class="form-label">Background image</label>
                                    <input type="file" name="background_image" class="form-control" accept="image/*">
                                    @error('background_image')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror --}}
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-control">
                                        <option value="1" {{ old('status', '1') == '1' ? 'selected' : '' }}>Active</option>
                                        <option value="0" {{ old('status') == '0' ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Custom style (CSS)</label>
                                    <textarea id="custom_style" name="custom_style" class="form-control" rows="6">{{ old('custom_style') }}</textarea>
                                    @error('custom_style')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>
                                <button type="submit" class="btn btn-primary">Save offer</button>
                                <a href="{{ route('product-management.product-offers.index') }}"
                                    class="btn btn-secondary">Cancel</a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('footer_js')
    <script>
        (function() {
            var el = document.getElementById('custom_style');
            if (!el) return;
            window.customStyleCm = CodeMirror.fromTextArea(el, {
                mode: 'css',
                theme: 'material',
                lineNumbers: true,
                autoCloseTags: true,
                autoCloseBrackets: true,
                lineWrapping: true,
            });
            window.customStyleCm.setSize('100%', 360);
            $('form').on('submit', function() {
                if (window.customStyleCm) {
                    window.customStyleCm.save();
                }
            });
        })();
    </script>
@endsection
