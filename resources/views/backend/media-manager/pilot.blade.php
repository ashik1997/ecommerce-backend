@extends('backend.master')

@section('page_title', 'Media Picker Pilot')
@section('page_heading', 'Media Picker Pilot')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="mb-1">Media Picker Pilot</h5>
                        <p class="mb-0 text-muted small">This page validates the new picker without changing saved business data.</p>
                    </div>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.MediaManager && window.MediaManager.open({ mode: 'browse' })">
                        <i class="fas fa-folder-open"></i>
                        Browse Files
                    </button>
                </div>
                <div class="card-body">
                    @if (session('media_picker_pilot_success'))
                        <div class="alert alert-success">
                            {{ session('media_picker_pilot_success') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('media.picker-pilot.submit') }}">
                        @csrf

                        <div class="row">
                            <div class="col-lg-6 mb-4">
                                @include('backend.components.media-picker-field', [
                                    'label' => 'Single Image',
                                    'mode' => 'single',
                                    'inputName' => 'pilot_single_media_id',
                                    'pathInputName' => 'pilot_single_media_path',
                                    'value' => old('pilot_single_media_id', ''),
                                    'pathValue' => old('pilot_single_media_path', ''),
                                    'selectedFiles' => $singleSelectedFiles,
                                    'directory' => 'pilot',
                                    'width' => 800,
                                    'height' => 800,
                                    'purpose' => 'media_picker_pilot_single',
                                    'helpText' => 'Remove clears this field only. It does not delete the media file.',
                                ])
                            </div>

                            <div class="col-lg-6 mb-4">
                                @include('backend.components.media-picker-field', [
                                    'label' => 'Gallery Images',
                                    'mode' => 'multiple',
                                    'max' => 6,
                                    'inputName' => 'pilot_gallery_media_ids',
                                    'pathInputName' => 'pilot_gallery_media_paths',
                                    'value' => old('pilot_gallery_media_ids', ''),
                                    'pathValue' => old('pilot_gallery_media_paths', ''),
                                    'selectedFiles' => $multipleSelectedFiles,
                                    'directory' => 'pilot',
                                    'width' => 800,
                                    'height' => 800,
                                    'purpose' => 'media_picker_pilot_gallery',
                                    'buttonText' => 'Choose Gallery',
                                    'helpText' => 'Maximum 6 images. Remove clears only this pilot field.',
                                ])
                            </div>
                        </div>

                        <div class="border rounded p-3 bg-light mb-3">
                            <div class="row">
                                <div class="col-lg-6">
                                    <strong>Single media ID</strong>
                                    <div class="text-muted">{{ old('pilot_single_media_id', '-') ?: '-' }}</div>
                                    <strong class="d-block mt-2">Single media path</strong>
                                    <div class="text-muted">{{ old('pilot_single_media_path', '-') ?: '-' }}</div>
                                </div>
                                <div class="col-lg-6">
                                    <strong>Gallery media IDs</strong>
                                    <div class="text-muted">{{ old('pilot_gallery_media_ids', '-') ?: '-' }}</div>
                                    <strong class="d-block mt-2">Gallery media paths</strong>
                                    <div class="text-muted">{{ old('pilot_gallery_media_paths', '-') ?: '-' }}</div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check"></i>
                            Submit Pilot Form
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
