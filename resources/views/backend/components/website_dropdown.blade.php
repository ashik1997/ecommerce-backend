@if (is_multiple_domain())
    <label class="form-label">Website <span class="required">*</span></label>
    <div>
        <select name="product_website_id" class="form-control" id="product_website_dropdown">
            <option value="">Select Website</option>
            @foreach (get_all_websites() as $website)
                <option value="{{ $website->id }}" @if (isset($value) && $value == $website->id) selected @endif>
                    {{ $website->title }}
                </option>
            @endforeach
        </select>
    </div>
@endif
