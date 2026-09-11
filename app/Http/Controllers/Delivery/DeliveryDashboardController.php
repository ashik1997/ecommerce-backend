<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Controller;
use App\Models\Delivery\DeliveryEmployee;
use App\Models\Delivery\DeliveryProvider;
use App\Models\Delivery\DeliveryShipment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DeliveryDashboardController extends Controller
{
    public function index()
    {
        $summary = [
            'providers' => $this->countIfTableExists('delivery_providers'),
            'active_providers' => Schema::hasTable('delivery_providers') ? DeliveryProvider::where('status', 'active')->count() : 0,
            'employees' => $this->countIfTableExists('delivery_employees'),
            'shipments' => $this->countIfTableExists('delivery_shipments'),
            'pending_shipments' => Schema::hasTable('delivery_shipments')
                ? DeliveryShipment::whereNotIn('current_status', ['delivered', 'returned', 'returned_to_store', 'cancelled', 'closed'])->count()
                : 0,
            'delivered_today' => Schema::hasTable('delivery_shipments')
                ? DeliveryShipment::whereDate('delivered_at', now()->toDateString())->count()
                : 0,
            'cod_pending' => Schema::hasTable('delivery_cod_collections')
                ? (float) DB::table('delivery_cod_collections')
                    ->whereNotIn('collection_status', ['verified', 'posted'])
                    ->sum(DB::raw('GREATEST(expected_amount - submitted_amount, 0)'))
                : 0,
            'settlement_pending' => Schema::hasTable('delivery_settlements')
                ? (float) DB::table('delivery_settlements')
                    ->whereIn('status', ['draft', 'approved'])
                    ->sum('net_amount')
                : 0,
        ];

        $recentProviders = Schema::hasTable('delivery_providers')
            ? DeliveryProvider::latest()->take(8)->get()
            : collect();

        $recentShipments = Schema::hasTable('delivery_shipments')
            ? DeliveryShipment::with(['provider', 'employee'])->latest()->take(8)->get()
            : collect();

        $statusSummary = Schema::hasTable('delivery_shipments')
            ? DB::table('delivery_shipments')
                ->select('current_status', DB::raw('COUNT(*) as total'))
                ->groupBy('current_status')
                ->orderByDesc('total')
                ->get()
            : collect();

        $providerSummary = Schema::hasTable('delivery_shipments')
            ? DB::table('delivery_shipments')
                ->leftJoin('delivery_providers', 'delivery_providers.id', '=', 'delivery_shipments.provider_id')
                ->select(
                    DB::raw('COALESCE(delivery_providers.name, "Unassigned") as provider_name'),
                    DB::raw('COUNT(delivery_shipments.id) as total_shipments'),
                    DB::raw("SUM(CASE WHEN delivery_shipments.current_status = 'delivered' THEN 1 ELSE 0 END) as delivered_shipments"),
                    DB::raw("SUM(CASE WHEN delivery_shipments.current_status IN ('returned','returned_to_store') THEN 1 ELSE 0 END) as returned_shipments")
                )
                ->groupBy('delivery_shipments.provider_id', 'delivery_providers.name')
                ->orderByDesc('total_shipments')
                ->limit(8)
                ->get()
            : collect();

        $employeeCodSummary = Schema::hasTable('delivery_cod_collections')
            ? DB::table('delivery_cod_collections')
                ->leftJoin('delivery_employees', 'delivery_employees.id', '=', 'delivery_cod_collections.delivery_employee_id')
                ->select(
                    DB::raw('COALESCE(delivery_employees.name, "Unassigned") as employee_name'),
                    DB::raw('SUM(delivery_cod_collections.expected_amount) as expected_amount'),
                    DB::raw('SUM(delivery_cod_collections.submitted_amount) as submitted_amount'),
                    DB::raw('SUM(GREATEST(delivery_cod_collections.expected_amount - delivery_cod_collections.submitted_amount, 0)) as pending_amount')
                )
                ->whereNotIn('delivery_cod_collections.collection_status', ['verified', 'posted'])
                ->groupBy('delivery_cod_collections.delivery_employee_id', 'delivery_employees.name')
                ->orderByDesc('pending_amount')
                ->limit(8)
                ->get()
            : collect();

        return view('backend.delivery_management.dashboard', [
            'summary' => $summary,
            'recentProviders' => $recentProviders,
            'recentShipments' => $recentShipments,
            'statusSummary' => $statusSummary,
            'providerSummary' => $providerSummary,
            'employeeCodSummary' => $employeeCodSummary,
        ]);
    }

    private function countIfTableExists(string $table): int
    {
        return Schema::hasTable($table) ? (int) app('db')->table($table)->count() : 0;
    }
}
