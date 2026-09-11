<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Controller;
use App\Models\Delivery\DeliveryProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DeliveryProviderController extends Controller
{
    public function index()
    {
        $query = DeliveryProvider::query()->orderBy('name');

        if (request()->filled('q')) {
            $search = request('q');
            $query->where(function ($providerQuery) use ($search) {
                $providerQuery->where('name', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%')
                    ->orWhere('integration_driver', 'like', '%' . $search . '%');
            });
        }

        if (request()->filled('provider_type')) {
            $query->where('provider_type', request('provider_type'));
        }

        if (request()->filled('status')) {
            $query->where('status', request('status'));
        }

        return view('backend.delivery_management.providers.index', [
            'providers' => $query->paginate(25)->appends(request()->query()),
            'providerTypes' => $this->providerTypes(),
            'integrationDrivers' => $this->integrationDrivers(),
        ]);
    }

    public function create()
    {
        return view('backend.delivery_management.providers.create', [
            'provider' => new DeliveryProvider([
                'provider_type' => 'manual_provider',
                'integration_driver' => 'manual',
                'status' => 'active',
            ]),
            'providerTypes' => $this->providerTypes(),
            'integrationDrivers' => $this->integrationDrivers(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
        $data['creator'] = auth()->id();

        $provider = DeliveryProvider::create($data);

        return redirect()
            ->route('delivery-management.providers.edit', $provider)
            ->with('success', 'Delivery provider created successfully.');
    }

    public function show(DeliveryProvider $provider)
    {
        return view('backend.delivery_management.providers.show', [
            'provider' => $provider->load(['serviceTypes', 'statusMaps']),
        ]);
    }

    public function edit(DeliveryProvider $provider)
    {
        return view('backend.delivery_management.providers.edit', [
            'provider' => $provider,
            'providerTypes' => $this->providerTypes(),
            'integrationDrivers' => $this->integrationDrivers(),
        ]);
    }

    public function update(Request $request, DeliveryProvider $provider)
    {
        $data = $this->validatedData($request, $provider->id);
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

        $provider->update($data);

        return redirect()
            ->route('delivery-management.providers.edit', $provider)
            ->with('success', 'Delivery provider updated successfully.');
    }

    public function destroy(DeliveryProvider $provider)
    {
        $provider->update(['status' => 'inactive']);

        return redirect()
            ->route('delivery-management.providers.index')
            ->with('success', 'Delivery provider deactivated.');
    }

    private function validatedData(Request $request, ?int $providerId = null): array
    {
        return $request->validate([
            'product_website_id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:180'],
            'provider_type' => ['nullable', Rule::in(array_keys($this->providerTypes()))],
            'integration_driver' => ['nullable', Rule::in(array_keys($this->integrationDrivers()))],
            'service_scope' => ['nullable', 'string', 'max:40'],
            'contact_person' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:120'],
            'address' => ['nullable', 'string'],
            'config' => ['nullable', 'array'],
            'status' => ['nullable', 'string', 'max:20'],
        ]);
    }

    private function providerTypes(): array
    {
        return [
            'api_courier' => 'API Courier',
            'local_provider' => 'Local Provider',
            'internal_fleet' => 'Internal Fleet',
            'store_pickup' => 'Store Pickup',
            'manual_provider' => 'Manual Provider',
        ];
    }

    private function integrationDrivers(): array
    {
        return [
            'manual' => 'Manual',
            'pathao' => 'Pathao',
            'steadfast' => 'Steadfast',
            'carrybee' => 'CarryBee',
            'internal' => 'Internal Fleet',
            'store_pickup' => 'Store Pickup',
        ];
    }
}
