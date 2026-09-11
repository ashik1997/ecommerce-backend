@extends('backend.master')

@section('header_css')
    <style>
        .checkout-config-card .form-check-label {
            user-select: none;
        }
    </style>
@endsection

@section('page_title')
    Checkout Page Config
@endsection
@section('page_heading')
    Update Checkout Page Config
@endsection

@section('content')
    <div class="row" id="checkout-config-app">
        <div class="col-lg-12 col-xl-12">
            <div class="card">
                <div class="card-body checkout-config-card">
                    <h4 class="card-title mb-3">Checkout Page Config Update Form</h4>

                    <form class="needs-validation" @submit.prevent="saveConfig">
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="is_global_free_shipping" v-model="form.is_global_free_shipping">
                                <label class="custom-control-label" for="is_global_free_shipping">Global Free Shipping</label>
                            </div>
                        </div>

                        <div class="form-group" v-if="!form.is_global_free_shipping">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="is_shipping_charge_by_area" v-model="form.is_shipping_charge_by_area">
                                <label class="custom-control-label" for="is_shipping_charge_by_area">Shipping Charge By Area</label>
                            </div>
                        </div>

                        <div class="form-group" v-if="!form.is_global_free_shipping">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="is_shipping_charge_by_weight" v-model="form.is_shipping_charge_by_weight">
                                <label class="custom-control-label" for="is_shipping_charge_by_weight">Shipping Charge By Weight</label>
                            </div>
                        </div>

                        <div class="row" v-if="!form.is_global_free_shipping && !form.is_shipping_charge_by_weight">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="inside_dhaka_shipping_charge">Inside Dhaka Shipping Charge</label>
                                    <input type="number" min="0" step="0.01" class="form-control" id="inside_dhaka_shipping_charge" v-model.number="form.inside_dhaka_shipping_charge">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="outside_dhaka_shipping_charge">Outside Dhaka Shipping Charge</label>
                                    <input type="number" min="0" step="0.01" class="form-control" id="outside_dhaka_shipping_charge" v-model.number="form.outside_dhaka_shipping_charge">
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="is_show_zila" v-model="form.is_show_zila">
                                <label class="custom-control-label" for="is_show_zila">Show Zila</label>
                            </div>
                        </div>

                        <div class="form-group" v-if="form.is_show_zila">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="is_show_upozilla" v-model="form.is_show_upozilla">
                                <label class="custom-control-label" for="is_show_upozilla">Show Upozilla</label>
                            </div>
                        </div>

                        <div class="form-group" v-if="form.is_show_zila && form.is_show_upozilla">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="is_show_thana" v-model="form.is_show_thana">
                                <label class="custom-control-label" for="is_show_thana">Show Thana</label>
                            </div>
                        </div>

                        <div class="form-group" v-if="form.is_show_zila && form.is_show_upozilla && form.is_show_thana">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="is_show_post_office" v-model="form.is_show_post_office">
                                <label class="custom-control-label" for="is_show_post_office">Show Post Office</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="is_show_email" v-model="form.is_show_email">
                                <label class="custom-control-label" for="is_show_email">Show Email Field</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="is_show_store_pickup" v-model="form.is_show_store_pickup">
                                <label class="custom-control-label" for="is_show_store_pickup">Show Store Pickup</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="shipping_charge_note">Shipping Charge Note</label>
                            <textarea class="form-control" id="shipping_charge_note" v-model="form.shipping_charge_note"></textarea>
                        </div>


                        <div class="form-group text-center pt-3">
                            <button class="btn btn-primary" type="submit" :disabled="loading">
                                @{{ loading ? 'Saving...' : 'Update Info' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
@endsection


@section('footer_js')
    <script src="https://cdn.jsdelivr.net/npm/vue@2.7.16/dist/vue.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        new Vue({
            el: '#checkout-config-app',
            data: {
                loading: false,
                form: {
                    is_global_free_shipping: @json((bool) ($data->is_global_free_shipping ?? 0)),
                    is_shipping_charge_by_weight: @json((bool) ($data->is_shipping_charge_by_weight ?? 1)),
                    inside_dhaka_shipping_charge: @json((float) ($data->inside_dhaka_shipping_charge ?? 50)),
                    outside_dhaka_shipping_charge: @json((float) ($data->outside_dhaka_shipping_charge ?? 120)),
                    is_shipping_charge_by_area: @json((bool) ($data->is_shipping_charge_by_area ?? 0)),
                    is_show_zila: @json((bool) ($data->is_show_zila ?? 1)),
                    is_show_upozilla: @json((bool) ($data->is_show_upozilla ?? 0)),
                    is_show_thana: @json((bool) ($data->is_show_thana ?? 0)),
                    is_show_post_office: @json((bool) ($data->is_show_post_office ?? 0)),
                    is_show_email: @json((bool) ($data->is_show_email ?? 0)),
                    is_show_store_pickup: @json((bool) ($data->is_show_store_pickup ?? 0)),
                    shipping_charge_note: @json((string) ($data->shipping_charge_note ?? '')),
                }
            },
            watch: {
                'form.is_show_zila': function (val) {
                    if (!val) {
                        this.form.is_show_upozilla = false;
                        this.form.is_show_thana = false;
                        this.form.is_show_post_office = false;
                    }
                },
                'form.is_show_upozilla': function (val) {
                    if (!val) {
                        this.form.is_show_thana = false;
                        this.form.is_show_post_office = false;
                    }
                },
                'form.is_show_thana': function (val) {
                    if (!val) {
                        this.form.is_show_post_office = false;
                    }
                }
            },
            methods: {
                saveConfig: function () {
                    var self = this;
                    self.loading = true;

                    axios.post("{{ url('/save-checkout-config') }}", {
                        is_global_free_shipping: self.form.is_global_free_shipping ? 1 : 0,
                        is_shipping_charge_by_weight: self.form.is_shipping_charge_by_weight ? 1 : 0,
                        inside_dhaka_shipping_charge: self.form.inside_dhaka_shipping_charge || 50,
                        outside_dhaka_shipping_charge: self.form.outside_dhaka_shipping_charge || 120,
                        is_shipping_charge_by_area: self.form.is_shipping_charge_by_area ? 1 : 0,
                        is_show_zila: self.form.is_show_zila ? 1 : 0,
                        is_show_upozilla: self.form.is_show_upozilla ? 1 : 0,
                        is_show_thana: self.form.is_show_thana ? 1 : 0,
                        is_show_post_office: self.form.is_show_post_office ? 1 : 0,
                        is_show_email: self.form.is_show_email ? 1 : 0,
                        is_show_store_pickup: self.form.is_show_store_pickup ? 1 : 0,
                        shipping_charge_note: self.form.shipping_charge_note || '',
                    }, {
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    }).then(function (response) {
                        if (window.toastr) {
                            toastr.success(response.data.message || 'Checkout config saved successfully.');
                        } else {
                            alert(response.data.message || 'Checkout config saved successfully.');
                        }
                    }).catch(function (error) {
                        var errorMessage = 'Failed to save checkout config.';
                        if (error.response && error.response.data && error.response.data.message) {
                            errorMessage = error.response.data.message;
                        }
                        if (window.toastr) {
                            toastr.error(errorMessage);
                        } else {
                            alert(errorMessage);
                        }
                    }).finally(function () {
                        self.loading = false;
                    });
                }
            }
        });
    </script>
@endsection
