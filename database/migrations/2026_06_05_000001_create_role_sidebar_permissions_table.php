<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoleSidebarPermissionsTable extends Migration
{
    public function up()
    {
        Schema::create('role_sidebar_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id')->unique();
            $table->longText('permissions_json')->nullable();
            $table->longText('sidebar_json')->nullable();
            $table->string('sidebar_hash', 64)->nullable();
            $table->string('mother_sidebar_hash', 64)->nullable();
            $table->string('cache_key')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index('role_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('role_sidebar_permissions');
    }
}
