@extends('backend.master')

@section('page_title')
    Breaking News
@endsection
@section('page_heading')
    Update Breaking News
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-12 col-xl-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title mb-0">Update Form</h4>
                        <a href="{{ route('ViewAllBreakingNews') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                    </div>

                    <form class="needs-validation" method="POST" action="{{ route('UpdateBreakingNews') }}">
                        @csrf
                        <input type="hidden" name="id" value="{{ $data->id }}">

                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">Title <span class="text-danger">*</span></label>
                            <div class="col-sm-10">
                                <input type="text" name="title" class="form-control"
                                    value="{{ old('title', $data->title) }}" placeholder="Title" required>
                                <div class="invalid-feedback d-block">
                                    @error('title')
                                        {{ $message }}
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">Serial</label>
                            <div class="col-sm-10">
                                <input type="number" name="serial" class="form-control"
                                    value="{{ old('serial', $data->serial) }}" placeholder="Display order (optional)">
                                @error('serial')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">URL</label>
                            <div class="col-sm-10">
                                <input type="text" name="url" class="form-control"
                                    value="{{ old('url', $data->url) }}" placeholder="Link URL (optional)">
                                @error('url')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">Status</label>
                            <div class="col-sm-10">
                                <select name="status" class="form-control">
                                    <option value="1" {{ old('status', (string) $data->status) == '1' ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ old('status', (string) $data->status) == '0' ? 'selected' : '' }}>Inactive</option>
                                </select>
                                @error('status')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">Slug</label>
                            <div class="col-sm-10">
                                <input type="text" name="slug" class="form-control"
                                    value="{{ old('slug', $data->slug) }}"
                                    placeholder="Optional; regenerated from title if cleared">
                                @error('slug')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">Creator</label>
                            <div class="col-sm-10">
                                <input type="text" name="creator" class="form-control"
                                    value="{{ old('creator', $data->creator) }}" placeholder="Optional">
                                @error('creator')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group">
                            <button class="btn btn-primary" type="submit">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
