<?php

namespace App\Services\Crm;

use App\Models\Crm\CrmSavedCustomerSegment;
use App\Models\User;
use App\Services\RoleSidebarPermissionService;
use DateTimeImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CrmSavedCustomerSegmentService
{
    protected array $permissionCache = [];

    public const FILTER_KEYS = [
        'customer_id',
        'assigned_user_id',
        'tag_ids',
        'lifecycle_stage',
        'credit_status',
        'risk_bucket',
        'duplicate_candidate',
        'last_contact_from',
        'last_contact_to',
        'follow_up_from',
        'follow_up_to',
        'last_order_from',
        'last_order_to',
    ];

    public const STRING_FILTER_KEYS = [
        'lifecycle_stage',
        'credit_status',
        'risk_bucket',
        'duplicate_candidate',
        'last_contact_from',
        'last_contact_to',
        'follow_up_from',
        'follow_up_to',
        'last_order_from',
        'last_order_to',
    ];

    public const VISIBILITIES = ['private', 'shared'];

    public const STATUSES = ['active', 'archived'];

    protected const RISK_BUCKETS = ['overdue', 'due', 'follow_up_due', 'duplicate'];

    protected const DUPLICATE_CANDIDATE_VALUES = ['yes', 'no'];

    protected const DATE_FILTER_KEYS = [
        'last_contact_from',
        'last_contact_to',
        'follow_up_from',
        'follow_up_to',
        'last_order_from',
        'last_order_to',
    ];

    public function __construct(
        protected CrmActivityService $activityService,
        protected RoleSidebarPermissionService $permissionService
    ) {
    }

    public function worklistQuery(User $actor, array $filters = []): Builder
    {
        $this->ensureStorage();
        $this->authorize($actor, 'crm.saved-customer-segments.list', 'read');

        $query = $this->visibleQuery($actor);
        $this->loadUserRelations($query);

        if (!empty($filters['status']) && in_array($filters['status'], self::STATUSES, true)) {
            $query->where('status', $filters['status']);
        } else {
            $query->where('status', 'active');
        }

        if (!empty($filters['visibility']) && in_array($filters['visibility'], self::VISIBILITIES, true)) {
            $query->where('visibility', $filters['visibility']);
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
                ->orWhere('visibility', 'like', '%' . $search . '%')
                ->orWhere('status', 'like', '%' . $search . '%');

            if ($this->hasUserStorage()) {
                $nested->orWhereHas('creator', fn (Builder $user) => $user->where('name', 'like', '%' . $search . '%'));
            }
        });
    }

    public function options(User $actor, ?string $search = null, int $limit = 50): Collection
    {
        $this->ensureStorage();
        $this->authorize($actor, 'crm.saved-customer-segments.list', 'read');
        $search = trim((string) $search);
        $limit = max(1, min($limit, 100));

        $segments = $this->visibleQuery($actor)
            ->where('status', 'active')
            ->when($search !== '', fn (Builder $query) => $query->where('name', 'like', '%' . $search . '%'))
            ->orderBy('name')
            ->orderBy('id')
            ->select(['id', 'name', 'visibility', 'created_by', 'filters_json'])
            ->cursor();

        $options = collect();
        foreach ($segments as $segment) {
            if (!$this->hasValidStoredFilters($segment)) {
                continue;
            }

            $options->push([
                'id' => $segment->id,
                'text' => $segment->name . ($segment->visibility === 'private' ? ' (private)' : ' (shared)'),
            ]);
            if ($options->count() >= $limit) {
                break;
            }
        }

        return $options;
    }

    public function findVisible(int $segmentId, User $actor): CrmSavedCustomerSegment
    {
        $this->ensureStorage();
        $this->authorize($actor, 'crm.saved-customer-segments.list', 'read');
        $query = $this->visibleQuery($actor);
        $this->loadUserRelations($query);

        return $query->findOrFail($segmentId);
    }

    public function applicablePayload(int $segmentId, User $actor): array
    {
        $segment = $this->findVisible($segmentId, $actor);
        $this->ensureApplicable($segment);
        $filters = $this->storedFilters($segment);
        $payload = $this->payload($segment, $actor, true);
        $payload['filters'] = $filters;

        return $payload;
    }

    public function applicableSnapshot(int $segmentId, User $actor, bool $lockForUpdate = false): array
    {
        $this->ensureStorage();
        $this->authorize($actor, 'crm.saved-customer-segments.list', 'read');

        $query = $this->visibleQuery($actor);
        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        $segment = $query->findOrFail($segmentId);
        $this->ensureApplicable($segment);
        $filters = $this->storedFilters($segment);

        return [
            'id' => (int) $segment->id,
            'name' => (string) $segment->name,
            'visibility' => (string) $segment->visibility,
            'filters' => $filters,
            'filters_summary' => $this->filterSummary($filters),
        ];
    }

    public function filterSummaryFor(array $filters): array
    {
        return $this->filterSummary($filters);
    }

    public function create(array $data, User $actor): CrmSavedCustomerSegment
    {
        $this->ensureStorage();
        $this->authorize($actor, 'crm.saved-customer-segments.list', 'create');
        $filters = $this->normalizedFilters($data['filters'] ?? []);

        try {
            $segment = DB::transaction(function () use ($data, $filters, $actor) {
                $segment = CrmSavedCustomerSegment::create([
                    'product_website_id' => $actor->product_website_id ?? null,
                    'name' => trim((string) $data['name']),
                    'description' => $this->nullableString($data['description'] ?? null),
                    'filters_json' => $filters,
                    'visibility' => $data['visibility'],
                    'status' => 'active',
                    'created_by' => $actor->id,
                    'updated_by' => $actor->id,
                ]);

                $this->recordActivity('crm_saved_customer_segment_created', $segment, $actor->id, 'CRM saved customer segment created');

                return $segment;
            });
        } catch (\Throwable $exception) {
            $this->logFailure('create', null, $actor->id, $exception);
            throw $exception;
        }

        return $this->freshWithRelations($segment);
    }

    public function update(int $segmentId, array $data, User $actor): CrmSavedCustomerSegment
    {
        $this->ensureStorage();
        $this->authorize($actor, 'crm.saved-customer-segments.list', 'update');
        $filters = $this->normalizedFilters($data['filters'] ?? []);

        try {
            $segment = DB::transaction(function () use ($segmentId, $data, $filters, $actor) {
                $segment = $this->visibleQuery($actor)->lockForUpdate()->findOrFail($segmentId);
                $this->authorizeOwner($segment, $actor, 'update');
                $this->ensureActive($segment);

                $segment->forceFill([
                    'name' => trim((string) $data['name']),
                    'description' => $this->nullableString($data['description'] ?? null),
                    'filters_json' => $filters,
                    'visibility' => $data['visibility'],
                    'updated_by' => $actor->id,
                ])->save();

                $segment = $segment->refresh();
                $this->recordActivity('crm_saved_customer_segment_updated', $segment, $actor->id, 'CRM saved customer segment updated');

                return $segment;
            });
        } catch (\Throwable $exception) {
            $this->logFailure('update', $segmentId, $actor->id, $exception);
            throw $exception;
        }

        return $this->freshWithRelations($segment);
    }

    public function archive(int $segmentId, User $actor): CrmSavedCustomerSegment
    {
        $this->ensureStorage();
        $this->authorize($actor, 'crm.saved-customer-segments.list', 'delete');

        try {
            $segment = DB::transaction(function () use ($segmentId, $actor) {
                $segment = $this->visibleQuery($actor)->lockForUpdate()->findOrFail($segmentId);
                $this->authorizeOwner($segment, $actor, 'archive');
                $this->ensureActive($segment);

                $segment->forceFill([
                    'status' => 'archived',
                    'archived_by' => $actor->id,
                    'archived_at' => now(),
                    'updated_by' => $actor->id,
                ])->save();

                $segment = $segment->refresh();
                $this->recordActivity('crm_saved_customer_segment_archived', $segment, $actor->id, 'CRM saved customer segment archived');

                return $segment;
            });
        } catch (\Throwable $exception) {
            $this->logFailure('archive', $segmentId, $actor->id, $exception);
            throw $exception;
        }

        return $this->freshWithRelations($segment);
    }

    public function payload(CrmSavedCustomerSegment $segment, User $actor, bool $details = false): array
    {
        [$filters, $filtersValid, $filtersValidationMessage] = $this->storedFilterState($segment);
        $isOwner = (int) $segment->created_by === (int) $actor->id;
        $canEdit = $segment->status === 'active'
            && $isOwner
            && $this->can($actor, 'update');
        $canArchive = $segment->status === 'active'
            && $isOwner
            && $this->can($actor, 'delete');
        $filtersSummary = $filtersValid
            ? $this->filterSummary($filters)
            : [[
                'key' => 'invalid_definition',
                'label' => 'Saved filters',
                'value' => 'Invalid stored definition',
            ]];

        $payload = [
            'id' => $segment->id,
            'name' => $segment->name,
            'description' => $segment->description,
            'visibility' => $segment->visibility,
            'status' => $segment->status,
            'filter_count' => count($filters),
            'filters_summary' => $filtersSummary,
            'filters_valid' => $filtersValid,
            'filters_validation_message' => $filtersValidationMessage,
            'creator' => $segment->relationLoaded('creator') ? optional($segment->creator)->name : null,
            'updater' => $segment->relationLoaded('updater') ? optional($segment->updater)->name : null,
            'archiver' => $segment->relationLoaded('archiver') ? optional($segment->archiver)->name : null,
            'archived_at' => optional($segment->archived_at)->format('Y-m-d h:i a'),
            'created_at' => optional($segment->created_at)->format('Y-m-d h:i a'),
            'updated_at' => optional($segment->updated_at)->format('Y-m-d h:i a'),
            'can_edit' => $canEdit,
            'can_archive' => $canArchive,
            'details_url' => Route::has('crm.saved-customer-segments.show') ? route('crm.saved-customer-segments.show', ['segment' => $segment->id]) : null,
            'apply_url' => $segment->status === 'active' && $filtersValid && Route::has('crm.customer-segments.index')
                ? route('crm.customer-segments.index', ['saved_segment_id' => $segment->id])
                : null,
            'edit_url' => $canEdit && $filtersValid && Route::has('crm.customer-segments.index')
                ? route('crm.customer-segments.index', ['saved_segment_id' => $segment->id, 'edit_saved_segment_id' => $segment->id])
                : null,
        ];

        if ($details) {
            $payload['filters'] = $filters;
        }

        return $payload;
    }

    public function normalizedFilters(array $filters, bool $requireActive = true): array
    {
        $unknownKeys = array_values(array_diff(array_keys($filters), self::FILTER_KEYS));
        if (!empty($unknownKeys)) {
            $this->throwValidation('filters', 'Unsupported saved segment filter key: ' . implode(', ', $unknownKeys) . '.');
        }

        $normalized = [];
        foreach (self::FILTER_KEYS as $key) {
            if (!array_key_exists($key, $filters)) {
                continue;
            }

            $value = $filters[$key];
            if ($key === 'tag_ids') {
                $tagIds = $this->normalizeTagIds($value);
                if (!empty($tagIds)) {
                    $normalized[$key] = $tagIds;
                }
                continue;
            }

            if (in_array($key, ['customer_id', 'assigned_user_id'], true)) {
                if ($value === null || $value === '') {
                    continue;
                }
                $normalized[$key] = $this->positiveInteger($value, 'filters.' . $key);
                continue;
            }

            if ($value === null || $value === '') {
                continue;
            }
            if (!is_string($value)) {
                $this->throwValidation('filters.' . $key, 'The ' . str_replace('_', ' ', $key) . ' filter must be a string.');
            }

            $value = trim($value);
            if ($value === '') {
                continue;
            }
            $this->validateStringFilter($key, $value);
            $normalized[$key] = $value;
        }

        $this->validateDateRange($normalized, 'last_contact_from', 'last_contact_to', 'last contact');
        $this->validateDateRange($normalized, 'follow_up_from', 'follow_up_to', 'follow-up');
        $this->validateDateRange($normalized, 'last_order_from', 'last_order_to', 'last order');

        if ($requireActive && empty($normalized)) {
            $this->throwValidation('filters', 'Select at least one customer portfolio filter before saving a segment.');
        }

        return $normalized;
    }

    public function storedFilters(CrmSavedCustomerSegment $segment, bool $requireActive = true): array
    {
        $rawFilters = $segment->getRawOriginal('filters_json');
        if (is_string($rawFilters)) {
            $filters = json_decode($rawFilters, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($filters)) {
                $this->throwInvalidStoredDefinition();
            }
        } elseif (is_array($rawFilters)) {
            $filters = $rawFilters;
        } else {
            $this->throwInvalidStoredDefinition();
        }

        try {
            return $this->normalizedFilters($filters, $requireActive);
        } catch (ValidationException $exception) {
            $this->throwInvalidStoredDefinition($this->firstValidationMessage($exception));
        }
    }

    protected function visibleQuery(User $actor): Builder
    {
        return CrmSavedCustomerSegment::query()->where(function (Builder $query) use ($actor) {
            $query->where('visibility', 'shared')
                ->orWhere(function (Builder $private) use ($actor) {
                    $private->where('visibility', 'private')->where('created_by', $actor->id);
                });
        });
    }

    protected function loadUserRelations(Builder $query): void
    {
        if ($this->hasUserStorage()) {
            $query->with(['creator:id,name', 'updater:id,name', 'archiver:id,name']);
        }
    }

    protected function freshWithRelations(CrmSavedCustomerSegment $segment): CrmSavedCustomerSegment
    {
        $segment = $segment->fresh();
        if ($this->hasUserStorage()) {
            $segment->load(['creator:id,name', 'updater:id,name', 'archiver:id,name']);
        }

        return $segment;
    }

    protected function filterSummary(array $filters): array
    {
        $labels = [
            'customer_id' => 'Customer',
            'assigned_user_id' => 'Assigned user',
            'tag_ids' => 'CRM tags',
            'lifecycle_stage' => 'Lifecycle stage',
            'credit_status' => 'Credit status',
            'risk_bucket' => 'Risk bucket',
            'duplicate_candidate' => 'Duplicate candidate',
            'last_contact_from' => 'Last contact from',
            'last_contact_to' => 'Last contact to',
            'follow_up_from' => 'Follow-up from',
            'follow_up_to' => 'Follow-up to',
            'last_order_from' => 'Last order from',
            'last_order_to' => 'Last order to',
        ];

        $summary = [];
        foreach ($filters as $key => $value) {
            $summary[] = [
                'key' => $key,
                'label' => $labels[$key] ?? $key,
                'value' => is_array($value) ? implode(', ', $value) : (string) $value,
            ];
        }

        return $summary;
    }

    protected function normalizeTagIds($value): array
    {
        if ($value === null || $value === '') {
            return [];
        }
        if (!is_array($value)) {
            $this->throwValidation('filters.tag_ids', 'The CRM tag filter must be an array.');
        }
        if (count($value) > 50) {
            $this->throwValidation('filters.tag_ids', 'The CRM tag filter may not contain more than 50 tags.');
        }

        $tagIds = [];
        foreach ($value as $index => $tagId) {
            $tagIds[] = $this->positiveInteger($tagId, 'filters.tag_ids.' . $index);
        }
        if (count(array_unique($tagIds)) !== count($tagIds)) {
            $this->throwValidation('filters.tag_ids', 'The CRM tag filter may not contain duplicate tag IDs.');
        }

        return array_values($tagIds);
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

        $this->throwValidation($field, 'The ' . str_replace(['filters.', '_'], ['', ' '], $field) . ' filter must be a positive integer.');
    }

    protected function validateStringFilter(string $key, string $value): void
    {
        if (in_array($key, ['lifecycle_stage', 'credit_status'], true) && $this->stringLength($value) > 40) {
            $this->throwValidation('filters.' . $key, 'The ' . str_replace('_', ' ', $key) . ' filter may not be greater than 40 characters.');
        }
        if ($key === 'risk_bucket' && !in_array($value, self::RISK_BUCKETS, true)) {
            $this->throwValidation('filters.risk_bucket', 'The selected risk bucket is invalid.');
        }
        if ($key === 'duplicate_candidate' && !in_array($value, self::DUPLICATE_CANDIDATE_VALUES, true)) {
            $this->throwValidation('filters.duplicate_candidate', 'The selected duplicate candidate value is invalid.');
        }
        if (in_array($key, self::DATE_FILTER_KEYS, true) && !$this->isExactDate($value)) {
            $this->throwValidation('filters.' . $key, 'The ' . str_replace('_', ' ', $key) . ' filter must use the Y-m-d date format.');
        }
    }

    protected function validateDateRange(array $filters, string $fromKey, string $toKey, string $label): void
    {
        if (!empty($filters[$fromKey]) && !empty($filters[$toKey]) && $filters[$toKey] < $filters[$fromKey]) {
            $this->throwValidation('filters.' . $toKey, 'The ' . $label . ' end date must be after or equal to the start date.');
        }
    }

    protected function isExactDate(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = DateTimeImmutable::getLastErrors();

        return $date !== false
            && (!is_array($errors) || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
            && $date->format('Y-m-d') === $value;
    }

    protected function storedFilterState(CrmSavedCustomerSegment $segment): array
    {
        try {
            return [$this->storedFilters($segment), true, null];
        } catch (ValidationException $exception) {
            return [[], false, $this->firstValidationMessage($exception)];
        }
    }

    protected function hasValidStoredFilters(CrmSavedCustomerSegment $segment): bool
    {
        [, $valid] = $this->storedFilterState($segment);

        return $valid;
    }

    protected function ensureStorage(): void
    {
        $requiredColumns = [
            'id', 'product_website_id', 'name', 'description', 'filters_json', 'visibility', 'status',
            'created_by', 'updated_by', 'archived_by', 'archived_at', 'created_at', 'updated_at',
        ];
        if (!Schema::hasTable('crm_saved_customer_segments')) {
            $this->throwValidation('saved_segment', 'CRM saved segment storage is not available. Run application migrations first.');
        }

        foreach ($requiredColumns as $column) {
            if (!Schema::hasColumn('crm_saved_customer_segments', $column)) {
                $this->throwValidation('saved_segment', 'CRM saved segment storage is incomplete. Run application migrations first.');
            }
        }
    }

    protected function ensureActive(CrmSavedCustomerSegment $segment): void
    {
        if ($segment->status !== 'active') {
            $this->throwValidation('saved_segment', 'Archived CRM saved segments cannot be modified.');
        }
    }

    protected function ensureApplicable(CrmSavedCustomerSegment $segment): void
    {
        if ($segment->status !== 'active') {
            $this->throwValidation('saved_segment', 'Archived CRM saved segments cannot be applied.');
        }
    }

    protected function authorize(User $actor, string $permissionKey, string $action): void
    {
        if (!$this->permissionService->userCan($actor, $permissionKey, $action)) {
            throw new AuthorizationException('You do not have permission to perform this CRM saved segment action.');
        }
    }

    protected function can(User $actor, string $action): bool
    {
        $cacheKey = $actor->id . '|' . $action;
        if (!array_key_exists($cacheKey, $this->permissionCache)) {
            $this->permissionCache[$cacheKey] = $this->permissionService->userCan($actor, 'crm.saved-customer-segments.list', $action);
        }

        return $this->permissionCache[$cacheKey];
    }

    protected function authorizeOwner(CrmSavedCustomerSegment $segment, User $actor, string $action): void
    {
        if ((int) $segment->created_by !== (int) $actor->id) {
            throw new AuthorizationException('Only the creator can ' . $action . ' this CRM saved segment.');
        }
    }

    protected function recordActivity(string $type, CrmSavedCustomerSegment $segment, int $actorId, string $subject): void
    {
        [$filters, $filtersValid] = $this->storedFilterState($segment);
        $this->activityService->recordOrFail([
            'product_website_id' => $segment->product_website_id,
            'activity_type' => $type,
            'subject' => $subject,
            'description' => $subject . ': ' . $segment->name,
            'source_module' => 'crm_saved_customer_segments',
            'source_id' => $segment->id,
            'performed_by' => $actorId,
            'metadata' => [
                'visibility' => $segment->visibility,
                'status' => $segment->status,
                'filter_definition_valid' => $filtersValid,
                'filter_keys' => array_keys($filters),
                'filter_count' => count($filters),
            ],
        ]);
    }

    protected function nullableString($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
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

        return 'The CRM saved segment filter definition is invalid.';
    }

    protected function throwInvalidStoredDefinition(?string $reason = null): void
    {
        $message = 'This saved segment has an invalid stored filter definition and cannot be applied.';
        if ($reason) {
            $message .= ' ' . $reason;
        }

        $this->throwValidation('saved_segment', $message);
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

    protected function logFailure(string $operation, ?int $segmentId, int $actorId, \Throwable $exception): void
    {
        Log::error('CRM saved customer segment write failed', [
            'operation' => $operation,
            'saved_segment_id' => $segmentId,
            'actor_id' => $actorId,
            'error' => $exception->getMessage(),
        ]);
    }
}
