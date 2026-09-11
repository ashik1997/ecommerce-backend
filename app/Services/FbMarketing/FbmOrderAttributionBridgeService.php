<?php

namespace App\Services\FbMarketing;

use App\Models\FbMarketing\FbmAttributionReconciliation;
use App\Models\FbMarketing\FbmConnection;
use App\Models\FbMarketing\FbmConversionEvent;
use App\Models\FbMarketing\FbmOrderAttribution;
use App\Models\FbMarketing\FbmOrderAttributionItem;
use App\Models\FbMarketing\FbmVisitorAttributionSession;
use App\Models\Order;
use App\Models\ProductOrder;
use App\Support\Security\SecretRedactor;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class FbmOrderAttributionBridgeService
{
    private $lifecycle;
    private $conversionEvents;
    private $dispatch;
    private $capiReadiness;
    private $queueReadiness;

    public function __construct(
        FbmOrderLifecycleClassifier $lifecycle,
        FbmConversionEventService $conversionEvents,
        FbmConversionEventDispatchService $dispatch,
        FbmConversionEventReadinessService $capiReadiness,
        FbmQueueReadinessService $queueReadiness
    ) {
        $this->lifecycle = $lifecycle;
        $this->conversionEvents = $conversionEvents;
        $this->dispatch = $dispatch;
        $this->capiReadiness = $capiReadiness;
        $this->queueReadiness = $queueReadiness;
    }

    public static function tryCaptureLegacyOrderFromRequest(int $orderId, Request $request, string $origin = 'checkout'): ?FbmOrderAttribution
    {
        try {
            return app(static::class)->captureLegacyOrderFromRequest($orderId, $request, $origin);
        } catch (Throwable $exception) {
            static::logBootstrapFailure(FbmOrderAttribution::SOURCE_LEGACY_ORDER, $orderId, $exception);

            return null;
        }
    }

    public static function tryReconcileLegacyOrderById(int $orderId, string $origin = 'legacy_reconcile'): ?FbmOrderAttribution
    {
        try {
            return app(static::class)->reconcileLegacyOrderById($orderId, $origin);
        } catch (Throwable $exception) {
            static::logBootstrapFailure(FbmOrderAttribution::SOURCE_LEGACY_ORDER, $orderId, $exception);

            return null;
        }
    }

    public static function tryReconcileProductOrderById(int $orderId, string $origin = 'product_order_reconcile'): ?FbmOrderAttribution
    {
        try {
            return app(static::class)->reconcileProductOrderById($orderId, $origin);
        } catch (Throwable $exception) {
            static::logBootstrapFailure(FbmOrderAttribution::SOURCE_PRODUCT_ORDER, $orderId, $exception);

            return null;
        }
    }

    public function schemaReady(): bool
    {
        try {
            return Schema::hasTable('fbm_order_attributions')
                && Schema::hasTable('fbm_order_attribution_items')
                && Schema::hasTable('fbm_attribution_reconciliations')
                && Schema::hasTable('fbm_conversion_events')
                && Schema::hasColumn('fbm_conversion_events', 'fbm_order_attribution_id');
        } catch (Throwable $exception) {
            return false;
        }
    }

    public function captureLegacyOrderFromRequest(int $orderId, Request $request, string $origin = 'checkout'): ?FbmOrderAttribution
    {
        return $this->safelyBridge(function () use ($orderId, $request, $origin) {
            return $this->captureNormalized($this->normalizeLegacyOrder($orderId, $this->requestHandoff($request)), $origin);
        }, FbmOrderAttribution::SOURCE_LEGACY_ORDER, $orderId);
    }

    public function reconcileLegacyOrderById(int $orderId, string $origin = 'legacy_reconcile'): ?FbmOrderAttribution
    {
        return $this->safelyBridge(function () use ($orderId, $origin) {
            return $this->captureNormalized($this->normalizeLegacyOrder($orderId), $origin);
        }, FbmOrderAttribution::SOURCE_LEGACY_ORDER, $orderId);
    }

    public function reconcileProductOrderById(int $orderId, string $origin = 'product_order_reconcile'): ?FbmOrderAttribution
    {
        return $this->safelyBridge(function () use ($orderId, $origin) {
            $snapshot = $this->normalizeProductOrder($orderId);
            if ($snapshot === null) {
                return null;
            }

            return $this->captureNormalized($snapshot, $origin);
        }, FbmOrderAttribution::SOURCE_PRODUCT_ORDER, $orderId);
    }

    public function reconcileRecent(?int $requestedLimit = null): array
    {
        $limit = $this->reconcileLimit($requestedLimit);
        $summary = [
            'schema_ready' => $this->schemaReady(),
            'limit_per_source' => $limit,
            'legacy_scanned' => 0,
            'product_orders_scanned' => 0,
            'bridged' => 0,
            'warning_count' => 0,
        ];

        if (!$summary['schema_ready']) {
            return $summary;
        }

        $legacyIds = Schema::hasTable('orders')
            ? Order::query()->orderByDesc('id')->limit($limit)->pluck('id')
            : collect();
        foreach ($legacyIds as $id) {
            $summary['legacy_scanned']++;
            $attribution = $this->reconcileLegacyOrderById((int) $id, 'manual_recent_reconcile');
            $attribution ? $summary['bridged']++ : $summary['warning_count']++;
        }

        $productIds = Schema::hasTable('product_orders')
            ? ProductOrder::query()
                ->whereRaw('LOWER(order_source) IN (?, ?)', ['ecommerce', 'website'])
                ->orderByDesc('id')
                ->limit($limit)
                ->pluck('id')
            : collect();
        foreach ($productIds as $id) {
            $summary['product_orders_scanned']++;
            $attribution = $this->reconcileProductOrderById((int) $id, 'manual_recent_reconcile');
            $attribution ? $summary['bridged']++ : $summary['warning_count']++;
        }

        return $summary;
    }

    public function operationalSummary(): array
    {
        if (!$this->schemaReady()) {
            return [
                'schema_ready' => false,
                'total_orders' => 0,
                'attributed_orders' => 0,
                'missing_session_orders' => 0,
                'invalid_or_expired_session_orders' => 0,
                'pending_orders' => 0,
                'confirmed_orders' => 0,
                'cancelled_orders' => 0,
                'returned_orders' => 0,
                'unknown_lifecycle_orders' => 0,
                'capi_linked_orders' => 0,
                'pending_manual_delivery' => 0,
                'reconciliation_count' => 0,
            ];
        }

        $query = FbmOrderAttribution::query();

        return [
            'schema_ready' => true,
            'total_orders' => (clone $query)->count(),
            'attributed_orders' => (clone $query)->where('evidence_state', FbmOrderAttribution::EVIDENCE_ATTRIBUTED)->count(),
            'missing_session_orders' => (clone $query)->where('evidence_state', FbmOrderAttribution::EVIDENCE_MISSING_SESSION)->count(),
            'invalid_or_expired_session_orders' => (clone $query)->whereIn('evidence_state', [
                FbmOrderAttribution::EVIDENCE_INVALID_SESSION,
                FbmOrderAttribution::EVIDENCE_EXPIRED_SESSION,
            ])->count(),
            'pending_orders' => (clone $query)->where('lifecycle_state_current', FbmOrderLifecycleClassifier::STATE_PENDING)->count(),
            'confirmed_orders' => (clone $query)->where('lifecycle_state_current', FbmOrderLifecycleClassifier::STATE_CONFIRMED)->count(),
            'cancelled_orders' => (clone $query)->where('lifecycle_state_current', FbmOrderLifecycleClassifier::STATE_CANCELLED)->count(),
            'returned_orders' => (clone $query)->where('lifecycle_state_current', FbmOrderLifecycleClassifier::STATE_RETURNED)->count(),
            'unknown_lifecycle_orders' => (clone $query)->where('lifecycle_state_current', FbmOrderLifecycleClassifier::STATE_UNKNOWN)->count(),
            'capi_linked_orders' => (clone $query)->whereHas('conversionEvents')->count(),
            'pending_manual_delivery' => (clone $query)
                ->whereHas('conversionEvents', function ($eventQuery) {
                    $eventQuery->where('status', FbmConversionEvent::STATUS_PENDING);
                })
                ->count(),
            'reconciliation_count' => FbmAttributionReconciliation::query()->count(),
        ];
    }

    public function recentSafeSummaries(): \Illuminate\Support\Collection
    {
        if (!$this->schemaReady()) {
            return collect();
        }

        return FbmOrderAttribution::query()
            ->with('conversionEvents:id,fbm_order_attribution_id')
            ->orderByDesc('snapshot_created_at')
            ->orderByDesc('id')
            ->limit(max(1, min(250, (int) config('fb_marketing.order_attribution.history_limit', 100))))
            ->get()
            ->map(function (FbmOrderAttribution $attribution): array {
                return $attribution->toSafeSummary();
            });
    }

    private static function logBootstrapFailure(string $sourceType, int $sourceId, Throwable $exception): void
    {
        try {
            Log::warning('FB MARKETING order attribution bridge dependency resolution stopped safely.', [
                'source_order_type' => $sourceType,
                'source_order_id' => $sourceId,
                'exception_class' => get_class($exception),
                'redacted_message' => substr(SecretRedactor::redactString($exception->getMessage()), 0, 500),
            ]);
        } catch (Throwable $ignored) {
            // Order placement and lifecycle changes remain authoritative even if logging is unavailable.
        }
    }

    private function safelyBridge(callable $callback, string $sourceType, int $sourceId): ?FbmOrderAttribution
    {
        if (!$this->schemaReady()) {
            return null;
        }

        try {
            return $callback();
        } catch (Throwable $exception) {
            Log::warning('FB MARKETING order attribution bridge stopped safely.', [
                'source_order_type' => $sourceType,
                'source_order_id' => $sourceId,
                'exception_class' => get_class($exception),
                'redacted_message' => substr(SecretRedactor::redactString($exception->getMessage()), 0, 500),
            ]);

            return null;
        }
    }

    private function captureNormalized(array $snapshot, string $origin): FbmOrderAttribution
    {
        $existing = FbmOrderAttribution::query()
            ->where('source_order_type', $snapshot['source_order_type'])
            ->where('source_order_id', $snapshot['source_order_id'])
            ->first();

        if ($existing) {
            $this->reconcileExisting($existing, $snapshot, $origin);
            $this->ensurePurchaseEvent($existing->fresh(), $snapshot);

            return $existing->fresh();
        }

        try {
            $attribution = DB::transaction(function () use ($snapshot, $origin) {
                $now = now();
                $attribution = FbmOrderAttribution::query()->create([
                    'attribution_uuid' => (string) Str::uuid(),
                    'source_order_type' => $snapshot['source_order_type'],
                    'source_order_id' => $snapshot['source_order_id'],
                    'source_order_reference_ciphertext' => $snapshot['source_order_reference'],
                    'source_order_reference_hash' => $this->hmac('source-ref|' . (string) $snapshot['source_order_reference']),
                    'fbm_visitor_attribution_session_id' => $snapshot['session_id'],
                    'evidence_state' => $snapshot['evidence_state'],
                    'evidence_snapshot_ciphertext' => $this->encodeJson($snapshot['evidence_snapshot']),
                    'evidence_snapshot_hash' => $this->hmac('evidence|' . $this->encodeJson($snapshot['evidence_snapshot'])),
                    'purchase_event_id_ciphertext' => $snapshot['purchase_event_id'],
                    'purchase_event_id_hash' => $this->hmac('purchase-event|' . $snapshot['purchase_event_id']),
                    'currency' => $snapshot['currency'],
                    'order_subtotal_snapshot' => $snapshot['subtotal'],
                    'discount_snapshot' => $snapshot['discount'],
                    'delivery_fee_snapshot' => $snapshot['delivery_fee'],
                    'order_total_snapshot' => $snapshot['total'],
                    'item_quantity_snapshot' => $snapshot['item_quantity'],
                    'lifecycle_state_snapshot' => $snapshot['lifecycle_state'],
                    'lifecycle_state_current' => $snapshot['lifecycle_state'],
                    'snapshot_created_at' => $now,
                    'confirmed_at' => $snapshot['lifecycle_state'] === FbmOrderLifecycleClassifier::STATE_CONFIRMED ? $now : null,
                    'cancelled_at' => $snapshot['lifecycle_state'] === FbmOrderLifecycleClassifier::STATE_CANCELLED ? $now : null,
                    'returned_at' => $snapshot['lifecycle_state'] === FbmOrderLifecycleClassifier::STATE_RETURNED ? $now : null,
                ]);

                foreach ($snapshot['items'] as $item) {
                    $attribution->items()->create($item);
                }

                $this->appendReconciliation($attribution, 'captured', null, $snapshot['lifecycle_state'], 0, $origin, $this->safeLifecycleReason($snapshot));

                return $attribution;
            });
        } catch (QueryException $exception) {
            $attribution = FbmOrderAttribution::query()
                ->where('source_order_type', $snapshot['source_order_type'])
                ->where('source_order_id', $snapshot['source_order_id'])
                ->first();

            if (!$attribution) {
                throw $exception;
            }

            $this->reconcileExisting($attribution, $snapshot, $origin);
        }

        $this->ensurePurchaseEvent($attribution->fresh(), $snapshot);

        return $attribution->fresh();
    }

    private function reconcileExisting(FbmOrderAttribution $attribution, array $snapshot, string $origin): void
    {
        $trackedTotal = (float) $attribution->order_total_snapshot
            + (float) $attribution->reconciliations()->where('event_type', 'amount_adjusted')->sum('amount_delta');
        $amountDelta = round((float) $snapshot['total'] - $trackedTotal, 2);

        if (abs($amountDelta) >= 0.01) {
            $this->appendReconciliation(
                $attribution,
                'amount_adjusted',
                (string) $attribution->lifecycle_state_current,
                (string) $attribution->lifecycle_state_current,
                $amountDelta,
                $origin,
                'ERP order amount changed after the immutable attribution snapshot.'
            );
        }

        $previousState = (string) $attribution->lifecycle_state_current;
        $currentState = (string) $snapshot['lifecycle_state'];
        if ($previousState === $currentState) {
            return;
        }

        $eventType = $this->lifecycle->transitionEventType($previousState, $currentState);
        $this->appendReconciliation($attribution, $eventType, $previousState, $currentState, 0, $origin, $this->safeLifecycleReason($snapshot));

        $updates = ['lifecycle_state_current' => $currentState];
        if ($currentState === FbmOrderLifecycleClassifier::STATE_CONFIRMED && !$attribution->confirmed_at) {
            $updates['confirmed_at'] = now();
        }
        if ($currentState === FbmOrderLifecycleClassifier::STATE_CANCELLED && !$attribution->cancelled_at) {
            $updates['cancelled_at'] = now();
        }
        if ($currentState === FbmOrderLifecycleClassifier::STATE_RETURNED && !$attribution->returned_at) {
            $updates['returned_at'] = now();
        }
        $attribution->forceFill($updates)->save();
    }

    private function appendReconciliation(
        FbmOrderAttribution $attribution,
        string $eventType,
        ?string $previousState,
        string $currentState,
        float $amountDelta,
        string $origin,
        ?string $safeReason = null
    ): void {
        FbmAttributionReconciliation::query()->create([
            'fbm_order_attribution_id' => (int) $attribution->id,
            'event_type' => substr($eventType, 0, 40),
            'previous_state' => $previousState !== null ? substr($previousState, 0, 30) : null,
            'current_state' => substr($currentState, 0, 30),
            'amount_delta' => round($amountDelta, 2),
            'safe_reason' => $safeReason !== null ? substr($safeReason, 0, 500) : null,
            'origin' => substr($origin, 0, 40),
            'created_at' => now(),
        ]);
    }

    private function ensurePurchaseEvent(FbmOrderAttribution $attribution, array $snapshot): void
    {
        if ((string) $attribution->evidence_state !== FbmOrderAttribution::EVIDENCE_ATTRIBUTED
            || (string) $attribution->lifecycle_state_current !== FbmOrderLifecycleClassifier::STATE_CONFIRMED
            || $this->capiReadiness->currentMode() !== FbmConversionEventReadinessService::MODE_LIVE
            || $attribution->conversionEvents()->exists()) {
            return;
        }

        $connection = FbmConnection::query()->where('is_active', true)->orderBy('id')->get()->first(function (FbmConnection $candidate): bool {
            return $candidate->hasConfiguredSecret('capi_access_token');
        });
        if (!$connection) {
            return;
        }

        $evidence = $this->decodeJson((string) $attribution->evidence_snapshot_ciphertext);
        $event = $this->conversionEvents->createPurchaseEvent($connection, [
            'fbm_order_attribution_id' => (int) $attribution->id,
            'event_id' => (string) $attribution->purchase_event_id_ciphertext,
            'event_time' => time(),
            'event_source_url' => $this->eventSourceUrl($evidence),
            'client_user_agent' => $this->boundedString($evidence['client_user_agent'] ?? null, 512) ?: 'FBM-15-ERP-Order-Bridge/1.0',
            'client_ip_address' => $this->boundedString($evidence['client_ip_address'] ?? null, 64),
            'attribution_session_uuid' => $this->boundedString($evidence['session_uuid'] ?? null, 36),
            'fbc' => $this->boundedString($evidence['fbc'] ?? null, 2048),
            'fbp' => $this->boundedString($evidence['fbp'] ?? null, 2048),
            'email' => $snapshot['customer_email'],
            'phone' => $snapshot['customer_phone'],
            'external_id' => $snapshot['external_customer_id'],
            'currency' => $snapshot['currency'],
            'value' => $snapshot['total'],
            'content_ids' => array_column($snapshot['contents'], 'id'),
            'contents' => $snapshot['contents'],
        ]);

        if ($event->fbm_order_attribution_id !== null
            && (int) $event->fbm_order_attribution_id !== (int) $attribution->id) {
            Log::warning('FB MARKETING Purchase event identifier collision stopped safely.', [
                'fbm_order_attribution_id' => (int) $attribution->id,
                'existing_order_attribution_id' => (int) $event->fbm_order_attribution_id,
                'fbm_conversion_event_id' => (int) $event->id,
            ]);

            return;
        }

        if ((string) $event->status !== FbmConversionEvent::STATUS_PENDING) {
            return;
        }

        try {
            if ($this->queueReadiness->currentSummary()['ready']) {
                $this->dispatch->dispatch($event);
            }
        } catch (Throwable $exception) {
            Log::warning('FB MARKETING order CAPI event remains available for manual no-queue delivery.', [
                'fbm_order_attribution_id' => (int) $attribution->id,
                'fbm_conversion_event_id' => (int) $event->id,
                'exception_class' => get_class($exception),
            ]);
        }
    }

    private function normalizeLegacyOrder(int $orderId, array $handoff = []): array
    {
        $order = Order::query()->with(['orderDetails', 'shippingInfo', 'user'])->findOrFail($orderId);
        $evidence = $this->resolveEvidence($handoff);
        $items = $order->orderDetails->map(function ($item): array {
            $productId = $item->product_id !== null ? (int) $item->product_id : null;

            return [
                'source_order_item_id' => $item->id !== null ? (int) $item->id : null,
                'product_id' => $productId,
                'product_reference_snapshot' => $productId ? 'product:' . $productId : null,
                'quantity_snapshot' => max(0, (float) ($item->qty ?? 0)),
                'unit_price_snapshot' => max(0, (float) ($item->unit_price ?? 0)),
                'line_total_snapshot' => max(0, (float) ($item->total_price ?? 0)),
            ];
        })->values()->all();
        $shipping = $order->shippingInfo;
        $user = $order->user;

        return $this->normalizedOrderSnapshot([
            'source_order_type' => FbmOrderAttribution::SOURCE_LEGACY_ORDER,
            'source_order_id' => (int) $order->id,
            'source_order_reference' => (string) ($order->order_no ?: $order->id),
            'purchase_event_id' => $this->purchaseEventId($handoff),
            'evidence' => $evidence,
            'currency' => 'BDT',
            'subtotal' => $order->sub_total,
            'discount' => $order->discount,
            'delivery_fee' => $order->delivery_fee,
            'total' => $order->total,
            'items' => $items,
            'lifecycle_state' => $this->lifecycle->classifyLegacy($order->order_status),
            'customer_email' => optional($shipping)->email ?: optional($user)->email,
            'customer_phone' => optional($shipping)->phone ?: optional($user)->phone,
            'external_customer_id' => $order->user_id !== null ? 'legacy-user:' . (int) $order->user_id : null,
        ]);
    }

    private function normalizeProductOrder(int $orderId): ?array
    {
        $order = ProductOrder::query()->with(['order_products', 'customer'])->findOrFail($orderId);
        $source = strtolower(trim((string) ($order->order_source ?? '')));
        if (!in_array($source, ['ecommerce', 'website'], true)) {
            return null;
        }

        $handoff = is_array($order->request_data) ? $order->request_data : [];
        $evidence = $this->resolveEvidence($handoff);
        $items = $order->order_products->map(function ($item): array {
            $productId = $item->product_id !== null ? (int) $item->product_id : null;

            return [
                'source_order_item_id' => $item->id !== null ? (int) $item->id : null,
                'product_id' => $productId,
                'product_reference_snapshot' => $productId ? 'product:' . $productId : null,
                'quantity_snapshot' => max(0, (float) ($item->qty ?? 0)),
                'unit_price_snapshot' => max(0, (float) ($item->sale_price ?? 0)),
                'line_total_snapshot' => max(0, (float) ($item->total_price ?? 0)),
            ];
        })->values()->all();
        $activeReturnCount = Schema::hasTable('product_order_returns')
            ? DB::table('product_order_returns')->where('product_order_id', $order->id)->where('status', 'active')->count()
            : 0;
        $customer = $order->customer;

        return $this->normalizedOrderSnapshot([
            'source_order_type' => FbmOrderAttribution::SOURCE_PRODUCT_ORDER,
            'source_order_id' => (int) $order->id,
            'source_order_reference' => (string) ($order->order_code ?: $order->slug ?: $order->id),
            'purchase_event_id' => $this->purchaseEventId($handoff),
            'evidence' => $evidence,
            'currency' => 'BDT',
            'subtotal' => $order->subtotal,
            'discount' => $order->calculated_discount_amount ?? $order->discount_amount,
            'delivery_fee' => $order->delivery_fee,
            'total' => $order->total,
            'items' => $items,
            'lifecycle_state' => $this->lifecycle->classifyProductOrder($order, (int) $activeReturnCount),
            'customer_email' => optional($customer)->email,
            'customer_phone' => $order->customer_phone ?: optional($customer)->phone,
            'external_customer_id' => $order->customer_id !== null ? 'product-order-customer:' . (int) $order->customer_id : null,
        ]);
    }

    private function normalizedOrderSnapshot(array $input): array
    {
        $items = array_values($input['items']);
        $contents = [];
        foreach (array_slice($items, 0, max(1, min(500, (int) config('fb_marketing.conversions_api.max_contents', 100)))) as $item) {
            if (empty($item['product_reference_snapshot'])) {
                continue;
            }
            $contents[] = [
                'id' => (string) $item['product_reference_snapshot'],
                'quantity' => max(1, (int) round((float) $item['quantity_snapshot'])),
                'item_price' => round(max(0, (float) $item['unit_price_snapshot']), 2),
            ];
        }

        return [
            'source_order_type' => (string) $input['source_order_type'],
            'source_order_id' => (int) $input['source_order_id'],
            'source_order_reference' => substr((string) $input['source_order_reference'], 0, 500),
            'purchase_event_id' => (string) $input['purchase_event_id'],
            'session_id' => $input['evidence']['session_id'],
            'evidence_state' => $input['evidence']['state'],
            'evidence_snapshot' => $input['evidence']['snapshot'],
            'currency' => strtoupper(substr((string) ($input['currency'] ?? 'BDT'), 0, 3)),
            'subtotal' => round(max(0, (float) ($input['subtotal'] ?? 0)), 2),
            'discount' => round(max(0, (float) ($input['discount'] ?? 0)), 2),
            'delivery_fee' => round(max(0, (float) ($input['delivery_fee'] ?? 0)), 2),
            'total' => round(max(0, (float) ($input['total'] ?? 0)), 2),
            'item_quantity' => round(array_sum(array_map(function (array $item): float {
                return max(0, (float) ($item['quantity_snapshot'] ?? 0));
            }, $items)), 3),
            'items' => $items,
            'contents' => $contents,
            'lifecycle_state' => (string) $input['lifecycle_state'],
            'customer_email' => $input['customer_email'] ?? null,
            'customer_phone' => $input['customer_phone'] ?? null,
            'external_customer_id' => $input['external_customer_id'] ?? null,
        ];
    }

    private function requestHandoff(Request $request): array
    {
        return [
            'fbm_attribution_session_uuid' => $request->input('fbm_attribution_session_uuid'),
            'fbm_purchase_event_id' => $request->input('fbm_purchase_event_id'),
            'client_user_agent' => $request->userAgent(),
            'client_ip_address' => $request->ip(),
        ];
    }

    private function resolveEvidence(array $handoff): array
    {
        $uuid = $this->boundedString($handoff['fbm_attribution_session_uuid'] ?? $handoff['attribution_session_uuid'] ?? null, 36);
        $snapshot = [
            'session_uuid' => $uuid,
            'client_user_agent' => $this->boundedString($handoff['client_user_agent'] ?? null, 512),
            'client_ip_address' => $this->boundedString($handoff['client_ip_address'] ?? null, 64),
        ];

        if ($uuid === null) {
            return ['session_id' => null, 'state' => FbmOrderAttribution::EVIDENCE_MISSING_SESSION, 'snapshot' => $snapshot];
        }
        if (!Str::isUuid($uuid)) {
            return ['session_id' => null, 'state' => FbmOrderAttribution::EVIDENCE_INVALID_SESSION, 'snapshot' => $snapshot];
        }

        $session = Schema::hasTable('fbm_visitor_attribution_sessions')
            ? FbmVisitorAttributionSession::query()->where('session_uuid', $uuid)->first()
            : null;
        if (!$session) {
            return ['session_id' => null, 'state' => FbmOrderAttribution::EVIDENCE_INVALID_SESSION, 'snapshot' => $snapshot];
        }
        if (!$session->expires_at || $session->expires_at->lte(now())) {
            return ['session_id' => (int) $session->id, 'state' => FbmOrderAttribution::EVIDENCE_EXPIRED_SESSION, 'snapshot' => $snapshot];
        }

        $snapshot = array_merge($snapshot, [
            'first_seen_at' => optional($session->first_seen_at)->toIso8601String(),
            'last_seen_at' => optional($session->last_seen_at)->toIso8601String(),
            'expires_at' => optional($session->expires_at)->toIso8601String(),
            'first_landing_url' => $session->first_landing_url,
            'latest_landing_url' => $session->latest_landing_url,
            'first_referrer_url' => $session->first_referrer_url,
            'latest_referrer_url' => $session->latest_referrer_url,
            'first_utm_source' => $session->first_utm_source,
            'first_utm_medium' => $session->first_utm_medium,
            'first_utm_campaign' => $session->first_utm_campaign,
            'first_utm_content' => $session->first_utm_content,
            'first_utm_term' => $session->first_utm_term,
            'first_utm_id' => $session->first_utm_id,
            'latest_utm_source' => $session->latest_utm_source,
            'latest_utm_medium' => $session->latest_utm_medium,
            'latest_utm_campaign' => $session->latest_utm_campaign,
            'latest_utm_content' => $session->latest_utm_content,
            'latest_utm_term' => $session->latest_utm_term,
            'latest_utm_id' => $session->latest_utm_id,
            'fbc' => $session->fbc_ciphertext,
            'fbp' => $session->fbp_ciphertext,
        ]);

        return ['session_id' => (int) $session->id, 'state' => FbmOrderAttribution::EVIDENCE_ATTRIBUTED, 'snapshot' => $snapshot];
    }

    private function purchaseEventId(array $handoff): string
    {
        $value = $this->boundedString($handoff['fbm_purchase_event_id'] ?? $handoff['event_id'] ?? null, 255);
        if ($value !== null && preg_match('/^[A-Za-z0-9._:-]+$/', $value)) {
            return $value;
        }

        return 'fbm15-order-' . (string) Str::uuid();
    }

    private function eventSourceUrl(array $evidence): string
    {
        foreach (['latest_landing_url', 'first_landing_url'] as $key) {
            $url = $this->boundedString($evidence[$key] ?? null, 2048);
            if ($url !== null && preg_match('/^https?:\/\//i', $url)) {
                return $url;
            }
        }

        $appUrl = trim((string) config('app.url', ''));

        return preg_match('/^https?:\/\//i', $appUrl) ? rtrim($appUrl, '/') . '/' : 'https://localhost/';
    }

    private function safeLifecycleReason(array $snapshot): ?string
    {
        return $snapshot['lifecycle_state'] === FbmOrderLifecycleClassifier::STATE_UNKNOWN
            ? 'ERP order lifecycle status was not recognized. Review the deployed order-status schema before relying on reporting.'
            : null;
    }

    private function boundedString($value, int $maxLength): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);
        $value = preg_replace('/[\x00-\x1F\x7F]/', '', $value) ?? '';

        return $value === '' ? null : substr($value, 0, max(1, $maxLength));
    }

    private function encodeJson(array $data): string
    {
        $json = json_encode($data, JSON_UNESCAPED_SLASHES);

        return is_string($json) ? $json : '{}';
    }

    private function decodeJson(string $json): array
    {
        $data = json_decode($json, true);

        return is_array($data) ? $data : [];
    }

    private function reconcileLimit(?int $requestedLimit): int
    {
        $configured = max(1, min(500, (int) config('fb_marketing.order_attribution.manual_reconcile_limit', 100)));

        return max(1, min($configured, $requestedLimit ?: $configured));
    }

    private function hmac(string $value): string
    {
        return hash_hmac('sha256', $value, (string) config('app.key', ''));
    }
}
