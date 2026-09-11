@extends('backend.master')

@section('header_css')
    <link href="{{ url('assets') }}/plugins/select2/select2.min.css" rel="stylesheet" type="text/css" />
    <style>
        .product-row {
            background: #f8f9fa;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }
    </style>
@endsection

@section('page_title')
    Edit Manual Return
@endsection

@section('page_heading')
    Edit Manual Product Return
@endsection

@section('content')
    <div class="container" style="max-width: 1200px;">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4>Edit Return #{{ $return->return_code }}</h4>
                            <a href="{{ route('ViewAllManualProductReturns') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                        </div>

                        @if((int)($return->is_accounting_posted ?? 0) === 1)
                            <div class="alert alert-warning">
                                This return is already posted to accounting. Reverse it from the list before creating a corrected return.
                            </div>
                        @endif

                        <form id="returnForm" action="{{ route('UpdateManualProductReturn', $return->slug) }}" method="POST">
                            @csrf
                            <input type="hidden" name="manual_product_return_id" value="{{ $return->id }}">

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label>Return Code</label>
                                    <input type="text" class="form-control" value="{{ $return->return_code }}" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label for="customer_id">Customer <span class="text-danger">*</span></label>
                                    <select id="customer_id" class="form-control customer-select" name="customer_id" required>
                                        @if($return->customer)
                                            <option value="{{ $return->customer->id }}" selected>{{ $return->customer->name }}{{ $return->customer->phone ? ' - ' . $return->customer->phone : '' }}</option>
                                        @endif
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="return_date">Return Date <span class="text-danger">*</span></label>
                                    <input type="date" id="return_date" class="form-control" name="return_date" value="{{ $return->return_date }}" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="return_reason">Return Reason <span class="text-danger">*</span></label>
                                    <input type="text" id="return_reason" class="form-control" name="return_reason" value="{{ $return->return_reason }}" required>
                                </div>
                            </div>

                            <h5 class="mt-4">Return Items</h5>
                            <div id="returnItems">
                                @foreach($return->return_items as $index => $item)
                                <div class="product-row" data-index="{{ $index }}">
                                    <div class="row">
                                        <div class="col-md-5">
                                            <label>Product <span class="text-danger">*</span></label>
                                            <select class="form-control product-select" name="return_items[{{ $index }}][product_id]" required>
                                                @if($item->product_id)
                                                    <option value="{{ $item->product_id }}" data-price="{{ $item->unit_price }}" selected>{{ $item->product_name }}</option>
                                                @else
                                                    <option value="">-- Select Product --</option>
                                                @endif
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label>Qty <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control qty-input" name="return_items[{{ $index }}][qty]" value="{{ $item->qty }}" min="1" required>
                                        </div>
                                        <div class="col-md-2">
                                            <label>Unit Price <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control price-input" name="return_items[{{ $index }}][unit_price]" value="{{ $item->unit_price }}" step="0.01" required>
                                        </div>
                                        <div class="col-md-2">
                                            <label>Total</label>
                                            <input type="text" class="form-control item-total" value="৳{{ number_format($item->total_price, 2) }}" readonly>
                                        </div>
                                        <div class="col-md-1">
                                            <label>&nbsp;</label><br>
                                            <button type="button" class="btn btn-danger btn-sm remove-item"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>

                            @if((int)($return->is_accounting_posted ?? 0) !== 1)
                                <button type="button" id="addItem" class="btn btn-success btn-sm mb-3">
                                    <i class="fas fa-plus"></i> Add Another Item
                                </button>
                            @endif

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h5>Total Refund Amount: <span id="grandTotal" class="text-success">৳{{ number_format($return->total, 2) }}</span></h5>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="note">Note</label>
                                    <textarea id="note" class="form-control" name="note" rows="2">{{ $return->note }}</textarea>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-md-12 text-right">
                                    @if((int)($return->is_accounting_posted ?? 0) !== 1)
                                        <button type="submit" class="btn btn-success btn-lg">
                                            <i class="fas fa-save"></i> Update Return
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('footer_js')
    <script src="{{ url('assets') }}/plugins/select2/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            let itemIndex = {{ count($return->return_items) }};
            const customerSearchUrl = @json(route('ManualProductReturnCustomerSearch'));
            const productSearchUrl = @json(route('ManualProductReturnProductSearch'));

            function initCustomerSelect($el) {
                $el.select2({
                    placeholder: '-- Select Customer --',
                    allowClear: true,
                    ajax: {
                        url: customerSearchUrl,
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return { term: params.term || '', page: params.page || 1 };
                        },
                        processResults: function (data) {
                            return data;
                        },
                        cache: true
                    }
                });
            }

            function initProductSelect($el) {
                $el.select2({
                    placeholder: '-- Select Product --',
                    allowClear: true,
                    ajax: {
                        url: productSearchUrl,
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return { term: params.term || '', page: params.page || 1 };
                        },
                        processResults: function (data) {
                            return data;
                        },
                        cache: true
                    }
                });
            }

            initCustomerSelect($('#customer_id'));
            initProductSelect($('.product-select'));

            // Add item functionality (same as create)
            $('#addItem').on('click', function() {
                const newItem = `
                    <div class="product-row" data-index="${itemIndex}">
                        <div class="row">
                            <div class="col-md-5">
                                <label>Product <span class="text-danger">*</span></label>
                                <select class="form-control product-select" name="return_items[${itemIndex}][product_id]" required>
                                    <option value="">-- Select Product --</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label>Qty <span class="text-danger">*</span></label>
                                <input type="number" class="form-control qty-input" name="return_items[${itemIndex}][qty]" min="1" required>
                            </div>
                            <div class="col-md-2">
                                <label>Unit Price <span class="text-danger">*</span></label>
                                <input type="number" class="form-control price-input" name="return_items[${itemIndex}][unit_price]" step="0.01" required>
                            </div>
                            <div class="col-md-2">
                                <label>Total</label>
                                <input type="text" class="form-control item-total" readonly>
                            </div>
                            <div class="col-md-1">
                                <label>&nbsp;</label><br>
                                <button type="button" class="btn btn-danger btn-sm remove-item"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                    </div>
                `;
                $('#returnItems').append(newItem);
                initProductSelect($('#returnItems .product-select').last());
                itemIndex++;
            });

            $(document).on('click', '.remove-item', function() {
                if ($('.product-row').length > 1) {
                    $(this).closest('.product-row').remove();
                    calculateTotal();
                }
            });

            $(document).on('change', '.product-select', function() {
                const row = $(this).closest('.product-row');
                const selected = $(this).select2('data')[0] || {};
                const optionPrice = $(this).find(':selected').data('price');
                const price = parseFloat(selected.price || optionPrice) || 0;
                row.find('.price-input').val(price.toFixed(2)).trigger('input');
            });

            $(document).on('input', '.qty-input, .price-input', function() {
                const row = $(this).closest('.product-row');
                const qty = parseFloat(row.find('.qty-input').val()) || 0;
                const price = parseFloat(row.find('.price-input').val()) || 0;
                row.find('.item-total').val('৳' + (qty * price).toFixed(2));
                calculateTotal();
            });

            function calculateTotal() {
                let total = 0;
                $('.product-row').each(function() {
                    const qty = parseFloat($(this).find('.qty-input').val()) || 0;
                    const price = parseFloat($(this).find('.price-input').val()) || 0;
                    total += qty * price;
                });
                $('#grandTotal').text('৳' + total.toFixed(2));
            }

            // Form submission (similar to create)
            $('#returnForm').on('submit', function(e) {
                e.preventDefault();

                const formData = {
                    _token: $('input[name="_token"]').val(),
                    manual_product_return_id: $('input[name="manual_product_return_id"]').val(),
                    customer_id: $('#customer_id').val(),
                    return_date: $('#return_date').val(),
                    return_reason: $('#return_reason').val(),
                    note: $('#note').val(),
                    return_items: []
                };

                $('.product-row').each(function() {
                    const index = $(this).data('index');
                    formData.return_items.push({
                        product_id: $(this).find(`select[name="return_items[${index}][product_id]"]`).val() || null,
                        qty: parseInt($(this).find(`input[name="return_items[${index}][qty]"]`).val()),
                        unit_price: parseFloat($(this).find(`input[name="return_items[${index}][unit_price]"]`).val())
                    });
                });

                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.message
                            }).then(() => {
                                window.location.href = response.redirect;
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: xhr.responseJSON?.message || 'Something went wrong!'
                        });
                    }
                });
            });
        });
    </script>
@endsection

