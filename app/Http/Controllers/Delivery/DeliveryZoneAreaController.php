<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Controller;
use App\Models\Delivery\DeliveryZone;
use App\Models\Delivery\DeliveryZoneArea;
use Illuminate\Http\Request;

class DeliveryZoneAreaController extends Controller
{
    public function store(Request $request, DeliveryZone $zone)
    {
        $data = $request->validate([
            'district_id' => ['nullable', 'integer', 'exists:districts,id'],
            'upazila_id' => ['nullable', 'integer', 'exists:upazilas,id'],
            'area_id' => ['nullable', 'integer'],
            'area_name' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        $data['zone_id'] = $zone->id;
        $data['status'] = $data['status'] ?? 'active';

        DeliveryZoneArea::create($data);

        return redirect()
            ->route('delivery-management.zones.edit', $zone)
            ->with('success', 'Zone area added successfully.');
    }

    public function destroy(DeliveryZone $zone, DeliveryZoneArea $area)
    {
        if ((int) $area->zone_id !== (int) $zone->id) {
            abort(404);
        }

        $area->delete();

        return redirect()
            ->route('delivery-management.zones.edit', $zone)
            ->with('success', 'Zone area removed successfully.');
    }
}
