<?php

namespace App\Services\Crm;

use App\Http\Controllers\Customer\Models\Customer;
use App\Models\User;
use App\Services\RoleSidebarPermissionService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CrmCustomerHealthWorklistService
{
    public function __construct(protected RoleSidebarPermissionService $permissionService)
    {
    }

    public function worklistQuery(array $filters = []): Builder
    {
        $this->ensureStorage();

        $query = Customer::query()->with(['assignedUser:id,name']);
        $columns = $this->selectColumns();
        if (!empty($columns)) {
            $query->select($columns);
        }

        if (!empty($filters['customer_id'])) {
            $query->where('id', (int) $filters['customer_id']);
        }
        if (!empty($filters['assigned_user_id']) && Schema::hasColumn('customers', 'assigned_user_id')) {
            $query->where('assigned_user_id', (int) $filters['assigned_user_id']);
        }
        if (!empty($filters['lifecycle_stage']) && Schema::hasColumn('customers', 'lifecycle_stage')) {
            $query->where('lifecycle_stage', $filters['lifecycle_stage']);
        }
        if (!empty($filters['credit_status']) && Schema::hasColumn('customers', 'credit_status')) {
            $query->where('credit_status', $filters['credit_status']);
        }
        if (!empty($filters['follow_up_from']) && Schema::hasColumn('customers', 'next_follow_up_at')) {
            $query->whereDate('next_follow_up_at', '>=', $filters['follow_up_from']);
        }
        if (!empty($filters['follow_up_to']) && Schema::hasColumn('customers', 'next_follow_up_at')) {
            $query->whereDate('next_follow_up_at', '<=', $filters['follow_up_to']);
        }
        if (!empty($filters['last_contact_from']) && Schema::hasColumn('customers', 'last_contact_at')) {
            $query->whereDate('last_contact_at', '>=', $filters['last_contact_from']);
        }
        if (!empty($filters['last_contact_to']) && Schema::hasColumn('customers', 'last_contact_at')) {
            $query->whereDate('last_contact_at', '<=', $filters['last_contact_to']);
        }
        if (!empty($filters['risk_bucket'])) {
            $this->applyRiskBucket($query, $filters['risk_bucket']);
        }

        if (Schema::hasColumn('customers', 'overdue_amount')) {
            $query->orderByDesc('overdue_amount');
        }
        if (Schema::hasColumn('customers', 'current_due')) {
            $query->orderByDesc('current_due');
        }

        return $query->orderByDesc('id');
    }

    public function applyGlobalSearch(Builder $query, ?string $search): void
    {
        $search = trim((string) $search);
        if ($search === '') {
            return;
        }

        $query->where(function (Builder $nested) use ($search) {
            foreach (['name', 'phone', 'email', 'customer_code', 'lifecycle_stage', 'credit_status'] as $column) {
                if (Schema::hasColumn('customers', $column)) {
                    $nested->orWhere($column, 'like', '%' . $search . '%');
                }
            }
            if (Schema::hasColumn('customers', 'assigned_user_id')) {
                $nested->orWhereHas('assignedUser', fn (Builder $user) => $user->where('name', 'like', '%' . $search . '%'));
            }
        });
    }

    public function payload(Customer $customer, ?User $actor): array
    {
        $canProfile = $this->permissionService->userCan($actor, 'crm.customers.profile', 'read') && Route::has('crm.customers.profile');

        return [
            'id' => $customer->id,
            'customer_code' => $customer->customer_code ?? null,
            'name' => $customer->name,
            'phone' => $customer->phone ?? null,
            'email' => $customer->email ?? null,
            'lifecycle_stage' => $customer->lifecycle_stage ?? null,
            'assigned_user' => optional($customer->assignedUser)->name,
            'last_contact_at' => $this->formatDateTime($customer->last_contact_at ?? null),
            'next_follow_up_at' => $this->formatDateTime($customer->next_follow_up_at ?? null),
            'last_order_at' => $this->formatDateTime($customer->last_order_at ?? null),
            'total_order_value' => $this->money($customer->total_order_value ?? 0),
            'total_paid' => $this->money($customer->total_paid ?? 0),
            'current_due' => $this->money($customer->current_due ?? 0),
            'overdue_amount' => $this->money($customer->overdue_amount ?? 0),
            'credit_status' => $customer->credit_status ?? null,
            'risk_label' => $this->riskLabel($customer),
            'is_duplicate_candidate' => (bool) ($customer->is_duplicate_candidate ?? false),
            'profile_url' => $canProfile ? route('crm.customers.profile', ['customer' => $customer->id]) : null,
        ];
    }

    public function filterOptions(): array
    {
        if (!Schema::hasTable('customers')) {
            return ['lifecycle_stages' => [], 'credit_statuses' => []];
        }

        return [
            'lifecycle_stages' => $this->distinctValues('lifecycle_stage'),
            'credit_statuses' => $this->distinctValues('credit_status'),
        ];
    }

    public function customerOptions(?string $search = null, int $limit = 30): Collection
    {
        $search = trim((string) $search);

        return Customer::query()
            ->select(['id', 'name'])
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $nested) use ($search) {
                    $nested->where('name', 'like', '%' . $search . '%');
                    foreach (['phone', 'email', 'customer_code'] as $column) {
                        if (Schema::hasColumn('customers', $column)) {
                            $nested->orWhere($column, 'like', '%' . $search . '%');
                        }
                    }
                });
            })
            ->orderBy('name')
            ->limit(max(1, min($limit, 100)))
            ->get()
            ->map(fn (Customer $customer) => ['id' => $customer->id, 'text' => $customer->name ?: ('Customer #' . $customer->id)]);
    }

    public function userOptions(?string $search = null, int $limit = 30): Collection
    {
        $search = trim((string) $search);

        return User::query()
            ->select(['id', 'name'])
            ->when($search !== '', fn (Builder $query) => $query->where('name', 'like', '%' . $search . '%'))
            ->orderBy('name')
            ->limit(max(1, min($limit, 100)))
            ->get()
            ->map(fn (User $user) => ['id' => $user->id, 'text' => $user->name ?: ('User #' . $user->id)]);
    }

    public function customerOption(?int $customerId): ?array
    {
        if (!$customerId || !Schema::hasTable('customers')) {
            return null;
        }

        $customer = Customer::query()->find($customerId);

        return $customer ? ['id' => $customer->id, 'text' => $customer->name ?: ('Customer #' . $customer->id)] : null;
    }

    protected function applyRiskBucket(Builder $query, string $bucket): void
    {
        if ($bucket === 'overdue' && Schema::hasColumn('customers', 'overdue_amount')) {
            $query->where('overdue_amount', '>', 0);
        } elseif ($bucket === 'due' && Schema::hasColumn('customers', 'current_due')) {
            $query->where('current_due', '>', 0);
        } elseif ($bucket === 'follow_up_due' && Schema::hasColumn('customers', 'next_follow_up_at')) {
            $query->whereNotNull('next_follow_up_at')->where('next_follow_up_at', '<=', Carbon::now('Asia/Dhaka'));
        } elseif ($bucket === 'duplicate' && Schema::hasColumn('customers', 'is_duplicate_candidate')) {
            $query->where('is_duplicate_candidate', true);
        }
    }

    protected function riskLabel(Customer $customer): string
    {
        if ((float) ($customer->overdue_amount ?? 0) > 0) {
            return 'Overdue';
        }
        if ((float) ($customer->current_due ?? 0) > 0) {
            return 'Due';
        }
        if (!empty($customer->next_follow_up_at) && Carbon::parse($customer->next_follow_up_at)->lte(Carbon::now('Asia/Dhaka'))) {
            return 'Follow-up due';
        }
        if ((bool) ($customer->is_duplicate_candidate ?? false)) {
            return 'Duplicate candidate';
        }

        return 'Normal';
    }

    protected function distinctValues(string $column): array
    {
        if (!Schema::hasColumn('customers', $column)) {
            return [];
        }

        return Customer::query()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->limit(100)
            ->pluck($column)
            ->filter()
            ->values()
            ->all();
    }

    protected function selectColumns(): array
    {
        return collect([
            'id', 'name', 'phone', 'email', 'customer_code', 'lifecycle_stage', 'assigned_user_id',
            'last_contact_at', 'next_follow_up_at', 'last_order_at', 'total_order_value', 'total_paid',
            'current_due', 'overdue_amount', 'credit_status', 'is_duplicate_candidate',
        ])->filter(fn (string $column) => Schema::hasColumn('customers', $column))->values()->all();
    }

    protected function ensureStorage(): void
    {
        if (!Schema::hasTable('customers')) {
            throw ValidationException::withMessages(['customers' => 'The customers table is not available.']);
        }
    }

    protected function formatDateTime($value): ?string
    {
        return $value ? Carbon::parse($value)->format('M d, Y h:i A') : null;
    }

    protected function money($value): string
    {
        return number_format((float) $value, 2);
    }
}
