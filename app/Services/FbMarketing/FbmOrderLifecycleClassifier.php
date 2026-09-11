<?php

namespace App\Services\FbMarketing;

use App\Models\ProductOrder;

class FbmOrderLifecycleClassifier
{
    public const STATE_PENDING = 'pending';
    public const STATE_CONFIRMED = 'confirmed';
    public const STATE_CANCELLED = 'cancelled';
    public const STATE_RETURNED = 'returned';
    public const STATE_UNKNOWN = 'unknown';

    public function classifyLegacy($status): string
    {
        if (!is_numeric($status)) {
            return self::STATE_UNKNOWN;
        }

        switch ((int) $status) {
            case 0:
                return self::STATE_PENDING;
            case 1:
            case 2:
            case 3:
            case 4:
                return self::STATE_CONFIRMED;
            case 5:
                return self::STATE_RETURNED;
            case 6:
                return self::STATE_CANCELLED;
            default:
                return self::STATE_UNKNOWN;
        }
    }

    public function classifyProductOrder(ProductOrder $order, int $activeReturnCount = 0): string
    {
        if ($activeReturnCount > 0 || (int) ($order->is_returned ?? 0) > 0) {
            return self::STATE_RETURNED;
        }

        if ((string) ($order->status ?? 'active') === 'inactive') {
            return self::STATE_CANCELLED;
        }

        $status = strtolower(trim((string) ($order->order_status ?? '')));
        if (in_array($status, ['pending', 'draft', 'quotation'], true)) {
            return self::STATE_PENDING;
        }
        if (in_array($status, [
            'accepted',
            'approved',
            'confirmed',
            'invoiced',
            'invoice',
            'processing',
            'packed',
            'couriered',
            'shipped',
            'delivered',
            'picked_up',
        ], true)) {
            return self::STATE_CONFIRMED;
        }
        if (in_array($status, ['cancelled', 'canceled', 'rejected', 'inactive'], true)) {
            return self::STATE_CANCELLED;
        }
        if (in_array($status, ['returned', 'partially_returned'], true)) {
            return self::STATE_RETURNED;
        }

        return self::STATE_UNKNOWN;
    }

    public function transitionEventType(?string $previousState, string $currentState): string
    {
        if ($previousState === null) {
            return 'captured';
        }
        if ($previousState === $currentState) {
            return 'status_observed';
        }

        switch ($currentState) {
            case self::STATE_CONFIRMED:
                return 'confirmed';
            case self::STATE_CANCELLED:
                return 'cancelled';
            case self::STATE_RETURNED:
                return 'returned';
            default:
                return 'status_changed';
        }
    }

    public function isConfirmed(string $state): bool
    {
        return $state === self::STATE_CONFIRMED;
    }
}
