<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAvailableAdvanceToCustomersTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('customers') || Schema::hasColumn('customers', 'available_advance')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->decimal('available_advance', 10, 2)->default(0)->after('address')->comment('Available advance payment balance');
        });
    }

    public function down()
    {
        // Intentionally non-destructive: preserve customer advance balances.
    }
}
