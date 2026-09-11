<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fbm_campaign_cost_adjustments')) {
            return;
        }

        Schema::create('fbm_campaign_cost_adjustments', function (Blueprint $table) {
            $table->id();
            $table->uuid('adjustment_uuid')->unique();
            $table->date('effective_date');
            $table->unsignedBigInteger('fbm_campaign_id')->nullable();
            $table->unsignedBigInteger('fbm_ad_set_id')->nullable();
            $table->unsignedBigInteger('fbm_ad_id')->nullable();
            $table->char('currency', 3)->default('BDT');
            $table->string('cost_type', 60);
            $table->string('label', 160);
            $table->decimal('base_amount', 14, 2)->default(0);
            $table->decimal('vat_amount', 14, 2)->default(0);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('service_charge_amount', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->string('status', 30)->default('approved');
            $table->string('safe_note', 500)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['effective_date', 'status'], 'fbm_cost_adj_date_status_idx');
            $table->index('fbm_campaign_id', 'fbm_cost_adj_campaign_idx');
            $table->index('fbm_ad_set_id', 'fbm_cost_adj_adset_idx');
            $table->index('fbm_ad_id', 'fbm_cost_adj_ad_idx');
        });
    }

    public function down(): void
    {
        // Local finance adjustment rows are retained for audit continuity.
    }
};
