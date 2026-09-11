<?php

namespace App\Services\Crm;

use App\Http\Controllers\Customer\Models\Customer;
use App\Models\Crm\CrmTask;
use App\Models\User;
use App\Services\RoleSidebarPermissionService;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CrmTaskService
{
    public const STATUSES = ['pending', 'in_progress', 'completed'];
    public const PRIORITIES = ['low', 'normal', 'high', 'urgent'];

    public function __construct(
        protected CrmActivityService $activityService,
        protected RoleSidebarPermissionService $permissionService
    ) {
    }

    public function worklistQuery(array $filters = []): Builder
    {
        $this->ensureStorage();

        $query = CrmTask::query()->with([
            'customer:id,name,slug',
            'assignedUser:id,name',
            'creator:id,name',
            'updater:id,name',
        ]);

        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', (int) $filters['customer_id']);
        }
        if (!empty($filters['assigned_user_id'])) {
            $query->where('assigned_user_id', (int) $filters['assigned_user_id']);
        }
        if (!empty($filters['priority']) && in_array($filters['priority'], self::PRIORITIES, true)) {
            $query->where('priority', $filters['priority']);
        }
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'overdue') {
                $query->where('status', '<>', 'completed')->whereNotNull('due_at')->where('due_at', '<', now());
            } elseif (in_array($filters['status'], self::STATUSES, true)) {
                $query->where('status', $filters['status']);
            }
        }
        if (!empty($filters['due_from'])) {
            $query->whereDate('due_at', '>=', $filters['due_from']);
        }
        if (!empty($filters['due_to'])) {
            $query->whereDate('due_at', '<=', $filters['due_to']);
        }

        return $query->orderByRaw("CASE WHEN status <> 'completed' AND due_at IS NOT NULL AND due_at < ? THEN 0 WHEN status = 'completed' THEN 2 ELSE 1 END", [now()])
            ->orderBy('due_at')
            ->orderByDesc('id');
    }

    public function applyGlobalSearch(Builder $query, ?string $search): void
    {
        $search = trim((string) $search);
        if ($search === '') {
            return;
        }

        $query->where(function (Builder $nested) use ($search) {
            $nested->where('title', 'like', '%' . $search . '%')
                ->orWhere('description', 'like', '%' . $search . '%')
                ->orWhere('priority', 'like', '%' . $search . '%')
                ->orWhere('status', 'like', '%' . $search . '%')
                ->orWhereHas('customer', fn (Builder $customer) => $customer->where('name', 'like', '%' . $search . '%'))
                ->orWhereHas('assignedUser', fn (Builder $user) => $user->where('name', 'like', '%' . $search . '%'));
        });
    }

    public function calendarFeed(array $filters, User $actor): array
    {
        $this->ensureStorage();

        $timezone = config('app.timezone', 'Asia/Dhaka');
        $start = Carbon::createFromFormat('Y-m-d', $filters['start'], $timezone)->startOfDay();
        $end = Carbon::createFromFormat('Y-m-d', $filters['end'], $timezone)->endOfDay();

        $query = CrmTask::query()
            ->with(['customer:id,name,slug', 'assignedUser:id,name'])
            ->whereNotNull('due_at')
            ->whereBetween('due_at', [$start, $end]);

        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', (int) $filters['customer_id']);
        }
        if (!empty($filters['assigned_user_id'])) {
            $query->where('assigned_user_id', (int) $filters['assigned_user_id']);
        }
        if (!empty($filters['priority']) && in_array($filters['priority'], self::PRIORITIES, true)) {
            $query->where('priority', $filters['priority']);
        }
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'overdue') {
                $query->where('status', '<>', 'completed')->where('due_at', '<', now($timezone));
            } elseif (in_array($filters['status'], self::STATUSES, true)) {
                $query->where('status', $filters['status']);
            }
        }

        $tasks = $query->orderBy('due_at')->orderByDesc('id')->limit(501)->get();
        $hasMore = $tasks->count() > 500;
        if ($hasMore) {
            $tasks->pop();
        }

        $canProfile = $this->permissionService->userCan($actor, 'crm.customers.profile', 'read');
        $canViewWorklist = $this->permissionService->userCan($actor, 'crm.tasks.list', 'read');

        return [
            'events' => $tasks->map(fn (CrmTask $task) => $this->calendarPayload($task, $canProfile, $canViewWorklist))->values(),
            'meta' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
                'total' => $tasks->count(),
                'has_more' => $hasMore,
                'limit' => 500,
                'generated_at' => now($timezone)->format('M d, Y h:i A'),
            ],
        ];
    }

    protected function calendarPayload(CrmTask $task, bool $canProfile, bool $canViewWorklist): array
    {
        $isCompleted = $task->status === 'completed';
        $isOverdue = !$isCompleted && $task->due_at && $task->due_at->isPast();
        $profileUrl = $task->customer_id && $canProfile && Route::has('crm.customers.profile')
            ? route('crm.customers.profile', ['customer' => $task->customer_id])
            : null;
        $worklistUrl = $canViewWorklist && Route::has('crm.tasks.index')
            ? route('crm.tasks.index', array_filter(['customer_id' => $task->customer_id]))
            : null;

        return [
            'id' => $task->id,
            'title' => $task->title,
            'date' => optional($task->due_at)->format('Y-m-d'),
            'time' => optional($task->due_at)->format('h:i a'),
            'due_at' => optional($task->due_at)->format('M d, Y h:i a'),
            'priority' => $task->priority,
            'status' => $task->status,
            'is_overdue' => (bool) $isOverdue,
            'customer' => $task->customer ? [
                'id' => $task->customer->id,
                'name' => $task->customer->name ?: ('Customer #' . $task->customer->id),
                'profile_url' => $profileUrl,
            ] : null,
            'assigned_user' => $task->assignedUser ? [
                'id' => $task->assignedUser->id,
                'name' => $task->assignedUser->name ?: ('User #' . $task->assignedUser->id),
            ] : null,
            'worklist_url' => $worklistUrl,
        ];
    }

    public function customerTasks(int $customerId, User $actor, int $perPage = 10): array
    {
        $this->ensureStorage();
        Customer::query()->findOrFail($customerId);
        $perPage = max(1, min($perPage, 50));

        $paginator = CrmTask::query()
            ->where('customer_id', $customerId)
            ->with(['customer:id,name,slug', 'assignedUser:id,name', 'creator:id,name', 'updater:id,name'])
            ->orderByRaw("CASE WHEN status <> 'completed' AND due_at IS NOT NULL AND due_at < ? THEN 0 WHEN status = 'completed' THEN 2 ELSE 1 END", [now()])
            ->orderBy('due_at')
            ->orderByDesc('id')
            ->paginate($perPage);

        $rows = collect($paginator->items())
            ->map(fn (CrmTask $task) => $this->payload($task, $actor))
            ->values()
            ->all();

        return [
            'data' => $rows,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'message' => empty($rows) ? 'No CRM tasks were found for this customer.' : null,
        ];
    }

    public function customerOptions(?string $search = null, int $limit = 30): Collection
    {
        $search = trim((string) $search);
        return Customer::query()
            ->when($search !== '', fn (Builder $query) => $query->where('name', 'like', '%' . $search . '%'))
            ->orderBy('name')
            ->limit(max(1, min($limit, 100)))
            ->get(['id', 'name'])
            ->map(fn (Customer $customer) => ['id' => $customer->id, 'text' => $customer->name ?: ('Customer #' . $customer->id)]);
    }

    public function userOptions(?string $search = null, int $limit = 30): Collection
    {
        $search = trim((string) $search);
        return User::query()
            ->where('status', 1)
            ->when($search !== '', fn (Builder $query) => $query->where('name', 'like', '%' . $search . '%'))
            ->orderBy('name')
            ->limit(max(1, min($limit, 100)))
            ->get(['id', 'name'])
            ->map(fn (User $user) => ['id' => $user->id, 'text' => $user->name ?: ('User #' . $user->id)]);
    }

    public function customerOption(?int $customerId): ?array
    {
        if (!$customerId) {
            return null;
        }
        $customer = Customer::query()->find($customerId);
        return $customer ? ['id' => $customer->id, 'text' => $customer->name ?: ('Customer #' . $customer->id)] : null;
    }

    public function create(array $data, User $actor): CrmTask
    {
        $this->ensureStorage();
        $this->authorize($actor, 'crm.tasks.create', 'create');

        try {
            $task = DB::transaction(function () use ($data, $actor) {
                $task = CrmTask::query()->create($this->writePayload($data) + [
                    'created_by' => $actor->id,
                    'updated_by' => $actor->id,
                ])->refresh();
                $this->recordActivity('crm_task_created', $task, $actor->id, 'CRM task created');

                return $task;
            });
        } catch (\Throwable $exception) {
            $this->logFailure('create', null, $actor->id, $exception);
            throw $exception;
        }

        return $task->load(['customer:id,name,slug', 'assignedUser:id,name', 'creator:id,name', 'updater:id,name']);
    }

    public function update(int $taskId, array $data, User $actor): CrmTask
    {
        $this->ensureStorage();
        $this->authorize($actor, 'crm.tasks.create', 'update');

        try {
            $task = DB::transaction(function () use ($taskId, $data, $actor) {
                $task = CrmTask::query()->lockForUpdate()->findOrFail($taskId);
                if ($task->status === 'completed') {
                    throw ValidationException::withMessages(['status' => ['Completed CRM tasks cannot be edited.']]);
                }
                $task->forceFill($this->writePayload($data) + ['updated_by' => $actor->id])->save();
                $task = $task->refresh();
                $this->recordActivity('crm_task_updated', $task, $actor->id, 'CRM task updated');

                return $task;
            });
        } catch (\Throwable $exception) {
            $this->logFailure('update', $taskId, $actor->id, $exception);
            throw $exception;
        }

        return $task->load(['customer:id,name,slug', 'assignedUser:id,name', 'creator:id,name', 'updater:id,name']);
    }

    public function complete(int $taskId, array $data, User $actor): array
    {
        $this->ensureStorage();
        $this->authorize($actor, 'crm.tasks.complete', 'update');

        try {
            [$task, $changed] = DB::transaction(function () use ($taskId, $data, $actor) {
                $task = CrmTask::query()->lockForUpdate()->findOrFail($taskId);
                if ($task->status === 'completed') {
                    return [$task, false];
                }
                $task->forceFill([
                    'status' => 'completed',
                    'completed_at' => $task->completed_at ?: now(),
                    'completion_note' => $data['completion_note'] ?? $task->completion_note,
                    'updated_by' => $actor->id,
                ])->save();
                $task = $task->refresh();
                $this->recordActivity('crm_task_completed', $task, $actor->id, 'CRM task completed');

                return [$task, true];
            });
        } catch (\Throwable $exception) {
            $this->logFailure('complete', $taskId, $actor->id, $exception);
            throw $exception;
        }


        return [
            'task' => $task->load(['customer:id,name,slug', 'assignedUser:id,name', 'creator:id,name', 'updater:id,name']),
            'changed' => $changed,
        ];
    }

    public function archive(int $taskId, User $actor): void
    {
        $this->ensureStorage();
        $this->authorize($actor, 'crm.tasks.list', 'delete');
        if (!Schema::hasColumn('crm_tasks', 'deleted_at')) {
            throw ValidationException::withMessages(['task' => ['CRM task archiving is not available for this schema.']]);
        }

        try {
            $task = DB::transaction(function () use ($taskId, $actor) {
                $task = CrmTask::query()->lockForUpdate()->findOrFail($taskId);
                $task->forceFill(['updated_by' => $actor->id])->save();
                $task->delete();
                $this->recordActivity('crm_task_archived', $task, $actor->id, 'CRM task archived');

                return $task;
            });
        } catch (\Throwable $exception) {
            $this->logFailure('archive', $taskId, $actor->id, $exception);
            throw $exception;
        }

    }

    public function payload(CrmTask $task, User $actor): array
    {
        $isCompleted = $task->status === 'completed';
        $isOverdue = !$isCompleted && $task->due_at && $task->due_at->isPast();
        $canProfile = $task->customer_id && $this->permissionService->userCan($actor, 'crm.customers.profile', 'read');

        return [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'customer_id' => $task->customer_id,
            'customer' => optional($task->customer)->name,
            'customer_profile_url' => $canProfile && Route::has('crm.customers.profile') ? route('crm.customers.profile', ['customer' => $task->customer_id]) : null,
            'assigned_user_id' => $task->assigned_user_id,
            'assigned_user' => optional($task->assignedUser)->name,
            'priority' => $task->priority,
            'status' => $task->status,
            'due_at' => optional($task->due_at)->format('Y-m-d h:i a'),
            'due_at_input' => optional($task->due_at)->format('Y-m-d\TH:i'),
            'completed_at' => optional($task->completed_at)->format('Y-m-d h:i a'),
            'completion_note' => $task->completion_note,
            'creator' => optional($task->creator)->name,
            'updater' => optional($task->updater)->name,
            'created_at' => optional($task->created_at)->format('Y-m-d h:i a'),
            'updated_at' => optional($task->updated_at)->format('Y-m-d h:i a'),
            'is_overdue' => (bool) $isOverdue,
            'can_edit' => !$isCompleted && $this->permissionService->userCan($actor, 'crm.tasks.create', 'update'),
            'can_complete' => !$isCompleted && $this->permissionService->userCan($actor, 'crm.tasks.complete', 'update'),
            'can_archive' => Schema::hasColumn('crm_tasks', 'deleted_at') && $this->permissionService->userCan($actor, 'crm.tasks.list', 'delete'),
        ];
    }

    protected function writePayload(array $data): array
    {
        return [
            'customer_id' => $data['customer_id'] ?? null,
            'assigned_user_id' => $data['assigned_user_id'] ?? null,
            'title' => trim((string) $data['title']),
            'description' => isset($data['description']) && $data['description'] !== '' ? trim((string) $data['description']) : null,
            'priority' => $data['priority'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'due_at' => $data['due_at'] ?? null,
        ];
    }

    protected function authorize(User $actor, string $permissionKey, string $action): void
    {
        if (!$this->permissionService->userCan($actor, $permissionKey, $action)) {
            throw new AuthorizationException('You do not have permission to perform this CRM task action.');
        }
    }

    protected function ensureStorage(): void
    {
        if (!Schema::hasTable('crm_tasks')) {
            throw ValidationException::withMessages(['task' => ['CRM task storage is not available.']]);
        }
    }

    protected function recordActivity(string $type, CrmTask $task, int $actorId, string $subject): void
    {
        $this->activityService->recordOrFail([
            'customer_id' => $task->customer_id,
            'activity_type' => $type,
            'subject' => $subject,
            'description' => $subject . ': ' . $task->title,
            'source_module' => 'crm_tasks',
            'source_id' => $task->id,
            'performed_by' => $actorId,
            'metadata' => ['status' => $task->status, 'priority' => $task->priority, 'assigned_user_id' => $task->assigned_user_id],
        ]);
    }

    protected function logFailure(string $operation, ?int $taskId, int $actorId, \Throwable $exception): void
    {
        Log::error('CRM task write failed', [
            'operation' => $operation,
            'task_id' => $taskId,
            'actor_id' => $actorId,
            'error' => $exception->getMessage(),
        ]);
    }
}
