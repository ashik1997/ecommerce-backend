<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Controller;
use App\Models\Delivery\DeliveryCodCollection;
use App\Models\Delivery\DeliveryEmployee;
use App\Models\Delivery\DeliveryShipment;
use Illuminate\Http\Request;

class DeliveryCodCollectionController extends Controller
{
    public function index()
    {
        $query = DeliveryCodCollection::query()
            ->with(['shipment.provider', 'employee'])
            ->latest();

        if (request()->filled('collection_status')) {
            $query->where('collection_status', request('collection_status'));
        }

        if (request()->filled('delivery_employee_id')) {
            $query->where('delivery_employee_id', request('delivery_employee_id'));
        }

        return view('backend.delivery_management.cod_collections.index', [
            'collections' => $query->paginate(25)->appends(request()->query()),
            'employees' => DeliveryEmployee::where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'statuses' => $this->statuses(),
        ]);
    }

    public function store(Request $request, DeliveryShipment $shipment)
    {
        $data = $this->validatedData($request);

        $collection = DeliveryCodCollection::updateOrCreate(
            ['delivery_shipment_id' => $shipment->id],
            array_merge($data, [
                'delivery_employee_id' => $data['delivery_employee_id'] ?? $shipment->delivery_employee_id,
                'expected_amount' => $data['expected_amount'] ?? $shipment->cod_amount,
                'collected_at' => $this->timestampFor($data['collection_status'] ?? 'pending', 'collected'),
                'submitted_at' => $this->timestampFor($data['collection_status'] ?? 'pending', 'submitted'),
            ])
        );

        $shipment->update([
            'payment_status' => in_array($collection->collection_status, ['verified', 'posted'], true) ? 'paid' : 'pending',
        ]);

        return redirect()
            ->route('delivery-management.shipments.show', $shipment)
            ->with('success', 'COD collection saved successfully.');
    }

    public function update(Request $request, DeliveryCodCollection $codCollection)
    {
        $data = $this->validatedData($request);
        $data['collected_at'] = $codCollection->collected_at ?: $this->timestampFor($data['collection_status'] ?? 'pending', 'collected');
        $data['submitted_at'] = $codCollection->submitted_at ?: $this->timestampFor($data['collection_status'] ?? 'pending', 'submitted');

        $codCollection->update($data);
        $codCollection->shipment()->update([
            'payment_status' => in_array($codCollection->collection_status, ['verified', 'posted'], true) ? 'paid' : 'pending',
        ]);

        return redirect()
            ->back()
            ->with('success', 'COD collection updated successfully.');
    }

    public function verify(DeliveryCodCollection $codCollection)
    {
        $codCollection->update([
            'collection_status' => 'verified',
            'verified_at' => now(),
            'verified_by' => auth()->id(),
        ]);

        $codCollection->shipment()->update(['payment_status' => 'paid']);

        return redirect()
            ->back()
            ->with('success', 'COD collection verified successfully.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'delivery_employee_id' => ['nullable', 'integer', 'exists:delivery_employees,id'],
            'expected_amount' => ['nullable', 'numeric', 'min:0'],
            'collected_amount' => ['nullable', 'numeric', 'min:0'],
            'submitted_amount' => ['nullable', 'numeric', 'min:0'],
            'collection_status' => ['required', 'in:' . implode(',', array_keys($this->statuses()))],
            'note' => ['nullable', 'string'],
        ]);
    }

    private function timestampFor(string $status, string $event)
    {
        if ($event === 'collected' && in_array($status, ['collected', 'partially_collected', 'submitted', 'verified', 'posted'], true)) {
            return now();
        }

        if ($event === 'submitted' && in_array($status, ['submitted', 'verified', 'posted'], true)) {
            return now();
        }

        return null;
    }

    private function statuses(): array
    {
        return [
            'pending' => 'Pending',
            'collected' => 'Collected',
            'partially_collected' => 'Partially Collected',
            'submitted' => 'Submitted',
            'verified' => 'Verified',
            'posted' => 'Posted',
            'disputed' => 'Disputed',
        ];
    }
}
