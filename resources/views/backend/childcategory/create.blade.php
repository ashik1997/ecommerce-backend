@extends('backend.master')

@section('page_title')
    Child Category
@endsection
@section('page_heading')
    Add New Child Category
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title mb-3">Child Category Create Form</h4>
                        <a href="{{ route('ViewAllChildcategory') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                    </div>

                    <form class="needs-validation" method="POST" action="{{ url('save/new/childcategory') }}"
                        enctype="multipart/form-data">
                        @csrf

                        <div class="form-group">
                            @include('backend.components.website_dropdown')
                        </div>

                        <div class="form-group">
                            <label class=" col-form-label">Select Category <span class="text-danger">*</span></label>
                            <div>
                                <select name="category_id" class="form-control" id="category_dropdown" required>
                                    @php
                                        echo App\Models\Category::getDropDownList('name');
                                    @endphp
                                </select>
                                <div class="invalid-feedback" style="display: block;">
                                    @error('category_id')
                                        {{ $message }}
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-form-label">Select Subcategory <span class="text-danger">*</span></label>
                            <div class="">
                                <select name="subcategory_id" class="form-control" id="subcategory_dropdown" required>
                                    <option value="">Select One</option>
                                </select>
                                <div class="invalid-feedback" style="display: block;">
                                    @error('subcategory_id')
                                        {{ $message }}
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group ">
                            <label for="colFormLabel" class=" col-form-label">
                                Name <span class="text-danger">*</span>
                            </label>
                            <div class="">
                                <input type="text" name="name" class="form-control" id="colFormLabel"
                                    placeholder="Child Category Title" required>
                                <div class="invalid-feedback" style="display: block;">
                                    @error('name')
                                        {{ $message }}
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <button class="btn btn-primary" type="submit">Save Child Category</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('footer_js')
    <script>
        $(document).ready(function() {
            var $websiteDropdown = $('#product_website_dropdown');
            var $categoryDropdown = $('#category_dropdown');
            var $subcategoryDropdown = $('#subcategory_dropdown');

            // Website change → reload categories, clear subcategories
            if ($websiteDropdown.length) {
                $websiteDropdown.on('change', function() {
                    loadCategories($(this).val(), null);
                    $subcategoryDropdown.html('<option value="">Select One</option>');
                });
            }

            // Category change → reload subcategories
            $categoryDropdown.on('change', function() {
                var categoryId = $(this).val();
                var websiteId = $websiteDropdown.length ? $websiteDropdown.val() : null;
                loadSubcategories(categoryId, websiteId, null);
            });

            function loadCategories(websiteId, trySelectId) {
                var url = '/api/categories';
                if (websiteId) url += '?product_website_id=' + websiteId;

                $.get(url, function(data) {
                    $categoryDropdown.html('<option value="">Select Category</option>');
                    $.each(data, function(i, item) {
                        var sel = (trySelectId && item.id == trySelectId) ? ' selected' : '';
                        $categoryDropdown.append('<option value="' + item.id + '"' + sel + '>' +
                            item.name + '</option>');
                    });
                    $subcategoryDropdown.html('<option value="">Select One</option>');
                });
            }

            function loadSubcategories(categoryId, websiteId, trySelectId) {
                if (!categoryId) {
                    $subcategoryDropdown.html('<option value="">Select One</option>');
                    return;
                }

                var url = '/api/subcategories?category_id=' + categoryId;
                if (websiteId) url += '&product_website_id=' + websiteId;

                $.get(url, function(data) {
                    $subcategoryDropdown.html('<option value="">Select Subcategory</option>');
                    $.each(data, function(i, item) {
                        var sel = (trySelectId && item.id == trySelectId) ? ' selected' : '';
                        $subcategoryDropdown.append('<option value="' + item.id + '"' + sel + '>' +
                            item.name + '</option>');
                    });
                });
            }
        });
    </script>
@endsection
