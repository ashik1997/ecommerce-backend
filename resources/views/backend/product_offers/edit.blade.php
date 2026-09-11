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
                        <h4 class="mb-sm-0">Edit product offer</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a
                                        href="{{ route('product-management.product-offers.index') }}">Product
                                        offers</a></li>
                                <li class="breadcrumb-item active">Edit</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-10">
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('product-management.product-offers.update', $productOffer) }}"
                                method="POST" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                <div class="mb-3">
                                    <label class="form-label">Title <span class="text-danger">*</span></label>
                                    <input type="text" name="title"
                                        class="form-control @error('title') is-invalid @enderror"
                                        value="{{ old('title', $productOffer->title) }}" required>
                                    @error('title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Start date <span class="text-danger">*</span></label>
                                        <input type="date" name="start_date"
                                            class="form-control @error('start_date') is-invalid @enderror"
                                            value="{{ old('start_date', $productOffer->start_date?->format('Y-m-d')) }}"
                                            required>
                                        @error('start_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">End date <span class="text-danger">*</span></label>
                                        <input type="date" name="end_date"
                                            class="form-control @error('end_date') is-invalid @enderror"
                                            value="{{ old('end_date', $productOffer->end_date?->format('Y-m-d')) }}"
                                            required>
                                        @error('end_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                @if ($productOffer->background_image)
                                    <div class="mb-2">
                                        <img src="{{ asset($productOffer->background_image) }}" alt=""
                                            class="img-thumbnail" style="max-height:120px;">
                                    </div>
                                @endif
                                <div class="mb-3">
                                    @include('backend.components.image_upload_v2', [
                                        'inputName' => 'background_image',
                                        'label' => 'Background Image',
                                        'required' => true,
                                        'width' => 1920,
                                        'height' => 1080,
                                        'maxWidth' => '300px',
                                        'previewHeight' => '150px',
                                        'directory' => 'product_offers',
                                        'value' => $productOffer->background_image? get_file_url() . '/' . $productOffer->background_image : '',
                                    ])
                                    {{-- <label class="form-label">Replace background image</label>
                                    <input type="file" name="background_image" class="form-control" accept="image/*">
                                    @error('background_image')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror --}}
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-control">
                                        <option value="1"
                                            {{ old('status', $productOffer->status) == 1 ? 'selected' : '' }}>Active
                                        </option>
                                        <option value="0"
                                            {{ old('status', $productOffer->status) == 0 ? 'selected' : '' }}>Inactive
                                        </option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Custom style (CSS)</label>
                                    <textarea id="custom_style" name="custom_style" class="form-control" rows="6">{{ old('custom_style', $productOffer->custom_style) }}</textarea>
                                    @error('custom_style')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>
                                <button type="submit" class="btn btn-primary">Update offer</button>
                                <a href="{{ route('product-management.product-offers.show', $productOffer) }}"
                                    class="btn btn-outline-secondary">Back to details</a>
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
