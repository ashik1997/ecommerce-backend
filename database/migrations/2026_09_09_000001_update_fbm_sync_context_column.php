<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fbm_sync_runs')
            || !Schema::hasColumn('fbm_sync_runs', 'tenant_context_fingerprint')
            || Schema::hasColumn('fbm_sync_runs', 'application_context_fingerprint')) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE `fbm_sync_runs` CHANGE `tenant_context_fingerprint` `application_context_fingerprint` CHAR(64) NOT NULL');
        } elseif ($driver === 'sqlsrv') {
            DB::statement("EXEC sp_rename 'fbm_sync_runs.tenant_context_fingerprint', 'application_context_fingerprint', 'COLUMN'");
        } else {
            DB::statement('ALTER TABLE fbm_sync_runs RENAME COLUMN tenant_context_fingerprint TO application_context_fingerprint');
        }
    }

    public function down(): void
    {
        // The legacy context column is not restored.
    }
};
