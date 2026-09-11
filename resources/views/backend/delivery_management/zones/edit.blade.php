@extends('backend.master')
@section('page_title', 'Edit Delivery Zone')
@section('page_heading', 'Edit Delivery Zone')
@section('header_css')
    <link href="{{ url('assets') }}/plugins/select2/select2.min.css" rel="stylesheet" type="text/css" />
@endsection
@section('content')
    @include('backend.delivery_management.partials.nav')

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="post" action="{{ route('delivery-management.zones.update', $zone) }}" class="mb-3">
        @csrf
        @method('PUT')
        @include('backend.delivery_management.zones._form')
    </form>

    <div class="card">
        <div class="card-body">
            <h5 class="mb-3">Zone Areas</h5>
            <form method="post" action="{{ route('delivery-management.zones.areas.store', $zone) }}" class="row mb-3">
                @csrf
                <div class="col-md-3 mb-2">
                    <label for="zone_area_district_id" class="mb-1">District</label>
                    <select name="district_id" id="zone_area_district_id" class="form-control select2">
                        <option value="">Select District</option>
                        @foreach ($districts as $district)
                            <option value="{{ $district->id }}" {{ old('district_id') == $district->id ? 'selected' : '' }}>{{ $district->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <label for="zone_area_upazila_id" class="mb-1">Upazila</label>
                    <select name="upazila_id" id="zone_area_upazila_id" class="form-control select2">
                        <option value="">Select Upazila</option>
                        @foreach ($upazilas as $upazila)
                            <option value="{{ $upazila->id }}" data-district-id="{{ $upazila->district_id }}" {{ old('upazila_id') == $upazila->id ? 'selected' : '' }}>{{ $upazila->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <label for="zone_area_name" class="mb-1">Area Name</label>
                    <input type="text" name="area_name" id="zone_area_name" class="form-control" placeholder="Area name">
                </div>
                <div class="col-md-2 mb-2">
                    <label for="zone_area_status" class="mb-1">Status</label>
                    <select name="status" id="zone_area_status" class="form-control">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-md-1 mb-2">
                    <label class="mb-1 d-block">&nbsp;</label>
                    <button class="btn btn-primary btn-block"><i class="feather-plus"></i></button>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead><tr><th>Area</th><th>District</th><th>Upazila</th><th>Status</th><th style="width: 90px;">Action</th></tr></thead>
                    <tbody>
                        @forelse ($zone->areas as $area)
                            <tr>
                                <td>{{ $area->area_name ?: 'N/A' }}</td>
                                <td>{{ $area->district->name ?? ($area->district_id ?: 'N/A') }}</td>
                                <td>{{ $area->upazila->name ?? ($area->upazila_id ?: 'N/A') }}</td>
                                <td><span class="badge badge-{{ $area->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($area->status) }}</span></td>
                                <td>
                                    <form method="post" action="{{ route('delivery-management.zones.areas.destroy', [$zone, $area]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger" onclick="return confirm('Remove this area?')">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">No area mapped yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('footer_js')
    <script src="{{ url('assets') }}/plugins/select2/select2.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var districtSelect = $('#zone_area_district_id');
            var upazilaSelect = $('#zone_area_upazila_id');
            var allUpazilaOptions = upazilaSelect.find('option').clone();

            $('.select2').select2({
                width: '100%',
                placeholder: 'Select an option',
                allowClear: true
            });

            function filterUpazilas() {
                var districtId = districtSelect.val();
                var selectedUpazila = upazilaSelect.val();

                upazilaSelect.empty();
                allUpazilaOptions.each(function () {
                    var option = $(this);
                    var optionDistrictId = option.data('district-id');
                    if (!option.val() || !districtId || String(optionDistrictId) === String(districtId)) {
                        upazilaSelect.append(option.clone());
                    }
                });

                if (selectedUpazila && upazilaSelect.find('option[value="' + selectedUpazila + '"]').length) {
                    upazilaSelect.val(selectedUpazila);
                } else {
                    upazilaSelect.val('');
                }

                upazilaSelect.trigger('change.select2');
            }

            districtSelect.on('change', filterUpazilas);
            filterUpazilas();
        });
    </script>
@endsection
