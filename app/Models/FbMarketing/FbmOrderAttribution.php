<?php

namespace App\Models\FbMarketing;

use App\Casts\EncryptedNullableString;
use Illuminate\Database\Eloquent\Model;

class FbmOrderAttribution extends Model
{
    public const SOURCE_LEGACY_ORDER = 'legacy_order';
    public const SOURCE_PRODUCT_ORDER = 'product_order';

    public const EVIDENCE_ATTRIBUTED = 'attributed';
    public const EVIDENCE_MISSING_SESSION = 'missing_session';
    public const EVIDENCE_INVALID_SESSION = 'invalid_session';
    public const EVIDENCE_EXPIRED_SESSION = 'expired_session';

    protected $table = 'fbm_order_attributions';

    protected $fillable = [
        'attribution_uuid',
        'source_order_type',
        'source_order_id',
        'source_order_reference_ciphertext',
        'source_order_reference_hash',
        'fbm_visitor_attribution_session_id',
        'evidence_state',
        'evidence_snapshot_ciphertext',
        'evidence_snapshot_hash',
        'purchase_event_id_ciphertext',
        'purchase_event_id_hash',
        'currency',
        'order_subtotal_snapshot',
        'discount_snapshot',
        'delivery_fee_snapshot',
        'order_total_snapshot',
        'item_quantity_snapshot',
        'lifecycle_state_snapshot',
        'lifecycle_state_current',
        'snapshot_created_at',
        'confirmed_at',
        'cancelled_at',
        'returned_at',
    ];

    protected $casts = [
        'source_order_reference_ciphertext' => EncryptedNullableString::class,
        'evidence_snapshot_ciphertext' => EncryptedNullableString::class,
        'purchase_event_id_ciphertext' => EncryptedNullableString::class,
        'source_order_id' => 'integer',
        'order_subtotal_snapshot' => 'float',
        'discount_snapshot' => 'float',
        'delivery_fee_snapshot' => 'float',
        'order_total_snapshot' => 'float',
        'item_quantity_snapshot' => 'float',
        'snapshot_created_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    protected $hidden = [
        'source_order_reference_ciphertext',
        'source_order_reference_hash',
        'evidence_snapshot_ciphertext',
        'evidence_snapshot_hash',
        'purchase_event_id_ciphertext',
        'purchase_event_id_hash',
    ];

    public function attributionSession()
    {
        return $this->belongsTo(FbmVisitorAttributionSession::class, 'fbm_visitor_attribution_session_id');
    }

    public function items()
    {
        return $this->hasMany(FbmOrderAttributionItem::class, 'fbm_order_attribution_id');
    }

    public function reconciliations()
    {
        return $this->hasMany(FbmAttributionReconciliation::class, 'fbm_order_attribution_id');
    }

    public function conversionEvents()
    {
        return $this->hasMany(FbmConversionEvent::class, 'fbm_order_attribution_id');
    }

    public function toSafeSummary(): array
    {
        $conversionEventCount = $this->relationLoaded('conversionEvents')
            ? $this->conversionEvents->count()
            : $this->conversionEvents()->count();

        return [
            'attribution_uuid' => (string) $this->attribution_uuid,
            'source_order_type' => (string) $this->source_order_type,
            'source_order_id' => (int) $this->source_order_id,
            'evidence_state' => (string) $this->evidence_state,
            'currency' => (string) $this->currency,
            'order_total_snapshot' => (float) $this->order_total_snapshot,
            'item_quantity_snapshot' => (float) $this->item_quantity_snapshot,
            'lifecycle_state_snapshot' => (string) $this->lifecycle_state_snapshot,
            'lifecycle_state_current' => (string) $this->lifecycle_state_current,
            'conversion_event_count' => (int) $conversionEventCount,
            'snapshot_created_at' => optional($this->snapshot_created_at)->toDateTimeString(),
            'confirmed_at' => optional($this->confirmed_at)->toDateTimeString(),
            'cancelled_at' => optional($this->cancelled_at)->toDateTimeString(),
            'returned_at' => optional($this->returned_at)->toDateTimeString(),
        ];
    }
}
