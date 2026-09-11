@extends('backend.master')

@section('header_css')
    <style>
        .crm-dashboard .crm-hero,
        .crm-dashboard .crm-panel,
        .crm-dashboard .crm-metric-card {
            border: 0;
            border-radius: 12px;
            box-shadow: 0 4px 18px rgba(23, 38, 58, .08);
        }

        .crm-dashboard .crm-hero {
            background: linear-gradient(115deg, #0f766e, #155e75);
            color: #fff;
        }

        .crm-dashboard .crm-hero .text-muted {
            color: rgba(255, 255, 255, .78) !important;
        }

        .crm-dashboard .crm-metric-card {
            min-height: 122px;
        }

        .crm-dashboard .crm-metric-icon {
            align-items: center;
            background: rgba(15, 118, 110, .1);
            border-radius: 50%;
            color: #0f766e;
            display: flex;
            font-size: 18px;
            height: 42px;
            justify-content: center;
            width: 42px;
        }

        .crm-dashboard .crm-section-title {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 0;
        }

        .crm-dashboard .crm-empty {
            color: #7a8797;
            padding: 20px 10px;
            text-align: center;
        }

        .crm-dashboard .crm-badge {
            background: #eef4f6;
            border-radius: 10px;
            color: #3f5264;
            display: inline-block;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 7px;
            text-transform: capitalize;
        }

        .crm-dashboard .crm-table td,
        .crm-dashboard .crm-table th {
            vertical-align: middle;
        }

        .crm-dashboard .crm-table td {
            white-space: normal;
        }
    </style>
@endsection

@section('page_title')
    CRM Dashboard
@endsection

@section('page_heading')
    CRM Dashboard
@endsection

@section('content')
    @php
        $metrics = $dashboard['metrics'];
        $permissions = $dashboard['permissions'];
        $availability = $dashboard['availability'];
        $missingSections = collect($availability)->filter(fn ($available) => !$available)->keys();
    @endphp

    <div class="crm-dashboard">
        <div class="card crm-hero mb-4">
            <div class="card-body d-flex flex-wrap align-items-center justify-content-between">
                <div>
                    <h3 class="text-white mb-2">CRM Operational Overview</h3>
                    <p class="text-muted mb-0">Read-only customer, lead, task, follow-up, communication, and activity snapshot.</p>
                    <small class="text-muted">Generated: {{ $dashboard['generated_at'] }}</small>
                </div>
                <div class="mt-3 mt-md-0">
                    @if ($permissions['can_view_customers'])
                        <a href="{{ url('/view/all/customer') }}" class="btn btn-light btn-sm mr-1 mb-1">Customers</a>
                    @endif
                    @if ($permissions['can_view_customer_health'])
                        <a href="{{ route('crm.customer-health.index') }}" class="btn btn-light btn-sm mr-1 mb-1">Customer Health</a>
                    @endif
                    @if ($permissions['can_view_customer_segments'])
                        <a href="{{ route('crm.customer-segments.index') }}" class="btn btn-light btn-sm mr-1 mb-1">Portfolio Segments</a>
                    @endif
                    @if ($permissions['can_view_saved_customer_segments'])
                        <a href="{{ route('crm.saved-customer-segments.index') }}" class="btn btn-light btn-sm mr-1 mb-1">Saved Segments</a>
                    @endif
                    @if ($permissions['can_view_campaign_drafts'])
                        <a href="{{ route('crm.campaign-drafts.index') }}" class="btn btn-light btn-sm mr-1 mb-1">Campaign Drafts</a>
                    @endif
                    @if ($permissions['can_view_duplicate_customers'])
                        <a href="{{ route('crm.duplicate-customers.index') }}" class="btn btn-light btn-sm mr-1 mb-1">Duplicate Review</a>
                    @endif
                    @if ($permissions['can_view_tasks'])
                        <a href="{{ route('crm.tasks.index') }}" class="btn btn-light btn-sm mr-1 mb-1">Task Worklist</a>
                    @endif
                    @if ($permissions['can_view_task_calendar'])
                        <a href="{{ route('crm.tasks.calendar') }}" class="btn btn-light btn-sm mr-1 mb-1">Task Calendar</a>
                    @endif
                    @if ($permissions['can_view_leads'])
                        <a href="{{ route('crm.leads.index') }}" class="btn btn-light btn-sm mr-1 mb-1">Lead Worklist</a>
                        <a href="{{ route('crm.leads.pipeline') }}" class="btn btn-light btn-sm mr-1 mb-1">Lead Pipeline</a>
                    @endif
                    @if ($permissions['can_view_communications'])
                        <a href="{{ route('crm.communications.index') }}" class="btn btn-light btn-sm mr-1 mb-1">Communication History</a>
                    @endif
                    @if ($permissions['can_view_activities'])
                        <a href="{{ route('crm.activities.index') }}" class="btn btn-light btn-sm mb-1">Activity History</a>
                    @endif
                </div>
            </div>
        </div>

        @if ($missingSections->isNotEmpty())
            <div class="alert alert-warning">
                Some optional CRM dashboard sections are unavailable for the current database schema:
                <strong>{{ $missingSections->map(fn ($section) => str_replace('_', ' ', $section))->implode(', ') }}</strong>.
                Available sections continue to load safely.
            </div>
        @endif

        <div class="row">
            @foreach ([
                ['Active Customers', $metrics['active_customers'], 'feather-users'],
                ['Customers Added in 30 Days', $metrics['recently_added_customers'], 'feather-user-plus'],
                ['Open CRM Tasks', $metrics['open_tasks'], 'feather-check-square'],
                ['Overdue CRM Tasks', $metrics['overdue_tasks'], 'feather-alert-circle'],
                ['Tasks Due Today', $metrics['tasks_due_today'], 'feather-calendar'],
                ['Upcoming Follow-ups', $metrics['upcoming_follow_ups'], 'feather-clock'],
                ['Open CRM Leads', $metrics['open_leads'], 'feather-target'],
                ['Overdue Lead Follow-ups', $metrics['overdue_lead_follow_ups'], 'feather-alert-triangle'],
                ['Qualified CRM Leads', $metrics['qualified_leads'], 'feather-award'],
                ['Converted Leads in 30 Days', $metrics['converted_leads_30_days'], 'feather-user-check'],
                ['Communication Logs in 30 Days', $metrics['recent_communication_logs'], 'feather-message-circle'],
                ['Manual Communication Logs', $metrics['manual_communication_logs'], 'feather-edit-3'],
            ] as [$label, $value, $icon])
                <div class="col-xl-3 col-md-6">
                    <div class="card crm-metric-card mb-4">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <p class="text-muted mb-2">{{ $label }}</p>
                                <h3 class="mb-0">{{ number_format((int) $value) }}</h3>
                            </div>
                            <span class="crm-metric-icon"><i class="{{ $icon }}"></i></span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row">
            <div class="col-xl-4">
                <div class="card crm-panel mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h5 class="crm-section-title">Lead Status Summary</h5>
                            @if ($permissions['can_view_leads'])
                                <a href="{{ route('crm.leads.index') }}" class="btn btn-sm btn-outline-primary">View Leads</a>
                            @endif
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm crm-table mb-0">
                                <thead><tr><th>Status</th><th class="text-right">Leads</th></tr></thead>
                                <tbody>
                                @forelse ($dashboard['lead_status_summary'] as $row)
                                    <tr><td><span class="crm-badge">{{ $row['label'] }}</span></td><td class="text-right">{{ number_format($row['total']) }}</td></tr>
                                @empty
                                    <tr><td colspan="2" class="crm-empty">Lead summary is not available.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-8">
                <div class="card crm-panel mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h5 class="crm-section-title">Lead Follow-up Snapshot</h5>
                            @if ($permissions['can_view_leads'])
                                <a href="{{ route('crm.leads.index') }}" class="btn btn-sm btn-outline-primary">View Worklist</a>
                            @endif
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm crm-table mb-0">
                                <thead><tr><th>Lead</th><th>Customer</th><th>Status</th><th>Follow-up</th></tr></thead>
                                <tbody>
                                @forelse ($dashboard['lead_follow_ups'] as $lead)
                                    <tr>
                                        <td>
                                            <strong>{{ $lead['name'] ?: ('Lead #' . $lead['id']) }}</strong>
                                            @if ($lead['company_name'])<br><small class="text-muted">{{ $lead['company_name'] }}</small>@endif
                                        </td>
                                        <td>
                                            @if ($permissions['can_view_customer_profiles'] && $lead['customer_id'])
                                                <a href="{{ route('crm.customers.profile', ['customer' => $lead['customer_id']]) }}">{{ $lead['customer_name'] ?: ('Customer #' . $lead['customer_id']) }}</a>
                                            @else
                                                {{ $lead['customer_name'] ?: '—' }}
                                            @endif
                                        </td>
                                        <td><span class="crm-badge">{{ $lead['status'] ?: 'new' }}</span><br><small class="text-muted">{{ $lead['priority'] ?: 'normal' }}</small></td>
                                        <td>
                                            <span class="{{ $lead['is_overdue'] ? 'text-danger font-weight-bold' : '' }}">{{ $lead['next_follow_up_at'] ?: '—' }}</span>
                                            @if ($lead['assigned_user_name'])<br><small class="text-muted">{{ $lead['assigned_user_name'] }}</small>@endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="crm-empty">No CRM lead follow-up is available.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6">
                <div class="card crm-panel mb-4">
                    <div class="card-body">
                        <h5 class="crm-section-title mb-3">Lifecycle Stage Summary</h5>
                        <div class="table-responsive">
                            <table class="table table-sm crm-table mb-0">
                                <thead><tr><th>Lifecycle Stage</th><th class="text-right">Customers</th></tr></thead>
                                <tbody>
                                @forelse ($dashboard['lifecycle_summary'] as $row)
                                    <tr><td>{{ $row['label'] }}</td><td class="text-right">{{ number_format($row['total']) }}</td></tr>
                                @empty
                                    <tr><td colspan="2" class="crm-empty">Lifecycle summary is not available.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card crm-panel mb-4">
                    <div class="card-body">
                        <h5 class="crm-section-title mb-3">Credit Status Summary</h5>
                        <div class="table-responsive">
                            <table class="table table-sm crm-table mb-0">
                                <thead><tr><th>Credit Status</th><th class="text-right">Customers</th></tr></thead>
                                <tbody>
                                @forelse ($dashboard['credit_status_summary'] as $row)
                                    <tr><td>{{ $row['label'] }}</td><td class="text-right">{{ number_format($row['total']) }}</td></tr>
                                @empty
                                    <tr><td colspan="2" class="crm-empty">Credit summary is not available.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-7">
                <div class="card crm-panel mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h5 class="crm-section-title">Open CRM Tasks</h5>
                            @if ($permissions['can_view_tasks'])
                                <a href="{{ route('crm.tasks.index') }}" class="btn btn-sm btn-outline-primary">View Worklist</a>
                            @endif
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm crm-table mb-0">
                                <thead><tr><th>Task</th><th>Customer</th><th>Priority</th><th>Due</th></tr></thead>
                                <tbody>
                                @forelse ($dashboard['open_tasks'] as $task)
                                    <tr>
                                        <td>
                                            <strong>{{ $task['title'] ?: ('Task #' . $task['id']) }}</strong><br>
                                            <span class="crm-badge">{{ $task['status'] ?: 'pending' }}</span>
                                        </td>
                                        <td>
                                            @if ($permissions['can_view_customer_profiles'] && $task['customer_id'])
                                                <a href="{{ route('crm.customers.profile', ['customer' => $task['customer_id']]) }}">{{ $task['customer_name'] ?: ('Customer #' . $task['customer_id']) }}</a>
                                            @else
                                                {{ $task['customer_name'] ?: '—' }}
                                            @endif
                                        </td>
                                        <td><span class="crm-badge">{{ $task['priority'] ?: 'normal' }}</span></td>
                                        <td>
                                            <span class="{{ $task['is_overdue'] ? 'text-danger font-weight-bold' : '' }}">{{ $task['due_at'] ?: '—' }}</span>
                                            @if ($task['assigned_user_name'])<br><small class="text-muted">{{ $task['assigned_user_name'] }}</small>@endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="crm-empty">No open CRM task is available.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-5">
                <div class="card crm-panel mb-4">
                    <div class="card-body">
                        <h5 class="crm-section-title mb-3">Upcoming Customer Follow-ups</h5>
                        <div class="table-responsive">
                            <table class="table table-sm crm-table mb-0">
                                <thead><tr><th>Customer</th><th>Follow-up</th></tr></thead>
                                <tbody>
                                @forelse ($dashboard['upcoming_follow_ups'] as $customer)
                                    <tr>
                                        <td>
                                            @if ($permissions['can_view_customer_profiles'] && $customer['id'])
                                                <a href="{{ route('crm.customers.profile', ['customer' => $customer['id']]) }}">{{ $customer['name'] ?: ('Customer #' . $customer['id']) }}</a>
                                            @else
                                                {{ $customer['name'] ?: ('Customer #' . $customer['id']) }}
                                            @endif
                                            @if ($customer['phone'])<br><small class="text-muted">{{ $customer['phone'] }}</small>@endif
                                        </td>
                                        <td>{{ $customer['next_follow_up_at'] ?: '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="2" class="crm-empty">No upcoming customer follow-up is available.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-7">
                <div class="card crm-panel mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h5 class="crm-section-title">Recent Communications</h5>
                            @if ($permissions['can_view_communications'])
                                <a href="{{ route('crm.communications.index') }}" class="btn btn-sm btn-outline-primary">View History</a>
                            @endif
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm crm-table mb-0">
                                <thead><tr><th>Customer</th><th>Communication</th><th>Status</th><th>Logged</th></tr></thead>
                                <tbody>
                                @forelse ($dashboard['recent_communications'] as $communication)
                                    <tr>
                                        <td>
                                            @if ($permissions['can_view_customer_profiles'] && $communication['customer_id'])
                                                <a href="{{ route('crm.customers.profile', ['customer' => $communication['customer_id']]) }}">{{ $communication['customer_name'] ?: ('Customer #' . $communication['customer_id']) }}</a>
                                            @else
                                                {{ $communication['customer_name'] ?: '—' }}
                                            @endif
                                        </td>
                                        <td>
                                            <span class="crm-badge">{{ $communication['channel'] ?: 'unknown' }}</span>
                                            <span class="crm-badge">{{ $communication['direction'] ?: 'unknown' }}</span><br>
                                            <small>{{ $communication['summary'] ?: 'No summary' }}</small>
                                        </td>
                                        <td><span class="crm-badge">{{ $communication['status'] ?: 'unknown' }}</span></td>
                                        <td>{{ $communication['activity_at'] ?: '—' }}@if ($communication['sender_name'])<br><small class="text-muted">{{ $communication['sender_name'] }}</small>@endif</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="crm-empty">No communication history is available.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-5">
                <div class="card crm-panel mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h5 class="crm-section-title">Recent CRM Activity</h5>
                            @if ($permissions['can_view_activities'])
                                <a href="{{ route('crm.activities.index') }}" class="btn btn-sm btn-outline-primary">View History</a>
                            @endif
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm crm-table mb-0">
                                <tbody>
                                @forelse ($dashboard['recent_activities'] as $activity)
                                    <tr>
                                        <td>
                                            <strong>{{ $activity['subject'] ?: str_replace('_', ' ', ucfirst((string) $activity['activity_type'])) }}</strong>
                                            @if ($activity['customer_name'])<br><small class="text-muted">{{ $activity['customer_name'] }}</small>@endif
                                            @if ($activity['description'])<br><small>{{ $activity['description'] }}</small>@endif
                                        </td>
                                        <td class="text-right">
                                            <small>{{ $activity['occurred_at'] ?: '—' }}</small>
                                            @if ($activity['performer_name'])<br><small class="text-muted">{{ $activity['performer_name'] }}</small>@endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td class="crm-empty">No CRM activity is available.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-8">
                <div class="card crm-panel mb-4">
                    <div class="card-body">
                        <h5 class="crm-section-title mb-3">Recently Added Customers</h5>
                        <div class="table-responsive">
                            <table class="table table-sm crm-table mb-0">
                                <thead><tr><th>Customer</th><th>Contact</th><th>Lifecycle</th><th>Credit</th><th>Added</th></tr></thead>
                                <tbody>
                                @forelse ($dashboard['recent_customers'] as $customer)
                                    <tr>
                                        <td>
                                            @if ($permissions['can_view_customer_profiles'] && $customer['id'])
                                                <a href="{{ route('crm.customers.profile', ['customer' => $customer['id']]) }}">{{ $customer['name'] ?: ('Customer #' . $customer['id']) }}</a>
                                            @else
                                                {{ $customer['name'] ?: ('Customer #' . $customer['id']) }}
                                            @endif
                                        </td>
                                        <td>{{ $customer['phone'] ?: '—' }}@if ($customer['email'])<br><small class="text-muted">{{ $customer['email'] }}</small>@endif</td>
                                        <td><span class="crm-badge">{{ $customer['lifecycle_stage'] ?: 'unspecified' }}</span></td>
                                        <td><span class="crm-badge">{{ $customer['credit_status'] ?: 'unspecified' }}</span></td>
                                        <td>{{ $customer['created_at'] ?: '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="crm-empty">No recent customer is available.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card crm-panel mb-4">
                    <div class="card-body">
                        <h5 class="crm-section-title mb-3">Legacy Contact Snapshot</h5>
                        <p class="text-muted">Existing contact-history and scheduled-contact dashboard semantics are preserved.</p>
                        <div class="d-flex justify-content-between py-2 border-bottom"><span>Contact Histories</span><strong>{{ number_format($metrics['legacy_contact_histories']) }}</strong></div>
                        <div class="d-flex justify-content-between py-2 border-bottom"><span>Scheduled Contacts</span><strong>{{ number_format($metrics['legacy_scheduled_contacts']) }}</strong></div>
                        <div class="d-flex justify-content-between py-2 border-bottom"><span>Upcoming Contacts</span><strong>{{ number_format($metrics['legacy_upcoming_contacts']) }}</strong></div>
                        <div class="d-flex justify-content-between py-2 border-bottom"><span>Pending Contacts</span><strong>{{ number_format($metrics['legacy_pending_contacts']) }}</strong></div>
                        <div class="d-flex justify-content-between py-2 border-bottom"><span>Missed Contacts</span><strong>{{ number_format($metrics['legacy_missed_contacts']) }}</strong></div>
                        <div class="d-flex justify-content-between py-2"><span>Completed Contacts</span><strong>{{ number_format($metrics['legacy_completed_contacts']) }}</strong></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
