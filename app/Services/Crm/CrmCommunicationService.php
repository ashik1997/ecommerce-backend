<?php

namespace App\Services\Crm;

use App\Http\Controllers\Customer\Models\Customer;
use App\Models\Crm\CrmCommunication;
use App\Models\User;
use App\Services\RoleSidebarPermissionService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CrmCommunicationService
{
    public const MANUAL_CHANNELS = ['phone', 'email', 'sms', 'whatsapp', 'meeting', 'other'];
    public const DIRECTIONS = ['inbound', 'outbound'];
    public const MANUAL_STATUS = 'logged';
    public const MANUAL_SOURCE_MODULE = 'crm_manual';

    public function __construct(
        protected CrmActivityService $activityService,
        protected RoleSidebarPermissionService $permissionService
    ) {
    }

    /**
     * Best-effort adapter-facing logger retained for future verified hooks.
     * Stage 5 does not connect any production SMS, email, or newsletter flow.
     */
    public function log(array $data): ?CrmCommunication
    {
        try {
            return CrmCommunication::create([
                'product_website_id' => $data['product_website_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'channel' => $data['channel'] ?? 'unknown',
                'direction' => $data['direction'] ?? 'outbound',
                'subject' => $data['subject'] ?? null,
                'message' => $data['message'] ?? null,
                'status' => $data['status'] ?? null,
                'provider' => $data['provider'] ?? null,
                'provider_reference' => $data['provider_reference'] ?? null,
                'source_module' => $data['source_module'] ?? null,
                'sent_by' => $data['sent_by'] ?? auth()->id(),
                'sent_at' => $data['sent_at'] ?? null,
                'failed_at' => $data['failed_at'] ?? null,
                'failure_reason' => $data['failure_reason'] ?? null,
                'metadata' => Arr::get($data, 'metadata'),
            ]);
        } catch (\Throwable $exception) {
            Log::error('CRM communication log failed', [
                'customer_id' => $data['customer_id'] ?? null,
                'channel' => $data['channel'] ?? null,
                'provider' => $data['provider'] ?? null,
                'provider_reference' => $data['provider_reference'] ?? null,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    public function worklistQuery(array $filters = []): Builder
    {
        $this->ensureStorage();

        $query = CrmCommunication::query()->with([
            'customer:id,name,slug',
            'sender:id,name',
        ]);

        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', (int) $filters['customer_id']);
        }
        if (!empty($filters['channel'])) {
            $query->where('channel', trim((string) $filters['channel']));
        }
        if (!empty($filters['direction']) && in_array($filters['direction'], self::DIRECTIONS, true)) {
            $query->where('direction', $filters['direction']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', trim((string) $filters['status']));
        }
        if (!empty($filters['source_module'])) {
            $query->where('source_module', trim((string) $filters['source_module']));
        }
        if (!empty($filters['sent_from'])) {
            $query->whereDate('sent_at', '>=', $filters['sent_from']);
        }
        if (!empty($filters['sent_to'])) {
            $query->whereDate('sent_at', '<=', $filters['sent_to']);
        }

        return $query
            ->orderByRaw('COALESCE(sent_at, created_at) DESC')
            ->orderByDesc('id');
    }

    public function applyGlobalSearch(Builder $query, ?string $search): void
    {
        $search = trim((string) $search);
        if ($search === '') {
            return;
        }

        $query->where(function (Builder $nested) use ($search) {
            $nested->where('subject', 'like', '%' . $search . '%')
                ->orWhere('message', 'like', '%' . $search . '%')
                ->orWhere('channel', 'like', '%' . $search . '%')
                ->orWhere('direction', 'like', '%' . $search . '%')
                ->orWhere('status', 'like', '%' . $search . '%')
                ->orWhere('source_module', 'like', '%' . $search . '%')
                ->orWhere('provider', 'like', '%' . $search . '%')
                ->orWhereHas('customer', fn (Builder $customer) => $customer->where('name', 'like', '%' . $search . '%'))
                ->orWhereHas('sender', fn (Builder $sender) => $sender->where('name', 'like', '%' . $search . '%'));
        });
    }

    public function customerOptions(?string $search = null): Collection
    {
        $search = trim((string) $search);

        return Customer::query()
            ->select(['id', 'name'])
            ->where('status', 'active')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('name', 'like', '%' . $search . '%')
                        ->orWhere('phone', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('name')
            ->limit(30)
            ->get()
            ->map(fn (Customer $customer) => [
                'id' => $customer->id,
                'text' => $customer->name ?: ('Customer #' . $customer->id),
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
        if (!Schema::hasTable('crm_communications')) {
            return [
                'channels' => self::MANUAL_CHANNELS,
                'directions' => self::DIRECTIONS,
                'statuses' => [self::MANUAL_STATUS],
                'source_modules' => [self::MANUAL_SOURCE_MODULE],
            ];
        }

        return [
            'channels' => $this->distinctValues('channel', self::MANUAL_CHANNELS),
            'directions' => $this->distinctValues('direction', self::DIRECTIONS),
            'statuses' => $this->distinctValues('status', [self::MANUAL_STATUS]),
            'source_modules' => $this->distinctValues('source_module', [self::MANUAL_SOURCE_MODULE]),
        ];
    }

    public function createManual(array $data, User $actor): CrmCommunication
    {
        $this->ensureStorage();
        $this->authorize($actor, 'create');

        try {
            $communication = DB::transaction(function () use ($data, $actor) {
                $customer = Customer::query()->lockForUpdate()->findOrFail((int) $data['customer_id']);

                $communication = CrmCommunication::query()->create([
                    'product_website_id' => $actor->product_website_id ?? $customer->product_website_id ?? null,
                    'customer_id' => $customer->id,
                    'channel' => $data['channel'],
                    'direction' => $data['direction'],
                    'subject' => $this->nullableTrim($data['subject'] ?? null),
                    'message' => trim((string) $data['message']),
                    'status' => self::MANUAL_STATUS,
                    'provider' => null,
                    'provider_reference' => null,
                    'source_module' => self::MANUAL_SOURCE_MODULE,
                    'sent_by' => $actor->id,
                    'sent_at' => $data['interaction_at'] ?? now(),
                    'failed_at' => null,
                    'failure_reason' => null,
                    'metadata' => ['manual_log' => true],
                ])->refresh();

                $this->activityService->recordOrFail([
                    'customer_id' => $communication->customer_id,
                    'activity_type' => 'crm_communication_logged',
                    'subject' => 'CRM communication manually logged',
                    'description' => ucfirst($communication->direction) . ' ' . $communication->channel . ' communication history recorded manually.',
                    'source_module' => 'crm_communications',
                    'source_id' => $communication->id,
                    'performed_by' => $actor->id,
                    'occurred_at' => $communication->sent_at ?: now(),
                    'metadata' => [
                        'channel' => $communication->channel,
                        'direction' => $communication->direction,
                        'status' => $communication->status,
                        'source_module' => $communication->source_module,
                    ],
                ]);

                return $communication;
            });
        } catch (\Throwable $exception) {
            $this->logFailure('manual_create', null, (int) ($data['customer_id'] ?? 0), $actor->id, $exception);
            throw $exception;
        }


        return $communication->load(['customer:id,name,slug', 'sender:id,name']);
    }

    public function details(int $communicationId, User $actor): CrmCommunication
    {
        $this->ensureStorage();
        $this->authorize($actor, 'read');

        return CrmCommunication::query()
            ->with(['customer:id,name,slug', 'sender:id,name'])
            ->findOrFail($communicationId);
    }

    public function payload(CrmCommunication $communication, User $actor, bool $includeDetails = false): array
    {
        $canProfile = $communication->customer_id
            && $this->permissionService->userCan($actor, 'crm.customers.profile', 'read')
            && Route::has('crm.customers.profile');
        $canViewDetails = $this->permissionService->userCan($actor, 'crm.communications.list', 'read')
            && Route::has('crm.communications.show');
        $loggedAt = $communication->sent_at ?: $communication->created_at;

        $payload = [
            'id' => $communication->id,
            'customer_id' => $communication->customer_id,
            'customer' => optional($communication->customer)->name,
            'customer_profile_url' => $canProfile ? route('crm.customers.profile', ['customer' => $communication->customer_id]) : null,
            'channel' => $communication->channel,
            'direction' => $communication->direction,
            'subject' => $communication->subject,
            'message_summary' => Str::limit(trim((string) $communication->message), 140),
            'status' => $communication->status,
            'source_module' => $communication->source_module,
            'provider' => $communication->provider,
            'actor' => optional($communication->sender)->name,
            'logged_at' => optional($loggedAt)->format('Y-m-d h:i a'),
            'created_at' => optional($communication->created_at)->format('Y-m-d h:i a'),
            'details_url' => $canViewDetails ? route('crm.communications.show', ['communication' => $communication->id]) : null,
        ];

        if ($includeDetails) {
            $payload += [
                'message' => $communication->message,
                'provider_reference' => $communication->provider_reference,
                'failed_at' => optional($communication->failed_at)->format('Y-m-d h:i a'),
                'failure_reason' => $communication->failure_reason,
            ];
        }

        return $payload;
    }

    protected function distinctValues(string $column, array $defaults = []): array
    {
        return CrmCommunication::query()
            ->whereNotNull($column)
            ->where($column, '<>', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->map(fn ($value) => trim((string) $value))
            ->merge($defaults)
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    protected function nullableTrim($value): ?string
    {
        $value = is_string($value) ? trim($value) : $value;
        return $value === null || $value === '' ? null : (string) $value;
    }

    protected function authorize(User $actor, string $action): void
    {
        if (!$this->permissionService->userCan($actor, 'crm.communications.list', $action)) {
            throw new AuthorizationException('You do not have permission to perform this CRM communication action.');
        }
    }

    protected function ensureStorage(): void
    {
        if (!Schema::hasTable('crm_communications')) {
            throw ValidationException::withMessages([
                'communication' => ['CRM communication storage is not available.'],
            ]);
        }
    }

    protected function logFailure(string $operation, ?int $communicationId, int $customerId, int $actorId, \Throwable $exception): void
    {
        Log::error('CRM communication write failed', [
            'operation' => $operation,
            'communication_id' => $communicationId,
            'customer_id' => $customerId ?: null,
            'actor_id' => $actorId,
            'error' => $exception->getMessage(),
        ]);
    }
}
