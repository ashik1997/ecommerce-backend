<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('general_infos')) {
            Schema::table('general_infos', function (Blueprint $table) {
                if (!Schema::hasColumn('general_infos', 'support_time')) {
                    $table->string('support_time')->nullable()->after('sms_send_to_customer_for_ecommerce');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('general_infos') && Schema::hasColumn('general_infos', 'support_time')) {
            Schema::table('general_infos', function (Blueprint $table) {
                $table->dropColumn('support_time');
            });
        }
    }
};