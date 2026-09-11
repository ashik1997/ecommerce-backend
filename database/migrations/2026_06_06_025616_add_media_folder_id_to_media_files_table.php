<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('media_files') || Schema::hasColumn('media_files', 'media_folder_id')) {
            return;
        }

        Schema::table('media_files', function (Blueprint $table) {
            $table->unsignedBigInteger('media_folder_id')->nullable()->after('product_website_id');
            $table->index('media_folder_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('media_files') || ! Schema::hasColumn('media_files', 'media_folder_id')) {
            return;
        }

        Schema::table('media_files', function (Blueprint $table) {
            $table->dropIndex(['media_folder_id']);
            $table->dropColumn('media_folder_id');
        });
    }
};
