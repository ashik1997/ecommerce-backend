@extends('backend.master')

@section('header_css')
@endsection

@section('page_title')
    Flag
@endsection
@section('page_heading')
    Update Flag
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-12 col-xl-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title mb-0">Flag Update Form</h4>
                        <a href="{{ route('ViewAllFlags') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                    </div>

                    <form class="needs-validation" method="POST" action="{{ url('update/flag') }}"
                        enctype="multipart/form-data">
                        @csrf

                        <input type="hidden" name="flag_slug" value="{{ $data->slug }}">

                        <div class="form-group">
                            @include('backend.components.website_dropdown', ['selected' => $data->product_website_id ?? null])
                        </div>

                        <div class="form-group row">
                            <label for="flagName" class="col-sm-2 col-form-label">Name <span
                                    class="text-danger">*</span></label>
                            <div class="col-sm-10">
                                <input type="text" name="name" class="form-control" id="flagName"
                                    value="{{ $data->name }}" placeholder="Flag Name" required>
                                <div class="invalid-feedback" style="display: block;">
                                    @error('name')
                                        {{ $message }}
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">Change Icon (1450*270px)</label>
                            <div class="col-sm-10">
                                <div class="form-group">
                                    @include('backend.components.image_upload_v2', [
                                        'inputName' => 'icon',
                                        'label' => 'Flag Icon',
                                        'required' => false,
                                        'width' => 1450,
                                        'height' => 270,
                                        'maxWidth' => '400px',
                                        'previewHeight' => '300px',
                                        'directory' => 'flag_icons',
                                        'value' => $data->icon ?? '',
                                    ])
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="flagStatus" class="col-sm-2 col-form-label">Status <span
                                    class="text-danger">*</span></label>
                            <div class="col-sm-3">
                                <select name="flag_status" class="form-control" id="flagStatus" required>
                                    <option value="">Select One</option>
                                    <option value="1" @if($data->status == 1) selected @endif>Active</option>
                                    <option value="0" @if($data->status == 0) selected @endif>Inactive</option>
                                </select>
                                <div class="invalid-feedback" style="display: block;">
                                    @error('flag_status')
                                        {{ $message }}
                                    @enderror
                                </div>
                            </div>
                        </div>

                        

                        <div class="form-group">
                            <button class="btn btn-primary" type="submit">Update Flag Info</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection


@section('footer_js')
@endsection
