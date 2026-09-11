@extends('backend.master')

@section('header_css')
    <link href="{{url('assets')}}/plugins/dropify/dropify.min.css" rel="stylesheet" type="text/css" />
    <link href="{{url('assets')}}/plugins/select2/select2.min.css" rel="stylesheet" type="text/css" />
    <style>
        .select2-selection{
            height: 34px !important;
            border: 1px solid #ced4da !important;
        }
        .select2 {
            width: 100% !important;
        }
        .bootstrap-tagsinput .badge {
            margin: 2px 2px !important;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice{
            background: #1B69D1;
            border-color: #1B69D1;
            color: white;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove{
            color: white;
        }
    </style>
@endsection

@section('page_title')
    Are Base Courier Charge Management
@endsection
@section('page_heading')
    Edit Area Base Courier Charge
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-12 col-xl-12">
            <div class="card">
                <div class="card-body">
                    
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title mb-3">Edit Area Base Courier Charge</h4>
                        <a href="{{ route('area-base-courier-charges.index')}}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                    </div>

                    <form class="needs-validation" method="POST" action="{{ route('area-base-courier-charges.update', $data->id) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <input type="hidden" name="id" value="{{$data->id}}">

                        <div class="form-group row">
                            <label for="area_base_courier_id" class="col-sm-2 col-form-label">Area Base Courier <span class="text-danger">*</span></label>
                            <div class="col-sm-10">
                                <select name="area_base_courier_id" class="form-control" id="area_base_courier_id" required>
                                    <option value="">Select One</option>
                                    @foreach ($courierNames as $courier)
                                        <option value="{{ $courier->id }}" @if($data->area_base_courier_id == $courier->id) selected @endif>{{ $courier->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" style="display: block;">
                                    @error('area_base_courier_id')
                                        {{ $message }}
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="area_name" class="col-sm-2 col-form-label">Area Name <span class="text-danger">*</span></label>
                            <div class="col-sm-10">
                                <input type="text" name="area_name" class="form-control" value="{{$data->area_name}}" id="area_name" placeholder="Area Name" required>
                                <div class="invalid-feedback" style="display: block;">
                                    @error('area_name')
                                        {{ $message }}
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="shipping_cost" class="col-sm-2 col-form-label">Shipping Cost <span class="text-danger">*</span></label>
                            <div class="col-sm-10">
                                <input type="number" name="shipping_cost" class="form-control" value="{{$data->shipping_cost}}" id="shipping_cost" placeholder="Shipping Cost" step="0.01" required>
                                <div class="invalid-feedback" style="display: block;">
                                    @error('shipping_cost')
                                        {{ $message }}
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="status" class="col-sm-2 col-form-label">Status <span class="text-danger">*</span></label>
                            <div class="col-sm-3">
                                <select name="status" class="form-control" id="status" required>
                                    <option value="">Select One</option>
                                    <option value="1" @if($data->status == 1) selected @endif>Active</option>
                                    <option value="0" @if($data->status == 0) selected @endif>Inactive</option>
                                </select>
                                <div class="invalid-feedback" style="display: block;">
                                    @error('status')
                                        {{ $message }}
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label"></label>
                            <div class="col-sm-10">
                                <button class="btn btn-primary" type="submit">Update</button>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection


@section('footer_js')
    <script src="{{url('assets')}}/plugins/dropify/dropify.min.js"></script>
    <script src="{{url('assets')}}/pages/fileuploads-demo.js"></script>
    <script src="{{url('assets')}}/plugins/select2/select2.min.js"></script>
    <script>
        $('[data-toggle="select2"]').select2();

    </script>

@endsection
