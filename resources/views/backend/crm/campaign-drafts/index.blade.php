@extends('backend.master')

@section('header_css')
    <link href="{{ url('dataTable') }}/css/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="{{ url('assets') }}/plugins/select2/select2.min.css" rel="stylesheet" type="text/css" />
    <style>
        .crm-campaign-draft-page { --crm-purple:#6d28d9; --crm-border:#e9ddff; --crm-soft:#faf5ff; }
        .crm-campaign-card { border:1px solid var(--crm-border); border-radius:14px; box-shadow:0 6px 18px rgba(109,40,217,.05); }
        .crm-campaign-filter-box { background:var(--crm-soft); border:1px solid var(--crm-border); border-radius:12px; }
        .crm-audience-pill { display:inline-flex; align-items:center; padding:2px 7px; margin:1px 2px 1px 0; border:1px solid #ddd6fe; border-radius:999px; background:#f5f3ff; font-size:11px; }
        .crm-preview-count { font-size:2rem; font-weight:700; color:var(--crm-purple); }
        .crm-readiness-check { border-bottom:1px solid #eee; padding:8px 0; }
        .crm-readiness-check:last-child { border-bottom:0; }
        #crmCampaignDraftTable td { vertical-align:middle; }
        .select2-container { width:100% !important; }
    </style>
@endsection

@section('page_title')
    CRM Campaign Drafts
@endsection

@section('page_heading')
    CRM Campaign Drafts
@endsection

@section('content')
    <div class="crm-campaign-draft-page">
        <div class="card crm-campaign-card">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
                    <div>
                        <h4 class="mb-1">CRM Campaign Planning: BulkSMSBD SMS and Email</h4>
                        <p class="text-muted mb-0">BulkSMSBD SMS has one manually confirmed bounded real-send path. Email may advance through the immutable non-sending ledger chain, while real SMTP transport execution remains intentionally deferred.</p>
                    </div>
                    <div class="mt-2 mt-md-0">
                        @if ($permissions['can_use_saved_segments'])
                            <a href="{{ route('crm.saved-customer-segments.index') }}" class="btn btn-sm btn-outline-primary mr-1"><i class="feather-bookmark"></i> Saved Segments</a>
                        @endif
                        @if ($permissions['can_create'] && $permissions['can_use_saved_segments'])
                            <button type="button" id="createCrmCampaignDraft" class="btn btn-sm btn-primary"><i class="feather-plus-circle"></i> New Draft</button>
                        @endif
                    </div>
                </div>

                <div class="alert alert-info">
                    <strong>Stage 32 stable CRM campaign boundary.</strong> New campaign planning exposes only <strong>BulkSMSBD SMS</strong> and <strong>Email</strong>. BulkSMSBD SMS keeps the Stage 27 bounded real-send path: one recipient per HTTPS POST, maximum five recipients, typed <code>SEND SMS</code> confirmation, append-only exchange events, and no automatic retry. Email may advance through preparation, release, claim, and provider-attempt ledgers in non-sending mode only. Real SMTP transport execution remains disabled. Newsletter, WhatsApp, and other legacy campaign channels remain hidden and disabled without deleting historical rows.
                </div>

                @if (!$permissions['can_use_saved_segments'])
                    <div class="alert alert-warning">Saved-segment read permission is required before a campaign draft audience can be selected or updated.</div>
                @endif

                <div id="crmCampaignDraftValidation" class="alert alert-danger d-none" role="alert"></div>

                <div class="crm-campaign-filter-box p-3 mb-3">
                    <div class="row align-items-end">
                        <div class="col-lg-3 col-md-4 mb-2">
                            <label for="crmCampaignDraftStatus">Status</label>
                            <select id="crmCampaignDraftStatus" class="form-control">
                                <option value="draft">Draft</option>
                                <option value="pending_review">Pending review</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                                <option value="archived">Archived</option>
                                <option value="">All statuses</option>
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-4 mb-2">
                            <label for="crmCampaignDraftVisibility">Visibility</label>
                            <select id="crmCampaignDraftVisibility" class="form-control">
                                <option value="">All visibility</option>
                                <option value="private">Private</option>
                                <option value="shared">Shared</option>
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-4 mb-2">
                            <label for="crmCampaignDraftChannel">Planned channel</label>
                            <select id="crmCampaignDraftChannel" class="form-control">
                                <option value="">All planned channels</option>
                                <option value="sms">BulkSMSBD SMS</option>
                                <option value="email">Email</option>
                                {{-- Stage 32 closure: newsletter, WhatsApp, and other remain legacy-disabled historical values. --}}
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-4 mb-2">
                            <button type="button" id="resetCrmCampaignDraftFilters" class="btn btn-outline-secondary btn-block">Reset filters</button>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped mb-0" id="crmCampaignDraftTable">
                        <thead>
                            <tr>
                                <th>Draft</th>
                                <th>Audience source</th>
                                <th>Planned channel</th>
                                <th>Visibility</th>
                                <th>Status</th>
                                <th>Creator</th>
                                <th>Updated</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="crmCampaignDraftDetailsModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Campaign Draft Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body" id="crmCampaignDraftDetailsBody"></div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="crmCampaignPreviewModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Read-only Audience Preview</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body" id="crmCampaignPreviewBody"></div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="crmCampaignPreflightModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Read-only Send-Readiness Preflight</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body" id="crmCampaignPreflightBody"></div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="crmCampaignApprovalHistoryModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Immutable Approval History</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body" id="crmCampaignApprovalHistoryBody"></div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="crmCampaignDispatchPreparationModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Immutable Dispatch Preparation</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body" id="crmCampaignDispatchPreparationBody"></div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="crmCampaignDispatchRunModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Provider-Neutral Dispatch Run</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body" id="crmCampaignDispatchRunBody"></div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button></div>
            </div>
        </div>
    </div>



    <div class="modal fade" id="crmCampaignDispatchExecutionBatchModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Provider-Neutral Manual Execution Batch</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body" id="crmCampaignDispatchExecutionBatchBody"></div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="crmCampaignDispatchAttemptModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Local Provider Readiness and Manual Attempt Ledger</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body" id="crmCampaignDispatchAttemptBody"></div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button></div>
            </div>
        </div>
    </div>

    @if (($permissions['can_create'] || $permissions['can_update']) && $permissions['can_use_saved_segments'])
        <div class="modal fade" id="crmCampaignDraftFormModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <form id="crmCampaignDraftForm">
                        <div class="modal-header">
                            <h5 class="modal-title" id="crmCampaignDraftFormTitle">New Campaign Draft</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <div id="crmCampaignDraftFormErrors" class="alert alert-danger d-none"></div>
                            <div class="row">
                                <div class="col-md-7">
                                    <div class="form-group">
                                        <label for="crmCampaignDraftName">Draft name</label>
                                        <input type="text" id="crmCampaignDraftName" class="form-control" maxlength="160" required>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label for="crmCampaignDraftFormChannel">Planned channel</label>
                                        <select id="crmCampaignDraftFormChannel" class="form-control">
                                            <option value="">Not selected</option>
                                            <option value="sms">BulkSMSBD SMS</option>
                                            <option value="email">Email</option>
                                            {{-- Stage 28: newsletter, WhatsApp, and other are intentionally unavailable for new drafts. --}}
                                        </select>
                                        <small class="text-muted">BulkSMSBD SMS supports the bounded manual real-send path. Email is planning and approval only until the dedicated email-delivery stages are completed.</small>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="crmCampaignDraftDescription">Description</label>
                                <textarea id="crmCampaignDraftDescription" class="form-control" rows="2" maxlength="2000"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="crmCampaignDraftAudience">Audience saved segment</label>
                                <select id="crmCampaignDraftAudience" class="form-control"></select>
                                <small class="text-muted">Only active, visible, valid saved segments can be newly attached.</small>
                            </div>
                            <div class="form-group">
                                <button type="button" id="previewCrmCampaignDraftAudience" class="btn btn-sm btn-outline-info"><i class="feather-eye"></i> Preview audience</button>
                                <div id="crmCampaignDraftAudienceEstimate" class="mt-2 text-muted">Select an audience segment to calculate a live read-only estimate.</div>
                            </div>
                            <div class="form-group">
                                <label for="crmCampaignDraftSubject">Subject</label>
                                <input type="text" id="crmCampaignDraftSubject" class="form-control" maxlength="255">
                                <small class="text-muted">Required by readiness preflight for Email campaign planning.</small>
                            </div>
                            <div class="form-group">
                                <label for="crmCampaignDraftMessageBody">Message body</label>
                                <textarea id="crmCampaignDraftMessageBody" class="form-control" rows="5" maxlength="10000"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="crmCampaignDraftFormVisibility">Visibility</label>
                                <select id="crmCampaignDraftFormVisibility" class="form-control" required>
                                    <option value="private">Private</option>
                                    <option value="shared">Shared</option>
                                </select>
                                <small class="text-muted">Independent approval requires a shared campaign with a shared sealed audience snapshot.</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" id="crmCampaignDraftSubmit" class="btn btn-primary"><i class="feather-save"></i> Save draft</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection

@section('footer_js')
    <script src="{{ url('dataTable') }}/js/jquery.dataTables.min.js"></script>
    <script src="{{ url('dataTable') }}/js/dataTables.bootstrap4.min.js"></script>
    <script src="{{ url('assets') }}/plugins/select2/select2.min.js"></script>
    @php
        $crmCampaignDraftUrls = [
            'data' => route('crm.campaign-drafts.data'),
            'show' => route('crm.campaign-drafts.show', ['draft' => '__DRAFT__']),
            'preview' => route('crm.campaign-drafts.preview', ['draft' => '__DRAFT__']),
            'preflight' => route('crm.campaign-drafts.preflight', ['draft' => '__DRAFT__']),
            'history' => route('crm.campaign-drafts.approval-history', ['draft' => '__DRAFT__']),
            'archive' => route('crm.campaign-drafts.archive', ['draft' => '__DRAFT__']),
        ];
        if ($permissions['can_use_saved_segments']) {
            $crmCampaignDraftUrls['saved_options'] = route('crm.campaign-drafts.options.saved-segments');
            $crmCampaignDraftUrls['saved_preview'] = route('crm.campaign-drafts.audience-preview.saved-segment', ['segment' => '__SEGMENT__']);
        }
        if ($permissions['can_create'] && $permissions['can_use_saved_segments']) {
            $crmCampaignDraftUrls['store'] = route('crm.campaign-drafts.store');
        }
        if ($permissions['can_update'] && $permissions['can_use_saved_segments']) {
            $crmCampaignDraftUrls['update'] = route('crm.campaign-drafts.update', ['draft' => '__DRAFT__']);
            $crmCampaignDraftUrls['refresh_audience_snapshot'] = route('crm.campaign-drafts.refresh-audience-snapshot', ['draft' => '__DRAFT__']);
        }
        if ($permissions['can_prepare_dispatch_read']) {
            $crmCampaignDraftUrls['dispatch_preview'] = route('crm.campaign-drafts.dispatch-preparations.preview', ['draft' => '__DRAFT__']);
            $crmCampaignDraftUrls['dispatch_history'] = route('crm.campaign-drafts.dispatch-preparations.history', ['draft' => '__DRAFT__']);
        }
        if ($permissions['can_prepare_dispatch_create']) {
            $crmCampaignDraftUrls['dispatch_store'] = route('crm.campaign-drafts.dispatch-preparations.store', ['draft' => '__DRAFT__']);
        }
        if ($permissions['can_prepare_dispatch_update']) {
            $crmCampaignDraftUrls['dispatch_cancel'] = route('crm.campaign-drafts.dispatch-preparations.cancel', ['draft' => '__DRAFT__', 'preparation' => '__PREPARATION__']);
            $crmCampaignDraftUrls['dispatch_invalidate'] = route('crm.campaign-drafts.dispatch-preparations.invalidate', ['draft' => '__DRAFT__', 'preparation' => '__PREPARATION__']);
        }
        if ($permissions['can_release_dispatch_read']) {
            $crmCampaignDraftUrls['dispatch_run_preview'] = route('crm.campaign-drafts.dispatch-runs.preview', ['draft' => '__DRAFT__']);
            $crmCampaignDraftUrls['dispatch_run_history'] = route('crm.campaign-drafts.dispatch-runs.history', ['draft' => '__DRAFT__']);
        }
        if ($permissions['can_release_dispatch_create']) {
            $crmCampaignDraftUrls['dispatch_run_store'] = route('crm.campaign-drafts.dispatch-runs.store', ['draft' => '__DRAFT__', 'preparation' => '__PREPARATION__']);
        }
        if ($permissions['can_release_dispatch_update']) {
            $crmCampaignDraftUrls['dispatch_run_cancel'] = route('crm.campaign-drafts.dispatch-runs.cancel', ['draft' => '__DRAFT__', 'run' => '__RUN__']);
        }
        if ($permissions['can_claim_dispatch_execution_read']) {
            $crmCampaignDraftUrls['dispatch_execution_preview'] = route('crm.campaign-drafts.dispatch-execution-batches.preview', ['draft' => '__DRAFT__']);
            $crmCampaignDraftUrls['dispatch_execution_history'] = route('crm.campaign-drafts.dispatch-execution-batches.history', ['draft' => '__DRAFT__']);
        }
        if ($permissions['can_claim_dispatch_execution_create']) {
            $crmCampaignDraftUrls['dispatch_execution_store'] = route('crm.campaign-drafts.dispatch-execution-batches.store', ['draft' => '__DRAFT__', 'run' => '__RUN__']);
        }
        if ($permissions['can_claim_dispatch_execution_update']) {
            $crmCampaignDraftUrls['dispatch_execution_cancel'] = route('crm.campaign-drafts.dispatch-execution-batches.cancel', ['draft' => '__DRAFT__', 'batch' => '__BATCH__']);
        }
        if ($permissions['can_prepare_dispatch_attempt_read']) {
            $crmCampaignDraftUrls['dispatch_attempt_preview'] = route('crm.campaign-drafts.dispatch-attempts.preview', ['draft' => '__DRAFT__']);
            $crmCampaignDraftUrls['dispatch_attempt_history'] = route('crm.campaign-drafts.dispatch-attempts.history', ['draft' => '__DRAFT__']);
        }
        if ($permissions['can_prepare_dispatch_attempt_create']) {
            $crmCampaignDraftUrls['dispatch_attempt_store'] = route('crm.campaign-drafts.dispatch-attempts.store', ['draft' => '__DRAFT__', 'batch' => '__BATCH__']);
        }
        if ($permissions['can_prepare_dispatch_attempt_update']) {
            $crmCampaignDraftUrls['dispatch_attempt_cancel'] = route('crm.campaign-drafts.dispatch-attempts.cancel', ['draft' => '__DRAFT__', 'attempt' => '__ATTEMPT__']);
        }
        if ($permissions['can_execute_dispatch_create']) {
            $crmCampaignDraftUrls['dispatch_attempt_execute'] = route('crm.campaign-drafts.dispatch-attempts.execute', ['draft' => '__DRAFT__', 'attempt' => '__ATTEMPT__']);
        }
        if ($permissions['can_update']) {
            $crmCampaignDraftUrls['submit'] = route('crm.campaign-drafts.submit-for-review', ['draft' => '__DRAFT__']);
            $crmCampaignDraftUrls['return_to_draft'] = route('crm.campaign-drafts.return-to-draft', ['draft' => '__DRAFT__']);
        }
        if ($permissions['can_approve']) {
            $crmCampaignDraftUrls['approve'] = route('crm.campaign-drafts.approve', ['draft' => '__DRAFT__']);
            $crmCampaignDraftUrls['reject'] = route('crm.campaign-drafts.reject', ['draft' => '__DRAFT__']);
        }
    @endphp
    <script>
        (function ($) {
            'use strict';
            const urls = @json($crmCampaignDraftUrls);
            let editingDraftId = null;
            let formBusy = false;
            let archiveBusy = false;
            let governanceBusy = false;
            let dispatchBusy = false;
            let currentDispatchDraftId = null;
            let dispatchRunBusy = false;
            let currentDispatchRunDraftId = null;
            let dispatchExecutionBusy = false;
            let currentDispatchExecutionDraftId = null;
            let dispatchAttemptBusy = false;
            let currentDispatchAttemptDraftId = null;
            let tableErrorVisible = false;

            function client() { return window.AppAxios || null; }
            function esc(value) { return $('<div>').text(value === null || value === undefined || value === '' ? '—' : String(value)).html(); }
            function attr(value) { return esc(value).replace(/`/g, '&#96;'); }
            function notify(title, text, icon) {
                if (typeof Swal !== 'undefined') return Swal.fire({title:title, text:text || '', icon:icon || 'info', confirmButtonColor:'#6d28d9'});
                window.alert((title ? title + ': ' : '') + (text || ''));
            }
            function emptyTable(draw) { return {draw:Number(draw || 0), recordsTotal:0, recordsFiltered:0, data:[]}; }
            function validationText(payload) {
                const errors = payload && payload.errors ? payload.errors : {};
                const messages = [];
                Object.keys(errors).forEach(function (key) {
                    (Array.isArray(errors[key]) ? errors[key] : [errors[key]]).forEach(function (message) { messages.push(String(message)); });
                });
                return messages.join('\n');
            }
            function showValidation(message) { $('#crmCampaignDraftValidation').toggleClass('d-none', !message).text(message || ''); }
            function showFormValidation(message) { $('#crmCampaignDraftFormErrors').toggleClass('d-none', !message).text(message || ''); }
            function badge(value, type) { return '<span class="badge badge-' + (type || 'light') + '">' + esc(value) + '</span>'; }
            function channelLabel(value) {
                if (value === 'sms') return 'BulkSMSBD SMS';
                if (value === 'email') return 'Email';
                if (value === 'newsletter') return 'Newsletter (legacy disabled)';
                if (value === 'whatsapp') return 'WhatsApp (legacy disabled)';
                if (value === 'other') return 'Other (legacy disabled)';
                return 'Not selected';
            }
            function channelBoundaryHtml(draft) {
                const text = draft && draft.channel_boundary_notice ? draft.channel_boundary_notice : '';
                if (!text) return '';
                const type = draft.email_planning_only ? 'warning' : (draft.legacy_disabled_channel ? 'secondary' : 'info');
                return '<div class="alert alert-' + type + '">' + esc(text) + '</div>';
            }
            function statusBadge(value) {
                if (value === 'draft') return badge('Draft', 'primary');
                if (value === 'pending_review') return badge('Pending review', 'warning');
                if (value === 'approved') return badge('Approved', 'success');
                if (value === 'rejected') return badge('Rejected', 'danger');
                if (value === 'archived') return badge('Archived', 'secondary');
                return badge('Unavailable', 'warning');
            }
            function pills(items) {
                if (!Array.isArray(items) || !items.length) return '<span class="text-muted">No filters</span>';
                return items.map(function (item) { return '<span class="crm-audience-pill">' + esc(item.label) + ': ' + esc(item.value) + '</span>'; }).join('');
            }
            function actionButtons(draft) {
                if (!draft || !draft.id) return '';
                let html = '<button type="button" class="btn btn-xs btn-outline-primary mr-1 mb-1 js-campaign-details" data-id="' + attr(draft.id) + '">Details</button>';
                if (draft.preview_url) html += '<button type="button" class="btn btn-xs btn-outline-info mr-1 mb-1 js-campaign-preview" data-id="' + attr(draft.id) + '">Audience</button>';
                if (draft.preflight_url) html += '<button type="button" class="btn btn-xs btn-outline-dark mr-1 mb-1 js-campaign-preflight" data-id="' + attr(draft.id) + '">Preflight</button>';
                if (draft.approval_history_url) html += '<button type="button" class="btn btn-xs btn-outline-secondary mr-1 mb-1 js-campaign-history" data-id="' + attr(draft.id) + '">History</button>';
                if (draft.dispatch_preparation_preview_url && urls.dispatch_preview) html += '<button type="button" class="btn btn-xs btn-outline-success mr-1 mb-1 js-campaign-dispatch-preview" data-id="' + attr(draft.id) + '">Prepare preview</button>';
                if (draft.dispatch_preparation_history_url && urls.dispatch_history) html += '<button type="button" class="btn btn-xs btn-outline-secondary mr-1 mb-1 js-campaign-dispatch-history" data-id="' + attr(draft.id) + '">Preparation history</button>';
                if (draft.dispatch_run_preview_url && urls.dispatch_run_preview) html += '<button type="button" class="btn btn-xs btn-outline-success mr-1 mb-1 js-campaign-dispatch-run-preview" data-id="' + attr(draft.id) + '">Release preview</button>';
                if (draft.dispatch_run_history_url && urls.dispatch_run_history) html += '<button type="button" class="btn btn-xs btn-outline-secondary mr-1 mb-1 js-campaign-dispatch-run-history" data-id="' + attr(draft.id) + '">Run history</button>';
                if (draft.dispatch_execution_batch_preview_url && urls.dispatch_execution_preview) html += '<button type="button" class="btn btn-xs btn-outline-success mr-1 mb-1 js-campaign-dispatch-execution-preview" data-id="' + attr(draft.id) + '">Claim preview</button>';
                if (draft.dispatch_execution_batch_history_url && urls.dispatch_execution_history) html += '<button type="button" class="btn btn-xs btn-outline-secondary mr-1 mb-1 js-campaign-dispatch-execution-history" data-id="' + attr(draft.id) + '">Execution history</button>';
                if (draft.dispatch_attempt_preview_url && urls.dispatch_attempt_preview) html += '<button type="button" class="btn btn-xs btn-outline-success mr-1 mb-1 js-campaign-dispatch-attempt-preview" data-id="' + attr(draft.id) + '">Provider readiness</button>';
                if (draft.dispatch_attempt_history_url && urls.dispatch_attempt_history) html += '<button type="button" class="btn btn-xs btn-outline-secondary mr-1 mb-1 js-campaign-dispatch-attempt-history" data-id="' + attr(draft.id) + '">Attempt history</button>';
                if (draft.can_refresh_audience_snapshot && urls.refresh_audience_snapshot) html += '<button type="button" class="btn btn-xs btn-outline-info mr-1 mb-1 js-campaign-refresh-audience" data-id="' + attr(draft.id) + '" data-name="' + attr(draft.name) + '">Refresh audience</button>';
                if (draft.can_edit && urls.update) html += '<button type="button" class="btn btn-xs btn-outline-primary mr-1 mb-1 js-campaign-edit" data-id="' + attr(draft.id) + '">Edit</button>';
                if (draft.can_submit_for_review && urls.submit) html += '<button type="button" class="btn btn-xs btn-outline-success mr-1 mb-1 js-campaign-submit" data-id="' + attr(draft.id) + '" data-name="' + attr(draft.name) + '">Submit review</button>';
                if (draft.can_approve && urls.approve) html += '<button type="button" class="btn btn-xs btn-success mr-1 mb-1 js-campaign-approve" data-id="' + attr(draft.id) + '" data-name="' + attr(draft.name) + '">Approve</button>';
                if (draft.can_reject && urls.reject) html += '<button type="button" class="btn btn-xs btn-outline-danger mr-1 mb-1 js-campaign-reject" data-id="' + attr(draft.id) + '" data-name="' + attr(draft.name) + '">Reject</button>';
                if (draft.can_return_to_draft && urls.return_to_draft) html += '<button type="button" class="btn btn-xs btn-outline-warning mr-1 mb-1 js-campaign-return" data-id="' + attr(draft.id) + '" data-name="' + attr(draft.name) + '">Return draft</button>';
                if (draft.can_archive) html += '<button type="button" class="btn btn-xs btn-outline-warning mr-1 mb-1 js-campaign-archive" data-id="' + attr(draft.id) + '" data-name="' + attr(draft.name) + '">Archive</button>';
                return html;
            }
            function detailsHtml(draft) {
                return '<div class="row">'
                    + '<div class="col-md-6"><p><strong>Name:</strong> ' + esc(draft.name) + '</p><p><strong>Status:</strong> ' + statusBadge(draft.status) + '</p><p><strong>Channel:</strong> ' + esc(draft.planned_channel_label || channelLabel(draft.planned_channel)) + '</p><p><strong>Visibility:</strong> ' + esc(draft.visibility) + '</p></div>'
                    + '<div class="col-md-6"><p><strong>Creator:</strong> ' + esc(draft.creator) + '</p><p><strong>Submitted by:</strong> ' + esc(draft.submitted_by_name) + '</p><p><strong>Reviewed by:</strong> ' + esc(draft.reviewed_by_name) + '</p><p><strong>Review decision:</strong> ' + esc(draft.review_decision) + '</p></div>'
                    + '</div>'
                    + channelBoundaryHtml(draft)
                    + '<p><strong>Description:</strong><br>' + esc(draft.description) + '</p>'
                    + '<p><strong>Subject:</strong><br>' + esc(draft.subject) + '</p>'
                    + '<p><strong>Message body:</strong><br>' + esc(draft.message_body) + '</p>'
                    + '<p><strong>Review note:</strong><br>' + esc(draft.review_note) + '</p>'
                    + '<p><strong>Historical audience source:</strong> ' + esc(draft.audience_segment_name_snapshot) + ' · ' + esc(draft.audience_segment_visibility_snapshot) + '</p>'
                    + '<p><strong>Audience seal:</strong> ' + esc(draft.audience_snapshot_integrity_label) + '<br><small class="text-muted">' + esc(draft.audience_snapshot_integrity_message) + '</small></p>'
                    + '<p><strong>Approval integrity:</strong> ' + esc(draft.approval_snapshot_integrity_label) + '<br><small class="text-muted">' + esc(draft.approval_snapshot_integrity_message) + '</small></p>'
                    + '<p><strong>Approval-time sealed recipients:</strong> ' + (draft.approved_recipient_count === null ? '—' : Number(draft.approved_recipient_count || 0).toLocaleString()) + '</p>'
                    + '<h6>Immutable audience snapshot filters</h6><div>' + pills(draft.audience_filters_summary) + '</div>';
            }
            function previewHtml(preview) {
                return '<div class="crm-preview-count">' + Number(preview.estimated_recipients || 0).toLocaleString() + '</div>'
                    + '<p class="text-muted">Estimated recipients calculated live from the immutable stored filters.</p>'
                    + '<h6>Snapshot filters</h6><div class="mb-3">' + pills(preview.filters_summary) + '</div>'
                    + '<div class="alert alert-info mb-0">' + esc(preview.notice) + '</div>';
            }
            function preflightHtml(preflight) {
                const checks = Array.isArray(preflight.checks) ? preflight.checks : [];
                const rows = checks.map(function (check) {
                    return '<div class="crm-readiness-check d-flex justify-content-between align-items-start"><div><strong>' + esc(check.label) + '</strong><br><small class="text-muted">' + esc(check.message) + '</small></div><span class="badge badge-' + (check.passed ? 'success' : 'danger') + '">' + (check.passed ? 'Pass' : 'Fail') + '</span></div>';
                }).join('');
                const localReadiness = preflight.local_channel_readiness || null;
                const localChecks = localReadiness && Array.isArray(localReadiness.checks) ? localReadiness.checks.map(function (check) {
                    return '<div class="crm-readiness-check d-flex justify-content-between align-items-start"><div><strong>' + esc(check.label) + '</strong><br><small class="text-muted">' + esc(check.message) + '</small></div><span class="badge badge-' + (check.passed ? 'success' : 'danger') + '">' + (check.passed ? 'Pass' : 'Fail') + '</span></div>';
                }).join('') : '';
                const emailPolicyHtml = localReadiness ? '<div class="alert alert-secondary mt-2 mb-0"><strong>Stage 30 non-sending Email policy boundary</strong><br><small>Future transport: Disabled · Request boundary: One normalized Email recipient per transport call · Future manual-attempt cap: ' + Number(localReadiness.future_recipient_cap || 0).toLocaleString() + ' · Subject limit: ' + Number(localReadiness.future_max_subject_length || 0).toLocaleString() + ' characters · Message limit: ' + Number(localReadiness.future_max_message_body_length || 0).toLocaleString() + ' characters · SMTP modes: none / TLS / SSL · Automatic retry: Disabled · Browser resend after interruption: Disabled</small></div>' : '';
                const localReadinessHtml = localReadiness ? '<div class="card mt-3"><div class="card-body py-2"><strong>Local Email SMTP and policy readiness: ' + esc(localReadiness.ready_label) + '</strong><br><small class="text-muted">Informational only · Provider boundary: ' + esc(localReadiness.provider_key) + ' · Remote connectivity probe performed: No · Mail transport invoked: No · Real Email dispatch available: No</small>' + localChecks + '<div class="alert alert-warning mt-2 mb-0">' + esc(localReadiness.message) + '</div>' + emailPolicyHtml + '</div></div>' : '';
                return '<div class="alert alert-' + (preflight.send_readiness ? 'success' : 'warning') + '"><strong>' + esc(preflight.send_readiness_label) + '</strong><br>Channel: ' + esc(preflight.planned_channel_label || channelLabel(preflight.planned_channel)) + ' · Estimated recipients: ' + Number(preflight.estimated_recipients || 0).toLocaleString() + '</div>'
                    + (preflight.channel_boundary_notice ? '<div class="alert alert-' + (preflight.email_planning_only ? 'warning' : 'info') + '">' + esc(preflight.channel_boundary_notice) + '</div>' : '')
                    + rows + localReadinessHtml
                    + '<div class="alert alert-info mt-3 mb-0">' + esc(preflight.notice) + '</div>';
            }
            function historyHtml(history) {
                if (!Array.isArray(history) || !history.length) return '<p class="text-muted mb-0">No approval-governance history has been recorded yet.</p>';
                return '<div class="table-responsive"><table class="table table-sm table-bordered mb-0"><thead><tr><th>When</th><th>Action</th><th>Transition</th><th>Actor</th><th>Note</th></tr></thead><tbody>'
                    + history.map(function (row) { return '<tr><td>' + esc(row.created_at) + '</td><td>' + esc(row.action) + '</td><td>' + esc(row.from_status) + ' → ' + esc(row.to_status) + '</td><td>' + esc(row.actor_name) + '</td><td>' + esc(row.review_note) + '</td></tr>'; }).join('')
                    + '</tbody></table></div>';
            }
            function dispatchPreparationEndpoint(template, draftId, preparationId) {
                return (template || '').replace('__DRAFT__', draftId).replace('__PREPARATION__', preparationId);
            }
            function dispatchChecksHtml(checks) {
                return (Array.isArray(checks) ? checks : []).map(function (check) {
                    return '<div class="crm-readiness-check d-flex justify-content-between align-items-start"><div><strong>' + esc(check.label) + '</strong><br><small class="text-muted">' + esc(check.message) + '</small></div><span class="badge badge-' + (check.passed ? 'success' : 'danger') + '">' + (check.passed ? 'Pass' : 'Fail') + '</span></div>';
                }).join('');
            }
            function dispatchPreviewHtml(preview) {
                const active = preview.active_preparation;
                const activeHtml = active ? '<div class="alert alert-warning"><strong>Active preparation #' + esc(active.id) + '</strong><br>Frozen recipients: ' + Number(active.frozen_recipient_count || 0).toLocaleString() + ' · ' + esc(active.prepared_at) + '</div>' : '';
                const prepareButton = preview.ready_for_preparation && urls.dispatch_store
                    ? '<button type="button" id="crmCampaignPrepareDispatch" class="btn btn-success mt-3" data-id="' + attr(preview.draft_id) + '"><i class="feather-lock"></i> Freeze immutable preparation</button>'
                    : '';
                return '<div class="alert alert-' + (preview.ready_for_preparation ? 'success' : 'warning') + '"><strong>' + esc(preview.ready_for_preparation_label) + '</strong><br>Approved recipients: ' + (preview.approved_recipient_count === null ? '—' : Number(preview.approved_recipient_count || 0).toLocaleString()) + '<br>Live normalized recipients: ' + (preview.live_normalized_recipient_count === null ? '—' : Number(preview.live_normalized_recipient_count || 0).toLocaleString()) + '</div>'
                    + activeHtml + dispatchChecksHtml(preview.checks)
                    + '<div class="alert alert-info mt-3 mb-0">' + esc(preview.notice) + '<br><small>' + esc(preview.destination_summary) + '</small></div>'
                    + prepareButton;
            }
            function dispatchHistoryHtml(history) {
                if (!Array.isArray(history) || !history.length) return '<p class="text-muted mb-0">No dispatch preparation has been frozen yet.</p>';
                return '<div class="alert alert-info">Aggregate history only. Recipient lists and destinations are intentionally unavailable.</div><div class="table-responsive"><table class="table table-sm table-bordered mb-0"><thead><tr><th>When</th><th>Status</th><th>Channel</th><th>Recipients</th><th>Actor</th><th>Reason</th><th>Action</th></tr></thead><tbody>'
                    + history.map(function (row) {
                        let actions = '';
                        if (row.can_cancel && urls.dispatch_cancel) actions += '<button type="button" class="btn btn-xs btn-outline-warning mr-1 js-campaign-dispatch-cancel" data-draft="' + attr(row.crm_campaign_draft_id) + '" data-id="' + attr(row.id) + '">Cancel</button>';
                        if (row.can_invalidate && urls.dispatch_invalidate) actions += '<button type="button" class="btn btn-xs btn-outline-danger js-campaign-dispatch-invalidate" data-draft="' + attr(row.crm_campaign_draft_id) + '" data-id="' + attr(row.id) + '">Invalidate</button>';
                        return '<tr><td>' + esc(row.prepared_at || row.created_at) + '</td><td>' + esc(row.release_state || row.status) + '</td><td>' + esc(row.channel) + '</td><td>' + Number(row.frozen_recipient_count || 0).toLocaleString() + '</td><td>' + esc(row.prepared_by_name) + '</td><td>' + esc(row.cancellation_reason || row.invalidation_reason) + '</td><td>' + actions + '</td></tr>';
                    }).join('') + '</tbody></table></div>';
            }
            function loadDispatchPreview(draftId) {
                const http = client(); if (!http || !urls.dispatch_preview) return notify('Preparation preview unavailable', 'The dispatch-preparation preview endpoint is unavailable.', 'error');
                currentDispatchDraftId = Number(draftId); $('#crmCampaignDispatchPreparationBody').html('<p class="text-muted mb-0">Verifying immutable preparation boundary...</p>'); $('#crmCampaignDispatchPreparationModal').modal('show');
                http.get(endpoint(urls.dispatch_preview, draftId)).then(function (response) { $('#crmCampaignDispatchPreparationBody').html(dispatchPreviewHtml(response.data.preview || {})); }).catch(function (error) {
                    const payload = (error.response && error.response.data) || {}; $('#crmCampaignDispatchPreparationBody').html('<div class="alert alert-danger mb-0">' + esc(validationText(payload) || payload.message || 'Unable to verify dispatch-preparation readiness.') + '</div>');
                });
            }
            function loadDispatchHistory(draftId) {
                const http = client(); if (!http || !urls.dispatch_history) return notify('Preparation history unavailable', 'The dispatch-preparation history endpoint is unavailable.', 'error');
                currentDispatchDraftId = Number(draftId); $('#crmCampaignDispatchPreparationBody').html('<p class="text-muted mb-0">Loading aggregate preparation history...</p>'); $('#crmCampaignDispatchPreparationModal').modal('show');
                http.get(endpoint(urls.dispatch_history, draftId)).then(function (response) { $('#crmCampaignDispatchPreparationBody').html(dispatchHistoryHtml(response.data.history || [])); }).catch(function (error) {
                    const payload = (error.response && error.response.data) || {}; $('#crmCampaignDispatchPreparationBody').html('<div class="alert alert-danger mb-0">' + esc(validationText(payload) || payload.message || 'Unable to load dispatch-preparation history.') + '</div>');
                });
            }
            function dispatchRunEndpoint(template, draftId, preparationId, runId) {
                return (template || '').replace('__DRAFT__', draftId).replace('__PREPARATION__', preparationId || '').replace('__RUN__', runId || '');
            }
            function dispatchRunPreviewHtml(preview) {
                const preparation = preview.active_preparation;
                const activeRun = preview.active_run;
                const preparationHtml = preparation ? '<div class="alert alert-info"><strong>Eligible frozen preparation #' + esc(preparation.id) + '</strong><br>Channel: ' + esc(preparation.channel) + ' · Frozen recipients: ' + Number(preparation.frozen_recipient_count || 0).toLocaleString() + ' · Prepared: ' + esc(preparation.prepared_at) + '</div>' : '';
                const activeRunHtml = activeRun ? '<div class="alert alert-warning"><strong>Active released run #' + esc(activeRun.id) + '</strong><br>Channel: ' + esc(activeRun.channel) + ' · Frozen recipients: ' + Number(activeRun.frozen_recipient_count || 0).toLocaleString() + ' · Released: ' + esc(activeRun.released_at) + '</div>' : '';
                const releaseButton = preview.ready_for_release && preparation && urls.dispatch_run_store
                    ? '<button type="button" id="crmCampaignReleaseDispatchRun" class="btn btn-success mt-3" data-draft="' + attr(preview.draft_id) + '" data-preparation="' + attr(preparation.id) + '"><i class="feather-shield"></i> Release provider-neutral run</button>'
                    : '';
                return '<div class="alert alert-' + (preview.ready_for_release ? 'success' : 'warning') + '"><strong>' + esc(preview.ready_for_release_label) + '</strong><br>Approved recipients: ' + (preview.approved_recipient_count === null ? '—' : Number(preview.approved_recipient_count || 0).toLocaleString()) + '<br>Frozen recipients: ' + (preview.frozen_recipient_count === null ? '—' : Number(preview.frozen_recipient_count || 0).toLocaleString()) + '</div>'
                    + preparationHtml + activeRunHtml + dispatchChecksHtml(preview.checks)
                    + '<div class="alert alert-info mt-3 mb-0">' + esc(preview.notice) + '</div>'
                    + releaseButton;
            }
            function dispatchRunHistoryHtml(history) {
                if (!Array.isArray(history) || !history.length) return '<p class="text-muted mb-0">No provider-neutral dispatch run has been released yet.</p>';
                return '<div class="alert alert-info">Aggregate provider-neutral ledger only. Recipient lists, destinations, provider payloads, send actions, and execution actions are intentionally unavailable.</div><div class="table-responsive"><table class="table table-sm table-bordered mb-0"><thead><tr><th>Released</th><th>Status</th><th>Channel</th><th>Recipients</th><th>Releaser</th><th>Seal</th><th>Cancellation</th><th>Action</th></tr></thead><tbody>'
                    + history.map(function (row) {
                        const seal = row.run_integrity_seal_recorded ? '<span class="badge badge-success">Recorded</span>' : '<span class="badge badge-danger">Missing</span>';
                        const cancelAction = row.can_cancel && urls.dispatch_run_cancel ? '<button type="button" class="btn btn-xs btn-outline-warning js-campaign-dispatch-run-cancel" data-draft="' + attr(row.crm_campaign_draft_id) + '" data-id="' + attr(row.id) + '">Cancel run</button>' : '';
                        return '<tr><td>' + esc(row.released_at || row.created_at) + '</td><td>' + esc(row.release_state || row.status) + '</td><td>' + esc(row.channel) + '</td><td>' + Number(row.frozen_recipient_count || 0).toLocaleString() + '</td><td>' + esc(row.released_by_name) + '</td><td>' + seal + '</td><td>' + esc(row.cancellation_reason) + '</td><td>' + cancelAction + '</td></tr>';
                    }).join('') + '</tbody></table></div>';
            }
            function loadDispatchRunPreview(draftId) {
                const http = client(); if (!http || !urls.dispatch_run_preview) return notify('Release preview unavailable', 'The provider-neutral dispatch-run preview endpoint is unavailable.', 'error');
                currentDispatchRunDraftId = Number(draftId); $('#crmCampaignDispatchRunBody').html('<p class="text-muted mb-0">Verifying provider-neutral release boundary...</p>'); $('#crmCampaignDispatchRunModal').modal('show');
                http.get(endpoint(urls.dispatch_run_preview, draftId)).then(function (response) { $('#crmCampaignDispatchRunBody').html(dispatchRunPreviewHtml(response.data.preview || {})); }).catch(function (error) {
                    const payload = (error.response && error.response.data) || {}; $('#crmCampaignDispatchRunBody').html('<div class="alert alert-danger mb-0">' + esc(validationText(payload) || payload.message || 'Unable to verify provider-neutral dispatch-run readiness.') + '</div>');
                });
            }
            function loadDispatchRunHistory(draftId) {
                const http = client(); if (!http || !urls.dispatch_run_history) return notify('Run history unavailable', 'The provider-neutral dispatch-run history endpoint is unavailable.', 'error');
                currentDispatchRunDraftId = Number(draftId); $('#crmCampaignDispatchRunBody').html('<p class="text-muted mb-0">Loading aggregate provider-neutral run history...</p>'); $('#crmCampaignDispatchRunModal').modal('show');
                http.get(endpoint(urls.dispatch_run_history, draftId)).then(function (response) { $('#crmCampaignDispatchRunBody').html(dispatchRunHistoryHtml(response.data.history || [])); }).catch(function (error) {
                    const payload = (error.response && error.response.data) || {}; $('#crmCampaignDispatchRunBody').html('<div class="alert alert-danger mb-0">' + esc(validationText(payload) || payload.message || 'Unable to load provider-neutral dispatch-run history.') + '</div>');
                });
            }

            function dispatchExecutionEndpoint(template, draftId, runId, batchId) {
                return (template || '').replace('__DRAFT__', draftId).replace('__RUN__', runId || '').replace('__BATCH__', batchId || '');
            }
            function dispatchExecutionPreviewHtml(preview) {
                const run = preview.active_run;
                const activeBatch = preview.active_execution_batch;
                const runHtml = run ? '<div class="alert alert-info"><strong>Eligible released run #' + esc(run.id) + '</strong><br>Channel: ' + esc(run.channel) + ' · Frozen recipients: ' + Number(run.frozen_recipient_count || 0).toLocaleString() + ' · Released: ' + esc(run.released_at) + '</div>' : '';
                const activeBatchHtml = activeBatch ? '<div class="alert alert-warning"><strong>Active execution batch #' + esc(activeBatch.id) + '</strong><br>Channel: ' + esc(activeBatch.channel) + ' · Frozen recipients: ' + Number(activeBatch.frozen_recipient_count || 0).toLocaleString() + ' · Claimed: ' + esc(activeBatch.claimed_at) + '</div>' : '';
                const claimButton = preview.ready_for_claim && run && urls.dispatch_execution_store
                    ? '<button type="button" id="crmCampaignClaimDispatchExecution" class="btn btn-success mt-3" data-draft="' + attr(preview.draft_id) + '" data-run="' + attr(run.id) + '"><i class="feather-shield"></i> Claim manual execution batch</button>'
                    : '';
                return '<div class="alert alert-' + (preview.ready_for_claim ? 'success' : 'warning') + '"><strong>' + esc(preview.ready_for_claim_label) + '</strong><br>Approved recipients: ' + (preview.approved_recipient_count === null ? '—' : Number(preview.approved_recipient_count || 0).toLocaleString()) + '<br>Frozen recipients: ' + (preview.frozen_recipient_count === null ? '—' : Number(preview.frozen_recipient_count || 0).toLocaleString()) + '</div>'
                    + runHtml + activeBatchHtml + dispatchChecksHtml(preview.checks)
                    + '<div class="alert alert-info mt-3 mb-0">' + esc(preview.notice) + '</div>'
                    + claimButton;
            }
            function dispatchExecutionHistoryHtml(history) {
                if (!Array.isArray(history) || !history.length) return '<p class="text-muted mb-0">No provider-neutral manual execution batch has been claimed yet.</p>';
                return '<div class="alert alert-info">Aggregate execution ledger only. Recipient lists, destinations, provider payloads, send actions, provider execution actions, queues, retries, and delivery statuses are intentionally unavailable.</div><div class="table-responsive"><table class="table table-sm table-bordered mb-0"><thead><tr><th>Claimed</th><th>Status</th><th>Run</th><th>Channel</th><th>Recipients</th><th>Claimant</th><th>Seal</th><th>Cancellation</th><th>Action</th></tr></thead><tbody>'
                    + history.map(function (row) {
                        const seal = row.execution_integrity_seal_recorded ? '<span class="badge badge-success">Recorded</span>' : '<span class="badge badge-danger">Missing</span>';
                        const cancelAction = row.can_cancel && urls.dispatch_execution_cancel ? '<button type="button" class="btn btn-xs btn-outline-warning js-campaign-dispatch-execution-cancel" data-draft="' + attr(row.crm_campaign_draft_id) + '" data-id="' + attr(row.id) + '">Cancel batch</button>' : '';
                        return '<tr><td>' + esc(row.claimed_at || row.created_at) + '</td><td>' + esc(row.status) + '</td><td>#' + esc(row.crm_campaign_dispatch_run_id) + '</td><td>' + esc(row.channel) + '</td><td>' + Number(row.frozen_recipient_count || 0).toLocaleString() + '</td><td>' + esc(row.claimed_by_name) + '</td><td>' + seal + '</td><td>' + esc(row.cancellation_reason) + '</td><td>' + cancelAction + '</td></tr>';
                    }).join('') + '</tbody></table></div>';
            }
            function loadDispatchExecutionPreview(draftId) {
                const http = client(); if (!http || !urls.dispatch_execution_preview) return notify('Execution-claim preview unavailable', 'The provider-neutral execution-claim preview endpoint is unavailable.', 'error');
                currentDispatchExecutionDraftId = Number(draftId); $('#crmCampaignDispatchExecutionBatchBody').html('<p class="text-muted mb-0">Verifying provider-neutral single-consumer execution boundary...</p>'); $('#crmCampaignDispatchExecutionBatchModal').modal('show');
                http.get(endpoint(urls.dispatch_execution_preview, draftId)).then(function (response) { $('#crmCampaignDispatchExecutionBatchBody').html(dispatchExecutionPreviewHtml(response.data.preview || {})); }).catch(function (error) {
                    const payload = (error.response && error.response.data) || {}; $('#crmCampaignDispatchExecutionBatchBody').html('<div class="alert alert-danger mb-0">' + esc(validationText(payload) || payload.message || 'Unable to verify provider-neutral execution-claim readiness.') + '</div>');
                });
            }
            function loadDispatchExecutionHistory(draftId) {
                const http = client(); if (!http || !urls.dispatch_execution_history) return notify('Execution history unavailable', 'The provider-neutral execution-batch history endpoint is unavailable.', 'error');
                currentDispatchExecutionDraftId = Number(draftId); $('#crmCampaignDispatchExecutionBatchBody').html('<p class="text-muted mb-0">Loading aggregate provider-neutral execution history...</p>'); $('#crmCampaignDispatchExecutionBatchModal').modal('show');
                http.get(endpoint(urls.dispatch_execution_history, draftId)).then(function (response) { $('#crmCampaignDispatchExecutionBatchBody').html(dispatchExecutionHistoryHtml(response.data.history || [])); }).catch(function (error) {
                    const payload = (error.response && error.response.data) || {}; $('#crmCampaignDispatchExecutionBatchBody').html('<div class="alert alert-danger mb-0">' + esc(validationText(payload) || payload.message || 'Unable to load provider-neutral execution-batch history.') + '</div>');
                });
            }
            function dispatchAttemptEndpoint(template, draftId, batchId, attemptId) {
                return (template || '').replace('__DRAFT__', draftId).replace('__BATCH__', batchId || '').replace('__ATTEMPT__', attemptId || '');
            }
            function dispatchAttemptPreviewHtml(preview) {
                const batch = preview.active_execution_batch;
                const activeAttempt = preview.active_attempt;
                const readiness = preview.provider_readiness || {};
                const batchHtml = batch ? '<div class="alert alert-info"><strong>Prepared Stage 24 execution batch #' + esc(batch.id) + '</strong><br>Channel: ' + esc(batch.channel) + ' · Recipients: ' + Number(batch.recipient_count || 0).toLocaleString() + ' · Claimed: ' + esc(batch.claimed_at) + '</div>' : '';
                const activeAttemptHtml = activeAttempt ? '<div class="alert alert-warning"><strong>Active bounded attempt #' + esc(activeAttempt.id) + '</strong><br>Attempt number: ' + Number(activeAttempt.attempt_number || 0).toLocaleString() + ' · Status: ' + esc(activeAttempt.status) + ' · Provider: ' + esc(activeAttempt.provider_key) + ' · Recipients: ' + Number(activeAttempt.recipient_count || 0).toLocaleString() + ' · Started: ' + esc(activeAttempt.started_at) + '<br>Requests: ' + Number(activeAttempt.provider_request_count || 0).toLocaleString() + ' · Succeeded: ' + Number(activeAttempt.provider_success_count || 0).toLocaleString() + ' · Failed: ' + Number(activeAttempt.provider_failure_count || 0).toLocaleString() + ' · Unknown: ' + Number(activeAttempt.provider_unknown_count || 0).toLocaleString() + '</div>' : '';
                const readinessHtml = '<div class="card mt-3"><div class="card-body py-2"><strong>Local provider readiness: ' + esc(readiness.ready_label) + '</strong><br><small class="text-muted">Channel: ' + esc(readiness.channel) + ' · Provider: ' + esc(readiness.provider_key) + ' · Remote connectivity probe performed: No</small>' + dispatchChecksHtml(readiness.checks) + '</div></div>';
                const isEmail = String(preview.channel || readiness.channel || '').toLowerCase() === 'email';
                const policyHtml = isEmail
                    ? '<div class="alert alert-secondary mt-3"><strong>Stage 31 non-sending Email ledger policy</strong><br><small>Immutable attempt-ledger preparation: Enabled · Real SMTP transport execution: Disabled · Remote SMTP connectivity probe: Disabled · Destination decryption for sending: Disabled · Recipient cap: ' + Number(preview.maximum_allowed_recipients || readiness.future_recipient_cap || 0).toLocaleString() + ' · Automatic retry: Disabled · Browser resend: Disabled · Queue and scheduler: Disabled</small></div>'
                    : '<div class="alert alert-secondary mt-3"><strong>Stage 27 bounded real-SMS policy</strong><br><small>Enabled channel: SMS only · Execution adapter: BulkSMSBD only · Request boundary: One recipient per HTTPS POST · Recipient cap: ' + Number(preview.maximum_allowed_recipients || readiness.future_recipient_cap || 0).toLocaleString() + ' · Timeout: ' + Number(preview.future_connect_timeout_seconds || readiness.future_connect_timeout_seconds || 0).toLocaleString() + 's connect / ' + Number(preview.future_total_timeout_seconds || readiness.future_total_timeout_seconds || 0).toLocaleString() + 's total · Automatic retry: Disabled · Queue, scheduler, webhook, polling, and delivery receipt mutation: Disabled · Event ledger ready: ' + ((preview.recipient_event_ledger_ready || readiness.recipient_event_ledger_ready) ? 'Yes' : 'No') + '</small></div>';
                const prepareButton = preview.ready_for_attempt_preparation && batch && urls.dispatch_attempt_store
                    ? '<button type="button" id="crmCampaignPrepareDispatchAttempt" class="btn btn-success mt-3 mr-2" data-draft="' + attr(preview.draft_id) + '" data-batch="' + attr(batch.id) + '"><i class="feather-shield"></i> ' + (isEmail ? 'Prepare non-sending Email attempt ledger' : 'Prepare bounded SMS attempt') + '</button>'
                    : '';
                const executeButton = activeAttempt && activeAttempt.can_execute && urls.dispatch_attempt_execute
                    ? '<button type="button" class="btn btn-danger mt-3 js-campaign-dispatch-attempt-execute" data-draft="' + attr(activeAttempt.crm_campaign_draft_id) + '" data-id="' + attr(activeAttempt.id) + '"><i class="feather-send"></i> Execute bounded SMS</button>'
                    : '';
                return '<div class="alert alert-' + (preview.ready_for_attempt_preparation ? 'success' : 'warning') + '"><strong>' + esc(preview.ready_for_attempt_preparation_label) + '</strong><br>Recipients: ' + (preview.recipient_count === null ? '—' : Number(preview.recipient_count || 0).toLocaleString()) + ' · Maximum allowed: ' + Number(preview.maximum_allowed_recipients || 0).toLocaleString() + '</div>'
                    + batchHtml + activeAttemptHtml + dispatchChecksHtml(preview.checks) + readinessHtml + policyHtml
                    + '<div class="alert alert-info mt-3 mb-0">' + esc(preview.notice) + '</div>'
                    + prepareButton + executeButton;
            }
            function dispatchAttemptHistoryHtml(history) {
                if (!Array.isArray(history) || !history.length) return '<p class="text-muted mb-0">No bounded manual provider-attempt ledger has been prepared yet.</p>';
                return '<div class="alert alert-info">Aggregate attempt history only. Recipient destinations, destination samples, hashes, ciphertext, credentials, endpoint URLs, provider payloads, raw responses, retry actions, queues, schedules, webhooks, and polling controls are intentionally unavailable.</div><div class="table-responsive"><table class="table table-sm table-bordered mb-0"><thead><tr><th>Prepared</th><th>Status</th><th>Attempt</th><th>Batch</th><th>Provider</th><th>Recipients</th><th>Preparer</th><th>Started by</th><th>Requests</th><th>Success</th><th>Failed</th><th>Unknown</th><th>Completed</th><th>Safe summary</th><th>Action</th></tr></thead><tbody>'
                    + history.map(function (row) {
                        const cancelAction = row.can_cancel && urls.dispatch_attempt_cancel ? '<button type="button" class="btn btn-xs btn-outline-warning mr-1 mb-1 js-campaign-dispatch-attempt-cancel" data-draft="' + attr(row.crm_campaign_draft_id) + '" data-id="' + attr(row.id) + '">Cancel attempt</button>' : '';
                        const executeAction = row.can_execute && urls.dispatch_attempt_execute ? '<button type="button" class="btn btn-xs btn-danger mr-1 mb-1 js-campaign-dispatch-attempt-execute" data-draft="' + attr(row.crm_campaign_draft_id) + '" data-id="' + attr(row.id) + '">Execute SMS</button>' : '';
                        return '<tr><td>' + esc(row.prepared_at || row.created_at) + '</td><td>' + esc(row.status) + '</td><td>#' + esc(row.attempt_number) + '</td><td>#' + esc(row.crm_campaign_dispatch_execution_batch_id) + '</td><td>' + esc(row.provider_key) + '</td><td>' + Number(row.recipient_count || 0).toLocaleString() + '</td><td>' + esc(row.prepared_by_name) + '</td><td>' + esc(row.started_by_name) + '</td><td>' + Number(row.provider_request_count || 0).toLocaleString() + '</td><td>' + Number(row.provider_success_count || 0).toLocaleString() + '</td><td>' + Number(row.provider_failure_count || 0).toLocaleString() + '</td><td>' + Number(row.provider_unknown_count || 0).toLocaleString() + '</td><td>' + esc(row.completed_at) + '</td><td>' + esc(row.failure_summary) + '</td><td>' + executeAction + cancelAction + '</td></tr>';
                    }).join('') + '</tbody></table></div>';
            }
            function loadDispatchAttemptPreview(draftId) {
                const http = client(); if (!http || !urls.dispatch_attempt_preview) return notify('Provider readiness unavailable', 'The bounded provider-readiness preview endpoint is unavailable.', 'error');
                currentDispatchAttemptDraftId = Number(draftId); $('#crmCampaignDispatchAttemptBody').html('<p class="text-muted mb-0">Verifying local provider readiness and immutable attempt boundary...</p>'); $('#crmCampaignDispatchAttemptModal').modal('show');
                http.get(endpoint(urls.dispatch_attempt_preview, draftId)).then(function (response) { $('#crmCampaignDispatchAttemptBody').html(dispatchAttemptPreviewHtml(response.data.preview || {})); }).catch(function (error) {
                    const payload = (error.response && error.response.data) || {}; $('#crmCampaignDispatchAttemptBody').html('<div class="alert alert-danger mb-0">' + esc(validationText(payload) || payload.message || 'Unable to verify bounded provider readiness.') + '</div>');
                });
            }
            function loadDispatchAttemptHistory(draftId) {
                const http = client(); if (!http || !urls.dispatch_attempt_history) return notify('Attempt history unavailable', 'The bounded provider-attempt history endpoint is unavailable.', 'error');
                currentDispatchAttemptDraftId = Number(draftId); $('#crmCampaignDispatchAttemptBody').html('<p class="text-muted mb-0">Loading aggregate bounded provider-attempt history...</p>'); $('#crmCampaignDispatchAttemptModal').modal('show');
                http.get(endpoint(urls.dispatch_attempt_history, draftId)).then(function (response) { $('#crmCampaignDispatchAttemptBody').html(dispatchAttemptHistoryHtml(response.data.history || [])); }).catch(function (error) {
                    const payload = (error.response && error.response.data) || {}; $('#crmCampaignDispatchAttemptBody').html('<div class="alert alert-danger mb-0">' + esc(validationText(payload) || payload.message || 'Unable to load bounded provider-attempt history.') + '</div>');
                });
            }
            function setAudienceEstimate(preview) {
                $('#crmCampaignDraftAudienceEstimate').html('<strong>' + Number(preview.estimated_recipients || 0).toLocaleString() + '</strong> estimated recipients. <span class="text-muted">' + esc(preview.segment_name) + ' · ' + esc(preview.segment_visibility) + ' source · read only</span>');
                if (preview.segment_visibility === 'private') $('#crmCampaignDraftFormVisibility').val('private');
            }
            function selectAudienceOption(id, text) {
                const select = $('#crmCampaignDraftAudience');
                if (!select.length || !id) return;
                const option = new Option(text || ('Saved segment #' + id), id, true, true);
                select.append(option).trigger('change');
            }
            function resetDraftForm() {
                editingDraftId = null;
                if ($('#crmCampaignDraftForm').length) $('#crmCampaignDraftForm')[0].reset();
                $('#crmCampaignDraftAudience').empty().trigger('change');
                $('#crmCampaignDraftAudienceEstimate').text('Select an audience segment to calculate a live read-only estimate.');
                $('#crmCampaignDraftFormTitle').text('New Campaign Draft');
                $('#crmCampaignDraftSubmit').html('<i class="feather-save"></i> Save draft');
                showFormValidation('');
            }
            function draftPayload() {
                return {
                    name:$('#crmCampaignDraftName').val(),
                    description:$('#crmCampaignDraftDescription').val(),
                    planned_channel:$('#crmCampaignDraftFormChannel').val(),
                    audience_saved_segment_id:$('#crmCampaignDraftAudience').val(),
                    subject:$('#crmCampaignDraftSubject').val(),
                    message_body:$('#crmCampaignDraftMessageBody').val(),
                    visibility:$('#crmCampaignDraftFormVisibility').val()
                };
            }
            function endpoint(template, id) { return (template || '').replace('__DRAFT__', id); }
            function postGovernance(url, payload, button, successTitle) {
                if (governanceBusy) return;
                const http = client();
                if (!http || !url) return notify('Action unavailable', 'The requested governance endpoint is unavailable.', 'error');
                governanceBusy = true; button.prop('disabled', true);
                http.post(url, payload || {}).then(function (response) {
                    notify(successTitle, response.data.message || 'Campaign governance action completed.', 'success');
                    table.ajax.reload(null, false);
                }).catch(function (error) {
                    const payload = (error.response && error.response.data) || {};
                    notify('Governance action failed', validationText(payload) || payload.message || 'Unable to complete this campaign governance action.', 'error');
                }).finally(function () { governanceBusy = false; button.prop('disabled', false); });
            }
            function notePrompt(title, text, required, confirmText) {
                if (typeof Swal !== 'undefined') {
                    return Swal.fire({title:title, text:text || '', input:'textarea', inputPlaceholder:required ? 'Review note is required' : 'Optional note', showCancelButton:true, confirmButtonText:confirmText, confirmButtonColor:'#6d28d9', inputValidator:function (value) { if (required && !String(value || '').trim()) return 'A review note is required.'; }});
                }
                const note = window.prompt(text || title, '');
                return Promise.resolve({isConfirmed:note !== null && (!required || String(note).trim() !== ''), value:note});
            }

            if ($('#crmCampaignDraftAudience').length && urls.saved_options) {
                $('#crmCampaignDraftAudience').select2({
                    dropdownParent:$('#crmCampaignDraftFormModal'), placeholder:'Select an active saved segment', allowClear:true, width:'100%',
                    ajax:{
                        delay:250,
                        transport:function (params, success, failure) {
                            const http = client(); if (!http) { failure(); return {abort:function(){}}; }
                            const request = http.get(urls.saved_options, {params:params.data || {}});
                            request.then(function (response) { success(response.data); }).catch(failure);
                            return {abort:function(){}};
                        },
                        data:function (params) { return {q:params.term || ''}; },
                        processResults:function (data) { return data; }
                    }
                });
            }

            const table = $('#crmCampaignDraftTable').DataTable({
                processing:true, serverSide:true, searching:true, ordering:false, pageLength:25,
                ajax:function (data, callback) {
                    const http = client(); showValidation('');
                    if (!http) { if (!tableErrorVisible) { tableErrorVisible = true; notify('Axios unavailable', 'The shared window.AppAxios client is required.', 'error'); } callback(emptyTable(data.draw)); return; }
                    data.status = $('#crmCampaignDraftStatus').val();
                    data.visibility = $('#crmCampaignDraftVisibility').val();
                    data.planned_channel = $('#crmCampaignDraftChannel').val();
                    http.get(urls.data, {params:data}).then(function (response) { tableErrorVisible = false; callback(response.data); }).catch(function (error) {
                        const payload = (error.response && error.response.data) || {}; const details = validationText(payload); callback(emptyTable(data.draw));
                        if (error.response && error.response.status === 422) showValidation(details || payload.message || 'Please review the selected filters.');
                        if (!tableErrorVisible) { tableErrorVisible = true; notify('Campaign drafts load failed', details || payload.message || 'Unable to load CRM campaign drafts.', 'error'); }
                    });
                },
                columns:[
                    {data:'draft.name'},
                    {data:'draft.audience_segment_name_snapshot', defaultContent:''},
                    {data:'draft.planned_channel', render:function (value, type, row) { const draft = row && row.draft ? row.draft : {}; return badge(draft.planned_channel_label || channelLabel(value), draft.legacy_disabled_channel ? 'secondary' : (value === 'sms' ? 'success' : 'warning')); }},
                    {data:'draft.visibility', render:function (value) { return badge(value || '—'); }},
                    {data:'draft.status', render:function (value) { return statusBadge(value); }},
                    {data:'draft.creator', defaultContent:''},
                    {data:'draft.updated_at', defaultContent:''},
                    {data:'draft', render:function (draft) { return actionButtons(draft); }}
                ]
            });

            $('#crmCampaignDraftStatus,#crmCampaignDraftVisibility,#crmCampaignDraftChannel').on('change', function () { table.ajax.reload(); });
            $('#resetCrmCampaignDraftFilters').on('click', function () { $('#crmCampaignDraftStatus').val('draft'); $('#crmCampaignDraftVisibility,#crmCampaignDraftChannel').val(''); showValidation(''); table.ajax.reload(); });
            $('#createCrmCampaignDraft').on('click', function () { resetDraftForm(); $('#crmCampaignDraftFormModal').modal('show'); });

            $('#previewCrmCampaignDraftAudience').on('click', function () {
                const id = $('#crmCampaignDraftAudience').val(); const http = client();
                if (!id) return showFormValidation('Select an audience saved segment first.');
                if (!http || !urls.saved_preview) return notify('Audience preview unavailable', 'The read-only saved-segment preview endpoint is unavailable.', 'error');
                const button = $(this); button.prop('disabled', true); showFormValidation('');
                http.get(urls.saved_preview.replace('__SEGMENT__', id)).then(function (response) { setAudienceEstimate(response.data.preview || {}); }).catch(function (error) {
                    const payload = (error.response && error.response.data) || {}; showFormValidation(validationText(payload) || payload.message || 'Unable to preview this saved segment.');
                }).finally(function () { button.prop('disabled', false); });
            });

            $('#crmCampaignDraftForm').on('submit', function (event) {
                event.preventDefault(); if (formBusy) return;
                const http = client(); const url = editingDraftId ? endpoint(urls.update, editingDraftId) : urls.store;
                if (!http || !url) return notify('Unable to save draft', 'The campaign draft endpoint is unavailable.', 'error');
                formBusy = true; $('#crmCampaignDraftSubmit').prop('disabled', true); showFormValidation('');
                http.post(url, draftPayload()).then(function (response) {
                    $('#crmCampaignDraftFormModal').modal('hide'); notify(editingDraftId ? 'Draft updated' : 'Draft created', response.data.message, 'success'); table.ajax.reload(null, false);
                }).catch(function (error) {
                    const payload = (error.response && error.response.data) || {}; const details = validationText(payload) || payload.message || 'Unable to save this CRM campaign draft.';
                    if (error.response && error.response.status === 422) showFormValidation(details); else notify('Draft save failed', details, 'error');
                }).finally(function () { formBusy = false; $('#crmCampaignDraftSubmit').prop('disabled', false); });
            });

            $(document).on('click', '.js-campaign-details', function () {
                const http = client(); const id = $(this).data('id');
                if (!http) return notify('Unable to load details', 'The shared window.AppAxios client is required.', 'error');
                $('#crmCampaignDraftDetailsBody').html('<p class="text-muted mb-0">Loading...</p>'); $('#crmCampaignDraftDetailsModal').modal('show');
                http.get(endpoint(urls.show, id)).then(function (response) { $('#crmCampaignDraftDetailsBody').html(detailsHtml(response.data.draft || {})); }).catch(function (error) {
                    const payload = (error.response && error.response.data) || {}; $('#crmCampaignDraftDetailsBody').html('<div class="alert alert-danger mb-0">' + esc(validationText(payload) || payload.message || 'Unable to load campaign draft details.') + '</div>');
                });
            });

            $(document).on('click', '.js-campaign-preview', function () {
                const http = client(); const id = $(this).data('id');
                if (!http) return notify('Audience preview unavailable', 'The shared window.AppAxios client is required.', 'error');
                $('#crmCampaignPreviewBody').html('<p class="text-muted mb-0">Calculating read-only estimate...</p>'); $('#crmCampaignPreviewModal').modal('show');
                http.get(endpoint(urls.preview, id)).then(function (response) { $('#crmCampaignPreviewBody').html(previewHtml(response.data.preview || {})); }).catch(function (error) {
                    const payload = (error.response && error.response.data) || {}; $('#crmCampaignPreviewBody').html('<div class="alert alert-danger mb-0">' + esc(validationText(payload) || payload.message || 'Unable to preview this campaign draft audience.') + '</div>');
                });
            });

            $(document).on('click', '.js-campaign-preflight', function () {
                const http = client(); const id = $(this).data('id');
                if (!http) return notify('Preflight unavailable', 'The shared window.AppAxios client is required.', 'error');
                $('#crmCampaignPreflightBody').html('<p class="text-muted mb-0">Calculating read-only readiness checks...</p>'); $('#crmCampaignPreflightModal').modal('show');
                http.get(endpoint(urls.preflight, id)).then(function (response) { $('#crmCampaignPreflightBody').html(preflightHtml(response.data.preflight || {})); }).catch(function (error) {
                    const payload = (error.response && error.response.data) || {}; $('#crmCampaignPreflightBody').html('<div class="alert alert-danger mb-0">' + esc(validationText(payload) || payload.message || 'Unable to calculate send-readiness preflight.') + '</div>');
                });
            });

            $(document).on('click', '.js-campaign-history', function () {
                const http = client(); const id = $(this).data('id');
                if (!http) return notify('History unavailable', 'The shared window.AppAxios client is required.', 'error');
                $('#crmCampaignApprovalHistoryBody').html('<p class="text-muted mb-0">Loading immutable approval history...</p>'); $('#crmCampaignApprovalHistoryModal').modal('show');
                http.get(endpoint(urls.history, id)).then(function (response) { $('#crmCampaignApprovalHistoryBody').html(historyHtml(response.data.history || [])); }).catch(function (error) {
                    const payload = (error.response && error.response.data) || {}; $('#crmCampaignApprovalHistoryBody').html('<div class="alert alert-danger mb-0">' + esc(validationText(payload) || payload.message || 'Unable to load approval history.') + '</div>');
                });
            });

            $(document).on('click', '.js-campaign-dispatch-preview', function () { loadDispatchPreview($(this).data('id')); });
            $(document).on('click', '.js-campaign-dispatch-history', function () { loadDispatchHistory($(this).data('id')); });

            $(document).on('click', '.js-campaign-dispatch-run-preview', function () { loadDispatchRunPreview($(this).data('id')); });
            $(document).on('click', '.js-campaign-dispatch-run-history', function () { loadDispatchRunHistory($(this).data('id')); });
            $(document).on('click', '.js-campaign-dispatch-execution-preview', function () { loadDispatchExecutionPreview($(this).data('id')); });
            $(document).on('click', '.js-campaign-dispatch-execution-history', function () { loadDispatchExecutionHistory($(this).data('id')); });
            $(document).on('click', '.js-campaign-dispatch-attempt-preview', function () { loadDispatchAttemptPreview($(this).data('id')); });
            $(document).on('click', '.js-campaign-dispatch-attempt-history', function () { loadDispatchAttemptHistory($(this).data('id')); });

            $(document).on('click', '#crmCampaignReleaseDispatchRun', function () {
                if (dispatchRunBusy) return;
                const button = $(this); const draftId = button.data('draft'); const preparationId = button.data('preparation');
                const confirm = typeof Swal !== 'undefined'
                    ? Swal.fire({title:'Release provider-neutral dispatch run?', text:'This permanently consumes the frozen preparation and creates an auditable immutable run ledger only. No message will be sent, queued, or executed.', icon:'question', showCancelButton:true, confirmButtonText:'Release run', confirmButtonColor:'#15803d'})
                    : Promise.resolve({isConfirmed:window.confirm('Release the provider-neutral dispatch run? No message will be sent, queued, or executed.')});
                confirm.then(function (result) {
                    if (!result.isConfirmed) return;
                    const http = client(); if (!http || !urls.dispatch_run_store) return notify('Release unavailable', 'The provider-neutral dispatch-run release endpoint is unavailable.', 'error');
                    dispatchRunBusy = true; button.prop('disabled', true);
                    http.post(dispatchRunEndpoint(urls.dispatch_run_store, draftId, preparationId), {}).then(function (response) {
                        notify('Dispatch run released', response.data.message || 'Provider-neutral dispatch run released.', 'success'); table.ajax.reload(null, false); loadDispatchRunPreview(draftId);
                    }).catch(function (error) {
                        const payload = (error.response && error.response.data) || {}; $('#crmCampaignDispatchRunBody').prepend('<div class="alert alert-danger">' + esc(validationText(payload) || payload.message || 'Unable to release provider-neutral dispatch run.') + '</div>');
                    }).finally(function () { dispatchRunBusy = false; button.prop('disabled', false); });
                });
            });

            $(document).on('click', '.js-campaign-dispatch-run-cancel', function () {
                if (dispatchRunBusy) return;
                const button = $(this);
                notePrompt('Cancel provider-neutral dispatch run?', 'Provide an audit reason. The consumed frozen preparation remains immutable and cannot be reused.', true, 'Cancel run').then(function (result) {
                    if (!result.isConfirmed) return;
                    const http = client(); if (!http || !urls.dispatch_run_cancel) return notify('Cancellation unavailable', 'The provider-neutral dispatch-run cancellation endpoint is unavailable.', 'error');
                    dispatchRunBusy = true; button.prop('disabled', true);
                    http.post(dispatchRunEndpoint(urls.dispatch_run_cancel, button.data('draft'), null, button.data('id')), {reason:result.value || ''}).then(function (response) {
                        notify('Dispatch run cancelled', response.data.message || 'Provider-neutral dispatch run cancelled.', 'success'); table.ajax.reload(null, false); loadDispatchRunHistory(button.data('draft'));
                    }).catch(function (error) {
                        const payload = (error.response && error.response.data) || {}; $('#crmCampaignDispatchRunBody').prepend('<div class="alert alert-danger">' + esc(validationText(payload) || payload.message || 'Unable to cancel provider-neutral dispatch run.') + '</div>');
                    }).finally(function () { dispatchRunBusy = false; button.prop('disabled', false); });
                });
            });


            $(document).on('click', '#crmCampaignClaimDispatchExecution', function () {
                if (dispatchExecutionBusy) return;
                const button = $(this); const draftId = button.data('draft'); const runId = button.data('run');
                const confirm = typeof Swal !== 'undefined'
                    ? Swal.fire({title:'Claim provider-neutral manual execution batch?', text:'This permanently consumes the released run and creates an immutable single-consumer execution ledger only. No message will be sent, queued, or executed.', icon:'question', showCancelButton:true, confirmButtonText:'Claim execution batch', confirmButtonColor:'#15803d'})
                    : Promise.resolve({isConfirmed:window.confirm('Claim the provider-neutral manual execution batch? No message will be sent, queued, or executed.')});
                confirm.then(function (result) {
                    if (!result.isConfirmed) return;
                    const http = client(); if (!http || !urls.dispatch_execution_store) return notify('Claim unavailable', 'The provider-neutral execution-batch claim endpoint is unavailable.', 'error');
                    dispatchExecutionBusy = true; button.prop('disabled', true);
                    http.post(dispatchExecutionEndpoint(urls.dispatch_execution_store, draftId, runId), {}).then(function (response) {
                        notify('Execution batch claimed', response.data.message || 'Provider-neutral manual execution batch claimed.', 'success'); table.ajax.reload(null, false); loadDispatchExecutionPreview(draftId);
                    }).catch(function (error) {
                        const payload = (error.response && error.response.data) || {}; $('#crmCampaignDispatchExecutionBatchBody').prepend('<div class="alert alert-danger">' + esc(validationText(payload) || payload.message || 'Unable to claim provider-neutral manual execution batch.') + '</div>');
                    }).finally(function () { dispatchExecutionBusy = false; button.prop('disabled', false); });
                });
            });

            $(document).on('click', '.js-campaign-dispatch-execution-cancel', function () {
                if (dispatchExecutionBusy) return;
                const button = $(this);
                notePrompt('Cancel provider-neutral execution batch?', 'Provide an audit reason. The claimed released run remains consumed and cannot be reused. A fresh preparation and release chain is required for another attempt.', true, 'Cancel batch').then(function (result) {
                    if (!result.isConfirmed) return;
                    const http = client(); if (!http || !urls.dispatch_execution_cancel) return notify('Cancellation unavailable', 'The provider-neutral execution-batch cancellation endpoint is unavailable.', 'error');
                    dispatchExecutionBusy = true; button.prop('disabled', true);
                    http.post(dispatchExecutionEndpoint(urls.dispatch_execution_cancel, button.data('draft'), null, button.data('id')), {reason:result.value || ''}).then(function (response) {
                        notify('Execution batch cancelled', response.data.message || 'Provider-neutral manual execution batch cancelled.', 'success'); table.ajax.reload(null, false); loadDispatchExecutionHistory(button.data('draft'));
                    }).catch(function (error) {
                        const payload = (error.response && error.response.data) || {}; $('#crmCampaignDispatchExecutionBatchBody').prepend('<div class="alert alert-danger">' + esc(validationText(payload) || payload.message || 'Unable to cancel provider-neutral manual execution batch.') + '</div>');
                    }).finally(function () { dispatchExecutionBusy = false; button.prop('disabled', false); });
                });
            });

            $(document).on('click', '#crmCampaignPrepareDispatchAttempt', function () {
                if (dispatchAttemptBusy) return;
                const button = $(this); const draftId = button.data('draft'); const batchId = button.data('batch');
                const confirm = typeof Swal !== 'undefined'
                    ? Swal.fire({title:'Prepare bounded provider-attempt ledger?', text:'This validates local server-side provider configuration and creates immutable recipient-attempt identities only. No destination is decrypted and no message is sent during preparation. Real transport execution, when available for the selected channel, remains a separate explicitly confirmed action. Email remains non-sending.', icon:'question', showCancelButton:true, confirmButtonText:'Prepare attempt ledger', confirmButtonColor:'#15803d'})
                    : Promise.resolve({isConfirmed:window.confirm('Prepare the bounded provider-attempt ledger? No message is sent during preparation.')});
                confirm.then(function (result) {
                    if (!result.isConfirmed) return;
                    const http = client(); if (!http || !urls.dispatch_attempt_store) return notify('Attempt preparation unavailable', 'The bounded provider-attempt preparation endpoint is unavailable.', 'error');
                    dispatchAttemptBusy = true; button.prop('disabled', true);
                    http.post(dispatchAttemptEndpoint(urls.dispatch_attempt_store, draftId, batchId), {}).then(function (response) {
                        notify('Attempt ledger prepared', response.data.message || 'Bounded manual provider-attempt ledger prepared. No message was sent during preparation.', 'success'); table.ajax.reload(null, false); loadDispatchAttemptPreview(draftId);
                    }).catch(function (error) {
                        const payload = (error.response && error.response.data) || {}; $('#crmCampaignDispatchAttemptBody').prepend('<div class="alert alert-danger">' + esc(validationText(payload) || payload.message || 'Unable to prepare the bounded manual provider-attempt ledger.') + '</div>');
                    }).finally(function () { dispatchAttemptBusy = false; button.prop('disabled', false); });
                });
            });

            $(document).on('click', '.js-campaign-dispatch-attempt-execute', function () {
                if (dispatchAttemptBusy) return;
                const button = $(this); const draftId = button.data('draft'); const attemptId = button.data('id');
                if (typeof Swal === 'undefined') return notify('Execution blocked', 'SweetAlert2 is required for the typed SEND SMS confirmation. No SMS was sent.', 'error');
                Swal.fire({
                    title:'Execute bounded real SMS?',
                    html:'This sends real SMS through the hardened BulkSMSBD adapter only. The server allows at most five recipients, sends one recipient per HTTPS POST, records append-only events, and never retries automatically.<br><br>Type <strong>SEND SMS</strong> exactly to continue.',
                    icon:'warning',
                    input:'text',
                    inputLabel:'Typed confirmation phrase',
                    inputPlaceholder:'SEND SMS',
                    showCancelButton:true,
                    confirmButtonText:'Execute bounded SMS',
                    confirmButtonColor:'#b91c1c',
                    focusConfirm:false,
                    preConfirm:function (value) {
                        if (String(value || '').trim() !== 'SEND SMS') {
                            Swal.showValidationMessage('Type SEND SMS exactly.');
                            return false;
                        }
                        return String(value).trim();
                    }
                }).then(function (result) {
                    if (!result.isConfirmed) return;
                    const http = client(); if (!http || !urls.dispatch_attempt_execute) return notify('Execution unavailable', 'The bounded SMS execution endpoint is unavailable. No SMS was sent.', 'error');
                    dispatchAttemptBusy = true; button.prop('disabled', true);
                    http.post(dispatchAttemptEndpoint(urls.dispatch_attempt_execute, draftId, null, attemptId), {confirmation_phrase:result.value}).then(function (response) {
                        notify('Bounded SMS execution result', response.data.message || 'Bounded BulkSMSBD SMS execution finished. Review aggregate counters.', response.data.executed ? 'success' : 'info');
                        table.ajax.reload(null, false); loadDispatchAttemptHistory(draftId);
                    }).catch(function (error) {
                        const payload = (error.response && error.response.data) || {}; $('#crmCampaignDispatchAttemptBody').prepend('<div class="alert alert-danger">' + esc(validationText(payload) || payload.message || 'Unable to execute the bounded SMS attempt. No browser retry was issued.') + '</div>');
                    }).finally(function () { dispatchAttemptBusy = false; button.prop('disabled', false); });
                });
            });

            $(document).on('click', '.js-campaign-dispatch-attempt-cancel', function () {
                if (dispatchAttemptBusy) return;
                const button = $(this);
                notePrompt('Cancel prepared provider-attempt ledger?', 'Provide an audit reason. Immutable recipient-attempt identity rows remain in history. No provider call has occurred.', true, 'Cancel attempt').then(function (result) {
                    if (!result.isConfirmed) return;
                    const http = client(); if (!http || !urls.dispatch_attempt_cancel) return notify('Attempt cancellation unavailable', 'The prepared provider-attempt cancellation endpoint is unavailable.', 'error');
                    dispatchAttemptBusy = true; button.prop('disabled', true);
                    http.post(dispatchAttemptEndpoint(urls.dispatch_attempt_cancel, button.data('draft'), null, button.data('id')), {reason:result.value || ''}).then(function (response) {
                        notify('Attempt ledger cancelled', response.data.message || 'Prepared manual provider-attempt ledger cancelled.', 'success'); table.ajax.reload(null, false); loadDispatchAttemptHistory(button.data('draft'));
                    }).catch(function (error) {
                        const payload = (error.response && error.response.data) || {}; $('#crmCampaignDispatchAttemptBody').prepend('<div class="alert alert-danger">' + esc(validationText(payload) || payload.message || 'Unable to cancel the prepared manual provider-attempt ledger.') + '</div>');
                    }).finally(function () { dispatchAttemptBusy = false; button.prop('disabled', false); });
                });
            });

            $(document).on('click', '#crmCampaignPrepareDispatch', function () {
                if (dispatchBusy) return;
                const button = $(this); const draftId = button.data('id');
                const confirm = typeof Swal !== 'undefined'
                    ? Swal.fire({title:'Freeze immutable recipient snapshot?', text:'This creates an auditable encrypted preparation record only. No message will be sent.', icon:'question', showCancelButton:true, confirmButtonText:'Freeze preparation', confirmButtonColor:'#15803d'})
                    : Promise.resolve({isConfirmed:window.confirm('Freeze immutable dispatch preparation? No message will be sent.')});
                confirm.then(function (result) {
                    if (!result.isConfirmed) return;
                    const http = client(); if (!http || !urls.dispatch_store) return notify('Preparation unavailable', 'The dispatch-preparation endpoint is unavailable.', 'error');
                    dispatchBusy = true; button.prop('disabled', true);
                    http.post(endpoint(urls.dispatch_store, draftId), {}).then(function (response) {
                        notify('Preparation frozen', response.data.message || 'Immutable dispatch preparation created.', 'success'); table.ajax.reload(null, false); loadDispatchPreview(draftId);
                    }).catch(function (error) {
                        const payload = (error.response && error.response.data) || {}; $('#crmCampaignDispatchPreparationBody').prepend('<div class="alert alert-danger">' + esc(validationText(payload) || payload.message || 'Unable to freeze dispatch preparation.') + '</div>');
                    }).finally(function () { dispatchBusy = false; button.prop('disabled', false); });
                });
            });

            function transitionDispatchPreparation(action, draftId, preparationId, reason, button) {
                if (dispatchBusy) return;
                const template = action === 'cancel' ? urls.dispatch_cancel : urls.dispatch_invalidate;
                const http = client(); if (!http || !template) return notify('Preparation action unavailable', 'The requested dispatch-preparation endpoint is unavailable.', 'error');
                dispatchBusy = true; button.prop('disabled', true);
                http.post(dispatchPreparationEndpoint(template, draftId, preparationId), {reason:reason || ''}).then(function (response) {
                    notify('Preparation updated', response.data.message || 'Dispatch preparation updated.', 'success'); table.ajax.reload(null, false); loadDispatchHistory(draftId);
                }).catch(function (error) {
                    const payload = (error.response && error.response.data) || {}; $('#crmCampaignDispatchPreparationBody').prepend('<div class="alert alert-danger">' + esc(validationText(payload) || payload.message || 'Unable to update dispatch preparation.') + '</div>');
                }).finally(function () { dispatchBusy = false; button.prop('disabled', false); });
            }

            $(document).on('click', '.js-campaign-dispatch-cancel,.js-campaign-dispatch-invalidate', function () {
                if (dispatchBusy) return;
                const button = $(this); const action = button.hasClass('js-campaign-dispatch-cancel') ? 'cancel' : 'invalidate';
                notePrompt(action === 'cancel' ? 'Cancel dispatch preparation?' : 'Invalidate dispatch preparation?', 'Provide an audit reason. Frozen recipient rows remain immutable in history.', true, action === 'cancel' ? 'Cancel preparation' : 'Invalidate preparation').then(function (result) {
                    if (result.isConfirmed) transitionDispatchPreparation(action, button.data('draft'), button.data('id'), result.value || '', button);
                });
            });

            $(document).on('click', '.js-campaign-edit', function () {
                const http = client(); const id = $(this).data('id');
                if (!http || !urls.update) return notify('Unable to edit draft', 'The campaign draft update endpoint is unavailable.', 'error');
                resetDraftForm(); editingDraftId = Number(id); $('#crmCampaignDraftFormTitle').text('Edit Campaign Draft'); $('#crmCampaignDraftSubmit').html('<i class="feather-save"></i> Update draft');
                $('#crmCampaignDraftFormModal').modal('show');
                http.get(endpoint(urls.show, id)).then(function (response) {
                    const draft = response.data.draft || {};
                    $('#crmCampaignDraftName').val(draft.name || ''); $('#crmCampaignDraftDescription').val(draft.description || ''); $('#crmCampaignDraftFormChannel').val(draft.planned_channel || '');
                    $('#crmCampaignDraftSubject').val(draft.subject || ''); $('#crmCampaignDraftMessageBody').val(draft.message_body || ''); $('#crmCampaignDraftFormVisibility').val(draft.visibility || 'private');
                    selectAudienceOption(draft.audience_saved_segment_id, draft.audience_segment_name_snapshot + ' (' + draft.audience_segment_visibility_snapshot + ')');
                    $('#crmCampaignDraftAudienceEstimate').text('Use Preview audience to recalculate the live read-only estimate from this draft source.');
                }).catch(function (error) {
                    const payload = (error.response && error.response.data) || {}; showFormValidation(validationText(payload) || payload.message || 'Unable to load this campaign draft.');
                });
            });

            $(document).on('click', '.js-campaign-submit', function () {
                if (governanceBusy) return;
                const button = $(this); const id = button.data('id'); const name = button.data('name');
                const confirm = typeof Swal !== 'undefined'
                    ? Swal.fire({title:'Submit campaign for review?', text:String(name || ''), icon:'question', showCancelButton:true, confirmButtonText:'Submit for review', confirmButtonColor:'#6d28d9'})
                    : Promise.resolve({isConfirmed:window.confirm('Submit this campaign for review?')});
                confirm.then(function (result) { if (result.isConfirmed) postGovernance(endpoint(urls.submit, id), {}, button, 'Submitted for review'); });
            });

            $(document).on('click', '.js-campaign-approve', function () {
                if (governanceBusy) return;
                const button = $(this); const id = button.data('id');
                notePrompt('Approve campaign draft?', 'Optional approval note. Approval remains readiness-only and does not send any message.', false, 'Approve').then(function (result) {
                    if (result.isConfirmed) postGovernance(endpoint(urls.approve, id), {review_note:result.value || ''}, button, 'Campaign approved');
                });
            });

            $(document).on('click', '.js-campaign-reject', function () {
                if (governanceBusy) return;
                const button = $(this); const id = button.data('id');
                notePrompt('Reject campaign draft?', 'Provide a review note explaining the rejection.', true, 'Reject').then(function (result) {
                    if (result.isConfirmed) postGovernance(endpoint(urls.reject, id), {review_note:result.value || ''}, button, 'Campaign rejected');
                });
            });

            $(document).on('click', '.js-campaign-return', function () {
                if (governanceBusy) return;
                const button = $(this); const id = button.data('id');
                notePrompt('Return campaign to draft planning?', 'This auditable action clears the active approval so the creator can edit and resubmit.', false, 'Return to draft').then(function (result) {
                    if (result.isConfirmed) postGovernance(endpoint(urls.return_to_draft, id), {review_note:result.value || ''}, button, 'Returned to draft');
                });
            });

            $(document).on('click', '.js-campaign-refresh-audience', function () {
                if (governanceBusy) return;
                const button = $(this); const id = button.data('id'); const name = button.data('name');
                const confirm = typeof Swal !== 'undefined'
                    ? Swal.fire({title:'Refresh saved audience snapshot?', text:String(name || '') + ' will require review submission and independent reapproval before dispatch preparation.', icon:'warning', showCancelButton:true, confirmButtonText:'Refresh audience', confirmButtonColor:'#0e7490'})
                    : Promise.resolve({isConfirmed:window.confirm('Refresh the saved audience snapshot? Independent reapproval will be required.')});
                confirm.then(function (result) { if (result.isConfirmed) postGovernance(endpoint(urls.refresh_audience_snapshot, id), {}, button, 'Audience snapshot refreshed'); });
            });

            $(document).on('click', '.js-campaign-archive', function () {
                if (archiveBusy) return;
                const button = $(this); const id = button.data('id'); const name = button.data('name');
                const confirm = typeof Swal !== 'undefined'
                    ? Swal.fire({title:'Archive campaign draft?', text:String(name || ''), icon:'warning', showCancelButton:true, confirmButtonText:'Archive', confirmButtonColor:'#b45309'})
                    : Promise.resolve({isConfirmed:window.confirm('Archive this campaign draft?')});
                confirm.then(function (result) {
                    if (!result.isConfirmed) return;
                    const http = client(); if (!http) return notify('Axios unavailable', 'The shared window.AppAxios client is required.', 'error');
                    archiveBusy = true; button.prop('disabled', true);
                    http.post(endpoint(urls.archive, id), {}).then(function (response) { notify('Draft archived', response.data.message, 'success'); table.ajax.reload(null, false); }).catch(function (error) {
                        const payload = (error.response && error.response.data) || {}; notify('Archive failed', validationText(payload) || payload.message || 'Unable to archive this campaign draft.', 'error');
                    }).finally(function () { archiveBusy = false; button.prop('disabled', false); });
                });
            });
        })(jQuery);
    </script>
@endsection
