<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRouteModuleNameToPermissionRoutesTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('permission_routes') || Schema::hasColumn('permission_routes', 'route_module_name')) {
            return;
        }

        Schema::table('permission_routes', function (Blueprint $table) {
            $table->string('route_module_name')->nullable()->after('route_group_name');
        });
    }

    public function down()
    {
        // Intentionally non-destructive: preserve permission route grouping metadata.
    }
}
