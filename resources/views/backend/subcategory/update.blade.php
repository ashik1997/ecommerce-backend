@extends('backend.master')

@section('page_title')
    Subcategory
@endsection
@section('page_heading')
    Update Subcategory
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-12 col-xl-12">
            <div class="card" style="max-width: 768px; margin: 0 auto;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title mb-3">Subcategory Update Form</h4>
                        <a href="{{ route('ViewAllSubcategory')}}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                    </div>

                    <form class="needs-validation" method="POST" action="{{url('update/subcategory')}}" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="id" value="{{$subcategory->id}}">

                        <div class="form-group row">
                            <div class="col-sm-12">
                                @include('backend.components.website_dropdown', ['value' => $subcategory->product_website_id ?? null])
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-12 col-form-label">Select Category <span class="text-danger">*</span></label>
                            <div class="col-sm-12">
                                <select name="category_id" class="form-control" id="category_dropdown"
                                    data-selected="{{ $subcategory->category_id }}" required>
                                    @php
                                        echo App\Models\Category::getDropDownList('name', $subcategory->category_id);
                                    @endphp
                                </select>
                                <div class="invalid-feedback" style="display: block;">
                                    @error('category_id')
                                        {{ $message }}
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="colFormLabel" class="col-12 col-form-label">Name <span class="text-danger">*</span></label>
                            <div class="col-sm-12">
                                <input type="text" name="name" value="{{$subcategory->name}}" class="form-control" id="colFormLabel" placeholder="Subcategory Title" required>
                                <div class="invalid-feedback" style="display: block;">
                                    @error('name')
                                        {{ $message }}
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-sm-8">
                                @php
                                    $imageUrl = '';
                                    if ($subcategory->icon) {
                                        $imageUrl = get_file_url().'/'.$subcategory->icon;
                                    }
                                @endphp
                                @include('backend.components.image_upload_v2', [
                                    'inputName' => 'icon',
                                    'label' => 'Subcategory Icon',
                                    'required' => true,
                                    'width' => 100,
                                    'height' => 100,
                                    'maxWidth' => '150px',
                                    'previewHeight' => '100px',
                                    'directory' => 'subcategory_icons',
                                    'value' => $subcategory->icon ?? '',
                                    'imageUrl' => $imageUrl
                                ])
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-sm-8">
                                @php
                                    $imageUrl = '';
                                    if ($subcategory->image) { 
                                        $imageUrl = get_file_url().'/'.$subcategory->image;
                                    }
                                @endphp
                                @include('backend.components.image_upload_v2', [
                                    'inputName' => 'image',
                                    'label' => 'Subcategory Image',
                                    'required' => true,
                                    'width' => 1620,
                                    'height' => 375,
                                    'maxWidth' => '150px',
                                    'previewHeight' => '100px',
                                    'directory' => 'subcategory_images',
                                    'value' => $subcategory->image ?? '',
                                    'imageUrl' => $imageUrl
                                ])
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="slug" class="col-12 col-form-label">Slug <span class="text-danger">*</span></label>
                            <div class="col-sm-12">
                                <input type="text" name="slug" value="{{$subcategory->slug}}" class="form-control" id="slug" placeholder="Subcategory Slug" required>
                                <div class="invalid-feedback" style="display: block;">
                                    @error('slug')
                                        {{ $message }}
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-12 col-form-label">Status <span class="text-danger">*</span></label>
                            <div class="col-sm-12">
                                <select name="status" class="form-control" required>
                                    <option value="">Select One</option>
                                    <option value="1" @if($subcategory->status == 1) selected @endif>Active</option>
                                    <option value="0" @if($subcategory->status == 0) selected @endif>Inactive</option>
                                </select>
                                <div class="invalid-feedback" style="display: block;">
                                    @error('status')
                                        {{ $message }}
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-sm-12">
                                <button class="btn btn-primary" type="submit">Update Subcategory</button>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('footer_js')
<script>
    $(document).ready(function () {
        var $websiteDropdown = $('#product_website_dropdown');
        var $categoryDropdown = $('#category_dropdown');

        if ($websiteDropdown.length) {
            $websiteDropdown.on('change', function () {
                var websiteId = $(this).val();
                var currentSelected = $categoryDropdown.data('selected');
                loadCategories(websiteId, currentSelected);
            });
        }

        function loadCategories(websiteId, trySelectId) {
            var url = '/api/categories';
            if (websiteId) {
                url += '?product_website_id=' + websiteId;
            }

            $.get(url, function (data) {
                $categoryDropdown.html('<option value="">Select Category</option>');
                var matched = false;
                $.each(data, function (i, item) {
                    var selected = (trySelectId && item.id == trySelectId) ? ' selected' : '';
                    if (selected) matched = true;
                    $categoryDropdown.append('<option value="' + item.id + '"' + selected + '>' + item.name + '</option>');
                });
            });
        }
    });
</script>
@endsection
