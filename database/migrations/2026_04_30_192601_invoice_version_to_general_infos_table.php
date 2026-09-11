<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('general_infos', function (Blueprint $table) {
            $table->string('invoice_version', 50)
                  ->default('v1')
                  ->after('landing_page_version'); // change position if needed
        });
    }

    public function down(): void
    {
        Schema::table('general_infos', function (Blueprint $table) {
            $table->dropColumn('invoice_version');
        });
    }
};
