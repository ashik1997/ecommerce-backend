<div class="row">
    
    <!-- Shipping Information -->
    <div class="col-12 mb-4">
        <h5 class="border-bottom pb-2 mb-3">Shipping Information</h5>
        <div class="row">
            <div class="col-md-3 mb-3">
                <label class="form-label d-block">Free Shipping</label>
                <div class="form-check form-switch">
                    <input type="checkbox"
                           class="form-check-input"
                           id="isFreeShippingCheck"
                           v-model="shippingInfo.is_free_shipping"
                           :true-value="1"
                           :false-value="0">
                    <label class="form-check-label" for="isFreeShippingCheck">
                        Enable free shipping for this product
                    </label>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <label class="form-label d-block">Product Weight</label>
                <input type="number" v-model="shippingInfo.weight" class="form-control" 
                    step="0.01" min="0" placeholder="Weight in kg">
            </div>

            <div class="col-md-3 mb-3">
                <label class="form-label d-block">Unit of weight</label>
                <select v-model="shippingInfo.dimension_unit" class="form-control">
                    <option value="gm">Grams (gm)</option>
                    <option value="mg">Milligrams (mg)</option>
                    <option value="kg">Kilograms (kg)</option>
                    <option value="ltr">Liters (ltr)</option>
                    <option value="ml">Milliliters (ml)</option>
                    
                    {{-- <option value="pcs">Pieces (pcs)</option>
                    <option value="mm">Millimeters (mm)</option>
                    <option value="cm">Centimeters (cm)</option>
                    <option value="m">Meters (m)</option>
                    <option value="in">Inches (in)</option>
                    <option value="ft">Feet (ft)</option>
                    <option value="yd">Yards (yd)</option>
                    
                    <option value="km">Kilometers (km)</option>
                    <option value="lb">Pounds (lb)</option>
                    <option value="oz">Ounces (oz)</option>

                    <option value="dozen">Dozen</option>
                    <option value="carton">Carton</option> --}}
               
                </select>
            </div>

            <div class="col-md-3 mb-3">
                <label class="form-label d-block">Package Type</label>
                <select v-model="shippingInfo.package_type" class="form-control">
                    <option value="Box">Box</option>
                    <option value="Envelope">Envelope</option>
                    <option value="Bag">Bag</option>
                    <option value="Parcel">Parcel</option>
                    <option value="Custom">Custom</option>
                </select>
            </div>

            <div class="col-md-3 mb-3">
                <label class="form-label d-block">Return Policy (Days)</label>
                <input type="number" v-model="shippingInfo.return_policy_days" 
                    class="form-control" min="0" placeholder="e.g., 7">
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <h6 class="border-bottom pb-2 mb-3">Product Delivery Cost</h6>
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label d-block">Is Shipping Fixed For This Product?</label>
                <div class="form-check form-switch">
                    <input type="checkbox"
                           class="form-check-input"
                           id="isShippingFixedForThisProductCheck"
                           v-model="shippingInfo.is_shipping_fixed_for_this_product"
                           :true-value="1"
                           :false-value="0">
                    <label class="form-check-label" for="isShippingFixedForThisProductCheck">
                        Enable product-specific shipping cost setup
                    </label>
                </div>
            </div>
        </div>

        <div v-show="shippingInfo.is_shipping_fixed_for_this_product == 1">
            <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label d-block">Central Area</label>
                <select id="centralAreaDistrictSelect"
                        v-model="shippingInfo.central_area_district_id"
                        @change="shippingInfo.central_area_name = $event.target.value ? $event.target.options[$event.target.selectedIndex].text : ''"
                        class="form-control">
                    <option value="">Select central area</option>
                    @foreach (($districts ?? []) as $district)
                        <option value="{{ $district->id }}">{{ $district->name }}</option>
                    @endforeach
                </select>
                <small class="text-muted d-block">Company dispatch/center area for this product.</small>
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label d-block">Delivery Cost Type</label>
                <select v-model="shippingInfo.shipping_cost_type" class="form-control">
                    <option value="weight_based">Weight Based</option>
                    <option value="fixed">Fixed Cost</option>
                </select>
            </div>
        </div>

        <div class="row" v-show="shippingInfo.is_shipping_fixed_for_this_product == 1 && shippingInfo.shipping_cost_type === 'weight_based'">
            <div class="col-md-3 mb-3">
                <label class="form-label d-block">Inside 1st KG Cost</label>
                <input type="number" v-model="shippingInfo.inside_first_kg_cost"
                    class="form-control" step="0.01" min="0" placeholder="e.g., 60">
            </div>

            <div class="col-md-3 mb-3">
                <label class="form-label d-block">Inside After 1st KG Cost</label>
                <input type="number" v-model="shippingInfo.inside_after_first_kg_cost"
                    class="form-control" step="0.01" min="0" placeholder="e.g., 20">
            </div>

            <div class="col-md-3 mb-3">
                <label class="form-label d-block">Outside 1st KG Cost</label>
                <input type="number" v-model="shippingInfo.outside_first_kg_cost"
                    class="form-control" step="0.01" min="0" placeholder="e.g., 120">
            </div>

            <div class="col-md-3 mb-3">
                <label class="form-label d-block">Outside After 1st KG Cost</label>
                <input type="number" v-model="shippingInfo.outside_after_first_kg_cost"
                    class="form-control" step="0.01" min="0" placeholder="e.g., 30">
            </div>
        </div>

        <div class="row" v-show="shippingInfo.is_shipping_fixed_for_this_product == 1 && shippingInfo.shipping_cost_type === 'fixed'">
            <div class="col-md-3 mb-3">
                <label class="form-label d-block">Inside Fixed Cost</label>
                <input type="number" v-model="shippingInfo.inside_fixed_cost"
                    class="form-control" step="0.01" min="0" placeholder="e.g., 150">
            </div>

            <div class="col-md-3 mb-3">
                <label class="form-label d-block">Outside Fixed Cost</label>
                <input type="number" v-model="shippingInfo.outside_fixed_cost"
                    class="form-control" step="0.01" min="0" placeholder="e.g., 250">
            </div>
        </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <div class="form-check form-switch">
                    <input type="checkbox" v-model="shippingInfo.is_fragile" 
                        class="form-check-input" id="isFragileCheck" :true-value="1" :false-value="0">
                    <label class="form-check-label" for="isFragileCheck">
                        This is a fragile item
                    </label>
                </div>
            </div>

            <div class="col-md-6 mb-3">
                <div class="form-check form-switch">
                    <input type="checkbox" v-model="shippingInfo.returnable" 
                        class="form-check-input" id="returnableCheck" :true-value="1" :false-value="0">
                    <label class="form-check-label" for="returnableCheck">
                        This product is returnable
                    </label>
                </div>
            </div>
        </div>
    </div>

    <!-- Tax Information -->
    <div class="col-12 mb-4">
        <h5 class="border-bottom pb-2 mb-3">Tax Information</h5>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label d-block">Tax Class ID</label>
                <input type="number" v-model="taxInfo.tax_class_id" class="form-control" 
                    min="0" placeholder="Tax class identifier">
                <small class="text-muted d-block">Leave empty if no tax applicable</small>
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label d-block">Tax Percentage (%)</label>
                <input type="number" v-model="taxInfo.tax_percent" class="form-control" 
                    step="0.01" min="0" max="100" placeholder="e.g., 5, 15">
            </div>
        </div>
    </div>

    <!-- Additional Info -->
    <div class="col-12">
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> 
            <strong>Note:</strong> Shipping and tax information helps calculate accurate delivery charges 
            and final prices for customers. Fill in these details carefully.
        </div>
    </div>

</div>
