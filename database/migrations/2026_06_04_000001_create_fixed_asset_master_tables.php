<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFixedAssetMasterTables extends Migration
{
    public function up()
    {
        Schema::create('fa_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 30)->unique();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->enum('tracking_mode', ['individual', 'pooled'])->default('individual');
            $table->boolean('is_depreciable')->default(true);
            $table->unsignedInteger('default_useful_life_months')->nullable();
            $table->decimal('default_residual_value', 20, 4)->default(0);
            $table->unsignedBigInteger('asset_account_id')->nullable();
            $table->unsignedBigInteger('accumulated_depreciation_account_id')->nullable();
            $table->unsignedBigInteger('depreciation_expense_account_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('fa_locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('name');
            $table->string('code', 50)->nullable();
            $table->enum('type', ['building','floor','room','zone','rack','desk','other'])->default('room');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->index(['warehouse_id', 'parent_id']);
        });

        Schema::create('fa_depreciation_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('method', ['straight_line','diminishing_balance','units_of_production','none'])->default('straight_line');
            $table->unsignedInteger('useful_life_months')->nullable();
            $table->decimal('residual_value', 20, 4)->default(0);
            $table->decimal('rate_percent', 8, 4)->nullable();
            $table->enum('start_rule', ['available_for_use_date','next_month','full_month'])->default('available_for_use_date');
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('fa_disposal_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 50)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('fa_disposal_reasons');
        Schema::dropIfExists('fa_depreciation_profiles');
        Schema::dropIfExists('fa_locations');
        Schema::dropIfExists('fa_categories');
    }
}
