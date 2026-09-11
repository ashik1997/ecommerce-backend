<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fbm_lead_ad_events')) {
            Schema::create('fbm_lead_ad_events', function (Blueprint $table) {
                $table->id();
                $table->uuid('event_uuid')->unique();
                $table->unsignedBigInteger('fbm_connection_id')->nullable();
                $table->unsignedBigInteger('fbm_page_id')->nullable();
                $table->string('provider_leadgen_id', 190)->nullable();
                $table->string('provider_form_id', 190)->nullable();
                $table->string('provider_page_id', 190)->nullable();
                $table->string('provider_ad_id', 190)->nullable();
                $table->string('provider_adgroup_id', 190)->nullable();
                $table->string('status', 40)->default('received');
                $table->json('field_keys')->nullable();
                $table->json('mapped_field_flags')->nullable();
                $table->unsignedBigInteger('crm_lead_id')->nullable();
                $table->string('request_fingerprint', 80)->nullable();
                $table->string('redacted_message', 500)->nullable();
                $table->timestamp('received_at')->nullable();
                $table->timestamp('mapped_at')->nullable();
                $table->timestamps();

                $table->unique('provider_leadgen_id', 'fbm_lead_event_provider_lead_uq');
                $table->index(['status', 'received_at'], 'fbm_lead_event_status_idx');
                $table->index('crm_lead_id', 'fbm_lead_event_crm_idx');
            });
        }

        if (!Schema::hasTable('fbm_lead_ad_webhook_logs')) {
            Schema::create('fbm_lead_ad_webhook_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('fbm_connection_id')->nullable();
                $table->string('event_type', 40);
                $table->string('status', 40);
                $table->string('request_fingerprint', 80)->nullable();
                $table->string('redacted_message', 500)->nullable();
                $table->timestamp('created_at')->nullable();

                $table->index(['event_type', 'status'], 'fbm_lead_webhook_status_idx');
            });
        }
    }

    public function down(): void
    {
        // Lead webhook and CRM mapping ledgers are retained intentionally.
    }
};
