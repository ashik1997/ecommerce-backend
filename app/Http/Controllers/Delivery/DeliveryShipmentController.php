<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Controller;
use App\Models\Delivery\DeliveryEmployee;
use App\Models\Delivery\DeliveryProvider;
use App\Models\Delivery\DeliveryShipment;

class DeliveryShipmentController extends Controller
{
    public function index()
    {
        $query = DeliveryShipment::query()
            ->with(['provider', 'employee', 'order'])
            ->latest();

        if (request()->filled('q')) {
            $search = request('q');
            $query->where(function ($shipmentQuery) use ($search) {
                $shipmentQuery->where('shipment_code', 'like', '%' . $search . '%')
                    ->orWhere('recipient_name', 'like', '%' . $search . '%')
                    ->orWhere('recipient_phone', 'like', '%' . $search . '%')
                    ->orWhere('tracking_number', 'like', '%' . $search . '%');
            });
        }

        if (request()->filled('provider_id')) {
            $query->where('provider_id', request('provider_id'));
        }

        if (request()->filled('current_status')) {
            $query->where('current_status', request('current_status'));
        }

        return view('backend.delivery_management.shipments.index', [
            'shipments' => $query->paginate(25)->appends(request()->query()),
            'providers' => DeliveryProvider::orderBy('name')->get(['id', 'name']),
            'statuses' => $this->statuses(),
        ]);
    }

    public function show(DeliveryShipment $shipment)
    {
        return view('backend.delivery_management.shipments.show', [
            'shipment' => $shipment->load([
                'provider',
                'employee',
                'order',
                'assignments.provider',
                'assignments.employee',
                'codCollections.employee',
                'statusLogs',
            ]),
            'providers' => DeliveryProvider::where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'employees' => DeliveryEmployee::where('status', 'active')->orderBy('name')->get(['id', 'name', 'phone', 'current_status']),
            'statuses' => $this->statuses(),
            'codStatuses' => [
                'pending' => 'Pending',
                'collected' => 'Collected',
                'partially_collected' => 'Partially Collected',
                'submitted' => 'Submitted',
                'verified' => 'Verified',
                'posted' => 'Posted',
                'disputed' => 'Disputed',
            ],
        ]);
    }

    private function statuses(): array
    {
        return [
            'draft' => 'Draft',
            'ready_for_dispatch' => 'Ready for Dispatch',
            'assigned' => 'Assigned',
            'assignment_accepted' => 'Assignment Accepted',
            'pickup_requested' => 'Pickup Requested',
            'picked_up' => 'Picked Up',
            'in_transit' => 'In Transit',
            'out_for_delivery' => 'Out for Delivery',
            'delivery_attempted' => 'Delivery Attempted',
            'delivered' => 'Delivered',
            'partial_delivered' => 'Partial Delivered',
            'delivery_failed' => 'Delivery Failed',
            'returned' => 'Returned',
            'return_in_transit' => 'Return In Transit',
            'returned_to_store' => 'Returned To Store',
            'cancelled' => 'Cancelled',
            'closed' => 'Closed',
        ];
    }
}
