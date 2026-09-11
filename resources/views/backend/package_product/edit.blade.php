@extends('backend.master')

@section('content')
<div class="page-content">
    <div class="container-fluid">

        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <div>
                <h4 class="mb-sm-0">Edit Package: {{ $package->title }}</h4>
                <p class="text-muted mb-0">
                    <span class="badge bg-secondary me-1 text-white">{{ $package->package_code }}</span>
                    Last updated {{ $package->updated_at->diffForHumans() }}
                </p>
            </div>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('PackageProducts.Index') }}">Packages</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </div>
        </div>

        <div id="packageCreateApp" v-cloak>

            {{-- Tab navigation --}}
            <div class="package_management">
                <div class="pm-tabs-wrap">
                    <ul class="pm-tabs" role="tablist">
                        <li role="presentation">
                            <button class="pm-tab-btn" :class="{active: isActiveTab('overview')}"
                                @click="switchTab('overview')" type="button">
                                <i class="fas fa-rocket"></i>
                                <span>Overview</span>
                            </button>
                        </li>
                        <li role="presentation">
                            <button class="pm-tab-btn" :class="{active: isActiveTab('catalog')}"
                                @click="switchTab('catalog')" type="button">
                                <i class="fas fa-boxes"></i>
                                <span>Select Products</span>
                                <span class="pm-tab-badge" v-if="items.length">@{{ items.length }}</span>
                            </button>
                        </li>
                        <li role="presentation">
                            <button class="pm-tab-btn" :class="{active: isActiveTab('details')}"
                                @click="switchTab('details')" type="button">
                                <i class="fas fa-info-circle"></i>
                                <span>Package Info</span>
                            </button>
                        </li>
                        <li role="presentation">
                            <button class="pm-tab-btn" :class="{active: isActiveTab('seo')}"
                                @click="switchTab('seo')" type="button">
                                <i class="fas fa-search"></i>
                                <span>SEO &amp; Meta</span>
                            </button>
                        </li>
                    </ul>
                </div>
            </div>

            {{-- Tab content --}}
            @include('backend.package_product.partials.form-tabs')

            {{-- Action bar --}}
            <div class="package_management">
                <div class="pm-action-bar">
                    <div class="pm-action-left">
                        <a href="{{ route('PackageProducts.Index') }}" class="pm-btn pm-btn--ghost">
                            <i class="fas fa-arrow-left"></i> Back to list
                        </a>
                        <button type="button" class="pm-btn pm-btn--danger-ghost"
                            @click="clearAll" :disabled="isSubmitting">
                            <i class="fas fa-trash-alt"></i> Reset form
                        </button>
                    </div>
                    <div class="pm-action-right">
                        <div class="pm-totals-line" v-if="itemsTotals.itemsTotal > 0">
                            <span>৳@{{ itemsTotals.itemsTotal.toFixed(2) }}</span>
                            <span class="sep">|</span>
                            <span>Compare ৳@{{ itemsTotals.compareTotal.toFixed(2) }}</span>
                            <span class="sep">|</span>
                            <span>Save ৳@{{ itemsTotals.savingsAmount.toFixed(2) }}</span>
                        </div>
                        <button type="button" class="pm-btn pm-btn--success pm-btn--lg"
                            @click="submitPackage" :disabled="isSubmitting">
                            <span v-if="!isSubmitting"><i class="fas fa-save"></i> Update Package</span>
                            <span v-else><span class="spinner-border spinner-border-sm" style="width:14px;height:14px;border-width:2px;"></span> Saving…</span>
                        </button>
                    </div>
                </div>
            </div>

        </div>{{-- #packageCreateApp --}}
    </div>
</div>
@endsection

@push('header_css')
@include('backend.package_product.partials.builder-styles')
@endpush

@push('js')
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="{{ versioned_asset('assets/js/vue.min.js') }}"></script>
<script>
    window.packageBuilderSeed = {
        mode:       'edit',
        packageId:  {{ $package->id }},
        draftKey:   'package_builder_edit_{{ $package->id }}',
        statuses:   @json($statuses),
        visibility: @json($visibilityOptions),
        categories: @json($categories),
        websites:   @json($websites),
        routes: {
            store:       null,
            update:      "{{ route('PackageProducts.Update', $package->id) }}",
            search:      "{{ route('PackageProducts.Search') }}",
            matrix:      "{{ route('PackageProducts.ProductMatrix', ['productId' => '__ID__']) }}",
            mediaUpload: "{{ url('/media/upload') }}"
        },
        csrf: "{{ csrf_token() }}"
    };
    window.packageBuilderState = @json($packageState);
</script>
<script src="{{ versioned_asset('assets/js/package_builder_vue.js') }}"></script>
@endpush