<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerOpeningBalancesAndRefs extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('customer_opening_balances')) {
            Schema::create('customer_opening_balances', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_website_id')->nullable();
                $table->unsignedBigInteger('store_id')->nullable();
                $table->unsignedBigInteger('customer_id');
                $table->enum('entry_type', ['due', 'advance']);
                $table->date('opening_date')->nullable();
                $table->string('reference_no', 100)->nullable();
                $table->decimal('opening_amount', 20, 4)->default(0);
                $table->decimal('paid_amount', 20, 4)->default(0);
                $table->decimal('remaining_amount', 20, 4)->default(0);
                $table->text('note')->nullable();
                $table->boolean('posted_to_accounts')->default(false);
                $table->unsignedBigInteger('creator')->nullable();
                $table->string('slug')->nullable();
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->timestamps();

                $table->index(['customer_id', 'entry_type', 'status'], 'idx_customer_opening_type_status');
                $table->index('remaining_amount', 'idx_customer_opening_remaining');
            });
        }

        Schema::table('db_customer_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('db_customer_payments', 'customer_opening_balance_id')) {
                $table->unsignedBigInteger('customer_opening_balance_id')->nullable()->after('order_id');
                $table->index('customer_opening_balance_id', 'idx_customer_payments_opening_id');
            }
        });

        Schema::table('ac_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('ac_transactions', 'event_type')) {
                $table->string('event_type', 100)->nullable()->after('transaction_type');
            }

            if (!Schema::hasColumn('ac_transactions', 'ref_customer_opening_balance_id')) {
                $table->unsignedBigInteger('ref_customer_opening_balance_id')->nullable()->after('ref_customer_payment_id');
                $table->index('ref_customer_opening_balance_id', 'idx_ac_txn_customer_opening_id');
            }
        });
    }

    public function down()
    {
        Schema::table('ac_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('ac_transactions', 'ref_customer_opening_balance_id')) {
                $table->dropIndex('idx_ac_txn_customer_opening_id');
                $table->dropColumn('ref_customer_opening_balance_id');
            }
        });

        Schema::table('db_customer_payments', function (Blueprint $table) {
            if (Schema::hasColumn('db_customer_payments', 'customer_opening_balance_id')) {
                $table->dropIndex('idx_customer_payments_opening_id');
                $table->dropColumn('customer_opening_balance_id');
            }
        });

        Schema::dropIfExists('customer_opening_balances');
    }
}
