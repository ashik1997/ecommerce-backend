<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Controller;
use App\Models\Delivery\DeliveryCodCollection;
use App\Models\Delivery\DeliveryEmployee;
use App\Models\Delivery\DeliveryProvider;
use App\Models\Delivery\DeliverySettlement;
use App\Models\Delivery\DeliverySettlementItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DeliverySettlementController extends Controller
{
    public function index()
    {
        $query = DeliverySettlement::query()
            ->with(['provider', 'employee'])
            ->withCount('items')
            ->latest();

        if (request()->filled('settlement_type')) {
            $query->where('settlement_type', request('settlement_type'));
        }

        if (request()->filled('status')) {
            $query->where('status', request('status'));
        }

        return view('backend.delivery_management.settlements.index', [
            'settlements' => $query->paginate(25)->appends(request()->query()),
            'types' => $this->types(),
            'statuses' => $this->statuses(),
        ]);
    }

    public function create()
    {
        $collectionQuery = DeliveryCodCollection::query()
            ->with(['shipment.provider', 'employee'])
            ->where('collection_status', 'verified')
            ->whereDoesntHave('shipment.settlementItems');

        if (request()->filled('provider_id')) {
            $collectionQuery->whereHas('shipment', function ($shipmentQuery) {
                $shipmentQuery->where('provider_id', request('provider_id'));
            });
        }

        if (request()->filled('delivery_employee_id')) {
            $collectionQuery->where('delivery_employee_id', request('delivery_employee_id'));
        }

        return view('backend.delivery_management.settlements.create', [
            'collections' => $collectionQuery->latest()->paginate(50)->appends(request()->query()),
            'providers' => DeliveryProvider::where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'employees' => DeliveryEmployee::where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'types' => $this->types(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'settlement_type' => ['required', 'in:' . implode(',', array_keys($this->types()))],
            'provider_id' => ['nullable', 'integer', 'exists:delivery_providers,id'],
            'delivery_employee_id' => ['nullable', 'integer', 'exists:delivery_employees,id'],
            'collection_ids' => ['required', 'array', 'min:1'],
            'collection_ids.*' => ['integer', 'exists:delivery_cod_collections,id'],
            'settlement_date' => ['nullable', 'date'],
            'note' => ['nullable', 'string'],
        ]);

        $settlement = DB::transaction(function () use ($data) {
            $collections = DeliveryCodCollection::query()
                ->with('shipment')
                ->whereIn('id', $data['collection_ids'])
                ->where('collection_status', 'verified')
                ->whereDoesntHave('shipment.settlementItems')
                ->get();

            if ($collections->isEmpty()) {
                abort(422, 'No verified unsettled COD collections found.');
            }

            $settlement = DeliverySettlement::create([
                'settlement_code' => $this->makeSettlementCode(),
                'provider_id' => $data['provider_id'] ?? null,
                'delivery_employee_id' => $data['delivery_employee_id'] ?? null,
                'settlement_type' => $data['settlement_type'],
                'settlement_date' => $data['settlement_date'] ?? now()->toDateString(),
                'total_cod_amount' => 0,
                'total_delivery_cost' => 0,
                'total_adjustment' => 0,
                'net_amount' => 0,
                'status' => 'draft',
                'created_by' => auth()->id(),
                'note' => $data['note'] ?? null,
            ]);

            $totalCod = 0;
            $totalDeliveryCost = 0;

            foreach ($collections as $collection) {
                $shipment = $collection->shipment;
                if (!$shipment) {
                    continue;
                }

                $codAmount = (float) $collection->submitted_amount;
                if ($codAmount <= 0) {
                    $codAmount = (float) $collection->collected_amount;
                }

                $deliveryCost = (float) ($shipment->provider_cost ?? 0);
                $netAmount = $codAmount - $deliveryCost;

                DeliverySettlementItem::create([
                    'delivery_settlement_id' => $settlement->id,
                    'delivery_shipment_id' => $shipment->id,
                    'cod_amount' => $codAmount,
                    'delivery_cost' => $deliveryCost,
                    'adjustment_amount' => 0,
                    'net_amount' => $netAmount,
                    'status' => 'pending',
                    'note' => null,
                ]);

                $shipment->update(['settlement_status' => 'in_settlement']);

                $totalCod += $codAmount;
                $totalDeliveryCost += $deliveryCost;
            }

            $settlement->update([
                'total_cod_amount' => $totalCod,
                'total_delivery_cost' => $totalDeliveryCost,
                'net_amount' => $totalCod - $totalDeliveryCost,
            ]);

            return $settlement;
        });

        return redirect()
            ->route('delivery-management.settlements.show', $settlement)
            ->with('success', 'Delivery settlement draft created successfully.');
    }

    public function show(DeliverySettlement $settlement)
    {
        return view('backend.delivery_management.settlements.show', [
            'settlement' => $settlement->load(['provider', 'employee', 'items.shipment.provider']),
            'types' => $this->types(),
            'statuses' => $this->statuses(),
        ]);
    }

    public function approve(DeliverySettlement $settlement)
    {
        DB::transaction(function () use ($settlement) {
            $settlement->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            $settlement->items()->update(['status' => 'settled']);

            foreach ($settlement->items as $item) {
                $item->shipment()->update(['settlement_status' => 'settled']);
            }
        });

        return redirect()
            ->route('delivery-management.settlements.show', $settlement)
            ->with('success', 'Delivery settlement approved successfully.');
    }

    private function makeSettlementCode(): string
    {
        do {
            $code = 'DSET-' . now()->format('ymd') . '-' . Str::upper(Str::random(5));
        } while (DeliverySettlement::where('settlement_code', $code)->exists());

        return $code;
    }

    private function types(): array
    {
        return [
            'external_provider_settlement' => 'External Provider Settlement',
            'internal_employee_cash_handover' => 'Internal Employee Cash Handover',
            'provider_bill_payment' => 'Provider Bill Payment',
            'return_adjustment' => 'Return Adjustment',
        ];
    }

    private function statuses(): array
    {
        return [
            'draft' => 'Draft',
            'approved' => 'Approved',
            'posted' => 'Posted',
            'cancelled' => 'Cancelled',
        ];
    }
}
