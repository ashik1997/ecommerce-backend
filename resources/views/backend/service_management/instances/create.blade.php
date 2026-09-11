@extends('backend.master')
@section('header_css')
    <link href="{{ url('assets') }}/plugins/select2/select2.min.css" rel="stylesheet" type="text/css" />
@endsection
@section('page_title', 'Create Service Instance')
@section('page_heading', 'Create Service Instance')
@section('content')
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="mb-1">Create Service Instance</h5>
                    <p class="mb-0 text-muted">Select a service to auto-load linked products, then adjust quantities and charges.</p>
                </div>
                <a href="{{ route('service-management.instances.index') }}" class="btn btn-secondary">Back to Instances</a>
            </div>

            <form method="POST" action="{{ route('service-management.instances.store') }}" id="service-instance-form">
                @csrf
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Customer <span class="text-danger">*</span></label>
                        <select name="customer_id" class="form-control select2 @error('customer_id') is-invalid @enderror" required>
                            <option value="">Select Customer</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name ?? $customer->full_name }}{{ $customer->phone ? ' - ' . $customer->phone : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('customer_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Service <span class="text-danger">*</span></label>
                        <select name="service_id" id="service_id" class="form-control select2 @error('service_id') is-invalid @enderror" required>
                            <option value="">Select Service</option>
                            @foreach ($services as $service)
                                <option value="{{ $service->id }}" {{ old('service_id') == $service->id ? 'selected' : '' }}>
                                    {{ $service->name }} ({{ ucfirst($service->type) }} / {{ ucfirst($service->billing_unit) }})
                                </option>
                            @endforeach
                        </select>
                        @error('service_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" id="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date', now()->toDateString()) }}">
                        @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" id="end_date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date') }}">
                        @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">Billing Qty <span class="text-danger">*</span></label>
                        <input type="number" step="0.0001" min="0.0001" name="billing_unit_qty" id="billing_unit_qty" class="form-control @error('billing_unit_qty') is-invalid @enderror" value="{{ old('billing_unit_qty', 1) }}" required>
                        <small class="text-muted">Day/month services auto-calculate from dates.</small>
                        @error('billing_unit_qty')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">Service Unit Price <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="service_unit_price" id="service_unit_price" class="form-control @error('service_unit_price') is-invalid @enderror" value="{{ old('service_unit_price', 0) }}" required>
                        @error('service_unit_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">Billing Unit</label>
                        <input type="text" id="billing_unit_label" class="form-control" value="-" readonly>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-control @error('status') is-invalid @enderror" required>
                            @foreach ($statuses as $status)
                                <option value="{{ $status }}" {{ old('status', 'confirmed') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Note</label>
                        <input type="text" name="note" class="form-control @error('note') is-invalid @enderror" value="{{ old('note') }}">
                        @error('note')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="card bg-light mb-3">
                    <div class="card-body">
                        @error('products')
                            <div class="alert alert-danger">{{ $message }}</div>
                        @enderror
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <h5 class="mb-1">Products</h5>
                                <p class="mb-0 text-muted">Linked products will load automatically. You can remove rows or edit quantity/price before saving.</p>
                            </div>
                            <div class="text-right">
                                <div>Service: <strong id="service_subtotal_label">0.00</strong></div>
                                <div>Products: <strong id="products_subtotal_label">0.00</strong></div>
                                <div>Total: <strong id="total_amount_label">0.00</strong></div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <select id="manual_product_id" class="form-control"></select>
                            </div>
                            <div class="col-md-2">
                                <button type="button" id="add_manual_product" class="btn btn-outline-primary btn-block">Add Product</button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-sm bg-white">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th style="width: 140px;">Quantity</th>
                                        <th style="width: 160px;">Unit Price</th>
                                        <th style="width: 120px;">Required</th>
                                        <th>Note</th>
                                        <th style="width: 120px;">Total</th>
                                        <th style="width: 90px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="product_rows">
                                    <tr id="empty_product_row">
                                        <td colspan="7" class="text-center text-muted">Select a service to load linked products.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <button class="btn btn-success">Save Service Instance</button>
            </form>
        </div>
    </div>
@endsection

@section('footer_js')
    <script src="{{ url('assets') }}/plugins/select2/select2.min.js"></script>
    <script>
        var productRowIndex = 0;
        var manualProduct = null;
        var currentBillingUnit = null;

        $('.select2').select2({ width: '100%' });

        $('#manual_product_id').select2({
            width: '100%',
            placeholder: 'Search additional product by name, SKU, or code',
            allowClear: true,
            ajax: {
                url: '{{ route('product-management.search-products') }}',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { term: params.term || '' };
                },
                processResults: function (data) {
                    return data;
                },
            },
        }).on('select2:select', function (event) {
            manualProduct = event.params.data;
        }).on('select2:clear', function () {
            manualProduct = null;
        });

        $('#service_id').on('change', function () {
            var serviceId = $(this).val();
            resetProducts('Select a service to load linked products.');

            if (!serviceId) {
                currentBillingUnit = null;
                $('#service_unit_price').val('0.00');
                $('#billing_unit_label').val('-');
                recalculateTotals();
                return;
            }

            $.get('{{ url('/service-management/instances/service-products') }}/' + serviceId, function (response) {
                currentBillingUnit = response.service.billing_unit || null;
                $('#service_unit_price').val(Number(response.service.base_price || 0).toFixed(2));
                $('#billing_unit_label').val(currentBillingUnit || '-');
                updateBillingQtyFromDates();
                resetProducts('No linked products for this service.');

                response.products.forEach(function (product) {
                    addProductRow(product);
                });

                recalculateTotals();
            });
        });

        $('#add_manual_product').on('click', function () {
            if (!manualProduct || !manualProduct.id) {
                alert('Please select a product first.');
                return;
            }

            addProductRow({
                product_id: manualProduct.id,
                name: manualProduct.text,
                sku: manualProduct.sku || '',
                quantity_used: 1,
                unit_price: manualProduct.price || 0,
                is_required: false,
                note: '',
            });

            $('#manual_product_id').val(null).trigger('change');
            manualProduct = null;
            recalculateTotals();
        });

        $(document).on('input change', '#billing_unit_qty, #service_unit_price, .product-qty, .product-price', recalculateTotals);
        $(document).on('change', '#start_date, #end_date', function () {
            updateBillingQtyFromDates();
            recalculateTotals();
        });

        $(document).on('click', '.remove-product-row', function () {
            $(this).closest('tr').remove();

            if ($('#product_rows tr').length === 0) {
                resetProducts('No products selected.');
            }

            recalculateTotals();
        });

        function resetProducts(message) {
            productRowIndex = 0;
            $('#product_rows').html('<tr id="empty_product_row"><td colspan="7" class="text-center text-muted">' + escapeHtml(message) + '</td></tr>');
        }

        function addProductRow(product) {
            $('#empty_product_row').remove();

            var index = productRowIndex++;
            var sku = product.sku ? ' <span class="text-muted">[' + escapeHtml(product.sku) + ']</span>' : '';
            var checked = product.is_required ? 'checked' : '';

            $('#product_rows').append(
                '<tr>' +
                    '<td>' +
                        '<strong>' + escapeHtml(product.name || 'Product') + '</strong>' + sku +
                        '<input type="hidden" name="products[' + index + '][product_id]" value="' + product.product_id + '">' +
                    '</td>' +
                    '<td><input type="number" step="1" min="1" name="products[' + index + '][quantity_used]" class="form-control product-qty" value="' + Math.max(1, Math.round(Number(product.quantity_used || 1))) + '" required></td>' +
                    '<td><input type="number" step="0.01" min="0" name="products[' + index + '][unit_price]" class="form-control product-price" value="' + Number(product.unit_price || 0).toFixed(2) + '" required></td>' +
                    '<td><label class="mb-0"><input type="checkbox" name="products[' + index + '][is_required]" value="1" ' + checked + '> Required</label></td>' +
                    '<td><input type="text" name="products[' + index + '][note]" class="form-control" value="' + escapeAttr(product.note || '') + '"></td>' +
                    '<td class="product-row-total">0.00</td>' +
                    '<td><button type="button" class="btn btn-sm btn-danger remove-product-row">Remove</button></td>' +
                '</tr>'
            );
        }

        function recalculateTotals() {
            var serviceQty = Number($('#billing_unit_qty').val() || 0);
            var serviceUnitPrice = Number($('#service_unit_price').val() || 0);
            var serviceSubtotal = serviceQty * serviceUnitPrice;
            var productsSubtotal = 0;

            $('#product_rows tr').each(function () {
                var row = $(this);
                var qtyInput = row.find('.product-qty');

                if (!qtyInput.length) {
                    return;
                }

                var qty = Number(qtyInput.val() || 0);
                var price = Number(row.find('.product-price').val() || 0);
                var total = qty * price;
                productsSubtotal += total;
                row.find('.product-row-total').text(total.toFixed(2));
            });

            $('#service_subtotal_label').text(serviceSubtotal.toFixed(2));
            $('#products_subtotal_label').text(productsSubtotal.toFixed(2));
            $('#total_amount_label').text((serviceSubtotal + productsSubtotal).toFixed(2));
        }

        function updateBillingQtyFromDates() {
            if (currentBillingUnit !== 'day' && currentBillingUnit !== 'month') {
                return;
            }

            var startDate = $('#start_date').val();
            var endDate = $('#end_date').val();

            if (!startDate || !endDate) {
                return;
            }

            var start = new Date(startDate + 'T00:00:00');
            var end = new Date(endDate + 'T00:00:00');

            if (end < start) {
                return;
            }

            var days = Math.floor((end - start) / 86400000) + 1;
            var qty = currentBillingUnit === 'month' ? Math.max(1, Math.ceil(days / 30)) : Math.max(1, days);

            $('#billing_unit_qty').val(qty.toFixed(4));
        }

        function escapeHtml(value) {
            return String(value).replace(/[&<>"']/g, function (char) {
                return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char];
            });
        }

        function escapeAttr(value) {
            return escapeHtml(value).replace(/"/g, '&quot;');
        }

        if ($('#service_id').val()) {
            $('#service_id').trigger('change');
        } else {
            recalculateTotals();
        }
    </script>
@endsection
