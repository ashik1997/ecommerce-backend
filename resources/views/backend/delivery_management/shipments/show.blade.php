@extends('backend.master')
@section('page_title', 'Delivery Shipment')
@section('page_heading', 'Delivery Shipment')
@section('content')
    @include('backend.delivery_management.partials.nav')

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Could not update shipment.</strong>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-7 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="mb-3">{{ $shipment->shipment_code }}</h4>
                    <table class="table table-bordered">
                        <tr><th style="width: 220px;">Order</th><td>{{ $shipment->order->order_code ?? $shipment->source_reference }}</td></tr>
                        <tr><th>Provider</th><td>{{ $shipment->provider->name ?? 'N/A' }}</td></tr>
                        <tr><th>Employee</th><td>{{ $shipment->employee->name ?? 'N/A' }}</td></tr>
                        <tr><th>Recipient</th><td>{{ $shipment->recipient_name ?: 'N/A' }} / {{ $shipment->recipient_phone ?: 'N/A' }}</td></tr>
                        <tr><th>Address</th><td>{{ $shipment->recipient_address ?: 'N/A' }}</td></tr>
                        <tr><th>COD Amount</th><td>{{ number_format((float) $shipment->cod_amount, 2) }}</td></tr>
                        <tr><th>Customer Charge</th><td>{{ number_format((float) $shipment->customer_delivery_charge, 2) }}</td></tr>
                        <tr><th>Status</th><td>{{ ucwords(str_replace('_', ' ', $shipment->current_status)) }}</td></tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-5 mb-3">
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="mb-3">COD Collection</h5>
                    @php($codCollection = $shipment->codCollections->first())
                    <form method="post" action="{{ route('delivery-management.shipments.cod-collections.store', $shipment) }}">
                        @csrf
                        <div class="form-group">
                            <label>Delivery Employee</label>
                            <select name="delivery_employee_id" class="form-control">
                                <option value="">No employee</option>
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->id }}" {{ (string) old('delivery_employee_id', $codCollection->delivery_employee_id ?? $shipment->delivery_employee_id) === (string) $employee->id ? 'selected' : '' }}>{{ $employee->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label>Expected</label>
                                <input type="number" step="0.01" name="expected_amount" class="form-control" value="{{ old('expected_amount', $codCollection->expected_amount ?? $shipment->cod_amount) }}">
                            </div>
                            <div class="col-md-4 form-group">
                                <label>Collected</label>
                                <input type="number" step="0.01" name="collected_amount" class="form-control" value="{{ old('collected_amount', $codCollection->collected_amount ?? 0) }}">
                            </div>
                            <div class="col-md-4 form-group">
                                <label>Submitted</label>
                                <input type="number" step="0.01" name="submitted_amount" class="form-control" value="{{ old('submitted_amount', $codCollection->submitted_amount ?? 0) }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="collection_status" class="form-control">
                                @foreach ($codStatuses as $value => $label)
                                    <option value="{{ $value }}" {{ old('collection_status', $codCollection->collection_status ?? 'pending') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Note</label>
                            <textarea name="note" class="form-control" rows="2">{{ old('note', $codCollection->note ?? '') }}</textarea>
                        </div>
                        <button class="btn btn-success"><i class="feather-save"></i> Save COD</button>
                    </form>
                    @if ($codCollection && $codCollection->collection_status !== 'verified')
                        <form method="post" action="{{ route('delivery-management.cod-collections.verify', $codCollection) }}" class="d-inline-block mt-2">
                            @csrf
                            <button class="btn btn-outline-success">Verify COD</button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="mb-3">Assign / Reassign</h5>
                    <form method="post" action="{{ route('delivery-management.shipments.assign', $shipment) }}">
                        @csrf
                        <div class="form-group">
                            <label>Provider</label>
                            <select name="provider_id" class="form-control">
                                <option value="">Keep current / no provider</option>
                                @foreach ($providers as $provider)
                                    <option value="{{ $provider->id }}" {{ (string) $shipment->provider_id === (string) $provider->id ? 'selected' : '' }}>{{ $provider->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Delivery Employee</label>
                            <select name="delivery_employee_id" class="form-control">
                                <option value="">No employee</option>
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->id }}" {{ (string) $shipment->delivery_employee_id === (string) $employee->id ? 'selected' : '' }}>
                                        {{ $employee->name }}{{ $employee->phone ? ' - ' . $employee->phone : '' }} ({{ str_replace('_', ' ', $employee->current_status) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Note</label>
                            <textarea name="note" class="form-control" rows="2"></textarea>
                        </div>
                        <button class="btn btn-primary"><i class="feather-user-check"></i> Assign Shipment</button>
                    </form>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="mb-3">Update Status</h5>
                    <form method="post" action="{{ route('delivery-management.shipments.status.update', $shipment) }}">
                        @csrf
                        <div class="form-group">
                            <label>Status</label>
                            <select name="current_status" class="form-control">
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}" {{ $shipment->current_status === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Note</label>
                            <textarea name="note" class="form-control" rows="2"></textarea>
                        </div>
                        <button class="btn btn-info"><i class="feather-refresh-cw"></i> Update Status</button>
                    </form>
                </div>
            </div>

            <div class="card h-100">
                <div class="card-body">
                    <h5 class="mb-3">Status History</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead><tr><th>Status</th><th>Source</th><th>Time</th></tr></thead>
                            <tbody>
                                @forelse ($shipment->statusLogs as $log)
                                    <tr>
                                        <td>{{ ucwords(str_replace('_', ' ', $log->status)) }}</td>
                                        <td>{{ $log->source }}</td>
                                        <td>{{ optional($log->created_at)->format('Y-m-d H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted">No status history found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <h5 class="mb-3 mt-4">Meta</h5>
                    <pre class="bg-light p-2 rounded small">{{ json_encode($shipment->meta ?: [], JSON_PRETTY_PRINT) }}</pre>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="mb-3">Assignment History</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th>Provider</th>
                            <th>Employee</th>
                            <th>Status</th>
                            <th>Assigned</th>
                            <th>Completed</th>
                            <th>Note</th>
                            <th style="width: 220px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($shipment->assignments as $assignment)
                            <tr>
                                <td>{{ $assignment->provider->name ?? 'N/A' }}</td>
                                <td>{{ $assignment->employee->name ?? 'N/A' }}</td>
                                <td><span class="badge badge-info">{{ ucwords(str_replace('_', ' ', $assignment->status)) }}</span></td>
                                <td>{{ optional($assignment->assigned_at)->format('Y-m-d H:i') ?: 'N/A' }}</td>
                                <td>{{ optional($assignment->completed_at)->format('Y-m-d H:i') ?: 'N/A' }}</td>
                                <td>{{ $assignment->note ?: 'N/A' }}</td>
                                <td>
                                    @if (in_array($assignment->status, ['pending', 'accepted'], true))
                                        <form method="post" action="{{ route('delivery-management.shipments.assignments.status', [$shipment, $assignment]) }}" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="status" value="accepted">
                                            <button class="btn btn-sm btn-success">Accept</button>
                                        </form>
                                        <form method="post" action="{{ route('delivery-management.shipments.assignments.status', [$shipment, $assignment]) }}" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="status" value="rejected">
                                            <button class="btn btn-sm btn-warning">Reject</button>
                                        </form>
                                        <form method="post" action="{{ route('delivery-management.shipments.assignments.status', [$shipment, $assignment]) }}" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="status" value="cancelled">
                                            <button class="btn btn-sm btn-danger">Cancel</button>
                                        </form>
                                    @else
                                        <span class="text-muted">No action</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted">No assignment history found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
