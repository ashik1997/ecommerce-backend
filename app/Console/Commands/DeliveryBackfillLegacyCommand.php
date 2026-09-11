<?php

namespace App\Console\Commands;

use App\Models\Delivery\DeliveryProvider;
use App\Models\Delivery\DeliveryRateCard;
use App\Models\Delivery\DeliveryZone;
use App\Models\Delivery\DeliveryZoneArea;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DeliveryBackfillLegacyCommand extends Command
{
    protected $signature = 'delivery:backfill-legacy {--dry-run : Show what would be imported without writing data}';
    protected $description = 'Backfill Delivery Management providers, zones, and rate cards from legacy courier tables.';

    private $dryRun = false;
    private $created = [
        'providers' => 0,
        'zones' => 0,
        'zone_areas' => 0,
        'rate_cards' => 0,
    ];
    private $skipped = [
        'providers' => 0,
        'zones' => 0,
        'zone_areas' => 0,
        'rate_cards' => 0,
    ];

    public function handle(): int
    {
        $this->dryRun = (bool) $this->option('dry-run');

        if (!Schema::hasTable('delivery_providers')) {
            $this->error('Delivery foundation tables are missing. Run the delivery migration first.');

            return self::FAILURE;
        }

        $this->info($this->dryRun ? 'Dry-run: no data will be written.' : 'Backfilling legacy courier data...');

        $this->backfillCourierMethods();
        $this->backfillAreaBasedCouriers();

        $this->line('');
        $this->info('Backfill summary');
        $this->table(
            ['Entity', 'Created', 'Skipped'],
            [
                ['Providers', $this->created['providers'], $this->skipped['providers']],
                ['Zones', $this->created['zones'], $this->skipped['zones']],
                ['Zone Areas', $this->created['zone_areas'], $this->skipped['zone_areas']],
                ['Rate Cards', $this->created['rate_cards'], $this->skipped['rate_cards']],
            ]
        );

        return self::SUCCESS;
    }

    private function backfillCourierMethods(): void
    {
        if (!Schema::hasTable('product_order_courier_methods')) {
            $this->warn('Skipping product_order_courier_methods: table not found.');

            return;
        }

        $rows = DB::table('product_order_courier_methods')->orderBy('id')->get();

        foreach ($rows as $row) {
            $name = trim((string) ($row->title ?? ''));
            if ($name === '') {
                continue;
            }

            $driver = $this->driverFromName($name);
            $type = in_array($driver, ['pathao', 'steadfast', 'carrybee'], true) ? 'api_courier' : 'manual_provider';

            $this->ensureProvider([
                'product_website_id' => $row->product_website_id ?? null,
                'name' => $name,
                'slug' => $this->providerSlug($name),
                'provider_type' => $type,
                'integration_driver' => $driver,
                'config' => $this->decodeJson($row->config ?? null),
                'status' => $this->normalizeStatus($row->status ?? 'active'),
                'creator' => $row->creator ?? null,
            ]);
        }
    }

    private function backfillAreaBasedCouriers(): void
    {
        if (!Schema::hasTable('area_base_courier_names')) {
            $this->warn('Skipping area_base_courier_names: table not found.');

            return;
        }

        $providers = DB::table('area_base_courier_names')->orderBy('id')->get();

        foreach ($providers as $legacyProvider) {
            $name = trim((string) ($legacyProvider->name ?? ''));
            if ($name === '') {
                continue;
            }

            $provider = $this->ensureProvider([
                'product_website_id' => null,
                'name' => $name,
                'slug' => $this->providerSlug($name),
                'provider_type' => 'local_provider',
                'integration_driver' => 'manual',
                'status' => $this->normalizeBooleanStatus($legacyProvider->status ?? 1),
                'creator' => $legacyProvider->creator ?? null,
            ]);

            if (!$provider || !Schema::hasTable('area_base_couriers')) {
                continue;
            }

            $areas = DB::table('area_base_couriers')
                ->where('area_base_courier_id', $legacyProvider->id)
                ->orderBy('id')
                ->get();

            foreach ($areas as $legacyArea) {
                $areaName = trim((string) ($legacyArea->area_name ?? ''));
                if ($areaName === '') {
                    continue;
                }

                $zone = $this->ensureZone($areaName, $legacyArea->creator ?? $legacyProvider->creator ?? null, $legacyArea->status ?? 1);

                if ($zone) {
                    $this->ensureZoneArea($zone, $areaName, $legacyArea->status ?? 1);
                    $this->ensureRateCard($provider, $zone, (float) ($legacyArea->shipping_cost ?? 0), $legacyArea->creator ?? null, $legacyArea->status ?? 1);
                }
            }
        }
    }

    private function ensureProvider(array $data): ?DeliveryProvider
    {
        $query = DeliveryProvider::query()
            ->where('slug', $data['slug'])
            ->where(function ($websiteQuery) use ($data) {
                if (empty($data['product_website_id'])) {
                    $websiteQuery->whereNull('product_website_id');
                } else {
                    $websiteQuery->where('product_website_id', $data['product_website_id']);
                }
            });

        $existing = $query->first();
        if ($existing) {
            $this->skipped['providers']++;

            return $existing;
        }

        $this->created['providers']++;
        $this->line('Provider: ' . $data['name']);

        if ($this->dryRun) {
            return new DeliveryProvider($data);
        }

        return DeliveryProvider::create($data);
    }

    private function ensureZone(string $areaName, ?int $creator, $legacyStatus): ?DeliveryZone
    {
        $slug = 'legacy-' . Str::slug($areaName);
        $existing = DeliveryZone::query()
            ->whereNull('product_website_id')
            ->where('slug', $slug)
            ->first();

        if ($existing) {
            $this->skipped['zones']++;

            return $existing;
        }

        $data = [
            'product_website_id' => null,
            'name' => $areaName,
            'slug' => $slug,
            'description' => 'Backfilled from legacy area-based courier charge.',
            'status' => $this->normalizeBooleanStatus($legacyStatus),
            'creator' => $creator,
        ];

        $this->created['zones']++;
        $this->line('Zone: ' . $areaName);

        if ($this->dryRun) {
            return new DeliveryZone($data);
        }

        return DeliveryZone::create($data);
    }

    private function ensureZoneArea(DeliveryZone $zone, string $areaName, $legacyStatus): void
    {
        if (!$zone->exists) {
            $this->created['zone_areas']++;

            return;
        }

        $existing = DeliveryZoneArea::query()
            ->where('zone_id', $zone->id)
            ->where('area_name', $areaName)
            ->first();

        if ($existing) {
            $this->skipped['zone_areas']++;

            return;
        }

        $this->created['zone_areas']++;

        if ($this->dryRun) {
            return;
        }

        DeliveryZoneArea::create([
            'zone_id' => $zone->id,
            'area_name' => $areaName,
            'status' => $this->normalizeBooleanStatus($legacyStatus),
        ]);
    }

    private function ensureRateCard(DeliveryProvider $provider, DeliveryZone $zone, float $charge, ?int $creator, $legacyStatus): void
    {
        if (!$provider->exists || !$zone->exists) {
            $this->created['rate_cards']++;

            return;
        }

        $existing = DeliveryRateCard::query()
            ->where('provider_id', $provider->id)
            ->where('zone_id', $zone->id)
            ->whereNull('service_type_id')
            ->where('minimum_weight', 0)
            ->first();

        if ($existing) {
            $this->skipped['rate_cards']++;

            return;
        }

        $this->created['rate_cards']++;

        if ($this->dryRun) {
            return;
        }

        DeliveryRateCard::create([
            'provider_id' => $provider->id,
            'zone_id' => $zone->id,
            'service_type_id' => null,
            'minimum_weight' => 0,
            'maximum_weight' => null,
            'base_charge' => $charge,
            'additional_weight_charge' => 0,
            'cod_charge_type' => 'none',
            'cod_charge_value' => 0,
            'return_charge' => 0,
            'status' => $this->normalizeBooleanStatus($legacyStatus),
            'creator' => $creator,
        ]);
    }

    private function providerSlug(string $name): string
    {
        return Str::slug($name) ?: 'provider-' . Str::random(8);
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

    private function decodeJson($value): ?array
    {
        if (is_array($value)) {
            return $value;
        }

        if (empty($value)) {
            return null;
        }

        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function normalizeStatus($value): string
    {
        return $value === 'inactive' ? 'inactive' : 'active';
    }

    private function normalizeBooleanStatus($value): string
    {
        return (int) $value === 0 ? 'inactive' : 'active';
    }
}
