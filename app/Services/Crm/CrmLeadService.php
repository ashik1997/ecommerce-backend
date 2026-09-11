<?php

namespace App\Services\Crm;

use App\Http\Controllers\Customer\Models\Customer;
use App\Models\Crm\CrmLead;
use App\Models\User;
use App\Services\RoleSidebarPermissionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CrmLeadService
{
    public const STATUSES = ['new', 'contacted', 'qualified', 'converted', 'lost'];
    public const PRIORITIES = ['low', 'normal', 'high', 'urgent'];
    public const SOURCES = ['walk_in', 'phone', 'website', 'facebook', 'referral', 'campaign', 'other'];
    public const PIPELINE_CARD_LIMIT_PER_STATUS = 100;

    public function __construct(
        protected CrmActivityService $activityService,
        protected RoleSidebarPermissionService $permissionService
    ) {
    }

    public function worklistQuery(array $filters = []): Builder
    {
        $this->ensureStorage();

        $query = CrmLead::query()->with([
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
        if (!empty($filters['status']) && in_array($filters['status'], self::STATUSES, true)) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['priority']) && in_array($filters['priority'], self::PRIORITIES, true)) {
            $query->where('priority', $filters['priority']);
        }
        if (!empty($filters['source'])) {
            $query->where('source', $filters['source']);
        }
        if (!empty($filters['follow_up_from'])) {
            $query->whereDate('next_follow_up_at', '>=', $filters['follow_up_from']);
        }
        if (!empty($filters['follow_up_to'])) {
            $query->whereDate('next_follow_up_at', '<=', $filters['follow_up_to']);
        }

        return $query->orderByRaw("CASE WHEN status IN ('converted','lost') THEN 2 WHEN next_follow_up_at IS NOT NULL AND next_follow_up_at < ? THEN 0 ELSE 1 END", [now()])
            ->orderBy('next_follow_up_at')
            ->orderByDesc('id');
    }

    public function pipelineBoard(array $filters, User $actor): array
    {
        $this->ensureStorage();

        $canChangeStatus = $this->permissionService->userCan($actor, 'crm.leads.list', 'update');
        $canViewCustomerProfiles = $this->permissionService->userCan($actor, 'crm.customers.profile', 'read');
        $columns = [];
        foreach (self::STATUSES as $status) {
            $query = CrmLead::query()
                ->with(['customer:id,name,slug', 'assignedUser:id,name'])
                ->where('status', $status);

            $this->applyPipelineFilters($query, $filters);
            $this->applyGlobalSearch($query, $filters['search'] ?? null);

            $total = (clone $query)->count();
            $cards = $query
                ->orderByRaw('CASE WHEN next_follow_up_at IS NOT NULL AND next_follow_up_at < ? THEN 0 ELSE 1 END', [now()])
                ->orderBy('next_follow_up_at')
                ->orderByDesc('id')
                ->limit(self::PIPELINE_CARD_LIMIT_PER_STATUS)
                ->get()
                ->map(fn (CrmLead $lead) => $this->pipelineCard($lead, $canChangeStatus, $canViewCustomerProfiles))
                ->values()
                ->all();

            $columns[] = [
                'status' => $status,
                'label' => ucwords(str_replace('_', ' ', $status)),
                'total' => (int) $total,
                'visible' => count($cards),
                'has_more' => $total > self::PIPELINE_CARD_LIMIT_PER_STATUS,
                'cards' => $cards,
            ];
        }

        return [
            'columns' => $columns,
            'meta' => [
                'generated_at' => now()->format('Y-m-d H:i:s'),
                'card_limit_per_status' => self::PIPELINE_CARD_LIMIT_PER_STATUS,
            ],
        ];
    }

    public function applyGlobalSearch(Builder $query, ?string $search): void
    {
        $search = trim((string) $search);
        if ($search === '') {
            return;
        }

        $query->where(function (Builder $nested) use ($search) {
            $nested->where('name', 'like', '%' . $search . '%')
                ->orWhere('company_name', 'like', '%' . $search . '%')
                ->orWhere('phone', 'like', '%' . $search . '%')
                ->orWhere('email', 'like', '%' . $search . '%')
                ->orWhere('source', 'like', '%' . $search . '%')
                ->orWhere('status', 'like', '%' . $search . '%')
                ->orWhere('priority', 'like', '%' . $search . '%')
                ->orWhere('requirement', 'like', '%' . $search . '%')
                ->orWhereHas('customer', fn (Builder $customer) => $customer->where('name', 'like', '%' . $search . '%'))
                ->orWhereHas('assignedUser', fn (Builder $user) => $user->where('name', 'like', '%' . $search . '%'));
        });
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

    public function customerPrefill(?int $customerId): ?array
    {
        if (!$customerId) {
            return null;
        }

        $customer = Customer::query()->find($customerId, ['id', 'name']);
        if (!$customer) {
            return null;
        }

        return [
            'id' => $customer->id,
            'text' => $customer->name ?: ('Customer #' . $customer->id),
        ];
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

    public function filterOptions(): array
    {
        return [
            'statuses' => self::STATUSES,
            'priorities' => self::PRIORITIES,
            'sources' => self::SOURCES,
        ];
    }


    public function details(int $leadId): CrmLead
    {
        $this->ensureStorage();

        return CrmLead::query()
            ->with(['customer:id,name,slug', 'assignedUser:id,name', 'creator:id,name', 'updater:id,name'])
            ->findOrFail($leadId);
    }

    public function create(array $data, User $actor): CrmLead
    {
        $this->ensureStorage();
        $this->authorize($actor, 'crm.leads.list', 'create');

        try {
            $lead = DB::transaction(function () use ($data, $actor) {
                $lead = CrmLead::query()->create($this->writePayload($data) + [
                    'status' => 'new',
                    'created_by' => $actor->id,
                    'updated_by' => $actor->id,
                ])->refresh();
                $this->recordActivity('crm_lead_created', $lead, $actor->id, 'CRM lead created');

                return $lead;
            });
        } catch (\Throwable $exception) {
            $this->logFailure('create', null, $actor->id, $exception);
            throw $exception;
        }

        return $lead->load(['customer:id,name,slug', 'assignedUser:id,name', 'creator:id,name', 'updater:id,name']);
    }

    public function update(int $leadId, array $data, User $actor): CrmLead
    {
        $this->ensureStorage();
        $this->authorize($actor, 'crm.leads.list', 'update');

        try {
            $lead = DB::transaction(function () use ($leadId, $data, $actor) {
                $lead = CrmLead::query()->lockForUpdate()->findOrFail($leadId);
                if (in_array($lead->status, ['converted', 'lost'], true)) {
                    throw ValidationException::withMessages(['status' => ['Converted or lost CRM leads cannot be edited.']]);
                }
                $lead->forceFill($this->writePayload($data) + ['updated_by' => $actor->id])->save();
                $lead = $lead->refresh();
                $this->recordActivity('crm_lead_updated', $lead, $actor->id, 'CRM lead updated');

                return $lead;
            });
        } catch (\Throwable $exception) {
            $this->logFailure('update', $leadId, $actor->id, $exception);
            throw $exception;
        }

        return $lead->load(['customer:id,name,slug', 'assignedUser:id,name', 'creator:id,name', 'updater:id,name']);
    }

    public function updateStatus(int $leadId, array $data, User $actor): CrmLead
    {
        $this->ensureStorage();
        $this->authorize($actor, 'crm.leads.list', 'update');

        try {
            $lead = DB::transaction(function () use ($leadId, $data, $actor) {
                $lead = CrmLead::query()->lockForUpdate()->findOrFail($leadId);
                $status = $data['status'];
                $payload = ['status' => $status, 'updated_by' => $actor->id];

                if ($status === 'qualified' && !$lead->qualified_at) {
                    $payload['qualified_at'] = now();
                }
                if ($status === 'converted') {
                    $payload['converted_at'] = now();
                    $payload['customer_id'] = $data['customer_id'] ?? $lead->customer_id;
                    if (empty($payload['customer_id'])) {
                        throw ValidationException::withMessages(['customer_id' => ['Select an existing customer before marking a lead as converted.']]);
                    }
                }
                if ($status === 'lost') {
                    $payload['lost_reason'] = $data['lost_reason'] ?? null;
                }

                $lead->forceFill($payload)->save();
                $lead = $lead->refresh();
                $this->recordActivity('crm_lead_status_changed', $lead, $actor->id, 'CRM lead status changed');

                return $lead;
            });
        } catch (\Throwable $exception) {
            $this->logFailure('status', $leadId, $actor->id, $exception);
            throw $exception;
        }

        return $lead->load(['customer:id,name,slug', 'assignedUser:id,name', 'creator:id,name', 'updater:id,name']);
    }

    public function archive(int $leadId, User $actor): CrmLead
    {
        $this->ensureStorage();
        $this->authorize($actor, 'crm.leads.list', 'delete');

        try {
            $lead = DB::transaction(function () use ($leadId, $actor) {
                $lead = CrmLead::query()->lockForUpdate()->findOrFail($leadId);
                $lead->forceFill(['updated_by' => $actor->id])->save();
                $lead->delete();
                $this->recordActivity('crm_lead_archived', $lead, $actor->id, 'CRM lead archived');

                return $lead;
            });
        } catch (\Throwable $exception) {
            $this->logFailure('archive', $leadId, $actor->id, $exception);
            throw $exception;
        }

        return $lead;
    }

    public function payload(CrmLead $lead, User $actor, bool $details = false): array
    {
        $customer = $lead->customer;
        $payload = [
            'id' => $lead->id,
            'name' => $lead->name,
            'company_name' => $lead->company_name,
            'phone' => $lead->phone,
            'email' => $lead->email,
            'source' => $lead->source,
            'status' => $lead->status,
            'priority' => $lead->priority,
            'score' => $lead->score,
            'estimated_value' => $lead->estimated_value,
            'next_follow_up_at' => optional($lead->next_follow_up_at)->format('Y-m-d H:i'),
            'qualified_at' => optional($lead->qualified_at)->format('Y-m-d H:i'),
            'converted_at' => optional($lead->converted_at)->format('Y-m-d H:i'),
            'customer' => $customer ? ['id' => $customer->id, 'name' => $customer->name, 'profile_url' => $this->customerProfileUrl($customer, $actor)] : null,
            'assigned_user' => $lead->assignedUser ? ['id' => $lead->assignedUser->id, 'name' => $lead->assignedUser->name] : null,
            'created_by' => optional($lead->creator)->name,
            'updated_by' => optional($lead->updater)->name,
            'created_at' => optional($lead->created_at)->format('Y-m-d H:i'),
            'updated_at' => optional($lead->updated_at)->format('Y-m-d H:i'),
            'can_update' => $this->permissionService->userCan($actor, 'crm.leads.list', 'update') && !in_array($lead->status, ['converted', 'lost'], true),
            'can_change_status' => $this->permissionService->userCan($actor, 'crm.leads.list', 'update'),
            'can_archive' => $this->permissionService->userCan($actor, 'crm.leads.list', 'delete'),
        ];

        if ($details) {
            $payload['requirement'] = $lead->requirement;
            $payload['lost_reason'] = $lead->lost_reason;
        }

        return $payload;
    }

    protected function applyPipelineFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', (int) $filters['customer_id']);
        }
        if (!empty($filters['assigned_user_id'])) {
            $query->where('assigned_user_id', (int) $filters['assigned_user_id']);
        }
        if (!empty($filters['priority']) && in_array($filters['priority'], self::PRIORITIES, true)) {
            $query->where('priority', $filters['priority']);
        }
        if (!empty($filters['source']) && in_array($filters['source'], self::SOURCES, true)) {
            $query->where('source', $filters['source']);
        }
        if (!empty($filters['follow_up_from'])) {
            $query->whereDate('next_follow_up_at', '>=', $filters['follow_up_from']);
        }
        if (!empty($filters['follow_up_to'])) {
            $query->whereDate('next_follow_up_at', '<=', $filters['follow_up_to']);
        }
    }

    protected function pipelineCard(CrmLead $lead, bool $canChangeStatus, bool $canViewCustomerProfiles): array
    {
        $customer = $lead->customer;

        return [
            'id' => $lead->id,
            'name' => $lead->name,
            'company_name' => $lead->company_name,
            'source' => $lead->source,
            'status' => $lead->status,
            'priority' => $lead->priority,
            'estimated_value' => $lead->estimated_value,
            'next_follow_up_at' => optional($lead->next_follow_up_at)->format('Y-m-d H:i'),
            'customer' => $customer ? [
                'id' => $customer->id,
                'name' => $customer->name,
                'profile_url' => $canViewCustomerProfiles ? route('crm.customers.profile', ['customer' => $customer->id]) : null,
            ] : null,
            'assigned_user' => $lead->assignedUser ? [
                'id' => $lead->assignedUser->id,
                'name' => $lead->assignedUser->name,
            ] : null,
            'can_change_status' => $canChangeStatus,
        ];
    }

    protected function writePayload(array $data): array
    {
        return [
            'customer_id' => $data['customer_id'] ?? null,
            'assigned_user_id' => $data['assigned_user_id'] ?? null,
            'name' => $data['name'],
            'company_name' => $data['company_name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'source' => $data['source'] ?? null,
            'priority' => $data['priority'] ?? 'normal',
            'score' => (int) ($data['score'] ?? 0),
            'estimated_value' => $data['estimated_value'] ?? null,
            'requirement' => $data['requirement'] ?? null,
            'next_follow_up_at' => $data['next_follow_up_at'] ?? null,
        ];
    }

    protected function ensureStorage(): void
    {
        if (!Schema::hasTable('crm_leads')) {
            throw ValidationException::withMessages(['crm_leads' => ['CRM leads table is missing. Run application migrations before using lead management.']]);
        }
    }

    protected function authorize(User $actor, string $permissionKey, string $action): void
    {
        if (!$this->permissionService->userCan($actor, $permissionKey, $action)) {
            abort(403, 'You are not authorized to perform this CRM lead action.');
        }
    }

    protected function customerProfileUrl(Customer $customer, User $actor): ?string
    {
        if (!$this->permissionService->userCan($actor, 'crm.customers.profile', 'read')) {
            return null;
        }

        return route('crm.customers.profile', ['customer' => $customer->id]);
    }

    protected function recordActivity(string $type, CrmLead $lead, ?int $actorId, string $subject): void
    {
        $this->activityService->recordOrFail([
            'customer_id' => $lead->customer_id,
            'activity_type' => $type,
            'subject' => $subject,
            'source_module' => 'crm_leads',
            'source_id' => $lead->id,
            'performed_by' => $actorId,
            'metadata' => ['lead_name' => $lead->name, 'status' => $lead->status],
        ]);
    }

    protected function logFailure(string $action, ?int $leadId, ?int $actorId, \Throwable $exception): void
    {
        Log::error('CRM lead action failed', [
            'action' => $action,
            'lead_id' => $leadId,
            'actor_id' => $actorId,
            'error' => $exception->getMessage(),
        ]);
    }
}
