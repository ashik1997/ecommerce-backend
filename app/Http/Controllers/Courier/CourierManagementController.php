<?php

namespace App\Http\Controllers\Courier;

use App\Http\Controllers\Controller;
use App\Models\Delivery\DeliveryProvider;
use App\Models\ProductOrderCourierMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CourierManagementController extends Controller
{
    /**
     * Show courier management page (Vue loads data via API).
     */
    public function index()
    {
        return view('backend.courier.index');
    }

    /**
     * Return all courier methods for the management UI.
     */
    public function getMethods(): JsonResponse
    {
        $methods = ProductOrderCourierMethod::orderBy('id')->get();
        return response()->json($methods);
    }

    /**
     * Update a courier method's config and status.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $method = ProductOrderCourierMethod::findOrFail($id);

        $validated = $request->validate([
            'config' => 'nullable|array',
            'status' => 'nullable|in:active,inactive',
        ]);

        if (isset($validated['config'])) {
            $method->config = $validated['config'];
        }
        if (isset($validated['status'])) {
            $method->status = $validated['status'];
        }
        $method->save();
        $this->syncDeliveryProvider($method);

        return response()->json($method);
    }

    private function syncDeliveryProvider(ProductOrderCourierMethod $method): void
    {
        $name = trim((string) $method->title);
        if ($name === '') {
            return;
        }

        $driver = $this->driverFromName($name);

        DeliveryProvider::updateOrCreate(
            [
                'product_website_id' => $method->product_website_id ?? null,
                'slug' => Str::slug($name),
            ],
            [
                'name' => $name,
                'provider_type' => in_array($driver, ['pathao', 'steadfast', 'carrybee'], true) ? 'api_courier' : 'manual_provider',
                'integration_driver' => $driver,
                'config' => $method->config,
                'status' => $method->status === 'inactive' ? 'inactive' : 'active',
                'creator' => $method->creator ?? null,
            ]
        );
    }

    private function driverFromName(string $name): string
    {
        $slug = Str::slug($name);

        foreach (['pathao', 'steadfast', 'carrybee'] as $driver) {
            if (Str::contains($slug, $driver)) {
                return $driver;
            }
        }

        return 'manual';
    }
}
