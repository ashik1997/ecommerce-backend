@extends('backend.master')

@section('page_title')
    Product Websites
@endsection
@section('page_heading')
    Update Product Website
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-12 col-xl-12">
            <div class="card">
                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title mb-3">Product Website Update Form</h4>
                        <a href="{{ route('ViewAllProductWebsites') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                    </div>

                    <form class="needs-validation" method="POST" action="{{ route('UpdateProductWebsite') }}" enctype="multipart/form-data">
                        @csrf

                        <input type="hidden" name="slug" value="{{ $data->slug }}">
                        <input type="hidden" name="id" value="{{ $data->id }}">

                        <div class="form-group row">
                            <label for="title" class="col-sm-2 col-form-label">Title <span class="text-danger">*</span></label>
                            <div class="col-sm-10">
                                <input type="text" name="title" value="{{ old('title', $data->title) }}" class="form-control" id="title" placeholder="Website Title" required>
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
                                <input type="url" name="url" value="{{ old('url', $data->url) }}" class="form-control" id="url" placeholder="https://example.com">
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
                                @php
                                    $logoUrl = '';
                                    if ($data->logo) {
                                        if (str_starts_with($data->logo, 'http://') || str_starts_with($data->logo, 'https://')) {
                                            $logoUrl = $data->logo;
                                        } else {
                                            $baseUrl = get_file_url();
                                            $baseUrl = rtrim($baseUrl, '/');
                                            $imagePath = ltrim($data->logo, '/');
                                            $logoUrl = $baseUrl . '/' . $imagePath;
                                        }
                                    }
                                @endphp
                                @include('backend.components.image_upload_v2', [
                                    'inputName' => 'logo',
                                    'label' => 'Logo',
                                    'required' => false,
                                    'width' => 200,
                                    'height' => 60,
                                    'maxWidth' => '300px',
                                    'previewHeight' => '150px',
                                    'directory' => 'product-website-logos',
                                    'value' => $data->logo ?? '',
                                    'imageUrl' => $logoUrl
                                ])
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="status" class="col-sm-2 col-form-label">Status <span class="text-danger">*</span></label>
                            <div class="col-sm-10">
                                <select name="status" class="form-control" id="status" required>
                                    <option value="">Select One</option>
                                    <option value="active" @if($data->status == 'active') selected @endif>Active</option>
                                    <option value="inactive" @if($data->status == 'inactive') selected @endif>Inactive</option>
                                </select>
                                <div class="invalid-feedback" style="display: block;">
                                    @error('status')
                                        {{ $message }}
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label"></label>
                            <div class="col-sm-10">
                                <button class="btn btn-primary" type="submit">Update Product Website</button>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
