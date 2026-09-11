<?php

namespace App\Services\Crm;

use App\Models\Crm\CrmCampaignDispatchAttempt;
use App\Models\Crm\CrmCampaignDispatchExecutionBatch;
use App\Models\Crm\CrmCampaignDispatchRecipientAttempt;
use App\Models\Crm\CrmCampaignDraft;
use App\Models\User;
use App\Services\Crm\CampaignDispatch\BulkSmsBdCampaignSmsProtocol;
use App\Services\Crm\CampaignDispatch\EmailCampaignSmtpProtocol;
use App\Services\RoleSidebarPermissionService;
use DateTimeInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CrmCampaignDispatchAttemptService
{
    public const STATUSES = ['prepared', 'processing', 'completed', 'partially_failed', 'failed', 'cancelled'];

    public const RECIPIENT_STATUSES = ['prepared'];

    public const PROVIDER_REQUEST_SNAPSHOT_VERSION = 1;

    public const ATTEMPT_INTEGRITY_SIGNATURE_VERSION = 1;

    protected const PERMISSION_KEY = 'crm.campaign-drafts.prepare-dispatch-attempt';

    public function __construct(
        protected CrmCampaignDraftService $campaignDraftService,
        protected CrmCampaignDispatchExecutionBatchService $executionBatchService,
        protected CrmCampaignDispatchProviderRegistry $providerRegistry,
        protected CrmActivityService $activityService,
        protected RoleSidebarPermissionService $permissionService
    ) {
    }

    public function preview(int $draftId, User $actor): array
    {
        $this->ensureStorage();
        $this->authorize($actor, 'read');
        $draft = $this->campaignDraftService->findVisible($draftId, $actor);
        $batch = $this->activeExecutionBatch((int) $draft->id);
        $eligibility = $this->prepareEligibility($draft, $batch, $actor);
        unset($eligibility['recipient_rows']);
        $attempt = $this->activeAttemptForDraft((int) $draft->id);
        $attemptPayload = $attempt ? $this->payload($attempt, $actor) : null;

        return array_merge($eligibility, [
            'active_execution_batch' => $batch ? $this->executionBatchPayload($batch) : null,
            'active_attempt' => $attemptPayload,
            'recipient_list_exposed' => false,
            'recipient_export_available' => false,
            'destination_sample_available' => false,
            'provider_payload_available' => false,
            'provider_response_available' => false,
            'provider_credentials_available' => false,
            'send_available' => (bool) ($attemptPayload['can_execute'] ?? false),
            'execute_available' => (bool) ($attemptPayload['can_execute'] ?? false),
            'schedule_available' => false,
            'queue_available' => false,
            'retry_available' => false,
            'remote_connectivity_checked' => false,
            'future_recipient_cap' => (int) ($eligibility['provider_readiness']['future_recipient_cap'] ?? 0),
            'future_connect_timeout_seconds' => (int) ($eligibility['provider_readiness']['future_connect_timeout_seconds'] ?? 0),
            'future_total_timeout_seconds' => (int) ($eligibility['provider_readiness']['future_total_timeout_seconds'] ?? 0),
            'single_recipient_requests_only' => true,
            'post_only' => true,
            'recipient_event_ledger_ready' => (bool) ($eligibility['provider_readiness']['recipient_event_ledger_ready'] ?? false),
            'read_only' => false,
            'notice' => $this->previewNotice((string) ($eligibility['channel'] ?? $draft->planned_channel)),
        ]);
    }

    public function history(int $draftId, User $actor): array
    {
        $this->ensureStorage();
        $this->authorize($actor, 'read');
        $draft = $this->campaignDraftService->findVisible($draftId, $actor);

        $query = CrmCampaignDispatchAttempt::query()
            ->where('crm_campaign_draft_id', $draft->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($this->hasUserStorage()) {
            $query->with(['preparer:id,name', 'starter:id,name', 'canceller:id,name']);
        }

        return $query->get()
            ->map(fn (CrmCampaignDispatchAttempt $attempt) => $this->payload($attempt, $actor))
            ->values()
            ->all();
    }

    public function prepare(int $draftId, int $batchId, User $actor): CrmCampaignDispatchAttempt
    {
        $this->ensureStorage();
        $this->authorize($actor, 'create');
        $this->campaignDraftService->findVisible($draftId, $actor);

        try {
            $attempt = DB::transaction(function () use ($draftId, $batchId, $actor) {
                $draft = CrmCampaignDraft::query()->lockForUpdate()->findOrFail($draftId);
                $this->ensureReadable($draft, $actor);

                $batch = CrmCampaignDispatchExecutionBatch::query()
                    ->where('crm_campaign_draft_id', $draft->id)
                    ->lockForUpdate()
                    ->findOrFail($batchId);

                $eligibility = $this->prepareEligibility($draft, $batch, $actor, true);
                $this->ensureReady($eligibility);
                $executionRows = $eligibility['recipient_rows'];
                $preparedAt = now();
                $attemptNumber = ((int) CrmCampaignDispatchAttempt::query()
                    ->where('crm_campaign_dispatch_execution_batch_id', $batch->id)
                    ->max('attempt_number')) + 1;
                $providerReadiness = $eligibility['provider_readiness'];
                $providerKey = (string) $providerReadiness['provider_key'];
                $transportExecutionAvailable = $this->isTransportExecutionAvailable((string) $batch->channel, $providerKey);
                $snapshot = $this->providerRequestSnapshot($draft, $batch, $providerKey, count($executionRows));
                $attemptIdempotencyKey = $this->attemptIdempotencyKey($batch, $attemptNumber, $providerKey);

                $attempt = CrmCampaignDispatchAttempt::create([
                    'product_website_id' => $batch->product_website_id,
                    'crm_campaign_draft_id' => $draft->id,
                    'crm_campaign_dispatch_preparation_id' => $batch->crm_campaign_dispatch_preparation_id,
                    'crm_campaign_dispatch_run_id' => $batch->crm_campaign_dispatch_run_id,
                    'crm_campaign_dispatch_execution_batch_id' => $batch->id,
                    'attempt_number' => $attemptNumber,
                    'status' => 'prepared',
                    'active_slot' => 1,
                    'channel' => $batch->channel,
                    'provider_key' => $providerKey,
                    'provider_request_snapshot_version' => self::PROVIDER_REQUEST_SNAPSHOT_VERSION,
                    'provider_request_snapshot_json' => $snapshot,
                    'recipient_count' => count($executionRows),
                    'attempt_idempotency_key' => $attemptIdempotencyKey,
                    'provider_request_count' => 0,
                    'provider_success_count' => 0,
                    'provider_failure_count' => 0,
                    'provider_unknown_count' => 0,
                    'prepared_by' => $actor->id,
                    'prepared_at' => $preparedAt,
                    'metadata_json' => [
                        'non_sending_foundation' => !$transportExecutionAvailable,
                        'local_configuration_only' => true,
                        'remote_connectivity_checked' => false,
                        'provider_call_available' => $transportExecutionAvailable,
                        'recipient_list_exposed' => false,
                        'recipient_export_available' => false,
                        'destination_sample_available' => false,
                        'provider_payload_available' => false,
                        'provider_response_available' => false,
                        'provider_credentials_available' => false,
                        'send_available' => $transportExecutionAvailable,
                        'execute_available' => $transportExecutionAvailable,
                        'execution_transport_available' => $transportExecutionAvailable,
                        'schedule_available' => false,
                        'queue_available' => false,
                        'retry_available' => false,
                        'recipient_event_ledger_foundation' => true,
                        'future_recipient_cap' => (int) ($providerReadiness['future_recipient_cap'] ?? 0),
                        'future_connect_timeout_seconds' => (int) ($providerReadiness['future_connect_timeout_seconds'] ?? 0),
                        'future_total_timeout_seconds' => (int) ($providerReadiness['future_total_timeout_seconds'] ?? 0),
                        'single_recipient_requests_only' => true,
                        'post_only' => $transportExecutionAvailable,
                        'attempt_preparer_is_campaign_creator' => (int) $draft->created_by === (int) $actor->id,
                        'attempt_preparer_is_campaign_approver' => (int) $draft->reviewed_by === (int) $actor->id,
                        'attempt_preparer_is_execution_claimant' => (int) $batch->claimed_by === (int) $actor->id,
                    ],
                ]);

                $rows = collect($executionRows)->map(fn (array $row) => [
                    'product_website_id' => $batch->product_website_id,
                    'crm_campaign_draft_id' => $draft->id,
                    'crm_campaign_dispatch_preparation_id' => $batch->crm_campaign_dispatch_preparation_id,
                    'crm_campaign_dispatch_run_id' => $batch->crm_campaign_dispatch_run_id,
                    'crm_campaign_dispatch_execution_batch_id' => $batch->id,
                    'crm_campaign_dispatch_execution_recipient_id' => $row['id'],
                    'crm_campaign_dispatch_attempt_id' => $attempt->id,
                    'customer_id' => $row['customer_id'],
                    'channel' => $batch->channel,
                    'provider_key' => $providerKey,
                    'status' => 'prepared',
                    'recipient_idempotency_key' => $this->recipientIdempotencyKey($attempt, $row),
                    'created_at' => $preparedAt,
                ])->all();

                foreach (array_chunk($rows, 500) as $chunk) {
                    DB::table('crm_campaign_dispatch_recipient_attempts')->insert($chunk);
                }

                $recipientRows = $this->attemptRows((int) $attempt->id, true);
                $attempt->forceFill([
                    'attempt_integrity_signature' => $this->attemptIntegritySignature($attempt, $recipientRows),
                    'attempt_integrity_signature_version' => self::ATTEMPT_INTEGRITY_SIGNATURE_VERSION,
                ])->save();

                $this->recordActivity('crm_campaign_dispatch_attempt_prepared', $attempt, $actor->id, 'CRM campaign manual provider-attempt ledger prepared', [
                    'crm_campaign_dispatch_execution_batch_id' => (int) $batch->id,
                    'recipient_count' => (int) $attempt->recipient_count,
                    'provider_key' => $providerKey,
                    'non_sending_foundation' => !$transportExecutionAvailable,
                    'local_configuration_only' => true,
                    'remote_connectivity_checked' => false,
                    'attempt_preparer_is_campaign_creator' => (bool) ($attempt->metadata_json['attempt_preparer_is_campaign_creator'] ?? false),
                    'attempt_preparer_is_campaign_approver' => (bool) ($attempt->metadata_json['attempt_preparer_is_campaign_approver'] ?? false),
                    'attempt_preparer_is_execution_claimant' => (bool) ($attempt->metadata_json['attempt_preparer_is_execution_claimant'] ?? false),
                ]);

                return $attempt;
            });
        } catch (\Throwable $exception) {
            $this->logFailure('prepare', $draftId, $batchId, null, $actor->id, $exception);
            if ($this->isUniqueConstraintViolation($exception)) {
                $this->throwValidation('dispatch_attempt', 'This execution batch already has an active manual attempt ledger or the immutable attempt identity has already been consumed. Refresh the aggregate preview.');
            }
            throw $exception;
        }

        return $this->freshWithRelations($attempt);
    }

    /**
     * Reverify the complete immutable Stage 20-25 chain for one active prepared
     * attempt before Stage 27 execution starts. Mutable provider counters are
     * intentionally outside the sealed recipient identity.
     */
    public function assertPreparedAttemptIntegrity(CrmCampaignDispatchAttempt $attempt, bool $lockRows = false): array
    {
        $this->ensureStorage();
        if ($attempt->status !== 'prepared' || (int) $attempt->active_slot !== 1) {
            $this->throwValidation('dispatch_attempt', 'Only an active prepared manual provider attempt can start real SMS execution.');
        }

        $batchQuery = CrmCampaignDispatchExecutionBatch::query()
            ->where('crm_campaign_draft_id', $attempt->crm_campaign_draft_id);
        if ($lockRows) {
            $batchQuery->lockForUpdate();
        }
        $batch = $batchQuery->find($attempt->crm_campaign_dispatch_execution_batch_id);
        if (!$batch) {
            $this->throwValidation('dispatch_attempt', 'The manual provider attempt no longer references its Stage 24 execution batch.');
        }

        $executionRows = $this->executionBatchService->assertPreparedBatchIntegrity($batch, $lockRows);
        $this->assertAttemptIntegrity($attempt, $lockRows);
        $attemptRows = $this->attemptRows((int) $attempt->id, $lockRows);
        $executionById = collect($executionRows)->keyBy('id');
        foreach ($attemptRows as $row) {
            $source = $executionById->get($row['crm_campaign_dispatch_execution_recipient_id']);
            if (!$source
                || (int) $source['customer_id'] !== (int) $row['customer_id']
                || !hash_equals((string) $source['channel'], (string) $row['channel'])
                || !$this->sameNullableInteger($source['product_website_id'], $row['product_website_id'])) {
                $this->throwValidation('dispatch_attempt', 'A manual provider-attempt recipient no longer matches its immutable Stage 24 source identity.');
            }
        }

        return $attemptRows;
    }

    public function cancel(int $draftId, int $attemptId, array $data, User $actor): CrmCampaignDispatchAttempt
    {
        $this->ensureStorage();
        $this->authorize($actor, 'update');
        $this->campaignDraftService->findVisible($draftId, $actor);
        $reason = trim((string) ($data['reason'] ?? ''));
        if ($reason === '') {
            $this->throwValidation('reason', 'A reason is required.');
        }
        if ($this->stringLength($reason) > 1000) {
            $this->throwValidation('reason', 'The reason may not be greater than 1000 characters.');
        }

        try {
            $attempt = DB::transaction(function () use ($draftId, $attemptId, $reason, $actor) {
                $draft = CrmCampaignDraft::query()->lockForUpdate()->findOrFail($draftId);
                $this->ensureReadable($draft, $actor);

                $attempt = CrmCampaignDispatchAttempt::query()
                    ->where('crm_campaign_draft_id', $draft->id)
                    ->lockForUpdate()
                    ->findOrFail($attemptId);

                if ($attempt->status !== 'prepared' || (int) $attempt->active_slot !== 1) {
                    $this->throwValidation('dispatch_attempt', 'Only an active prepared non-sending manual attempt ledger can be cancelled.');
                }

                $this->assertAttemptIntegrity($attempt, true);

                $attempt->forceFill([
                    'status' => 'cancelled',
                    'active_slot' => null,
                    'cancelled_by' => $actor->id,
                    'cancelled_at' => now(),
                    'cancellation_reason' => $reason,
                ])->save();
                $attempt = $attempt->refresh();

                $this->recordActivity('crm_campaign_dispatch_attempt_cancelled', $attempt, $actor->id, 'CRM campaign manual provider-attempt ledger cancelled', [
                    'reason_present' => true,
                    'non_sending_foundation' => true,
                    'provider_call_occurred' => false,
                    'execution_batch_remains_prepared' => true,
                ]);

                return $attempt;
            });
        } catch (\Throwable $exception) {
            $this->logFailure('cancel', $draftId, null, $attemptId, $actor->id, $exception);
            throw $exception;
        }

        return $this->freshWithRelations($attempt);
    }

    public function payload(CrmCampaignDispatchAttempt $attempt, User $actor): array
    {
        $canCancel = $attempt->status === 'prepared'
            && (int) $attempt->active_slot === 1
            && $this->can($actor, 'update');
        $canExecute = $attempt->status === 'prepared'
            && (int) $attempt->active_slot === 1
            && $attempt->channel === 'sms'
            && $attempt->provider_key === 'bulksmsbd'
            && (int) $attempt->recipient_count > 0
            && (int) $attempt->recipient_count <= BulkSmsBdCampaignSmsProtocol::MAX_RECIPIENTS
            && $this->permissionService->userCan($actor, 'crm.campaign-drafts.execute-dispatch', 'create');

        return [
            'id' => (int) $attempt->id,
            'crm_campaign_draft_id' => (int) $attempt->crm_campaign_draft_id,
            'crm_campaign_dispatch_preparation_id' => (int) $attempt->crm_campaign_dispatch_preparation_id,
            'crm_campaign_dispatch_run_id' => (int) $attempt->crm_campaign_dispatch_run_id,
            'crm_campaign_dispatch_execution_batch_id' => (int) $attempt->crm_campaign_dispatch_execution_batch_id,
            'attempt_number' => (int) $attempt->attempt_number,
            'status' => $attempt->status,
            'channel' => $attempt->channel,
            'provider_key' => $attempt->provider_key,
            'recipient_count' => (int) $attempt->recipient_count,
            'attempt_integrity_seal_recorded' => $this->isValidSignature($attempt->attempt_integrity_signature)
                && (int) $attempt->attempt_integrity_signature_version === self::ATTEMPT_INTEGRITY_SIGNATURE_VERSION,
            'provider_request_count' => (int) $attempt->provider_request_count,
            'provider_success_count' => (int) $attempt->provider_success_count,
            'provider_failure_count' => (int) $attempt->provider_failure_count,
            'provider_unknown_count' => (int) $attempt->provider_unknown_count,
            'prepared_by' => $attempt->prepared_by ? (int) $attempt->prepared_by : null,
            'prepared_by_name' => $attempt->relationLoaded('preparer') ? optional($attempt->preparer)->name : null,
            'prepared_at' => optional($attempt->prepared_at)->format('Y-m-d h:i a'),
            'started_by_name' => $attempt->relationLoaded('starter') ? optional($attempt->starter)->name : null,
            'started_at' => optional($attempt->started_at)->format('Y-m-d h:i a'),
            'completed_at' => optional($attempt->completed_at)->format('Y-m-d h:i a'),
            'failed_at' => optional($attempt->failed_at)->format('Y-m-d h:i a'),
            'failure_summary' => $attempt->failure_summary,
            'cancelled_by_name' => $attempt->relationLoaded('canceller') ? optional($attempt->canceller)->name : null,
            'cancelled_at' => optional($attempt->cancelled_at)->format('Y-m-d h:i a'),
            'cancellation_reason' => $attempt->cancellation_reason,
            'non_sending_foundation' => !$canExecute,
            'local_configuration_only' => true,
            'remote_connectivity_checked' => (int) $attempt->provider_request_count > 0,
            'recipient_list_exposed' => false,
            'recipient_export_available' => false,
            'destination_sample_available' => false,
            'provider_payload_available' => false,
            'provider_response_available' => false,
            'provider_credentials_available' => false,
            'send_available' => $canExecute,
            'execute_available' => $canExecute,
            'schedule_available' => false,
            'queue_available' => false,
            'retry_available' => false,
            'recipient_event_ledger_foundation' => true,
            'future_recipient_cap' => (int) (($attempt->metadata_json ?? [])['future_recipient_cap'] ?? 0),
            'single_recipient_requests_only' => true,
            'post_only' => $canExecute,
            'execution_transport_available' => $canExecute,
            'can_cancel' => $canCancel,
            'can_execute' => $canExecute,
            'maximum_allowed_recipients' => $this->maximumAllowedRecipients((string) $attempt->channel),
            'created_at' => optional($attempt->created_at)->format('Y-m-d h:i a'),
        ];
    }

    protected function prepareEligibility(CrmCampaignDraft $draft, ?CrmCampaignDispatchExecutionBatch $batch, User $actor, bool $lockRows = false): array
    {
        $checks = [];
        $addCheck = function (string $key, string $label, bool $passed, string $message) use (&$checks): void {
            $checks[] = compact('key', 'label', 'passed', 'message');
        };

        $approved = $draft->status === 'approved';
        $addCheck('approved_lifecycle', 'Approved lifecycle', $approved, $approved ? 'Campaign lifecycle is approved.' : 'Only an approved campaign can prepare a manual provider-attempt ledger.');

        $ledgerChannel = CrmCampaignDraftService::isDispatchLedgerChannel($draft->planned_channel)
            && $batch !== null
            && CrmCampaignDraftService::isDispatchLedgerChannel($batch->channel);
        $addCheck('stage31_ledger_channel', 'Stage 31 dispatch-ledger channel boundary', $ledgerChannel, $ledgerChannel ? 'BulkSMSBD SMS and Email may prepare immutable provider-attempt ledgers. Email remains non-sending.' : 'Provider-attempt preparation is disabled for newsletter, WhatsApp, and other legacy campaign channels.');

        $activeBatch = $batch !== null && $batch->status === 'prepared' && (int) $batch->active_slot === 1;
        $addCheck('active_execution_batch', 'Active Stage 24 execution batch', $activeBatch, $activeBatch ? 'An active prepared Stage 24 execution batch is available.' : 'A prepared active Stage 24 execution batch is required.');

        $separated = $batch !== null && (int) $batch->claimed_by > 0 && (int) $batch->claimed_by !== (int) $actor->id;
        $addCheck('claim_attempt_separation', 'Claim-to-attempt responsibility separation', $separated, $separated ? 'The attempt preparer differs from the Stage 24 execution claimant.' : 'The Stage 24 execution claimant cannot prepare the provider-attempt ledger. Assign a separate authorized user.');

        $recipientRows = [];
        $executionIntegrity = false;
        $executionIntegrityMessage = 'Stage 24 execution-batch integrity could not be verified.';
        if ($batch !== null) {
            try {
                $recipientRows = $this->executionBatchService->assertPreparedBatchIntegrity($batch, $lockRows);
                $executionIntegrity = true;
                $executionIntegrityMessage = number_format(count($recipientRows)) . ' immutable Stage 24 execution-recipient rows passed full lineage verification.';
            } catch (ValidationException $exception) {
                $executionIntegrityMessage = $this->firstValidationMessage($exception);
            }
        }
        $addCheck('execution_batch_integrity', 'Stage 24 immutable lineage and integrity', $executionIntegrity, $executionIntegrityMessage);

        $recipientCount = $batch && is_numeric($batch->frozen_recipient_count) ? (int) $batch->frozen_recipient_count : 0;
        $maximumAllowedRecipients = $this->maximumAllowedRecipients((string) ($batch?->channel ?: $draft->planned_channel));
        $withinRecipientCap = $recipientCount > 0 && $recipientCount <= $maximumAllowedRecipients;
        $addCheck(
            'bounded_recipient_cap',
            'Stage 31 bounded recipient cap',
            $withinRecipientCap,
            $withinRecipientCap
                ? 'The immutable attempt is bounded to ' . $recipientCount . ' recipient(s), within the server-side maximum of ' . $maximumAllowedRecipients . ' for this channel.'
                : 'Stage 31 attempts must contain between 1 and ' . $maximumAllowedRecipients . ' recipient(s). No truncation or partial cap execution is allowed.'
        );

        $providerReadiness = $this->providerRegistry->readiness((string) ($batch?->channel ?: $draft->planned_channel), $batch?->product_website_id ?? $draft->product_website_id);
        $addCheck('provider_readiness', 'Local provider readiness', !empty($providerReadiness['ready']), (string) ($providerReadiness['message'] ?? 'Provider readiness could not be verified.'));

        $activeAttempt = $batch ? $this->activeAttemptForBatch((int) $batch->id) : null;
        $addCheck('no_active_attempt', 'Single active manual attempt', $activeAttempt === null, $activeAttempt === null ? 'No active manual provider-attempt ledger exists for this execution batch.' : 'Cancel the active manual provider-attempt ledger before preparing another one.');

        $executionAlreadyStarted = $batch ? $this->batchExecutionStarted((int) $batch->id) : false;
        $addCheck('execution_not_previously_started', 'Permanent execution-consumption guard', !$executionAlreadyStarted, !$executionAlreadyStarted ? 'This Stage 24 execution batch has never started a provider request.' : 'This Stage 24 execution batch has already entered real execution and cannot prepare a fresh attempt.');

        $ready = collect($checks)->every(fn (array $check) => $check['passed']);

        return [
            'draft_id' => (int) $draft->id,
            'draft_name' => $draft->name,
            'status' => $draft->status,
            'channel' => $batch?->channel ?: $draft->planned_channel,
            'execution_batch_id' => $batch?->id ? (int) $batch->id : null,
            'recipient_count' => $batch && is_numeric($batch->frozen_recipient_count) ? (int) $batch->frozen_recipient_count : null,
            'maximum_allowed_recipients' => $maximumAllowedRecipients,
            'ready_for_attempt_preparation' => $ready,
            'ready_for_attempt_preparation_label' => $ready ? 'Eligible for bounded manual provider-attempt preparation' : 'Not eligible for manual provider-attempt preparation',
            'checks' => $checks,
            'provider_readiness' => $providerReadiness,
            'recipient_rows' => $recipientRows,
        ];
    }

    protected function maximumAllowedRecipients(string $channel): int
    {
        return strtolower(trim($channel)) === 'email'
            ? EmailCampaignSmtpProtocol::MAX_RECIPIENTS
            : BulkSmsBdCampaignSmsProtocol::MAX_RECIPIENTS;
    }

    protected function isTransportExecutionAvailable(string $channel, string $providerKey): bool
    {
        return strtolower(trim($channel)) === 'sms'
            && strtolower(trim($providerKey)) === 'bulksmsbd';
    }

    protected function providerRequestSnapshot(CrmCampaignDraft $draft, CrmCampaignDispatchExecutionBatch $batch, string $providerKey, int $recipientCount): array
    {
        return [
            'snapshot_version' => self::PROVIDER_REQUEST_SNAPSHOT_VERSION,
            'crm_campaign_draft_id' => (int) $draft->id,
            'crm_campaign_dispatch_execution_batch_id' => (int) $batch->id,
            'channel' => (string) $batch->channel,
            'provider_key' => $providerKey,
            'recipient_count' => $recipientCount,
            'manual_non_sending_mode' => !$this->isTransportExecutionAvailable((string) $batch->channel, $providerKey),
            'provider_call_available' => $this->isTransportExecutionAvailable((string) $batch->channel, $providerKey),
            'recipient_event_ledger_foundation' => true,
            'subject_present' => trim((string) $batch->subject_snapshot) !== '',
            'message_body_sha256' => hash('sha256', (string) $batch->message_body_snapshot),
        ];
    }

    protected function assertAttemptIntegrity(CrmCampaignDispatchAttempt $attempt, bool $lockRows): void
    {
        if (!$this->isValidSignature($attempt->attempt_integrity_signature)
            || (int) $attempt->attempt_integrity_signature_version !== self::ATTEMPT_INTEGRITY_SIGNATURE_VERSION
            || (int) $attempt->provider_request_snapshot_version !== self::PROVIDER_REQUEST_SNAPSHOT_VERSION) {
            $this->throwValidation('dispatch_attempt', 'Manual dispatch-attempt integrity metadata is missing or unsupported.');
        }

        $rows = $this->attemptRows((int) $attempt->id, $lockRows);
        if (count($rows) !== (int) $attempt->recipient_count || (int) $attempt->recipient_count <= 0) {
            $this->throwValidation('dispatch_attempt', 'Manual dispatch-attempt recipient-row count no longer matches the recorded header count.');
        }

        $seenExecutionRecipients = [];
        $seenCustomers = [];
        foreach ($rows as $row) {
            $expectedIdempotencyKey = $this->recipientIdempotencyKey($attempt, $row);
            if ($row['id'] <= 0
                || !$this->sameNullableInteger($row['product_website_id'], $attempt->product_website_id)
                || $row['crm_campaign_draft_id'] !== (int) $attempt->crm_campaign_draft_id
                || $row['crm_campaign_dispatch_preparation_id'] !== (int) $attempt->crm_campaign_dispatch_preparation_id
                || $row['crm_campaign_dispatch_run_id'] !== (int) $attempt->crm_campaign_dispatch_run_id
                || $row['crm_campaign_dispatch_execution_batch_id'] !== (int) $attempt->crm_campaign_dispatch_execution_batch_id
                || $row['crm_campaign_dispatch_execution_recipient_id'] <= 0
                || $row['crm_campaign_dispatch_attempt_id'] !== (int) $attempt->id
                || $row['customer_id'] <= 0
                || !hash_equals((string) $attempt->channel, $row['channel'])
                || !hash_equals((string) $attempt->provider_key, $row['provider_key'])
                || $row['status'] !== 'prepared'
                || !$this->sameSignature($expectedIdempotencyKey, $row['recipient_idempotency_key'])
                || isset($seenExecutionRecipients[$row['crm_campaign_dispatch_execution_recipient_id']])
                || isset($seenCustomers[$row['customer_id']])) {
                $this->throwValidation('dispatch_attempt', 'Manual dispatch-attempt recipient rows failed identity, channel, provider, duplicate, or idempotency verification.');
            }
            $seenExecutionRecipients[$row['crm_campaign_dispatch_execution_recipient_id']] = true;
            $seenCustomers[$row['customer_id']] = true;
        }

        if (!hash_equals(strtolower((string) $attempt->attempt_integrity_signature), $this->attemptIntegritySignature($attempt, $rows))) {
            $this->throwValidation('dispatch_attempt', 'Manual dispatch-attempt recipient rows failed integrity-seal verification.');
        }
    }

    protected function attemptRows(int $attemptId, bool $lockRows): array
    {
        $query = CrmCampaignDispatchRecipientAttempt::query()
            ->where('crm_campaign_dispatch_attempt_id', $attemptId)
            ->orderBy('id');
        if ($lockRows) {
            $query->lockForUpdate();
        }

        return $query->get([
            'id', 'product_website_id', 'crm_campaign_draft_id', 'crm_campaign_dispatch_preparation_id',
            'crm_campaign_dispatch_run_id', 'crm_campaign_dispatch_execution_batch_id',
            'crm_campaign_dispatch_execution_recipient_id', 'crm_campaign_dispatch_attempt_id', 'customer_id',
            'channel', 'provider_key', 'status', 'recipient_idempotency_key',
        ])->map(fn (CrmCampaignDispatchRecipientAttempt $recipient) => [
            'id' => (int) $recipient->id,
            'product_website_id' => $recipient->product_website_id,
            'crm_campaign_draft_id' => (int) $recipient->crm_campaign_draft_id,
            'crm_campaign_dispatch_preparation_id' => (int) $recipient->crm_campaign_dispatch_preparation_id,
            'crm_campaign_dispatch_run_id' => (int) $recipient->crm_campaign_dispatch_run_id,
            'crm_campaign_dispatch_execution_batch_id' => (int) $recipient->crm_campaign_dispatch_execution_batch_id,
            'crm_campaign_dispatch_execution_recipient_id' => (int) $recipient->crm_campaign_dispatch_execution_recipient_id,
            'crm_campaign_dispatch_attempt_id' => (int) $recipient->crm_campaign_dispatch_attempt_id,
            'customer_id' => (int) $recipient->customer_id,
            'channel' => (string) $recipient->channel,
            'provider_key' => (string) $recipient->provider_key,
            'status' => (string) $recipient->status,
            'recipient_idempotency_key' => strtolower(trim((string) $recipient->recipient_idempotency_key)),
        ])->all();
    }

    protected function attemptIntegritySignature(CrmCampaignDispatchAttempt $attempt, array $rows): string
    {
        $payload = [
            'signature_version' => self::ATTEMPT_INTEGRITY_SIGNATURE_VERSION,
            'provider_request_snapshot_version' => (int) $attempt->provider_request_snapshot_version,
            'provider_request_snapshot_json' => $attempt->provider_request_snapshot_json ?: [],
            'crm_campaign_dispatch_attempt_id' => (int) $attempt->id,
            'product_website_id' => $attempt->product_website_id === null ? null : (int) $attempt->product_website_id,
            'crm_campaign_draft_id' => (int) $attempt->crm_campaign_draft_id,
            'crm_campaign_dispatch_preparation_id' => (int) $attempt->crm_campaign_dispatch_preparation_id,
            'crm_campaign_dispatch_run_id' => (int) $attempt->crm_campaign_dispatch_run_id,
            'crm_campaign_dispatch_execution_batch_id' => (int) $attempt->crm_campaign_dispatch_execution_batch_id,
            'attempt_number' => (int) $attempt->attempt_number,
            'channel' => (string) $attempt->channel,
            'provider_key' => (string) $attempt->provider_key,
            'recipient_count' => (int) $attempt->recipient_count,
            'attempt_idempotency_key' => strtolower((string) $attempt->attempt_idempotency_key),
            'prepared_by' => $attempt->prepared_by === null ? null : (int) $attempt->prepared_by,
            'prepared_at' => $this->timestampString($attempt->prepared_at),
            'recipients' => collect($rows)->map(fn (array $row) => [
                'crm_campaign_dispatch_recipient_attempt_id' => (int) $row['id'],
                'product_website_id' => $row['product_website_id'] === null ? null : (int) $row['product_website_id'],
                'crm_campaign_draft_id' => (int) $row['crm_campaign_draft_id'],
                'crm_campaign_dispatch_preparation_id' => (int) $row['crm_campaign_dispatch_preparation_id'],
                'crm_campaign_dispatch_run_id' => (int) $row['crm_campaign_dispatch_run_id'],
                'crm_campaign_dispatch_execution_batch_id' => (int) $row['crm_campaign_dispatch_execution_batch_id'],
                'crm_campaign_dispatch_execution_recipient_id' => (int) $row['crm_campaign_dispatch_execution_recipient_id'],
                'crm_campaign_dispatch_attempt_id' => (int) $row['crm_campaign_dispatch_attempt_id'],
                'customer_id' => (int) $row['customer_id'],
                'channel' => (string) $row['channel'],
                'provider_key' => (string) $row['provider_key'],
                'status' => (string) $row['status'],
                'recipient_idempotency_key' => strtolower((string) $row['recipient_idempotency_key']),
            ])->sortBy(fn (array $row) => sprintf('%020d|%020d|%020d', $row['crm_campaign_dispatch_recipient_attempt_id'], $row['crm_campaign_dispatch_execution_recipient_id'], $row['customer_id']))->values()->all(),
        ];

        return hash_hmac('sha256', $this->encoded($payload), $this->attemptSignatureKey());
    }

    protected function attemptIdempotencyKey(CrmCampaignDispatchExecutionBatch $batch, int $attemptNumber, string $providerKey): string
    {
        return hash_hmac('sha256', $this->encoded([
            'scope' => 'crm_campaign_dispatch_attempt',
            'product_website_id' => $batch->product_website_id === null ? null : (int) $batch->product_website_id,
            'crm_campaign_draft_id' => (int) $batch->crm_campaign_draft_id,
            'crm_campaign_dispatch_execution_batch_id' => (int) $batch->id,
            'attempt_number' => $attemptNumber,
            'channel' => (string) $batch->channel,
            'provider_key' => $providerKey,
            'execution_integrity_signature' => strtolower((string) $batch->execution_integrity_signature),
        ]), $this->attemptSignatureKey());
    }

    protected function recipientIdempotencyKey(CrmCampaignDispatchAttempt $attempt, array $row): string
    {
        return hash_hmac('sha256', $this->encoded([
            'scope' => 'crm_campaign_dispatch_recipient_attempt',
            'crm_campaign_dispatch_attempt_id' => (int) $attempt->id,
            'crm_campaign_dispatch_execution_recipient_id' => (int) ($row['crm_campaign_dispatch_execution_recipient_id'] ?? $row['id'] ?? 0),
            'customer_id' => (int) ($row['customer_id'] ?? 0),
            'channel' => (string) $attempt->channel,
            'provider_key' => (string) $attempt->provider_key,
        ]), $this->attemptSignatureKey());
    }

    protected function executionBatchPayload(CrmCampaignDispatchExecutionBatch $batch): array
    {
        return [
            'id' => (int) $batch->id,
            'crm_campaign_draft_id' => (int) $batch->crm_campaign_draft_id,
            'crm_campaign_dispatch_run_id' => (int) $batch->crm_campaign_dispatch_run_id,
            'status' => $batch->status,
            'channel' => $batch->channel,
            'recipient_count' => (int) $batch->frozen_recipient_count,
            'claimed_by' => $batch->claimed_by ? (int) $batch->claimed_by : null,
            'claimed_at' => optional($batch->claimed_at)->format('Y-m-d h:i a'),
            'recipient_list_exposed' => false,
            'recipient_export_available' => false,
            'provider_payload_available' => false,
        ];
    }

    protected function activeExecutionBatch(int $draftId): ?CrmCampaignDispatchExecutionBatch
    {
        return CrmCampaignDispatchExecutionBatch::query()
            ->where('crm_campaign_draft_id', $draftId)
            ->where('status', 'prepared')
            ->where('active_slot', 1)
            ->first();
    }

    protected function batchExecutionStarted(int $batchId): bool
    {
        if (CrmCampaignDispatchAttempt::query()
            ->where('crm_campaign_dispatch_execution_batch_id', $batchId)
            ->whereNotNull('started_at')
            ->exists()) {
            return true;
        }

        return DB::table('crm_campaign_dispatch_recipient_attempt_events')
            ->where('crm_campaign_dispatch_execution_batch_id', $batchId)
            ->where('event_type', 'request_started')
            ->exists();
    }

    protected function activeAttemptForBatch(int $batchId): ?CrmCampaignDispatchAttempt
    {
        return CrmCampaignDispatchAttempt::query()
            ->where('crm_campaign_dispatch_execution_batch_id', $batchId)
            ->where('status', 'prepared')
            ->where('active_slot', 1)
            ->first();
    }

    protected function activeAttemptForDraft(int $draftId): ?CrmCampaignDispatchAttempt
    {
        $query = CrmCampaignDispatchAttempt::query()
            ->where('crm_campaign_draft_id', $draftId)
            ->whereIn('status', ['prepared', 'processing'])
            ->where('active_slot', 1);
        if ($this->hasUserStorage()) {
            $query->with(['preparer:id,name', 'starter:id,name', 'canceller:id,name']);
        }

        return $query->first();
    }

    protected function freshWithRelations(CrmCampaignDispatchAttempt $attempt): CrmCampaignDispatchAttempt
    {
        $attempt = $attempt->fresh();
        if ($this->hasUserStorage()) {
            $attempt->load(['preparer:id,name', 'starter:id,name', 'canceller:id,name']);
        }

        return $attempt;
    }

    protected function ensureReady(array $eligibility): void
    {
        if (!empty($eligibility['ready_for_attempt_preparation'])) {
            return;
        }

        $failed = collect($eligibility['checks'] ?? [])
            ->filter(fn (array $check) => empty($check['passed']))
            ->pluck('label')
            ->filter()
            ->implode(', ');

        $this->throwValidation('dispatch_attempt', 'Manual provider-attempt preparation failed verification' . ($failed !== '' ? ': ' . $failed . '.' : '.'));
    }

    protected function ensureReadable(CrmCampaignDraft $draft, User $actor): void
    {
        if ($draft->visibility !== 'shared' && (int) $draft->created_by !== (int) $actor->id) {
            throw new AuthorizationException('You cannot access this CRM campaign draft.');
        }
    }

    protected function authorize(User $actor, string $action): void
    {
        if (!$this->permissionService->userCan($actor, self::PERMISSION_KEY, $action)) {
            throw new AuthorizationException('You do not have permission to perform this CRM campaign manual provider-attempt action.');
        }
    }

    protected function can(User $actor, string $action): bool
    {
        return $this->permissionService->userCan($actor, self::PERMISSION_KEY, $action);
    }

    protected function recordActivity(string $type, CrmCampaignDispatchAttempt $attempt, int $actorId, string $subject, array $extraMetadata = []): void
    {
        $this->activityService->recordOrFail([
            'product_website_id' => $attempt->product_website_id,
            'activity_type' => $type,
            'subject' => $subject,
            'description' => $subject . ' for CRM campaign draft #' . $attempt->crm_campaign_draft_id,
            'source_module' => 'crm_campaign_dispatch_attempts',
            'source_id' => $attempt->id,
            'performed_by' => $actorId,
            'metadata' => array_merge([
                'crm_campaign_draft_id' => (int) $attempt->crm_campaign_draft_id,
                'crm_campaign_dispatch_preparation_id' => (int) $attempt->crm_campaign_dispatch_preparation_id,
                'crm_campaign_dispatch_run_id' => (int) $attempt->crm_campaign_dispatch_run_id,
                'crm_campaign_dispatch_execution_batch_id' => (int) $attempt->crm_campaign_dispatch_execution_batch_id,
                'attempt_number' => (int) $attempt->attempt_number,
                'status' => $attempt->status,
                'channel' => $attempt->channel,
                'provider_key' => $attempt->provider_key,
                'recipient_count' => (int) $attempt->recipient_count,
                'recipient_list_exposed' => false,
                'recipient_export_available' => false,
                'destination_sample_available' => false,
                'provider_payload_available' => false,
                'provider_response_available' => false,
                'provider_credentials_available' => false,
                'send_available' => false,
                'execute_available' => false,
                'schedule_available' => false,
                'queue_available' => false,
                'retry_available' => false,
                'recipient_event_ledger_foundation' => true,
                'single_recipient_requests_only' => true,
                'post_only' => true,
            ], $extraMetadata),
        ]);
    }

    protected function previewNotice(string $channel): string
    {
        if (strtolower(trim($channel)) === 'email') {
            return 'Stage 31 permits immutable Email provider-attempt ledger preparation only. Real SMTP transport execution, destination decryption for sending, remote connectivity probes, automatic retry, queueing, scheduling, and browser resend remain disabled. SMTP credentials and recipient destinations remain server-only.';
        }

        return 'Stage 27 enables one tightly controlled real SMS execution path only: BulkSMSBD, one recipient per HTTPS POST, maximum five recipients, explicit typed operator confirmation, no automatic retry, and aggregate-only browser results. Destinations, credentials, endpoint URLs, raw requests, and raw responses remain server-only.';
    }

    protected function ensureStorage(): void
    {
        $tables = [
            'crm_campaign_dispatch_execution_batches' => [
                'id', 'product_website_id', 'crm_campaign_draft_id', 'crm_campaign_dispatch_preparation_id',
                'crm_campaign_dispatch_run_id', 'status', 'active_slot', 'channel', 'subject_snapshot',
                'message_body_snapshot', 'execution_integrity_signature', 'execution_integrity_signature_version',
                'frozen_recipient_count', 'claimed_by', 'claimed_at',
            ],
            'crm_campaign_dispatch_execution_recipients' => [
                'id', 'product_website_id', 'crm_campaign_draft_id', 'crm_campaign_dispatch_preparation_id',
                'crm_campaign_dispatch_run_id', 'crm_campaign_dispatch_execution_batch_id', 'customer_id',
                'channel', 'status',
            ],
            'crm_campaign_dispatch_attempts' => [
                'id', 'product_website_id', 'crm_campaign_draft_id', 'crm_campaign_dispatch_preparation_id',
                'crm_campaign_dispatch_run_id', 'crm_campaign_dispatch_execution_batch_id', 'attempt_number',
                'status', 'active_slot', 'channel', 'provider_key', 'provider_request_snapshot_version',
                'provider_request_snapshot_json', 'recipient_count', 'attempt_idempotency_key',
                'attempt_integrity_signature', 'attempt_integrity_signature_version', 'provider_request_count',
                'provider_success_count', 'provider_failure_count', 'provider_unknown_count', 'prepared_by',
                'prepared_at', 'started_by', 'started_at', 'completed_at', 'failed_at', 'failure_summary',
                'cancelled_by', 'cancelled_at', 'cancellation_reason', 'metadata_json', 'created_at', 'updated_at',
            ],
            'crm_campaign_dispatch_recipient_attempts' => [
                'id', 'product_website_id', 'crm_campaign_draft_id', 'crm_campaign_dispatch_preparation_id',
                'crm_campaign_dispatch_run_id', 'crm_campaign_dispatch_execution_batch_id',
                'crm_campaign_dispatch_execution_recipient_id', 'crm_campaign_dispatch_attempt_id', 'customer_id',
                'channel', 'provider_key', 'status', 'recipient_idempotency_key', 'created_at',
            ],
            'crm_campaign_dispatch_recipient_attempt_events' => [
                'id', 'product_website_id', 'crm_campaign_draft_id', 'crm_campaign_dispatch_attempt_id',
                'crm_campaign_dispatch_recipient_attempt_id', 'crm_campaign_dispatch_execution_batch_id',
                'event_type', 'provider_key', 'request_sequence', 'terminal_slot', 'status',
                'provider_message_id', 'provider_response_code', 'redacted_response_summary', 'response_hash',
                'request_started_at', 'request_completed_at', 'failed_at', 'failure_category', 'failure_summary',
                'metadata_json', 'created_at',
            ],
        ];

        foreach ($tables as $table => $columns) {
            if (!Schema::hasTable($table)) {
                $this->throwValidation('dispatch_attempt', 'CRM campaign manual dispatch-attempt storage is unavailable. Run application migrations first.');
            }
            foreach ($columns as $column) {
                if (!Schema::hasColumn($table, $column)) {
                    $this->throwValidation('dispatch_attempt', 'CRM campaign manual dispatch-attempt storage is incomplete. Run application migrations first.');
                }
            }
        }

        $requiredUniqueIndexes = [
            'crm_campaign_dispatch_attempts' => [
                'crm_campaign_dispatch_attempts_active_unique',
                'crm_campaign_dispatch_attempts_number_unique',
                'crm_campaign_dispatch_attempts_idem_unique',
            ],
            'crm_campaign_dispatch_recipient_attempts' => [
                'crm_campaign_dispatch_rec_attempts_exec_rec_unique',
                'crm_campaign_dispatch_rec_attempts_customer_unique',
                'crm_campaign_dispatch_rec_attempts_idem_unique',
            ],
            'crm_campaign_dispatch_recipient_attempt_events' => [
                'crm_campaign_dispatch_rec_events_seq_type_unique',
                'crm_campaign_dispatch_rec_events_seq_terminal_unique',
            ],
        ];

        foreach ($requiredUniqueIndexes as $table => $indexes) {
            foreach ($indexes as $index) {
                if (!$this->hasIndex($table, $index)) {
                    $this->throwValidation('dispatch_attempt', 'CRM campaign manual dispatch-attempt uniqueness protection is incomplete. Re-run the application migration and repair duplicate ledger data before preparing an attempt.');
                }
            }
        }
    }

    protected function hasIndex(string $table, string $index): bool
    {
        try {
            return count(DB::select('SHOW INDEX FROM `' . $table . '` WHERE Key_name = ?', [$index])) > 0;
        } catch (\Throwable $exception) {
            return false;
        }
    }

    protected function hasUserStorage(): bool
    {
        return Schema::hasTable('users') && Schema::hasColumn('users', 'id') && Schema::hasColumn('users', 'name');
    }

    protected function sameSignature($left, $right): bool
    {
        $left = strtolower(trim((string) $left));
        $right = strtolower(trim((string) $right));

        return $this->isValidSignature($left) && $this->isValidSignature($right) && hash_equals($left, $right);
    }

    protected function sameNullableInteger($left, $right): bool
    {
        $leftEmpty = $left === null || $left === '';
        $rightEmpty = $right === null || $right === '';
        if ($leftEmpty || $rightEmpty) {
            return $leftEmpty && $rightEmpty;
        }

        return is_numeric($left) && is_numeric($right) && (int) $left === (int) $right;
    }

    protected function isValidSignature($value): bool
    {
        return preg_match('/^[a-f0-9]{64}$/D', strtolower(trim((string) $value))) === 1;
    }

    protected function timestampString($value): string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return trim((string) $value);
    }

    protected function attemptSignatureKey(): string
    {
        return hash('sha256', 'crm-campaign-dispatch-attempt-signature|' . $this->applicationKey(), true);
    }

    protected function applicationKey(): string
    {
        $key = trim((string) config('app.key'));
        if ($key === '') {
            $this->throwValidation('dispatch_attempt', 'Application encryption key is unavailable. Manual provider-attempt preparation cannot continue safely.');
        }

        return $key;
    }

    protected function encoded(array $payload): string
    {
        return json_encode($this->canonicalize($payload), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    protected function canonicalize($value)
    {
        if (!is_array($value)) {
            return $value;
        }

        if ($value === []) {
            return [];
        }

        $isList = array_keys($value) === range(0, count($value) - 1);
        if ($isList) {
            return array_map(fn ($item) => $this->canonicalize($item), $value);
        }

        ksort($value);
        foreach ($value as $key => $item) {
            $value[$key] = $this->canonicalize($item);
        }

        return $value;
    }

    protected function firstValidationMessage(ValidationException $exception): string
    {
        foreach ($exception->errors() as $messages) {
            foreach ((array) $messages as $message) {
                return (string) $message;
            }
        }

        return 'Campaign manual provider-attempt validation failed.';
    }

    protected function stringLength(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
    }

    protected function isUniqueConstraintViolation(\Throwable $exception): bool
    {
        return $exception instanceof QueryException && strncmp((string) $exception->getCode(), '23', 2) === 0;
    }

    protected function throwValidation(string $field, string $message): void
    {
        throw ValidationException::withMessages([$field => [$message]]);
    }

    protected function logFailure(string $operation, int $draftId, ?int $batchId, ?int $attemptId, int $actorId, \Throwable $exception): void
    {
        Log::error('CRM campaign manual dispatch-attempt write failed', [
            'operation' => $operation,
            'campaign_draft_id' => $draftId,
            'execution_batch_id' => $batchId,
            'dispatch_attempt_id' => $attemptId,
            'actor_id' => $actorId,
            'error' => $exception->getMessage(),
        ]);
    }
}
