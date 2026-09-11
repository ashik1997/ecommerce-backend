@extends('backend.master')

@section('header_css')
    <link href="{{ url('assets') }}/plugins/select2/select2.min.css" rel="stylesheet" type="text/css" />
    <style>
        .crm360-page { --crm-teal:#0f766e; --crm-soft:#f0fdfa; --crm-border:#d7eeee; --crm-muted:#64748b; }
        .crm360-hero { border:1px solid var(--crm-border); border-radius:16px; background:linear-gradient(135deg,#ffffff,#f0fdfa); box-shadow:0 10px 28px rgba(15,118,110,.08); }
        .crm360-avatar { width:64px; height:64px; border-radius:18px; display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg,#0f766e,#14b8a6); color:#fff; font-size:24px; font-weight:800; }
        .crm360-card { border:1px solid var(--crm-border); border-radius:14px; background:#fff; box-shadow:0 6px 18px rgba(15,118,110,.05); height:100%; }
        .crm360-stat-label { color:var(--crm-muted); font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; }
        .crm360-stat-value { color:#0f172a; font-size:20px; font-weight:800; margin-top:4px; }
        .crm360-nav .nav-link { border-radius:999px; margin:0 6px 8px 0; color:#475569; font-weight:700; border:1px solid #e2e8f0; }
        .crm360-nav .nav-link.active { background:var(--crm-teal); border-color:var(--crm-teal); color:#fff; }
        .crm360-tag { display:inline-flex; align-items:center; gap:6px; border-radius:999px; padding:5px 10px; margin:0 6px 6px 0; background:#f8fafc; border:1px solid #e2e8f0; font-size:12px; font-weight:700; }
        .crm360-tag-dot { width:9px; height:9px; border-radius:50%; background:#94a3b8; display:inline-block; }
        .crm360-note { border:1px solid #e2e8f0; border-radius:12px; padding:12px; margin-bottom:10px; background:#fff; }
        .crm360-note-private { border-left:4px solid #f59e0b; }
        .crm360-timeline { border-left:2px solid #ccfbf1; margin-left:7px; padding-left:18px; }
        .crm360-timeline-item { position:relative; padding:0 0 16px; }
        .crm360-timeline-item:before { content:''; width:11px; height:11px; border-radius:50%; background:#14b8a6; position:absolute; left:-24px; top:5px; }
        .crm360-empty { border:1px dashed #cbd5e1; border-radius:12px; padding:18px; color:#64748b; background:#f8fafc; text-align:center; }
        .crm360-table th { font-size:12px; text-transform:uppercase; color:#64748b; letter-spacing:.03em; white-space:nowrap; }
        .crm360-table td { vertical-align:middle; }
        .crm360-loading { padding:24px; text-align:center; color:#64748b; }
        .select2-container { width:100% !important; }
        .select2-container--default .select2-selection--multiple { min-height:40px; border-color:#d1d5db; }
        .crm360-meta { color:#64748b; font-size:12px; }
        .crm360-section-title { color:#0f766e; font-weight:800; }
        .crm360-detail-label { color:#64748b; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.03em; }
        .crm360-detail-value { white-space:pre-wrap; word-break:break-word; }
    </style>
@endsection

@section('content')
    @php
        $customer = $profile['customer'];
        $summary = $profile['summary'];
        $sections = $profile['sections'];
        $initial = trim((string) ($customer['name'] ?? 'C')) !== '' ? mb_strtoupper(mb_substr($customer['name'], 0, 1)) : 'C';
        $show = fn($value, $fallback = 'Unavailable') => $value !== null && $value !== '' ? $value : $fallback;
    @endphp

    <div class="container-fluid crm360-page">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0 font-size-18">Customer 360 Profile</h4>
                    <a href="{{ route('ViewAllCustomer') }}" class="btn btn-sm btn-outline-secondary"><i class="feather-arrow-left"></i> Customer list</a>
                </div>
            </div>
        </div>

        <div class="crm360-hero p-3 p-md-4 mb-3">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between">
                <div class="d-flex align-items-center mb-3 mb-md-0">
                    <div class="crm360-avatar mr-3">{{ $initial }}</div>
                    <div>
                        <div class="d-flex flex-wrap align-items-center">
                            <h3 class="mb-1 mr-2">{{ $show($customer['name'], 'Unnamed customer') }}</h3>
                            <span class="badge {{ ($customer['status'] ?? null) === 'active' ? 'badge-success' : 'badge-secondary' }}">
                                {{ ucfirst($customer['status'] ?? 'unknown') }}
                            </span>
                        </div>
                        <div class="text-muted">
                            <span class="mr-3"><i class="feather-hash"></i> {{ $show($customer['customer_code'], 'No customer code') }}</span>
                            <span class="mr-3"><i class="feather-phone"></i> {{ $show($customer['phone']) }}</span>
                            <span><i class="feather-mail"></i> {{ $show($customer['email']) }}</span>
                        </div>
                    </div>
                </div>
                <div class="d-flex flex-wrap">
                    @foreach ($profile['quick_links'] as $link)
                        <a href="{{ $link['url'] }}" class="btn btn-sm btn-outline-primary mr-2 mb-2">
                            <i class="{{ $link['icon'] }}"></i> {{ $link['label'] }}
                        </a>
                    @endforeach
                    @if ($permissions['can_view_customer_health'])
                        <a href="{{ route('crm.customer-health.index', ['customer_id' => $customer['id']]) }}" class="btn btn-sm btn-outline-primary mr-2 mb-2">
                            <i class="feather-heart"></i> Health worklist
                        </a>
                    @endif
                    @if ($permissions['can_view_customer_segments'])
                        <a href="{{ route('crm.customer-segments.index', ['customer_id' => $customer['id']]) }}" class="btn btn-sm btn-outline-primary mr-2 mb-2">
                            <i class="feather-filter"></i> Portfolio segments
                        </a>
                    @endif
                    @if ($permissions['can_view_duplicate_customers'])
                        <a href="{{ route('crm.duplicate-customers.index', ['customer_id' => $customer['id']]) }}" class="btn btn-sm btn-outline-primary mr-2 mb-2">
                            <i class="feather-copy"></i> Duplicate review
                        </a>
                    @endif
                    @if ($permissions['can_view_leads'])
                        <a href="{{ route('crm.leads.index', ['customer_id' => $customer['id']]) }}" class="btn btn-sm btn-outline-primary mr-2 mb-2">
                            <i class="feather-target"></i> Customer leads
                        </a>
                    @endif
                    @if ($permissions['can_create_lead'])
                        <a href="{{ route('crm.leads.index', ['create' => 1, 'customer_id' => $customer['id']]) }}" class="btn btn-sm btn-outline-primary mr-2 mb-2">
                            <i class="feather-plus-circle"></i> Add lead
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="row mb-3">
            @foreach ([
                ['Total order value', $summary['total_order_value'], 'feather-shopping-cart'],
                ['Total paid', $summary['total_paid'], 'feather-check-circle'],
                ['Current due', $summary['current_due'], 'feather-alert-circle'],
                ['Advance / balance', $summary['advance_balance'], 'feather-credit-card'],
                ['Overdue amount', $summary['overdue_amount'], 'feather-clock'],
                ['Total orders', $summary['total_orders_count'], 'feather-package'],
            ] as [$label, $value, $icon])
                <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                    <div class="crm360-card p-3">
                        <div class="crm360-stat-label"><i class="{{ $icon }}"></i> {{ $label }}</div>
                        <div class="crm360-stat-value">
                            @if ($label === 'Total orders')
                                {{ $value !== null ? $value : 'Unavailable' }}
                            @else
                                {{ $value !== null ? '৳ ' . $value : 'Unavailable' }}
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="card crm360-card mb-3">
            <div class="card-body">
                <ul class="nav nav-pills crm360-nav" role="tablist">
                    <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#crm360-overview" role="tab">Overview</a></li>
                    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#crm360-tags" role="tab">CRM Tags</a></li>
                    @if ($sections['notes'])<li class="nav-item"><a class="nav-link js-lazy-tab" data-section="notes" data-toggle="tab" href="#crm360-notes" role="tab">CRM Notes</a></li>@endif
                    @if ($sections['tasks'] && $permissions['can_view_tasks'])<li class="nav-item"><a class="nav-link js-lazy-tab" data-section="tasks" data-toggle="tab" href="#crm360-tasks" role="tab">CRM Tasks</a></li>@endif
                    @if ($sections['leads'] && $permissions['can_view_leads'])<li class="nav-item"><a class="nav-link js-lazy-tab" data-section="leads" data-toggle="tab" href="#crm360-leads" role="tab">CRM Leads</a></li>@endif
                    @if ($sections['activities'])<li class="nav-item"><a class="nav-link js-lazy-tab" data-section="activities" data-toggle="tab" href="#crm360-activities" role="tab">Activity Timeline</a></li>@endif
                    @if ($sections['communications'])<li class="nav-item"><a class="nav-link js-lazy-tab" data-section="communications" data-toggle="tab" href="#crm360-communications" role="tab">Communications</a></li>@endif
                    @if ($sections['orders'])<li class="nav-item"><a class="nav-link js-lazy-tab" data-section="orders" data-toggle="tab" href="#crm360-orders" role="tab">Orders</a></li>@endif
                    @if ($sections['contact_histories'])<li class="nav-item"><a class="nav-link js-lazy-tab" data-section="contactHistories" data-toggle="tab" href="#crm360-contact-histories" role="tab">Contact History</a></li>@endif
                    @if ($sections['scheduled_contacts'])<li class="nav-item"><a class="nav-link js-lazy-tab" data-section="scheduledContacts" data-toggle="tab" href="#crm360-scheduled-contacts" role="tab">Scheduled Contacts</a></li>@endif
                    @if ($sections['quotations'])<li class="nav-item"><a class="nav-link js-lazy-tab" data-section="quotations" data-toggle="tab" href="#crm360-quotations" role="tab">Quotations</a></li>@endif
                    @if ($sections['returns_refunds'])<li class="nav-item"><a class="nav-link js-lazy-tab" data-section="returnsRefunds" data-toggle="tab" href="#crm360-returns-refunds" role="tab">Returns & Refunds</a></li>@endif
                </ul>

                <div class="tab-content pt-3">
                    <div class="tab-pane active" id="crm360-overview" role="tabpanel">
                        <div class="row">
                            <div class="col-lg-6 mb-3">
                                <h5 class="crm360-section-title">Customer information</h5>
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0">
                                        <tbody>
                                            <tr><th>Category</th><td>{{ $show($customer['category']) }}</td></tr>
                                            <tr><th>Source type</th><td>{{ $show($customer['source_type']) }}</td></tr>
                                            <tr><th>Lifecycle stage</th><td>{{ $show($customer['lifecycle_stage']) }}</td></tr>
                                            <tr><th>Assigned user</th><td>{{ $show($customer['assigned_user']) }}</td></tr>
                                            <tr><th>Address</th><td>{{ $show($customer['address']) }}</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-lg-6 mb-3">
                                <h5 class="crm360-section-title">CRM snapshot</h5>
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0">
                                        <tbody>
                                            <tr><th>Credit status</th><td>{{ $show($summary['credit_status']) }}</td></tr>
                                            <tr><th>Credit limit</th><td>{{ $customer['credit_limit'] !== null ? '৳ ' . $customer['credit_limit'] : 'Unavailable' }}</td></tr>
                                            <tr><th>Payment terms</th><td>{{ $customer['payment_terms_days'] !== null ? $customer['payment_terms_days'] . ' day(s)' : 'Unavailable' }}</td></tr>
                                            <tr><th>Last order</th><td>{{ $show($summary['last_order_at']) }}</td></tr>
                                            <tr><th>Last contact</th><td>{{ $show($summary['last_contact_at']) }}</td></tr>
                                            <tr><th>Next follow-up</th><td>{{ $show($summary['next_follow_up_at']) }}</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                                <p class="crm360-meta mt-2 mb-0">Accounting values reuse the established customer snapshot. This profile does not recalculate payment, due, advance, ledger, return, or refund logic.</p>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane" id="crm360-tags" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="crm360-section-title mb-0">Assigned CRM tags</h5>
                        </div>
                        <div id="crm360AssignedTags" class="mb-3"></div>
                        @if ($permissions['can_manage_tags'])
                            <div class="border rounded p-3">
                                <label for="crmTagSelect" class="font-weight-bold">Update assigned tags</label>
                                <select id="crmTagSelect" class="form-control" multiple></select>
                                <div id="crmTagError" class="invalid-feedback d-block"></div>
                                <small class="form-text text-muted">Only active tags can be newly selected. Existing inactive assignments remain visible and may be preserved.</small>
                                <button type="button" id="saveCrmTags" class="btn btn-primary mt-3"><i class="feather-save"></i> Save tags</button>
                            </div>
                        @else
                            <div class="alert alert-light border mb-0">Tag assignment is read-only for your role.</div>
                        @endif
                    </div>

                    @if ($sections['notes'])
                        <div class="tab-pane" id="crm360-notes" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="crm360-section-title mb-0">CRM notes</h5>
                                @if ($permissions['can_create_note'])
                                    <button type="button" id="openCreateCrmNote" class="btn btn-sm btn-primary"><i class="feather-plus"></i> Add note</button>
                                @endif
                            </div>
                            <div id="crm360Notes"><div class="crm360-loading">Open this tab to load notes.</div></div>
                        </div>
                    @endif

                    @if ($sections['tasks'] && $permissions['can_view_tasks'])
                        <div class="tab-pane" id="crm360-tasks" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="crm360-section-title mb-0">CRM tasks and follow-ups</h5>
                                <div>
                                    @if ($permissions['can_create_task'])
                                        <a href="{{ route('crm.tasks.index', ['create' => 1, 'customer_id' => $customer['id']]) }}" class="btn btn-sm btn-primary mr-1"><i class="feather-plus"></i> Add task</a>
                                    @endif
                                    @if ($permissions['can_view_task_calendar'])
                                        <a href="{{ route('crm.tasks.calendar', ['customer_id' => $customer['id']]) }}" class="btn btn-sm btn-outline-primary mr-1"><i class="feather-calendar"></i> Calendar</a>
                                    @endif
                                    <a href="{{ route('crm.tasks.index', ['customer_id' => $customer['id']]) }}" class="btn btn-sm btn-outline-secondary"><i class="feather-list"></i> Worklist</a>
                                </div>
                            </div>
                            <div id="crm360Tasks"><div class="crm360-loading">Open this tab to load CRM tasks.</div></div>
                        </div>
                    @endif

                    @if ($sections['leads'] && $permissions['can_view_leads'])
                        <div class="tab-pane" id="crm360-leads" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="crm360-section-title mb-0">CRM leads linked to this customer</h5>
                                <div>
                                    @if ($permissions['can_create_lead'])
                                        <a href="{{ route('crm.leads.index', ['create' => 1, 'customer_id' => $customer['id']]) }}" class="btn btn-sm btn-primary mr-1"><i class="feather-plus"></i> Add lead</a>
                                    @endif
                                    <a href="{{ route('crm.leads.index', ['customer_id' => $customer['id']]) }}" class="btn btn-sm btn-outline-secondary"><i class="feather-list"></i> Worklist</a>
                                </div>
                            </div>
                            <div id="crm360Leads"><div class="crm360-loading">Open this tab to load CRM leads.</div></div>
                        </div>
                    @endif

                    @if ($sections['activities'])
                        <div class="tab-pane" id="crm360-activities" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="crm360-section-title mb-0">CRM activity timeline</h5>
                                @if ($permissions['can_view_activity_worklist'])
                                    <a href="{{ route('crm.activities.index', ['customer_id' => $customer['id']]) }}" class="btn btn-sm btn-outline-secondary"><i class="feather-list"></i> Audit worklist</a>
                                @endif
                            </div>
                            <div id="crm360Activities"><div class="crm360-loading">Open this tab to load activities.</div></div>
                        </div>
                    @endif

                    @if ($sections['communications'])
                        <div class="tab-pane" id="crm360-communications" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="crm360-section-title mb-0">CRM communication history</h5>
                                @if ($permissions['can_view_communications_worklist'])
                                    <div>
                                        @if ($permissions['can_create_communication'])
                                            <a href="{{ route('crm.communications.index', ['create' => 1, 'customer_id' => $customer['id']]) }}" class="btn btn-sm btn-primary mr-1"><i class="feather-plus"></i> Log communication</a>
                                        @endif
                                        <a href="{{ route('crm.communications.index', ['customer_id' => $customer['id']]) }}" class="btn btn-sm btn-outline-secondary"><i class="feather-list"></i> Worklist</a>
                                    </div>
                                @endif
                            </div>
                            <div id="crm360Communications"><div class="crm360-loading">Open this tab to load communications.</div></div>
                        </div>
                    @endif

                    @if ($sections['orders'])
                        <div class="tab-pane" id="crm360-orders" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center"><h5 class="crm360-section-title">Order summary</h5></div>
                            <div id="crm360Orders"><div class="crm360-loading">Open this tab to load orders.</div></div>
                        </div>
                    @endif

                    @if ($sections['contact_histories'])
                        <div class="tab-pane" id="crm360-contact-histories" role="tabpanel">
                            <h5 class="crm360-section-title">Existing contact history</h5>
                            <div id="crm360ContactHistories"><div class="crm360-loading">Open this tab to load contact history.</div></div>
                        </div>
                    @endif

                    @if ($sections['scheduled_contacts'])
                        <div class="tab-pane" id="crm360-scheduled-contacts" role="tabpanel">
                            <h5 class="crm360-section-title">Existing scheduled contacts</h5>
                            <div id="crm360ScheduledContacts"><div class="crm360-loading">Open this tab to load scheduled contacts.</div></div>
                        </div>
                    @endif

                    @if ($sections['quotations'])
                        <div class="tab-pane" id="crm360-quotations" role="tabpanel">
                            <h5 class="crm360-section-title">Quotation summary</h5>
                            <div id="crm360Quotations"><div class="crm360-loading">Open this tab to load quotations.</div></div>
                        </div>
                    @endif

                    @if ($sections['returns_refunds'])
                        <div class="tab-pane" id="crm360-returns-refunds" role="tabpanel">
                            <h5 class="crm360-section-title">Returns and refunds summary</h5>
                            <div id="crm360ReturnsRefunds"><div class="crm360-loading">Open this tab to load returns and refunds.</div></div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="crmCustomerNoteModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="crmCustomerNoteForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="crmCustomerNoteModalTitle">Add CRM note</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="crmNoteId">
                        <div class="form-group">
                            <label for="crmNoteText">Note <span class="text-danger">*</span></label>
                            <textarea id="crmNoteText" class="form-control" rows="6" maxlength="5000"></textarea>
                            <div id="crmNoteTextError" class="invalid-feedback"></div>
                        </div>
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="crmNotePrivate">
                            <label class="custom-control-label" for="crmNotePrivate">Private CRM note</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                        <button type="submit" id="saveCrmNote" class="btn btn-primary"><i class="feather-save"></i> Save note</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="crm360CommunicationDetailsModal" tabindex="-1" role="dialog" aria-labelledby="crm360CommunicationDetailsModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="crm360CommunicationDetailsModalTitle">Communication Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body" id="crm360CommunicationDetailsBody">
                    <div class="text-center text-muted py-4">Select a communication to view details.</div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Close</button></div>
            </div>
        </div>
    </div>
@endsection

@section('footer_js')
    <script src="{{ url('assets') }}/plugins/select2/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @php
        $crmCustomerProfileUrls = [
            'notes' => route('crm.customers.profile.notes.index', ['customer' => $customer['id']]),
            'noteStore' => route('crm.customers.profile.notes.store', ['customer' => $customer['id']]),
            'noteUpdate' => route('crm.customers.profile.notes.update', ['customer' => $customer['id'], 'note' => '__NOTE__']),
            'noteArchive' => route('crm.customers.profile.notes.archive', ['customer' => $customer['id'], 'note' => '__NOTE__']),
            'activities' => route('crm.customers.profile.activities', ['customer' => $customer['id']]),
            'communications' => route('crm.customers.profile.communications', ['customer' => $customer['id']]),
            'leads' => route('crm.customers.profile.leads', ['customer' => $customer['id']]),
            'communicationShow' => $permissions['can_view_communications_worklist'] ? route('crm.communications.show', ['communication' => '__COMMUNICATION__']) : null,
            'tasks' => route('crm.customers.profile.tasks', ['customer' => $customer['id']]),
            'taskComplete' => route('crm.tasks.complete', ['task' => '__TASK__']),
            'taskArchive' => route('crm.tasks.archive', ['task' => '__TASK__']),
            'orders' => route('crm.customers.profile.orders', ['customer' => $customer['id']]),
            'quotations' => route('crm.customers.profile.quotations', ['customer' => $customer['id']]),
            'contactHistories' => route('crm.customers.profile.contact-histories', ['customer' => $customer['id']]),
            'scheduledContacts' => route('crm.customers.profile.scheduled-contacts', ['customer' => $customer['id']]),
            'returnsRefunds' => route('crm.customers.profile.returns-refunds', ['customer' => $customer['id']]),
            'tagOptions' => $permissions['can_manage_tags'] ? route('crm.settings.customer-tags.active-options') : null,
            'tagSync' => $permissions['can_manage_tags'] ? route('crm.customers.tags.sync', ['customer' => $customer['id']]) : null,
        ];
    @endphp
    <script>
        (function ($) {
            'use strict';

            const initialTags = @json($profile['tags']);
            const canManageTags = @json($permissions['can_manage_tags']);
            const urls = @json($crmCustomerProfileUrls);
            const containers = {
                notes: '#crm360Notes', activities: '#crm360Activities', communications: '#crm360Communications', tasks: '#crm360Tasks', leads: '#crm360Leads',
                orders: '#crm360Orders', quotations: '#crm360Quotations', contactHistories: '#crm360ContactHistories',
                scheduledContacts: '#crm360ScheduledContacts', returnsRefunds: '#crm360ReturnsRefunds'
            };
            const loaded = {};
            let noteCache = {};
            let noteSubmitting = false;
            let tagSubmitting = false;
            const taskCompletionRequests = new Set();
            const taskArchiveRequests = new Set();
            let communicationDetailsLoading = false;

            function client() { return window.AppAxios || null; }
            function endpoint(template, id) { return String(template || '').replace('__NOTE__', String(id)).replace('__TASK__', String(id)).replace('__COMMUNICATION__', String(id)); }
            function esc(value) {
                return $('<div>').text(value === null || value === undefined || value === '' ? '—' : String(value)).html();
            }
            function attr(value) { return esc(value).replace(/`/g, '&#96;'); }
            function badge(value) { return '<span class="badge badge-light border">' + esc(value) + '</span>'; }
            function empty(message) { return '<div class="crm360-empty">' + esc(message || 'No records found.') + '</div>'; }
            function loading() { return '<div class="crm360-loading"><span class="spinner-border spinner-border-sm mr-2"></span>Loading...</div>'; }
            function failure(message) { return '<div class="alert alert-danger mb-0">' + esc(message || 'Unable to load this section.') + '</div>'; }
            function notify(title, text, icon) { Swal.fire({title: title, text: text, icon: icon, confirmButtonColor: '#0f766e'}); }
            function linked(value, url) { return url ? '<a href="' + attr(url) + '">' + esc(value) + '</a>' : esc(value); }
            function communicationDetailsAction(row) { return urls.communicationShow ? '<button type="button" class="btn btn-xs btn-outline-primary js-profile-view-communication" data-id="' + Number(row.id) + '"><i class="feather-eye"></i></button>' : '<span class="text-muted">—</span>'; }
            function pager(section, meta) {
                if (!meta || Number(meta.last_page || 1) <= 1) return '';
                const current = Number(meta.current_page || 1);
                const last = Number(meta.last_page || 1);
                return '<div class="d-flex justify-content-between align-items-center mt-2">' +
                    '<small class="text-muted">Page ' + current + ' of ' + last + ' · ' + Number(meta.total || 0) + ' record(s)</small>' +
                    '<div>' +
                    '<button class="btn btn-sm btn-outline-secondary js-profile-page mr-1" data-section="' + attr(section) + '" data-page="' + (current - 1) + '" ' + (current <= 1 ? 'disabled' : '') + '>Previous</button>' +
                    '<button class="btn btn-sm btn-outline-secondary js-profile-page" data-section="' + attr(section) + '" data-page="' + (current + 1) + '" ' + (current >= last ? 'disabled' : '') + '>Next</button>' +
                    '</div></div>';
            }
            function table(headers, rows) {
                if (!rows.length) return '';
                let html = '<div class="table-responsive"><table class="table table-sm table-hover crm360-table"><thead><tr>';
                headers.forEach(function (header) { html += '<th>' + esc(header.label) + '</th>'; });
                html += '</tr></thead><tbody>';
                rows.forEach(function (row) {
                    html += '<tr>';
                    headers.forEach(function (header) { html += '<td>' + header.render(row) + '</td>'; });
                    html += '</tr>';
                });
                return html + '</tbody></table></div>';
            }

            function renderNotes(payload) {
                noteCache = {};
                const rows = payload.data || [];
                if (!rows.length) return empty(payload.message || 'No CRM notes found.') + pager('notes', payload.meta);
                let html = '';
                rows.forEach(function (note) {
                    noteCache[String(note.id)] = note;
                    html += '<div class="crm360-note ' + (note.is_private ? 'crm360-note-private' : '') + '">' +
                        '<div class="d-flex justify-content-between"><div>' + (note.is_private ? badge('Private') : badge('Shared')) +
                        ' <small class="text-muted">by ' + esc(note.creator || 'Unknown') + ' · ' + esc(note.created_at) + '</small></div><div>' +
                        (note.can_edit ? '<button type="button" class="btn btn-xs btn-outline-primary js-edit-note mr-1" data-id="' + Number(note.id) + '"><i class="feather-edit-2"></i></button>' : '') +
                        (note.can_archive ? '<button type="button" class="btn btn-xs btn-outline-danger js-archive-note" data-id="' + Number(note.id) + '"><i class="feather-archive"></i></button>' : '') +
                        '</div></div><div class="mt-2" style="white-space:pre-wrap">' + esc(note.note) + '</div></div>';
                });
                return html + pager('notes', payload.meta);
            }
            function renderActivities(payload) {
                const rows = payload.data || [];
                if (!rows.length) return empty(payload.message || 'No customer-specific CRM activities found.') + pager('activities', payload.meta);
                let html = '<div class="crm360-timeline">';
                rows.forEach(function (row) {
                    html += '<div class="crm360-timeline-item"><div class="font-weight-bold">' + esc(row.subject || row.activity_type) + '</div>' +
                        '<div class="small text-muted">' + esc(row.activity_type) + ' · ' + esc(row.actor || 'System') + ' · ' + esc(row.occurred_at) + '</div>' +
                        (row.description ? '<div class="mt-1">' + esc(row.description) + '</div>' : '') + '</div>';
                });
                return html + '</div>' + pager('activities', payload.meta);
            }
            function renderCommunications(payload) {
                const rows = payload.data || [];
                if (!rows.length) return empty(payload.message || 'No communication records found.') + pager('communications', payload.meta);
                return table([
                    {label:'When', render:r => esc(r.sent_at)}, {label:'Channel', render:r => badge(r.channel)},
                    {label:'Direction', render:r => esc(r.direction)}, {label:'Status', render:r => esc(r.status)},
                    {label:'Summary', render:r => esc(r.subject || r.message_summary)}, {label:'Source', render:r => esc(r.source_module)},
                    {label:'Actor', render:r => esc(r.actor)}, {label:'Details', render:r => communicationDetailsAction(r)}
                ], rows) + pager('communications', payload.meta);
            }
            function renderOrders(payload) {
                const rows = payload.data || [];
                if (!rows.length) return empty(payload.message || 'No orders found.') + pager('orders', payload.meta);
                return table([
                    {label:'Order', render:r => linked(r.code, r.url)}, {label:'Date', render:r => esc(r.date)},
                    {label:'Total', render:r => esc(r.total)}, {label:'Paid', render:r => esc(r.paid)},
                    {label:'Due', render:r => esc(r.due)}, {label:'Status', render:r => badge(r.status)}
                ], rows) + pager('orders', payload.meta);
            }
            function renderQuotations(payload) {
                const rows = payload.data || [];
                if (!rows.length) return empty(payload.message || 'No quotations found.') + pager('quotations', payload.meta);
                return table([
                    {label:'Quotation', render:r => linked(r.code, r.url)}, {label:'Date', render:r => esc(r.date)},
                    {label:'Valid until', render:r => esc(r.valid_until)}, {label:'Total', render:r => esc(r.total)},
                    {label:'Status', render:r => badge(r.status)}
                ], rows) + pager('quotations', payload.meta);
            }
            function renderContactHistories(payload) {
                const rows = payload.data || [];
                if (!rows.length) return empty(payload.message || 'No contact history found.') + pager('contactHistories', payload.meta);
                return table([
                    {label:'Date', render:r => esc(r.date)}, {label:'Subject', render:r => linked(r.subject, r.url)},
                    {label:'Note', render:r => esc(r.note)}, {label:'Priority', render:r => badge(r.priority)},
                    {label:'Status', render:r => badge(r.status)}
                ], rows) + pager('contactHistories', payload.meta);
            }
            function renderScheduledContacts(payload) {
                const rows = payload.data || [];
                if (!rows.length) return empty(payload.message || 'No scheduled contacts found.') + pager('scheduledContacts', payload.meta);
                return table([
                    {label:'Next date', render:r => linked(r.date, r.url)}, {label:'Contact status', render:r => badge(r.contact_status)},
                    {label:'Record status', render:r => badge(r.status)}
                ], rows) + pager('scheduledContacts', payload.meta);
            }
            function renderTasks(payload) {
                const rows = payload.data || [];
                if (!rows.length) return empty(payload.message || 'No CRM tasks found for this customer.') + pager('tasks', payload.meta);
                return table([
                    {label:'Task', render:r => '<strong>' + esc(r.title) + '</strong>' + (r.description ? '<div class="crm360-meta">' + esc(r.description) + '</div>' : '')},
                    {label:'Assigned user', render:r => esc(r.assigned_user)},
                    {label:'Priority', render:r => badge(r.priority || 'Not set')},
                    {label:'Status', render:r => badge(r.is_overdue ? 'Overdue' : r.status)},
                    {label:'Due date', render:r => esc(r.due_at)},
                    {label:'Completed at', render:r => esc(r.completed_at)},
                    {label:'Actions', render:function (r) {
                        let html = '';
                        if (r.can_complete) html += '<button type="button" class="btn btn-xs btn-outline-success js-profile-complete-task mr-1" data-id="' + Number(r.id) + '"><i class="feather-check-circle"></i></button>';
                        if (r.can_archive) html += '<button type="button" class="btn btn-xs btn-outline-danger js-profile-archive-task" data-id="' + Number(r.id) + '"><i class="feather-archive"></i></button>';
                        return html || '<span class="text-muted">—</span>';
                    }}
                ], rows) + pager('tasks', payload.meta);
            }
            function renderLeads(payload) {
                const rows = payload.data || [];
                if (!rows.length) return empty(payload.message || 'No CRM leads are linked to this customer.') + pager('leads', payload.meta);
                return table([
                    {label:'Lead', render:r => '<strong>' + esc(r.name) + '</strong>' + (r.company_name ? '<div class="crm360-meta">' + esc(r.company_name) + '</div>' : '') + (r.requirement_summary ? '<div class="crm360-meta">' + esc(r.requirement_summary) + '</div>' : '')},
                    {label:'Contact', render:r => esc(r.phone)},
                    {label:'Assigned user', render:r => esc(r.assigned_user)},
                    {label:'Source', render:r => badge(r.source || 'Not set')},
                    {label:'Priority', render:r => badge(r.priority || 'Not set')},
                    {label:'Status', render:r => badge(r.status || 'Not set')},
                    {label:'Estimated value', render:r => esc(r.estimated_value)},
                    {label:'Next follow-up', render:r => esc(r.next_follow_up_at)}
                ], rows) + pager('leads', payload.meta);
            }
            function renderReturnsRefunds(payload) {
                const returns = (payload.returns && payload.returns.data) || [];
                const refunds = (payload.refunds && payload.refunds.data) || [];
                let html = '<h6>Returns</h6>' + (returns.length ? table([
                    {label:'Return', render:r => linked(r.code, r.url)}, {label:'Date', render:r => esc(r.date)},
                    {label:'Total', render:r => esc(r.total)}, {label:'Status', render:r => badge(r.status)}
                ], returns) : empty((payload.returns && payload.returns.message) || 'No returns found.'));
                html += '<h6 class="mt-4">Refunds</h6>' + (refunds.length ? table([
                    {label:'Refund', render:r => linked(r.code, r.url)}, {label:'Date', render:r => esc(r.date)},
                    {label:'Amount', render:r => esc(r.total)}, {label:'Status', render:r => badge(r.status)}
                ], refunds) : empty((payload.refunds && payload.refunds.message) || 'No refunds found.'));
                return html;
            }
            const renderers = {notes:renderNotes, activities:renderActivities, communications:renderCommunications, tasks:renderTasks, leads:renderLeads, orders:renderOrders,
                quotations:renderQuotations, contactHistories:renderContactHistories, scheduledContacts:renderScheduledContacts, returnsRefunds:renderReturnsRefunds};

            function loadSection(section, page) {
                page = Number(page || 1);
                const http = client();
                const target = containers[section];
                if (!http || !target || !urls[section]) {
                    if (target) $(target).html(failure('The shared window.AppAxios client is required.'));
                    return;
                }
                $(target).html(loading());
                http.get(urls[section], {params: {page: page, per_page: 10}})
                    .then(function (response) {
                        $(target).html(renderers[section](response.data || {}));
                        loaded[section] = true;
                    })
                    .catch(function (error) {
                        const data = (error.response && error.response.data) || {};
                        $(target).html(failure(data.message || 'Unable to load this customer profile section.'));
                    });
            }

            function renderAssignedTags(tags) {
                if (!tags || !tags.length) { $('#crm360AssignedTags').html(empty('No CRM tags assigned.')); return; }
                let html = '';
                tags.forEach(function (tag) {
                    const color = /^#(?:[A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/.test(tag.color || '') ? tag.color : '#94a3b8';
                    html += '<span class="crm360-tag"><span class="crm360-tag-dot" style="background:' + attr(color) + '"></span>' + esc(tag.name) +
                        (tag.status === 'active' ? '' : ' <small class="text-muted">inactive</small>') + '</span>';
                });
                $('#crm360AssignedTags').html(html);
            }
            function initializeTagSelect(tags) {
                if (!canManageTags || !urls.tagOptions) return;
                const select = $('#crmTagSelect');
                if (select.hasClass('select2-hidden-accessible')) select.select2('destroy');
                select.empty();
                (tags || []).forEach(function (tag) {
                    select.append(new Option(tag.name + (tag.status === 'active' ? '' : ' (inactive)'), String(tag.id), true, true));
                });
                select.select2({
                    width: '100%', placeholder: 'Search active CRM tags',
                    ajax: {
                        delay: 250,
                        transport: function (params, success, failure) {
                            const http = client();
                            if (!http) { failure(); return {abort:function(){}}; }
                            http.get(urls.tagOptions, {params: params.data || {}}).then(function (response) { success(response.data); }).catch(failure);
                            return {abort:function(){}};
                        },
                        data: function (params) { return {q: params.term || ''}; },
                        processResults: function (data) { return data; }
                    }
                });
            }
            function tagLoading(state) {
                tagSubmitting = state;
                $('#saveCrmTags').prop('disabled', state).html(state ? '<span class="spinner-border spinner-border-sm mr-1"></span>Saving...' : '<i class="feather-save"></i> Save tags');
            }

            renderAssignedTags(initialTags);
            initializeTagSelect(initialTags);

            $('#saveCrmTags').on('click', function () {
                if (tagSubmitting) return;
                const http = client();
                if (!http) { notify('Axios unavailable', 'The shared window.AppAxios client is required.', 'error'); return; }
                $('#crmTagError').text('');
                tagLoading(true);
                http.post(urls.tagSync, {tag_ids: ($('#crmTagSelect').val() || []).map(Number)})
                    .then(function (response) {
                        const tags = (((response.data || {}).data || {}).tags || []);
                        renderAssignedTags(tags);
                        initializeTagSelect(tags);
                        loaded.activities = false;
                        notify('Tags updated', response.data.message || 'Customer CRM tags synchronized successfully.', 'success');
                    })
                    .catch(function (error) {
                        const response = error.response || {}, data = response.data || {};
                        if (response.status === 422 && data.errors && data.errors.tag_ids) $('#crmTagError').text(data.errors.tag_ids[0]);
                        notify('Tag update failed', data.message || 'Unable to synchronize customer CRM tags.', 'error');
                    })
                    .finally(function () { tagLoading(false); });
            });

            $('.js-lazy-tab').on('shown.bs.tab', function () {
                const section = $(this).data('section');
                if (!loaded[section]) loadSection(section, 1);
            });
            $(document).on('click', '.js-profile-page', function () { loadSection($(this).data('section'), $(this).data('page')); });

            function communicationDetailRow(label, value) { return '<div class="mb-3"><div class="crm360-detail-label">' + esc(label) + '</div><div class="crm360-detail-value">' + esc(value) + '</div></div>'; }
            function renderCommunicationDetails(record) {
                let html = '<div class="row"><div class="col-md-6">' + communicationDetailRow('Customer', record.customer) + communicationDetailRow('Channel', record.channel) + communicationDetailRow('Direction', record.direction) + communicationDetailRow('Status', record.status) + communicationDetailRow('Source module', record.source_module) + '</div>' +
                    '<div class="col-md-6">' + communicationDetailRow('Logged by', record.actor) + communicationDetailRow('Sent or logged date', record.logged_at) + communicationDetailRow('Created at', record.created_at) + communicationDetailRow('Provider', record.provider) + communicationDetailRow('Provider reference', record.provider_reference) + '</div></div>' +
                    communicationDetailRow('Subject', record.subject) + communicationDetailRow('Full message or interaction summary', record.message);
                if (record.failed_at || record.failure_reason) html += '<hr>' + communicationDetailRow('Failed at', record.failed_at) + communicationDetailRow('Failure reason', record.failure_reason);
                return html;
            }
            $(document).on('click', '.js-profile-view-communication', function () {
                if (communicationDetailsLoading || !urls.communicationShow) return;
                const http = client(); if (!http) { notify('Axios unavailable', 'The shared window.AppAxios client is required.', 'error'); return; }
                communicationDetailsLoading = true;
                $('#crm360CommunicationDetailsBody').html('<div class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm mr-2"></span>Loading...</div>');
                $('#crm360CommunicationDetailsModal').modal('show');
                http.get(endpoint(urls.communicationShow, $(this).data('id'))).then(function (response) {
                    $('#crm360CommunicationDetailsBody').html(renderCommunicationDetails(response.data.communication || {}));
                }).catch(function (error) {
                    const data = (error.response && error.response.data) || {};
                    $('#crm360CommunicationDetailsBody').html('<div class="alert alert-danger mb-0">' + esc(data.message || 'Unable to load communication details.') + '</div>');
                }).finally(function () { communicationDetailsLoading = false; });
            });

            function resetNoteErrors() { $('#crmNoteText').removeClass('is-invalid'); $('#crmNoteTextError').text(''); }
            function noteLoading(state) {
                noteSubmitting = state;
                $('#saveCrmNote').prop('disabled', state).html(state ? '<span class="spinner-border spinner-border-sm mr-1"></span>Saving...' : '<i class="feather-save"></i> Save note');
            }
            function resetNoteForm() {
                $('#crmCustomerNoteForm')[0].reset(); $('#crmNoteId').val(''); resetNoteErrors(); noteLoading(false);
                $('#crmCustomerNoteModalTitle').text('Add CRM note');
            }
            $('#openCreateCrmNote').on('click', function () { resetNoteForm(); $('#crmCustomerNoteModal').modal('show'); });
            $(document).on('click', '.js-edit-note', function () {
                const note = noteCache[String($(this).data('id'))]; if (!note) return;
                resetNoteForm(); $('#crmNoteId').val(note.id); $('#crmNoteText').val(note.note || ''); $('#crmNotePrivate').prop('checked', !!note.is_private);
                $('#crmCustomerNoteModalTitle').text('Edit CRM note'); $('#crmCustomerNoteModal').modal('show');
            });
            $('#crmCustomerNoteForm').on('submit', function (event) {
                event.preventDefault(); if (noteSubmitting) return;
                const http = client(); if (!http) { notify('Axios unavailable', 'The shared window.AppAxios client is required.', 'error'); return; }
                resetNoteErrors(); noteLoading(true);
                const id = $('#crmNoteId').val();
                http.post(id ? endpoint(urls.noteUpdate, id) : urls.noteStore, {note: $('#crmNoteText').val(), is_private: $('#crmNotePrivate').is(':checked')})
                    .then(function (response) {
                        $('#crmCustomerNoteModal').modal('hide'); loaded.notes = false; loaded.activities = false; loadSection('notes', 1);
                        notify('Saved', response.data.message || 'CRM note saved successfully.', 'success');
                    })
                    .catch(function (error) {
                        const response = error.response || {}, data = response.data || {};
                        if (response.status === 422 && data.errors && data.errors.note) { $('#crmNoteText').addClass('is-invalid'); $('#crmNoteTextError').text(data.errors.note[0]); }
                        notify('Save failed', data.message || 'Unable to save the CRM note.', 'error');
                    })
                    .finally(function () { noteLoading(false); });
            });
            $(document).on('click', '.js-archive-note', function () {
                const id = $(this).data('id');
                Swal.fire({title:'Archive CRM note?', text:'The note will be softly archived and removed from the active profile.', icon:'warning', showCancelButton:true, confirmButtonText:'Yes, archive', confirmButtonColor:'#0f766e'})
                    .then(function (result) {
                        if (!result.isConfirmed) return;
                        const http = client(); if (!http) { notify('Axios unavailable', 'The shared window.AppAxios client is required.', 'error'); return; }
                        http.post(endpoint(urls.noteArchive, id), {})
                            .then(function (response) { loaded.notes = false; loaded.activities = false; loadSection('notes', 1); notify('Archived', response.data.message || 'CRM note archived.', 'success'); })
                            .catch(function (error) { const data = (error.response && error.response.data) || {}; notify('Archive failed', data.message || 'Unable to archive the CRM note.', 'error'); });
                    });
            });
            $(document).on('click', '.js-profile-complete-task', function () {
                const button = $(this), id = String(button.data('id')); if (taskCompletionRequests.has(id)) return;
                Swal.fire({title:'Complete CRM task?', text:'Add an optional completion note.', input:'textarea', inputPlaceholder:'Completion note', icon:'question', showCancelButton:true, confirmButtonText:'Yes, complete', confirmButtonColor:'#0f766e'})
                    .then(function (result) { if (!result.isConfirmed) return; const http = client(); if (!http) { notify('Axios unavailable', 'The shared window.AppAxios client is required.', 'error'); return; }
                        taskCompletionRequests.add(id); button.prop('disabled', true); http.post(endpoint(urls.taskComplete, id), {completion_note:result.value || null})
                            .then(function (response) { loaded.tasks = false; loaded.activities = false; loadSection('tasks', 1); notify('Completed', response.data.message || 'CRM task completed.', 'success'); })
                            .catch(function (error) { const data = (error.response && error.response.data) || {}; notify('Completion failed', data.message || 'Unable to complete the CRM task.', 'error'); })
                            .finally(function () { taskCompletionRequests.delete(id); button.prop('disabled', false); }); });
            });
            $(document).on('click', '.js-profile-archive-task', function () {
                const button = $(this), id = String(button.data('id')); if (taskArchiveRequests.has(id)) return;
                Swal.fire({title:'Archive CRM task?', text:'The task will be softly archived. No task record will be hard deleted.', icon:'warning', showCancelButton:true, confirmButtonText:'Yes, archive', confirmButtonColor:'#0f766e'})
                    .then(function (result) { if (!result.isConfirmed) return; const http = client(); if (!http) { notify('Axios unavailable', 'The shared window.AppAxios client is required.', 'error'); return; }
                        taskArchiveRequests.add(id); button.prop('disabled', true); http.post(endpoint(urls.taskArchive, id), {})
                            .then(function (response) { loaded.tasks = false; loaded.activities = false; loadSection('tasks', 1); notify('Archived', response.data.message || 'CRM task archived.', 'success'); })
                            .catch(function (error) { const data = (error.response && error.response.data) || {}; notify('Archive failed', data.message || 'Unable to archive the CRM task.', 'error'); })
                            .finally(function () { taskArchiveRequests.delete(id); button.prop('disabled', false); }); });
            });
            $('#crmCustomerNoteModal').on('hidden.bs.modal', resetNoteForm);
        })(jQuery);
    </script>
@endsection
