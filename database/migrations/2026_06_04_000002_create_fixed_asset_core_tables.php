<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFixedAssetCoreTables extends Migration
{
    public function up()
    {
        Schema::create('fa_assets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('asset_code')->unique();
            $table->string('asset_name');
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('custodian_employee_id')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('brand_name')->nullable();
            $table->string('model_name')->nullable();
            $table->text('description')->nullable();
            $table->date('purchase_date')->nullable();
            $table->date('available_for_use_date')->nullable();
            $table->date('depreciation_start_date')->nullable();
            $table->decimal('purchase_cost', 20, 4)->default(0);
            $table->decimal('additional_cost', 20, 4)->default(0);
            $table->decimal('capitalized_cost', 20, 4)->default(0);
            $table->decimal('residual_value', 20, 4)->default(0);
            $table->unsignedInteger('useful_life_months')->nullable();
            $table->enum('depreciation_method', ['straight_line','diminishing_balance','units_of_production','none'])->default('straight_line');
            $table->decimal('accumulated_depreciation', 20, 4)->default(0);
            $table->decimal('carrying_amount', 20, 4)->default(0);
            $table->enum('lifecycle_status', ['draft','pending_approval','approved','capitalized','disposed','archived'])->default('capitalized');
            $table->enum('operational_status', ['available','assigned','in_transfer','under_maintenance','idle','lost','damaged','retired'])->default('available');
            $table->enum('condition_status', ['new','excellent','good','fair','poor','damaged','beyond_repair'])->default('good');
            $table->date('warranty_start_date')->nullable();
            $table->date('warranty_end_date')->nullable();
            $table->string('invoice_number')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->index(['warehouse_id','category_id','operational_status']);
            $table->index(['custodian_employee_id','department_id']);
        });

        Schema::create('fa_asset_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('asset_id');
            $table->string('event_type');
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->json('before_json')->nullable();
            $table->json('after_json')->nullable();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index(['asset_id','event_type']);
        });

        Schema::create('fa_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('assignment_no')->unique();
            $table->unsignedBigInteger('asset_id');
            $table->enum('assigned_to_type', ['employee','department','warehouse'])->default('employee');
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->date('assigned_at');
            $table->date('expected_return_date')->nullable();
            $table->date('returned_at')->nullable();
            $table->enum('issue_condition', ['new','excellent','good','fair','poor','damaged','beyond_repair'])->default('good');
            $table->enum('return_condition', ['new','excellent','good','fair','poor','damaged','beyond_repair'])->nullable();
            $table->text('handover_note')->nullable();
            $table->text('return_note')->nullable();
            $table->enum('status', ['active','returned','cancelled'])->default('active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->index(['asset_id','status']);
            $table->index(['warehouse_id','employee_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('fa_assignments');
        Schema::dropIfExists('fa_asset_events');
        Schema::dropIfExists('fa_assets');
    }
}
