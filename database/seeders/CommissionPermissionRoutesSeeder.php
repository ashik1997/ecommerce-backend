<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CommissionPermissionRoutesSeeder extends Seeder
{
    /**
     * Seed permission routes for Sales Commission & Affiliate module.
     *
     * Run after extracting the patch:
     * php artisan db:seed --class=CommissionPermissionRoutesSeeder
     */
    public function run()
    {
        $now = Carbon::now();

        $routes = [
            ['route' => 'sr-management/affiliates', 'name' => 'sr.affiliates.index', 'method' => 'GET', 'group' => 'Affiliate Partners'],
            ['route' => 'sr-management/affiliates/create', 'name' => 'sr.affiliates.create', 'method' => 'GET', 'group' => 'Affiliate Partners'],
            ['route' => 'sr-management/affiliates', 'name' => 'sr.affiliates.store', 'method' => 'POST', 'group' => 'Affiliate Partners'],
            ['route' => 'sr-management/affiliates/{affiliate}/edit', 'name' => 'sr.affiliates.edit', 'method' => 'GET', 'group' => 'Affiliate Partners'],
            ['route' => 'sr-management/affiliates/{affiliate}', 'name' => 'sr.affiliates.update', 'method' => 'PUT', 'group' => 'Affiliate Partners'],
            ['route' => 'sr-management/affiliates/{affiliate}', 'name' => 'sr.affiliates.destroy', 'method' => 'DELETE', 'group' => 'Affiliate Partners'],
            ['route' => 'sr-management/affiliates/validate-code', 'name' => 'sr.affiliates.validate-code', 'method' => 'POST', 'group' => 'Affiliate Partners'],
            ['route' => 'sr-management/commission-rules', 'name' => 'sr.commission-rules.index', 'method' => 'GET', 'group' => 'Commission Rules'],
            ['route' => 'sr-management/commission-rules/create', 'name' => 'sr.commission-rules.create', 'method' => 'GET', 'group' => 'Commission Rules'],
            ['route' => 'sr-management/commission-rules', 'name' => 'sr.commission-rules.store', 'method' => 'POST', 'group' => 'Commission Rules'],
            ['route' => 'sr-management/commission-rules/{commission_rule}/edit', 'name' => 'sr.commission-rules.edit', 'method' => 'GET', 'group' => 'Commission Rules'],
            ['route' => 'sr-management/commission-rules/{commission_rule}', 'name' => 'sr.commission-rules.update', 'method' => 'PUT', 'group' => 'Commission Rules'],
            ['route' => 'sr-management/commission-rules/{commission_rule}', 'name' => 'sr.commission-rules.destroy', 'method' => 'DELETE', 'group' => 'Commission Rules'],
            ['route' => 'sr-management/commission-rules/conflict-check', 'name' => 'sr.commission-rules.conflict-check', 'method' => 'POST', 'group' => 'Commission Rules'],
            ['route' => 'sr-management/commission-entries', 'name' => 'sr.commission-entries.index', 'method' => 'GET', 'group' => 'Commission Entries'],
            ['route' => 'sr-management/commission-entries/bulk-approve', 'name' => 'sr.commission-entries.bulk-approve', 'method' => 'POST', 'group' => 'Commission Entries'],
            ['route' => 'sr-management/commission-entries/{id}/approve', 'name' => 'sr.commission-entries.approve', 'method' => 'POST', 'group' => 'Commission Entries'],
            ['route' => 'sr-management/commission-entries/{id}/reverse', 'name' => 'sr.commission-entries.reverse', 'method' => 'POST', 'group' => 'Commission Entries'],
            ['route' => 'sr-management/commission-entries/order/{orderId}/recalculate', 'name' => 'sr.commission-entries.order-recalculate', 'method' => 'POST', 'group' => 'Commission Entries'],
            ['route' => 'sr-management/commission-settlements', 'name' => 'sr.commission-settlements.index', 'method' => 'GET', 'group' => 'Commission Settlement'],
            ['route' => 'sr-management/commission-settlements/create', 'name' => 'sr.commission-settlements.create', 'method' => 'GET', 'group' => 'Commission Settlement'],
            ['route' => 'sr-management/commission-settlements', 'name' => 'sr.commission-settlements.store', 'method' => 'POST', 'group' => 'Commission Settlement'],
            ['route' => 'sr-management/commission-settlements/{id}', 'name' => 'sr.commission-settlements.show', 'method' => 'GET', 'group' => 'Commission Settlement'],
            ['route' => 'sr-management/commission-settlements/{id}/approve', 'name' => 'sr.commission-settlements.approve', 'method' => 'POST', 'group' => 'Commission Settlement'],
            ['route' => 'sr-management/commission-settlements/{id}/mark-paid', 'name' => 'sr.commission-settlements.mark-paid', 'method' => 'POST', 'group' => 'Commission Settlement'],
            ['route' => 'sr-management/commission-settlements/{id}/cancel', 'name' => 'sr.commission-settlements.cancel', 'method' => 'POST', 'group' => 'Commission Settlement'],
            ['route' => 'sr-management/commission-ledger', 'name' => 'sr.commission-ledger.index', 'method' => 'GET', 'group' => 'Commission Ledger'],
            ['route' => 'sr-management/commission-reports', 'name' => 'sr.commission-reports.index', 'method' => 'GET', 'group' => 'Commission Report'],
            ['route' => 'sr-management/commission-reports/export', 'name' => 'sr.commission-reports.export', 'method' => 'GET', 'group' => 'Commission Report'],
            ['route' => 'sr-management/commission-manual', 'name' => 'sr.commission-manual.index', 'method' => 'GET', 'group' => 'Commission Manual'],
            ['route' => 'sr-management/commission-manual/bn', 'name' => 'sr.commission-manual.bn', 'method' => 'GET', 'group' => 'Commission Manual'],
            ['route' => 'sr-management/commission-manual/en', 'name' => 'sr.commission-manual.en', 'method' => 'GET', 'group' => 'Commission Manual'],
        ];

        foreach ($routes as $route) {
            DB::table('permission_routes')->updateOrInsert(
                ['name' => $route['name'], 'method' => $route['method']],
                [
                    'route' => $route['route'],
                    'route_group_name' => $route['group'],
                    'route_module_name' => 'sr',
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }
}
