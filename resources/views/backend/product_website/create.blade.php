@extends('backend.master')

@section('page_title')
    Product Websites
@endsection
@section('page_heading')
    Add New Product Website
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-12 col-xl-12">
            <div class="card">
                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title mb-3">Product Website Create Form</h4>
                        <a href="{{ route('ViewAllProductWebsites') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                    </div>

                    <form class="needs-validation" method="POST" action="{{ route('SaveNewProductWebsite') }}" enctype="multipart/form-data">
                        @csrf

                        <div class="form-group row">
                            <label for="title" class="col-sm-2 col-form-label">Title <span class="text-danger">*</span></label>
                            <div class="col-sm-10">
                                <input type="text" name="title" class="form-control" id="title" placeholder="Website Title" required>
                                <div class="invalid-feedback" style="display: block;">
                                    @error('title')
                                        {{ $message }}
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="url" class="col-sm-2 col-form-label">URL</label>
                            <div class="col-sm-10">
                                <input type="url" name="url" class="form-control" id="url" placeholder="https://example.com">
                                <div class="invalid-feedback" style="display: block;">
                                    @error('url')
                                        {{ $message }}
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="logo" class="col-sm-2 col-form-label">Logo</label>
                            <div class="col-sm-10">
                                @include('backend.components.image_upload_v2', [
                                    'inputName' => 'logo',
                                    'label' => 'Logo',
                                    'required' => false,
                                    'width' => 200,
                                    'height' => 60,
                                    'maxWidth' => '300px',
                                    'previewHeight' => '150px',
                                    'directory' => 'product-website-logos',
                                    'value' => '',
                                    'imageUrl' => ''
                                ])
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label"></label>
                            <div class="col-sm-10">
                                <button class="btn btn-primary" type="submit">Save Product Website</button>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
