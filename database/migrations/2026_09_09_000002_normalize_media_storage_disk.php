<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['media', 'media_files'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'disk')) {
                DB::table($table)->where('disk', 'ftp')->update(['disk' => 'public']);
            }
        }
    }

    public function down(): void
    {
        // Local storage remains the canonical disk after rollback.
    }
};
