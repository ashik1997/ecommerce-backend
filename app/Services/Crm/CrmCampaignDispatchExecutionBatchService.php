<?php

namespace App\Services\Crm;

use App\Models\Crm\CrmCampaignDispatchExecutionBatch;
use App\Models\Crm\CrmCampaignDispatchExecutionRecipient;
use App\Models\Crm\CrmCampaignDispatchPreparation;
use App\Models\Crm\CrmCampaignDispatchRecipient;
use App\Models\Crm\CrmCampaignDispatchRun;
use App\Models\Crm\CrmCampaignDispatchRunRecipient;
use App\Models\Crm\CrmCampaignDraft;
use App\Models\User;
use App\Services\RoleSidebarPermissionService;
use DateTimeInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CrmCampaignDispatchExecutionBatchService
{
    public const STATUSES = ['prepared', 'cancelled'];

    public const RECIPIENT_STATUSES = ['prepared'];

    public const EXECUTION_INTEGRITY_SIGNATURE_VERSION = 1;

    protected const PERMISSION_KEY = 'crm.campaign-drafts.claim-dispatch-execution';

    public function __construct(
        protected CrmCampaignDraftService $campaignDraftService,
        protected CrmCampaignRecipientSnapshotService $recipientSnapshotService,
        protected CrmActivityService $activityService,
        protected RoleSidebarPermissionService $permissionService
    ) {
    }

    public function preview(int $draftId, User $actor): array
    {
        $this->ensureStorage();
        $this->authorize($actor, 'read');
        $draft = $this->campaignDraftService->findVisible($draftId, $actor);
        $run = $this->activeReleasedRun((int) $draft->id);
        $eligibility = $this->claimEligibility($draft, $run, $actor);
        unset($eligibility['recipient_rows']);

        return array_merge($eligibility, [
            'active_run' => $run ? $this->runPayload($run) : null,
            'active_execution_batch' => ($batch = $this->activeExecutionBatch((int) $draft->id)) ? $this->payload($batch, $actor) : null,
            'recipient_list_exposed' => false,
            'recipient_export_available' => false,
            'provider_payload_available' => false,
            'send_available' => false,
            'execute_available' => false,
            'schedule_available' => false,
            'read_only' => true,
            'notice' => 'This aggregate preview creates a provider-neutral manual execution boundary only. Claiming does not send, execute, schedule, queue, export, or disclose recipient destinations.',
        ]);
    }

    public function history(int $draftId, User $actor): array
    {
        $this->ensureStorage();
        $this->authorize($actor, 'read');
        $draft = $this->campaignDraftService->findVisible($draftId, $actor);

        $query = CrmCampaignDispatchExecutionBatch::query()
            ->where('crm_campaign_draft_id', $draft->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($this->hasUserStorage()) {
            $query->with(['claimant:id,name', 'canceller:id,name']);
        }

        return $query->get()
            ->map(fn (CrmCampaignDispatchExecutionBatch $batch) => $this->payload($batch, $actor))
            ->values()
            ->all();
    }

    public function claim(int $draftId, int $runId, User $actor): CrmCampaignDispatchExecutionBatch
    {
        $this->ensureStorage();
        $this->authorize($actor, 'create');
        $this->campaignDraftService->findVisible($draftId, $actor);

        try {
            $batch = DB::transaction(function () use ($draftId, $runId, $actor) {
                $draft = CrmCampaignDraft::query()->lockForUpdate()->findOrFail($draftId);
                $this->ensureReadable($draft, $actor);

                $run = CrmCampaignDispatchRun::query()
                    ->where('crm_campaign_draft_id', $draft->id)
                    ->lockForUpdate()
                    ->findOrFail($runId);

                $eligibility = $this->claimEligibility($draft, $run, $actor, true);
                $this->ensureReady($eligibility);
                $claimedAt = now();
                $runRecipientRows = $eligibility['recipient_rows'];
                $batchIdempotencyKey = $this->batchIdempotencyKey($draft, $run);

                $batch = CrmCampaignDispatchExecutionBatch::create([
                    'product_website_id' => $run->product_website_id,
                    'crm_campaign_draft_id' => $draft->id,
                    'crm_campaign_dispatch_preparation_id' => $run->crm_campaign_dispatch_preparation_id,
                    'crm_campaign_dispatch_run_id' => $run->id,
                    'status' => 'prepared',
                    'active_slot' => 1,
                    'channel' => $run->channel,
                    'approved_snapshot_signature' => $run->approved_snapshot_signature,
                    'approved_snapshot_signature_version' => $run->approved_snapshot_signature_version,
                    'approved_snapshot_at' => $run->approved_snapshot_at,
                    'approved_recipient_set_signature' => $run->approved_recipient_set_signature,
                    'approved_recipient_set_signature_version' => $run->approved_recipient_set_signature_version,
                    'approved_recipient_count' => $run->approved_recipient_count,
                    'frozen_recipient_set_signature' => $run->frozen_recipient_set_signature,
                    'frozen_recipient_set_signature_version' => $run->frozen_recipient_set_signature_version,
                    'frozen_recipient_count' => $run->frozen_recipient_count,
                    'run_integrity_signature' => $run->run_integrity_signature,
                    'run_integrity_signature_version' => $run->run_integrity_signature_version,
                    'subject_snapshot' => $draft->subject,
                    'message_body_snapshot' => $draft->message_body,
                    'batch_idempotency_key' => $batchIdempotencyKey,
                    'claimed_by' => $actor->id,
                    'claimed_at' => $claimedAt,
                    'metadata_json' => [
                        'provider_neutral' => true,
                        'manual_batch_boundary' => true,
                        'release_claim_separated' => true,
                        'claimant_is_campaign_creator' => (int) $draft->created_by === (int) $actor->id,
                        'claimant_is_campaign_approver' => (int) $draft->reviewed_by === (int) $actor->id,
                        'claimant_is_dispatch_preparer' => (int) ($eligibility['preparation_prepared_by'] ?? 0) === (int) $actor->id,
                        'recipient_list_exposed' => false,
                        'recipient_export_available' => false,
                        'provider_payload_available' => false,
                        'send_available' => false,
                        'execute_available' => false,
                        'schedule_available' => false,
                    ],
                ]);

                $rows = collect($runRecipientRows)->map(fn (array $row) => [
                    'product_website_id' => $run->product_website_id,
                    'crm_campaign_draft_id' => $draft->id,
                    'crm_campaign_dispatch_preparation_id' => $run->crm_campaign_dispatch_preparation_id,
                    'crm_campaign_dispatch_run_id' => $run->id,
                    'crm_campaign_dispatch_execution_batch_id' => $batch->id,
                    'crm_campaign_dispatch_run_recipient_id' => $row['id'],
                    'customer_id' => $row['customer_id'],
                    'channel' => $run->channel,
                    'destination_hash' => $row['destination_hash'],
                    'idempotency_key' => $this->recipientIdempotencyKey($batch, $row),
                    'status' => 'prepared',
                    'created_at' => $claimedAt,
                ])->all();

                foreach (array_chunk($rows, 500) as $chunk) {
                    DB::table('crm_campaign_dispatch_execution_recipients')->insert($chunk);
                }

                $executionRows = $this->executionRows((int) $batch->id, true);
                $batch->forceFill([
                    'execution_integrity_signature' => $this->executionIntegritySignature($batch, $executionRows),
                    'execution_integrity_signature_version' => self::EXECUTION_INTEGRITY_SIGNATURE_VERSION,
                ])->save();

                $run->forceFill([
                    'status' => 'claimed',
                    'active_slot' => null,
                    'claimed_execution_batch_id' => $batch->id,
                    'claimed_by' => $actor->id,
                    'claimed_at' => $claimedAt,
                ])->save();

                $this->recordActivity('crm_campaign_dispatch_execution_claimed', $batch, $actor->id, 'CRM campaign dispatch execution batch claimed', [
                    'crm_campaign_dispatch_run_id' => (int) $run->id,
                    'frozen_recipient_count' => (int) $batch->frozen_recipient_count,
                    'provider_neutral' => true,
                    'manual_batch_boundary' => true,
                    'release_claim_separated' => true,
                    'claimant_is_campaign_creator' => (bool) ($batch->metadata_json['claimant_is_campaign_creator'] ?? false),
                    'claimant_is_campaign_approver' => (bool) ($batch->metadata_json['claimant_is_campaign_approver'] ?? false),
                    'claimant_is_dispatch_preparer' => (bool) ($batch->metadata_json['claimant_is_dispatch_preparer'] ?? false),
                ]);

                return $batch;
            });
        } catch (\Throwable $exception) {
            $this->logFailure('claim', $draftId, $runId, null, $actor->id, $exception);
            if ($this->isUniqueConstraintViolation($exception)) {
                $this->throwValidation('dispatch_execution_batch', 'This dispatch run has already been claimed or another active execution batch already exists for the campaign. Refresh the aggregate preview.');
            }
            throw $exception;
        }

        return $this->freshWithRelations($batch);
    }

    /**
     * Revalidate the complete Stage 24 -> Stage 23 -> Stage 22 immutable chain.
     * The returned execution-recipient identities are server-side only and must
     * never be serialized into a browser response.
     */
    public function assertPreparedBatchIntegrity(CrmCampaignDispatchExecutionBatch $batch, bool $lockRows = false): array
    {
        $this->ensureStorage();
        if ($batch->status !== 'prepared' || (int) $batch->active_slot !== 1) {
            $this->throwValidation('dispatch_execution_batch', 'Only an active prepared Stage 24 execution batch can be consumed by a manual provider-attempt ledger.');
        }

        $draftQuery = CrmCampaignDraft::query();
        $preparationQuery = CrmCampaignDispatchPreparation::query();
        $runQuery = CrmCampaignDispatchRun::query();
        if ($lockRows) {
            $draftQuery->lockForUpdate();
            $preparationQuery->lockForUpdate();
            $runQuery->lockForUpdate();
        }

        $draft = $draftQuery->find($batch->crm_campaign_draft_id);
        $preparation = $preparationQuery->find($batch->crm_campaign_dispatch_preparation_id);
        $run = $runQuery->find($batch->crm_campaign_dispatch_run_id);

        if (!$draft || !$preparation || !$run) {
            $this->throwValidation('dispatch_execution_batch', 'The Stage 24 execution batch no longer references a complete immutable Stage 20–23 lineage.');
        }

        $this->campaignDraftService->assertApprovedSnapshotIntegrity($draft);

        $lineageMatches = $this->lineageMatches($draft, $preparation, $run)
            && (int) $batch->crm_campaign_draft_id === (int) $draft->id
            && (int) $batch->crm_campaign_dispatch_preparation_id === (int) $preparation->id
            && (int) $batch->crm_campaign_dispatch_run_id === (int) $run->id
            && $this->sameNullableInteger($batch->product_website_id, $run->product_website_id)
            && hash_equals((string) $run->channel, (string) $batch->channel)
            && $run->status === 'claimed'
            && $run->active_slot === null
            && (int) $run->claimed_execution_batch_id === (int) $batch->id
            && (int) $run->claimed_by === (int) $batch->claimed_by
            && $this->timestampString($run->claimed_at) === $this->timestampString($batch->claimed_at)
            && $this->sameSignature($run->approved_snapshot_signature, $batch->approved_snapshot_signature)
            && (int) $run->approved_snapshot_signature_version === (int) $batch->approved_snapshot_signature_version
            && $this->timestampString($run->approved_snapshot_at) === $this->timestampString($batch->approved_snapshot_at)
            && $this->sameSignature($run->approved_recipient_set_signature, $batch->approved_recipient_set_signature)
            && (int) $run->approved_recipient_set_signature_version === (int) $batch->approved_recipient_set_signature_version
            && (int) $run->approved_recipient_count === (int) $batch->approved_recipient_count
            && $this->sameSignature($run->frozen_recipient_set_signature, $batch->frozen_recipient_set_signature)
            && (int) $run->frozen_recipient_set_signature_version === (int) $batch->frozen_recipient_set_signature_version
            && (int) $run->frozen_recipient_count === (int) $batch->frozen_recipient_count
            && $this->sameSignature($run->run_integrity_signature, $batch->run_integrity_signature)
            && (int) $run->run_integrity_signature_version === (int) $batch->run_integrity_signature_version
            && hash_equals((string) $draft->subject, (string) $batch->subject_snapshot)
            && hash_equals((string) $draft->message_body, (string) $batch->message_body_snapshot)
            && $this->sameSignature($this->batchIdempotencyKey($draft, $run), $batch->batch_idempotency_key);

        if (!$lineageMatches) {
            $this->throwValidation('dispatch_execution_batch', 'The Stage 24 execution batch failed immutable Stage 20–23 lineage verification.');
        }

        [$runRowsVerified, $runRowsMessage, $runRows] = $this->verifiedRunRows($draft, $preparation, $run, $lockRows);
        if (!$runRowsVerified) {
            $this->throwValidation('dispatch_execution_batch', $runRowsMessage);
        }

        $this->assertExecutionBatchIntegrity($batch, $lockRows);
        $executionRows = $this->executionRows((int) $batch->id, $lockRows);
        $runRowsById = collect($runRows)->keyBy('id');
        foreach ($executionRows as $row) {
            $source = $runRowsById->get($row['crm_campaign_dispatch_run_recipient_id']);
            if (!$source
                || $row['customer_id'] !== (int) $source['customer_id']
                || !hash_equals((string) $row['channel'], (string) $source['channel'])
                || !hash_equals((string) $row['destination_hash'], (string) $source['destination_hash'])
                || !$this->sameNullableInteger($row['product_website_id'], $source['product_website_id'])) {
                $this->throwValidation('dispatch_execution_batch', 'A Stage 24 execution recipient no longer matches its immutable Stage 23 source identity.');
            }
        }

        return $executionRows;
    }

    public function cancel(int $draftId, int $batchId, array $data, User $actor): CrmCampaignDispatchExecutionBatch
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
            $batch = DB::transaction(function () use ($draftId, $batchId, $reason, $actor) {
                $draft = CrmCampaignDraft::query()->lockForUpdate()->findOrFail($draftId);
                $this->ensureReadable($draft, $actor);

                $batch = CrmCampaignDispatchExecutionBatch::query()
                    ->where('crm_campaign_draft_id', $draft->id)
                    ->lockForUpdate()
                    ->findOrFail($batchId);

                if ($batch->status !== 'prepared' || (int) $batch->active_slot !== 1) {
                    $this->throwValidation('dispatch_execution_batch', 'Only an active prepared provider-neutral execution batch can be cancelled.');
                }

                if ($this->hasActiveDispatchAttempt((int) $batch->id)) {
                    $this->throwValidation('dispatch_execution_batch', 'Cancel the active prepared manual provider-attempt ledger before cancelling this Stage 24 execution batch. Processing attempts cannot be cancelled.');
                }
                if ($this->hasStartedDispatchAttempt((int) $batch->id)) {
                    $this->throwValidation('dispatch_execution_batch', 'This Stage 24 execution batch has already entered real SMS execution and remains permanently consumed. It cannot be cancelled or reused.');
                }

                $this->assertExecutionBatchIntegrity($batch, true);

                $batch->forceFill([
                    'status' => 'cancelled',
                    'active_slot' => null,
                    'cancelled_by' => $actor->id,
                    'cancelled_at' => now(),
                    'cancellation_reason' => $reason,
                ])->save();
                $batch = $batch->refresh();

                $this->recordActivity('crm_campaign_dispatch_execution_cancelled', $batch, $actor->id, 'CRM campaign dispatch execution batch cancelled', [
                    'reason_present' => true,
                    'provider_neutral' => true,
                    'manual_batch_boundary' => true,
                    'run_remains_claimed' => true,
                ]);

                return $batch;
            });
        } catch (\Throwable $exception) {
            $this->logFailure('cancel', $draftId, null, $batchId, $actor->id, $exception);
            throw $exception;
        }

        return $this->freshWithRelations($batch);
    }

    public function payload(CrmCampaignDispatchExecutionBatch $batch, User $actor): array
    {
        $hasActiveDispatchAttempt = $batch->status === 'prepared'
            && (int) $batch->active_slot === 1
            && $this->hasActiveDispatchAttempt((int) $batch->id);
        $hasStartedDispatchAttempt = $this->hasStartedDispatchAttempt((int) $batch->id);
        $canCancel = $batch->status === 'prepared'
            && (int) $batch->active_slot === 1
            && !$hasActiveDispatchAttempt
            && !$hasStartedDispatchAttempt
            && $this->can($actor, 'update');

        return [
            'id' => (int) $batch->id,
            'crm_campaign_draft_id' => (int) $batch->crm_campaign_draft_id,
            'crm_campaign_dispatch_preparation_id' => (int) $batch->crm_campaign_dispatch_preparation_id,
            'crm_campaign_dispatch_run_id' => (int) $batch->crm_campaign_dispatch_run_id,
            'status' => $batch->status,
            'channel' => $batch->channel,
            'approved_recipient_count' => (int) $batch->approved_recipient_count,
            'frozen_recipient_count' => (int) $batch->frozen_recipient_count,
            'execution_integrity_seal_recorded' => $this->isValidSignature($batch->execution_integrity_signature)
                && (int) $batch->execution_integrity_signature_version === self::EXECUTION_INTEGRITY_SIGNATURE_VERSION,
            'claimed_by' => $batch->claimed_by ? (int) $batch->claimed_by : null,
            'claimed_by_name' => $batch->relationLoaded('claimant') ? optional($batch->claimant)->name : null,
            'claimed_at' => optional($batch->claimed_at)->format('Y-m-d h:i a'),
            'cancelled_by_name' => $batch->relationLoaded('canceller') ? optional($batch->canceller)->name : null,
            'cancelled_at' => optional($batch->cancelled_at)->format('Y-m-d h:i a'),
            'cancellation_reason' => $batch->cancellation_reason,
            'provider_neutral' => true,
            'manual_batch_boundary' => true,
            'recipient_list_exposed' => false,
            'recipient_export_available' => false,
            'provider_payload_available' => false,
            'send_available' => false,
            'execute_available' => false,
            'schedule_available' => false,
            'active_dispatch_attempt_exists' => $hasActiveDispatchAttempt,
            'real_sms_execution_started' => $hasStartedDispatchAttempt,
            'can_cancel' => $canCancel,
            'created_at' => optional($batch->created_at)->format('Y-m-d h:i a'),
        ];
    }

    protected function claimEligibility(CrmCampaignDraft $draft, ?CrmCampaignDispatchRun $run, User $actor, bool $lockRows = false): array
    {
        $checks = [];
        $addCheck = function (string $key, string $label, bool $passed, string $message) use (&$checks) {
            $checks[] = compact('key', 'label', 'passed', 'message');
        };

        $approved = $draft->status === 'approved';
        $addCheck('approved_lifecycle', 'Approved lifecycle', $approved, $approved ? 'Campaign lifecycle is approved.' : 'Only an approved campaign can claim a provider-neutral execution batch.');

        $ledgerChannel = CrmCampaignDraftService::isDispatchLedgerChannel($draft->planned_channel)
            && $run !== null
            && CrmCampaignDraftService::isDispatchLedgerChannel($run->channel);
        $addCheck('stage31_ledger_channel', 'Stage 31 dispatch-ledger channel boundary', $ledgerChannel, $ledgerChannel ? 'BulkSMSBD SMS and Email may advance through immutable dispatch ledgers. Email remains non-sending.' : 'Execution-batch claim is disabled for newsletter, WhatsApp, and other legacy campaign channels.');

        $approvalIntegrity = false;
        $approvalIntegrityMessage = null;
        try {
            $this->campaignDraftService->assertApprovedSnapshotIntegrity($draft);
            $approvalIntegrity = true;
        } catch (ValidationException $exception) {
            $approvalIntegrityMessage = $this->firstValidationMessage($exception);
        }
        $addCheck('approved_planning_integrity', 'Approved planning integrity', $approvalIntegrity, $approvalIntegrity ? 'Approved campaign planning seal passed integrity verification.' : ($approvalIntegrityMessage ?: 'Approved campaign planning integrity could not be verified.'));

        $activeRun = $run !== null && $run->status === 'released' && (int) $run->active_slot === 1;
        $addCheck('active_released_run', 'Active released run', $activeRun, $activeRun ? 'An active released Stage 23 run is available.' : 'Execution claim requires an active released Stage 23 run. Cancelled, claimed, or missing runs cannot be claimed.');

        $unclaimed = $run !== null && empty($run->claimed_execution_batch_id);
        $addCheck('run_unclaimed', 'Single-consumer run boundary', $unclaimed, $unclaimed ? 'The released run has not been claimed by an earlier execution batch.' : 'The released run has already been claimed and cannot be consumed again.');

        $separated = $run !== null && (int) $run->released_by > 0 && (int) $run->released_by !== (int) $actor->id;
        $addCheck('release_claim_separation', 'Release-to-claim responsibility separation', $separated, $separated ? 'The execution claimant differs from the dispatch releaser.' : 'The dispatch releaser cannot claim the provider-neutral execution batch. Assign a separate authorized user.');

        $preparation = null;
        if ($run !== null) {
            $query = CrmCampaignDispatchPreparation::query()->where('crm_campaign_draft_id', $draft->id);
            if ($lockRows) {
                $query->lockForUpdate();
            }
            $preparation = $query->find($run->crm_campaign_dispatch_preparation_id);
        }

        $lineageMatches = $preparation !== null && $this->lineageMatches($draft, $preparation, $run);
        $addCheck('immutable_lineage', 'Stage 20–23 immutable lineage', $lineageMatches, $lineageMatches ? 'Campaign, preparation, and released-run integrity metadata remain aligned.' : 'Campaign, preparation, or released-run metadata is missing, stale, or inconsistent. Create a fresh preparation and release.');

        $recipientRows = [];
        $runRowsVerified = false;
        $runRowsMessage = 'Released-run recipient rows are unavailable.';
        if ($preparation !== null && $run !== null) {
            [$runRowsVerified, $runRowsMessage, $recipientRows] = $this->verifiedRunRows($draft, $preparation, $run, $lockRows);
        }
        $addCheck('run_recipient_rows', 'Run-recipient integrity', $runRowsVerified, $runRowsMessage);

        $previousBatchExists = $run !== null && CrmCampaignDispatchExecutionBatch::query()
            ->where('crm_campaign_dispatch_run_id', $run->id)
            ->exists();
        $addCheck('no_previous_run_batch', 'Run duplicate-batch prevention', !$previousBatchExists, $previousBatchExists ? 'This released run already has an execution-batch ledger.' : 'No previous execution batch exists for this run.');

        $activeBatch = $this->activeExecutionBatch((int) $draft->id);
        $addCheck('no_active_campaign_batch', 'Campaign active-batch prevention', $activeBatch === null, $activeBatch === null ? 'No active provider-neutral execution batch exists for this campaign.' : 'An active provider-neutral execution batch already exists. Cancel it before creating a fresh preparation and release chain.');

        $ready = collect($checks)->every(fn (array $check) => $check['passed']);

        return [
            'draft_id' => (int) $draft->id,
            'draft_name' => $draft->name,
            'status' => $draft->status,
            'channel' => $run?->channel ?: $draft->planned_channel,
            'approved_recipient_count' => is_numeric($draft->approved_recipient_count) ? (int) $draft->approved_recipient_count : null,
            'frozen_recipient_count' => $run && is_numeric($run->frozen_recipient_count) ? (int) $run->frozen_recipient_count : null,
            'ready_for_claim' => $ready,
            'ready_for_claim_label' => $ready ? 'Eligible for provider-neutral execution-batch claim' : 'Not eligible for execution-batch claim',
            'checks' => $checks,
            'recipient_rows' => $recipientRows,
            'preparation_prepared_by' => $preparation?->prepared_by,
        ];
    }

    protected function lineageMatches(CrmCampaignDraft $draft, CrmCampaignDispatchPreparation $preparation, ?CrmCampaignDispatchRun $run): bool
    {
        if ($run === null) {
            return false;
        }

        return (int) $preparation->crm_campaign_draft_id === (int) $draft->id
            && (int) $run->crm_campaign_draft_id === (int) $draft->id
            && (int) $run->crm_campaign_dispatch_preparation_id === (int) $preparation->id
            && (int) $preparation->released_dispatch_run_id === (int) $run->id
            && $preparation->status === 'prepared'
            && $preparation->active_slot === null
            && trim((string) $draft->planned_channel) !== ''
            && hash_equals((string) $draft->planned_channel, (string) $preparation->channel)
            && hash_equals((string) $preparation->channel, (string) $run->channel)
            && $this->sameNullableInteger($draft->product_website_id, $preparation->product_website_id)
            && $this->sameNullableInteger($preparation->product_website_id, $run->product_website_id)
            && $this->sameSignature($draft->approved_snapshot_signature, $preparation->approved_snapshot_signature)
            && $this->sameSignature($preparation->approved_snapshot_signature, $run->approved_snapshot_signature)
            && (int) $draft->approved_snapshot_signature_version === (int) $preparation->approved_snapshot_signature_version
            && (int) $preparation->approved_snapshot_signature_version === (int) $run->approved_snapshot_signature_version
            && $this->timestampString($draft->approved_snapshot_at) === $this->timestampString($preparation->approved_snapshot_at)
            && $this->timestampString($preparation->approved_snapshot_at) === $this->timestampString($run->approved_snapshot_at)
            && $this->sameSignature($draft->approved_recipient_set_signature, $preparation->approved_recipient_set_signature)
            && $this->sameSignature($preparation->approved_recipient_set_signature, $run->approved_recipient_set_signature)
            && (int) $draft->approved_recipient_set_signature_version === CrmCampaignRecipientSnapshotService::RECIPIENT_SET_SIGNATURE_VERSION
            && (int) $draft->approved_recipient_set_signature_version === (int) $preparation->approved_recipient_set_signature_version
            && (int) $preparation->approved_recipient_set_signature_version === (int) $run->approved_recipient_set_signature_version
            && (int) $draft->approved_recipient_count > 0
            && (int) $draft->approved_recipient_count === (int) $preparation->approved_recipient_count
            && (int) $preparation->approved_recipient_count === (int) $run->approved_recipient_count
            && $this->sameSignature($preparation->frozen_recipient_set_signature, $run->frozen_recipient_set_signature)
            && (int) $preparation->frozen_recipient_set_signature_version === CrmCampaignRecipientSnapshotService::FROZEN_RECIPIENT_SET_SIGNATURE_VERSION
            && (int) $preparation->frozen_recipient_set_signature_version === (int) $run->frozen_recipient_set_signature_version
            && (int) $preparation->frozen_recipient_count > 0
            && (int) $preparation->frozen_recipient_count === (int) $run->frozen_recipient_count;
    }

    protected function verifiedRunRows(CrmCampaignDraft $draft, CrmCampaignDispatchPreparation $preparation, CrmCampaignDispatchRun $run, bool $lockRows): array
    {
        $storedSignature = strtolower(trim((string) $run->run_integrity_signature));
        if (!$this->isValidSignature($storedSignature)
            || (int) $run->run_integrity_signature_version !== CrmCampaignDispatchRunService::RUN_INTEGRITY_SIGNATURE_VERSION
            || (int) $run->frozen_recipient_count <= 0) {
            return [false, 'Released-run integrity metadata is missing or unsupported. Create a fresh preparation and release.', []];
        }

        $query = CrmCampaignDispatchRunRecipient::query()
            ->where('crm_campaign_dispatch_run_id', $run->id)
            ->orderBy('id');
        if ($lockRows) {
            $query->lockForUpdate();
        }

        $rows = $query->get([
            'id', 'product_website_id', 'crm_campaign_draft_id', 'crm_campaign_dispatch_preparation_id',
            'crm_campaign_dispatch_run_id', 'crm_campaign_dispatch_recipient_id', 'customer_id', 'channel',
            'destination_hash', 'status',
        ])->map(fn (CrmCampaignDispatchRunRecipient $recipient) => [
            'id' => (int) $recipient->id,
            'product_website_id' => $recipient->product_website_id,
            'crm_campaign_draft_id' => (int) $recipient->crm_campaign_draft_id,
            'crm_campaign_dispatch_preparation_id' => (int) $recipient->crm_campaign_dispatch_preparation_id,
            'crm_campaign_dispatch_run_id' => (int) $recipient->crm_campaign_dispatch_run_id,
            'crm_campaign_dispatch_recipient_id' => (int) $recipient->crm_campaign_dispatch_recipient_id,
            'customer_id' => (int) $recipient->customer_id,
            'channel' => (string) $recipient->channel,
            'destination_hash' => strtolower(trim((string) $recipient->destination_hash)),
            'status' => (string) $recipient->status,
        ])->all();

        if (count($rows) !== (int) $run->frozen_recipient_count || count($rows) !== (int) $run->approved_recipient_count) {
            return [false, 'Released-run recipient-row count no longer matches the recorded header count.', []];
        }

        $sourceIds = collect($rows)->pluck('crm_campaign_dispatch_recipient_id')->all();
        $sourceQuery = CrmCampaignDispatchRecipient::query()
            ->where('crm_campaign_dispatch_preparation_id', $preparation->id)
            ->whereIn('id', $sourceIds)
            ->orderBy('id');
        if ($lockRows) {
            $sourceQuery->lockForUpdate();
        }
        $sourceRows = $sourceQuery->get([
            'id', 'product_website_id', 'crm_campaign_draft_id', 'crm_campaign_dispatch_preparation_id',
            'customer_id', 'channel', 'destination_ciphertext', 'destination_hash',
        ])->keyBy('id');

        if ($sourceRows->count() !== count($rows)) {
            return [false, 'A released-run recipient no longer references its immutable Stage 22 source row.', []];
        }

        $seenCustomers = [];
        $seenDestinations = [];
        $seenSources = [];
        $frozenRows = [];
        foreach ($rows as $row) {
            $source = $sourceRows->get($row['crm_campaign_dispatch_recipient_id']);
            if (!$source
                || $row['id'] <= 0
                || $row['crm_campaign_dispatch_recipient_id'] <= 0
                || !$this->sameNullableInteger($row['product_website_id'], $run->product_website_id)
                || $row['crm_campaign_draft_id'] !== (int) $draft->id
                || $row['crm_campaign_dispatch_preparation_id'] !== (int) $preparation->id
                || $row['crm_campaign_dispatch_run_id'] !== (int) $run->id
                || $row['customer_id'] <= 0
                || !hash_equals((string) $run->channel, $row['channel'])
                || $row['status'] !== 'released'
                || !$this->isValidSignature($row['destination_hash'])
                || isset($seenCustomers[$row['customer_id']])
                || isset($seenDestinations[$row['destination_hash']])
                || isset($seenSources[$row['crm_campaign_dispatch_recipient_id']])
                || !$this->sameNullableInteger($source->product_website_id, $run->product_website_id)
                || (int) $source->crm_campaign_draft_id !== (int) $draft->id
                || (int) $source->crm_campaign_dispatch_preparation_id !== (int) $preparation->id
                || (int) $source->customer_id !== $row['customer_id']
                || !hash_equals((string) $run->channel, (string) $source->channel)
                || !hash_equals($row['destination_hash'], strtolower(trim((string) $source->destination_hash)))
                || trim((string) $source->destination_ciphertext) === '') {
                return [false, 'Released-run recipient rows failed identity, channel, duplicate, or immutable source-link verification.', []];
            }
            $seenCustomers[$row['customer_id']] = true;
            $seenDestinations[$row['destination_hash']] = true;
            $seenSources[$row['crm_campaign_dispatch_recipient_id']] = true;
            $frozenRows[] = [
                'id' => (int) $source->id,
                'customer_id' => (int) $source->customer_id,
                'destination_hash' => strtolower(trim((string) $source->destination_hash)),
            ];
        }

        $frozenSignature = $this->recipientSnapshotService->frozenRecipientSetSignature(
            (int) $draft->id,
            (int) $preparation->id,
            (string) $preparation->channel,
            $frozenRows
        );
        if (!hash_equals(strtolower((string) $preparation->frozen_recipient_set_signature), $frozenSignature)) {
            return [false, 'The immutable Stage 22 frozen-recipient seal failed verification.', []];
        }

        if (!hash_equals($storedSignature, $this->runIntegritySignature($run, $rows))) {
            return [false, 'Released-run recipient rows failed integrity-seal verification.', []];
        }

        return [true, number_format(count($rows)) . ' immutable released-run recipient rows passed claim-time verification.', $rows];
    }

    protected function runIntegritySignature(CrmCampaignDispatchRun $run, array $rows): string
    {
        $payload = [
            'signature_version' => CrmCampaignDispatchRunService::RUN_INTEGRITY_SIGNATURE_VERSION,
            'product_website_id' => $run->product_website_id === null ? null : (int) $run->product_website_id,
            'crm_campaign_draft_id' => (int) $run->crm_campaign_draft_id,
            'crm_campaign_dispatch_preparation_id' => (int) $run->crm_campaign_dispatch_preparation_id,
            'channel' => (string) $run->channel,
            'released_by' => $run->released_by === null ? null : (int) $run->released_by,
            'released_at' => $this->timestampString($run->released_at),
            'approved_snapshot_signature' => strtolower((string) $run->approved_snapshot_signature),
            'approved_snapshot_signature_version' => (int) $run->approved_snapshot_signature_version,
            'approved_snapshot_at' => $this->timestampString($run->approved_snapshot_at),
            'approved_recipient_set_signature' => strtolower((string) $run->approved_recipient_set_signature),
            'approved_recipient_set_signature_version' => (int) $run->approved_recipient_set_signature_version,
            'approved_recipient_count' => (int) $run->approved_recipient_count,
            'frozen_recipient_set_signature' => strtolower((string) $run->frozen_recipient_set_signature),
            'frozen_recipient_set_signature_version' => (int) $run->frozen_recipient_set_signature_version,
            'frozen_recipient_count' => (int) $run->frozen_recipient_count,
            'recipients' => collect($rows)->map(fn (array $row) => [
                'crm_campaign_dispatch_recipient_id' => (int) $row['crm_campaign_dispatch_recipient_id'],
                'customer_id' => (int) $row['customer_id'],
                'destination_hash' => strtolower((string) $row['destination_hash']),
            ])->sortBy(fn (array $row) => sprintf('%020d|%020d|%s', $row['crm_campaign_dispatch_recipient_id'], $row['customer_id'], $row['destination_hash']))->values()->all(),
        ];

        return hash_hmac('sha256', $this->encoded($payload), $this->runSignatureKey());
    }

    protected function assertExecutionBatchIntegrity(CrmCampaignDispatchExecutionBatch $batch, bool $lockRows): void
    {
        if (!$this->isValidSignature($batch->execution_integrity_signature)
            || (int) $batch->execution_integrity_signature_version !== self::EXECUTION_INTEGRITY_SIGNATURE_VERSION) {
            $this->throwValidation('dispatch_execution_batch', 'Execution-batch integrity metadata is missing or unsupported.');
        }

        $rows = $this->executionRows((int) $batch->id, $lockRows);
        if (count($rows) !== (int) $batch->frozen_recipient_count || count($rows) !== (int) $batch->approved_recipient_count) {
            $this->throwValidation('dispatch_execution_batch', 'Execution-batch recipient-row count no longer matches the recorded header count.');
        }

        $seenRunRecipients = [];
        $seenCustomers = [];
        $seenDestinations = [];
        foreach ($rows as $row) {
            $expectedIdempotencyKey = $this->recipientIdempotencyKey($batch, $row);
            if ($row['id'] <= 0
                || !$this->sameNullableInteger($row['product_website_id'], $batch->product_website_id)
                || $row['crm_campaign_draft_id'] !== (int) $batch->crm_campaign_draft_id
                || $row['crm_campaign_dispatch_preparation_id'] !== (int) $batch->crm_campaign_dispatch_preparation_id
                || $row['crm_campaign_dispatch_execution_batch_id'] !== (int) $batch->id
                || $row['crm_campaign_dispatch_run_id'] !== (int) $batch->crm_campaign_dispatch_run_id
                || $row['crm_campaign_dispatch_run_recipient_id'] <= 0
                || $row['customer_id'] <= 0
                || !hash_equals((string) $batch->channel, $row['channel'])
                || $row['status'] !== 'prepared'
                || !$this->isValidSignature($row['destination_hash'])
                || !$this->sameSignature($expectedIdempotencyKey, $row['idempotency_key'])
                || isset($seenRunRecipients[$row['crm_campaign_dispatch_run_recipient_id']])
                || isset($seenCustomers[$row['customer_id']])
                || isset($seenDestinations[$row['destination_hash']])) {
                $this->throwValidation('dispatch_execution_batch', 'Execution-batch recipient rows failed identity, channel, duplicate, or idempotency verification.');
            }
            $seenRunRecipients[$row['crm_campaign_dispatch_run_recipient_id']] = true;
            $seenCustomers[$row['customer_id']] = true;
            $seenDestinations[$row['destination_hash']] = true;
        }

        if (!hash_equals(strtolower((string) $batch->execution_integrity_signature), $this->executionIntegritySignature($batch, $rows))) {
            $this->throwValidation('dispatch_execution_batch', 'Execution-batch recipient rows failed integrity-seal verification.');
        }
    }

    protected function executionRows(int $batchId, bool $lockRows): array
    {
        $query = CrmCampaignDispatchExecutionRecipient::query()
            ->where('crm_campaign_dispatch_execution_batch_id', $batchId)
            ->orderBy('id');
        if ($lockRows) {
            $query->lockForUpdate();
        }

        return $query->get([
            'id', 'product_website_id', 'crm_campaign_draft_id', 'crm_campaign_dispatch_preparation_id',
            'crm_campaign_dispatch_run_id', 'crm_campaign_dispatch_execution_batch_id',
            'crm_campaign_dispatch_run_recipient_id', 'customer_id', 'channel', 'destination_hash',
            'idempotency_key', 'status',
        ])->map(fn (CrmCampaignDispatchExecutionRecipient $recipient) => [
            'id' => (int) $recipient->id,
            'product_website_id' => $recipient->product_website_id,
            'crm_campaign_draft_id' => (int) $recipient->crm_campaign_draft_id,
            'crm_campaign_dispatch_preparation_id' => (int) $recipient->crm_campaign_dispatch_preparation_id,
            'crm_campaign_dispatch_run_id' => (int) $recipient->crm_campaign_dispatch_run_id,
            'crm_campaign_dispatch_execution_batch_id' => (int) $recipient->crm_campaign_dispatch_execution_batch_id,
            'crm_campaign_dispatch_run_recipient_id' => (int) $recipient->crm_campaign_dispatch_run_recipient_id,
            'customer_id' => (int) $recipient->customer_id,
            'channel' => (string) $recipient->channel,
            'destination_hash' => strtolower(trim((string) $recipient->destination_hash)),
            'idempotency_key' => strtolower(trim((string) $recipient->idempotency_key)),
            'status' => (string) $recipient->status,
        ])->all();
    }

    protected function executionIntegritySignature(CrmCampaignDispatchExecutionBatch $batch, array $rows): string
    {
        $payload = [
            'signature_version' => self::EXECUTION_INTEGRITY_SIGNATURE_VERSION,
            'crm_campaign_dispatch_execution_batch_id' => (int) $batch->id,
            'product_website_id' => $batch->product_website_id === null ? null : (int) $batch->product_website_id,
            'crm_campaign_draft_id' => (int) $batch->crm_campaign_draft_id,
            'crm_campaign_dispatch_preparation_id' => (int) $batch->crm_campaign_dispatch_preparation_id,
            'crm_campaign_dispatch_run_id' => (int) $batch->crm_campaign_dispatch_run_id,
            'channel' => (string) $batch->channel,
            'claimed_by' => $batch->claimed_by === null ? null : (int) $batch->claimed_by,
            'claimed_at' => $this->timestampString($batch->claimed_at),
            'approved_snapshot_signature' => strtolower((string) $batch->approved_snapshot_signature),
            'approved_snapshot_signature_version' => (int) $batch->approved_snapshot_signature_version,
            'approved_snapshot_at' => $this->timestampString($batch->approved_snapshot_at),
            'approved_recipient_set_signature' => strtolower((string) $batch->approved_recipient_set_signature),
            'approved_recipient_set_signature_version' => (int) $batch->approved_recipient_set_signature_version,
            'approved_recipient_count' => (int) $batch->approved_recipient_count,
            'frozen_recipient_set_signature' => strtolower((string) $batch->frozen_recipient_set_signature),
            'frozen_recipient_set_signature_version' => (int) $batch->frozen_recipient_set_signature_version,
            'frozen_recipient_count' => (int) $batch->frozen_recipient_count,
            'run_integrity_signature' => strtolower((string) $batch->run_integrity_signature),
            'run_integrity_signature_version' => (int) $batch->run_integrity_signature_version,
            'subject_snapshot' => (string) $batch->subject_snapshot,
            'message_body_snapshot' => (string) $batch->message_body_snapshot,
            'batch_idempotency_key' => strtolower((string) $batch->batch_idempotency_key),
            'recipients' => collect($rows)->map(fn (array $row) => [
                'crm_campaign_dispatch_execution_recipient_id' => (int) $row['id'],
                'product_website_id' => $row['product_website_id'] === null ? null : (int) $row['product_website_id'],
                'crm_campaign_draft_id' => (int) $row['crm_campaign_draft_id'],
                'crm_campaign_dispatch_preparation_id' => (int) $row['crm_campaign_dispatch_preparation_id'],
                'crm_campaign_dispatch_run_id' => (int) $row['crm_campaign_dispatch_run_id'],
                'crm_campaign_dispatch_execution_batch_id' => (int) $row['crm_campaign_dispatch_execution_batch_id'],
                'crm_campaign_dispatch_run_recipient_id' => (int) $row['crm_campaign_dispatch_run_recipient_id'],
                'customer_id' => (int) $row['customer_id'],
                'channel' => (string) $row['channel'],
                'destination_hash' => strtolower((string) $row['destination_hash']),
                'idempotency_key' => strtolower((string) $row['idempotency_key']),
                'status' => (string) $row['status'],
            ])->sortBy(fn (array $row) => sprintf('%020d|%020d|%020d|%s', $row['crm_campaign_dispatch_execution_recipient_id'], $row['crm_campaign_dispatch_run_recipient_id'], $row['customer_id'], $row['destination_hash']))->values()->all(),
        ];

        return hash_hmac('sha256', $this->encoded($payload), $this->executionSignatureKey());
    }

    protected function batchIdempotencyKey(CrmCampaignDraft $draft, CrmCampaignDispatchRun $run): string
    {
        return hash_hmac('sha256', $this->encoded([
            'scope' => 'crm_campaign_dispatch_execution_batch',
            'product_website_id' => $draft->product_website_id === null ? null : (int) $draft->product_website_id,
            'crm_campaign_draft_id' => (int) $draft->id,
            'crm_campaign_dispatch_run_id' => (int) $run->id,
            'channel' => (string) $run->channel,
        ]), $this->executionSignatureKey());
    }

    protected function recipientIdempotencyKey(CrmCampaignDispatchExecutionBatch $batch, array $row): string
    {
        return hash_hmac('sha256', $this->encoded([
            'scope' => 'crm_campaign_dispatch_execution_recipient',
            'crm_campaign_dispatch_execution_batch_id' => (int) $batch->id,
            'crm_campaign_dispatch_run_recipient_id' => (int) ($row['crm_campaign_dispatch_run_recipient_id'] ?? $row['id'] ?? 0),
            'channel' => (string) $batch->channel,
            'destination_hash' => strtolower((string) ($row['destination_hash'] ?? '')),
        ]), $this->executionSignatureKey());
    }

    protected function runPayload(CrmCampaignDispatchRun $run): array
    {
        return [
            'id' => (int) $run->id,
            'crm_campaign_draft_id' => (int) $run->crm_campaign_draft_id,
            'crm_campaign_dispatch_preparation_id' => (int) $run->crm_campaign_dispatch_preparation_id,
            'status' => $run->status,
            'channel' => $run->channel,
            'frozen_recipient_count' => (int) $run->frozen_recipient_count,
            'released_by' => $run->released_by ? (int) $run->released_by : null,
            'released_at' => optional($run->released_at)->format('Y-m-d h:i a'),
            'recipient_list_exposed' => false,
            'recipient_export_available' => false,
        ];
    }

    protected function activeReleasedRun(int $draftId): ?CrmCampaignDispatchRun
    {
        return CrmCampaignDispatchRun::query()
            ->where('crm_campaign_draft_id', $draftId)
            ->where('status', 'released')
            ->where('active_slot', 1)
            ->whereNull('claimed_execution_batch_id')
            ->first();
    }

    protected function activeExecutionBatch(int $draftId): ?CrmCampaignDispatchExecutionBatch
    {
        $query = CrmCampaignDispatchExecutionBatch::query()
            ->where('crm_campaign_draft_id', $draftId)
            ->where('status', 'prepared')
            ->where('active_slot', 1);
        if ($this->hasUserStorage()) {
            $query->with(['claimant:id,name', 'canceller:id,name']);
        }

        return $query->first();
    }

    protected function hasActiveDispatchAttempt(int $batchId): bool
    {
        $table = 'crm_campaign_dispatch_attempts';
        foreach (['crm_campaign_dispatch_execution_batch_id', 'status', 'active_slot'] as $column) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
                return false;
            }
        }

        return DB::table($table)
            ->where('crm_campaign_dispatch_execution_batch_id', $batchId)
            ->whereIn('status', ['prepared', 'processing'])
            ->where('active_slot', 1)
            ->exists();
    }

    protected function hasStartedDispatchAttempt(int $batchId): bool
    {
        $attemptTable = 'crm_campaign_dispatch_attempts';
        if (Schema::hasTable($attemptTable)
            && Schema::hasColumn($attemptTable, 'crm_campaign_dispatch_execution_batch_id')
            && Schema::hasColumn($attemptTable, 'started_at')
            && DB::table($attemptTable)
                ->where('crm_campaign_dispatch_execution_batch_id', $batchId)
                ->whereNotNull('started_at')
                ->exists()) {
            return true;
        }

        $eventTable = 'crm_campaign_dispatch_recipient_attempt_events';
        return Schema::hasTable($eventTable)
            && Schema::hasColumn($eventTable, 'crm_campaign_dispatch_execution_batch_id')
            && Schema::hasColumn($eventTable, 'event_type')
            && DB::table($eventTable)
                ->where('crm_campaign_dispatch_execution_batch_id', $batchId)
                ->where('event_type', 'request_started')
                ->exists();
    }

    protected function freshWithRelations(CrmCampaignDispatchExecutionBatch $batch): CrmCampaignDispatchExecutionBatch
    {
        $batch = $batch->fresh();
        if ($this->hasUserStorage()) {
            $batch->load(['claimant:id,name', 'canceller:id,name']);
        }

        return $batch;
    }

    protected function ensureReady(array $eligibility): void
    {
        if (!empty($eligibility['ready_for_claim'])) {
            return;
        }

        $failed = collect($eligibility['checks'] ?? [])
            ->filter(fn (array $check) => empty($check['passed']))
            ->pluck('label')
            ->filter()
            ->implode(', ');

        $this->throwValidation('dispatch_execution_batch', 'Execution-batch claim failed integrity verification' . ($failed !== '' ? ': ' . $failed . '.' : '.'));
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
            throw new AuthorizationException('You do not have permission to perform this CRM campaign dispatch execution-batch action.');
        }
    }

    protected function can(User $actor, string $action): bool
    {
        return $this->permissionService->userCan($actor, self::PERMISSION_KEY, $action);
    }

    protected function recordActivity(string $type, CrmCampaignDispatchExecutionBatch $batch, int $actorId, string $subject, array $extraMetadata = []): void
    {
        $this->activityService->recordOrFail([
            'product_website_id' => $batch->product_website_id,
            'activity_type' => $type,
            'subject' => $subject,
            'description' => $subject . ' for CRM campaign draft #' . $batch->crm_campaign_draft_id,
            'source_module' => 'crm_campaign_dispatch_execution_batches',
            'source_id' => $batch->id,
            'performed_by' => $actorId,
            'metadata' => array_merge([
                'crm_campaign_draft_id' => (int) $batch->crm_campaign_draft_id,
                'crm_campaign_dispatch_preparation_id' => (int) $batch->crm_campaign_dispatch_preparation_id,
                'crm_campaign_dispatch_run_id' => (int) $batch->crm_campaign_dispatch_run_id,
                'status' => $batch->status,
                'channel' => $batch->channel,
                'frozen_recipient_count' => (int) $batch->frozen_recipient_count,
                'recipient_list_exposed' => false,
                'recipient_export_available' => false,
                'provider_payload_available' => false,
                'send_available' => false,
                'execute_available' => false,
                'schedule_available' => false,
            ], $extraMetadata),
        ]);
    }

    protected function ensureStorage(): void
    {
        $tables = [
            'crm_campaign_dispatch_preparations' => [
                'id', 'product_website_id', 'crm_campaign_draft_id', 'status', 'active_slot', 'channel',
                'approved_snapshot_signature', 'approved_snapshot_signature_version', 'approved_snapshot_at',
                'approved_recipient_set_signature', 'approved_recipient_set_signature_version', 'approved_recipient_count',
                'frozen_recipient_set_signature', 'frozen_recipient_set_signature_version', 'frozen_recipient_count',
                'prepared_by', 'released_dispatch_run_id',
            ],
            'crm_campaign_dispatch_recipients' => [
                'id', 'product_website_id', 'crm_campaign_draft_id', 'crm_campaign_dispatch_preparation_id',
                'customer_id', 'channel', 'destination_ciphertext', 'destination_hash',
            ],
            'crm_campaign_dispatch_runs' => [
                'id', 'product_website_id', 'crm_campaign_draft_id', 'crm_campaign_dispatch_preparation_id',
                'status', 'active_slot', 'channel', 'approved_snapshot_signature', 'approved_snapshot_signature_version',
                'approved_snapshot_at', 'approved_recipient_set_signature', 'approved_recipient_set_signature_version',
                'approved_recipient_count', 'frozen_recipient_set_signature', 'frozen_recipient_set_signature_version',
                'frozen_recipient_count', 'run_integrity_signature', 'run_integrity_signature_version', 'released_by',
                'released_at', 'claimed_execution_batch_id', 'claimed_by', 'claimed_at',
            ],
            'crm_campaign_dispatch_run_recipients' => [
                'id', 'product_website_id', 'crm_campaign_draft_id', 'crm_campaign_dispatch_preparation_id',
                'crm_campaign_dispatch_run_id', 'crm_campaign_dispatch_recipient_id', 'customer_id', 'channel',
                'destination_hash', 'status', 'created_at',
            ],
            'crm_campaign_dispatch_execution_batches' => [
                'id', 'product_website_id', 'crm_campaign_draft_id', 'crm_campaign_dispatch_preparation_id',
                'crm_campaign_dispatch_run_id', 'status', 'active_slot', 'channel', 'approved_snapshot_signature',
                'approved_snapshot_signature_version', 'approved_snapshot_at', 'approved_recipient_set_signature',
                'approved_recipient_set_signature_version', 'approved_recipient_count', 'frozen_recipient_set_signature',
                'frozen_recipient_set_signature_version', 'frozen_recipient_count', 'run_integrity_signature',
                'run_integrity_signature_version', 'subject_snapshot', 'message_body_snapshot', 'batch_idempotency_key',
                'execution_integrity_signature', 'execution_integrity_signature_version', 'claimed_by', 'claimed_at',
                'cancelled_by', 'cancelled_at', 'cancellation_reason', 'metadata_json', 'created_at', 'updated_at',
            ],
            'crm_campaign_dispatch_execution_recipients' => [
                'id', 'product_website_id', 'crm_campaign_draft_id', 'crm_campaign_dispatch_preparation_id',
                'crm_campaign_dispatch_run_id', 'crm_campaign_dispatch_execution_batch_id',
                'crm_campaign_dispatch_run_recipient_id', 'customer_id', 'channel', 'destination_hash',
                'idempotency_key', 'status', 'created_at',
            ],
        ];

        foreach ($tables as $table => $columns) {
            if (!Schema::hasTable($table)) {
                $this->throwValidation('dispatch_execution_batch', 'CRM campaign dispatch execution storage is unavailable. Run application migrations first.');
            }
            foreach ($columns as $column) {
                if (!Schema::hasColumn($table, $column)) {
                    $this->throwValidation('dispatch_execution_batch', 'CRM campaign dispatch execution storage is incomplete. Run application migrations first.');
                }
            }
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

    protected function runSignatureKey(): string
    {
        return hash('sha256', 'crm-campaign-dispatch-run-signature|' . $this->applicationKey(), true);
    }

    protected function executionSignatureKey(): string
    {
        return hash('sha256', 'crm-campaign-dispatch-execution-signature|' . $this->applicationKey(), true);
    }

    protected function applicationKey(): string
    {
        $key = trim((string) config('app.key'));
        if ($key === '') {
            $this->throwValidation('dispatch_execution_batch', 'Application encryption key is unavailable. Execution-batch claim cannot continue safely.');
        }

        return $key;
    }

    protected function encoded(array $payload): string
    {
        return json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    protected function firstValidationMessage(ValidationException $exception): string
    {
        foreach ($exception->errors() as $messages) {
            foreach ((array) $messages as $message) {
                return (string) $message;
            }
        }

        return 'Campaign dispatch execution validation failed.';
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

    protected function logFailure(string $operation, int $draftId, ?int $runId, ?int $batchId, int $actorId, \Throwable $exception): void
    {
        Log::error('CRM campaign dispatch execution-batch write failed', [
            'operation' => $operation,
            'campaign_draft_id' => $draftId,
            'dispatch_run_id' => $runId,
            'execution_batch_id' => $batchId,
            'actor_id' => $actorId,
            'error' => $exception->getMessage(),
        ]);
    }
}
