@extends('backend.master')

@section('page_title')
    Child Category
@endsection
@section('page_heading')
    Edit Child Category
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title mb-3">Child Category Update Form</h4>
                        <a href="{{ route('ViewAllChildcategory')}}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                    </div>

                    <form class="needs-validation" method="POST" action="{{url('update/childcategory')}}" enctype="multipart/form-data">
                        @csrf

                        <input type="hidden" name="slug" value="{{$childcategory->slug}}">
                        <input type="hidden" name="id" value="{{$childcategory->id}}">

                        <div class="form-group">
                            @include('backend.components.website_dropdown', ['value' => $childcategory->product_website_id ?? null])
                        </div>

                        <div class="form-group">
                            <label class="col-form-label">Select Category <span class="text-danger">*</span></label>
                            <div class="">
                                <select name="category_id" class="form-control" id="category_dropdown"
                                    data-selected="{{ $childcategory->category_id }}" required>
                                    @php
                                        echo App\Models\Category::getDropDownList('name', $childcategory->category_id);
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
                                <select name="subcategory_id" class="form-control" id="subcategory_dropdown"
                                    data-selected="{{ $childcategory->subcategory_id }}" required>
                                    <option value="">Select One</option>
                                    @foreach ($subcategories as $subcategory)
                                        <option value="{{$subcategory->id}}"
                                            @if($subcategory->id == $childcategory->subcategory_id) selected @endif>
                                            {{$subcategory->name}}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" style="display: block;">
                                    @error('subcategory_id')
                                        {{ $message }}
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="colFormLabel" class="col-form-label">Name <span class="text-danger">*</span></label>
                            <div class="">
                                <input type="text" name="name" class="form-control" value="{{$childcategory->name}}"
                                    id="colFormLabel" placeholder="Child Category Title" required>
                                <div class="invalid-feedback" style="display: block;">
                                    @error('name')
                                        {{ $message }}
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group ">
                            <label class="col-form-label">Status <span class="text-danger">*</span></label>
                            <div class="">
                                <select name="status" class="form-control" required>
                                    <option value="">Select One</option>
                                    <option value="1" @if($childcategory->status == 1) selected @endif>Active</option>
                                    <option value="0" @if($childcategory->status == 0) selected @endif>Inactive</option>
                                </select>
                                <div class="invalid-feedback" style="display: block;">
                                    @error('status')
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
    $(document).ready(function () {
        var $websiteDropdown     = $('#product_website_dropdown');
        var $categoryDropdown    = $('#category_dropdown');
        var $subcategoryDropdown = $('#subcategory_dropdown');

        // Website change → reload categories (try to keep current), cascade to subcategories
        if ($websiteDropdown.length) {
            $websiteDropdown.on('change', function () {
                var websiteId      = $(this).val();
                var currentCatId   = $categoryDropdown.data('selected');
                var currentSubId   = $subcategoryDropdown.data('selected');
                loadCategories(websiteId, currentCatId, currentSubId);
            });
        }

        // Category change → reload subcategories (try to keep current)
        $categoryDropdown.on('change', function () {
            var categoryId = $(this).val();
            var websiteId  = $websiteDropdown.length ? $websiteDropdown.val() : null;
            var currentSubId = $subcategoryDropdown.data('selected');
            loadSubcategories(categoryId, websiteId, currentSubId);
        });

        function loadCategories(websiteId, trySelectCatId, trySelectSubId) {
            var url = '/api/categories';
            if (websiteId) url += '?product_website_id=' + websiteId;

            $.get(url, function (data) {
                $categoryDropdown.html('<option value="">Select Category</option>');
                var matchedCat = false;
                $.each(data, function (i, item) {
                    var sel = (trySelectCatId && item.id == trySelectCatId) ? ' selected' : '';
                    if (sel) matchedCat = true;
                    $categoryDropdown.append('<option value="' + item.id + '"' + sel + '>' + item.name + '</option>');
                });

                // If the original category matched, reload its subcategories
                if (matchedCat && trySelectCatId) {
                    loadSubcategories(trySelectCatId, websiteId, trySelectSubId);
                } else {
                    $subcategoryDropdown.html('<option value="">Select Subcategory</option>');
                }
            });
        }

        function loadSubcategories(categoryId, websiteId, trySelectId) {
            if (!categoryId) {
                $subcategoryDropdown.html('<option value="">Select One</option>');
                return;
            }

            var url = '/api/subcategories?category_id=' + categoryId;
            if (websiteId) url += '&product_website_id=' + websiteId;

            $.get(url, function (data) {
                $subcategoryDropdown.html('<option value="">Select Subcategory</option>');
                $.each(data, function (i, item) {
                    var sel = (trySelectId && item.id == trySelectId) ? ' selected' : '';
                    $subcategoryDropdown.append('<option value="' + item.id + '"' + sel + '>' + item.name + '</option>');
                });
            });
        }
    });
</script>
@endsection
