<?php

namespace App\Services\Crm;

use App\Http\Controllers\Customer\Models\Customer;
use App\Models\Crm\CrmNote;
use App\Models\User;
use App\Services\RoleSidebarPermissionService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CrmCustomerNoteService
{
    protected CrmActivityService $activityService;
    protected RoleSidebarPermissionService $permissionService;

    public function __construct(
        CrmActivityService $activityService,
        RoleSidebarPermissionService $permissionService
    ) {
        $this->activityService = $activityService;
        $this->permissionService = $permissionService;
    }

    public function paginate(int $customerId, User $actor, int $perPage = 10): array
    {
        Customer::query()->findOrFail($customerId);

        if (!Schema::hasTable('crm_notes')) {
            return $this->emptyPagination('CRM notes storage is not available.');
        }

        $canUpdateAny = $this->permissionService->userCan($actor, 'crm.customers.profile', 'update');
        $canDeleteAny = $this->permissionService->userCan($actor, 'crm.customers.profile', 'delete');

        $query = CrmNote::query()
            ->where('customer_id', $customerId)
            ->with(['creator:id,name', 'updater:id,name'])
            ->when(!$canUpdateAny, function ($query) use ($actor) {
                $query->where(function ($nested) use ($actor) {
                    $nested->where('is_private', false)
                        ->orWhere('created_by', $actor->id);
                });
            })
            ->orderByDesc('id');

        /** @var LengthAwarePaginator $paginator */
        $paginator = $query->paginate(max(1, min($perPage, 50)));
        $rows = collect($paginator->items())
            ->map(fn(CrmNote $note) => $this->payload($note, $actor, $canUpdateAny, $canDeleteAny))
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
            'message' => empty($rows) ? 'No CRM notes have been added for this customer.' : null,
        ];
    }

    public function create(int $customerId, array $data, User $actor): CrmNote
    {
        try {
            $note = DB::transaction(function () use ($customerId, $data, $actor) {
                Customer::query()->lockForUpdate()->findOrFail($customerId);

                return CrmNote::query()->create([
                    'product_website_id' => $actor->product_website_id ?? null,
                    'customer_id' => $customerId,
                    'note' => trim((string) $data['note']),
                    'is_private' => (bool) ($data['is_private'] ?? false),
                    'created_by' => $actor->id,
                    'updated_by' => $actor->id,
                ]);
            });
        } catch (\Throwable $exception) {
            $this->logFailure('create', $customerId, null, $actor->id, $exception);
            throw $exception;
        }

        $this->activityService->record([
            'customer_id' => $customerId,
            'activity_type' => 'customer_note_created',
            'subject' => 'CRM customer note created',
            'description' => 'A CRM note was added to the customer profile.',
            'source_module' => 'crm_notes',
            'source_id' => $note->id,
            'performed_by' => $actor->id,
            'metadata' => ['is_private' => $note->is_private],
        ]);

        return $note->load(['creator:id,name', 'updater:id,name']);
    }

    public function update(int $customerId, int $noteId, array $data, User $actor): CrmNote
    {
        try {
            $note = DB::transaction(function () use ($customerId, $noteId, $data, $actor) {
                Customer::query()->lockForUpdate()->findOrFail($customerId);
                $note = CrmNote::query()
                    ->where('customer_id', $customerId)
                    ->lockForUpdate()
                    ->findOrFail($noteId);

                $this->authorizeMutation($note, $actor, 'update');

                $note->forceFill([
                    'note' => trim((string) $data['note']),
                    'is_private' => (bool) ($data['is_private'] ?? false),
                    'updated_by' => $actor->id,
                ])->save();

                return $note->refresh();
            });
        } catch (\Throwable $exception) {
            $this->logFailure('update', $customerId, $noteId, $actor->id, $exception);
            throw $exception;
        }

        $this->activityService->record([
            'customer_id' => $customerId,
            'activity_type' => 'customer_note_updated',
            'subject' => 'CRM customer note updated',
            'description' => 'A CRM note was updated on the customer profile.',
            'source_module' => 'crm_notes',
            'source_id' => $note->id,
            'performed_by' => $actor->id,
            'metadata' => ['is_private' => $note->is_private],
        ]);

        return $note->load(['creator:id,name', 'updater:id,name']);
    }

    public function archive(int $customerId, int $noteId, User $actor): void
    {
        if (!Schema::hasTable('crm_notes') || !Schema::hasColumn('crm_notes', 'deleted_at')) {
            throw ValidationException::withMessages([
                'note' => ['CRM note archiving is not available for this schema.'],
            ]);
        }

        try {
            $note = DB::transaction(function () use ($customerId, $noteId, $actor) {
                Customer::query()->lockForUpdate()->findOrFail($customerId);
                $note = CrmNote::query()
                    ->where('customer_id', $customerId)
                    ->lockForUpdate()
                    ->findOrFail($noteId);

                $this->authorizeMutation($note, $actor, 'delete');
                $note->forceFill(['updated_by' => $actor->id])->save();
                $note->delete();

                return $note;
            });
        } catch (\Throwable $exception) {
            $this->logFailure('archive', $customerId, $noteId, $actor->id, $exception);
            throw $exception;
        }

        $this->activityService->record([
            'customer_id' => $customerId,
            'activity_type' => 'customer_note_archived',
            'subject' => 'CRM customer note archived',
            'description' => 'A CRM note was softly archived from the customer profile.',
            'source_module' => 'crm_notes',
            'source_id' => $noteId,
            'performed_by' => $actor->id,
        ]);
    }

    public function payload(CrmNote $note, User $actor, ?bool $canUpdateAny = null, ?bool $canDeleteAny = null): array
    {
        $isAuthor = (int) $note->created_by === (int) $actor->id;
        $canUpdateAny ??= $this->permissionService->userCan($actor, 'crm.customers.profile', 'update');
        $canDeleteAny ??= $this->permissionService->userCan($actor, 'crm.customers.profile', 'delete');

        return [
            'id' => $note->id,
            'note' => $note->note,
            'is_private' => (bool) $note->is_private,
            'creator' => optional($note->creator)->name,
            'updater' => optional($note->updater)->name,
            'created_at' => optional($note->created_at)->format('Y-m-d h:i a'),
            'updated_at' => optional($note->updated_at)->format('Y-m-d h:i a'),
            'can_edit' => $isAuthor || $canUpdateAny,
            'can_archive' => $isAuthor || $canDeleteAny,
        ];
    }

    protected function authorizeMutation(CrmNote $note, User $actor, string $action): void
    {
        if ((int) $note->created_by === (int) $actor->id) {
            return;
        }

        if ($this->permissionService->userCan($actor, 'crm.customers.profile', $action)) {
            return;
        }

        throw new AuthorizationException('You do not have permission to modify this CRM note.');
    }

    protected function emptyPagination(?string $message = null): array
    {
        return [
            'data' => [],
            'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 0, 'total' => 0],
            'message' => $message,
        ];
    }

    protected function logFailure(string $operation, int $customerId, ?int $noteId, int $actorId, \Throwable $exception): void
    {
        Log::error('CRM customer note write failed', [
            'operation' => $operation,
            'customer_id' => $customerId,
            'note_id' => $noteId,
            'actor_id' => $actorId,
            'error' => $exception->getMessage(),
        ]);
    }
}
