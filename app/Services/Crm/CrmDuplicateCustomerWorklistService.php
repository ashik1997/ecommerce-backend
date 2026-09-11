<?php

namespace App\Services\Crm;

use App\Http\Controllers\Customer\Models\Customer;
use App\Models\User;
use App\Services\RoleSidebarPermissionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CrmDuplicateCustomerWorklistService
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

        $this->appendMatchCountColumns($query);

        $candidateOnly = !array_key_exists('candidate_only', $filters) || (bool) $filters['candidate_only'];
        $matchType = !empty($filters['match_type']) ? $filters['match_type'] : 'any';

        if ($candidateOnly && Schema::hasColumn('customers', 'is_duplicate_candidate')) {
            $query->where('is_duplicate_candidate', true);

            if ($matchType !== 'any') {
                $this->applyDuplicateSignal($query, $matchType);
            }
        } else {
            $this->applyDuplicateSignal($query, $matchType);
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
        if (Schema::hasColumn('customers', 'is_duplicate_candidate')) {
            $query->orderByDesc('is_duplicate_candidate');
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
            foreach (['name', 'phone', 'email', 'customer_code', 'lifecycle_stage'] as $column) {
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
            'is_duplicate_candidate' => (bool) ($customer->is_duplicate_candidate ?? false),
            'phone_match_count' => (int) ($customer->phone_match_count ?? 0),
            'email_match_count' => (int) ($customer->email_match_count ?? 0),
            'profile_url' => $canProfile ? route('crm.customers.profile', ['customer' => $customer->id]) : null,
        ];
    }

    public function filterOptions(): array
    {
        if (!Schema::hasTable('customers')) {
            return ['lifecycle_stages' => []];
        }

        return ['lifecycle_stages' => $this->distinctValues('lifecycle_stage')];
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

    protected function applyDuplicateSignal(Builder $query, string $type): void
    {
        $hasPhone = Schema::hasColumn('customers', 'phone');
        $hasEmail = Schema::hasColumn('customers', 'email');

        if ($type === 'phone') {
            if ($hasPhone) {
                $this->appendDuplicateExistsCondition($query, 'whereExists', 'phone', 'duplicate_customers_phone');
            } else {
                $query->whereRaw('1 = 0');
            }

            return;
        }

        if ($type === 'email') {
            if ($hasEmail) {
                $this->appendDuplicateExistsCondition($query, 'whereExists', 'email', 'duplicate_customers_email');
            } else {
                $query->whereRaw('1 = 0');
            }

            return;
        }

        $query->where(function (Builder $nested) use ($hasPhone, $hasEmail) {
            $hasSignal = false;

            if ($hasPhone) {
                $this->appendDuplicateExistsCondition($nested, 'whereExists', 'phone', 'duplicate_customers_phone');
                $hasSignal = true;
            }

            if ($hasEmail) {
                $this->appendDuplicateExistsCondition($nested, $hasSignal ? 'orWhereExists' : 'whereExists', 'email', 'duplicate_customers_email');
                $hasSignal = true;
            }

            if (!$hasSignal) {
                $nested->whereRaw('1 = 0');
            }
        });
    }

    protected function appendDuplicateExistsCondition(Builder $query, string $method, string $column, string $alias): void
    {
        $query->{$method}(function ($subQuery) use ($column, $alias) {
            $subQuery->select(DB::raw(1))
                ->from('customers as ' . $alias)
                ->whereColumn($alias . '.' . $column, 'customers.' . $column)
                ->whereColumn($alias . '.id', '!=', 'customers.id')
                ->whereNotNull($alias . '.' . $column)
                ->where($alias . '.' . $column, '!=', '');
        });
    }

    protected function appendMatchCountColumns(Builder $query): void
    {
        foreach (['phone', 'email'] as $column) {
            if (!Schema::hasColumn('customers', $column)) {
                continue;
            }

            $alias = 'duplicate_count_' . $column;

            $query->selectSub(function ($subQuery) use ($column, $alias) {
                $subQuery->selectRaw('COUNT(*)')
                    ->from('customers as ' . $alias)
                    ->whereColumn($alias . '.' . $column, 'customers.' . $column)
                    ->whereColumn($alias . '.id', '!=', 'customers.id')
                    ->whereNotNull($alias . '.' . $column)
                    ->where($alias . '.' . $column, '!=', '');
            }, $column . '_match_count');
        }
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
            'id', 'name', 'phone', 'email', 'customer_code', 'lifecycle_stage', 'assigned_user_id', 'is_duplicate_candidate',
        ])->filter(fn (string $column) => Schema::hasColumn('customers', $column))->values()->all();
    }

    protected function ensureStorage(): void
    {
        if (!Schema::hasTable('customers')) {
            throw ValidationException::withMessages(['customers' => 'The customers table is not available.']);
        }
    }
}
