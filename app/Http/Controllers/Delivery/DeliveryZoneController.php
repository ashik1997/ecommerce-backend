<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\Delivery\DeliveryZone;
use App\Models\Upazila;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DeliveryZoneController extends Controller
{
    public function index()
    {
        $query = DeliveryZone::query()->withCount('areas')->orderBy('name');

        if (request()->filled('q')) {
            $search = request('q');
            $query->where(function ($zoneQuery) use ($search) {
                $zoneQuery->where('name', 'like', '%' . $search . '%')
                    ->orWhere('slug', 'like', '%' . $search . '%');
            });
        }

        if (request()->filled('status')) {
            $query->where('status', request('status'));
        }

        return view('backend.delivery_management.zones.index', [
            'zones' => $query->paginate(25)->appends(request()->query()),
        ]);
    }

    public function create()
    {
        return view('backend.delivery_management.zones.create', [
            'zone' => new DeliveryZone(['status' => 'active']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $data['creator'] = auth()->id();

        $zone = DeliveryZone::create($data);

        return redirect()
            ->route('delivery-management.zones.edit', $zone)
            ->with('success', 'Delivery zone created successfully.');
    }

    public function show(DeliveryZone $zone)
    {
        return view('backend.delivery_management.zones.show', [
            'zone' => $zone->load('areas'),
        ]);
    }

    public function edit(DeliveryZone $zone)
    {
        return view('backend.delivery_management.zones.edit', [
            'zone' => $zone->load(['areas.district', 'areas.upazila']),
            'districts' => District::orderBy('name')->get(['id', 'name']),
            'upazilas' => Upazila::orderBy('name')->get(['id', 'name', 'district_id']),
        ]);
    }

    public function update(Request $request, DeliveryZone $zone)
    {
        $data = $this->validatedData($request);
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);

        $zone->update($data);

        return redirect()
            ->route('delivery-management.zones.edit', $zone)
            ->with('success', 'Delivery zone updated successfully.');
    }

    public function destroy(DeliveryZone $zone)
    {
        $zone->update(['status' => 'inactive']);

        return redirect()
            ->route('delivery-management.zones.index')
            ->with('success', 'Delivery zone deactivated.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'product_website_id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:180'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);
    }
}
