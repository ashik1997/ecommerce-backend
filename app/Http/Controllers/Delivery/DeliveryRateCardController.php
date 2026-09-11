<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Controller;
use App\Models\Delivery\DeliveryProvider;
use App\Models\Delivery\DeliveryRateCard;
use App\Models\Delivery\DeliveryServiceType;
use App\Models\Delivery\DeliveryZone;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeliveryRateCardController extends Controller
{
    public function index()
    {
        $query = DeliveryRateCard::query()
            ->with(['provider', 'zone', 'serviceType'])
            ->latest();

        if (request()->filled('provider_id')) {
            $query->where('provider_id', request('provider_id'));
        }

        if (request()->filled('zone_id')) {
            $query->where('zone_id', request('zone_id'));
        }

        if (request()->filled('status')) {
            $query->where('status', request('status'));
        }

        return view('backend.delivery_management.rate_cards.index', [
            'rateCards' => $query->paginate(25)->appends(request()->query()),
            'providers' => $this->providers(),
            'zones' => $this->zones(),
            'serviceTypes' => $this->serviceTypes(),
            'codChargeTypes' => $this->codChargeTypes(),
        ]);
    }

    public function create()
    {
        return view('backend.delivery_management.rate_cards.create', [
            'rateCard' => new DeliveryRateCard(['status' => 'active']),
            'providers' => $this->providers(),
            'zones' => $this->zones(),
            'serviceTypes' => $this->serviceTypes(),
            'codChargeTypes' => $this->codChargeTypes(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $data['creator'] = auth()->id();

        $rateCard = DeliveryRateCard::create($data);

        return redirect()
            ->route('delivery-management.rate-cards.edit', $rateCard)
            ->with('success', 'Delivery rate card created successfully.');
    }

    public function show(DeliveryRateCard $rateCard)
    {
        return view('backend.delivery_management.rate_cards.show', [
            'rateCard' => $rateCard->load(['provider', 'zone', 'serviceType']),
        ]);
    }

    public function edit(DeliveryRateCard $rateCard)
    {
        return view('backend.delivery_management.rate_cards.edit', [
            'rateCard' => $rateCard,
            'providers' => $this->providers(),
            'zones' => $this->zones(),
            'serviceTypes' => $this->serviceTypes(),
            'codChargeTypes' => $this->codChargeTypes(),
        ]);
    }

    public function update(Request $request, DeliveryRateCard $rateCard)
    {
        $rateCard->update($this->validatedData($request));

        return redirect()
            ->route('delivery-management.rate-cards.edit', $rateCard)
            ->with('success', 'Delivery rate card updated successfully.');
    }

    public function destroy(DeliveryRateCard $rateCard)
    {
        $rateCard->update(['status' => 'inactive']);

        return redirect()
            ->route('delivery-management.rate-cards.index')
            ->with('success', 'Delivery rate card deactivated.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'provider_id' => ['required', 'integer', 'exists:delivery_providers,id'],
            'zone_id' => ['nullable', 'integer', 'exists:delivery_zones,id'],
            'service_type_id' => ['nullable', 'integer', 'exists:delivery_service_types,id'],
            'minimum_weight' => ['nullable', 'numeric', 'min:0'],
            'maximum_weight' => ['nullable', 'numeric', 'min:0'],
            'base_charge' => ['nullable', 'numeric', 'min:0'],
            'additional_weight_charge' => ['nullable', 'numeric', 'min:0'],
            'cod_charge_type' => ['nullable', Rule::in(array_keys($this->codChargeTypes()))],
            'cod_charge_value' => ['nullable', 'numeric', 'min:0'],
            'return_charge' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);
    }

    private function providers()
    {
        return DeliveryProvider::query()->orderBy('name')->get(['id', 'name']);
    }

    private function zones()
    {
        return DeliveryZone::query()->orderBy('name')->get(['id', 'name']);
    }

    private function serviceTypes()
    {
        return DeliveryServiceType::query()->orderBy('name')->get(['id', 'name', 'provider_id']);
    }

    private function codChargeTypes(): array
    {
        return [
            'none' => 'None',
            'fixed' => 'Fixed',
            'percentage' => 'Percentage',
        ];
    }
}
