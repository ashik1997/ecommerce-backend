<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFixedAssetFinanceAndWorkflowTables extends Migration
{
    public function up()
    {
        Schema::create('fa_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_no')->unique();
            $table->unsignedBigInteger('asset_id');
            $table->unsignedBigInteger('from_warehouse_id');
            $table->unsignedBigInteger('to_warehouse_id');
            $table->unsignedBigInteger('from_location_id')->nullable();
            $table->unsignedBigInteger('to_location_id')->nullable();
            $table->date('transfer_date');
            $table->date('received_date')->nullable();
            $table->enum('condition_at_dispatch', ['new','excellent','good','fair','poor','damaged','beyond_repair'])->default('good');
            $table->enum('condition_at_receive', ['new','excellent','good','fair','poor','damaged','beyond_repair'])->nullable();
            $table->enum('status', ['pending','dispatched','received','cancelled'])->default('pending');
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('received_by')->nullable();
            $table->timestamps();
        });

        Schema::create('fa_maintenance_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('job_no')->unique();
            $table->unsignedBigInteger('asset_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->enum('type', ['preventive','corrective','warranty','inspection','calibration'])->default('corrective');
            $table->string('title');
            $table->text('problem_description')->nullable();
            $table->date('reported_at');
            $table->date('started_at')->nullable();
            $table->date('completed_at')->nullable();
            $table->decimal('parts_cost', 20, 4)->default(0);
            $table->decimal('labor_cost', 20, 4)->default(0);
            $table->decimal('other_cost', 20, 4)->default(0);
            $table->decimal('total_cost', 20, 4)->default(0);
            $table->enum('status', ['open','in_progress','completed','cancelled'])->default('open');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('fa_depreciation_runs', function (Blueprint $table) {
            $table->id();
            $table->string('run_no')->unique();
            $table->string('period', 7); // YYYY-MM
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->date('run_date');
            $table->decimal('total_depreciation', 20, 4)->default(0);
            $table->enum('status', ['preview','posted','reversed'])->default('preview');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
            $table->unique(['period','warehouse_id']);
        });

        Schema::create('fa_depreciation_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('depreciation_run_id');
            $table->unsignedBigInteger('asset_id');
            $table->string('period', 7);
            $table->decimal('opening_book_value', 20, 4)->default(0);
            $table->decimal('depreciation_amount', 20, 4)->default(0);
            $table->decimal('closing_book_value', 20, 4)->default(0);
            $table->timestamps();
            $table->unique(['asset_id','period']);
        });

        Schema::create('fa_disposals', function (Blueprint $table) {
            $table->id();
            $table->string('disposal_no')->unique();
            $table->unsignedBigInteger('asset_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('disposal_reason_id')->nullable();
            $table->date('disposal_date');
            $table->decimal('carrying_amount', 20, 4)->default(0);
            $table->decimal('proceeds_amount', 20, 4)->default(0);
            $table->decimal('gain_loss_amount', 20, 4)->default(0);
            $table->enum('status', ['pending','approved','completed','cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('fa_verification_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_no')->unique();
            $table->unsignedBigInteger('warehouse_id');
            $table->string('title');
            $table->date('started_at');
            $table->date('completed_at')->nullable();
            $table->enum('status', ['open','completed','cancelled'])->default('open');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('fa_verification_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('verification_session_id');
            $table->unsignedBigInteger('asset_id');
            $table->enum('result', ['expected','found','missing','wrong_location','damaged'])->default('expected');
            $table->timestamp('scanned_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['verification_session_id','asset_id']);
        });

        Schema::create('fa_account_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('event_key');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('debit_account_id')->nullable();
            $table->unsignedBigInteger('credit_account_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['event_key','category_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('fa_account_mappings');
        Schema::dropIfExists('fa_verification_items');
        Schema::dropIfExists('fa_verification_sessions');
        Schema::dropIfExists('fa_disposals');
        Schema::dropIfExists('fa_depreciation_entries');
        Schema::dropIfExists('fa_depreciation_runs');
        Schema::dropIfExists('fa_maintenance_jobs');
        Schema::dropIfExists('fa_transfers');
    }
}
