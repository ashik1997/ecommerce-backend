<style>
    :root{
        --fa-primary:#0f766e;
        --fa-primary-dark:#115e59;
        --fa-primary-soft:#e6fffb;
        --fa-ink:#0f172a;
        --fa-muted:#64748b;
        --fa-border:#e2e8f0;
        --fa-bg:#f8fafc;
        --fa-card:#ffffff;
        --fa-danger:#dc2626;
        --fa-warning:#d97706;
        --fa-success:#059669;
        --fa-info:#2563eb;
    }
    .fa-page-wrap{background:var(--fa-bg);border-radius:18px;padding:16px;margin-bottom:20px}
    .fa-page-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:16px;flex-wrap:wrap}
    .fa-page-title{margin:0;font-weight:800;color:var(--fa-ink);font-size:22px;line-height:1.25}
    .fa-page-subtitle{margin:4px 0 0;color:var(--fa-muted);font-size:13px}
    .fa-toolbar{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
    .fa-card{border:1px solid var(--fa-border);border-radius:16px;background:var(--fa-card);box-shadow:0 10px 25px rgba(15,23,42,.05);overflow:hidden;margin-bottom:16px}
    .fa-card .card-header,.fa-card-header{background:linear-gradient(135deg,#ffffff,#f0fdfa);border-bottom:1px solid var(--fa-border);padding:14px 16px}
    .fa-card .card-body,.fa-card-body{padding:16px}
    .fa-card-title{font-weight:800;color:var(--fa-ink);font-size:16px;margin:0}
    .fa-kpi{border-radius:16px;border:1px solid var(--fa-border);background:#fff;box-shadow:0 8px 20px rgba(15,23,42,.05);padding:16px;margin-bottom:14px;position:relative;overflow:hidden}
    .fa-kpi:before{content:'';position:absolute;top:0;left:0;width:5px;height:100%;background:var(--fa-primary)}
    .fa-kpi .label{font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:var(--fa-muted);font-weight:700}.fa-kpi .value{font-size:24px;font-weight:900;color:var(--fa-ink);line-height:1.2}
    .fa-module-nav{display:flex;gap:8px;overflow-x:auto;padding:10px;border:1px solid var(--fa-border);border-radius:14px;background:#fff;box-shadow:0 6px 14px rgba(15,23,42,.04)}
    .fa-module-nav a{white-space:nowrap;padding:8px 12px;border-radius:999px;color:#334155;font-weight:700;font-size:13px;text-decoration:none;border:1px solid transparent;transition:.2s ease}
    .fa-module-nav a:hover{background:var(--fa-primary-soft);color:var(--fa-primary-dark);border-color:#99f6e4;text-decoration:none}
    .fa-module-nav a.active{background:var(--fa-primary);color:#fff;border-color:var(--fa-primary)}
    .fa-filter-card{border:1px solid var(--fa-border);background:#fff;border-radius:14px;padding:12px;margin-bottom:14px}
    .fa-filter-card label{font-size:12px;font-weight:800;color:#475569;margin-bottom:4px}
    .fa-table{background:#fff;border:1px solid var(--fa-border);border-radius:14px;overflow:hidden}
    .fa-table table{margin-bottom:0}.fa-table thead th{background:#f8fafc;color:#334155;font-size:12px;text-transform:uppercase;letter-spacing:.03em;border-bottom:1px solid var(--fa-border);white-space:nowrap}.fa-table td{vertical-align:middle;color:#1e293b}
    .fa-badge{display:inline-block;padding:5px 10px;border-radius:999px;background:#eef6ff;color:#0f4c81;font-size:12px;font-weight:800;text-transform:capitalize;line-height:1}
    .fa-badge.green,.fa-badge.active,.fa-badge.approved,.fa-badge.completed,.fa-badge.received{background:#ecfdf5;color:#047857}.fa-badge.red,.fa-badge.cancelled,.fa-badge.disposed,.fa-badge.retired,.fa-badge.lost{background:#fff1f2;color:#be123c}.fa-badge.amber,.fa-badge.pending,.fa-badge.in_transfer,.fa-badge.under_maintenance{background:#fffbeb;color:#b45309}.fa-badge.gray,.fa-badge.draft,.fa-badge.idle{background:#f1f5f9;color:#475569}
    .fa-actions{display:flex;align-items:center;gap:4px;flex-wrap:wrap}.fa-actions a,.fa-actions button{margin:1px;border-radius:8px;font-weight:700}.required:after{content:' *';color:#dc3545;font-weight:800}
    .fa-form-section{border:1px solid var(--fa-border);border-radius:14px;padding:14px;background:#fff;margin-bottom:14px}.fa-form-section-title{font-weight:800;color:var(--fa-ink);margin-bottom:12px;border-bottom:1px dashed var(--fa-border);padding-bottom:8px}
    .fa-empty{padding:36px 16px;text-align:center;color:var(--fa-muted)}.fa-empty .title{font-weight:800;color:#334155;font-size:16px;margin-bottom:4px}
    .fa-help{font-size:12px;color:var(--fa-muted);margin-top:4px}.fa-money{text-align:right;font-variant-numeric:tabular-nums}.fa-text-strong{font-weight:800;color:var(--fa-ink)}
    .btn-fa-primary{background:var(--fa-primary);border-color:var(--fa-primary);color:#fff}.btn-fa-primary:hover{background:var(--fa-primary-dark);border-color:var(--fa-primary-dark);color:#fff}.btn-fa-soft{background:var(--fa-primary-soft);border-color:#99f6e4;color:var(--fa-primary-dark);font-weight:800}.btn-fa-soft:hover{background:#ccfbf1;color:var(--fa-primary-dark)}
    .form-control:focus,.custom-select:focus{border-color:#14b8a6;box-shadow:0 0 0 .15rem rgba(20,184,166,.15)}
    @media(max-width:767px){.fa-page-wrap{padding:10px}.fa-page-title{font-size:19px}.fa-card .card-body,.fa-card-body{padding:12px}.fa-actions{gap:3px}.fa-actions .btn{padding:4px 7px;font-size:12px}.fa-filter-card .col-md-1,.fa-filter-card .col-md-2,.fa-filter-card .col-md-3,.fa-filter-card .col-md-4{margin-bottom:8px}}
</style>
