<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpgradeFundTransferTables extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('ac_moneytransfer')) {
            Schema::create('ac_moneytransfer', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_website_id')->nullable();
                $table->unsignedBigInteger('store_id')->nullable();
                $table->unsignedBigInteger('count_id')->nullable();
                $table->string('transfer_code', 100)->nullable();
                $table->date('transfer_date')->nullable();
                $table->string('reference_no', 100)->nullable();
                $table->unsignedBigInteger('debit_account_id')->nullable();
                $table->unsignedBigInteger('credit_account_id')->nullable();
                $table->double('amount', 20, 4)->nullable();
                $table->text('note')->nullable();
                $table->string('created_by', 50)->nullable();
                $table->date('created_date')->nullable();
                $table->string('created_time', 100)->nullable();
                $table->string('system_ip', 100)->nullable();
                $table->string('system_name', 100)->nullable();
                $table->unsignedBigInteger('creator')->nullable();
                $table->string('slug', 192)->nullable();
                $table->enum('status', ['active', 'inactive'])->nullable()->default('active');
                $table->timestamps();
            });
        }

        Schema::table('ac_moneytransfer', function (Blueprint $table) {
            if (!Schema::hasColumn('ac_moneytransfer', 'from_payment_type_id')) {
                $table->unsignedBigInteger('from_payment_type_id')->nullable()->after('reference_no');
            }
            if (!Schema::hasColumn('ac_moneytransfer', 'to_payment_type_id')) {
                $table->unsignedBigInteger('to_payment_type_id')->nullable()->after('from_payment_type_id');
            }
            if (!Schema::hasColumn('ac_moneytransfer', 'transfer_type_id')) {
                $table->unsignedBigInteger('transfer_type_id')->nullable()->after('to_payment_type_id');
            }
            if (!Schema::hasColumn('ac_moneytransfer', 'transfer_method')) {
                $table->string('transfer_method', 100)->nullable()->after('transfer_type_id');
            }
            if (!Schema::hasColumn('ac_moneytransfer', 'transfer_charge')) {
                $table->double('transfer_charge', 20, 4)->default(0)->after('amount');
            }
            if (!Schema::hasColumn('ac_moneytransfer', 'charge_account_id')) {
                $table->unsignedBigInteger('charge_account_id')->nullable()->after('transfer_charge');
            }
            if (!Schema::hasColumn('ac_moneytransfer', 'attachment')) {
                $table->string('attachment')->nullable()->after('charge_account_id');
            }
            if (!Schema::hasColumn('ac_moneytransfer', 'from_balance_before')) {
                $table->double('from_balance_before', 20, 4)->default(0)->after('attachment');
            }
            if (!Schema::hasColumn('ac_moneytransfer', 'from_balance_after')) {
                $table->double('from_balance_after', 20, 4)->default(0)->after('from_balance_before');
            }
            if (!Schema::hasColumn('ac_moneytransfer', 'to_balance_before')) {
                $table->double('to_balance_before', 20, 4)->default(0)->after('from_balance_after');
            }
            if (!Schema::hasColumn('ac_moneytransfer', 'to_balance_after')) {
                $table->double('to_balance_after', 20, 4)->default(0)->after('to_balance_before');
            }
            if (!Schema::hasColumn('ac_moneytransfer', 'closing_balance_date')) {
                $table->date('closing_balance_date')->nullable()->after('to_balance_after');
            }
            if (!Schema::hasColumn('ac_moneytransfer', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('closing_balance_date');
            }
            if (!Schema::hasColumn('ac_moneytransfer', 'approval_status')) {
                $table->string('approval_status', 50)->default('approved')->after('approved_by');
            }
        });

        if (!Schema::hasTable('ac_moneytransfer_types')) {
            Schema::create('ac_moneytransfer_types', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->text('note')->nullable();
                $table->unsignedBigInteger('creator')->nullable();
                $table->string('slug')->nullable();
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('ac_moneytransfer_types');
    }
}
