@extends('backend.master')

@section('header_css')
@endsection

@section('page_title')
    Flag
@endsection
@section('page_heading')
    Add New Flag
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-12 col-xl-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title mb-0">Flag Create Form</h4>
                        <a href="{{ route('ViewAllFlags') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                    </div>

                    <form class="needs-validation" method="POST" action="{{ url('create/new/flag') }}"
                        enctype="multipart/form-data">
                        @csrf

                        <div class="form-group">
                            @include('backend.components.website_dropdown')
                        </div>

                        <div class="form-group row">
                            <label for="flagName" class="col-sm-2 col-form-label">Name <span
                                    class="text-danger">*</span></label>
                            <div class="col-sm-10">
                                <input type="text" name="name" class="form-control" id="flagName"
                                    placeholder="Flag Name" required>
                                <div class="invalid-feedback" style="display: block;">
                                    @error('name')
                                        {{ $message }}
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">Flag Icon (1450*270px)</label>
                            <div class="col-sm-10">
                                <div class="form-group">
                                    @include('backend.components.image_upload_v2', [
                                        'inputName' => 'icon',
                                        'label' => 'Flag Icon',
                                        'required' => false,
                                        'width' => 1600,
                                        'height' => 600,
                                        'maxWidth' => '800px',
                                        'previewHeight' => '300px',
                                        'directory' => 'flag_icons',
                                    ])
                                </div>
                            </div>
                        </div>



                        <div class="form-group">
                            <button class="btn btn-primary" type="submit">Save Flag</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection


@section('footer_js')
@endsection
