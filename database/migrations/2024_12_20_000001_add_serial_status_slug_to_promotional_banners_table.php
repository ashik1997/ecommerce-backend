<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSerialStatusSlugToPromotionalBannersTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('promotional_banners')) {
            return;
        }

        Schema::table('promotional_banners', function (Blueprint $table) {
            if (!Schema::hasColumn('promotional_banners', 'serial')) {
                $table->integer('serial')->default(1)->after('id');
            }
            if (!Schema::hasColumn('promotional_banners', 'status')) {
                $table->tinyInteger('status')->default(1)->after('serial');
            }
            if (!Schema::hasColumn('promotional_banners', 'slug')) {
                $table->string('slug')->nullable()->after('status');
            }
        });
    }

    public function down()
    {
        // Intentionally non-destructive: preserve promotional banner metadata.
    }
}
