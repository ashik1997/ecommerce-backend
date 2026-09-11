<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'crm_campaign_drafts';

    public function up(): void
    {
        if (!Schema::hasTable(self::TABLE)) {
            return;
        }

        if (!Schema::hasColumn(self::TABLE, 'audience_snapshot_signature')) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->string('audience_snapshot_signature', 64)->nullable();
            });
        }

        if (!Schema::hasColumn(self::TABLE, 'audience_snapshot_signature_version')) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->unsignedSmallInteger('audience_snapshot_signature_version')->nullable();
            });
        }

        if (!Schema::hasColumn(self::TABLE, 'audience_snapshot_share_token')) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->string('audience_snapshot_share_token', 64)->nullable();
            });
        }
    }

    public function down(): void
    {
        // Intentionally non-destructive: historical campaign snapshot integrity metadata is retained.
    }
};
