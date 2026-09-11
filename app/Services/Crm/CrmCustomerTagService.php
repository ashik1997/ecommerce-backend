<?php

namespace App\Services\Crm;

use App\Models\Crm\CustomerTag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CrmCustomerTagService
{
    protected CrmActivityService $activityService;

    public function __construct(CrmActivityService $activityService)
    {
        $this->activityService = $activityService;
    }

    public function listQuery(): Builder
    {
        return CustomerTag::query()
            ->select([
                'id',
                'name',
                'slug',
                'color',
                'status',
                'created_by',
                'updated_by',
                'created_at',
                'updated_at',
            ])
            ->withCount('customers')
            ->orderByDesc('id');
    }

    public function create(array $data, ?int $actorId = null): CustomerTag
    {
        try {
            $tag = DB::transaction(function () use ($data, $actorId) {
                $payload = $this->normalizedPayload($data);
                $this->assertUnique($payload['name'], $payload['slug']);

                return CustomerTag::create([
                    'name' => $payload['name'],
                    'slug' => $payload['slug'],
                    'color' => $payload['color'],
                    'status' => $payload['status'],
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                ]);
            });
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (QueryException $exception) {
            $this->throwDuplicateValidationException($exception);
            $this->logWriteFailure('create', null, $exception);
            throw $exception;
        } catch (\Throwable $exception) {
            $this->logWriteFailure('create', null, $exception);
            throw $exception;
        }

        $this->recordActivity('crm_tag_created', $tag, $actorId);

        return $tag;
    }

    public function update(int $tagId, array $data, ?int $actorId = null): CustomerTag
    {
        try {
            [$tag, $statusChanged] = DB::transaction(function () use ($tagId, $data, $actorId) {
                $tag = CustomerTag::query()->lockForUpdate()->findOrFail($tagId);
                $payload = $this->normalizedPayload($data);
                $this->assertUnique($payload['name'], $payload['slug'], $tag->id);
                $statusChanged = $tag->status !== $payload['status'];

                $tag->forceFill([
                    'name' => $payload['name'],
                    'slug' => $payload['slug'],
                    'color' => $payload['color'],
                    'status' => $payload['status'],
                    'updated_by' => $actorId,
                ])->save();

                return [$tag->refresh(), $statusChanged];
            });
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (QueryException $exception) {
            $this->throwDuplicateValidationException($exception);
            $this->logWriteFailure('update', $tagId, $exception);
            throw $exception;
        } catch (\Throwable $exception) {
            $this->logWriteFailure('update', $tagId, $exception);
            throw $exception;
        }

        $this->recordActivity('crm_tag_updated', $tag, $actorId);

        if ($statusChanged) {
            $this->recordActivity($tag->status === 'active' ? 'crm_tag_activated' : 'crm_tag_deactivated', $tag, $actorId);
        }

        return $tag;
    }

    public function changeStatus(int $tagId, string $status, ?int $actorId = null): CustomerTag
    {
        if (!in_array($status, ['active', 'inactive'], true)) {
            throw ValidationException::withMessages(['status' => ['The selected status is invalid.']]);
        }

        try {
            [$tag, $statusChanged] = DB::transaction(function () use ($tagId, $status, $actorId) {
                $tag = CustomerTag::query()->lockForUpdate()->findOrFail($tagId);
                $statusChanged = $tag->status !== $status;

                if ($statusChanged) {
                    $tag->forceFill([
                        'status' => $status,
                        'updated_by' => $actorId,
                    ])->save();
                }

                return [$tag->refresh(), $statusChanged];
            });
        } catch (\Throwable $exception) {
            $this->logWriteFailure('status_change', $tagId, $exception);
            throw $exception;
        }

        if ($statusChanged) {
            $this->recordActivity($status === 'active' ? 'crm_tag_activated' : 'crm_tag_deactivated', $tag, $actorId);
        }

        return $tag;
    }

    protected function normalizedPayload(array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        $slugSource = trim((string) ($data['slug'] ?? ''));
        $slug = Str::slug($slugSource !== '' ? $slugSource : $name);

        if ($slug === '') {
            $slug = 'tag-' . Str::lower(Str::random(10));
        }

        return [
            'name' => $name,
            'slug' => $slug,
            'color' => isset($data['color']) && trim((string) $data['color']) !== '' ? trim((string) $data['color']) : null,
            'status' => $data['status'] ?? 'active',
        ];
    }

    protected function assertUnique(string $name, string $slug, ?int $exceptId = null): void
    {
        $nameQuery = CustomerTag::query()
            ->when($exceptId, fn(Builder $query) => $query->where('id', '<>', $exceptId))
            ->whereRaw('LOWER(TRIM(name)) = ?', [Str::lower($name)])
            ->lockForUpdate();

        if ($nameQuery->exists()) {
            throw ValidationException::withMessages(['name' => ['A CRM customer tag with this name already exists.']]);
        }

        $slugQuery = CustomerTag::query()
            ->when($exceptId, fn(Builder $query) => $query->where('id', '<>', $exceptId))
            ->where('slug', $slug)
            ->lockForUpdate();

        if ($slugQuery->exists()) {
            throw ValidationException::withMessages(['slug' => ['A CRM customer tag with this slug already exists.']]);
        }
    }

    protected function throwDuplicateValidationException(QueryException $exception): void
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? '');
        $message = strtolower($exception->getMessage());

        if ($sqlState === '23000' || str_contains($message, 'duplicate')) {
            throw ValidationException::withMessages([
                'name' => ['The CRM customer tag name or slug is already in use.'],
            ]);
        }
    }

    protected function recordActivity(string $activityType, CustomerTag $tag, ?int $actorId): void
    {
        $this->activityService->record([
            'activity_type' => $activityType,
            'subject' => 'CRM customer tag: ' . $tag->name,
            'description' => 'CRM customer tag settings changed.',
            'source_module' => 'crm_customer_tags',
            'source_id' => $tag->id,
            'performed_by' => $actorId,
            'metadata' => [
                'tag_id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
                'status' => $tag->status,
            ],
        ]);
    }

    protected function logWriteFailure(string $operation, ?int $tagId, \Throwable $exception): void
    {
        Log::error('CRM customer tag write failed', [
            'operation' => $operation,
            'tag_id' => $tagId,
            'error' => $exception->getMessage(),
        ]);
    }
}
