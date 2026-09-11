<?php

namespace App\Services\Crm;

use App\Models\Crm\CrmCampaignDraft;
use App\Models\Crm\CrmCampaignDraftApprovalHistory;
use App\Models\User;
use App\Services\RoleSidebarPermissionService;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CrmCampaignDraftService
{
    protected array $permissionCache = [];

    public const VISIBILITIES = ['private', 'shared'];

    public const STATUSES = ['draft', 'pending_review', 'approved', 'rejected', 'archived'];

    public const REVIEWABLE_STATUSES = ['pending_review', 'approved', 'rejected'];

    /**
     * Stage 28 surfaces two actively supported CRM campaign-planning choices.
     * Historical values remain readable for non-destructive legacy preservation.
     */
    public const ACTIVE_PLANNED_CHANNELS = ['sms', 'email'];

    public const LEGACY_DISABLED_PLANNED_CHANNELS = ['newsletter', 'whatsapp', 'other'];

    public const PLANNED_CHANNELS = ['sms', 'email', 'newsletter', 'whatsapp', 'other'];

    public const SEND_READINESS_CHANNELS = ['sms', 'email'];

    public const REAL_SEND_CHANNELS = ['sms'];

    /**
     * Stage 31 allows Email to move through immutable dispatch ledgers without
     * enabling Email transport execution. Real transport remains SMS-only.
     */
    public const DISPATCH_LEDGER_CHANNELS = ['sms', 'email'];

    protected const SUBJECT_REQUIRED_CHANNELS = ['email'];

    protected const SNAPSHOT_SIGNATURE_VERSION = 1;

    protected const LEGACY_APPROVED_SNAPSHOT_SIGNATURE_VERSION = 1;

    protected const APPROVED_SNAPSHOT_SIGNATURE_VERSION = 2;

    public function __construct(
        protected CrmActivityService $activityService,
        protected CrmSavedCustomerSegmentService $savedSegmentService,
        protected CrmCustomerSegmentWorklistService $customerSegmentService,
        protected RoleSidebarPermissionService $permissionService,
        protected CrmCampaignRecipientSnapshotService $recipientSnapshotService,
        protected CrmCampaignDispatchProviderRegistry $dispatchProviderRegistry
    ) {
    }

    public static function isActivePlannedChannel($channel): bool
    {
        return in_array(trim((string) $channel), self::ACTIVE_PLANNED_CHANNELS, true);
    }

    public static function isLegacyDisabledPlannedChannel($channel): bool
    {
        $channel = trim((string) $channel);

        return $channel !== '' && !self::isActivePlannedChannel($channel);
    }

    public static function isRealSendChannel($channel): bool
    {
        return in_array(trim((string) $channel), self::REAL_SEND_CHANNELS, true);
    }

    public static function isDispatchLedgerChannel($channel): bool
    {
        return in_array(trim((string) $channel), self::DISPATCH_LEDGER_CHANNELS, true);
    }

    public static function channelLabel($channel): string
    {
        return match (trim((string) $channel)) {
            'sms' => 'BulkSMSBD SMS',
            'email' => 'Email',
            'newsletter' => 'Newsletter (legacy disabled)',
            'whatsapp' => 'WhatsApp (legacy disabled)',
            'other' => 'Other (legacy disabled)',
            default => 'Not selected',
        };
    }

    public static function channelBoundaryNotice($channel): string
    {
        return match (trim((string) $channel)) {
            'sms' => 'BulkSMSBD SMS is the only enabled real-send channel. Stage 27 bounded manual execution rules remain enforced.',
            'email' => 'Email campaign planning, approval, and non-sending immutable dispatch ledgers are available. Real email transport execution is not enabled yet.',
            'newsletter', 'whatsapp', 'other' => 'This legacy campaign channel is preserved read-only and disabled for new planning, governance transitions, and dispatch.',
            default => 'Choose BulkSMSBD SMS or Email before submitting this campaign for review.',
        };
    }

    public function worklistQuery(User $actor, array $filters = []): Builder
    {
        $this->ensureStorage();
        $this->authorize($actor, 'read');

        $query = $this->visibleQuery($actor);
        $this->loadUserRelations($query);

        if (!array_key_exists('status', $filters)) {
            $query->where('status', 'draft');
        } elseif (!empty($filters['status']) && in_array($filters['status'], self::STATUSES, true)) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['visibility']) && in_array($filters['visibility'], self::VISIBILITIES, true)) {
            $query->where('visibility', $filters['visibility']);
        }

        if (!empty($filters['planned_channel']) && in_array($filters['planned_channel'], self::PLANNED_CHANNELS, true)) {
            $query->where('planned_channel', $filters['planned_channel']);
        }

        return $query->orderByDesc('updated_at')->orderByDesc('id');
    }

    public function applyGlobalSearch(Builder $query, ?string $search): void
    {
        $search = trim((string) $search);
        if ($search === '') {
            return;
        }

        $query->where(function (Builder $nested) use ($search) {
            $nested->where('name', 'like', '%' . $search . '%')
                ->orWhere('description', 'like', '%' . $search . '%')
                ->orWhere('subject', 'like', '%' . $search . '%')
                ->orWhere('planned_channel', 'like', '%' . $search . '%')
                ->orWhere('visibility', 'like', '%' . $search . '%')
                ->orWhere('status', 'like', '%' . $search . '%')
                ->orWhere('audience_segment_name_snapshot', 'like', '%' . $search . '%');

            if ($this->hasUserStorage()) {
                $nested->orWhereHas('creator', fn (Builder $user) => $user->where('name', 'like', '%' . $search . '%'));
            }
        });
    }

    public function savedSegmentOptions(User $actor, ?string $search = null): Collection
    {
        $this->ensureStorage();
        $this->authorize($actor, 'read');

        return $this->savedSegmentService->options($actor, $search);
    }

    public function findVisible(int $draftId, User $actor): CrmCampaignDraft
    {
        $this->ensureStorage();
        $this->authorize($actor, 'read');

        $query = $this->visibleQuery($actor);
        $this->loadUserRelations($query);

        $draft = $query->findOrFail($draftId);
        $this->ensureReadableSharedSnapshot($draft, $actor);

        return $draft;
    }

    public function previewSavedSegment(int $segmentId, User $actor): array
    {
        $this->ensureStorage();
        $this->authorize($actor, 'read');

        $snapshot = $this->savedSegmentService->applicableSnapshot($segmentId, $actor);

        return $this->previewPayload(
            $snapshot['name'],
            $snapshot['visibility'],
            $snapshot['filters'],
            $snapshot['filters_summary'],
            null
        );
    }

    public function previewDraft(int $draftId, User $actor): array
    {
        $draft = $this->findVisible($draftId, $actor);
        $snapshot = $this->storedAudienceSnapshot($draft);

        return $this->previewPayload(
            $snapshot['name'],
            $snapshot['visibility'],
            $snapshot['filters'],
            $this->savedSegmentService->filterSummaryFor($snapshot['filters']),
            $draft
        );
    }

    public function preflight(int $draftId, User $actor): array
    {
        $draft = $this->findVisible($draftId, $actor);

        return $this->preflightPayload($draft);
    }

    public function approvalHistory(int $draftId, User $actor): Collection
    {
        $this->ensureStorage();
        $this->ensureApprovalHistoryStorage();
        $draft = $this->findVisible($draftId, $actor);

        $query = CrmCampaignDraftApprovalHistory::query()
            ->where('crm_campaign_draft_id', $draft->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($this->hasUserStorage()) {
            $query->with('actor:id,name');
        }

        return $query->get()->map(fn (CrmCampaignDraftApprovalHistory $history) => $this->approvalHistoryPayload($history));
    }

    public function submitForReview(int $draftId, User $actor): CrmCampaignDraft
    {
        $this->ensureStorage();
        $this->ensureApprovalHistoryStorage();
        $this->authorize($actor, 'update');

        try {
            $draft = DB::transaction(function () use ($draftId, $actor) {
                $draft = $this->visibleQuery($actor)->lockForUpdate()->findOrFail($draftId);
                $this->authorizeOwner($draft, $actor, 'submit for review');
                $this->ensureStatus($draft, ['draft'], 'Only an editable draft can be submitted for review.');
                $this->ensureMutablePlannedChannel($draft);
                $preflight = $this->preflightPayload($draft);
                $this->ensurePreflightReady($preflight);
                $fromStatus = $draft->status;

                $draft->forceFill([
                    'status' => 'pending_review',
                    'submitted_by' => $actor->id,
                    'submitted_at' => now(),
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                    'review_decision' => null,
                    'review_note' => null,
                    'approved_snapshot_signature' => null,
                    'approved_snapshot_signature_version' => null,
                    'approved_snapshot_at' => null,
                    'approved_recipient_set_signature' => null,
                    'approved_recipient_set_signature_version' => null,
                    'approved_recipient_count' => null,
                    'updated_by' => $actor->id,
                ])->save();

                $draft = $draft->refresh();
                $metadata = [
                    'estimated_recipients' => $preflight['estimated_recipients'],
                    'send_readiness' => $preflight['send_readiness'],
                ];
                $this->recordApprovalHistory('submitted_for_review', $draft, $actor, $fromStatus, 'pending_review', null, $metadata);
                $this->recordActivity('crm_campaign_draft_submitted_for_review', $draft, $actor->id, 'CRM campaign draft submitted for review', $metadata);

                return $draft;
            });
        } catch (\Throwable $exception) {
            $this->logFailure('submit_for_review', $draftId, $actor->id, $exception);
            throw $exception;
        }

        return $this->freshWithRelations($draft);
    }

    public function approve(int $draftId, array $data, User $actor): CrmCampaignDraft
    {
        $this->ensureStorage();
        $this->ensureApprovalHistoryStorage();
        $this->authorizeApproval($actor);
        $note = $this->nullableBoundedString($data['review_note'] ?? null, 'review_note', 2000);

        try {
            $draft = DB::transaction(function () use ($draftId, $actor, $note) {
                $draft = $this->visibleQuery($actor)->lockForUpdate()->findOrFail($draftId);
                $this->ensureReadableSharedSnapshot($draft, $actor);
                $this->ensureReviewerIsNotCreator($draft, $actor);
                $this->ensureStatus($draft, ['pending_review'], 'Only a pending-review campaign draft can be approved.');
                $this->ensureMutablePlannedChannel($draft);
                $preflight = $this->preflightPayload($draft);
                $this->ensurePreflightReady($preflight);
                $fromStatus = $draft->status;
                $approvedAt = now();
                $audienceSnapshot = $this->verifiedStoredAudienceSnapshot($draft);
                $recipientSet = $this->recipientSnapshotService->resolve($draft, $audienceSnapshot);
                $approvedSignature = $this->approvedSnapshotSignature(
                    $draft,
                    $approvedAt,
                    $recipientSet['signature'],
                    $recipientSet['signature_version'],
                    $recipientSet['recipient_count'],
                    self::APPROVED_SNAPSHOT_SIGNATURE_VERSION
                );

                $draft->forceFill([
                    'status' => 'approved',
                    'reviewed_by' => $actor->id,
                    'reviewed_at' => $approvedAt,
                    'review_decision' => 'approved',
                    'review_note' => $note,
                    'approved_snapshot_signature' => $approvedSignature,
                    'approved_snapshot_signature_version' => self::APPROVED_SNAPSHOT_SIGNATURE_VERSION,
                    'approved_snapshot_at' => $approvedAt,
                    'approved_recipient_set_signature' => $recipientSet['signature'],
                    'approved_recipient_set_signature_version' => $recipientSet['signature_version'],
                    'approved_recipient_count' => $recipientSet['recipient_count'],
                    'updated_by' => $actor->id,
                ])->save();

                $draft = $draft->refresh();
                $metadata = [
                    'estimated_recipients' => $preflight['estimated_recipients'],
                    'send_readiness' => $preflight['send_readiness'],
                    'approved_snapshot_integrity' => 'verified',
                    'approved_recipient_set_integrity' => 'verified',
                    'approved_recipient_count' => $recipientSet['recipient_count'],
                ];
                $this->recordApprovalHistory('approved', $draft, $actor, $fromStatus, 'approved', $note, $metadata);
                $this->recordActivity('crm_campaign_draft_approved', $draft, $actor->id, 'CRM campaign draft approved', $metadata);

                return $draft;
            });
        } catch (\Throwable $exception) {
            $this->logFailure('approve', $draftId, $actor->id, $exception);
            throw $exception;
        }

        return $this->freshWithRelations($draft);
    }

    public function reject(int $draftId, array $data, User $actor): CrmCampaignDraft
    {
        $this->ensureStorage();
        $this->ensureApprovalHistoryStorage();
        $this->authorizeApproval($actor);
        $note = $this->nullableBoundedString($data['review_note'] ?? null, 'review_note', 2000);
        if ($note === null) {
            $this->throwValidation('review_note', 'A review note is required when rejecting a campaign draft.');
        }

        try {
            $draft = DB::transaction(function () use ($draftId, $actor, $note) {
                $draft = $this->visibleQuery($actor)->lockForUpdate()->findOrFail($draftId);
                $this->ensureReadableSharedSnapshot($draft, $actor);
                $this->ensureReviewerIsNotCreator($draft, $actor);
                $this->ensureStatus($draft, ['pending_review'], 'Only a pending-review campaign draft can be rejected.');
                $this->ensureMutablePlannedChannel($draft);
                $fromStatus = $draft->status;

                $draft->forceFill([
                    'status' => 'rejected',
                    'reviewed_by' => $actor->id,
                    'reviewed_at' => now(),
                    'review_decision' => 'rejected',
                    'review_note' => $note,
                    'approved_snapshot_signature' => null,
                    'approved_snapshot_signature_version' => null,
                    'approved_snapshot_at' => null,
                    'approved_recipient_set_signature' => null,
                    'approved_recipient_set_signature_version' => null,
                    'approved_recipient_count' => null,
                    'updated_by' => $actor->id,
                ])->save();

                $draft = $draft->refresh();
                $this->recordApprovalHistory('rejected', $draft, $actor, $fromStatus, 'rejected', $note);
                $this->recordActivity('crm_campaign_draft_rejected', $draft, $actor->id, 'CRM campaign draft rejected', [
                    'review_note_present' => true,
                ]);

                return $draft;
            });
        } catch (\Throwable $exception) {
            $this->logFailure('reject', $draftId, $actor->id, $exception);
            throw $exception;
        }

        return $this->freshWithRelations($draft);
    }

    public function returnToDraft(int $draftId, array $data, User $actor): CrmCampaignDraft
    {
        $this->ensureStorage();
        $this->ensureApprovalHistoryStorage();
        $this->authorize($actor, 'update');
        $note = $this->nullableBoundedString($data['review_note'] ?? null, 'review_note', 2000);

        try {
            $draft = DB::transaction(function () use ($draftId, $actor, $note) {
                $draft = $this->visibleQuery($actor)->lockForUpdate()->findOrFail($draftId);
                $this->authorizeOwner($draft, $actor, 'return to draft');
                $this->ensureStatus($draft, self::REVIEWABLE_STATUSES, 'Only a submitted, approved, or rejected campaign can be returned to draft planning.');
                $this->ensureMutablePlannedChannel($draft);
                if ($this->hasActiveReleasedDispatchRun((int) $draft->id)) {
                    $this->throwValidation('campaign_draft', 'Cancel the active provider-neutral dispatch run before returning this campaign to draft planning.');
                }
                if ($this->hasActiveExecutionBatch((int) $draft->id)) {
                    $this->throwValidation('campaign_draft', 'Cancel the active provider-neutral execution batch before returning this campaign to draft planning.');
                }
                $fromStatus = $draft->status;
                $metadata = [
                    'previous_review_decision' => $draft->review_decision,
                    'previous_reviewed_by' => $draft->reviewed_by,
                    'previous_approved_snapshot_signature' => $draft->approved_snapshot_signature,
                    'previous_approved_recipient_set_signature' => $draft->approved_recipient_set_signature,
                    'previous_approved_recipient_count' => $draft->approved_recipient_count,
                ];

                $this->recordApprovalHistory('returned_to_draft', $draft, $actor, $fromStatus, 'draft', $note, $metadata);

                $draft->forceFill([
                    'status' => 'draft',
                    'submitted_by' => null,
                    'submitted_at' => null,
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                    'review_decision' => null,
                    'review_note' => null,
                    'approved_snapshot_signature' => null,
                    'approved_snapshot_signature_version' => null,
                    'approved_snapshot_at' => null,
                    'approved_recipient_set_signature' => null,
                    'approved_recipient_set_signature_version' => null,
                    'approved_recipient_count' => null,
                    'updated_by' => $actor->id,
                ])->save();

                $draft = $draft->refresh();
                $this->recordActivity('crm_campaign_draft_returned_to_draft', $draft, $actor->id, 'CRM campaign returned to draft planning', [
                    'from_status' => $fromStatus,
                    'review_note_present' => $note !== null,
                ]);

                return $draft;
            });
        } catch (\Throwable $exception) {
            $this->logFailure('return_to_draft', $draftId, $actor->id, $exception);
            throw $exception;
        }

        return $this->freshWithRelations($draft);
    }

    public function refreshAudienceSnapshot(int $draftId, User $actor): CrmCampaignDraft
    {
        $this->ensureStorage();
        $this->authorize($actor, 'update');

        try {
            $draft = DB::transaction(function () use ($draftId, $actor) {
                $draft = $this->visibleQuery($actor)->lockForUpdate()->findOrFail($draftId);
                $this->authorizeOwner($draft, $actor, 'refresh the audience snapshot for');
                $this->ensureDraft($draft);
                $this->ensureMutablePlannedChannel($draft);
                $previousSignature = $draft->audience_snapshot_signature;
                $snapshot = $this->audienceSnapshot((int) $draft->audience_saved_segment_id, $actor, true, (string) $draft->visibility);

                $draft->forceFill([
                    'audience_saved_segment_id' => $snapshot['id'],
                    'audience_segment_name_snapshot' => $snapshot['name'],
                    'audience_segment_visibility_snapshot' => $snapshot['visibility'],
                    'audience_filters_json' => $snapshot['filters'],
                    'audience_snapshot_at' => $snapshot['snapshot_at'],
                    'audience_snapshot_signature' => $snapshot['signature'],
                    'audience_snapshot_signature_version' => $snapshot['signature_version'],
                    'audience_snapshot_share_token' => $snapshot['share_token'],
                    'approved_snapshot_signature' => null,
                    'approved_snapshot_signature_version' => null,
                    'approved_snapshot_at' => null,
                    'approved_recipient_set_signature' => null,
                    'approved_recipient_set_signature_version' => null,
                    'approved_recipient_count' => null,
                    'updated_by' => $actor->id,
                ])->save();

                $draft = $draft->refresh();
                $this->recordActivity('crm_campaign_draft_audience_snapshot_refreshed', $draft, $actor->id, 'CRM campaign draft audience snapshot refreshed', [
                    'previous_audience_snapshot_signature' => $previousSignature,
                    'audience_snapshot_signature' => $draft->audience_snapshot_signature,
                ]);

                return $draft;
            });
        } catch (\Throwable $exception) {
            $this->logFailure('refresh_audience_snapshot', $draftId, $actor->id, $exception);
            throw $exception;
        }

        return $this->freshWithRelations($draft);
    }

    public function create(array $data, User $actor): CrmCampaignDraft
    {
        $this->ensureStorage();
        $this->authorize($actor, 'create');
        $data = $this->normalizedDraftInput($data);

        try {
            $draft = DB::transaction(function () use ($data, $actor) {
                $snapshot = $this->audienceSnapshot((int) $data['audience_saved_segment_id'], $actor, true, $data['visibility']);

                $draft = CrmCampaignDraft::create([
                    'product_website_id' => $actor->product_website_id ?? null,
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'planned_channel' => $data['planned_channel'],
                    'subject' => $data['subject'],
                    'message_body' => $data['message_body'],
                    'audience_saved_segment_id' => $snapshot['id'],
                    'audience_segment_name_snapshot' => $snapshot['name'],
                    'audience_segment_visibility_snapshot' => $snapshot['visibility'],
                    'audience_filters_json' => $snapshot['filters'],
                    'audience_snapshot_at' => $snapshot['snapshot_at'],
                    'audience_snapshot_signature' => $snapshot['signature'],
                    'audience_snapshot_signature_version' => $snapshot['signature_version'],
                    'audience_snapshot_share_token' => $snapshot['share_token'],
                    'visibility' => $data['visibility'],
                    'status' => 'draft',
                    'created_by' => $actor->id,
                    'updated_by' => $actor->id,
                ]);

                $this->recordActivity('crm_campaign_draft_created', $draft, $actor->id, 'CRM campaign draft created');

                return $draft;
            });
        } catch (\Throwable $exception) {
            $this->logFailure('create', null, $actor->id, $exception);
            throw $exception;
        }

        return $this->freshWithRelations($draft);
    }

    public function update(int $draftId, array $data, User $actor): CrmCampaignDraft
    {
        $this->ensureStorage();
        $this->authorize($actor, 'update');
        $data = $this->normalizedDraftInput($data);

        try {
            $draft = DB::transaction(function () use ($draftId, $data, $actor) {
                $draft = $this->visibleQuery($actor)->lockForUpdate()->findOrFail($draftId);
                $this->authorizeOwner($draft, $actor, 'update');
                $this->ensureDraft($draft);
                $this->ensureMutablePlannedChannel($draft);

                $snapshot = (int) $draft->audience_saved_segment_id === (int) $data['audience_saved_segment_id']
                    ? $this->existingAudienceSnapshot($draft, $data['visibility'])
                    : $this->audienceSnapshot((int) $data['audience_saved_segment_id'], $actor, true, $data['visibility']);

                $draft->forceFill([
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'planned_channel' => $data['planned_channel'],
                    'subject' => $data['subject'],
                    'message_body' => $data['message_body'],
                    'audience_saved_segment_id' => $snapshot['id'],
                    'audience_segment_name_snapshot' => $snapshot['name'],
                    'audience_segment_visibility_snapshot' => $snapshot['visibility'],
                    'audience_filters_json' => $snapshot['filters'],
                    'audience_snapshot_at' => $snapshot['snapshot_at'],
                    'audience_snapshot_signature' => $snapshot['signature'],
                    'audience_snapshot_signature_version' => $snapshot['signature_version'],
                    'audience_snapshot_share_token' => $snapshot['share_token'],
                    'visibility' => $data['visibility'],
                    'submitted_by' => null,
                    'submitted_at' => null,
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                    'review_decision' => null,
                    'review_note' => null,
                    'approved_snapshot_signature' => null,
                    'approved_snapshot_signature_version' => null,
                    'approved_snapshot_at' => null,
                    'approved_recipient_set_signature' => null,
                    'approved_recipient_set_signature_version' => null,
                    'approved_recipient_count' => null,
                    'updated_by' => $actor->id,
                ])->save();

                $draft = $draft->refresh();
                $this->recordActivity('crm_campaign_draft_updated', $draft, $actor->id, 'CRM campaign draft updated');

                return $draft;
            });
        } catch (\Throwable $exception) {
            $this->logFailure('update', $draftId, $actor->id, $exception);
            throw $exception;
        }

        return $this->freshWithRelations($draft);
    }

    public function archive(int $draftId, User $actor): CrmCampaignDraft
    {
        $this->ensureStorage();
        $this->authorize($actor, 'delete');

        try {
            $draft = DB::transaction(function () use ($draftId, $actor) {
                $draft = $this->visibleQuery($actor)->lockForUpdate()->findOrFail($draftId);
                $this->authorizeOwner($draft, $actor, 'archive');
                $this->ensureDraft($draft);
                $this->ensureMutablePlannedChannel($draft);

                $draft->forceFill([
                    'status' => 'archived',
                    'archived_by' => $actor->id,
                    'archived_at' => now(),
                    'updated_by' => $actor->id,
                ])->save();

                $draft = $draft->refresh();
                $this->recordActivity('crm_campaign_draft_archived', $draft, $actor->id, 'CRM campaign draft archived');

                return $draft;
            });
        } catch (\Throwable $exception) {
            $this->logFailure('archive', $draftId, $actor->id, $exception);
            throw $exception;
        }

        return $this->freshWithRelations($draft);
    }

    public function payload(CrmCampaignDraft $draft, User $actor, bool $details = false): array
    {
        [$snapshot, $snapshotValid, $snapshotValidationMessage, $integrity] = $this->storedAudienceSnapshotState($draft);
        $filters = $snapshot['filters'] ?? [];
        $isOwner = (int) $draft->created_by === (int) $actor->id;
        $legacyDisabledChannel = self::isLegacyDisabledPlannedChannel($draft->planned_channel);
        $smsExecutionEnabled = self::isRealSendChannel($draft->planned_channel);
        $dispatchLedgerEnabled = self::isDispatchLedgerChannel($draft->planned_channel);
        $emailPlanningOnly = trim((string) $draft->planned_channel) === 'email';

        if (!$isOwner && !$snapshotValid) {
            return $this->redactedUnavailablePayload($draft, $snapshotValidationMessage, $integrity);
        }

        $approvalIntegrity = $this->approvedSnapshotIntegrityPresentation($draft);
        $canEdit = !$legacyDisabledChannel && $draft->status === 'draft' && $isOwner && $this->can($actor, 'update');
        $canArchive = !$legacyDisabledChannel && $draft->status === 'draft' && $isOwner && $this->can($actor, 'delete');
        $canSubmitForReview = !$legacyDisabledChannel && $draft->status === 'draft' && $isOwner && $this->can($actor, 'update');
        $hasActiveExecutionBatch = $this->hasActiveExecutionBatch((int) $draft->id);
        $canReturnToDraft = !$legacyDisabledChannel && in_array($draft->status, self::REVIEWABLE_STATUSES, true) && !$hasActiveExecutionBatch && $isOwner && $this->can($actor, 'update');
        $canRefreshAudienceSnapshot = !$legacyDisabledChannel && $draft->status === 'draft' && $isOwner && $this->can($actor, 'update') && $this->can($actor, 'read', 'crm.saved-customer-segments.list');
        $canViewDispatchPreparations = $this->can($actor, 'read', 'crm.campaign-drafts.prepare-dispatch');
        $canPrepareDispatch = $dispatchLedgerEnabled && $draft->status === 'approved' && !$hasActiveExecutionBatch && $this->can($actor, 'create', 'crm.campaign-drafts.prepare-dispatch');
        $canViewDispatchRuns = $this->can($actor, 'read', 'crm.campaign-drafts.release-dispatch');
        $canReleaseDispatch = $dispatchLedgerEnabled && $draft->status === 'approved' && !$hasActiveExecutionBatch && $this->can($actor, 'create', 'crm.campaign-drafts.release-dispatch');
        $canViewDispatchExecutionBatches = $this->can($actor, 'read', 'crm.campaign-drafts.claim-dispatch-execution');
        $canClaimDispatchExecution = $dispatchLedgerEnabled && $draft->status === 'approved' && !$hasActiveExecutionBatch && $this->can($actor, 'create', 'crm.campaign-drafts.claim-dispatch-execution');
        $canViewDispatchAttempts = $this->can($actor, 'read', 'crm.campaign-drafts.prepare-dispatch-attempt');
        $canPrepareDispatchAttempt = $dispatchLedgerEnabled && $draft->status === 'approved' && $hasActiveExecutionBatch && $this->can($actor, 'create', 'crm.campaign-drafts.prepare-dispatch-attempt');
        $canReview = !$legacyDisabledChannel
            && $draft->status === 'pending_review'
            && !$isOwner
            && $this->can($actor, 'update', 'crm.campaign-drafts.approve');

        return [
            'id' => (int) $draft->id,
            'name' => $draft->name,
            'description' => $draft->description,
            'planned_channel' => $draft->planned_channel,
            'planned_channel_label' => self::channelLabel($draft->planned_channel),
            'channel_boundary_notice' => self::channelBoundaryNotice($draft->planned_channel),
            'legacy_disabled_channel' => $legacyDisabledChannel,
            'sms_execution_enabled' => $smsExecutionEnabled,
            'dispatch_ledger_enabled' => $dispatchLedgerEnabled,
            'email_planning_only' => $emailPlanningOnly,
            'real_send_available' => $smsExecutionEnabled,
            'subject' => $draft->subject,
            'message_body' => $details ? $draft->message_body : null,
            'visibility' => $draft->visibility,
            'status' => $draft->status,
            'audience_saved_segment_id' => $draft->audience_saved_segment_id ? (int) $draft->audience_saved_segment_id : null,
            'audience_segment_name_snapshot' => $draft->audience_segment_name_snapshot,
            'audience_segment_visibility_snapshot' => $draft->audience_segment_visibility_snapshot,
            'audience_snapshot_at' => optional($draft->audience_snapshot_at)->format('Y-m-d h:i a'),
            'audience_snapshot_integrity' => $integrity['status'],
            'audience_snapshot_integrity_label' => $integrity['label'],
            'audience_snapshot_integrity_message' => $integrity['message'],
            'audience_filters_valid' => $snapshotValid,
            'audience_filters_validation_message' => $snapshotValidationMessage,
            'audience_filters_summary' => $snapshotValid
                ? $this->savedSegmentService->filterSummaryFor($filters)
                : [[
                    'key' => 'invalid_definition',
                    'label' => 'Audience snapshot',
                    'value' => 'Invalid stored definition',
                ]],
            'approval_snapshot_integrity' => $approvalIntegrity['status'],
            'approval_snapshot_integrity_label' => $approvalIntegrity['label'],
            'approval_snapshot_integrity_message' => $approvalIntegrity['message'],
            'submitted_by' => $draft->submitted_by ? (int) $draft->submitted_by : null,
            'submitted_by_name' => $draft->relationLoaded('submitter') ? optional($draft->submitter)->name : null,
            'submitted_at' => optional($draft->submitted_at)->format('Y-m-d h:i a'),
            'reviewed_by' => $draft->reviewed_by ? (int) $draft->reviewed_by : null,
            'reviewed_by_name' => $draft->relationLoaded('reviewer') ? optional($draft->reviewer)->name : null,
            'reviewed_at' => optional($draft->reviewed_at)->format('Y-m-d h:i a'),
            'review_decision' => $draft->review_decision,
            'review_note' => $details ? $draft->review_note : null,
            'approved_snapshot_at' => optional($draft->approved_snapshot_at)->format('Y-m-d h:i a'),
            'approved_recipient_count' => is_numeric($draft->approved_recipient_count) ? (int) $draft->approved_recipient_count : null,
            'creator' => $draft->relationLoaded('creator') ? optional($draft->creator)->name : null,
            'updater' => $draft->relationLoaded('updater') ? optional($draft->updater)->name : null,
            'archiver' => $draft->relationLoaded('archiver') ? optional($draft->archiver)->name : null,
            'archived_at' => optional($draft->archived_at)->format('Y-m-d h:i a'),
            'created_at' => optional($draft->created_at)->format('Y-m-d h:i a'),
            'updated_at' => optional($draft->updated_at)->format('Y-m-d h:i a'),
            'can_edit' => $canEdit,
            'can_archive' => $canArchive,
            'can_submit_for_review' => $canSubmitForReview,
            'can_approve' => $canReview,
            'can_reject' => $canReview,
            'can_return_to_draft' => $canReturnToDraft,
            'can_refresh_audience_snapshot' => $canRefreshAudienceSnapshot,
            'can_view_dispatch_preparations' => $canViewDispatchPreparations,
            'can_prepare_dispatch' => $canPrepareDispatch,
            'can_view_dispatch_runs' => $canViewDispatchRuns,
            'can_release_dispatch' => $canReleaseDispatch,
            'can_view_dispatch_execution_batches' => $canViewDispatchExecutionBatches,
            'can_claim_dispatch_execution' => $canClaimDispatchExecution,
            'can_view_dispatch_attempts' => $canViewDispatchAttempts,
            'can_prepare_dispatch_attempt' => $canPrepareDispatchAttempt,
            'details_url' => Route::has('crm.campaign-drafts.show') ? route('crm.campaign-drafts.show', ['draft' => $draft->id]) : null,
            'preview_url' => $snapshotValid && Route::has('crm.campaign-drafts.preview') ? route('crm.campaign-drafts.preview', ['draft' => $draft->id]) : null,
            'preflight_url' => Route::has('crm.campaign-drafts.preflight') ? route('crm.campaign-drafts.preflight', ['draft' => $draft->id]) : null,
            'approval_history_url' => Route::has('crm.campaign-drafts.approval-history') ? route('crm.campaign-drafts.approval-history', ['draft' => $draft->id]) : null,
            'refresh_audience_snapshot_url' => $canRefreshAudienceSnapshot && Route::has('crm.campaign-drafts.refresh-audience-snapshot') ? route('crm.campaign-drafts.refresh-audience-snapshot', ['draft' => $draft->id]) : null,
            'dispatch_preparation_preview_url' => $dispatchLedgerEnabled && $canViewDispatchPreparations && Route::has('crm.campaign-drafts.dispatch-preparations.preview') ? route('crm.campaign-drafts.dispatch-preparations.preview', ['draft' => $draft->id]) : null,
            'dispatch_preparation_history_url' => $canViewDispatchPreparations && Route::has('crm.campaign-drafts.dispatch-preparations.history') ? route('crm.campaign-drafts.dispatch-preparations.history', ['draft' => $draft->id]) : null,
            'dispatch_run_preview_url' => $dispatchLedgerEnabled && $canViewDispatchRuns && Route::has('crm.campaign-drafts.dispatch-runs.preview') ? route('crm.campaign-drafts.dispatch-runs.preview', ['draft' => $draft->id]) : null,
            'dispatch_run_history_url' => $canViewDispatchRuns && Route::has('crm.campaign-drafts.dispatch-runs.history') ? route('crm.campaign-drafts.dispatch-runs.history', ['draft' => $draft->id]) : null,
            'dispatch_execution_batch_preview_url' => $dispatchLedgerEnabled && $canViewDispatchExecutionBatches && Route::has('crm.campaign-drafts.dispatch-execution-batches.preview') ? route('crm.campaign-drafts.dispatch-execution-batches.preview', ['draft' => $draft->id]) : null,
            'dispatch_execution_batch_history_url' => $canViewDispatchExecutionBatches && Route::has('crm.campaign-drafts.dispatch-execution-batches.history') ? route('crm.campaign-drafts.dispatch-execution-batches.history', ['draft' => $draft->id]) : null,
            'dispatch_attempt_preview_url' => $dispatchLedgerEnabled && $canViewDispatchAttempts && Route::has('crm.campaign-drafts.dispatch-attempts.preview') ? route('crm.campaign-drafts.dispatch-attempts.preview', ['draft' => $draft->id]) : null,
            'dispatch_attempt_history_url' => $canViewDispatchAttempts && Route::has('crm.campaign-drafts.dispatch-attempts.history') ? route('crm.campaign-drafts.dispatch-attempts.history', ['draft' => $draft->id]) : null,
        ];
    }

    public function verifiedStoredAudienceSnapshot(CrmCampaignDraft $draft): array
    {
        $snapshot = $this->storedAudienceSnapshot($draft);
        if (($snapshot['integrity_status'] ?? null) !== 'verified') {
            $this->throwValidation('campaign_draft', 'Only a verified sealed audience snapshot can be used for recipient preparation.');
        }

        return $snapshot;
    }

    public function assertApprovedSnapshotIntegrity(CrmCampaignDraft $draft): void
    {
        $state = $this->approvedSnapshotIntegrityState($draft);
        if (($state['status'] ?? null) !== 'verified') {
            $this->throwValidation('campaign_draft', $state['message'] ?? 'Approved campaign planning integrity could not be verified.');
        }
    }

    protected function audienceSnapshot(int $segmentId, User $actor, bool $lockForUpdate, string $draftVisibility): array
    {
        $snapshot = $this->savedSegmentService->applicableSnapshot($segmentId, $actor, $lockForUpdate);
        if ($snapshot['visibility'] === 'private' && $draftVisibility !== 'private') {
            $this->throwValidation('visibility', 'A private saved segment can only be attached to a private campaign draft.');
        }
        $snapshot['snapshot_at'] = now();

        return $this->withSnapshotSignature($snapshot);
    }

    protected function existingAudienceSnapshot(CrmCampaignDraft $draft, string $draftVisibility): array
    {
        $snapshot = $this->storedAudienceSnapshot($draft);
        if ($snapshot['visibility'] === 'private' && $draftVisibility !== 'private') {
            $this->throwValidation('visibility', 'A private saved segment snapshot can only be retained by a private campaign draft.');
        }

        if ($snapshot['integrity_status'] === 'legacy_unsigned') {
            $snapshot = $this->withSnapshotSignature($snapshot);
        }

        return $snapshot;
    }

    protected function previewPayload(?string $segmentName, ?string $segmentVisibility, array $filters, array $summary, ?CrmCampaignDraft $draft): array
    {
        return [
            'draft_id' => $draft ? (int) $draft->id : null,
            'draft_name' => $draft?->name,
            'segment_name' => $segmentName,
            'segment_visibility' => $segmentVisibility,
            'estimated_recipients' => $this->customerSegmentService->worklistQuery($filters)->count(),
            'filters_summary' => $summary,
            'read_only' => true,
            'notice' => 'Audience preview is read only. No messages are sent and no customer records are changed.',
        ];
    }

    protected function normalizedDraftInput(array $data): array
    {
        $visibility = trim((string) ($data['visibility'] ?? ''));
        if (!in_array($visibility, self::VISIBILITIES, true)) {
            $this->throwValidation('visibility', 'The selected campaign draft visibility is invalid.');
        }

        $plannedChannel = $this->nullableBoundedString($data['planned_channel'] ?? null, 'planned_channel', 30);
        if ($plannedChannel !== null && !in_array($plannedChannel, self::ACTIVE_PLANNED_CHANNELS, true)) {
            $this->throwValidation('planned_channel', 'Choose BulkSMSBD SMS or Email. Newsletter, WhatsApp, and other legacy campaign channels are disabled.');
        }

        $segmentId = $this->positiveInteger($data['audience_saved_segment_id'] ?? null, 'audience_saved_segment_id');
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            $this->throwValidation('name', 'The campaign draft name is required.');
        }
        if ($this->stringLength($name) > 160) {
            $this->throwValidation('name', 'The campaign draft name may not be greater than 160 characters.');
        }

        return [
            'name' => $name,
            'description' => $this->nullableBoundedString($data['description'] ?? null, 'description', 2000),
            'planned_channel' => $plannedChannel,
            'subject' => $this->nullableBoundedString($data['subject'] ?? null, 'subject', 255),
            'message_body' => $this->nullableBoundedString($data['message_body'] ?? null, 'message_body', 10000),
            'audience_saved_segment_id' => $segmentId,
            'visibility' => $visibility,
        ];
    }

    protected function storedAudienceFilters(CrmCampaignDraft $draft): array
    {
        $rawFilters = $draft->getRawOriginal('audience_filters_json');
        if (is_string($rawFilters)) {
            $filters = json_decode($rawFilters, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($filters)) {
                $this->throwInvalidStoredAudience();
            }
        } elseif (is_array($rawFilters)) {
            $filters = $rawFilters;
        } else {
            $this->throwInvalidStoredAudience();
        }

        try {
            return $this->savedSegmentService->normalizedFilters($filters);
        } catch (ValidationException $exception) {
            $this->throwInvalidStoredAudience($this->firstValidationMessage($exception));
        }
    }

    protected function storedAudienceFilterState(CrmCampaignDraft $draft): array
    {
        [$snapshot, $valid, $message] = $this->storedAudienceSnapshotState($draft);

        return [$snapshot['filters'] ?? [], $valid, $message];
    }

    protected function storedAudienceSnapshot(CrmCampaignDraft $draft): array
    {
        $segmentId = $this->storedPositiveInteger($draft->audience_saved_segment_id, 'The stored source segment ID is invalid.');
        $name = trim((string) $draft->audience_segment_name_snapshot);
        if ($name === '' || $this->stringLength($name) > 160) {
            $this->throwInvalidStoredAudience('The stored source segment name is invalid.');
        }

        $visibility = trim((string) $draft->audience_segment_visibility_snapshot);
        if (!in_array($visibility, CrmSavedCustomerSegmentService::VISIBILITIES, true)) {
            $this->throwInvalidStoredAudience('The stored source visibility is invalid.');
        }

        $snapshotAt = $this->normalizedSnapshotTimestamp($draft->getRawOriginal('audience_snapshot_at'));
        $snapshot = [
            'id' => $segmentId,
            'name' => $name,
            'visibility' => $visibility,
            'filters' => $this->storedAudienceFilters($draft),
            'snapshot_at' => $snapshotAt,
        ];
        $integrity = $this->snapshotIntegrityState($draft, $snapshot);
        if ($integrity['status'] === 'invalid') {
            $this->throwInvalidStoredAudience($integrity['message']);
        }

        $snapshot['signature'] = $integrity['signature'];
        $snapshot['signature_version'] = $integrity['signature_version'];
        $snapshot['share_token'] = $integrity['share_token'];
        $snapshot['integrity_status'] = $integrity['status'];

        return $snapshot;
    }

    protected function storedAudienceSnapshotState(CrmCampaignDraft $draft): array
    {
        try {
            $snapshot = $this->storedAudienceSnapshot($draft);
            $integrity = $this->snapshotIntegrityPresentation($snapshot['integrity_status']);

            return [$snapshot, true, null, $integrity];
        } catch (ValidationException $exception) {
            return [[], false, $this->firstValidationMessage($exception), $this->snapshotIntegrityPresentation('invalid')];
        }
    }

    protected function withSnapshotSignature(array $snapshot): array
    {
        $snapshot = $this->normalizedSnapshotEnvelopeForSignature($snapshot);
        $snapshot['signature_version'] = self::SNAPSHOT_SIGNATURE_VERSION;
        $snapshot['signature'] = hash_hmac('sha256', $this->snapshotSignatureMaterial($snapshot), $this->snapshotSignatureKey());
        $snapshot['share_token'] = $snapshot['visibility'] === 'shared'
            ? $this->snapshotShareToken($snapshot['signature'])
            : null;
        $snapshot['integrity_status'] = 'verified';

        return $snapshot;
    }

    protected function normalizedSnapshotEnvelopeForSignature(array $snapshot): array
    {
        $snapshot['id'] = $this->storedPositiveInteger($snapshot['id'] ?? null, 'The stored source segment ID is invalid.');
        $snapshot['name'] = trim((string) ($snapshot['name'] ?? ''));
        if ($snapshot['name'] === '' || $this->stringLength($snapshot['name']) > 160) {
            $this->throwInvalidStoredAudience('The stored source segment name is invalid.');
        }

        $snapshot['visibility'] = trim((string) ($snapshot['visibility'] ?? ''));
        if (!in_array($snapshot['visibility'], CrmSavedCustomerSegmentService::VISIBILITIES, true)) {
            $this->throwInvalidStoredAudience('The stored source visibility is invalid.');
        }

        if (!isset($snapshot['filters']) || !is_array($snapshot['filters'])) {
            $this->throwInvalidStoredAudience('The stored audience filters are invalid.');
        }

        $snapshot['snapshot_at'] = $this->normalizedSnapshotTimestamp($snapshot['snapshot_at'] ?? null);

        return $snapshot;
    }

    protected function snapshotIntegrityState(CrmCampaignDraft $draft, array $snapshot): array
    {
        $signature = $this->nullableString($draft->audience_snapshot_signature);
        $version = $draft->audience_snapshot_signature_version;
        $shareToken = $this->nullableString($draft->audience_snapshot_share_token);

        if ($signature === null && ($version === null || $version === '') && $shareToken === null) {
            return [
                'status' => 'legacy_unsigned',
                'signature' => null,
                'signature_version' => null,
                'share_token' => null,
                'message' => null,
            ];
        }

        if ($signature === null || !is_numeric($version) || (int) $version !== self::SNAPSHOT_SIGNATURE_VERSION) {
            return [
                'status' => 'invalid',
                'signature' => $signature,
                'signature_version' => is_numeric($version) ? (int) $version : null,
                'share_token' => $shareToken,
                'message' => 'The stored audience snapshot signature metadata is invalid.',
            ];
        }

        $signature = strtolower($signature);
        if (preg_match('/^[a-f0-9]{64}$/D', $signature) !== 1) {
            return [
                'status' => 'invalid',
                'signature' => $signature,
                'signature_version' => (int) $version,
                'share_token' => $shareToken,
                'message' => 'The stored audience snapshot signature is malformed.',
            ];
        }

        $expected = hash_hmac('sha256', $this->snapshotSignatureMaterial($snapshot), $this->snapshotSignatureKey());
        if (!hash_equals($expected, $signature)) {
            return [
                'status' => 'invalid',
                'signature' => $signature,
                'signature_version' => (int) $version,
                'share_token' => $shareToken,
                'message' => 'The stored audience snapshot failed integrity verification.',
            ];
        }

        $expectedShareToken = $snapshot['visibility'] === 'shared'
            ? $this->snapshotShareToken($expected)
            : null;
        if (($expectedShareToken === null && $shareToken !== null)
            || ($expectedShareToken !== null
                && ($shareToken === null
                    || preg_match('/^[a-f0-9]{64}$/D', strtolower($shareToken)) !== 1
                    || !hash_equals($expectedShareToken, strtolower($shareToken))))) {
            return [
                'status' => 'invalid',
                'signature' => $signature,
                'signature_version' => (int) $version,
                'share_token' => $shareToken,
                'message' => 'The stored audience snapshot sharing token failed integrity verification.',
            ];
        }

        return [
            'status' => 'verified',
            'signature' => $signature,
            'signature_version' => (int) $version,
            'share_token' => $shareToken,
            'message' => null,
        ];
    }

    protected function snapshotIntegrityPresentation(string $status): array
    {
        return match ($status) {
            'verified' => [
                'status' => 'verified',
                'label' => 'Verified sealed snapshot',
                'message' => 'This historical audience snapshot has passed integrity verification.',
            ],
            'legacy_unsigned' => [
                'status' => 'legacy_unsigned',
                'label' => 'Legacy unsigned snapshot',
                'message' => 'This Stage 18 historical snapshot passed strict structural validation but predates integrity sealing. A creator-owned update will seal the retained snapshot without refreshing its audience definition.',
            ],
            default => [
                'status' => 'invalid',
                'label' => 'Invalid snapshot',
                'message' => 'This stored audience snapshot failed validation or integrity verification.',
            ],
        };
    }

    protected function snapshotSignatureMaterial(array $snapshot): string
    {
        $payload = [
            'audience_saved_segment_id' => (int) $snapshot['id'],
            'audience_segment_name_snapshot' => (string) $snapshot['name'],
            'audience_segment_visibility_snapshot' => (string) $snapshot['visibility'],
            'audience_filters_json' => $this->canonicalizeForSignature($snapshot['filters']),
            'audience_snapshot_at' => $this->normalizedSnapshotTimestamp($snapshot['snapshot_at']),
        ];

        return json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    protected function canonicalizeForSignature($value)
    {
        if (!is_array($value)) {
            return $value;
        }

        if ($this->isListArray($value)) {
            return array_map(fn ($item) => $this->canonicalizeForSignature($item), $value);
        }

        ksort($value);
        foreach ($value as $key => $item) {
            $value[$key] = $this->canonicalizeForSignature($item);
        }

        return $value;
    }

    protected function snapshotShareToken(string $signature): string
    {
        return hash_hmac('sha256', 'crm_campaign_draft_shared|' . $signature, $this->snapshotSignatureKey());
    }

    protected function snapshotSignatureKey(): string
    {
        $key = (string) config('app.key');
        if ($key === '') {
            $this->throwValidation('campaign_draft', 'CRM campaign snapshot sealing is unavailable because the application key is missing.');
        }

        return $key;
    }

    protected function normalizedSnapshotTimestamp($value): string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        $value = trim((string) $value);
        if ($value === '') {
            $this->throwInvalidStoredAudience('The stored audience snapshot timestamp is missing.');
        }

        $timestamp = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value);
        $errors = DateTimeImmutable::getLastErrors();
        if ($timestamp === false
            || (is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
            || $timestamp->format('Y-m-d H:i:s') !== $value) {
            $this->throwInvalidStoredAudience('The stored audience snapshot timestamp is invalid.');
        }

        return $timestamp->format('Y-m-d H:i:s');
    }

    protected function isListArray(array $value): bool
    {
        return $value === [] || array_keys($value) === range(0, count($value) - 1);
    }

    protected function storedPositiveInteger($value, string $reason): int
    {
        if (is_int($value) && $value > 0) {
            return $value;
        }
        if (is_string($value) && preg_match('/^[1-9][0-9]*$/D', $value) === 1) {
            $integer = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($integer !== false) {
                return $integer;
            }
        }

        $this->throwInvalidStoredAudience($reason);
    }

    protected function ensureReadableSharedSnapshot(CrmCampaignDraft $draft, User $actor): void
    {
        if ((int) $draft->created_by === (int) $actor->id) {
            return;
        }

        [, $valid] = $this->storedAudienceSnapshotState($draft);
        if (!$valid) {
            throw new AuthorizationException('This shared CRM campaign draft is unavailable because its audience snapshot failed integrity validation.');
        }
    }

    protected function redactedUnavailablePayload(CrmCampaignDraft $draft, ?string $message, array $integrity): array
    {
        return [
            'id' => (int) $draft->id,
            'name' => 'Unavailable campaign draft',
            'description' => null,
            'planned_channel' => null,
            'planned_channel_label' => 'Unavailable',
            'channel_boundary_notice' => 'Campaign channel information is unavailable because the stored audience snapshot failed integrity validation.',
            'legacy_disabled_channel' => false,
            'sms_execution_enabled' => false,
            'email_planning_only' => false,
            'real_send_available' => false,
            'subject' => null,
            'message_body' => null,
            'visibility' => null,
            'status' => 'unavailable',
            'audience_saved_segment_id' => null,
            'audience_segment_name_snapshot' => 'Unavailable audience snapshot',
            'audience_segment_visibility_snapshot' => null,
            'audience_snapshot_at' => null,
            'audience_snapshot_integrity' => $integrity['status'],
            'audience_snapshot_integrity_label' => $integrity['label'],
            'audience_snapshot_integrity_message' => $message ?: $integrity['message'],
            'audience_filters_valid' => false,
            'audience_filters_validation_message' => $message,
            'audience_filters_summary' => [],
            'approval_snapshot_integrity' => 'invalid',
            'approval_snapshot_integrity_label' => 'Unavailable approval state',
            'approval_snapshot_integrity_message' => 'Approval state is unavailable because the audience snapshot failed integrity validation.',
            'submitted_by' => null,
            'submitted_by_name' => null,
            'submitted_at' => null,
            'reviewed_by' => null,
            'reviewed_by_name' => null,
            'reviewed_at' => null,
            'review_decision' => null,
            'review_note' => null,
            'approved_snapshot_at' => null,
            'creator' => null,
            'updater' => null,
            'archiver' => null,
            'archived_at' => null,
            'created_at' => null,
            'updated_at' => null,
            'can_edit' => false,
            'can_archive' => false,
            'can_submit_for_review' => false,
            'can_approve' => false,
            'can_reject' => false,
            'can_return_to_draft' => false,
            'can_refresh_audience_snapshot' => false,
            'can_view_dispatch_preparations' => false,
            'can_prepare_dispatch' => false,
            'can_view_dispatch_runs' => false,
            'can_release_dispatch' => false,
            'can_view_dispatch_execution_batches' => false,
            'can_claim_dispatch_execution' => false,
            'details_url' => null,
            'preview_url' => null,
            'preflight_url' => null,
            'approval_history_url' => null,
            'refresh_audience_snapshot_url' => null,
            'dispatch_preparation_preview_url' => null,
            'dispatch_preparation_history_url' => null,
            'dispatch_run_preview_url' => null,
            'dispatch_run_history_url' => null,
            'dispatch_execution_batch_preview_url' => null,
            'dispatch_execution_batch_history_url' => null,
        ];
    }

    protected function visibleQuery(User $actor): Builder
    {
        return CrmCampaignDraft::query()->where(function (Builder $query) use ($actor) {
            $query->where('created_by', $actor->id)
                ->orWhere(function (Builder $shared) {
                    $shared->where('visibility', 'shared')
                        ->where('audience_segment_visibility_snapshot', 'shared')
                        ->whereNotNull('audience_snapshot_signature')
                        ->where('audience_snapshot_signature_version', self::SNAPSHOT_SIGNATURE_VERSION)
                        ->whereNotNull('audience_snapshot_share_token');
                });
        });
    }

    protected function loadUserRelations(Builder $query): void
    {
        if ($this->hasUserStorage()) {
            $query->with(['creator:id,name', 'updater:id,name', 'archiver:id,name', 'submitter:id,name', 'reviewer:id,name']);
        }
    }

    protected function freshWithRelations(CrmCampaignDraft $draft): CrmCampaignDraft
    {
        $draft = $draft->fresh();
        if ($this->hasUserStorage()) {
            $draft->load(['creator:id,name', 'updater:id,name', 'archiver:id,name', 'submitter:id,name', 'reviewer:id,name']);
        }

        return $draft;
    }

    protected function ensureStorage(): void
    {
        $requiredColumns = [
            'id', 'product_website_id', 'name', 'description', 'planned_channel', 'subject', 'message_body',
            'audience_saved_segment_id', 'audience_segment_name_snapshot', 'audience_segment_visibility_snapshot',
            'audience_filters_json', 'audience_snapshot_at', 'audience_snapshot_signature',
            'audience_snapshot_signature_version', 'audience_snapshot_share_token', 'submitted_by', 'submitted_at',
            'reviewed_by', 'reviewed_at', 'review_decision', 'review_note', 'approved_snapshot_signature',
            'approved_snapshot_signature_version', 'approved_snapshot_at', 'approved_recipient_set_signature',
            'approved_recipient_set_signature_version', 'approved_recipient_count', 'visibility', 'status', 'created_by', 'updated_by',
            'archived_by', 'archived_at', 'created_at', 'updated_at',
        ];

        if (!Schema::hasTable('crm_campaign_drafts')) {
            $this->throwValidation('campaign_draft', 'CRM campaign draft storage is not available. Run application migrations first.');
        }

        foreach ($requiredColumns as $column) {
            if (!Schema::hasColumn('crm_campaign_drafts', $column)) {
                $this->throwValidation('campaign_draft', 'CRM campaign draft storage is incomplete. Run application migrations first.');
            }
        }
    }

    protected function ensureMutablePlannedChannel(CrmCampaignDraft $draft): void
    {
        if (self::isLegacyDisabledPlannedChannel($draft->planned_channel)) {
            $this->throwValidation('planned_channel', 'This legacy campaign channel is preserved read-only. Create a new BulkSMSBD SMS or Email campaign draft instead.');
        }
    }

    protected function ensureDraft(CrmCampaignDraft $draft): void
    {
        if ($draft->status !== 'draft') {
            $this->throwValidation('campaign_draft', 'Only CRM campaign drafts in draft planning status can be modified.');
        }
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

    protected function authorize(User $actor, string $action): void
    {
        if (!$this->permissionService->userCan($actor, 'crm.campaign-drafts.list', $action)) {
            throw new AuthorizationException('You do not have permission to perform this CRM campaign draft action.');
        }
    }

    protected function authorizeApproval(User $actor): void
    {
        $this->authorize($actor, 'read');

        if (!$this->permissionService->userCan($actor, 'crm.campaign-drafts.approve', 'update')) {
            throw new AuthorizationException('You do not have permission to review CRM campaign drafts.');
        }
    }

    protected function can(User $actor, string $action, string $permissionKey = 'crm.campaign-drafts.list'): bool
    {
        $cacheKey = $actor->id . '|' . $permissionKey . '|' . $action;
        if (!array_key_exists($cacheKey, $this->permissionCache)) {
            $this->permissionCache[$cacheKey] = $this->permissionService->userCan($actor, $permissionKey, $action);
        }

        return $this->permissionCache[$cacheKey];
    }

    protected function authorizeOwner(CrmCampaignDraft $draft, User $actor, string $action): void
    {
        if ((int) $draft->created_by !== (int) $actor->id) {
            throw new AuthorizationException('Only the creator can ' . $action . ' this CRM campaign draft.');
        }
    }

    protected function ensureReviewerIsNotCreator(CrmCampaignDraft $draft, User $actor): void
    {
        if ((int) $draft->created_by === (int) $actor->id) {
            throw new AuthorizationException('Campaign creators cannot review or approve their own campaign drafts.');
        }
    }

    protected function ensureStatus(CrmCampaignDraft $draft, array $allowedStatuses, string $message): void
    {
        if (!in_array($draft->status, $allowedStatuses, true)) {
            $this->throwValidation('campaign_draft', $message);
        }
    }

    protected function recordActivity(string $type, CrmCampaignDraft $draft, int $actorId, string $subject, array $extraMetadata = []): void
    {
        [$snapshot, $snapshotValid, , $integrity] = $this->storedAudienceSnapshotState($draft);
        $filters = $snapshot['filters'] ?? [];
        $approvalIntegrity = $this->approvedSnapshotIntegrityPresentation($draft);
        $this->activityService->recordOrFail([
            'product_website_id' => $draft->product_website_id,
            'activity_type' => $type,
            'subject' => $subject,
            'description' => $subject . ': ' . $draft->name,
            'source_module' => 'crm_campaign_drafts',
            'source_id' => $draft->id,
            'performed_by' => $actorId,
            'metadata' => array_merge([
                'status' => $draft->status,
                'visibility' => $draft->visibility,
                'planned_channel' => $draft->planned_channel,
                'audience_saved_segment_id' => $draft->audience_saved_segment_id,
                'audience_snapshot_valid' => $snapshotValid,
                'audience_snapshot_integrity' => $integrity['status'],
                'approval_snapshot_integrity' => $approvalIntegrity['status'],
                'audience_filter_keys' => array_keys($filters),
                'audience_filter_count' => count($filters),
            ], $extraMetadata),
        ]);
    }

    protected function preflightPayload(CrmCampaignDraft $draft): array
    {
        [$snapshot, $snapshotValid, $snapshotValidationMessage, $audienceIntegrity] = $this->storedAudienceSnapshotState($draft);
        $estimatedRecipients = 0;
        $recipientCountMessage = null;

        if ($snapshotValid) {
            try {
                $estimatedRecipients = $this->customerSegmentService->worklistQuery($snapshot['filters'])->count();
            } catch (\Throwable $exception) {
                $recipientCountMessage = 'The live recipient estimate could not be calculated.';
                Log::warning('CRM campaign readiness recipient estimate failed.', [
                    'campaign_draft_id' => $draft->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        $approvalIntegrity = $this->approvedSnapshotIntegrityPresentation($draft);
        $plannedChannel = trim((string) $draft->planned_channel);
        $subjectRequired = in_array($plannedChannel, self::SUBJECT_REQUIRED_CHANNELS, true);
        $checks = [];
        $addCheck = function (string $key, string $label, bool $passed, string $message) use (&$checks) {
            $checks[] = [
                'key' => $key,
                'label' => $label,
                'passed' => $passed,
                'message' => $message,
            ];
        };

        $addCheck(
            'lifecycle_status',
            'Lifecycle status',
            in_array($draft->status, self::STATUSES, true) && $draft->status !== 'archived',
            $draft->status === 'archived' ? 'Archived campaigns are not send-ready.' : 'Lifecycle status is valid for readiness review.'
        );
        $addCheck(
            'campaign_name',
            'Campaign name',
            trim((string) $draft->name) !== '',
            trim((string) $draft->name) !== '' ? 'Campaign name is present.' : 'Campaign name is required.'
        );
        $addCheck(
            'planned_channel',
            'Supported planned channel',
            in_array($plannedChannel, self::SEND_READINESS_CHANNELS, true),
            in_array($plannedChannel, self::SEND_READINESS_CHANNELS, true)
                ? 'The planned channel is supported for campaign planning and governance review.'
                : 'Choose BulkSMSBD SMS or Email before submitting for review. Newsletter, WhatsApp, and other legacy campaign channels are disabled.'
        );
        $addCheck(
            'subject_requirement',
            'Subject requirement',
            !$subjectRequired || trim((string) $draft->subject) !== '',
            !$subjectRequired || trim((string) $draft->subject) !== ''
                ? 'Subject requirement is satisfied.'
                : 'A subject is required for Email campaign planning.'
        );
        $addCheck(
            'message_body',
            'Message body',
            trim((string) $draft->message_body) !== '',
            trim((string) $draft->message_body) !== '' ? 'Message body is present.' : 'Message body is required before review.'
        );
        $addCheck(
            'review_visibility',
            'Shared review visibility',
            $draft->visibility === 'shared',
            $draft->visibility === 'shared'
                ? 'Campaign visibility allows a separate approver to review this plan.'
                : 'Set campaign visibility to shared before submitting for independent review.'
        );
        $addCheck(
            'audience_snapshot_valid',
            'Audience snapshot structure',
            $snapshotValid,
            $snapshotValid ? 'Stored audience snapshot passed structural validation.' : ($snapshotValidationMessage ?: 'Stored audience snapshot is invalid.')
        );
        $addCheck(
            'audience_snapshot_sealed',
            'Audience snapshot integrity seal',
            $snapshotValid && $audienceIntegrity['status'] === 'verified',
            $snapshotValid && $audienceIntegrity['status'] === 'verified'
                ? 'Stored audience snapshot has a verified Stage 19 integrity seal.'
                : 'Save the draft with a valid active shared audience source so the audience snapshot is sealed.'
        );
        $addCheck(
            'recipient_count',
            'Estimated recipients',
            $recipientCountMessage === null && $estimatedRecipients > 0,
            $recipientCountMessage ?: ($estimatedRecipients > 0
                ? number_format($estimatedRecipients) . ' live recipients are currently estimated.'
                : 'The current audience estimate must be greater than zero.')
        );
        $addCheck(
            'approval_not_stale',
            'Approval integrity',
            $approvalIntegrity['status'] !== 'invalid',
            $approvalIntegrity['message']
        );

        $sendReadiness = collect($checks)->every(fn (array $check) => $check['passed']);
        $localChannelReadiness = $this->localChannelReadiness($draft, $plannedChannel);

        return [
            'draft_id' => (int) $draft->id,
            'draft_name' => $draft->name,
            'status' => $draft->status,
            'planned_channel' => $draft->planned_channel,
            'planned_channel_label' => self::channelLabel($draft->planned_channel),
            'channel_boundary_notice' => self::channelBoundaryNotice($draft->planned_channel),
            'real_send_available' => self::isRealSendChannel($draft->planned_channel),
            'email_planning_only' => trim((string) $draft->planned_channel) === 'email',
            'estimated_recipients' => (int) $estimatedRecipients,
            'send_readiness' => $sendReadiness,
            'send_readiness_label' => $sendReadiness ? 'Ready for governance review' : 'Not ready for governance review',
            'audience_snapshot_integrity' => $audienceIntegrity['status'],
            'approval_snapshot_integrity' => $approvalIntegrity['status'],
            'checks' => $checks,
            'local_channel_readiness' => $localChannelReadiness,
            'read_only' => true,
            'execution_available' => false,
            'notice' => 'This preflight is informational and read only. It does not send, schedule, queue, export, or dispatch any campaign message.',
        ];
    }

    protected function localChannelReadiness(CrmCampaignDraft $draft, string $plannedChannel): ?array
    {
        if ($plannedChannel !== 'email') {
            return null;
        }

        try {
            return $this->dispatchProviderRegistry->readiness(
                $plannedChannel,
                $draft->product_website_id === null ? null : (int) $draft->product_website_id
            );
        } catch (\Throwable $exception) {
            Log::warning('CRM Email campaign local SMTP readiness evaluation failed.', [
                'campaign_draft_id' => $draft->id,
                'exception_class' => get_class($exception),
            ]);

            return [
                'channel' => 'email',
                'provider_key' => 'smtp',
                'ready' => false,
                'ready_label' => 'Local SMTP readiness could not be inspected safely',
                'message' => 'Campaign planning and independent approval remain available. Real CRM Email dispatch remains unavailable.',
                'checks' => [[
                    'key' => 'smtp_readiness_inspection',
                    'label' => 'Local SMTP readiness inspection',
                    'passed' => false,
                    'message' => 'Local SMTP readiness could not be inspected safely.',
                ]],
                'local_configuration_only' => true,
                'execution_transport_available' => false,
                'remote_connectivity_checked' => false,
                'credential_fields_exposed' => false,
                'provider_endpoint_exposed' => false,
                'raw_gateway_record_exposed' => false,
                'provider_payload_exposed' => false,
                'provider_response_exposed' => false,
                'password_selected' => false,
                'planning_available' => true,
                'approval_available' => true,
                'automatic_retry_available' => false,
                'send_available' => false,
                'execute_available' => false,
            ];
        }
    }

    protected function ensurePreflightReady(array $preflight): void
    {
        if (!empty($preflight['send_readiness'])) {
            return;
        }

        $failed = collect($preflight['checks'] ?? [])
            ->filter(fn (array $check) => empty($check['passed']))
            ->pluck('label')
            ->filter()
            ->implode(', ');

        $this->throwValidation(
            'preflight',
            'Campaign send-readiness preflight failed' . ($failed !== '' ? ': ' . $failed . '.' : '.')
        );
    }

    protected function approvedSnapshotSignature(
        CrmCampaignDraft $draft,
        $approvedAt,
        ?string $recipientSetSignature = null,
        ?int $recipientSetSignatureVersion = null,
        ?int $recipientCount = null,
        ?int $signatureVersion = null
    ): string {
        $snapshot = $this->storedAudienceSnapshot($draft);
        if (($snapshot['integrity_status'] ?? null) !== 'verified') {
            $this->throwValidation('campaign_draft', 'Only a verified sealed audience snapshot can be approved.');
        }

        $signatureVersion = $signatureVersion ?? (is_numeric($draft->approved_snapshot_signature_version)
            ? (int) $draft->approved_snapshot_signature_version
            : self::APPROVED_SNAPSHOT_SIGNATURE_VERSION);

        if ($signatureVersion === self::APPROVED_SNAPSHOT_SIGNATURE_VERSION) {
            $recipientSetSignature = strtolower(trim((string) ($recipientSetSignature ?? $draft->approved_recipient_set_signature)));
            $recipientSetSignatureVersion = $recipientSetSignatureVersion ?? (is_numeric($draft->approved_recipient_set_signature_version) ? (int) $draft->approved_recipient_set_signature_version : null);
            $recipientCount = $recipientCount ?? (is_numeric($draft->approved_recipient_count) ? (int) $draft->approved_recipient_count : null);
            if (preg_match('/^[a-f0-9]{64}$/D', $recipientSetSignature) !== 1
                || $recipientSetSignatureVersion !== CrmCampaignRecipientSnapshotService::RECIPIENT_SET_SIGNATURE_VERSION
                || $recipientCount === null
                || $recipientCount <= 0) {
                $this->throwValidation('campaign_draft', 'Approved recipient-set seal metadata is missing or invalid.');
            }
        } elseif ($signatureVersion !== self::LEGACY_APPROVED_SNAPSHOT_SIGNATURE_VERSION) {
            $this->throwValidation('campaign_draft', 'Approved planning snapshot signature version is unsupported.');
        }

        return hash_hmac(
            'sha256',
            $this->approvedSnapshotSignatureMaterial($draft, $snapshot, $approvedAt, $signatureVersion, $recipientSetSignature, $recipientSetSignatureVersion, $recipientCount),
            $this->snapshotSignatureKey()
        );
    }

    protected function approvedSnapshotSignatureMaterial(
        CrmCampaignDraft $draft,
        array $snapshot,
        $approvedAt,
        int $signatureVersion,
        ?string $recipientSetSignature = null,
        ?int $recipientSetSignatureVersion = null,
        ?int $recipientCount = null
    ): string {
        $payload = [
            'crm_campaign_draft_id' => (int) $draft->id,
            'name' => (string) $draft->name,
            'description' => $draft->description === null ? null : (string) $draft->description,
            'planned_channel' => $draft->planned_channel === null ? null : (string) $draft->planned_channel,
            'subject' => $draft->subject === null ? null : (string) $draft->subject,
            'message_body' => $draft->message_body === null ? null : (string) $draft->message_body,
            'visibility' => (string) $draft->visibility,
            'audience_saved_segment_id' => (int) $snapshot['id'],
            'audience_snapshot_signature' => (string) $snapshot['signature'],
            'audience_snapshot_signature_version' => (int) $snapshot['signature_version'],
            'audience_snapshot_share_token' => $snapshot['share_token'] === null ? null : (string) $snapshot['share_token'],
            'approved_snapshot_at' => $this->normalizedApprovedSnapshotTimestamp($approvedAt),
        ];

        if ($signatureVersion === self::APPROVED_SNAPSHOT_SIGNATURE_VERSION) {
            $payload['approved_recipient_set_signature'] = $recipientSetSignature;
            $payload['approved_recipient_set_signature_version'] = $recipientSetSignatureVersion;
            $payload['approved_recipient_count'] = $recipientCount;
        }

        return json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    protected function approvedSnapshotIntegrityPresentation(CrmCampaignDraft $draft): array
    {
        $state = $this->approvedSnapshotIntegrityState($draft);

        return match ($state['status']) {
            'verified' => [
                'status' => 'verified',
                'label' => 'Verified approved planning snapshot',
                'message' => 'The approved campaign content and sealed audience snapshot still match the recorded approval.',
            ],
            'invalid' => [
                'status' => 'invalid',
                'label' => 'Stale or invalid approval',
                'message' => $state['message'] ?: 'The recorded approval no longer matches the current campaign planning snapshot.',
            ],
            default => [
                'status' => 'not_applicable',
                'label' => 'No active approval',
                'message' => 'No active approved planning snapshot is required for the current lifecycle state.',
            ],
        };
    }

    protected function approvedSnapshotIntegrityState(CrmCampaignDraft $draft): array
    {
        $signature = $this->nullableString($draft->approved_snapshot_signature);
        $version = $draft->approved_snapshot_signature_version;
        $approvedAt = $draft->getRawOriginal('approved_snapshot_at');

        if ($draft->status !== 'approved') {
            if ($signature === null && ($version === null || $version === '') && ($approvedAt === null || $approvedAt === '')) {
                return ['status' => 'not_applicable', 'message' => null];
            }

            return ['status' => 'invalid', 'message' => 'Approval metadata exists outside the approved lifecycle state. Return the campaign to draft planning and resubmit it.'];
        }

        if ($signature === null || !is_numeric($version) || !in_array((int) $version, [self::LEGACY_APPROVED_SNAPSHOT_SIGNATURE_VERSION, self::APPROVED_SNAPSHOT_SIGNATURE_VERSION], true)) {
            return ['status' => 'invalid', 'message' => 'Approved planning snapshot signature metadata is missing or unsupported.'];
        }

        $signature = strtolower($signature);
        if (preg_match('/^[a-f0-9]{64}$/D', $signature) !== 1) {
            return ['status' => 'invalid', 'message' => 'Approved planning snapshot signature is malformed.'];
        }

        try {
            $expected = $this->approvedSnapshotSignature($draft, $approvedAt, null, null, null, (int) $version);
        } catch (ValidationException $exception) {
            return ['status' => 'invalid', 'message' => $this->firstValidationMessage($exception)];
        }

        if (!hash_equals($expected, $signature)) {
            return ['status' => 'invalid', 'message' => 'Approved planning snapshot failed integrity verification. Content, channel, visibility, or audience metadata may have changed after approval.'];
        }

        return ['status' => 'verified', 'message' => null];
    }

    protected function normalizedApprovedSnapshotTimestamp($value): string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        $value = trim((string) $value);
        if ($value === '') {
            $this->throwValidation('campaign_draft', 'Approved planning snapshot timestamp is missing.');
        }

        $timestamp = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value);
        $errors = DateTimeImmutable::getLastErrors();
        if ($timestamp === false
            || (is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
            || $timestamp->format('Y-m-d H:i:s') !== $value) {
            $this->throwValidation('campaign_draft', 'Approved planning snapshot timestamp is invalid.');
        }

        return $timestamp->format('Y-m-d H:i:s');
    }

    protected function recordApprovalHistory(
        string $action,
        CrmCampaignDraft $draft,
        User $actor,
        ?string $fromStatus,
        string $toStatus,
        ?string $note = null,
        array $metadata = []
    ): void {
        $this->ensureApprovalHistoryStorage();

        CrmCampaignDraftApprovalHistory::create([
            'product_website_id' => $draft->product_website_id,
            'crm_campaign_draft_id' => $draft->id,
            'action' => $action,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'actor_id' => $actor->id,
            'review_note' => $note,
            'audience_snapshot_signature' => $draft->audience_snapshot_signature,
            'audience_snapshot_signature_version' => $draft->audience_snapshot_signature_version,
            'approved_snapshot_signature' => $draft->approved_snapshot_signature,
            'approved_snapshot_signature_version' => $draft->approved_snapshot_signature_version,
            'approved_snapshot_at' => $draft->approved_snapshot_at,
            'approved_recipient_set_signature' => $draft->approved_recipient_set_signature,
            'approved_recipient_set_signature_version' => $draft->approved_recipient_set_signature_version,
            'approved_recipient_count' => $draft->approved_recipient_count,
            'metadata_json' => $metadata,
            'created_at' => now(),
        ]);
    }

    protected function approvalHistoryPayload(CrmCampaignDraftApprovalHistory $history): array
    {
        return [
            'id' => (int) $history->id,
            'action' => $history->action,
            'from_status' => $history->from_status,
            'to_status' => $history->to_status,
            'actor_id' => $history->actor_id ? (int) $history->actor_id : null,
            'actor_name' => $history->relationLoaded('actor') ? optional($history->actor)->name : null,
            'review_note' => $history->review_note,
            'approved_snapshot_at' => optional($history->approved_snapshot_at)->format('Y-m-d h:i a'),
            'approved_recipient_count' => is_numeric($history->approved_recipient_count) ? (int) $history->approved_recipient_count : null,
            'created_at' => optional($history->created_at)->format('Y-m-d h:i a'),
        ];
    }

    protected function ensureApprovalHistoryStorage(): void
    {
        $requiredColumns = [
            'id', 'product_website_id', 'crm_campaign_draft_id', 'action', 'from_status', 'to_status',
            'actor_id', 'review_note', 'audience_snapshot_signature', 'audience_snapshot_signature_version',
            'approved_snapshot_signature', 'approved_snapshot_signature_version', 'approved_snapshot_at',
            'approved_recipient_set_signature', 'approved_recipient_set_signature_version', 'approved_recipient_count',
            'metadata_json', 'created_at',
        ];

        if (!Schema::hasTable('crm_campaign_draft_approval_history')) {
            $this->throwValidation('campaign_draft', 'CRM campaign approval-history storage is not available. Run application migrations first.');
        }

        foreach ($requiredColumns as $column) {
            if (!Schema::hasColumn('crm_campaign_draft_approval_history', $column)) {
                $this->throwValidation('campaign_draft', 'CRM campaign approval-history storage is incomplete. Run application migrations first.');
            }
        }
    }

    protected function positiveInteger($value, string $field): int
    {
        if (is_int($value) && $value > 0) {
            return $value;
        }
        if (is_string($value) && preg_match('/^[1-9][0-9]*$/D', $value) === 1) {
            $integer = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($integer !== false) {
                return $integer;
            }
        }

        $this->throwValidation($field, 'The ' . str_replace('_', ' ', $field) . ' must be a positive integer.');
    }

    protected function nullableString($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    protected function nullableBoundedString($value, string $field, int $maxLength): ?string
    {
        $value = $this->nullableString($value);
        if ($value !== null && $this->stringLength($value) > $maxLength) {
            $this->throwValidation($field, 'The ' . str_replace('_', ' ', $field) . ' may not be greater than ' . $maxLength . ' characters.');
        }

        return $value;
    }

    protected function stringLength(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
    }

    protected function firstValidationMessage(ValidationException $exception): string
    {
        foreach ($exception->errors() as $messages) {
            foreach ((array) $messages as $message) {
                return (string) $message;
            }
        }

        return 'The CRM campaign draft data is invalid.';
    }

    protected function throwInvalidStoredAudience(?string $reason = null): void
    {
        $message = 'This campaign draft has an invalid stored audience snapshot and cannot be previewed.';
        if ($reason) {
            $message .= ' ' . $reason;
        }

        $this->throwValidation('campaign_draft', $message);
    }

    protected function throwValidation(string $field, string $message): void
    {
        throw ValidationException::withMessages([$field => [$message]]);
    }

    protected function hasUserStorage(): bool
    {
        return Schema::hasTable('users')
            && Schema::hasColumn('users', 'id')
            && Schema::hasColumn('users', 'name');
    }

    protected function logFailure(string $operation, ?int $draftId, int $actorId, \Throwable $exception): void
    {
        Log::error('CRM campaign draft write failed', [
            'operation' => $operation,
            'campaign_draft_id' => $draftId,
            'actor_id' => $actorId,
            'error' => $exception->getMessage(),
        ]);
    }
}
