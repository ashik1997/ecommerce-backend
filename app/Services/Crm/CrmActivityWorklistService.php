<?php

namespace App\Services\Crm;

use App\Http\Controllers\Customer\Models\Customer;
use App\Models\Crm\CrmActivity;
use App\Models\User;
use App\Services\RoleSidebarPermissionService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CrmActivityWorklistService
{
    public function __construct(protected RoleSidebarPermissionService $permissionService)
    {
    }

    public function worklistQuery(array $filters = []): Builder
    {
        $this->ensureStorage();

        $query = CrmActivity::query()->with([
            'customer:id,name,slug',
            'performer:id,name',
        ]);

        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', (int) $filters['customer_id']);
        }
        if (!empty($filters['performed_by'])) {
            $query->where('performed_by', (int) $filters['performed_by']);
        }
        if (!empty($filters['activity_type'])) {
            $query->where('activity_type', trim((string) $filters['activity_type']));
        }
        if (!empty($filters['source_module'])) {
            $query->where('source_module', trim((string) $filters['source_module']));
        }
        if (!empty($filters['occurred_from'])) {
            $date = $filters['occurred_from'];
            $query->where(function (Builder $nested) use ($date) {
                $nested->whereDate('occurred_at', '>=', $date)
                    ->orWhere(function (Builder $fallback) use ($date) {
                        $fallback->whereNull('occurred_at')->whereDate('created_at', '>=', $date);
                    });
            });
        }
        if (!empty($filters['occurred_to'])) {
            $date = $filters['occurred_to'];
            $query->where(function (Builder $nested) use ($date) {
                $nested->whereDate('occurred_at', '<=', $date)
                    ->orWhere(function (Builder $fallback) use ($date) {
                        $fallback->whereNull('occurred_at')->whereDate('created_at', '<=', $date);
                    });
            });
        }

        return $query
            ->orderByRaw('COALESCE(occurred_at, created_at) DESC')
            ->orderByDesc('id');
    }

    public function applyGlobalSearch(Builder $query, ?string $search): void
    {
        $search = trim((string) $search);
        if ($search === '') {
            return;
        }

        $query->where(function (Builder $nested) use ($search) {
            $nested->where('activity_type', 'like', '%' . $search . '%')
                ->orWhere('subject', 'like', '%' . $search . '%')
                ->orWhere('description', 'like', '%' . $search . '%')
                ->orWhere('source_module', 'like', '%' . $search . '%')
                ->orWhere('source_id', 'like', '%' . $search . '%')
                ->orWhereHas('customer', fn (Builder $customer) => $customer->where('name', 'like', '%' . $search . '%'))
                ->orWhereHas('performer', fn (Builder $performer) => $performer->where('name', 'like', '%' . $search . '%'));
        });
    }

    public function customerOptions(?string $search = null, int $limit = 30): Collection
    {
        $search = trim((string) $search);

        return Customer::query()
            ->select(['id', 'name'])
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $nested) use ($search) {
                    $nested->where('name', 'like', '%' . $search . '%')
                        ->orWhere('phone', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('name')
            ->limit(max(1, min($limit, 100)))
            ->get()
            ->map(fn (Customer $customer) => [
                'id' => $customer->id,
                'text' => $customer->name ?: ('Customer #' . $customer->id),
            ]);
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
            ->map(fn (User $user) => [
                'id' => $user->id,
                'text' => $user->name ?: ('User #' . $user->id),
            ]);
    }

    public function customerOption(?int $customerId): ?array
    {
        if (!$customerId) {
            return null;
        }

        $customer = Customer::query()->find($customerId);

        return $customer ? [
            'id' => $customer->id,
            'text' => $customer->name ?: ('Customer #' . $customer->id),
        ] : null;
    }

    public function filterOptions(): array
    {
        if (!Schema::hasTable('crm_activities')) {
            return [
                'activity_types' => [],
                'source_modules' => [],
            ];
        }

        return [
            'activity_types' => $this->distinctValues('activity_type'),
            'source_modules' => $this->distinctValues('source_module'),
        ];
    }

    public function details(int $activityId, User $actor): CrmActivity
    {
        $this->ensureStorage();
        $this->authorize($actor);

        return CrmActivity::query()
            ->with(['customer:id,name,slug', 'performer:id,name'])
            ->findOrFail($activityId);
    }

    public function payload(CrmActivity $activity, User $actor, bool $includeDetails = false): array
    {
        $canProfile = $activity->customer_id
            && $this->permissionService->userCan($actor, 'crm.customers.profile', 'read')
            && Route::has('crm.customers.profile');
        $canViewDetails = $this->permissionService->userCan($actor, 'crm.activities.list', 'read')
            && Route::has('crm.activities.show');
        $occurredAt = $activity->occurred_at ?: $activity->created_at;

        $payload = [
            'id' => $activity->id,
            'customer_id' => $activity->customer_id,
            'customer' => optional($activity->customer)->name,
            'customer_profile_url' => $canProfile ? route('crm.customers.profile', ['customer' => $activity->customer_id]) : null,
            'activity_type' => $activity->activity_type,
            'subject' => $activity->subject,
            'description_summary' => Str::limit(trim((string) $activity->description), 160),
            'source_module' => $activity->source_module,
            'source_id' => $activity->source_id,
            'actor' => optional($activity->performer)->name,
            'occurred_at' => optional($occurredAt)->format('Y-m-d h:i a'),
            'created_at' => optional($activity->created_at)->format('Y-m-d h:i a'),
            'details_url' => $canViewDetails ? route('crm.activities.show', ['activity' => $activity->id]) : null,
        ];

        if ($includeDetails) {
            $payload['description'] = $activity->description;
        }

        return $payload;
    }

    protected function distinctValues(string $column): array
    {
        return CrmActivity::query()
            ->whereNotNull($column)
            ->where($column, '<>', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function authorize(User $actor): void
    {
        if (!$this->permissionService->userCan($actor, 'crm.activities.list', 'read')) {
            throw new AuthorizationException('You do not have permission to view CRM activity history.');
        }
    }

    protected function ensureStorage(): void
    {
        if (!Schema::hasTable('crm_activities')) {
            throw ValidationException::withMessages([
                'activity' => ['CRM activity storage is not available.'],
            ]);
        }
    }
}
