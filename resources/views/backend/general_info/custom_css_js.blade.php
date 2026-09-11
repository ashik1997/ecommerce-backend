@extends('backend.master')

@section('header_css')
    <link rel="stylesheet" href="{{ versioned_url('codeMirror/css/codemirror.css') }}">
    <link rel="stylesheet" href="{{ versioned_url('codeMirror/css/themes/material.css') }}">
    <style>
        /* ══════════════════════════════════════════════════
           CODE EDITOR PAGE — SLATE BRAND SYSTEM
           Prefix: .ce_
        ══════════════════════════════════════════════════ */
        .ce_wrap {
            --s50:  #f8fafc;
            --s100: #f1f5f9;
            --s200: #e2e8f0;
            --s300: #cbd5e1;
            --s400: #94a3b8;
            --s500: #64748b;
            --s600: #475569;
            --s700: #334155;
            --s800: #1e293b;

            --t400: #2dd4bf;
            --t500: #14b8a6;
            --t600: #0d9488;
            --t700: #0f766e;
            --t50:  #f0fdfa;
            --t100: #ccfbf1;

            --ok:   #10b981;
            --r: 8px; --rl: 12px;
            --sh: 0 1px 3px rgba(0,0,0,.07), 0 1px 2px rgba(0,0,0,.04);
            --shm: 0 4px 12px rgba(0,0,0,.08);

            font-family: 'Nunito', 'Segoe UI', sans-serif;
            font-size: 13px;
            color: var(--s800);
        }

        /* ── PAGE HEADER ── */
        .ce_header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .ce_header-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .ce_header-icon {
            width: 42px; height: 42px;
            background: var(--t600);
            border-radius: var(--rl);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 17px; flex-shrink: 0;
        }
        .ce_header-left h4 {
            font-size: 17px; font-weight: 700;
            margin: 0; color: var(--s800); letter-spacing: -.3px;
        }
        .ce_header-left span {
            font-size: 12px; color: var(--s400); display: block;
        }

        /* ── CARD ── */
        .ce_card {
            background: #fff;
            border-radius: var(--rl);
            border: 1.5px solid var(--s200);
            box-shadow: var(--sh);
            overflow: hidden;
        }

        /* ── TAB BAR ── */
        .ce_tabbar {
            display: flex;
            align-items: stretch;
            gap: 0;
            border-bottom: 2px solid var(--s200);
            background: var(--s50);
            padding: 0 18px;
            @media (max-width: 768px) {
                overflow-x: auto;
            }
        }
        .ce_tabbar::-webkit-scrollbar { height: 3px; }
        .ce_tabbar::-webkit-scrollbar-thumb { background: var(--s200); }

        .ce_tab {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 13px 18px 11px;
            font-size: 13px;
            font-weight: 600;
            color: var(--s500);
            cursor: pointer;
            border: none;
            background: transparent;
            border-bottom: 2px solid transparent;
            /* margin-bottom: -2px; */
            white-space: nowrap;
            transition: color .15s, border-color .15s;
            font-family: inherit;
            user-select: none;
        }
        .ce_tab i {
            font-size: 12px;
            color: var(--s400);
            transition: color .15s;
        }
        .ce_tab .ce_tab-badge {
            font-size: 10px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 20px;
            background: var(--s200);
            color: var(--s500);
            transition: background .15s, color .15s;
        }
        .ce_tab:hover { color: var(--t600); }
        .ce_tab:hover i { color: var(--t500); }

        .ce_tab.active {
            color: var(--t600);
            border-bottom-color: var(--t500);
        }
        .ce_tab.active i { color: var(--t500); }
        .ce_tab.active .ce_tab-badge {
            background: var(--t100);
            color: var(--t700);
        }

        /* ── TAB PANELS ── */
        .ce_panels { padding: 22px 20px; }

        .ce_panel { display: none; }
        .ce_panel.active { display: block; }

        /* ── PANEL HEADER ── */
        .ce_panel-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 14px;
            gap: 10px;
        }
        .ce_panel-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--s700);
            display: flex;
            align-items: center;
            gap: 7px;
        }
        .ce_panel-title i { color: var(--t500); }

        .ce_hint {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            color: var(--s400);
            background: var(--s100);
            border: 1px solid var(--s200);
            border-radius: 6px;
            padding: 5px 10px;
            font-family: 'Courier New', monospace;
            white-space: nowrap;
        }

        /* ── CODEMIRROR OVERRIDES ── */
        .CodeMirror {
            border-radius: var(--r);
            border: 1.5px solid var(--s200);
            font-size: 13px;
            line-height: 1.6;
        }
        .CodeMirror-scroll { width: 100%; }
        .CodeMirror-focused { border-color: var(--t400) !important; }

        /* ── FOOTER ACTIONS ── */
        .ce_footer {
            padding: 14px 20px;
            border-top: 1.5px solid var(--s100);
            background: var(--s50);
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 10px;
        }
        .ce_btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 20px;
            border-radius: var(--r);
            font-size: 13px;
            font-weight: 700;
            border: 1.5px solid transparent;
            cursor: pointer;
            font-family: inherit;
            transition: all .15s;
            text-decoration: none;
            line-height: 1;
        }
        .ce_btn-primary {
            background: var(--t600); border-color: var(--t600); color: #fff;
            box-shadow: 0 2px 6px rgba(13,148,136,.3);
        }
        .ce_btn-primary:hover {
            background: var(--t700); border-color: var(--t700);
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(13,148,136,.35);
            color: #fff; text-decoration: none;
        }
        .ce_btn-ghost {
            background: #fff; border-color: var(--s200); color: var(--s600);
        }
        .ce_btn-ghost:hover {
            background: var(--s100); border-color: var(--s300); color: var(--s700);
            text-decoration: none;
        }

        /* ── SAVE INDICATOR ── */
        .ce_save-msg {
            font-size: 12px;
            color: var(--ok);
            font-weight: 600;
            display: none;
            align-items: center;
            gap: 5px;
            margin-right: auto;
        }
        .ce_save-msg.show { display: flex; }
    </style>
@endsection

@section('header_js')
    <script src="{{ versioned_url('codeMirror/js/codemirror.js') }}"></script>
    <script src="{{ versioned_url('codeMirror/js/xml.js') }}"></script>
    <script src="{{ versioned_url('codeMirror/js/php.js') }}"></script>
    <script src="{{ versioned_url('codeMirror/js/javascript.js') }}"></script>
    <script src="{{ versioned_url('codeMirror/js/python.js') }}"></script>
    <script src="{{ versioned_url('codeMirror/js/addons/closetag.js') }}"></script>
    <script src="{{ versioned_url('codeMirror/js/addons/closebrackets.js') }}"></script>
@endsection

@section('page_title')
    Content Module
@endsection
@section('page_heading')
    Custom CSS & JS
@endsection

@section('content')
<div class="ce_wrap">

    <!-- Page Header -->
    <div class="ce_header">
        <div class="ce_header-left">
            <div class="ce_header-icon"><i class="fas fa-code"></i></div>
            <div>
                <h4>Custom CSS &amp; JS</h4>
                <span>Inject custom styles, scripts and HTML tags into the storefront</span>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ url('update/custom/css/js') }}" enctype="multipart/form-data" id="codeForm">
        @csrf

        <div class="ce_card">

            <!-- ── Tab Bar ── -->
            <div class="ce_tabbar" id="ceTabBar">
                <button type="button" class="ce_tab active" data-tab="tab-css">
                    <i class="fab fa-css3-alt"></i> Custom CSS
                    <span class="ce_tab-badge">CSS</span>
                </button>
                <button type="button" class="ce_tab" data-tab="tab-js">
                    <i class="fab fa-js-square"></i> Custom JS
                    <span class="ce_tab-badge">JS</span>
                </button>
                <button type="button" class="ce_tab" data-tab="tab-header">
                    <i class="fas fa-arrow-up"></i> Header Script
                    <span class="ce_tab-badge">&lt;head&gt;</span>
                </button>
                <button type="button" class="ce_tab" data-tab="tab-footer">
                    <i class="fas fa-arrow-down"></i> Footer Script
                    <span class="ce_tab-badge">&lt;/body&gt;</span>
                </button>
            </div>

            <!-- ── Tab Panels ── -->
            <div class="ce_panels">

                <!-- CSS -->
                <div class="ce_panel active" id="tab-css">
                    <div class="ce_panel-header">
                        <div class="ce_panel-title">
                            <i class="fab fa-css3-alt"></i> Custom CSS
                        </div>
                        <span class="ce_hint"><i class="fas fa-info-circle"></i> Injected inside &lt;style&gt; in &lt;head&gt;</span>
                    </div>
                    <textarea name="custom_css" id="ce_css">{{ $data->custom_css }}</textarea>
                </div>

                <!-- JS -->
                <div class="ce_panel" id="tab-js">
                    <div class="ce_panel-header">
                        <div class="ce_panel-title">
                            <i class="fab fa-js-square"></i> Custom JS
                        </div>
                        <span class="ce_hint"><i class="fas fa-info-circle"></i> Injected inside &lt;script&gt; before &lt;/body&gt;</span>
                    </div>
                    <textarea name="custom_js" id="ce_js">{{ $data->custom_js }}</textarea>
                </div>

                <!-- Header Script -->
                <div class="ce_panel" id="tab-header">
                    <div class="ce_panel-header">
                        <div class="ce_panel-title">
                            <i class="fas fa-arrow-up"></i> Header Script
                        </div>
                        <span class="ce_hint"><i class="fas fa-info-circle"></i> Raw HTML / &lt;script&gt; tags injected in &lt;head&gt;</span>
                    </div>
                    <textarea name="header_script" id="ce_header_script">{{ $data->header_script }}</textarea>
                </div>

                <!-- Footer Script -->
                <div class="ce_panel" id="tab-footer">
                    <div class="ce_panel-header">
                        <div class="ce_panel-title">
                            <i class="fas fa-arrow-down"></i> Footer Script
                        </div>
                        <span class="ce_hint"><i class="fas fa-info-circle"></i> Raw HTML / &lt;script&gt; tags injected before &lt;/body&gt;</span>
                    </div>
                    <textarea name="footer_script" id="ce_footer_script">{{ $data->footer_script }}</textarea>
                </div>

            </div><!-- end ce_panels -->

            <!-- ── Footer Actions ── -->
            <div class="ce_footer">
                <span class="ce_save-msg" id="ceSaveMsg">
                    <i class="fas fa-check-circle"></i> Saved successfully!
                </span>
                <a href="{{ url('/home') }}" class="ce_btn ce_btn-ghost">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <button type="submit" class="ce_btn ce_btn-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </div>

    </form>
</div>
@endsection


@section('footer_js')
<script>
(function () {
    /* ── Editor registry: tabId → CodeMirror instance ── */
    var editors = {};

    var editorConfig = {
        ce_css:           { mode: 'css',        label: 'CSS'  },
        ce_js:            { mode: 'javascript',  label: 'JS'   },
        ce_header_script: { mode: 'htmlmixed',   label: 'HTML' },
        ce_footer_script: { mode: 'htmlmixed',   label: 'HTML' },
    };

    /* ── Mount CodeMirror on all textareas ── */
    Object.keys(editorConfig).forEach(function (id) {
        var el = document.getElementById(id);
        if (!el) return;
        var cm = CodeMirror.fromTextArea(el, {
            mode:              editorConfig[id].mode,
            theme:             'material',
            lineNumbers:       true,
            autoCloseTags:     true,
            autoCloseBrackets: true,
            lineWrapping:      true,
        });
        cm.setSize('100%', 560);
        editors[id] = cm;
    });

    /* ── Tab switching ── */
    var tabMap = {
        'tab-css':    'ce_css',
        'tab-js':     'ce_js',
        'tab-header': 'ce_header_script',
        'tab-footer': 'ce_footer_script',
    };

    document.getElementById('ceTabBar').addEventListener('click', function (e) {
        var btn = e.target.closest('.ce_tab');
        if (!btn) return;

        var targetId = btn.getAttribute('data-tab');

        /* Update tab active state */
        document.querySelectorAll('.ce_tab').forEach(function (t) { t.classList.remove('active'); });
        btn.classList.add('active');

        /* Update panel active state */
        document.querySelectorAll('.ce_panel').forEach(function (p) { p.classList.remove('active'); });
        document.getElementById(targetId).classList.add('active');

        /* Refresh CodeMirror so it renders correctly after being hidden */
        var editorId = tabMap[targetId];
        if (editorId && editors[editorId]) {
            setTimeout(function () { editors[editorId].refresh(); }, 10);
        }
    });

    /* ── Flash save message after form submit ── */
    @if(session('success'))
        var msg = document.getElementById('ceSaveMsg');
        if (msg) {
            msg.classList.add('show');
            setTimeout(function () { msg.classList.remove('show'); }, 4000);
        }
    @endif
}());
</script>
@endsection
