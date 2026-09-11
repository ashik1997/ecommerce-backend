<?php

namespace App\Services\Crm;

use App\Models\User;
use App\Services\RoleSidebarPermissionService;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CrmDashboardService
{
    protected Carbon $now;

    protected array $tableCache = [];

    protected array $columnCache = [];

    public function __construct(protected RoleSidebarPermissionService $permissionService)
    {
    }

    public function overview(?User $actor): array
    {
        $this->now = Carbon::now('Asia/Dhaka');

        $customerMetrics = $this->customerMetrics();
        $taskMetrics = $this->taskMetrics();
        $leadMetrics = $this->leadMetrics();
        $communicationMetrics = $this->communicationMetrics();
        $legacyContactMetrics = $this->legacyContactMetrics();

        return [
            'generated_at' => $this->now->format('M d, Y h:i A'),
            'availability' => [
                'customers' => $this->hasTable('customers'),
                'tasks' => $this->hasTable('crm_tasks'),
                'leads' => $this->hasTable('crm_leads'),
                'communications' => $this->hasTable('crm_communications'),
                'activities' => $this->hasTable('crm_activities'),
                'legacy_contact_histories' => $this->hasTable('customer_contact_histories'),
                'legacy_scheduled_contacts' => $this->hasTable('customer_next_contact_dates'),
            ],
            'permissions' => $this->permissions($actor),
            'metrics' => array_merge($customerMetrics, $taskMetrics, $leadMetrics, $communicationMetrics, $legacyContactMetrics),
            'lifecycle_summary' => $this->customerBreakdown('lifecycle_stage'),
            'credit_status_summary' => $this->customerBreakdown('credit_status'),
            'lead_status_summary' => $this->leadStatusSummary(),
            'recent_customers' => $this->recentCustomers(),
            'open_tasks' => $this->openTasks(),
            'upcoming_follow_ups' => $this->upcomingFollowUps(),
            'lead_follow_ups' => $this->leadFollowUps(),
            'recent_communications' => $this->recentCommunications(),
            'recent_activities' => $this->recentActivities(),
        ];
    }

    protected function permissions(?User $actor): array
    {
        return [
            'can_view_customers' => $this->permissionService->userCan($actor, 'crm.customers.list', 'read'),
            'can_view_customer_profiles' => $this->permissionService->userCan($actor, 'crm.customers.profile', 'read'),
            'can_view_customer_health' => $this->permissionService->userCan($actor, 'crm.customer-health.list', 'read'),
            'can_view_customer_segments' => $this->permissionService->userCan($actor, 'crm.customer-segments.list', 'read'),
            'can_view_saved_customer_segments' => $this->permissionService->userCan($actor, 'crm.saved-customer-segments.list', 'read'),
            'can_view_campaign_drafts' => $this->permissionService->userCan($actor, 'crm.campaign-drafts.list', 'read'),
            'can_view_duplicate_customers' => $this->permissionService->userCan($actor, 'crm.duplicate-customers.list', 'read'),
            'can_view_tasks' => $this->permissionService->userCan($actor, 'crm.tasks.list', 'read'),
            'can_view_task_calendar' => $this->permissionService->userCan($actor, 'crm.tasks.calendar', 'read'),
            'can_view_leads' => $this->permissionService->userCan($actor, 'crm.leads.list', 'read'),
            'can_view_communications' => $this->permissionService->userCan($actor, 'crm.communications.list', 'read'),
            'can_view_activities' => $this->permissionService->userCan($actor, 'crm.activities.list', 'read'),
        ];
    }

    protected function customerMetrics(): array
    {
        $defaults = [
            'active_customers' => 0,
            'recently_added_customers' => 0,
            'upcoming_follow_ups' => 0,
        ];

        if (!$this->hasTable('customers')) {
            return $defaults;
        }

        return $this->safe(function () {
            $activeCustomers = $this->activeCustomersQuery();

            return [
                'active_customers' => (clone $activeCustomers)->count(),
                'recently_added_customers' => $this->hasColumn('customers', 'created_at')
                    ? (clone $activeCustomers)->where('created_at', '>=', $this->now->copy()->subDays(30))->count()
                    : 0,
                'upcoming_follow_ups' => $this->hasColumn('customers', 'next_follow_up_at')
                    ? (clone $activeCustomers)->whereNotNull('next_follow_up_at')->where('next_follow_up_at', '>=', $this->now)->count()
                    : 0,
            ];
        }, $defaults, 'customer metrics');
    }

    protected function taskMetrics(): array
    {
        $defaults = [
            'open_tasks' => 0,
            'overdue_tasks' => 0,
            'tasks_due_today' => 0,
        ];

        if (!$this->hasTable('crm_tasks') || !$this->hasColumn('crm_tasks', 'status')) {
            return $defaults;
        }

        return $this->safe(function () {
            $openTasks = $this->openTasksQuery();

            return [
                'open_tasks' => (clone $openTasks)->count(),
                'overdue_tasks' => $this->hasColumn('crm_tasks', 'due_at')
                    ? (clone $openTasks)->whereNotNull('due_at')->where('due_at', '<', $this->now)->count()
                    : 0,
                'tasks_due_today' => $this->hasColumn('crm_tasks', 'due_at')
                    ? (clone $openTasks)->whereDate('due_at', $this->now->toDateString())->count()
                    : 0,
            ];
        }, $defaults, 'task metrics');
    }

    protected function leadMetrics(): array
    {
        $defaults = [
            'open_leads' => 0,
            'overdue_lead_follow_ups' => 0,
            'qualified_leads' => 0,
            'converted_leads_30_days' => 0,
        ];

        if (!$this->hasTable('crm_leads')) {
            return $defaults;
        }

        return $this->safe(function () {
            $openLeads = $this->openLeadsQuery();
            $allLeads = $this->leadRecordsQuery();

            return [
                'open_leads' => (clone $openLeads)->count(),
                'overdue_lead_follow_ups' => $this->hasColumn('crm_leads', 'next_follow_up_at')
                    ? (clone $openLeads)->whereNotNull('next_follow_up_at')->where('next_follow_up_at', '<', $this->now)->count()
                    : 0,
                'qualified_leads' => $this->hasColumn('crm_leads', 'status')
                    ? (clone $openLeads)->where('status', 'qualified')->count()
                    : 0,
                'converted_leads_30_days' => $this->hasColumn('crm_leads', 'status') && $this->hasColumn('crm_leads', 'converted_at')
                    ? (clone $allLeads)->where('status', 'converted')->where('converted_at', '>=', $this->now->copy()->subDays(30))->count()
                    : 0,
            ];
        }, $defaults, 'lead metrics');
    }

    protected function communicationMetrics(): array
    {
        $defaults = [
            'recent_communication_logs' => 0,
            'manual_communication_logs' => 0,
        ];

        if (!$this->hasTable('crm_communications')) {
            return $defaults;
        }

        return $this->safe(function () {
            $communications = DB::table('crm_communications');
            $recent = clone $communications;

            if ($this->hasColumn('crm_communications', 'sent_at') && $this->hasColumn('crm_communications', 'created_at')) {
                $recent->whereRaw('COALESCE(sent_at, created_at) >= ?', [$this->now->copy()->subDays(30)]);
            } elseif ($this->hasColumn('crm_communications', 'sent_at')) {
                $recent->where('sent_at', '>=', $this->now->copy()->subDays(30));
            } elseif ($this->hasColumn('crm_communications', 'created_at')) {
                $recent->where('created_at', '>=', $this->now->copy()->subDays(30));
            } else {
                return [
                    'recent_communication_logs' => 0,
                    'manual_communication_logs' => $this->manualCommunicationCount($communications),
                ];
            }

            return [
                'recent_communication_logs' => $recent->count(),
                'manual_communication_logs' => $this->manualCommunicationCount($communications),
            ];
        }, $defaults, 'communication metrics');
    }

    protected function manualCommunicationCount(Builder $communications): int
    {
        if (!$this->hasColumn('crm_communications', 'source_module')) {
            return 0;
        }

        return (clone $communications)->where('source_module', 'crm_manual')->count();
    }

    protected function legacyContactMetrics(): array
    {
        $metrics = [
            'legacy_contact_histories' => 0,
            'legacy_scheduled_contacts' => 0,
            'legacy_upcoming_contacts' => 0,
            'legacy_pending_contacts' => 0,
            'legacy_missed_contacts' => 0,
            'legacy_completed_contacts' => 0,
        ];

        if ($this->hasTable('customer_contact_histories')) {
            $metrics['legacy_contact_histories'] = $this->safe(function () {
                $query = DB::table('customer_contact_histories');
                if ($this->hasColumn('customer_contact_histories', 'status')) {
                    $query->where('status', 'active');
                }

                return $query->count();
            }, 0, 'legacy contact history metric');
        }

        if (!$this->hasTable('customer_next_contact_dates')) {
            return $metrics;
        }

        return array_merge($metrics, $this->safe(function () {
            $base = DB::table('customer_next_contact_dates');
            $active = clone $base;
            if ($this->hasColumn('customer_next_contact_dates', 'status')) {
                $active->where('status', 'active');
            }

            $upcoming = clone $base;
            if ($this->hasColumn('customer_next_contact_dates', 'next_date')) {
                $upcoming->where('next_date', '>=', $this->now);
            } else {
                $upcoming->whereRaw('1 = 0');
            }

            // Preserve the existing dashboard semantics until application
            // verification explicitly approves a behavioral correction.
            return [
                'legacy_scheduled_contacts' => $active->count(),
                'legacy_upcoming_contacts' => (clone $upcoming)->count(),
                'legacy_pending_contacts' => $this->legacyScheduledContactStatusCount($upcoming, 'pending'),
                'legacy_missed_contacts' => $this->legacyScheduledContactStatusCount($upcoming, 'missed'),
                'legacy_completed_contacts' => $this->legacyScheduledContactStatusCount($upcoming, 'done'),
            ];
        }, [], 'legacy scheduled contact metrics'));
    }

    protected function legacyScheduledContactStatusCount(Builder $query, string $status): int
    {
        if (!$this->hasColumn('customer_next_contact_dates', 'contact_status')) {
            return 0;
        }

        return (clone $query)->where('contact_status', $status)->count();
    }

    protected function customerBreakdown(string $column): array
    {
        if (!$this->hasTable('customers') || !$this->hasColumn('customers', $column)) {
            return [];
        }

        return $this->safe(function () use ($column) {
            return $this->activeCustomersQuery()
                ->select([$column, DB::raw('COUNT(*) as total')])
                ->groupBy($column)
                ->orderByDesc('total')
                ->get()
                ->map(fn ($row) => [
                    'label' => trim((string) ($row->{$column} ?? '')) ?: 'Unspecified',
                    'total' => (int) ($row->total ?? 0),
                ])
                ->values()
                ->all();
        }, [], 'customer ' . $column . ' breakdown');
    }

    protected function leadStatusSummary(): array
    {
        if (!$this->hasTable('crm_leads') || !$this->hasColumn('crm_leads', 'status')) {
            return [];
        }

        return $this->safe(function () {
            return $this->leadRecordsQuery()
                ->select(['status', DB::raw('COUNT(*) as total')])
                ->groupBy('status')
                ->orderByDesc('total')
                ->get()
                ->map(fn ($row) => [
                    'label' => trim((string) ($row->status ?? '')) ?: 'Unspecified',
                    'total' => (int) ($row->total ?? 0),
                ])
                ->values()
                ->all();
        }, [], 'lead status breakdown');
    }

    protected function recentCustomers(): array
    {
        if (!$this->hasTable('customers')) {
            return [];
        }

        return $this->safe(function () {
            $query = $this->activeCustomersQuery();
            $columns = $this->selectColumns('customers', [
                'id', 'name', 'phone', 'email', 'lifecycle_stage', 'credit_status', 'created_at',
            ]);

            if (empty($columns)) {
                return [];
            }

            $query->select($columns);
            $this->orderLatest($query, 'customers');

            return $query->limit(8)->get()->map(fn ($customer) => [
                'id' => (int) ($customer->id ?? 0),
                'name' => $customer->name ?? null,
                'phone' => $customer->phone ?? null,
                'email' => $customer->email ?? null,
                'lifecycle_stage' => $customer->lifecycle_stage ?? null,
                'credit_status' => $customer->credit_status ?? null,
                'created_at' => $this->formatDateTime($customer->created_at ?? null),
            ])->values()->all();
        }, [], 'recent customers');
    }

    protected function openTasks(): array
    {
        if (!$this->hasTable('crm_tasks') || !$this->hasColumn('crm_tasks', 'status')) {
            return [];
        }

        return $this->safe(function () {
            $query = $this->openTasksQuery('tasks');

            $hasTaskCustomerJoin = $this->hasTable('customers')
                && $this->hasColumn('crm_tasks', 'customer_id')
                && $this->hasColumn('customers', 'id');
            $hasAssignedUserJoin = $this->hasTable('users')
                && $this->hasColumn('crm_tasks', 'assigned_user_id')
                && $this->hasColumn('users', 'id');

            if ($hasTaskCustomerJoin) {
                $query->leftJoin('customers as task_customers', 'task_customers.id', '=', 'tasks.customer_id');
            }
            if ($hasAssignedUserJoin) {
                $query->leftJoin('users as assigned_users', 'assigned_users.id', '=', 'tasks.assigned_user_id');
            }

            $query->select([
                $this->columnOrNull('crm_tasks', 'id', 'tasks.id', 'id'),
                $this->columnOrNull('crm_tasks', 'customer_id', 'tasks.customer_id', 'customer_id'),
                $this->columnOrNull('crm_tasks', 'title', 'tasks.title', 'title'),
                $this->columnOrNull('crm_tasks', 'priority', 'tasks.priority', 'priority'),
                $this->columnOrNull('crm_tasks', 'status', 'tasks.status', 'status'),
                $this->columnOrNull('crm_tasks', 'due_at', 'tasks.due_at', 'due_at'),
                $this->joinedColumnOrNull($hasTaskCustomerJoin && $this->hasColumn('customers', 'name'), 'task_customers.name', 'customer_name'),
                $this->joinedColumnOrNull($hasAssignedUserJoin && $this->hasColumn('users', 'name'), 'assigned_users.name', 'assigned_user_name'),
            ]);

            if ($this->hasColumn('crm_tasks', 'due_at')) {
                $query->orderByRaw('CASE WHEN tasks.due_at IS NOT NULL AND tasks.due_at < ? THEN 0 ELSE 1 END', [$this->now])
                    ->orderBy('tasks.due_at');
            }
            if ($this->hasColumn('crm_tasks', 'id')) {
                $query->orderByDesc('tasks.id');
            }

            return $query->limit(8)->get()->map(fn ($task) => [
                'id' => (int) ($task->id ?? 0),
                'customer_id' => (int) ($task->customer_id ?? 0),
                'title' => $task->title ?? null,
                'priority' => $task->priority ?? null,
                'status' => $task->status ?? null,
                'customer_name' => $task->customer_name ?? null,
                'assigned_user_name' => $task->assigned_user_name ?? null,
                'due_at' => $this->formatDateTime($task->due_at ?? null),
                'is_overdue' => !empty($task->due_at) && Carbon::parse($task->due_at)->lt($this->now),
            ])->values()->all();
        }, [], 'open CRM tasks');
    }

    protected function upcomingFollowUps(): array
    {
        if (!$this->hasTable('customers') || !$this->hasColumn('customers', 'next_follow_up_at')) {
            return [];
        }

        return $this->safe(function () {
            $query = $this->activeCustomersQuery()
                ->whereNotNull('next_follow_up_at')
                ->where('next_follow_up_at', '>=', $this->now)
                ->orderBy('next_follow_up_at');

            $columns = $this->selectColumns('customers', ['id', 'name', 'phone', 'next_follow_up_at']);
            if (empty($columns)) {
                return [];
            }

            return $query->select($columns)->limit(8)->get()->map(fn ($customer) => [
                'id' => (int) ($customer->id ?? 0),
                'name' => $customer->name ?? null,
                'phone' => $customer->phone ?? null,
                'next_follow_up_at' => $this->formatDateTime($customer->next_follow_up_at ?? null),
            ])->values()->all();
        }, [], 'upcoming customer follow-ups');
    }

    protected function leadFollowUps(): array
    {
        if (!$this->hasTable('crm_leads') || !$this->hasColumn('crm_leads', 'next_follow_up_at')) {
            return [];
        }

        return $this->safe(function () {
            $query = $this->openLeadsQuery('leads')
                ->whereNotNull('leads.next_follow_up_at');

            $hasLeadCustomerJoin = $this->hasTable('customers')
                && $this->hasColumn('crm_leads', 'customer_id')
                && $this->hasColumn('customers', 'id');
            $hasLeadAssignedUserJoin = $this->hasTable('users')
                && $this->hasColumn('crm_leads', 'assigned_user_id')
                && $this->hasColumn('users', 'id');

            if ($hasLeadCustomerJoin) {
                $query->leftJoin('customers as lead_customers', 'lead_customers.id', '=', 'leads.customer_id');
            }
            if ($hasLeadAssignedUserJoin) {
                $query->leftJoin('users as lead_assigned_users', 'lead_assigned_users.id', '=', 'leads.assigned_user_id');
            }

            $query->select([
                $this->columnOrNull('crm_leads', 'id', 'leads.id', 'id'),
                $this->columnOrNull('crm_leads', 'customer_id', 'leads.customer_id', 'customer_id'),
                $this->columnOrNull('crm_leads', 'name', 'leads.name', 'name'),
                $this->columnOrNull('crm_leads', 'company_name', 'leads.company_name', 'company_name'),
                $this->columnOrNull('crm_leads', 'status', 'leads.status', 'status'),
                $this->columnOrNull('crm_leads', 'priority', 'leads.priority', 'priority'),
                $this->columnOrNull('crm_leads', 'next_follow_up_at', 'leads.next_follow_up_at', 'next_follow_up_at'),
                $this->joinedColumnOrNull($hasLeadCustomerJoin && $this->hasColumn('customers', 'name'), 'lead_customers.name', 'customer_name'),
                $this->joinedColumnOrNull($hasLeadAssignedUserJoin && $this->hasColumn('users', 'name'), 'lead_assigned_users.name', 'assigned_user_name'),
            ]);

            $query->orderByRaw('CASE WHEN leads.next_follow_up_at < ? THEN 0 ELSE 1 END', [$this->now])
                ->orderBy('leads.next_follow_up_at');
            if ($this->hasColumn('crm_leads', 'id')) {
                $query->orderByDesc('leads.id');
            }

            return $query->limit(8)->get()->map(fn ($lead) => [
                'id' => (int) ($lead->id ?? 0),
                'customer_id' => (int) ($lead->customer_id ?? 0),
                'name' => $lead->name ?? null,
                'company_name' => $lead->company_name ?? null,
                'status' => $lead->status ?? null,
                'priority' => $lead->priority ?? null,
                'customer_name' => $lead->customer_name ?? null,
                'assigned_user_name' => $lead->assigned_user_name ?? null,
                'next_follow_up_at' => $this->formatDateTime($lead->next_follow_up_at ?? null),
                'is_overdue' => !empty($lead->next_follow_up_at) && Carbon::parse($lead->next_follow_up_at)->lt($this->now),
            ])->values()->all();
        }, [], 'CRM lead follow-ups');
    }

    protected function recentCommunications(): array
    {
        if (!$this->hasTable('crm_communications')) {
            return [];
        }

        return $this->safe(function () {
            $query = DB::table('crm_communications as communications');

            $hasCommunicationCustomerJoin = $this->hasTable('customers')
                && $this->hasColumn('crm_communications', 'customer_id')
                && $this->hasColumn('customers', 'id');
            $hasCommunicationSenderJoin = $this->hasTable('users')
                && $this->hasColumn('crm_communications', 'sent_by')
                && $this->hasColumn('users', 'id');

            if ($hasCommunicationCustomerJoin) {
                $query->leftJoin('customers as communication_customers', 'communication_customers.id', '=', 'communications.customer_id');
            }
            if ($hasCommunicationSenderJoin) {
                $query->leftJoin('users as communication_senders', 'communication_senders.id', '=', 'communications.sent_by');
            }

            $query->select([
                $this->columnOrNull('crm_communications', 'id', 'communications.id', 'id'),
                $this->columnOrNull('crm_communications', 'customer_id', 'communications.customer_id', 'customer_id'),
                $this->columnOrNull('crm_communications', 'channel', 'communications.channel', 'channel'),
                $this->columnOrNull('crm_communications', 'direction', 'communications.direction', 'direction'),
                $this->columnOrNull('crm_communications', 'subject', 'communications.subject', 'subject'),
                $this->columnOrNull('crm_communications', 'message', 'communications.message', 'message'),
                $this->columnOrNull('crm_communications', 'status', 'communications.status', 'status'),
                $this->columnOrNull('crm_communications', 'source_module', 'communications.source_module', 'source_module'),
                $this->columnOrNull('crm_communications', 'sent_at', 'communications.sent_at', 'sent_at'),
                $this->columnOrNull('crm_communications', 'created_at', 'communications.created_at', 'created_at'),
                $this->joinedColumnOrNull($hasCommunicationCustomerJoin && $this->hasColumn('customers', 'name'), 'communication_customers.name', 'customer_name'),
                $this->joinedColumnOrNull($hasCommunicationSenderJoin && $this->hasColumn('users', 'name'), 'communication_senders.name', 'sender_name'),
            ]);

            $this->orderCommunicationLatest($query);

            return $query->limit(8)->get()->map(function ($communication) {
                $summary = trim((string) ($communication->subject ?? ''));
                if ($summary === '') {
                    $summary = trim((string) ($communication->message ?? ''));
                }

                return [
                    'id' => (int) ($communication->id ?? 0),
                    'customer_id' => (int) ($communication->customer_id ?? 0),
                    'customer_name' => $communication->customer_name ?? null,
                    'channel' => $communication->channel ?? null,
                    'direction' => $communication->direction ?? null,
                    'summary' => Str::limit($summary, 90),
                    'status' => $communication->status ?? null,
                    'source_module' => $communication->source_module ?? null,
                    'sender_name' => $communication->sender_name ?? null,
                    'activity_at' => $this->formatDateTime($communication->sent_at ?? $communication->created_at ?? null),
                ];
            })->values()->all();
        }, [], 'recent CRM communications');
    }

    protected function recentActivities(): array
    {
        if (!$this->hasTable('crm_activities')) {
            return [];
        }

        return $this->safe(function () {
            $query = DB::table('crm_activities as activities');

            $hasActivityCustomerJoin = $this->hasTable('customers')
                && $this->hasColumn('crm_activities', 'customer_id')
                && $this->hasColumn('customers', 'id');
            $hasActivityPerformerJoin = $this->hasTable('users')
                && $this->hasColumn('crm_activities', 'performed_by')
                && $this->hasColumn('users', 'id');

            if ($hasActivityCustomerJoin) {
                $query->leftJoin('customers as activity_customers', 'activity_customers.id', '=', 'activities.customer_id');
            }
            if ($hasActivityPerformerJoin) {
                $query->leftJoin('users as activity_performers', 'activity_performers.id', '=', 'activities.performed_by');
            }

            $query->select([
                $this->columnOrNull('crm_activities', 'id', 'activities.id', 'id'),
                $this->columnOrNull('crm_activities', 'customer_id', 'activities.customer_id', 'customer_id'),
                $this->columnOrNull('crm_activities', 'activity_type', 'activities.activity_type', 'activity_type'),
                $this->columnOrNull('crm_activities', 'subject', 'activities.subject', 'subject'),
                $this->columnOrNull('crm_activities', 'description', 'activities.description', 'description'),
                $this->columnOrNull('crm_activities', 'occurred_at', 'activities.occurred_at', 'occurred_at'),
                $this->columnOrNull('crm_activities', 'created_at', 'activities.created_at', 'created_at'),
                $this->joinedColumnOrNull($hasActivityCustomerJoin && $this->hasColumn('customers', 'name'), 'activity_customers.name', 'customer_name'),
                $this->joinedColumnOrNull($hasActivityPerformerJoin && $this->hasColumn('users', 'name'), 'activity_performers.name', 'performer_name'),
            ]);

            if ($this->hasColumn('crm_activities', 'occurred_at') && $this->hasColumn('crm_activities', 'created_at')) {
                $query->orderByRaw('COALESCE(activities.occurred_at, activities.created_at) DESC');
            } elseif ($this->hasColumn('crm_activities', 'occurred_at')) {
                $query->orderByDesc('activities.occurred_at');
            } elseif ($this->hasColumn('crm_activities', 'created_at')) {
                $query->orderByDesc('activities.created_at');
            }
            if ($this->hasColumn('crm_activities', 'id')) {
                $query->orderByDesc('activities.id');
            }

            return $query->limit(8)->get()->map(fn ($activity) => [
                'id' => (int) ($activity->id ?? 0),
                'customer_id' => (int) ($activity->customer_id ?? 0),
                'activity_type' => $activity->activity_type ?? null,
                'subject' => $activity->subject ?? null,
                'description' => Str::limit(trim((string) ($activity->description ?? '')), 110),
                'customer_name' => $activity->customer_name ?? null,
                'performer_name' => $activity->performer_name ?? null,
                'occurred_at' => $this->formatDateTime($activity->occurred_at ?? $activity->created_at ?? null),
            ])->values()->all();
        }, [], 'recent CRM activities');
    }

    protected function activeCustomersQuery(): Builder
    {
        $query = DB::table('customers');
        if ($this->hasColumn('customers', 'status')) {
            $query->where('status', 'active');
        }

        return $query;
    }

    protected function leadRecordsQuery(string $alias = ''): Builder
    {
        $table = $alias === '' ? 'crm_leads' : 'crm_leads as ' . $alias;
        $prefix = $alias === '' ? '' : $alias . '.';
        $query = DB::table($table);

        if ($this->hasColumn('crm_leads', 'deleted_at')) {
            $query->whereNull($prefix . 'deleted_at');
        }

        return $query;
    }

    protected function openLeadsQuery(string $alias = ''): Builder
    {
        $prefix = $alias === '' ? '' : $alias . '.';
        $query = $this->leadRecordsQuery($alias);

        if ($this->hasColumn('crm_leads', 'status')) {
            $query->where(function (Builder $query) use ($prefix) {
                $query->whereNull($prefix . 'status')
                    ->orWhereNotIn($prefix . 'status', ['converted', 'lost']);
            });
        }

        return $query;
    }

    protected function openTasksQuery(string $alias = ''): Builder
    {
        $table = $alias === '' ? 'crm_tasks' : 'crm_tasks as ' . $alias;
        $prefix = $alias === '' ? '' : $alias . '.';
        $query = DB::table($table)
            ->where(function (Builder $query) use ($prefix) {
                $query->whereNull($prefix . 'status')->orWhere($prefix . 'status', '<>', 'completed');
            });

        if ($this->hasColumn('crm_tasks', 'deleted_at')) {
            $query->whereNull($prefix . 'deleted_at');
        }

        return $query;
    }

    protected function orderLatest(Builder $query, string $table): void
    {
        if ($this->hasColumn($table, 'created_at')) {
            $query->orderByDesc('created_at');
        }
        if ($this->hasColumn($table, 'id')) {
            $query->orderByDesc('id');
        }
    }

    protected function orderCommunicationLatest(Builder $query): void
    {
        if ($this->hasColumn('crm_communications', 'sent_at') && $this->hasColumn('crm_communications', 'created_at')) {
            $query->orderByRaw('COALESCE(communications.sent_at, communications.created_at) DESC');
        } elseif ($this->hasColumn('crm_communications', 'sent_at')) {
            $query->orderByDesc('communications.sent_at');
        } elseif ($this->hasColumn('crm_communications', 'created_at')) {
            $query->orderByDesc('communications.created_at');
        }
        if ($this->hasColumn('crm_communications', 'id')) {
            $query->orderByDesc('communications.id');
        }
    }

    protected function selectColumns(string $table, array $columns): array
    {
        return collect($columns)
            ->filter(fn (string $column) => $this->hasColumn($table, $column))
            ->values()
            ->all();
    }

    protected function columnOrNull(string $table, string $column, string $qualifiedColumn, string $alias)
    {
        return $this->hasColumn($table, $column) ? DB::raw($qualifiedColumn . ' as ' . $alias) : DB::raw('NULL as ' . $alias);
    }

    protected function joinedColumnOrNull(bool $available, string $qualifiedColumn, string $alias)
    {
        return $available ? DB::raw($qualifiedColumn . ' as ' . $alias) : DB::raw('NULL as ' . $alias);
    }

    protected function formatDateTime($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('M d, Y h:i A');
        } catch (\Throwable $exception) {
            return null;
        }
    }

    protected function hasTable(string $table): bool
    {
        if (!array_key_exists($table, $this->tableCache)) {
            $this->tableCache[$table] = $this->safe(fn () => Schema::hasTable($table), false, 'schema table check: ' . $table);
        }

        return $this->tableCache[$table];
    }

    protected function hasColumn(string $table, string $column): bool
    {
        $key = $table . '.' . $column;
        if (!array_key_exists($key, $this->columnCache)) {
            $this->columnCache[$key] = $this->hasTable($table)
                && $this->safe(fn () => Schema::hasColumn($table, $column), false, 'schema column check: ' . $key);
        }

        return $this->columnCache[$key];
    }

    protected function safe(callable $callback, $fallback, string $context)
    {
        try {
            return $callback();
        } catch (\Throwable $exception) {
            Log::warning('CRM dashboard read skipped.', [
                'context' => $context,
                'error' => $exception->getMessage(),
            ]);

            return $fallback;
        }
    }
}
