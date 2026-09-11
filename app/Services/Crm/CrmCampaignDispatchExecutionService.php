<?php

namespace App\Services\Crm;

use App\Data\Crm\CampaignDispatch\CrmCampaignSmsDispatchCommand;
use App\Data\Crm\CampaignDispatch\CrmCampaignSmsDispatchResult;
use App\Models\Crm\CrmCampaignDispatchAttempt;
use App\Models\Crm\CrmCampaignDispatchExecutionRecipient;
use App\Models\Crm\CrmCampaignDispatchRecipient;
use App\Models\Crm\CrmCampaignDispatchRecipientAttempt;
use App\Models\Crm\CrmCampaignDispatchRecipientAttemptEvent;
use App\Models\Crm\CrmCampaignDispatchRunRecipient;
use App\Models\Crm\CrmCampaignDraft;
use App\Models\User;
use App\Services\Crm\CampaignDispatch\BulkSmsBdCampaignGatewayResolver;
use App\Services\Crm\CampaignDispatch\BulkSmsBdCampaignSmsProtocol;
use App\Services\RoleSidebarPermissionService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CrmCampaignDispatchExecutionService
{
    protected const PERMISSION_KEY = 'crm.campaign-drafts.execute-dispatch';

    protected const REQUEST_SEQUENCE = 1;

    protected const PROCESSING_GRACE_SECONDS = 60;

    public function __construct(
        protected CrmCampaignDraftService $campaignDraftService,
        protected CrmCampaignDispatchAttemptService $attemptService,
        protected CrmCampaignRecipientSnapshotService $snapshotService,
        protected CrmCampaignSmsDispatchAdapterRegistry $adapterRegistry,
        protected BulkSmsBdCampaignGatewayResolver $gatewayResolver,
        protected BulkSmsBdCampaignSmsProtocol $protocol,
        protected CrmActivityService $activityService,
        protected RoleSidebarPermissionService $permissionService
    ) {
    }

    /**
     * Execute one Stage 25 prepared attempt synchronously. Remote provider calls
     * are deliberately outside database transactions. Browser retries never
     * issue a second provider request.
     */
    public function execute(int $draftId, int $attemptId, User $actor): array
    {
        $this->authorize($actor);
        $this->campaignDraftService->findVisible($draftId, $actor);

        try {
            $start = DB::transaction(function () use ($draftId, $attemptId, $actor) {
                $draft = CrmCampaignDraft::query()->lockForUpdate()->findOrFail($draftId);
                $this->ensureReadable($draft, $actor);

                $attempt = CrmCampaignDispatchAttempt::query()
                    ->where('crm_campaign_draft_id', $draft->id)
                    ->lockForUpdate()
                    ->findOrFail($attemptId);

                if ($attempt->status !== 'prepared' || (int) $attempt->active_slot !== 1) {
                    return [
                        'started' => false,
                        'attempt_id' => (int) $attempt->id,
                    ];
                }

                $recipientRows = $this->attemptService->assertPreparedAttemptIntegrity($attempt, true);
                $this->ensureExecutionBoundary($draft, $attempt, $actor, count($recipientRows));

                $gateway = $this->gatewayResolver->executionConfig($attempt->product_website_id === null ? null : (int) $attempt->product_website_id);
                $adapter = $this->adapterRegistry->resolve((string) $attempt->channel, (string) $attempt->provider_key);
                $recipientAttempts = CrmCampaignDispatchRecipientAttempt::query()
                    ->where('crm_campaign_dispatch_attempt_id', $attempt->id)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                if ($recipientAttempts->count() !== (int) $attempt->recipient_count) {
                    $this->throwValidation('dispatch_attempt', 'The immutable recipient-attempt count no longer matches the sealed header count.');
                }
                if (CrmCampaignDispatchRecipientAttemptEvent::query()->where('crm_campaign_dispatch_attempt_id', $attempt->id)->exists()) {
                    $this->throwValidation('dispatch_attempt', 'This manual attempt has already entered provider execution and cannot be started again.');
                }

                foreach ($recipientAttempts as $recipientAttempt) {
                    $plaintext = null;
                    try {
                        $preparationRecipient = $this->verifiedPreparationRecipient($recipientAttempt, true);
                        $plaintext = $this->snapshotService->decryptAndVerifyDestination(
                            (string) $recipientAttempt->channel,
                            (string) $preparationRecipient->destination_ciphertext,
                            (string) $preparationRecipient->destination_hash
                        );
                        $this->protocol->normalizeBangladeshiDestination($plaintext);
                    } finally {
                        unset($plaintext);
                    }
                }

                $metadata = array_merge((array) ($attempt->metadata_json ?? []), [
                    'stage27_real_sms_execution' => true,
                    'sms_only' => true,
                    'bulksmsbd_only' => true,
                    'single_recipient_requests_only' => true,
                    'maximum_allowed_recipients' => BulkSmsBdCampaignSmsProtocol::MAX_RECIPIENTS,
                    'automatic_retry_available' => false,
                    'queue_available' => false,
                    'schedule_available' => false,
                    'webhook_available' => false,
                    'polling_available' => false,
                    'executor_is_campaign_creator' => (int) $draft->created_by === (int) $actor->id,
                    'executor_is_stage22_preparer' => $this->actorMatchesPreparationRole($attempt, $actor),
                    'executor_is_stage23_releaser' => $this->actorMatchesRunReleaseRole($attempt, $actor),
                ]);

                $attempt->forceFill([
                    'status' => 'processing',
                    'started_by' => $actor->id,
                    'started_at' => now(),
                    'metadata_json' => $metadata,
                ])->save();
                $attempt = $attempt->refresh();

                $this->recordActivity('crm_campaign_dispatch_attempt_started', $attempt, $actor->id, 'CRM campaign bounded SMS attempt started', [
                    'maximum_allowed_recipients' => BulkSmsBdCampaignSmsProtocol::MAX_RECIPIENTS,
                    'typed_confirmation_required' => true,
                    'automatic_retry_available' => false,
                ]);

                return [
                    'started' => true,
                    'attempt_id' => (int) $attempt->id,
                    'recipient_attempt_ids' => $recipientAttempts->pluck('id')->map(fn ($id) => (int) $id)->all(),
                    'gateway' => $gateway,
                    'adapter' => $adapter,
                    'message' => (string) $attempt->executionBatch()->value('message_body_snapshot'),
                ];
            });

            if (empty($start['started'])) {
                $attempt = $this->reconcileInterruptedAttempt((int) $start['attempt_id'], $actor);

                return [
                    'executed' => false,
                    'message' => 'This attempt was already started. No browser retry or duplicate provider request was issued.',
                    'attempt' => $this->payload($attempt, $actor),
                ];
            }

            foreach ($start['recipient_attempt_ids'] as $recipientAttemptId) {
                if (!$this->recordRequestStarted((int) $start['attempt_id'], (int) $recipientAttemptId)) {
                    continue;
                }

                $plaintext = null;
                $command = null;
                try {
                    $recipientAttempt = CrmCampaignDispatchRecipientAttempt::query()->findOrFail($recipientAttemptId);
                    $preparationRecipient = $this->verifiedPreparationRecipient($recipientAttempt, false);
                    $plaintext = $this->snapshotService->decryptAndVerifyDestination(
                        (string) $recipientAttempt->channel,
                        (string) $preparationRecipient->destination_ciphertext,
                        (string) $preparationRecipient->destination_hash
                    );
                    $command = new CrmCampaignSmsDispatchCommand($start['gateway'], $plaintext, (string) $start['message']);
                    $result = $start['adapter']->dispatch($command);
                } catch (ValidationException $exception) {
                    $result = new CrmCampaignSmsDispatchResult(
                        'failed',
                        'validation_failure',
                        'An immutable recipient destination failed safe server-side validation before provider submission.'
                    );
                } catch (\Throwable $exception) {
                    $result = new CrmCampaignSmsDispatchResult(
                        'unknown',
                        'internal_unknown',
                        'A bounded recipient request ended without a safely confirmable outcome. Automatic retry is disabled.'
                    );
                } finally {
                    unset($command, $plaintext);
                }

                $this->recordTerminalResult((int) $start['attempt_id'], (int) $recipientAttemptId, $result);
            }

            $attempt = $this->finalizeAttempt((int) $start['attempt_id'], $actor);

            return [
                'executed' => true,
                'message' => 'Bounded BulkSMSBD SMS execution completed. Review the aggregate counters; automatic retry remains disabled.',
                'attempt' => $this->payload($attempt, $actor),
            ];
        } catch (\Throwable $exception) {
            $this->logFailure('execute', $draftId, $attemptId, $actor->id, $exception);
            throw $exception;
        }
    }

    protected function ensureExecutionBoundary(CrmCampaignDraft $draft, CrmCampaignDispatchAttempt $attempt, User $actor, int $recipientCount): void
    {
        if ((string) $attempt->channel !== 'sms' || (string) $attempt->provider_key !== BulkSmsBdCampaignGatewayResolver::PROVIDER_KEY) {
            $this->throwValidation('dispatch_attempt', 'Stage 27 enables real execution for BulkSMSBD SMS attempts only.');
        }
        if ($recipientCount <= 0 || $recipientCount > BulkSmsBdCampaignSmsProtocol::MAX_RECIPIENTS) {
            $this->throwValidation('dispatch_attempt', 'The bounded SMS attempt must contain between 1 and ' . BulkSmsBdCampaignSmsProtocol::MAX_RECIPIENTS . ' recipients. No recipient was sent.');
        }
        if ((int) $draft->reviewed_by === (int) $actor->id) {
            $this->throwValidation('dispatch_attempt', 'The campaign approver cannot execute this real SMS attempt. Assign a separate authorized operator.');
        }
        if ((int) $attempt->prepared_by === (int) $actor->id) {
            $this->throwValidation('dispatch_attempt', 'The Stage 25 attempt preparer cannot execute this real SMS attempt. Assign a separate authorized operator.');
        }
        $claimedBy = (int) $attempt->executionBatch()->value('claimed_by');
        if ($claimedBy > 0 && $claimedBy === (int) $actor->id) {
            $this->throwValidation('dispatch_attempt', 'The Stage 24 execution claimant cannot execute this real SMS attempt. Assign a separate authorized operator.');
        }
    }

    protected function recordRequestStarted(int $attemptId, int $recipientAttemptId): bool
    {
        return DB::transaction(function () use ($attemptId, $recipientAttemptId) {
            $attempt = CrmCampaignDispatchAttempt::query()->lockForUpdate()->findOrFail($attemptId);
            $recipient = CrmCampaignDispatchRecipientAttempt::query()
                ->where('crm_campaign_dispatch_attempt_id', $attempt->id)
                ->lockForUpdate()
                ->findOrFail($recipientAttemptId);

            if ($attempt->status !== 'processing' || (int) $attempt->active_slot !== 1) {
                return false;
            }
            if ($this->terminalEventExists((int) $recipient->id)) {
                return false;
            }
            if ($this->requestStartedEventExists((int) $recipient->id)) {
                return false;
            }

            CrmCampaignDispatchRecipientAttemptEvent::create([
                'product_website_id' => $attempt->product_website_id,
                'crm_campaign_draft_id' => $attempt->crm_campaign_draft_id,
                'crm_campaign_dispatch_attempt_id' => $attempt->id,
                'crm_campaign_dispatch_recipient_attempt_id' => $recipient->id,
                'crm_campaign_dispatch_execution_batch_id' => $attempt->crm_campaign_dispatch_execution_batch_id,
                'event_type' => 'request_started',
                'provider_key' => $attempt->provider_key,
                'request_sequence' => self::REQUEST_SEQUENCE,
                'terminal_slot' => null,
                'status' => 'started',
                'request_started_at' => now(),
                'metadata_json' => [
                    'single_recipient_request' => true,
                    'post_only' => true,
                    'automatic_retry_available' => false,
                    'request_body_persisted' => false,
                    'destination_persisted' => false,
                ],
                'created_at' => now(),
            ]);

            $this->syncCounters($attempt);

            return true;
        });
    }

    protected function recordTerminalResult(int $attemptId, int $recipientAttemptId, CrmCampaignSmsDispatchResult $result): void
    {
        DB::transaction(function () use ($attemptId, $recipientAttemptId, $result) {
            $attempt = CrmCampaignDispatchAttempt::query()->lockForUpdate()->findOrFail($attemptId);
            $recipient = CrmCampaignDispatchRecipientAttempt::query()
                ->where('crm_campaign_dispatch_attempt_id', $attempt->id)
                ->lockForUpdate()
                ->findOrFail($recipientAttemptId);

            if ($this->terminalEventExists((int) $recipient->id)) {
                return;
            }
            if (!$this->requestStartedEventExists((int) $recipient->id)) {
                $this->throwValidation('dispatch_attempt', 'A terminal recipient result cannot be recorded without its committed request-start event.');
            }

            $failed = $result->status() !== 'succeeded';
            CrmCampaignDispatchRecipientAttemptEvent::create([
                'product_website_id' => $attempt->product_website_id,
                'crm_campaign_draft_id' => $attempt->crm_campaign_draft_id,
                'crm_campaign_dispatch_attempt_id' => $attempt->id,
                'crm_campaign_dispatch_recipient_attempt_id' => $recipient->id,
                'crm_campaign_dispatch_execution_batch_id' => $attempt->crm_campaign_dispatch_execution_batch_id,
                'event_type' => $result->terminalEventType(),
                'provider_key' => $attempt->provider_key,
                'request_sequence' => self::REQUEST_SEQUENCE,
                'terminal_slot' => 1,
                'status' => $result->status(),
                'provider_message_id' => $result->providerMessageId(),
                'provider_response_code' => $result->providerResponseCode(),
                'redacted_response_summary' => $result->redactedResponseSummary(),
                'response_hash' => $result->responseHash(),
                'request_completed_at' => now(),
                'failed_at' => $failed ? now() : null,
                'failure_category' => $result->failureCategory(),
                'failure_summary' => $result->failureSummary(),
                'metadata_json' => [
                    'raw_request_persisted' => false,
                    'raw_response_persisted' => false,
                    'destination_persisted' => false,
                    'credential_persisted' => false,
                    'automatic_retry_available' => false,
                ],
                'created_at' => now(),
            ]);

            $this->syncCounters($attempt);
        });
    }

    protected function finalizeAttempt(int $attemptId, User $actor): CrmCampaignDispatchAttempt
    {
        $attempt = DB::transaction(function () use ($attemptId, $actor) {
            $attempt = CrmCampaignDispatchAttempt::query()->lockForUpdate()->findOrFail($attemptId);
            if ($attempt->status !== 'processing') {
                return $attempt;
            }

            $this->syncCounters($attempt);
            $terminalCount = $this->terminalEventQuery($attempt->id)->count();
            if ($terminalCount < (int) $attempt->recipient_count) {
                return $attempt->refresh();
            }

            $success = (int) $attempt->provider_success_count;
            $failure = (int) $attempt->provider_failure_count;
            $unknown = (int) $attempt->provider_unknown_count;
            $status = $failure === 0 && $unknown === 0
                ? 'completed'
                : ($success > 0 ? 'partially_failed' : 'failed');
            $summary = $status === 'completed'
                ? null
                : 'Bounded SMS execution completed with ' . $failure . ' failed and ' . $unknown . ' unknown recipient outcome(s). Automatic retry is disabled.';

            $attempt->forceFill([
                'status' => $status,
                'active_slot' => null,
                'completed_at' => now(),
                'failed_at' => $status === 'failed' ? now() : null,
                'failure_summary' => $summary,
            ])->save();
            $attempt = $attempt->refresh();

            $activityType = match ($status) {
                'completed' => 'crm_campaign_dispatch_attempt_completed',
                'partially_failed' => 'crm_campaign_dispatch_attempt_partially_failed',
                default => 'crm_campaign_dispatch_attempt_failed',
            };
            $this->recordActivity($activityType, $attempt, $actor->id, 'CRM campaign bounded SMS attempt finalized', [
                'provider_request_count' => (int) $attempt->provider_request_count,
                'provider_success_count' => (int) $attempt->provider_success_count,
                'provider_failure_count' => (int) $attempt->provider_failure_count,
                'provider_unknown_count' => (int) $attempt->provider_unknown_count,
            ]);

            return $attempt;
        });

        return $this->freshAttempt($attempt);
    }

    /**
     * A browser retry may close a stale interrupted attempt as unknown, but it
     * must never issue another provider request.
     */
    protected function reconcileInterruptedAttempt(int $attemptId, User $actor): CrmCampaignDispatchAttempt
    {
        $attempt = CrmCampaignDispatchAttempt::query()->findOrFail($attemptId);
        if ($attempt->status !== 'processing') {
            return $this->freshAttempt($attempt);
        }
        if ($attempt->started_at && $attempt->started_at->gt(now()->subSeconds(self::PROCESSING_GRACE_SECONDS))) {
            return $this->freshAttempt($attempt);
        }

        DB::transaction(function () use ($attemptId) {
            $attempt = CrmCampaignDispatchAttempt::query()->lockForUpdate()->findOrFail($attemptId);
            if ($attempt->status !== 'processing') {
                return;
            }

            $recipients = CrmCampaignDispatchRecipientAttempt::query()
                ->where('crm_campaign_dispatch_attempt_id', $attempt->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            foreach ($recipients as $recipient) {
                if ($this->terminalEventExists((int) $recipient->id)) {
                    continue;
                }
                $requestStarted = $this->requestStartedEventExists((int) $recipient->id);
                $failureCategory = $requestStarted
                    ? 'execution_interrupted_unknown'
                    : 'execution_interrupted_before_request';
                $status = $requestStarted ? 'unknown' : 'failed';
                $summary = $this->protocol->safeFailureSummary($failureCategory);
                CrmCampaignDispatchRecipientAttemptEvent::create([
                    'product_website_id' => $attempt->product_website_id,
                    'crm_campaign_draft_id' => $attempt->crm_campaign_draft_id,
                    'crm_campaign_dispatch_attempt_id' => $attempt->id,
                    'crm_campaign_dispatch_recipient_attempt_id' => $recipient->id,
                    'crm_campaign_dispatch_execution_batch_id' => $attempt->crm_campaign_dispatch_execution_batch_id,
                    'event_type' => $requestStarted ? 'recipient_unknown' : 'recipient_failed',
                    'provider_key' => $attempt->provider_key,
                    'request_sequence' => self::REQUEST_SEQUENCE,
                    'terminal_slot' => 1,
                    'status' => $status,
                    'request_completed_at' => now(),
                    'failed_at' => now(),
                    'failure_category' => $failureCategory,
                    'failure_summary' => $summary,
                    'metadata_json' => [
                        'stale_reconciliation' => true,
                        'request_started_event_exists' => $requestStarted,
                        'provider_request_reissued' => false,
                        'automatic_retry_available' => false,
                    ],
                    'created_at' => now(),
                ]);
            }
            $this->syncCounters($attempt);
        });

        return $this->finalizeAttempt($attemptId, $actor);
    }

    protected function verifiedPreparationRecipient(CrmCampaignDispatchRecipientAttempt $recipientAttempt, bool $lockRows): CrmCampaignDispatchRecipient
    {
        $executionQuery = CrmCampaignDispatchExecutionRecipient::query();
        $runQuery = CrmCampaignDispatchRunRecipient::query();
        $preparationQuery = CrmCampaignDispatchRecipient::query();
        if ($lockRows) {
            $executionQuery->lockForUpdate();
            $runQuery->lockForUpdate();
            $preparationQuery->lockForUpdate();
        }

        $execution = $executionQuery->find($recipientAttempt->crm_campaign_dispatch_execution_recipient_id);
        $run = $execution ? $runQuery->find($execution->crm_campaign_dispatch_run_recipient_id) : null;
        $preparation = $run ? $preparationQuery->find($run->crm_campaign_dispatch_recipient_id) : null;

        if (!$execution || !$run || !$preparation
            || (int) $execution->crm_campaign_dispatch_execution_batch_id !== (int) $recipientAttempt->crm_campaign_dispatch_execution_batch_id
            || (int) $run->crm_campaign_dispatch_run_id !== (int) $recipientAttempt->crm_campaign_dispatch_run_id
            || (int) $preparation->crm_campaign_dispatch_preparation_id !== (int) $recipientAttempt->crm_campaign_dispatch_preparation_id
            || (int) $execution->customer_id !== (int) $recipientAttempt->customer_id
            || (int) $run->customer_id !== (int) $recipientAttempt->customer_id
            || (int) $preparation->customer_id !== (int) $recipientAttempt->customer_id
            || !hash_equals((string) $execution->channel, (string) $recipientAttempt->channel)
            || !hash_equals((string) $run->channel, (string) $recipientAttempt->channel)
            || !hash_equals((string) $preparation->channel, (string) $recipientAttempt->channel)
            || !hash_equals(strtolower((string) $execution->destination_hash), strtolower((string) $run->destination_hash))
            || !hash_equals(strtolower((string) $run->destination_hash), strtolower((string) $preparation->destination_hash))
            || !$this->sameNullableInteger($execution->product_website_id, $recipientAttempt->product_website_id)
            || !$this->sameNullableInteger($run->product_website_id, $recipientAttempt->product_website_id)
            || !$this->sameNullableInteger($preparation->product_website_id, $recipientAttempt->product_website_id)) {
            $this->throwValidation('dispatch_attempt', 'A recipient failed immutable Stage 22-25 lineage verification.');
        }

        return $preparation;
    }

    protected function syncCounters(CrmCampaignDispatchAttempt $attempt): void
    {
        $requestCount = CrmCampaignDispatchRecipientAttemptEvent::query()
            ->where('crm_campaign_dispatch_attempt_id', $attempt->id)
            ->where('event_type', 'request_started')
            ->count();
        $terminal = $this->terminalEventQuery((int) $attempt->id);
        $success = (clone $terminal)->where('status', 'succeeded')->count();
        $failure = (clone $terminal)->where('status', 'failed')->count();
        $unknown = (clone $terminal)->where('status', 'unknown')->count();

        $attempt->forceFill([
            'provider_request_count' => $requestCount,
            'provider_success_count' => $success,
            'provider_failure_count' => $failure,
            'provider_unknown_count' => $unknown,
        ])->save();
        $attempt->refresh();
    }

    protected function terminalEventQuery(int $attemptId)
    {
        return CrmCampaignDispatchRecipientAttemptEvent::query()
            ->where('crm_campaign_dispatch_attempt_id', $attemptId)
            ->where('terminal_slot', 1);
    }

    protected function requestStartedEventExists(int $recipientAttemptId): bool
    {
        return CrmCampaignDispatchRecipientAttemptEvent::query()
            ->where('crm_campaign_dispatch_recipient_attempt_id', $recipientAttemptId)
            ->where('request_sequence', self::REQUEST_SEQUENCE)
            ->where('event_type', 'request_started')
            ->exists();
    }

    protected function terminalEventExists(int $recipientAttemptId): bool
    {
        return CrmCampaignDispatchRecipientAttemptEvent::query()
            ->where('crm_campaign_dispatch_recipient_attempt_id', $recipientAttemptId)
            ->where('request_sequence', self::REQUEST_SEQUENCE)
            ->where('terminal_slot', 1)
            ->exists();
    }

    protected function actorMatchesPreparationRole(CrmCampaignDispatchAttempt $attempt, User $actor): bool
    {
        return (int) $attempt->preparation()->value('prepared_by') === (int) $actor->id;
    }

    protected function actorMatchesRunReleaseRole(CrmCampaignDispatchAttempt $attempt, User $actor): bool
    {
        return (int) $attempt->run()->value('released_by') === (int) $actor->id;
    }

    protected function payload(CrmCampaignDispatchAttempt $attempt, User $actor): array
    {
        return $this->attemptService->payload($this->freshAttempt($attempt), $actor);
    }

    protected function freshAttempt(CrmCampaignDispatchAttempt $attempt): CrmCampaignDispatchAttempt
    {
        $attempt = $attempt->fresh();
        if ($this->hasUserStorage()) {
            $attempt->load(['preparer:id,name', 'starter:id,name', 'canceller:id,name']);
        }

        return $attempt;
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
                'crm_campaign_dispatch_execution_batch_id' => (int) $attempt->crm_campaign_dispatch_execution_batch_id,
                'attempt_number' => (int) $attempt->attempt_number,
                'status' => (string) $attempt->status,
                'channel' => (string) $attempt->channel,
                'provider_key' => (string) $attempt->provider_key,
                'recipient_count' => (int) $attempt->recipient_count,
                'sms_only' => true,
                'single_recipient_requests_only' => true,
                'raw_request_persisted' => false,
                'raw_response_persisted' => false,
                'destination_persisted' => false,
                'credential_persisted' => false,
                'automatic_retry_available' => false,
                'queue_available' => false,
                'schedule_available' => false,
                'webhook_available' => false,
                'polling_available' => false,
            ], $extraMetadata),
        ]);
    }

    protected function ensureReadable(CrmCampaignDraft $draft, User $actor): void
    {
        if ($draft->visibility !== 'shared' && (int) $draft->created_by !== (int) $actor->id) {
            throw new AuthorizationException('You cannot access this CRM campaign draft.');
        }
    }

    protected function authorize(User $actor): void
    {
        if (!$this->permissionService->userCan($actor, self::PERMISSION_KEY, 'create')) {
            throw new AuthorizationException('You do not have permission to execute CRM campaign SMS dispatch.');
        }
    }

    protected function hasUserStorage(): bool
    {
        return Schema::hasTable('users') && Schema::hasColumn('users', 'id') && Schema::hasColumn('users', 'name');
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

    protected function throwValidation(string $field, string $message): void
    {
        throw ValidationException::withMessages([$field => [$message]]);
    }

    protected function logFailure(string $operation, int $draftId, int $attemptId, int $actorId, \Throwable $exception): void
    {
        Log::error('CRM campaign bounded SMS execution failed safely', [
            'operation' => $operation,
            'campaign_draft_id' => $draftId,
            'dispatch_attempt_id' => $attemptId,
            'actor_id' => $actorId,
            'error_class' => get_class($exception),
        ]);
    }
}
