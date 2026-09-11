@include('invoice.purchase.purchase_order_invoice', [
    'order'       => $order,
    'generalInfo' => $generalInfo,
    'isPublic'    => true,
])
