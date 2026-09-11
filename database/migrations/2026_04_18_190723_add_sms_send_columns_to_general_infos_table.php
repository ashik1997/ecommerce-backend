<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('general_infos', function (Blueprint $table) {
            $table->boolean('sms_send_to_customer_for_pos')
                ->default(false)
                ->after('sms_api_key');

            $table->boolean('sms_send_to_customer_for_ecommerce')
                ->default(false)
                ->after('sms_send_to_customer_for_pos');
        });
    }

    public function down(): void
    {
        Schema::table('general_infos', function (Blueprint $table) {
            $table->dropColumn([
                'sms_send_to_customer_for_pos',
                'sms_send_to_customer_for_ecommerce',
            ]);
        });
    }
};