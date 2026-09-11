<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Controller;
use App\Models\Delivery\DeliveryAssignment;
use App\Models\Delivery\DeliveryEmployee;
use App\Models\Delivery\DeliveryProvider;
use App\Models\Delivery\DeliveryShipment;
use App\Models\Delivery\DeliveryShipmentStatusLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliveryShipmentWorkflowController extends Controller
{
    public function assign(Request $request, DeliveryShipment $shipment)
    {
        $data = $request->validate([
            'provider_id' => ['nullable', 'integer', 'exists:delivery_providers,id'],
            'delivery_employee_id' => ['nullable', 'integer', 'exists:delivery_employees,id'],
            'note' => ['nullable', 'string'],
        ]);

        if (empty($data['provider_id']) && empty($data['delivery_employee_id'])) {
            return redirect()
                ->route('delivery-management.shipments.show', $shipment)
                ->withErrors(['assignment' => 'Select a provider or delivery employee.']);
        }

        DB::transaction(function () use ($shipment, $data) {
            DeliveryAssignment::query()
                ->where('delivery_shipment_id', $shipment->id)
                ->whereIn('status', ['pending', 'accepted'])
                ->update([
                    'status' => 'reassigned',
                    'completed_at' => now(),
                ]);

            DeliveryAssignment::create([
                'delivery_shipment_id' => $shipment->id,
                'provider_id' => $data['provider_id'] ?? null,
                'delivery_employee_id' => $data['delivery_employee_id'] ?? null,
                'assigned_by' => auth()->id(),
                'assigned_at' => now(),
                'status' => 'pending',
                'note' => $data['note'] ?? null,
            ]);

            $shipment->update([
                'provider_id' => $data['provider_id'] ?? $shipment->provider_id,
                'delivery_employee_id' => $data['delivery_employee_id'] ?? $shipment->delivery_employee_id,
                'current_status' => 'assigned',
                'assigned_at' => now(),
            ]);

            if (!empty($data['delivery_employee_id'])) {
                DeliveryEmployee::where('id', $data['delivery_employee_id'])->update(['current_status' => 'assigned']);
            }

            $this->logStatus($shipment->fresh(), 'assigned', 'manual', $data['note'] ?? 'Shipment assigned.');
        });

        return redirect()
            ->route('delivery-management.shipments.show', $shipment)
            ->with('success', 'Shipment assigned successfully.');
    }

    public function updateStatus(Request $request, DeliveryShipment $shipment)
    {
        $data = $request->validate([
            'current_status' => ['required', 'string', 'max:80'],
            'note' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($shipment, $data) {
            $updates = ['current_status' => $data['current_status']];

            if ($data['current_status'] === 'picked_up') {
                $updates['picked_up_at'] = $shipment->picked_up_at ?: now();
            }

            if ($data['current_status'] === 'delivered') {
                $updates['delivered_at'] = $shipment->delivered_at ?: now();
                $updates['payment_status'] = $shipment->cod_amount > 0 ? 'pending' : 'paid';
            }

            if (in_array($data['current_status'], ['returned', 'returned_to_store'], true)) {
                $updates['returned_at'] = $shipment->returned_at ?: now();
            }

            if ($data['current_status'] === 'cancelled') {
                $updates['cancelled_at'] = $shipment->cancelled_at ?: now();
            }

            $shipment->update($updates);

            if ($shipment->delivery_employee_id && in_array($data['current_status'], ['delivered', 'returned', 'returned_to_store', 'cancelled', 'closed'], true)) {
                DeliveryEmployee::where('id', $shipment->delivery_employee_id)->update(['current_status' => 'available']);
            }

            if (in_array($data['current_status'], ['delivered', 'returned', 'returned_to_store', 'cancelled', 'closed'], true)) {
                DeliveryAssignment::query()
                    ->where('delivery_shipment_id', $shipment->id)
                    ->whereIn('status', ['pending', 'accepted'])
                    ->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                    ]);
            }

            $this->logStatus($shipment->fresh(), $data['current_status'], 'manual', $data['note'] ?? null);
        });

        return redirect()
            ->route('delivery-management.shipments.show', $shipment)
            ->with('success', 'Shipment status updated successfully.');
    }

    public function assignmentStatus(Request $request, DeliveryShipment $shipment, DeliveryAssignment $assignment)
    {
        if ((int) $assignment->delivery_shipment_id !== (int) $shipment->id) {
            abort(404);
        }

        $data = $request->validate([
            'status' => ['required', 'in:accepted,rejected,cancelled,completed'],
            'note' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($shipment, $assignment, $data) {
            $updates = ['status' => $data['status']];

            if ($data['status'] === 'accepted') {
                $updates['accepted_at'] = $assignment->accepted_at ?: now();
                $shipment->update(['current_status' => 'assignment_accepted']);
                $this->logStatus($shipment->fresh(), 'assignment_accepted', 'manual', $data['note'] ?? 'Assignment accepted.');
            }

            if ($data['status'] === 'rejected') {
                $updates['rejected_at'] = $assignment->rejected_at ?: now();
                $shipment->update(['current_status' => 'assigned']);
                $this->logStatus($shipment->fresh(), 'assigned', 'manual', $data['note'] ?? 'Assignment rejected.');
            }

            if (in_array($data['status'], ['cancelled', 'completed'], true)) {
                $updates['completed_at'] = $assignment->completed_at ?: now();
            }

            $assignment->update($updates);
        });

        return redirect()
            ->route('delivery-management.shipments.show', $shipment)
            ->with('success', 'Assignment updated successfully.');
    }

    public function options()
    {
        return [
            'providers' => DeliveryProvider::where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'employees' => DeliveryEmployee::where('status', 'active')->orderBy('name')->get(['id', 'name', 'phone', 'current_status']),
            'statuses' => $this->statuses(),
        ];
    }

    private function logStatus(DeliveryShipment $shipment, string $status, string $source, ?string $note): void
    {
        DeliveryShipmentStatusLog::create([
            'delivery_shipment_id' => $shipment->id,
            'status' => $status,
            'raw_status' => null,
            'source' => $source,
            'response_payload' => null,
            'note' => $note,
            'changed_by' => auth()->id(),
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
