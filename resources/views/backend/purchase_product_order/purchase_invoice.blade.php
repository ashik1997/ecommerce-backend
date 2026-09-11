{{--
    This view is kept for backwards-compatibility.
    The controller now renders invoice.purchase.purchase_order_invoice directly.
    If you want to include the invoice within the admin layout, use:
        @include('invoice.purchase.purchase_order_invoice', [...])
--}}
@extends('backend.master')
@section('content')
    @include('invoice.purchase.purchase_order_invoice', [
        'order' => $order,
        'generalInfo' => $generalInfo,
        'isPublic' => false,
    ])
@endsection
