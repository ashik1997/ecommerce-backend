<?php

namespace App\Services\Crm;

use App\Http\Controllers\Customer\Models\Customer;
use App\Models\Crm\CustomerTag;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CustomerTagAssignmentService
{
    protected CrmActivityService $activityService;

    public function __construct(CrmActivityService $activityService)
    {
        $this->activityService = $activityService;
    }

    public function activeOptions(?string $search = null, int $limit = 30): Collection
    {
        $search = trim((string) $search);
        $limit = max(1, min($limit, 100));

        return CustomerTag::query()
            ->where('status', 'active')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('name', 'like', '%' . $search . '%')
                        ->orWhere('slug', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'slug', 'color'])
            ->map(fn(CustomerTag $tag) => [
                'id' => $tag->id,
                'text' => $tag->name,
                'slug' => $tag->slug,
                'color' => $tag->color,
            ]);
    }

    public function assignedTags(int $customerId): Collection
    {
        $customer = Customer::query()->findOrFail($customerId);

        return $customer->tags()
            ->select([
                'crm_customer_tags.id',
                'crm_customer_tags.name',
                'crm_customer_tags.slug',
                'crm_customer_tags.color',
                'crm_customer_tags.status',
            ])
            ->orderBy('crm_customer_tags.name')
            ->get();
    }

    public function sync(int $customerId, array $tagIds, ?int $actorId = null): array
    {
        $requestedIds = collect($tagIds)
            ->map(fn($id) => (int) $id)
            ->filter(fn(int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        try {
            $changes = DB::transaction(function () use ($customerId, $requestedIds, $actorId) {
                Customer::query()->lockForUpdate()->findOrFail($customerId);

                $existingIds = DB::table('crm_customer_tag_pivots')
                    ->where('customer_id', $customerId)
                    ->lockForUpdate()
                    ->pluck('crm_customer_tag_id')
                    ->map(fn($id) => (int) $id)
                    ->all();

                $tags = CustomerTag::query()
                    ->whereIn('id', $requestedIds)
                    ->get(['id', 'status'])
                    ->keyBy('id');

                $missingIds = array_values(array_diff($requestedIds, $tags->keys()->map(fn($id) => (int) $id)->all()));
                if (!empty($missingIds)) {
                    throw ValidationException::withMessages([
                        'tag_ids' => ['One or more selected CRM customer tags do not exist.'],
                    ]);
                }

                $addedIds = array_values(array_diff($requestedIds, $existingIds));
                $removedIds = array_values(array_diff($existingIds, $requestedIds));

                $inactiveNewIds = collect($addedIds)
                    ->filter(fn(int $id) => ($tags->get($id)?->status ?? null) !== 'active')
                    ->values()
                    ->all();

                if (!empty($inactiveNewIds)) {
                    throw ValidationException::withMessages([
                        'tag_ids' => ['Inactive CRM customer tags cannot be newly assigned.'],
                    ]);
                }

                if (!empty($addedIds)) {
                    $timestamp = now();
                    $rows = collect($addedIds)->map(fn(int $tagId) => [
                        'customer_id' => $customerId,
                        'crm_customer_tag_id' => $tagId,
                        'created_by' => $actorId,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ])->all();

                    DB::table('crm_customer_tag_pivots')->insertOrIgnore($rows);
                }

                if (!empty($removedIds)) {
                    DB::table('crm_customer_tag_pivots')
                        ->where('customer_id', $customerId)
                        ->whereIn('crm_customer_tag_id', $removedIds)
                        ->delete();
                }

                return [
                    'added_tag_ids' => $addedIds,
                    'removed_tag_ids' => $removedIds,
                ];
            });
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            Log::error('CRM customer tag sync failed', [
                'customer_id' => $customerId,
                'tag_ids' => $requestedIds,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        $this->activityService->record([
            'customer_id' => $customerId,
            'activity_type' => 'customer_tags_synced',
            'subject' => 'CRM customer tags synchronized',
            'description' => 'Customer CRM tag assignments were synchronized through an authorized request.',
            'source_module' => 'crm_customer_tag_pivots',
            'performed_by' => $actorId,
            'metadata' => $changes,
        ]);

        return [
            'customer_id' => $customerId,
            'added_tag_ids' => $changes['added_tag_ids'],
            'removed_tag_ids' => $changes['removed_tag_ids'],
            'tags' => $this->assignedTags($customerId),
        ];
    }
}
