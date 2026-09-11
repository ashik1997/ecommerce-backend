<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensureOrderAttributions();
        $this->ensureOrderAttributionItems();
        $this->ensureAttributionReconciliations();
        $this->ensureConversionEventLink();
    }

    public function down(): void
    {
        // Immutable attribution snapshots and append-only lifecycle evidence are
        // deliberately retained. Removal requires an explicit retention review.
    }

    private function ensureOrderAttributions(): void
    {
        if (!Schema::hasTable('fbm_order_attributions')) {
            Schema::create('fbm_order_attributions', function (Blueprint $table) {
                $table->id();
                $table->uuid('attribution_uuid')->unique();
                $table->string('source_order_type', 30);
                $table->unsignedBigInteger('source_order_id');
                $table->longText('source_order_reference_ciphertext')->nullable();
                $table->char('source_order_reference_hash', 64)->nullable();
                $table->unsignedBigInteger('fbm_visitor_attribution_session_id')->nullable();
                $table->string('evidence_state', 30);
                $table->longText('evidence_snapshot_ciphertext')->nullable();
                $table->char('evidence_snapshot_hash', 64)->nullable();
                $table->longText('purchase_event_id_ciphertext');
                $table->char('purchase_event_id_hash', 64);
                $table->char('currency', 3)->default('BDT');
                $table->decimal('order_subtotal_snapshot', 14, 2)->default(0);
                $table->decimal('discount_snapshot', 14, 2)->default(0);
                $table->decimal('delivery_fee_snapshot', 14, 2)->default(0);
                $table->decimal('order_total_snapshot', 14, 2)->default(0);
                $table->decimal('item_quantity_snapshot', 14, 3)->default(0);
                $table->string('lifecycle_state_snapshot', 30);
                $table->string('lifecycle_state_current', 30);
                $table->timestamp('snapshot_created_at');
                $table->timestamp('confirmed_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamp('returned_at')->nullable();
                $table->timestamps();

                $table->unique(['source_order_type', 'source_order_id'], 'fbm_order_attr_source_unique');
                $table->index(['evidence_state', 'lifecycle_state_current'], 'fbm_order_attr_state_idx');
                $table->index('purchase_event_id_hash', 'fbm_order_attr_event_hash_idx');
                $table->index('created_at', 'fbm_order_attr_created_idx');
            });

            return;
        }

        $this->addMissingColumns('fbm_order_attributions', [
            'attribution_uuid' => fn(Blueprint $table) => $table->uuid('attribution_uuid')->nullable(),
            'source_order_type' => fn(Blueprint $table) => $table->string('source_order_type', 30)->nullable(),
            'source_order_id' => fn(Blueprint $table) => $table->unsignedBigInteger('source_order_id')->nullable(),
            'source_order_reference_ciphertext' => fn(Blueprint $table) => $table->longText('source_order_reference_ciphertext')->nullable(),
            'source_order_reference_hash' => fn(Blueprint $table) => $table->char('source_order_reference_hash', 64)->nullable(),
            'fbm_visitor_attribution_session_id' => fn(Blueprint $table) => $table->unsignedBigInteger('fbm_visitor_attribution_session_id')->nullable(),
            'evidence_state' => fn(Blueprint $table) => $table->string('evidence_state', 30)->default('missing_session'),
            'evidence_snapshot_ciphertext' => fn(Blueprint $table) => $table->longText('evidence_snapshot_ciphertext')->nullable(),
            'evidence_snapshot_hash' => fn(Blueprint $table) => $table->char('evidence_snapshot_hash', 64)->nullable(),
            'purchase_event_id_ciphertext' => fn(Blueprint $table) => $table->longText('purchase_event_id_ciphertext')->nullable(),
            'purchase_event_id_hash' => fn(Blueprint $table) => $table->char('purchase_event_id_hash', 64)->nullable(),
            'currency' => fn(Blueprint $table) => $table->char('currency', 3)->default('BDT'),
            'order_subtotal_snapshot' => fn(Blueprint $table) => $table->decimal('order_subtotal_snapshot', 14, 2)->default(0),
            'discount_snapshot' => fn(Blueprint $table) => $table->decimal('discount_snapshot', 14, 2)->default(0),
            'delivery_fee_snapshot' => fn(Blueprint $table) => $table->decimal('delivery_fee_snapshot', 14, 2)->default(0),
            'order_total_snapshot' => fn(Blueprint $table) => $table->decimal('order_total_snapshot', 14, 2)->default(0),
            'item_quantity_snapshot' => fn(Blueprint $table) => $table->decimal('item_quantity_snapshot', 14, 3)->default(0),
            'lifecycle_state_snapshot' => fn(Blueprint $table) => $table->string('lifecycle_state_snapshot', 30)->default('unknown'),
            'lifecycle_state_current' => fn(Blueprint $table) => $table->string('lifecycle_state_current', 30)->default('unknown'),
            'snapshot_created_at' => fn(Blueprint $table) => $table->timestamp('snapshot_created_at')->nullable(),
            'confirmed_at' => fn(Blueprint $table) => $table->timestamp('confirmed_at')->nullable(),
            'cancelled_at' => fn(Blueprint $table) => $table->timestamp('cancelled_at')->nullable(),
            'returned_at' => fn(Blueprint $table) => $table->timestamp('returned_at')->nullable(),
            'created_at' => fn(Blueprint $table) => $table->timestamp('created_at')->nullable(),
            'updated_at' => fn(Blueprint $table) => $table->timestamp('updated_at')->nullable(),
        ]);
    }

    private function ensureOrderAttributionItems(): void
    {
        if (Schema::hasTable('fbm_order_attribution_items')) {
            return;
        }

        Schema::create('fbm_order_attribution_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fbm_order_attribution_id');
            $table->unsignedBigInteger('source_order_item_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('product_reference_snapshot', 160)->nullable();
            $table->decimal('quantity_snapshot', 14, 3)->default(0);
            $table->decimal('unit_price_snapshot', 14, 2)->default(0);
            $table->decimal('line_total_snapshot', 14, 2)->default(0);
            $table->timestamps();

            $table->index('fbm_order_attribution_id', 'fbm_order_attr_items_attr_idx');
            $table->index('product_id', 'fbm_order_attr_items_product_idx');
        });
    }

    private function ensureAttributionReconciliations(): void
    {
        if (Schema::hasTable('fbm_attribution_reconciliations')) {
            return;
        }

        Schema::create('fbm_attribution_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fbm_order_attribution_id');
            $table->string('event_type', 40);
            $table->string('previous_state', 30)->nullable();
            $table->string('current_state', 30);
            $table->decimal('amount_delta', 14, 2)->default(0);
            $table->string('safe_reason', 500)->nullable();
            $table->string('origin', 40);
            $table->timestamp('created_at');

            $table->index('fbm_order_attribution_id', 'fbm_attr_reconcile_attr_idx');
            $table->index(['event_type', 'created_at'], 'fbm_attr_reconcile_event_idx');
        });
    }

    private function ensureConversionEventLink(): void
    {
        if (!Schema::hasTable('fbm_conversion_events')
            || Schema::hasColumn('fbm_conversion_events', 'fbm_order_attribution_id')) {
            return;
        }

        Schema::table('fbm_conversion_events', function (Blueprint $table) {
            $table->unsignedBigInteger('fbm_order_attribution_id')->nullable()->after('fbm_visitor_attribution_session_id');
            $table->index('fbm_order_attribution_id', 'fbm_capi_events_order_attr_idx');
        });
    }

    private function addMissingColumns(string $tableName, array $columns): void
    {
        foreach ($columns as $column => $definition) {
            if (Schema::hasColumn($tableName, $column)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($definition) {
                $definition($table);
            });
        }
    }
};
