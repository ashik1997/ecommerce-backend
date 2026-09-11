@extends('backend.master')

@section('header_css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    @if ($productOffer->custom_style)
        <style>
            {!! $productOffer->custom_style !!}
        </style>
    @endif
@endsection

@section('content')
    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Offer: {{ $productOffer->title }}</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a
                                        href="{{ route('product-management.product-offers.index') }}">Product
                                        offers</a></li>
                                <li class="breadcrumb-item active">Details</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <div class="row mb-3">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">{{ $productOffer->title }}</h5>
                            <p class="mb-1 text-muted">
                                {{ $productOffer->start_date?->format('Y-m-d') }}
                                — {{ $productOffer->end_date?->format('Y-m-d') }}
                            </p>
                            <p class="mb-0">
                                <span
                                    class="badge {{ $productOffer->status ? 'badge-success' : 'badge-secondary' }}">{{ $productOffer->status ? 'Active' : 'Inactive' }}</span>
                                <span class="text-muted ml-2">{{ $productOffer->items_count }} product(s)</span>
                            </p>
                            @if ($productOffer->background_image)
                                <div class="mt-3">
                                    <img src="{{ asset($productOffer->background_image) }}" alt=""
                                        class="img-fluid rounded border" style="max-height:220px;">
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-md-right">
                    <a href="{{ route('product-management.product-offers.edit', $productOffer) }}"
                        class="btn btn-primary mb-2 d-block d-md-inline-block">
                        <i class="fas fa-edit"></i> Edit offer
                    </a>
                    <button type="button" class="btn btn-success mb-2 d-block d-md-inline-block" data-toggle="modal"
                        data-target="#addProductModal">
                        <i class="fas fa-plus"></i> Add new product
                    </button>
                    <a href="{{ route('product-management.product-offers.index') }}"
                        class="btn btn-outline-secondary d-block d-md-inline-block">Back to list</a>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Products in this offer</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" id="offerProductsTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Product name</th>
                                            <th width="120">Price</th>
                                            <th width="140">Discount price</th>
                                            <th width="120">Discount %</th>
                                            <th width="100">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($items as $item)
                                            @php
                                                $base = (float) $item->price;
                                                $dpVal =
                                                    $item->discount_price !== null
                                                        ? (float) $item->discount_price
                                                        : $base;
                                                $pctVal =
                                                    $item->discount_percent !== null ? (int) $item->discount_percent : 0;
                                            @endphp
                                            <tr data-item-id="{{ $item->id }}"
                                                data-remove-url="{{ route('product-management.product-offers.items.destroy', [$productOffer, $item]) }}"
                                                data-discount-url="{{ route('product-management.product-offers.items.discount', [$productOffer, $item]) }}">
                                                <td>{{ $item->product->name ?? '—' }}</td>
                                                <td class="text-right">{{ number_format($base, 2) }}</td>
                                                <td>
                                                    <input type="number" step="0.01" min="0"
                                                        class="form-control form-control-sm js-offer-dp"
                                                        value="{{ number_format($dpVal, 2, '.', '') }}"
                                                        data-base-price="{{ $base }}">
                                                </td>
                                                <td>
                                                    <input type="number" min="0" max="100"
                                                        class="form-control form-control-sm js-offer-pct"
                                                        value="{{ $pctVal }}" data-base-price="{{ $base }}">
                                                </td>
                                                <td>
                                                    <button type="button"
                                                        class="btn btn-sm btn-danger js-remove-item">Remove</button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            {{ $items->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('backend.product_offers.partials.modal_add_product')
@endsection

@section('footer_js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        (function($) {
            var csrf = $('meta[name="csrf-token"]').attr('content');
            var addItemUrl = @json(route('product-management.product-offers.items.store', $productOffer));
            var searchUrl = @json(url('/api/products-search'));

            $(document).on('focusin', '.js-offer-dp, .js-offer-pct', function() {
                var $tr = $(this).closest('tr');
                $tr.data('snap-dp', $tr.find('.js-offer-dp').val());
                $tr.data('snap-pct', $tr.find('.js-offer-pct').val());
            });

            function fmt(n) {
                return Number(n).toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            $('#offerProductSelect').select2({
                dropdownParent: $('#addProductModal'),
                placeholder: 'Search by product name',
                allowClear: true,
                width: '100%',
                ajax: {
                    url: searchUrl,
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            search: params.term,
                            page: params.page || 1,
                            scope: 'all'
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.results,
                            pagination: {
                                more: data.pagination.more
                            }
                        };
                    },
                    cache: true
                }
            });

            $('#offerProductSelect').on('select2:select', function(e) {
                var id = e.params.data.id;
                $.ajax({
                    url: addItemUrl,
                    method: 'POST',
                    data: {
                        _token: csrf,
                        product_id: id
                    },
                    success: function(res) {
                        if (typeof toastr !== 'undefined') {
                            toastr.success(res.message || 'Product added');
                        }
                        $('#offerProductSelect').val(null).trigger('change');
                        window.location.reload();
                    },
                    error: function(xhr) {
                        var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message :
                            'Could not add product';
                        if (typeof toastr !== 'undefined') {
                            toastr.error(msg);
                        } else {
                            alert(msg);
                        }
                    }
                });
            });

            function patchDiscount($tr, payload) {
                var url = $tr.data('discount-url');
                $.ajax({
                    url: url,
                    method: 'PATCH',
                    data: $.extend({
                        _token: csrf
                    }, payload),
                    success: function(res) {
                        $tr.find('.js-offer-dp').val(Number(res.discount_price).toFixed(2));
                        $tr.find('.js-offer-pct').val(res.discount_percent);
                        $tr.data('snap-dp', $tr.find('.js-offer-dp').val());
                        $tr.data('snap-pct', $tr.find('.js-offer-pct').val());
                        if (typeof toastr !== 'undefined') {
                            toastr.success(res.message || 'Updated');
                        }
                    },
                    error: function(xhr) {
                        var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message :
                            'Update failed';
                        if (typeof toastr !== 'undefined') {
                            toastr.error(msg);
                        }
                        $tr.find('.js-offer-dp').val($tr.data('snap-dp'));
                        $tr.find('.js-offer-pct').val($tr.data('snap-pct'));
                    }
                });
            }

            $(document).on('change', '.js-offer-dp', function() {
                var $inp = $(this);
                var $tr = $inp.closest('tr');
                var base = parseFloat($inp.data('base-price')) || 0;
                if (base <= 0) return;
                var dp = parseFloat($inp.val());
                if (isNaN(dp)) return;
                var pct = Math.round(((base - dp) / base) * 100);
                Swal.fire({
                    title: 'Confirm discount price',
                    html: '<p>Original price: <strong>' + fmt(base) + '</strong></p>' +
                        '<p>Discount price: <strong>' + fmt(dp) + '</strong></p>' +
                        '<p>Calculated percent: <strong>' + pct + '%</strong></p>',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Save',
                    cancelButtonText: 'Cancel'
                }).then(function(result) {
                    if (!result.isConfirmed) {
                        $tr.find('.js-offer-dp').val($tr.data('snap-dp'));
                        $tr.find('.js-offer-pct').val($tr.data('snap-pct'));
                        return;
                    }
                    patchDiscount($tr, {
                        mode: 'discount_price',
                        discount_price: dp
                    });
                });
            });

            $(document).on('change', '.js-offer-pct', function() {
                var $inp = $(this);
                var $tr = $inp.closest('tr');
                var base = parseFloat($inp.data('base-price')) || 0;
                if (base <= 0) return;
                var p = parseInt($inp.val(), 10);
                if (isNaN(p)) return;
                var dp = Math.round((base - (base * p / 100)) * 100) / 100;
                Swal.fire({
                    title: 'Confirm discount percent',
                    html: '<p>Original price: <strong>' + fmt(base) + '</strong></p>' +
                        '<p>Calculated discount price: <strong>' + fmt(dp) + '</strong></p>' +
                        '<p>Percent: <strong>' + p + '%</strong></p>',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Save',
                    cancelButtonText: 'Cancel'
                }).then(function(result) {
                    if (!result.isConfirmed) {
                        $tr.find('.js-offer-dp').val($tr.data('snap-dp'));
                        $tr.find('.js-offer-pct').val($tr.data('snap-pct'));
                        return;
                    }
                    patchDiscount($tr, {
                        mode: 'discount_percent',
                        discount_percent: p
                    });
                });
            });

            $(document).on('click', '.js-remove-item', function() {
                var $tr = $(this).closest('tr');
                var url = $tr.data('remove-url');
                Swal.fire({
                    title: 'Remove product?',
                    text: 'This product will be removed from the offer.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Remove',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#d33'
                }).then(function(result) {
                    if (!result.isConfirmed) return;
                    $.ajax({
                        url: url,
                        method: 'DELETE',
                        data: {
                            _token: csrf
                        },
                        success: function(res) {
                            if (typeof toastr !== 'undefined') {
                                toastr.success(res.message || 'Removed');
                            }
                            $tr.remove();
                        },
                        error: function() {
                            if (typeof toastr !== 'undefined') {
                                toastr.error('Could not remove');
                            }
                        }
                    });
                });
            });
        })(jQuery);
    </script>
@endsection
