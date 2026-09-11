<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensureVisitorAttributionSessions();
        $this->ensureLandingAttributionSettings();
    }

    public function down(): void
    {
        Schema::dropIfExists('fbm_visitor_attribution_sessions');

        // Opt-in and retention settings are deliberately preserved. Dropping
        // them on rollback could silently change the application's tracking choice.
    }

    private function ensureVisitorAttributionSessions(): void
    {
        if (Schema::hasTable('fbm_visitor_attribution_sessions')) {
            return;
        }

        Schema::create('fbm_visitor_attribution_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('session_uuid')->unique();
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            $table->timestamp('expires_at');
            $table->unsignedInteger('capture_count')->default(1);

            $table->text('first_landing_url')->nullable();
            $table->text('latest_landing_url')->nullable();
            $table->text('first_referrer_url')->nullable();
            $table->text('latest_referrer_url')->nullable();

            $table->string('first_utm_source', 255)->nullable();
            $table->string('first_utm_medium', 255)->nullable();
            $table->string('first_utm_campaign', 255)->nullable();
            $table->string('first_utm_content', 255)->nullable();
            $table->string('first_utm_term', 255)->nullable();
            $table->string('first_utm_id', 255)->nullable();

            $table->string('latest_utm_source', 255)->nullable();
            $table->string('latest_utm_medium', 255)->nullable();
            $table->string('latest_utm_campaign', 255)->nullable();
            $table->string('latest_utm_content', 255)->nullable();
            $table->string('latest_utm_term', 255)->nullable();
            $table->string('latest_utm_id', 255)->nullable();

            $table->text('fbclid_ciphertext')->nullable();
            $table->text('fbc_ciphertext')->nullable();
            $table->text('fbp_ciphertext')->nullable();

            $table->char('request_ip_hash', 64)->nullable();
            $table->char('user_agent_hash', 64)->nullable();
            $table->timestamps();

            $table->index('expires_at', 'fbm_attr_expires_idx');
            $table->index('last_seen_at', 'fbm_attr_last_seen_idx');
        });
    }

    private function ensureLandingAttributionSettings(): void
    {
        if (!Schema::hasTable('fbm_module_settings')) {
            return;
        }

        $columns = [
            'landing_attribution_enabled' => fn(Blueprint $table) => $table->boolean('landing_attribution_enabled')->default(false),
            'landing_attribution_retention_days' => fn(Blueprint $table) => $table->unsignedSmallInteger('landing_attribution_retention_days')->default(90),
        ];

        foreach ($columns as $column => $definition) {
            if (Schema::hasColumn('fbm_module_settings', $column)) {
                continue;
            }

            Schema::table('fbm_module_settings', function (Blueprint $table) use ($definition) {
                $definition($table);
            });
        }
    }
};
