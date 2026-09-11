<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateDeliveryLogisticsFoundationTables extends Migration
{
    public function up()
    {
        Schema::create('delivery_providers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_website_id')->nullable()->index();
            $table->string('name', 150);
            $table->string('slug', 180)->index();
            $table->string('provider_type', 40)->default('manual_provider')->index();
            $table->string('integration_driver', 40)->nullable()->index();
            $table->string('service_scope', 40)->nullable();
            $table->string('contact_person', 120)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('email', 120)->nullable();
            $table->text('address')->nullable();
            $table->json('config')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->unsignedBigInteger('creator')->nullable();
            $table->timestamps();

            $table->unique(['product_website_id', 'slug'], 'delivery_providers_website_slug_unique');
        });

        Schema::create('delivery_service_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('provider_id')->nullable();
            $table->string('name', 120);
            $table->string('slug', 150)->index();
            $table->string('code', 80)->nullable();
            $table->text('description')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->unsignedBigInteger('creator')->nullable();
            $table->timestamps();

            $table->foreign('provider_id')->references('id')->on('delivery_providers')->onDelete('cascade');
        });

        Schema::create('delivery_employees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_website_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('employee_profile_id')->nullable()->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->string('name', 150);
            $table->string('phone', 40)->nullable();
            $table->string('email', 120)->nullable();
            $table->string('vehicle_type', 60)->nullable();
            $table->string('vehicle_number', 80)->nullable();
            $table->string('nid', 80)->nullable();
            $table->date('joining_date')->nullable();
            $table->string('salary_type', 40)->nullable();
            $table->decimal('salary_amount', 14, 2)->default(0);
            $table->string('commission_type', 40)->nullable();
            $table->decimal('commission_amount', 14, 2)->default(0);
            $table->decimal('cash_collection_limit', 14, 2)->nullable();
            $table->string('current_status', 40)->default('available')->index();
            $table->string('status', 20)->default('active')->index();
            $table->unsignedBigInteger('creator')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('delivery_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->string('label', 120);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_terminal')->default(false);
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
        });

        Schema::create('delivery_provider_status_maps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('provider_id');
            $table->string('raw_status', 150);
            $table->string('normalized_status', 80)->index();
            $table->string('label', 150)->nullable();
            $table->boolean('is_terminal')->default(false);
            $table->timestamps();

            $table->foreign('provider_id')->references('id')->on('delivery_providers')->onDelete('cascade');
            $table->unique(['provider_id', 'raw_status'], 'delivery_status_maps_provider_raw_unique');
        });

        Schema::create('delivery_zones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_website_id')->nullable()->index();
            $table->string('name', 150);
            $table->string('slug', 180)->index();
            $table->text('description')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->unsignedBigInteger('creator')->nullable();
            $table->timestamps();

            $table->unique(['product_website_id', 'slug'], 'delivery_zones_website_slug_unique');
        });

        Schema::create('delivery_zone_areas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('zone_id');
            $table->unsignedBigInteger('district_id')->nullable()->index();
            $table->unsignedBigInteger('upazila_id')->nullable()->index();
            $table->unsignedBigInteger('area_id')->nullable()->index();
            $table->string('area_name', 150)->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();

            $table->foreign('zone_id')->references('id')->on('delivery_zones')->onDelete('cascade');
        });

        Schema::create('delivery_rate_cards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('provider_id');
            $table->unsignedBigInteger('zone_id')->nullable();
            $table->unsignedBigInteger('service_type_id')->nullable();
            $table->decimal('minimum_weight', 10, 2)->default(0);
            $table->decimal('maximum_weight', 10, 2)->nullable();
            $table->decimal('base_charge', 14, 2)->default(0);
            $table->decimal('additional_weight_charge', 14, 2)->default(0);
            $table->string('cod_charge_type', 40)->nullable();
            $table->decimal('cod_charge_value', 14, 2)->default(0);
            $table->decimal('return_charge', 14, 2)->default(0);
            $table->string('status', 20)->default('active')->index();
            $table->unsignedBigInteger('creator')->nullable();
            $table->timestamps();

            $table->foreign('provider_id')->references('id')->on('delivery_providers')->onDelete('cascade');
            $table->foreign('zone_id')->references('id')->on('delivery_zones')->onDelete('set null');
            $table->foreign('service_type_id')->references('id')->on('delivery_service_types')->onDelete('set null');
        });

        Schema::create('delivery_shipments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_website_id')->nullable()->index();
            $table->string('shipment_code', 80)->unique();
            $table->unsignedBigInteger('product_order_id')->nullable()->index();
            $table->unsignedBigInteger('provider_id')->nullable()->index();
            $table->unsignedBigInteger('delivery_employee_id')->nullable()->index();
            $table->unsignedBigInteger('service_type_id')->nullable()->index();
            $table->unsignedBigInteger('zone_id')->nullable()->index();
            $table->string('source_type', 60)->nullable()->index();
            $table->string('source_reference', 120)->nullable();
            $table->string('recipient_name', 150)->nullable();
            $table->string('recipient_phone', 40)->nullable();
            $table->text('recipient_address')->nullable();
            $table->unsignedBigInteger('district_id')->nullable()->index();
            $table->unsignedBigInteger('upazila_id')->nullable()->index();
            $table->unsignedBigInteger('area_id')->nullable()->index();
            $table->decimal('cod_amount', 14, 2)->default(0);
            $table->decimal('delivery_charge', 14, 2)->default(0);
            $table->decimal('provider_cost', 14, 2)->default(0);
            $table->decimal('customer_delivery_charge', 14, 2)->default(0);
            $table->decimal('weight', 10, 2)->nullable();
            $table->unsignedInteger('item_quantity')->nullable();
            $table->string('tracking_number', 150)->nullable()->index();
            $table->string('external_reference', 150)->nullable()->index();
            $table->string('current_status', 80)->default('draft')->index();
            $table->string('payment_status', 40)->default('pending')->index();
            $table->string('settlement_status', 40)->default('pending')->index();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->json('meta')->nullable();
            $table->unsignedBigInteger('creator')->nullable();
            $table->timestamps();

            $table->foreign('product_order_id')->references('id')->on('product_orders')->onDelete('set null');
            $table->foreign('provider_id')->references('id')->on('delivery_providers')->onDelete('set null');
            $table->foreign('delivery_employee_id')->references('id')->on('delivery_employees')->onDelete('set null');
            $table->foreign('service_type_id')->references('id')->on('delivery_service_types')->onDelete('set null');
            $table->foreign('zone_id')->references('id')->on('delivery_zones')->onDelete('set null');
        });

        Schema::create('delivery_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delivery_shipment_id');
            $table->unsignedBigInteger('provider_id')->nullable()->index();
            $table->unsignedBigInteger('delivery_employee_id')->nullable()->index();
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('status', 40)->default('pending')->index();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('delivery_shipment_id')->references('id')->on('delivery_shipments')->onDelete('cascade');
            $table->foreign('provider_id')->references('id')->on('delivery_providers')->onDelete('set null');
            $table->foreign('delivery_employee_id')->references('id')->on('delivery_employees')->onDelete('set null');
        });

        Schema::create('delivery_shipment_status_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delivery_shipment_id');
            $table->string('status', 80)->index();
            $table->string('raw_status', 150)->nullable();
            $table->string('source', 40)->default('manual')->index();
            $table->json('response_payload')->nullable();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->timestamps();

            $table->foreign('delivery_shipment_id')->references('id')->on('delivery_shipments')->onDelete('cascade');
        });

        Schema::create('delivery_cod_collections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delivery_shipment_id');
            $table->unsignedBigInteger('delivery_employee_id')->nullable()->index();
            $table->decimal('expected_amount', 14, 2)->default(0);
            $table->decimal('collected_amount', 14, 2)->default(0);
            $table->decimal('submitted_amount', 14, 2)->default(0);
            $table->string('collection_status', 40)->default('pending')->index();
            $table->timestamp('collected_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('delivery_shipment_id')->references('id')->on('delivery_shipments')->onDelete('cascade');
            $table->foreign('delivery_employee_id')->references('id')->on('delivery_employees')->onDelete('set null');
        });

        Schema::create('delivery_settlements', function (Blueprint $table) {
            $table->id();
            $table->string('settlement_code', 80)->unique();
            $table->unsignedBigInteger('provider_id')->nullable()->index();
            $table->unsignedBigInteger('delivery_employee_id')->nullable()->index();
            $table->string('settlement_type', 60)->index();
            $table->date('settlement_date')->nullable();
            $table->decimal('total_cod_amount', 14, 2)->default(0);
            $table->decimal('total_delivery_cost', 14, 2)->default(0);
            $table->decimal('total_adjustment', 14, 2)->default(0);
            $table->decimal('net_amount', 14, 2)->default(0);
            $table->string('status', 40)->default('draft')->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('provider_id')->references('id')->on('delivery_providers')->onDelete('set null');
            $table->foreign('delivery_employee_id')->references('id')->on('delivery_employees')->onDelete('set null');
        });

        Schema::create('delivery_settlement_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delivery_settlement_id');
            $table->unsignedBigInteger('delivery_shipment_id');
            $table->decimal('cod_amount', 14, 2)->default(0);
            $table->decimal('delivery_cost', 14, 2)->default(0);
            $table->decimal('adjustment_amount', 14, 2)->default(0);
            $table->decimal('net_amount', 14, 2)->default(0);
            $table->string('status', 40)->default('pending')->index();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('delivery_settlement_id')->references('id')->on('delivery_settlements')->onDelete('cascade');
            $table->foreign('delivery_shipment_id')->references('id')->on('delivery_shipments')->onDelete('cascade');
        });

        $this->seedStatuses();
    }

    public function down()
    {
        Schema::dropIfExists('delivery_settlement_items');
        Schema::dropIfExists('delivery_settlements');
        Schema::dropIfExists('delivery_cod_collections');
        Schema::dropIfExists('delivery_shipment_status_logs');
        Schema::dropIfExists('delivery_assignments');
        Schema::dropIfExists('delivery_shipments');
        Schema::dropIfExists('delivery_rate_cards');
        Schema::dropIfExists('delivery_zone_areas');
        Schema::dropIfExists('delivery_zones');
        Schema::dropIfExists('delivery_provider_status_maps');
        Schema::dropIfExists('delivery_statuses');
        Schema::dropIfExists('delivery_employees');
        Schema::dropIfExists('delivery_service_types');
        Schema::dropIfExists('delivery_providers');
    }

    private function seedStatuses(): void
    {
        $statuses = [
            ['slug' => 'draft', 'label' => 'Draft', 'sort_order' => 10, 'is_terminal' => false],
            ['slug' => 'ready_for_dispatch', 'label' => 'Ready for Dispatch', 'sort_order' => 20, 'is_terminal' => false],
            ['slug' => 'assigned', 'label' => 'Assigned', 'sort_order' => 30, 'is_terminal' => false],
            ['slug' => 'assignment_accepted', 'label' => 'Assignment Accepted', 'sort_order' => 40, 'is_terminal' => false],
            ['slug' => 'pickup_requested', 'label' => 'Pickup Requested', 'sort_order' => 50, 'is_terminal' => false],
            ['slug' => 'picked_up', 'label' => 'Picked Up', 'sort_order' => 60, 'is_terminal' => false],
            ['slug' => 'in_transit', 'label' => 'In Transit', 'sort_order' => 70, 'is_terminal' => false],
            ['slug' => 'out_for_delivery', 'label' => 'Out for Delivery', 'sort_order' => 80, 'is_terminal' => false],
            ['slug' => 'delivery_attempted', 'label' => 'Delivery Attempted', 'sort_order' => 90, 'is_terminal' => false],
            ['slug' => 'delivered', 'label' => 'Delivered', 'sort_order' => 100, 'is_terminal' => true],
            ['slug' => 'partial_delivered', 'label' => 'Partial Delivered', 'sort_order' => 110, 'is_terminal' => false],
            ['slug' => 'delivery_failed', 'label' => 'Delivery Failed', 'sort_order' => 120, 'is_terminal' => false],
            ['slug' => 'returned', 'label' => 'Returned', 'sort_order' => 130, 'is_terminal' => true],
            ['slug' => 'return_in_transit', 'label' => 'Return In Transit', 'sort_order' => 140, 'is_terminal' => false],
            ['slug' => 'returned_to_store', 'label' => 'Returned To Store', 'sort_order' => 150, 'is_terminal' => true],
            ['slug' => 'cancelled', 'label' => 'Cancelled', 'sort_order' => 160, 'is_terminal' => true],
            ['slug' => 'closed', 'label' => 'Closed', 'sort_order' => 170, 'is_terminal' => true],
        ];

        foreach ($statuses as $status) {
            DB::table('delivery_statuses')->insert(array_merge($status, [
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
