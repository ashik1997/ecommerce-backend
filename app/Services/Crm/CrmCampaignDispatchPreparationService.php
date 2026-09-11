<?php

namespace App\Services\Crm;

use App\Models\Crm\CrmCampaignDispatchPreparation;
use App\Models\Crm\CrmCampaignDraft;
use App\Models\User;
use App\Services\RoleSidebarPermissionService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CrmCampaignDispatchPreparationService
{
    public const STATUSES = ['prepared', 'invalidated', 'cancelled'];

    protected const PERMISSION_KEY = 'crm.campaign-drafts.prepare-dispatch';

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

        $payload = $this->readinessPayload($draft, $actor, false);
        unset($payload['resolved_recipient_set']);

        return $payload;
    }

    public function history(int $draftId, User $actor): array
    {
        $this->ensureStorage();
        $this->authorize($actor, 'read');
        $draft = $this->campaignDraftService->findVisible($draftId, $actor);

        $query = CrmCampaignDispatchPreparation::query()
            ->where('crm_campaign_draft_id', $draft->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($this->hasUserStorage()) {
            $query->with(['preparer:id,name', 'releaser:id,name', 'invalidator:id,name', 'canceller:id,name']);
        }

        return $query->get()->map(fn (CrmCampaignDispatchPreparation $preparation) => $this->payload($preparation, $actor))->values()->all();
    }

    public function prepare(int $draftId, User $actor): CrmCampaignDispatchPreparation
    {
        $this->ensureStorage();
        $this->authorize($actor, 'create');
        $this->campaignDraftService->findVisible($draftId, $actor);

        try {
            $preparation = DB::transaction(function () use ($draftId, $actor) {
                $draft = CrmCampaignDraft::query()->lockForUpdate()->findOrFail($draftId);
                $this->ensureReadable($draft, $actor);
                $readiness = $this->readinessPayload($draft, $actor, true);
                $this->ensureReady($readiness);
                $recipientSet = $readiness['resolved_recipient_set'];
                $preparedAt = now();

                $preparation = CrmCampaignDispatchPreparation::create([
                    'product_website_id' => $draft->product_website_id,
                    'crm_campaign_draft_id' => $draft->id,
                    'status' => 'prepared',
                    'active_slot' => 1,
                    'channel' => $recipientSet['channel'],
                    'approved_snapshot_signature' => $draft->approved_snapshot_signature,
                    'approved_snapshot_signature_version' => $draft->approved_snapshot_signature_version,
                    'approved_snapshot_at' => $draft->approved_snapshot_at,
                    'approved_recipient_set_signature' => $draft->approved_recipient_set_signature,
                    'approved_recipient_set_signature_version' => $draft->approved_recipient_set_signature_version,
                    'approved_recipient_count' => $draft->approved_recipient_count,
                    'frozen_recipient_count' => $recipientSet['recipient_count'],
                    'prepared_by' => $actor->id,
                    'prepared_at' => $preparedAt,
                    'metadata_json' => [
                        'recipient_list_exposed' => false,
                        'recipient_export_available' => false,
                        'send_available' => false,
                        'destination_summary' => $recipientSet['destination_summary'],
                    ],
                ]);

                $rows = collect($recipientSet['rows'])->map(fn (array $row) => [
                    'product_website_id' => $draft->product_website_id,
                    'crm_campaign_draft_id' => $draft->id,
                    'crm_campaign_dispatch_preparation_id' => $preparation->id,
                    'customer_id' => $row['customer_id'],
                    'channel' => $recipientSet['channel'],
                    'destination_ciphertext' => $row['destination_ciphertext'],
                    'destination_hash' => $row['destination_hash'],
                    'created_at' => $preparedAt,
                ])->all();

                foreach (array_chunk($rows, 500) as $chunk) {
                    DB::table('crm_campaign_dispatch_recipients')->insert($chunk);
                }

                $preparation->forceFill([
                    'frozen_recipient_set_signature' => $this->recipientSnapshotService->frozenRecipientSetSignature(
                        (int) $draft->id,
                        (int) $preparation->id,
                        $recipientSet['channel'],
                        $recipientSet['rows']
                    ),
                    'frozen_recipient_set_signature_version' => CrmCampaignRecipientSnapshotService::FROZEN_RECIPIENT_SET_SIGNATURE_VERSION,
                ])->save();

                $this->recordActivity('crm_campaign_dispatch_prepared', $preparation, $actor->id, 'CRM campaign dispatch preparation frozen', [
                    'approved_recipient_count' => (int) $draft->approved_recipient_count,
                    'frozen_recipient_count' => (int) $preparation->frozen_recipient_count,
                ]);

                return $preparation;
            });
        } catch (\Throwable $exception) {
            $this->logFailure('prepare', $draftId, null, $actor->id, $exception);
            throw $exception;
        }

        return $this->freshWithRelations($preparation);
    }

    public function cancel(int $draftId, int $preparationId, array $data, User $actor): CrmCampaignDispatchPreparation
    {
        return $this->transitionToTerminal($draftId, $preparationId, 'cancelled', $data['reason'] ?? null, $actor);
    }

    public function invalidate(int $draftId, int $preparationId, array $data, User $actor): CrmCampaignDispatchPreparation
    {
        return $this->transitionToTerminal($draftId, $preparationId, 'invalidated', $data['reason'] ?? null, $actor);
    }

    public function payload(CrmCampaignDispatchPreparation $preparation, User $actor): array
    {
        $canMutate = $preparation->status === 'prepared' && (int) $preparation->active_slot === 1 && $this->can($actor, 'update');

        return [
            'id' => (int) $preparation->id,
            'crm_campaign_draft_id' => (int) $preparation->crm_campaign_draft_id,
            'status' => $preparation->status,
            'channel' => $preparation->channel,
            'approved_recipient_count' => (int) $preparation->approved_recipient_count,
            'frozen_recipient_count' => (int) $preparation->frozen_recipient_count,
            'prepared_by' => $preparation->prepared_by ? (int) $preparation->prepared_by : null,
            'prepared_by_name' => $preparation->relationLoaded('preparer') ? optional($preparation->preparer)->name : null,
            'prepared_at' => optional($preparation->prepared_at)->format('Y-m-d h:i a'),
            'release_state' => $preparation->released_dispatch_run_id ? 'released' : $preparation->status,
            'released_dispatch_run_id' => $preparation->released_dispatch_run_id ? (int) $preparation->released_dispatch_run_id : null,
            'released_by_name' => $preparation->relationLoaded('releaser') ? optional($preparation->releaser)->name : null,
            'released_at' => optional($preparation->released_at)->format('Y-m-d h:i a'),
            'invalidated_by_name' => $preparation->relationLoaded('invalidator') ? optional($preparation->invalidator)->name : null,
            'invalidated_at' => optional($preparation->invalidated_at)->format('Y-m-d h:i a'),
            'invalidation_reason' => $preparation->invalidation_reason,
            'cancelled_by_name' => $preparation->relationLoaded('canceller') ? optional($preparation->canceller)->name : null,
            'cancelled_at' => optional($preparation->cancelled_at)->format('Y-m-d h:i a'),
            'cancellation_reason' => $preparation->cancellation_reason,
            'destination_summary' => $this->recipientSnapshotService->destinationSummary($preparation->channel),
            'recipient_list_exposed' => false,
            'recipient_export_available' => false,
            'send_available' => false,
            'can_cancel' => $canMutate,
            'can_invalidate' => $canMutate,
            'created_at' => optional($preparation->created_at)->format('Y-m-d h:i a'),
        ];
    }

    protected function readinessPayload(CrmCampaignDraft $draft, User $actor, bool $includeEncryptedDestinations): array
    {
        $preflight = $this->campaignDraftService->preflight((int) $draft->id, $actor);
        $checks = [];
        $addCheck = function (string $key, string $label, bool $passed, string $message) use (&$checks) {
            $checks[] = compact('key', 'label', 'passed', 'message');
        };

        $addCheck('approved_lifecycle', 'Approved lifecycle', $draft->status === 'approved', $draft->status === 'approved' ? 'Campaign lifecycle is approved.' : 'Return the campaign through independent approval before preparing a dispatch snapshot.');
        $ledgerChannel = CrmCampaignDraftService::isDispatchLedgerChannel($draft->planned_channel);
        $addCheck('stage31_ledger_channel', 'Stage 31 dispatch-ledger channel boundary', $ledgerChannel, $ledgerChannel ? 'BulkSMSBD SMS and Email may advance through immutable dispatch ledgers. Email remains non-sending.' : 'Newsletter, WhatsApp, and other legacy channels remain disabled for dispatch-ledger preparation.');
        $addCheck('preflight_ready', 'Stage 20 preflight', !empty($preflight['send_readiness']), !empty($preflight['send_readiness']) ? 'Stage 20 readiness checks passed.' : 'Stage 20 readiness checks did not pass.');
        $addCheck('approval_integrity', 'Approved planning integrity', ($preflight['approval_snapshot_integrity'] ?? null) === 'verified', ($preflight['approval_snapshot_integrity'] ?? null) === 'verified' ? 'Approved planning snapshot passed integrity verification.' : 'Approved planning integrity is stale, unsupported, or missing.');

        $recipientSet = null;
        $recipientError = null;
        if ($ledgerChannel) {
            try {
                $audienceSnapshot = $this->campaignDraftService->verifiedStoredAudienceSnapshot($draft);
                $recipientSet = $this->recipientSnapshotService->resolve($draft, $audienceSnapshot, $includeEncryptedDestinations);
            } catch (ValidationException $exception) {
                $recipientError = $this->firstValidationMessage($exception);
            }
        } else {
            $recipientError = 'Dispatch recipient freezing is disabled for this planned channel.';
        }

        $addCheck('live_normalized_recipients', 'Normalized live recipients', $recipientSet !== null, $recipientSet !== null ? number_format($recipientSet['recipient_count']) . ' normalized live recipients resolved without duplicates.' : ($recipientError ?: 'The normalized live recipient set could not be resolved.'));

        $approvedSignature = strtolower(trim((string) $draft->approved_recipient_set_signature));
        $approvedVersion = $draft->approved_recipient_set_signature_version;
        $approvedCount = $draft->approved_recipient_count;
        $approvalRecipientSealValid = preg_match('/^[a-f0-9]{64}$/D', $approvedSignature) === 1
            && is_numeric($approvedVersion)
            && (int) $approvedVersion === CrmCampaignRecipientSnapshotService::RECIPIENT_SET_SIGNATURE_VERSION
            && is_numeric($approvedCount)
            && (int) $approvedCount > 0;
        $addCheck('approval_recipient_seal', 'Approval-time recipient seal', $approvalRecipientSealValid, $approvalRecipientSealValid ? number_format((int) $approvedCount) . ' approval-time recipients are sealed.' : 'Approval-time recipient seal metadata is missing or unsupported. Return to draft, refresh audience snapshot, and obtain independent reapproval.');

        $recipientSetMatches = $recipientSet !== null
            && $approvalRecipientSealValid
            && (int) $approvedCount === (int) $recipientSet['recipient_count']
            && hash_equals($approvedSignature, strtolower((string) $recipientSet['signature']));
        $addCheck('recipient_set_not_stale', 'Recipient-set freshness', $recipientSetMatches, $recipientSetMatches ? 'Live normalized recipients exactly match the approval-time sealed set.' : 'The live normalized audience no longer matches the approval-time sealed set. Return to draft, refresh audience snapshot, resubmit, and obtain independent reapproval.');

        $active = $this->activePreparation((int) $draft->id);
        $addCheck('no_active_preparation', 'Duplicate preparation prevention', $active === null, $active === null ? 'No active preparation exists for this campaign.' : 'An active frozen preparation already exists for this campaign. Cancel or invalidate it before preparing another snapshot.');

        $activeRunExists = $this->hasActiveReleasedDispatchRun((int) $draft->id);
        $addCheck('no_active_dispatch_run', 'Released-run prevention', !$activeRunExists, !$activeRunExists ? 'No active provider-neutral dispatch run exists for this campaign.' : 'Cancel the active provider-neutral dispatch run before freezing a new preparation.');

        $activeExecutionBatchExists = $this->hasActiveExecutionBatch((int) $draft->id);
        $addCheck('no_active_execution_batch', 'Execution-batch prevention', !$activeExecutionBatchExists, !$activeExecutionBatchExists ? 'No active provider-neutral execution batch exists for this campaign.' : 'Cancel the active provider-neutral execution batch before freezing a fresh preparation.');

        $ready = collect($checks)->every(fn (array $check) => $check['passed']);

        return [
            'draft_id' => (int) $draft->id,
            'draft_name' => $draft->name,
            'status' => $draft->status,
            'channel' => $draft->planned_channel,
            'approved_recipient_count' => is_numeric($approvedCount) ? (int) $approvedCount : null,
            'live_normalized_recipient_count' => $recipientSet ? (int) $recipientSet['recipient_count'] : null,
            'ready_for_preparation' => $ready,
            'ready_for_preparation_label' => $ready ? 'Eligible for immutable dispatch preparation' : 'Not eligible for dispatch preparation',
            'checks' => $checks,
            'active_preparation' => $active ? $this->payload($active, $actor) : null,
            'destination_summary' => $recipientSet['destination_summary'] ?? null,
            'recipient_list_exposed' => false,
            'recipient_export_available' => false,
            'send_available' => false,
            'read_only' => true,
            'notice' => $smsExecutionChannel
                ? 'This preview is read only. It does not send, schedule, queue, export, or disclose recipient destinations.'
                : CrmCampaignDraftService::channelBoundaryNotice($draft->planned_channel),
            'resolved_recipient_set' => $recipientSet,
        ];
    }

    protected function transitionToTerminal(int $draftId, int $preparationId, string $status, $reason, User $actor): CrmCampaignDispatchPreparation
    {
        $this->ensureStorage();
        $this->authorize($actor, 'update');
        $this->campaignDraftService->findVisible($draftId, $actor);
        $reason = trim((string) $reason);
        if ($reason === '') {
            $this->throwValidation('reason', 'A reason is required.');
        }
        if (function_exists('mb_strlen') ? mb_strlen($reason) > 1000 : strlen($reason) > 1000) {
            $this->throwValidation('reason', 'The reason may not be greater than 1000 characters.');
        }

        try {
            $preparation = DB::transaction(function () use ($draftId, $preparationId, $status, $reason, $actor) {
                $preparation = CrmCampaignDispatchPreparation::query()
                    ->where('crm_campaign_draft_id', $draftId)
                    ->lockForUpdate()
                    ->findOrFail($preparationId);
                if ($preparation->status !== 'prepared' || (int) $preparation->active_slot !== 1) {
                    $this->throwValidation('dispatch_preparation', 'Only an active prepared snapshot can be cancelled or invalidated.');
                }

                $fields = [
                    'status' => $status,
                    'active_slot' => null,
                ];
                if ($status === 'cancelled') {
                    $fields['cancelled_by'] = $actor->id;
                    $fields['cancelled_at'] = now();
                    $fields['cancellation_reason'] = $reason;
                } else {
                    $fields['invalidated_by'] = $actor->id;
                    $fields['invalidated_at'] = now();
                    $fields['invalidation_reason'] = $reason;
                }
                $preparation->forceFill($fields)->save();
                $preparation = $preparation->refresh();

                $this->recordActivity('crm_campaign_dispatch_' . $status, $preparation, $actor->id, 'CRM campaign dispatch preparation ' . $status, [
                    'reason_present' => true,
                ]);

                return $preparation;
            });
        } catch (\Throwable $exception) {
            $this->logFailure($status, $draftId, $preparationId, $actor->id, $exception);
            throw $exception;
        }

        return $this->freshWithRelations($preparation);
    }

    protected function activePreparation(int $draftId): ?CrmCampaignDispatchPreparation
    {
        $query = CrmCampaignDispatchPreparation::query()
            ->where('crm_campaign_draft_id', $draftId)
            ->where('status', 'prepared')
            ->where('active_slot', 1);
        if ($this->hasUserStorage()) {
            $query->with(['preparer:id,name', 'releaser:id,name', 'invalidator:id,name', 'canceller:id,name']);
        }

        return $query->first();
    }

    protected function freshWithRelations(CrmCampaignDispatchPreparation $preparation): CrmCampaignDispatchPreparation
    {
        $preparation = $preparation->fresh();
        if ($this->hasUserStorage()) {
            $preparation->load(['preparer:id,name', 'releaser:id,name', 'invalidator:id,name', 'canceller:id,name']);
        }

        return $preparation;
    }

    protected function hasActiveReleasedDispatchRun(int $draftId): bool
    {
        $table = 'crm_campaign_dispatch_runs';
        foreach (['crm_campaign_draft_id', 'status', 'active_slot'] as $column) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
                return false;
            }
        }

        return DB::table($table)
            ->where('crm_campaign_draft_id', $draftId)
            ->where('status', 'released')
            ->where('active_slot', 1)
            ->exists();
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

    protected function ensureReady(array $readiness): void
    {
        if (!empty($readiness['ready_for_preparation'])) {
            return;
        }

        $failed = collect($readiness['checks'] ?? [])
            ->filter(fn (array $check) => empty($check['passed']))
            ->pluck('label')
            ->filter()
            ->implode(', ');

        $this->throwValidation('dispatch_preparation', 'Dispatch preparation failed readiness verification' . ($failed !== '' ? ': ' . $failed . '.' : '.'));
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
            throw new AuthorizationException('You do not have permission to perform this CRM campaign dispatch-preparation action.');
        }
    }

    protected function can(User $actor, string $action): bool
    {
        return $this->permissionService->userCan($actor, self::PERMISSION_KEY, $action);
    }

    protected function recordActivity(string $type, CrmCampaignDispatchPreparation $preparation, int $actorId, string $subject, array $extraMetadata = []): void
    {
        $this->activityService->recordOrFail([
            'product_website_id' => $preparation->product_website_id,
            'activity_type' => $type,
            'subject' => $subject,
            'description' => $subject . ' for CRM campaign draft #' . $preparation->crm_campaign_draft_id,
            'source_module' => 'crm_campaign_dispatch_preparations',
            'source_id' => $preparation->id,
            'performed_by' => $actorId,
            'metadata' => array_merge([
                'crm_campaign_draft_id' => (int) $preparation->crm_campaign_draft_id,
                'status' => $preparation->status,
                'channel' => $preparation->channel,
                'frozen_recipient_count' => (int) $preparation->frozen_recipient_count,
                'recipient_list_exposed' => false,
                'recipient_export_available' => false,
                'send_available' => false,
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
                'prepared_by', 'prepared_at', 'invalidated_by', 'invalidated_at', 'invalidation_reason',
                'cancelled_by', 'cancelled_at', 'cancellation_reason', 'metadata_json', 'created_at', 'updated_at',
            ],
            'crm_campaign_dispatch_recipients' => [
                'id', 'product_website_id', 'crm_campaign_draft_id', 'crm_campaign_dispatch_preparation_id',
                'customer_id', 'channel', 'destination_ciphertext', 'destination_hash', 'created_at',
            ],
        ];

        foreach ($tables as $table => $columns) {
            if (!Schema::hasTable($table)) {
                $this->throwValidation('dispatch_preparation', 'CRM campaign dispatch-preparation storage is unavailable. Run application migrations first.');
            }
            foreach ($columns as $column) {
                if (!Schema::hasColumn($table, $column)) {
                    $this->throwValidation('dispatch_preparation', 'CRM campaign dispatch-preparation storage is incomplete. Run application migrations first.');
                }
            }
        }
    }

    protected function hasUserStorage(): bool
    {
        return Schema::hasTable('users') && Schema::hasColumn('users', 'id') && Schema::hasColumn('users', 'name');
    }

    protected function firstValidationMessage(ValidationException $exception): string
    {
        foreach ($exception->errors() as $messages) {
            foreach ((array) $messages as $message) {
                return (string) $message;
            }
        }

        return 'Campaign dispatch preparation validation failed.';
    }

    protected function throwValidation(string $field, string $message): void
    {
        throw ValidationException::withMessages([$field => [$message]]);
    }

    protected function logFailure(string $operation, int $draftId, ?int $preparationId, int $actorId, \Throwable $exception): void
    {
        Log::error('CRM campaign dispatch preparation write failed', [
            'operation' => $operation,
            'campaign_draft_id' => $draftId,
            'dispatch_preparation_id' => $preparationId,
            'actor_id' => $actorId,
            'error' => $exception->getMessage(),
        ]);
    }
}
