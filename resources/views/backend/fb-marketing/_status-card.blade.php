<div class="card">
    <div class="card-body">
        <div class="d-flex flex-wrap align-items-start justify-content-between">
            <div class="mb-2">
                <h4 class="card-title mb-1">FB MARKETING Creative Library and Publish Preflight Assets</h4>
                <p class="text-muted mb-0">Local creative draft assets can now be collected, UTM-tagged and preflighted before any future publish workflow.</p>
            </div>
            <span class="badge badge-info px-3 py-2">FBM-21 Complete</span>
        </div>
        <hr>
        <div class="row">
            <div class="col-lg-4 col-md-6 mb-3 mb-lg-0">
                <small class="text-muted d-block">Current stage</small>
                <strong>FBM-21 — Creative Library and Publish Preflight Assets</strong>
            </div>
            <div class="col-lg-4 col-md-6 mb-3 mb-lg-0">
                <small class="text-muted d-block">Preflight boundary</small>
                <strong>Local creative assets and validation checks only</strong>
            </div>
            <div class="col-lg-4 col-md-12">
                <small class="text-muted d-block">Next stage</small>
                <strong>FBM-22 — Audience and Product-set Management</strong>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-light border">
    <strong>Security rule:</strong> Dashboard, Performance, Products &amp; Catalog, Attribution Reports, Product Performance, Profitability, Boosting Jobs, Reports and Creative Library pages expose safe application-local projections only. Tokens, provider IDs, raw Graph payloads, raw URLs, query strings, browser identifiers, customer values and Meta async report keys remain outside browser projections.
</div>

@if ((bool) config('fb_marketing.manual_sync.enabled', true) || (bool) config('fb_marketing.manual_drilldown_sync.enabled', true) || (bool) config('fb_marketing.catalog_sync.manual_enabled', true))
    <div class="alert alert-info border">
        <strong>Testing fallback:</strong> Configuration includes separate bounded application-level manual actions for account-level dashboard snapshots, campaign/ad-set/ad drilldowns and read-only catalog mapping. They bypass queue storage and workers and deliberately defer production-scale traversal or historical async backfill.
    </div>
@endif
