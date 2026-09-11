@extends('backend.master')
@section('page_title', 'Create Service')
@section('page_heading', 'Create Service')
@section('content')
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="mb-1">Create Service</h5>
                    <p class="mb-0 text-muted">Define the base service before assigning products.</p>
                </div>
                <a href="{{ route('service-management.services.index') }}" class="btn btn-secondary">Back to Services</a>
            </div>
            <form method="POST" action="{{ route('service-management.services.store') }}">
                @include('backend.service_management.services._form')
                <button class="btn btn-success">Save Service</button>
            </form>
        </div>
    </div>
@endsection
