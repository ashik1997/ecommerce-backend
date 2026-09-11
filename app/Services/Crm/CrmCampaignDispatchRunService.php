<?php

namespace App\Services\Crm;

use App\Models\Crm\CrmCampaignDispatchPreparation;
use App\Models\Crm\CrmCampaignDispatchRecipient;
use App\Models\Crm\CrmCampaignDispatchRun;
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

class CrmCampaignDispatchRunService
{
    public const STATUSES = ['released', 'claimed', 'cancelled'];

    public const RECIPIENT_STATUSES = ['released'];

    public const RUN_INTEGRITY_SIGNATURE_VERSION = 1;

    protected const PERMISSION_KEY = 'crm.campaign-drafts.release-dispatch';

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
        $preparation = $this->activePreparation((int) $draft->id);
        $eligibility = $this->releaseEligibility($draft, $preparation);
        unset($eligibility['recipient_rows']);

        return array_merge($eligibility, [
            'active_preparation' => $preparation ? $this->preparationPayload($preparation) : null,
            'active_run' => ($activeRun = $this->activeRun((int) $draft->id)) ? $this->payload($activeRun, $actor) : null,
            'recipient_list_exposed' => false,
            'recipient_export_available' => false,
            'provider_payload_available' => false,
            'send_available' => false,
            'execute_available' => false,
            'schedule_available' => false,
            'read_only' => true,
            'notice' => 'This release preview is aggregate only. Releasing creates a provider-neutral immutable run ledger. It does not send, execute, schedule, queue, export, or disclose recipient destinations.',
        ]);
    }

    public function history(int $draftId, User $actor): array
    {
        $this->ensureStorage();
        $this->authorize($actor, 'read');
        $draft = $this->campaignDraftService->findVisible($draftId, $actor);

        $query = CrmCampaignDispatchRun::query()
            ->where('crm_campaign_draft_id', $draft->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($this->hasUserStorage()) {
            $query->with(['releaser:id,name', 'claimant:id,name', 'canceller:id,name']);
        }

        return $query->get()
            ->map(fn (CrmCampaignDispatchRun $run) => $this->payload($run, $actor))
            ->values()
            ->all();
    }

    public function release(int $draftId, int $preparationId, User $actor): CrmCampaignDispatchRun
    {
        $this->ensureStorage();
        $this->authorize($actor, 'create');
        $this->campaignDraftService->findVisible($draftId, $actor);

        try {
            $run = DB::transaction(function () use ($draftId, $preparationId, $actor) {
                $draft = CrmCampaignDraft::query()->lockForUpdate()->findOrFail($draftId);
                $this->ensureReadable($draft, $actor);

                $preparation = CrmCampaignDispatchPreparation::query()
                    ->where('crm_campaign_draft_id', $draft->id)
                    ->lockForUpdate()
                    ->findOrFail($preparationId);

                $eligibility = $this->releaseEligibility($draft, $preparation, true);
                $this->ensureReady($eligibility);
                $releasedAt = now();
                $recipientRows = $eligibility['recipient_rows'];

                $run = CrmCampaignDispatchRun::create([
                    'product_website_id' => $draft->product_website_id,
                    'crm_campaign_draft_id' => $draft->id,
                    'crm_campaign_dispatch_preparation_id' => $preparation->id,
                    'status' => 'released',
                    'active_slot' => 1,
                    'channel' => $preparation->channel,
                    'approved_snapshot_signature' => $preparation->approved_snapshot_signature,
                    'approved_snapshot_signature_version' => $preparation->approved_snapshot_signature_version,
                    'approved_snapshot_at' => $preparation->approved_snapshot_at,
                    'approved_recipient_set_signature' => $preparation->approved_recipient_set_signature,
                    'approved_recipient_set_signature_version' => $preparation->approved_recipient_set_signature_version,
                    'approved_recipient_count' => $preparation->approved_recipient_count,
                    'frozen_recipient_set_signature' => $preparation->frozen_recipient_set_signature,
                    'frozen_recipient_set_signature_version' => $preparation->frozen_recipient_set_signature_version,
                    'frozen_recipient_count' => $preparation->frozen_recipient_count,
                    'released_by' => $actor->id,
                    'released_at' => $releasedAt,
                    'metadata_json' => [
                        'provider_neutral' => true,
                        'recipient_list_exposed' => false,
                        'recipient_export_available' => false,
                        'provider_payload_available' => false,
                        'send_available' => false,
                        'execute_available' => false,
                        'schedule_available' => false,
                    ],
                ]);

                $runRecipientRows = collect($recipientRows)->map(fn (array $row) => [
                    'product_website_id' => $draft->product_website_id,
                    'crm_campaign_draft_id' => $draft->id,
                    'crm_campaign_dispatch_preparation_id' => $preparation->id,
                    'crm_campaign_dispatch_run_id' => $run->id,
                    'crm_campaign_dispatch_recipient_id' => $row['id'],
                    'customer_id' => $row['customer_id'],
                    'channel' => $preparation->channel,
                    'destination_hash' => $row['destination_hash'],
                    'status' => 'released',
                    'created_at' => $releasedAt,
                ])->all();

                foreach (array_chunk($runRecipientRows, 500) as $chunk) {
                    DB::table('crm_campaign_dispatch_run_recipients')->insert($chunk);
                }

                $run->forceFill([
                    'run_integrity_signature' => $this->runIntegritySignature($run, $recipientRows),
                    'run_integrity_signature_version' => self::RUN_INTEGRITY_SIGNATURE_VERSION,
                ])->save();

                $preparation->forceFill([
                    'active_slot' => null,
                    'released_dispatch_run_id' => $run->id,
                    'released_by' => $actor->id,
                    'released_at' => $releasedAt,
                ])->save();

                $this->recordActivity('crm_campaign_dispatch_released', $run, $actor->id, 'CRM campaign dispatch run released', [
                    'crm_campaign_dispatch_preparation_id' => (int) $preparation->id,
                    'frozen_recipient_count' => (int) $run->frozen_recipient_count,
                    'provider_neutral' => true,
                ]);

                return $run;
            });
        } catch (\Throwable $exception) {
            $this->logFailure('release', $draftId, $preparationId, null, $actor->id, $exception);
            if ($this->isUniqueConstraintViolation($exception)) {
                $this->throwValidation('dispatch_run', 'This dispatch preparation has already been released or another active dispatch run already exists for the campaign. Refresh the aggregate preview.');
            }
            throw $exception;
        }

        return $this->freshWithRelations($run);
    }

    public function cancel(int $draftId, int $runId, array $data, User $actor): CrmCampaignDispatchRun
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
            $run = DB::transaction(function () use ($draftId, $runId, $reason, $actor) {
                $draft = CrmCampaignDraft::query()->lockForUpdate()->findOrFail($draftId);
                $this->ensureReadable($draft, $actor);

                $run = CrmCampaignDispatchRun::query()
                    ->where('crm_campaign_draft_id', $draft->id)
                    ->lockForUpdate()
                    ->findOrFail($runId);

                if ($run->status !== 'released' || (int) $run->active_slot !== 1) {
                    $this->throwValidation('dispatch_run', 'Only an active released provider-neutral dispatch run can be cancelled.');
                }

                $run->forceFill([
                    'status' => 'cancelled',
                    'active_slot' => null,
                    'cancelled_by' => $actor->id,
                    'cancelled_at' => now(),
                    'cancellation_reason' => $reason,
                ])->save();
                $run = $run->refresh();

                $this->recordActivity('crm_campaign_dispatch_run_cancelled', $run, $actor->id, 'CRM campaign dispatch run cancelled', [
                    'reason_present' => true,
                    'provider_neutral' => true,
                ]);

                return $run;
            });
        } catch (\Throwable $exception) {
            $this->logFailure('cancel', $draftId, null, $runId, $actor->id, $exception);
            throw $exception;
        }

        return $this->freshWithRelations($run);
    }

    public function payload(CrmCampaignDispatchRun $run, User $actor): array
    {
        $canCancel = $run->status === 'released'
            && (int) $run->active_slot === 1
            && $this->can($actor, 'update');

        return [
            'id' => (int) $run->id,
            'crm_campaign_draft_id' => (int) $run->crm_campaign_draft_id,
            'crm_campaign_dispatch_preparation_id' => (int) $run->crm_campaign_dispatch_preparation_id,
            'status' => $run->status,
            'channel' => $run->channel,
            'approved_recipient_count' => (int) $run->approved_recipient_count,
            'frozen_recipient_count' => (int) $run->frozen_recipient_count,
            'run_integrity_seal_recorded' => $this->isValidSignature($run->run_integrity_signature)
                && (int) $run->run_integrity_signature_version === self::RUN_INTEGRITY_SIGNATURE_VERSION,
            'released_by' => $run->released_by ? (int) $run->released_by : null,
            'released_by_name' => $run->relationLoaded('releaser') ? optional($run->releaser)->name : null,
            'released_at' => optional($run->released_at)->format('Y-m-d h:i a'),
            'claimed_execution_batch_id' => $run->claimed_execution_batch_id ? (int) $run->claimed_execution_batch_id : null,
            'claimed_by_name' => $run->relationLoaded('claimant') ? optional($run->claimant)->name : null,
            'claimed_at' => optional($run->claimed_at)->format('Y-m-d h:i a'),
            'cancelled_by_name' => $run->relationLoaded('canceller') ? optional($run->canceller)->name : null,
            'cancelled_at' => optional($run->cancelled_at)->format('Y-m-d h:i a'),
            'cancellation_reason' => $run->cancellation_reason,
            'provider_neutral' => true,
            'recipient_list_exposed' => false,
            'recipient_export_available' => false,
            'provider_payload_available' => false,
            'send_available' => false,
            'execute_available' => false,
            'schedule_available' => false,
            'can_cancel' => $canCancel,
            'created_at' => optional($run->created_at)->format('Y-m-d h:i a'),
        ];
    }

    protected function releaseEligibility(CrmCampaignDraft $draft, ?CrmCampaignDispatchPreparation $preparation, bool $lockRows = false): array
    {
        $checks = [];
        $addCheck = function (string $key, string $label, bool $passed, string $message) use (&$checks) {
            $checks[] = compact('key', 'label', 'passed', 'message');
        };

        $approved = $draft->status === 'approved';
        $addCheck('approved_lifecycle', 'Approved lifecycle', $approved, $approved ? 'Campaign lifecycle is approved.' : 'Only an approved campaign can release a provider-neutral dispatch run.');

        $ledgerChannel = CrmCampaignDraftService::isDispatchLedgerChannel($draft->planned_channel)
            && $preparation !== null
            && CrmCampaignDraftService::isDispatchLedgerChannel($preparation->channel);
        $addCheck('stage31_ledger_channel', 'Stage 31 dispatch-ledger channel boundary', $ledgerChannel, $ledgerChannel ? 'BulkSMSBD SMS and Email may advance through immutable dispatch ledgers. Email remains non-sending.' : 'Dispatch-run release is disabled for newsletter, WhatsApp, and other legacy campaign channels.');

        $approvalIntegrity = false;
        $approvalIntegrityMessage = null;
        try {
            $this->campaignDraftService->assertApprovedSnapshotIntegrity($draft);
            $approvalIntegrity = true;
        } catch (ValidationException $exception) {
            $approvalIntegrityMessage = $this->firstValidationMessage($exception);
        }
        $addCheck('approved_planning_integrity', 'Approved planning integrity', $approvalIntegrity, $approvalIntegrity ? 'Approved campaign planning seal passed integrity verification.' : ($approvalIntegrityMessage ?: 'Approved campaign planning integrity could not be verified.'));

        $activePreparation = $preparation !== null
            && $preparation->status === 'prepared'
            && (int) $preparation->active_slot === 1;
        $addCheck('active_preparation', 'Active frozen preparation', $activePreparation, $activePreparation ? 'An active Stage 22 frozen preparation is available.' : 'Release requires an active prepared Stage 22 snapshot. Cancelled, invalidated, consumed, or missing preparations cannot be released.');

        $unconsumed = $preparation !== null && empty($preparation->released_dispatch_run_id);
        $addCheck('preparation_unconsumed', 'One-way preparation boundary', $unconsumed, $unconsumed ? 'The preparation has not been consumed by an earlier release.' : 'The preparation has already been consumed and cannot be released again.');

        $channelMatches = $preparation !== null && trim((string) $preparation->channel) !== '' && hash_equals((string) $draft->planned_channel, (string) $preparation->channel);
        $addCheck('channel_match', 'Frozen channel integrity', $channelMatches, $channelMatches ? 'The frozen preparation channel matches the approved campaign channel.' : 'The frozen preparation channel no longer matches the approved campaign channel.');

        $approvalMetadataMatches = $preparation !== null && $this->preparationApprovalMetadataMatches($draft, $preparation);
        $addCheck('preparation_approval_metadata', 'Preparation approval metadata', $approvalMetadataMatches, $approvalMetadataMatches ? 'Preparation approval metadata exactly matches the active approved campaign seal.' : 'Preparation approval metadata is missing, stale, or inconsistent with the approved campaign.');

        $websiteScopeMatches = $preparation !== null && $this->sameNullableInteger($draft->product_website_id, $preparation->product_website_id);
        $addCheck('website_scope_match', 'Website scope', $websiteScopeMatches, $websiteScopeMatches ? 'The campaign and frozen preparation remain in the same website scope.' : 'The frozen preparation website scope no longer matches the campaign.');

        $recipientRows = [];
        $frozenRowsVerified = false;
        $frozenRowsMessage = 'Frozen preparation rows are unavailable.';
        if ($preparation !== null) {
            [$frozenRowsVerified, $frozenRowsMessage, $recipientRows] = $this->verifiedFrozenRows($draft, $preparation, $lockRows);
        }
        $addCheck('frozen_recipient_rows', 'Frozen recipient-row integrity', $frozenRowsVerified, $frozenRowsMessage);

        $previousRunExists = $preparation !== null && CrmCampaignDispatchRun::query()
            ->where('crm_campaign_dispatch_preparation_id', $preparation->id)
            ->exists();
        $addCheck('no_previous_preparation_run', 'Preparation duplicate-run prevention', !$previousRunExists, $previousRunExists ? 'This frozen preparation already has a dispatch-run ledger.' : 'No previous dispatch-run ledger exists for this preparation.');

        $activeRun = $this->activeRun((int) $draft->id);
        $addCheck('no_active_campaign_run', 'Campaign active-run prevention', $activeRun === null, $activeRun === null ? 'No active provider-neutral dispatch run exists for this campaign.' : 'An active released dispatch run already exists. Cancel it before creating a new preparation and release.');

        $activeExecutionBatchExists = $this->hasActiveExecutionBatch((int) $draft->id);
        $addCheck('no_active_execution_batch', 'Execution-batch prevention', !$activeExecutionBatchExists, !$activeExecutionBatchExists ? 'No active provider-neutral execution batch exists for this campaign.' : 'Cancel the active provider-neutral execution batch before creating a fresh release chain.');

        $ready = collect($checks)->every(fn (array $check) => $check['passed']);

        return [
            'draft_id' => (int) $draft->id,
            'draft_name' => $draft->name,
            'status' => $draft->status,
            'channel' => $preparation?->channel ?: $draft->planned_channel,
            'approved_recipient_count' => is_numeric($draft->approved_recipient_count) ? (int) $draft->approved_recipient_count : null,
            'frozen_recipient_count' => $preparation && is_numeric($preparation->frozen_recipient_count) ? (int) $preparation->frozen_recipient_count : null,
            'ready_for_release' => $ready,
            'ready_for_release_label' => $ready ? 'Eligible for provider-neutral dispatch-run release' : 'Not eligible for dispatch-run release',
            'checks' => $checks,
            'recipient_rows' => $recipientRows,
        ];
    }

    protected function verifiedFrozenRows(CrmCampaignDraft $draft, CrmCampaignDispatchPreparation $preparation, bool $lockRows): array
    {
        $storedSignature = strtolower(trim((string) $preparation->frozen_recipient_set_signature));
        $storedVersion = $preparation->frozen_recipient_set_signature_version;
        $storedCount = $preparation->frozen_recipient_count;
        if (!$this->isValidSignature($storedSignature)
            || !is_numeric($storedVersion)
            || (int) $storedVersion !== CrmCampaignRecipientSnapshotService::FROZEN_RECIPIENT_SET_SIGNATURE_VERSION
            || !is_numeric($storedCount)
            || (int) $storedCount <= 0) {
            return [false, 'Frozen preparation integrity metadata is missing or unsupported. Create a fresh preparation.', []];
        }

        $query = CrmCampaignDispatchRecipient::query()
            ->where('crm_campaign_dispatch_preparation_id', $preparation->id)
            ->orderBy('id');
        if ($lockRows) {
            $query->lockForUpdate();
        }

        $rows = $query->get([
            'id', 'product_website_id', 'crm_campaign_draft_id', 'crm_campaign_dispatch_preparation_id',
            'customer_id', 'channel', 'destination_hash',
        ])->map(fn (CrmCampaignDispatchRecipient $recipient) => [
            'id' => (int) $recipient->id,
            'product_website_id' => $recipient->product_website_id,
            'crm_campaign_draft_id' => (int) $recipient->crm_campaign_draft_id,
            'crm_campaign_dispatch_preparation_id' => (int) $recipient->crm_campaign_dispatch_preparation_id,
            'customer_id' => (int) $recipient->customer_id,
            'channel' => (string) $recipient->channel,
            'destination_hash' => strtolower(trim((string) $recipient->destination_hash)),
        ])->all();

        if (count($rows) !== (int) $storedCount) {
            return [false, 'Frozen preparation recipient-row count no longer matches the recorded header count.', []];
        }
        if ((int) $preparation->approved_recipient_count !== (int) $storedCount) {
            return [false, 'Frozen preparation recipient count no longer matches the approval-time recipient count.', []];
        }

        $seenCustomers = [];
        $seenDestinations = [];
        foreach ($rows as $row) {
            if ($row['id'] <= 0
                || !$this->sameNullableInteger($row['product_website_id'], $preparation->product_website_id)
                || $row['crm_campaign_draft_id'] !== (int) $draft->id
                || $row['crm_campaign_dispatch_preparation_id'] !== (int) $preparation->id
                || $row['customer_id'] <= 0
                || !hash_equals((string) $preparation->channel, $row['channel'])
                || !$this->isValidSignature($row['destination_hash'])
                || isset($seenCustomers[$row['customer_id']])
                || isset($seenDestinations[$row['destination_hash']])) {
                return [false, 'Frozen preparation recipient rows failed identity, channel, or duplicate verification.', []];
            }
            $seenCustomers[$row['customer_id']] = true;
            $seenDestinations[$row['destination_hash']] = true;
        }

        $expected = $this->recipientSnapshotService->frozenRecipientSetSignature(
            (int) $draft->id,
            (int) $preparation->id,
            (string) $preparation->channel,
            $rows
        );
        if (!hash_equals($expected, $storedSignature)) {
            return [false, 'Frozen preparation recipient rows failed integrity-seal verification.', []];
        }

        return [true, number_format((int) $storedCount) . ' immutable frozen recipient rows passed integrity verification.', $rows];
    }

    protected function preparationApprovalMetadataMatches(CrmCampaignDraft $draft, CrmCampaignDispatchPreparation $preparation): bool
    {
        return $this->sameSignature($draft->approved_snapshot_signature, $preparation->approved_snapshot_signature)
            && (int) $draft->approved_snapshot_signature_version === (int) $preparation->approved_snapshot_signature_version
            && $this->timestampString($draft->approved_snapshot_at) === $this->timestampString($preparation->approved_snapshot_at)
            && $this->sameSignature($draft->approved_recipient_set_signature, $preparation->approved_recipient_set_signature)
            && (int) $draft->approved_recipient_set_signature_version === CrmCampaignRecipientSnapshotService::RECIPIENT_SET_SIGNATURE_VERSION
            && (int) $draft->approved_recipient_set_signature_version === (int) $preparation->approved_recipient_set_signature_version
            && (int) $draft->approved_recipient_count > 0
            && (int) $draft->approved_recipient_count === (int) $preparation->approved_recipient_count;
    }

    protected function preparationPayload(CrmCampaignDispatchPreparation $preparation): array
    {
        return [
            'id' => (int) $preparation->id,
            'crm_campaign_draft_id' => (int) $preparation->crm_campaign_draft_id,
            'status' => $preparation->status,
            'channel' => $preparation->channel,
            'frozen_recipient_count' => (int) $preparation->frozen_recipient_count,
            'prepared_by' => $preparation->prepared_by ? (int) $preparation->prepared_by : null,
            'prepared_at' => optional($preparation->prepared_at)->format('Y-m-d h:i a'),
            'recipient_list_exposed' => false,
            'recipient_export_available' => false,
        ];
    }

    protected function activePreparation(int $draftId): ?CrmCampaignDispatchPreparation
    {
        return CrmCampaignDispatchPreparation::query()
            ->where('crm_campaign_draft_id', $draftId)
            ->where('status', 'prepared')
            ->where('active_slot', 1)
            ->whereNull('released_dispatch_run_id')
            ->first();
    }

    protected function activeRun(int $draftId): ?CrmCampaignDispatchRun
    {
        $query = CrmCampaignDispatchRun::query()
            ->where('crm_campaign_draft_id', $draftId)
            ->where('status', 'released')
            ->where('active_slot', 1);
        if ($this->hasUserStorage()) {
            $query->with(['releaser:id,name', 'claimant:id,name', 'canceller:id,name']);
        }

        return $query->first();
    }

    protected function freshWithRelations(CrmCampaignDispatchRun $run): CrmCampaignDispatchRun
    {
        $run = $run->fresh();
        if ($this->hasUserStorage()) {
            $run->load(['releaser:id,name', 'claimant:id,name', 'canceller:id,name']);
        }

        return $run;
    }


    protected function hasActiveExecutionBatch(int $draftId): bool
    {
        $table = 'crm_campaign_dispatch_execution_batches';
        foreach (['crm_campaign_draft_id', 'status', 'active_slot'] as $column) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
                return false;
            }
        }

        return DB::table($table)
            ->where('crm_campaign_draft_id', $draftId)
            ->where('status', 'prepared')
            ->where('active_slot', 1)
            ->exists();
    }

    protected function ensureReady(array $eligibility): void
    {
        if (!empty($eligibility['ready_for_release'])) {
            return;
        }

        $failed = collect($eligibility['checks'] ?? [])
            ->filter(fn (array $check) => empty($check['passed']))
            ->pluck('label')
            ->filter()
            ->implode(', ');

        $this->throwValidation('dispatch_run', 'Dispatch-run release failed integrity verification' . ($failed !== '' ? ': ' . $failed . '.' : '.'));
    }

    protected function runIntegritySignature(CrmCampaignDispatchRun $run, array $rows): string
    {
        $payload = [
            'signature_version' => self::RUN_INTEGRITY_SIGNATURE_VERSION,
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
                'crm_campaign_dispatch_recipient_id' => (int) $row['id'],
                'customer_id' => (int) $row['customer_id'],
                'destination_hash' => strtolower((string) $row['destination_hash']),
            ])->sortBy(fn (array $row) => sprintf('%020d|%020d|%s', $row['crm_campaign_dispatch_recipient_id'], $row['customer_id'], $row['destination_hash']))->values()->all(),
        ];

        return hash_hmac('sha256', $this->encoded($payload), $this->signatureKey());
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
            throw new AuthorizationException('You do not have permission to perform this CRM campaign dispatch-run action.');
        }
    }

    protected function can(User $actor, string $action): bool
    {
        return $this->permissionService->userCan($actor, self::PERMISSION_KEY, $action);
    }

    protected function recordActivity(string $type, CrmCampaignDispatchRun $run, int $actorId, string $subject, array $extraMetadata = []): void
    {
        $this->activityService->recordOrFail([
            'product_website_id' => $run->product_website_id,
            'activity_type' => $type,
            'subject' => $subject,
            'description' => $subject . ' for CRM campaign draft #' . $run->crm_campaign_draft_id,
            'source_module' => 'crm_campaign_dispatch_runs',
            'source_id' => $run->id,
            'performed_by' => $actorId,
            'metadata' => array_merge([
                'crm_campaign_draft_id' => (int) $run->crm_campaign_draft_id,
                'crm_campaign_dispatch_preparation_id' => (int) $run->crm_campaign_dispatch_preparation_id,
                'status' => $run->status,
                'channel' => $run->channel,
                'frozen_recipient_count' => (int) $run->frozen_recipient_count,
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
                'prepared_by', 'prepared_at', 'released_dispatch_run_id', 'released_by', 'released_at',
            ],
            'crm_campaign_dispatch_recipients' => [
                'id', 'product_website_id', 'crm_campaign_draft_id', 'crm_campaign_dispatch_preparation_id',
                'customer_id', 'channel', 'destination_hash',
            ],
            'crm_campaign_dispatch_runs' => [
                'id', 'product_website_id', 'crm_campaign_draft_id', 'crm_campaign_dispatch_preparation_id',
                'status', 'active_slot', 'channel', 'approved_snapshot_signature', 'approved_snapshot_signature_version',
                'approved_snapshot_at', 'approved_recipient_set_signature', 'approved_recipient_set_signature_version',
                'approved_recipient_count', 'frozen_recipient_set_signature', 'frozen_recipient_set_signature_version',
                'frozen_recipient_count', 'run_integrity_signature', 'run_integrity_signature_version',
                'released_by', 'released_at', 'claimed_execution_batch_id', 'claimed_by', 'claimed_at',
                'cancelled_by', 'cancelled_at', 'cancellation_reason',
                'metadata_json', 'created_at', 'updated_at',
            ],
            'crm_campaign_dispatch_run_recipients' => [
                'id', 'product_website_id', 'crm_campaign_draft_id', 'crm_campaign_dispatch_preparation_id',
                'crm_campaign_dispatch_run_id', 'crm_campaign_dispatch_recipient_id', 'customer_id', 'channel',
                'destination_hash', 'status', 'created_at',
            ],
        ];

        foreach ($tables as $table => $columns) {
            if (!Schema::hasTable($table)) {
                $this->throwValidation('dispatch_run', 'CRM campaign dispatch-run storage is unavailable. Run application migrations first.');
            }
            foreach ($columns as $column) {
                if (!Schema::hasColumn($table, $column)) {
                    $this->throwValidation('dispatch_run', 'CRM campaign dispatch-run storage is incomplete. Run application migrations first.');
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

    protected function signatureKey(): string
    {
        $key = trim((string) config('app.key'));
        if ($key === '') {
            $this->throwValidation('dispatch_run', 'Application encryption key is unavailable. Dispatch-run release cannot continue safely.');
        }

        return hash('sha256', 'crm-campaign-dispatch-run-signature|' . $key, true);
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

        return 'Campaign dispatch-run validation failed.';
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

    protected function logFailure(string $operation, int $draftId, ?int $preparationId, ?int $runId, int $actorId, \Throwable $exception): void
    {
        Log::error('CRM campaign dispatch-run write failed', [
            'operation' => $operation,
            'campaign_draft_id' => $draftId,
            'dispatch_preparation_id' => $preparationId,
            'dispatch_run_id' => $runId,
            'actor_id' => $actorId,
            'error' => $exception->getMessage(),
        ]);
    }
}
