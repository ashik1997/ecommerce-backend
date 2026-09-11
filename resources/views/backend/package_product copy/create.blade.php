@extends('backend.master')

@section('content')
@include('backend.package_product.partials.builder-styles')
<div class="page-content">
    <div class="container-fluid">

        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <div>
                <h4 class="mb-sm-0">Create Marketing Package</h4>
                <p class="text-muted mb-0">Compose a bundled offer, craft its landing experience, and optimise SEO before publishing.</p>
            </div>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('PackageProducts.Index') }}">Packages</a></li>
                    <li class="breadcrumb-item active">Create</li>
                </ol>
            </div>
        </div>

        <div id="packageCreateApp" v-cloak>

            {{-- Draft restore banner --}}
            <div class="alert alert-warning alert-dismissible fade show d-flex align-items-center justify-content-between"
                 v-if="autoSave.hasStoredData">
                <div>
                    <i class="fas fa-history me-2"></i>
                    <strong>Draft detected.</strong>
                    <span class="d-block">You have unsaved package data from @{{ autoSave.lastSavedTime | formatDateTime }}.</span>
                </div>
                <div class="btn-group">
                    <button class="btn btn-sm btn-success" @click="restoreDraft"><i class="fas fa-undo"></i> Restore</button>
                    <button class="btn btn-sm btn-outline-danger" @click="discardDraft"><i class="fas fa-trash-alt"></i> Discard</button>
                    <button class="btn-close ms-2" @click="autoSave.hasStoredData = false"></button>
                </div>
            </div>

            {{-- Tab navigation --}}
            <div class="card">
                <div class="card-body pb-1">
                    <ul class="nav nav-pills nav-justified package-tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" :class="{active: isActiveTab('overview')}" @click="switchTab('overview')" type="button">
                                <i class="fas fa-rocket"></i>
                                <span>Overview</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" :class="{active: isActiveTab('catalog')}" @click="switchTab('catalog')" type="button">
                                <i class="fas fa-boxes"></i>
                                <span>Select Products</span>
                                <span class="badge bg-primary ms-2" v-if="items.length">@{{ items.length }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" :class="{active: isActiveTab('details')}" @click="switchTab('details')" type="button">
                                <i class="fas fa-info-circle"></i>
                                <span>Package Info</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" :class="{active: isActiveTab('seo')}" @click="switchTab('seo')" type="button">
                                <i class="fas fa-search"></i>
                                <span>SEO &amp; Metadata</span>
                            </button>
                        </li>
                    </ul>
                </div>
            </div>

            {{-- Tab content (shared partial) --}}
            @include('backend.package_product.partials.form-tabs')

            {{-- Action bar --}}
            <div class="card mt-4">
                <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                    <div class="mb-3 mb-md-0">
                        <a href="{{ route('PackageProducts.Index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Back to list
                        </a>
                        <button type="button" class="btn btn-outline-danger ms-2" @click="clearAll" :disabled="isSubmitting">
                            <i class="fas fa-trash-alt"></i> Reset form
                        </button>
                    </div>
                    <div class="text-md-end">
                        <small class="text-muted d-block mb-1" v-if="itemsTotals.itemsTotal > 0">
                            Bundle totals: ৳@{{ itemsTotals.itemsTotal.toFixed(2) }} |
                            Compare ৳@{{ itemsTotals.compareTotal.toFixed(2) }} |
                            Save ৳@{{ itemsTotals.savingsAmount.toFixed(2) }}
                        </small>
                        <button type="button" class="btn btn-primary btn-lg" @click="submitPackage" :disabled="isSubmitting">
                            <span v-if="!isSubmitting"><i class="fas fa-save me-2"></i>Create Package</span>
                            <span v-else><span class="spinner-border spinner-border-sm me-2"></span>Processing...</span>
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
        mode:       'create',
        packageId:  null,
        draftKey:   'package_builder_create',
        statuses:   @json($statuses),
        visibility: @json($visibilityOptions),
        categories: @json($categories),
        websites:   @json($websites),
        routes: {
            store:       "{{ route('PackageProducts.Store') }}",
            update:      null,
            search:      "{{ route('PackageProducts.Search') }}",
            matrix:      "{{ route('PackageProducts.ProductMatrix', ['productId' => '__ID__']) }}",
            mediaUpload: "{{ url('/media/upload') }}"
        },
        csrf: "{{ csrf_token() }}"
    };
    window.packageBuilderState = null;
</script>
<script src="{{ versioned_asset('assets/js/package_builder_vue.js') }}"></script>
@endpush