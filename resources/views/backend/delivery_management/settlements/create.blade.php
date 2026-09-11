@extends('backend.master')
@section('page_title', 'Create Delivery Settlement')
@section('page_heading', 'Create Delivery Settlement')
@section('content')
    @include('backend.delivery_management.partials.nav')

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Could not create settlement.</strong>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <h4 class="mb-3">Verified Unsettled COD Collections</h4>
            <form method="get" class="row mb-3">
                <div class="col-md-4 mb-2">
                    <select name="provider_id" class="form-control">
                        <option value="">All Providers</option>
                        @foreach ($providers as $provider)
                            <option value="{{ $provider->id }}" {{ (string) request('provider_id') === (string) $provider->id ? 'selected' : '' }}>{{ $provider->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-2">
                    <select name="delivery_employee_id" class="form-control">
                        <option value="">All Employees</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}" {{ (string) request('delivery_employee_id') === (string) $employee->id ? 'selected' : '' }}>{{ $employee->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <button class="btn btn-dark btn-block"><i class="feather-filter"></i> Filter</button>
                </div>
            </form>

            <form method="post" action="{{ route('delivery-management.settlements.store') }}">
                @csrf
                <div class="row mb-3">
                    <div class="col-md-4 mb-2">
                        <label>Settlement Type</label>
                        <select name="settlement_type" class="form-control" required>
                            @foreach ($types as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label>Provider</label>
                        <select name="provider_id" class="form-control">
                            <option value="">No provider</option>
                            @foreach ($providers as $provider)
                                <option value="{{ $provider->id }}" {{ (string) request('provider_id') === (string) $provider->id ? 'selected' : '' }}>{{ $provider->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label>Employee</label>
                        <select name="delivery_employee_id" class="form-control">
                            <option value="">No employee</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}" {{ (string) request('delivery_employee_id') === (string) $employee->id ? 'selected' : '' }}>{{ $employee->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <label>Date</label>
                        <input type="date" name="settlement_date" class="form-control" value="{{ now()->toDateString() }}">
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead><tr><th><input type="checkbox" onclick="$('input[name=&quot;collection_ids[]&quot;]').prop('checked', this.checked)"></th><th>Shipment</th><th>Provider</th><th>Employee</th><th>Submitted</th></tr></thead>
                        <tbody>
                            @forelse ($collections as $collection)
                                <tr>
                                    <td><input type="checkbox" name="collection_ids[]" value="{{ $collection->id }}"></td>
                                    <td>{{ $collection->shipment->shipment_code ?? 'N/A' }}</td>
                                    <td>{{ $collection->shipment->provider->name ?? 'N/A' }}</td>
                                    <td>{{ $collection->employee->name ?? 'N/A' }}</td>
                                    <td>{{ number_format((float) $collection->submitted_amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted">No verified unsettled COD collections found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="form-group">
                    <label>Note</label>
                    <textarea name="note" class="form-control" rows="2"></textarea>
                </div>
                <button class="btn btn-primary"><i class="feather-save"></i> Create Draft Settlement</button>
            </form>

            {{ $collections->links() }}
        </div>
    </div>
@endsection
