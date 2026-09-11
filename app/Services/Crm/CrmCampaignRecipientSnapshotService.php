<?php

namespace App\Services\Crm;

use App\Models\Crm\CrmCampaignDraft;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CrmCampaignRecipientSnapshotService
{
    public const RECIPIENT_SET_SIGNATURE_VERSION = 1;

    public const FROZEN_RECIPIENT_SET_SIGNATURE_VERSION = 1;

    public const SUPPORTED_CHANNELS = ['sms', 'email'];

    public function __construct(protected CrmCustomerSegmentWorklistService $customerSegmentService)
    {
    }


    /**
     * Decrypt one immutable Stage 22 destination in memory, normalize it using
     * the original snapshot rules, and recheck its keyed hash. Callers must
     * unset the returned plaintext in a finally block.
     */
    public function decryptAndVerifyDestination(string $channel, string $ciphertext, string $expectedHash): string
    {
        try {
            $plaintext = Crypt::decryptString($ciphertext);
        } catch (\Throwable $exception) {
            $this->throwValidation('destination', 'An encrypted campaign destination could not be verified safely.');
        }

        $normalized = $this->normalizeDestination($channel, $plaintext ?? null);
        unset($plaintext);
        if ($normalized === null) {
            $this->throwValidation('destination', 'A campaign destination could not be normalized safely.');
        }

        $actualHash = $this->destinationHash($channel, $normalized);
        $expectedHash = strtolower(trim($expectedHash));
        if (preg_match('/^[a-f0-9]{64}$/D', $expectedHash) !== 1 || !hash_equals($expectedHash, $actualHash)) {
            unset($normalized);
            $this->throwValidation('destination', 'A campaign destination failed immutable keyed-hash verification.');
        }

        return $normalized;
    }

    public function resolve(CrmCampaignDraft $draft, array $audienceSnapshot, bool $includeEncryptedDestinations = false): array
    {
        $channel = $this->normalizedChannel($draft->planned_channel);
        $destinationColumn = $this->destinationColumn($channel);
        if (!Schema::hasTable('customers') || !Schema::hasColumn('customers', 'id') || !Schema::hasColumn('customers', $destinationColumn)) {
            $this->throwValidation('recipient_snapshot', 'The required customer destination storage is unavailable for this campaign channel. Run application migrations or repair the customer schema first.');
        }

        $filters = $audienceSnapshot['filters'] ?? null;
        if (!is_array($filters)) {
            $this->throwValidation('recipient_snapshot', 'The verified campaign audience filters are unavailable.');
        }

        $rows = [];
        $seenCustomers = [];
        $seenDestinations = [];
        $normalizationFailures = 0;
        $duplicateCustomers = 0;
        $duplicateDestinations = 0;

        $query = $this->customerSegmentService->worklistQuery($filters);
        $query->setEagerLoads([]);
        $query->select(['id', $destinationColumn])->reorder('id');

        $query->chunkById(500, function ($customers) use (
            &$rows,
            &$seenCustomers,
            &$seenDestinations,
            &$normalizationFailures,
            &$duplicateCustomers,
            &$duplicateDestinations,
            $channel,
            $destinationColumn,
            $includeEncryptedDestinations
        ) {
            foreach ($customers as $customer) {
                $customerId = (int) $customer->id;
                if ($customerId <= 0 || isset($seenCustomers[$customerId])) {
                    $duplicateCustomers++;
                    continue;
                }
                $seenCustomers[$customerId] = true;

                $destination = $this->normalizeDestination($channel, $customer->{$destinationColumn} ?? null);
                if ($destination === null) {
                    $normalizationFailures++;
                    continue;
                }

                $destinationHash = $this->destinationHash($channel, $destination);
                if (isset($seenDestinations[$destinationHash])) {
                    $duplicateDestinations++;
                    continue;
                }
                $seenDestinations[$destinationHash] = true;

                $row = [
                    'customer_id' => $customerId,
                    'destination_hash' => $destinationHash,
                ];
                if ($includeEncryptedDestinations) {
                    $row['destination_ciphertext'] = Crypt::encryptString($destination);
                }
                $rows[] = $row;
            }
        }, 'id');

        if ($normalizationFailures > 0 || $duplicateCustomers > 0 || $duplicateDestinations > 0) {
            $parts = [];
            if ($normalizationFailures > 0) {
                $parts[] = number_format($normalizationFailures) . ' recipient destination(s) could not be normalized';
            }
            if ($duplicateCustomers > 0) {
                $parts[] = number_format($duplicateCustomers) . ' duplicate customer row(s) were detected';
            }
            if ($duplicateDestinations > 0) {
                $parts[] = number_format($duplicateDestinations) . ' duplicate normalized destination(s) were detected';
            }

            $this->throwValidation('recipient_snapshot', 'Campaign recipient preparation is blocked: ' . implode('; ', $parts) . '. Resolve the audience data, refresh the audience snapshot, and resubmit for independent approval.');
        }

        usort($rows, fn (array $left, array $right) => [$left['customer_id'], $left['destination_hash']] <=> [$right['customer_id'], $right['destination_hash']]);
        if (empty($rows)) {
            $this->throwValidation('recipient_snapshot', 'Campaign recipient preparation is blocked because the resolved normalized recipient set is empty.');
        }

        return [
            'channel' => $channel,
            'recipient_count' => count($rows),
            'signature' => $this->recipientSetSignature($draft, $audienceSnapshot, $channel, $rows),
            'signature_version' => self::RECIPIENT_SET_SIGNATURE_VERSION,
            'rows' => $rows,
            'destination_summary' => $this->destinationSummary($channel),
        ];
    }

    public function frozenRecipientSetSignature(int $draftId, int $preparationId, string $channel, array $rows): string
    {
        $payload = [
            'signature_version' => self::FROZEN_RECIPIENT_SET_SIGNATURE_VERSION,
            'crm_campaign_draft_id' => $draftId,
            'crm_campaign_dispatch_preparation_id' => $preparationId,
            'channel' => $this->signatureChannel($channel),
            'recipient_count' => count($rows),
            'recipients' => $this->identityRows($rows),
        ];

        return hash_hmac('sha256', $this->encoded($payload), $this->signatureKey());
    }

    public function destinationSummary(string $channel): string
    {
        return match (trim($channel)) {
            'email' => 'Email destinations are encrypted at rest and intentionally excluded from preview payloads.',
            'newsletter' => 'Historical newsletter email destinations remain encrypted at rest and excluded from preview payloads. New newsletter dispatch is disabled.',
            'whatsapp' => 'Historical WhatsApp phone destinations remain encrypted at rest and excluded from preview payloads. New WhatsApp dispatch is disabled.',
            'other' => 'Historical legacy destinations remain encrypted at rest and excluded from preview payloads. New legacy-channel dispatch is disabled.',
            default => 'Phone destinations are encrypted at rest and intentionally excluded from preview payloads.',
        };
    }

    protected function recipientSetSignature(CrmCampaignDraft $draft, array $audienceSnapshot, string $channel, array $rows): string
    {
        $payload = [
            'signature_version' => self::RECIPIENT_SET_SIGNATURE_VERSION,
            'crm_campaign_draft_id' => (int) $draft->id,
            'channel' => $channel,
            'audience_snapshot_signature' => (string) ($audienceSnapshot['signature'] ?? ''),
            'audience_snapshot_signature_version' => (int) ($audienceSnapshot['signature_version'] ?? 0),
            'recipient_count' => count($rows),
            'recipients' => $this->identityRows($rows),
        ];

        return hash_hmac('sha256', $this->encoded($payload), $this->signatureKey());
    }

    protected function identityRows(array $rows): array
    {
        return collect($rows)
            ->map(fn (array $row) => [
                'customer_id' => (int) ($row['customer_id'] ?? 0),
                'destination_hash' => strtolower((string) ($row['destination_hash'] ?? '')),
            ])
            ->sortBy(fn (array $row) => sprintf('%020d|%s', $row['customer_id'], $row['destination_hash']))
            ->values()
            ->all();
    }

    protected function normalizeDestination(string $channel, $value): ?string
    {
        return $channel === 'email'
            ? $this->normalizeEmail($value)
            : $this->normalizePhone($value);
    }

    /**
     * Reuse the canonical Stage 22 Email normalization rule for later
     * server-only CRM Email protocol validation.
     */
    public function normalizeEmailDestination($value): ?string
    {
        return $this->normalizeEmail($value);
    }

    protected function normalizeEmail($value): ?string
    {
        $value = strtolower(trim((string) $value));
        if ($value === '' || filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            return null;
        }

        return $value;
    }

    protected function normalizePhone($value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $value = preg_replace('/[\s\-().]/', '', $value);
        if (!is_string($value) || $value === '') {
            return null;
        }

        if (preg_match('/^01[3-9][0-9]{8}$/D', $value) === 1) {
            return '+880' . substr($value, 1);
        }
        if (preg_match('/^8801[3-9][0-9]{8}$/D', $value) === 1) {
            return '+' . $value;
        }
        if (preg_match('/^\+[1-9][0-9]{7,14}$/D', $value) === 1) {
            return $value;
        }

        return null;
    }

    protected function destinationColumn(string $channel): string
    {
        return $channel === 'email' ? 'email' : 'phone';
    }

    protected function destinationHash(string $channel, string $destination): string
    {
        return hash_hmac('sha256', $channel . '|' . $destination, $this->destinationHashKey());
    }

    protected function normalizedChannel($channel): string
    {
        $channel = trim((string) $channel);
        if (!in_array($channel, self::SUPPORTED_CHANNELS, true)) {
            $this->throwValidation('planned_channel', 'Campaign recipient snapshots support BulkSMSBD SMS or Email planning only. Newsletter, WhatsApp, and other legacy channels are disabled.');
        }

        return $channel;
    }

    /**
     * Preserve verification of historical Stage 22 signatures without
     * reopening legacy channels for new recipient resolution or dispatch.
     */
    protected function signatureChannel($channel): string
    {
        $channel = trim((string) $channel);
        if (!in_array($channel, ['sms', 'email', 'newsletter', 'whatsapp'], true)) {
            $this->throwValidation('planned_channel', 'Stored campaign recipient-snapshot channel metadata is unsupported.');
        }

        return $channel;
    }

    protected function destinationHashKey(): string
    {
        return hash('sha256', 'crm-campaign-destination-hash|' . $this->applicationKey(), true);
    }

    protected function signatureKey(): string
    {
        return hash('sha256', 'crm-campaign-recipient-set-signature|' . $this->applicationKey(), true);
    }

    protected function applicationKey(): string
    {
        $key = trim((string) config('app.key'));
        if ($key === '') {
            $this->throwValidation('recipient_snapshot', 'Application encryption key is unavailable. Dispatch preparation cannot continue safely.');
        }

        return $key;
    }

    protected function encoded(array $payload): string
    {
        return json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    protected function throwValidation(string $field, string $message): void
    {
        throw ValidationException::withMessages([$field => [$message]]);
    }
}
