@extends('backend.master')

@section('content')
    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Product Offers</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                                <li class="breadcrumb-item active">Product Offers</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">All offers</h5>
                            <a href="{{ route('product-management.product-offers.create') }}" class="btn btn-success btn-sm">
                                <i class="fas fa-plus"></i> Create offer
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Offer title</th>
                                            <th>Date</th>
                                            <th>Total products</th>
                                            <th width="160">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($offers as $offer)
                                            <tr>
                                                <td>{{ $offer->title }}</td>
                                                <td>{{ $offer->start_date?->format('Y-m-d') }}
                                                    — {{ $offer->end_date?->format('Y-m-d') }}</td>
                                                <td>{{ $offer->items_count }}</td>
                                                <td>
                                                    <a href="{{ route('product-management.product-offers.show', $offer) }}"
                                                        class="btn btn-sm btn-info">View</a>
                                                    <a href="{{ route('product-management.product-offers.edit', $offer) }}"
                                                        class="btn btn-sm btn-primary">Edit</a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-muted">No offers yet.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            {{ $offers->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
