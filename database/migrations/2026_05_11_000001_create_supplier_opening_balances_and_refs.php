<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSupplierOpeningBalancesAndRefs extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('supplier_opening_balances')) {
            Schema::create('supplier_opening_balances', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_website_id')->nullable();
                $table->unsignedBigInteger('store_id')->nullable();
                $table->unsignedBigInteger('supplier_id');
                $table->enum('entry_type', ['due', 'advance']);
                $table->string('invoice_no', 100)->nullable();
                $table->date('invoice_date')->nullable();
                $table->date('due_date')->nullable();
                $table->date('opening_date')->nullable();
                $table->decimal('opening_amount', 20, 4)->default(0);
                $table->decimal('paid_amount', 20, 4)->default(0);
                $table->decimal('remaining_amount', 20, 4)->default(0);
                $table->string('reference_no', 100)->nullable();
                $table->text('note')->nullable();
                $table->boolean('posted_to_accounts')->default(false);
                $table->unsignedBigInteger('creator')->nullable();
                $table->string('slug')->nullable();
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->timestamps();

                $table->index('supplier_id', 'sob_supplier_idx');
                $table->index('entry_type', 'sob_entry_type_idx');
                $table->index('status', 'sob_status_idx');
                $table->index('invoice_no', 'sob_invoice_idx');
                $table->index('remaining_amount', 'sob_remaining_idx');
            });
        }

        Schema::table('db_supplier_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('db_supplier_payments', 'supplier_opening_balance_id')) {
                $table->unsignedBigInteger('supplier_opening_balance_id')->nullable()->after('purchasepayment_id');
                $table->index('supplier_opening_balance_id', 'dsp_opening_id_idx');
            }
        });

        Schema::table('ac_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('ac_transactions', 'event_type')) {
                $table->string('event_type', 100)->nullable()->after('transaction_type');
            }

            if (!Schema::hasColumn('ac_transactions', 'ref_supplier_opening_balance_id')) {
                $table->unsignedBigInteger('ref_supplier_opening_balance_id')->nullable()->after('supplier_payment_id');
                $table->index('ref_supplier_opening_balance_id', 'ac_txn_sob_idx');
            }
        });
    }

    public function down()
    {
        Schema::table('ac_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('ac_transactions', 'ref_supplier_opening_balance_id')) {
                $table->dropIndex('ac_txn_sob_idx');
                $table->dropColumn('ref_supplier_opening_balance_id');
            }
        });

        Schema::table('db_supplier_payments', function (Blueprint $table) {
            if (Schema::hasColumn('db_supplier_payments', 'supplier_opening_balance_id')) {
                $table->dropIndex('dsp_opening_id_idx');
                $table->dropColumn('supplier_opening_balance_id');
            }
        });

        Schema::dropIfExists('supplier_opening_balances');
    }
}
