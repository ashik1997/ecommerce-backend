@extends('backend.master')

@section('header_css')
    <style>
        /* ═══════════════════════════════════════════════
       WEBSITE CONFIG — SOCIAL LOGIN & SCRIPTS
       Prefix: .wc_component
       ═══════════════════════════════════════════════ */

        .wc_component *,
        .wc_component *::before,
        .wc_component *::after {
            box-sizing: border-box;
        }

        .wc_component {
            --t50: #f0fdfa;
            --t100: #ccfbf1;
            --t200: #99f6e4;
            --t400: #2dd4bf;
            --t500: #14b8a6;
            --t600: #0d9488;
            --t700: #0f766e;
            --t800: #115e59;

            --g50: #f8fafc;
            --g100: #f1f5f9;
            --g200: #e2e8f0;
            --g300: #cbd5e1;
            --g400: #94a3b8;
            --g500: #64748b;
            --g600: #475569;
            --g700: #334155;
            --g800: #1e293b;

            --ok: #10b981;
            --err: #ef4444;

            --r: 8px;
            --rl: 12px;
            --rxl: 16px;
            --sh: 0 1px 3px rgba(0, 0, 0, .07), 0 1px 2px rgba(0, 0, 0, .04);
            --shm: 0 4px 14px rgba(0, 0, 0, .08);

            font-family: 'Nunito', 'Segoe UI', sans-serif;
            font-size: 13.5px;
            color: var(--g800);
            line-height: 1.55;
        }

        /* ── PAGE HEADER ───────────────────────────────── */
        .wc_component .wc-page-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .wc_component .wc-page-icon {
            width: 44px;
            height: 44px;
            border-radius: var(--rl);
            background: var(--t600);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 18px;
            flex-shrink: 0;
        }

        .wc_component .wc-page-header h4 {
            font-size: 18px;
            font-weight: 800;
            margin: 0;
            color: var(--g800);
            letter-spacing: -.3px;
        }

        .wc_component .wc-page-header p {
            font-size: 12px;
            color: var(--g400);
            margin: 2px 0 0;
        }

        /* ── LAYOUT SHELL ──────────────────────────────── */
        .wc_component .wc-shell {
            display: grid;
            grid-template-columns: 220px 1fr;
            gap: 16px;
            align-items: start;
        }

        @media(max-width: 768px) {
            .wc_component .wc-shell {
                grid-template-columns: 1fr;
            }
        }

        /* ── SIDEBAR NAV ───────────────────────────────── */
        .wc_component .wc-nav {
            background: #fff;
            border: 1.5px solid var(--g200);
            border-radius: var(--rl);
            overflow: hidden;
            box-shadow: var(--sh);
        }

        .wc_component .wc-nav-header {
            padding: 10px 14px;
            background: var(--g50);
            border-bottom: 1px solid var(--g100);
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .8px;
            color: var(--g400);
        }

        /* Mobile: horizontal scroll nav */
        @media(max-width: 768px) {
            .wc_component .wc-nav {
                overflow-x: auto;
                overflow-y: hidden;
                white-space: nowrap;
                border-radius: var(--rxl);
            }

            .wc_component .wc-nav-list {
                display: flex;
                flex-direction: row;
                padding: 8px;
                gap: 6px;
                overflow-x: auto;
            }
        }

        @media(min-width: 769px) {
            .wc_component .wc-nav-list {
                display: flex;
                flex-direction: column;
                padding: 6px;
                gap: 2px;
            }
        }

        .wc_component .wc-nav-item {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 9px 12px;
            border-radius: var(--r);
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            color: var(--g600);
            border: none;
            background: transparent;
            text-align: left;
            transition: all .14s;
            white-space: nowrap;
            font-family: inherit;
            width: 100%;
            outline: none;
        }

        @media(max-width: 768px) {
            .wc_component .wc-nav-item {
                width: auto;
                flex-shrink: 0;
            }
        }

        .wc_component .wc-nav-item .wc-nav-icon {
            width: 30px;
            height: 30px;
            border-radius: var(--r);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            flex-shrink: 0;
            transition: all .14s;
            background: var(--g100);
            color: var(--g500);
        }

        .wc_component .wc-nav-item:hover {
            background: var(--t50);
            color: var(--t700);
        }

        .wc_component .wc-nav-item:hover .wc-nav-icon {
            background: var(--t100);
            color: var(--t600);
        }

        .wc_component .wc-nav-item.active {
            background: var(--t50);
            color: var(--t700);
        }

        .wc_component .wc-nav-item.active .wc-nav-icon {
            background: var(--t500);
            color: #fff;
        }

        .wc_component .wc-nav-badge {
            margin-left: auto;
            font-size: 10px;
            font-weight: 700;
            padding: 1px 7px;
            border-radius: 20px;
            background: var(--ok);
            color: #fff;
            flex-shrink: 0;
        }

        .wc_component .wc-nav-badge.off {
            background: var(--g200);
            color: var(--g500);
        }

        /* ── CONTENT PANELS ────────────────────────────── */
        .wc_component .wc-panels {}

        .wc_component .wc-panel {
            display: none;
        }

        .wc_component .wc-panel.active {
            display: block;
            animation: wc-fadein .18s ease;
        }

        @keyframes wc-fadein {
            from {
                opacity: 0;
                transform: translateY(-4px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Panel card */
        .wc_component .wc-panel-card {
            background: #fff;
            border: 1.5px solid var(--g200);
            border-radius: var(--rl);
            box-shadow: var(--sh);
            overflow: hidden;
        }

        .wc_component .wc-panel-header {
            padding: 14px 18px;
            background: var(--g50);
            border-bottom: 1.5px solid var(--g100);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .wc_component .wc-panel-header-icon {
            width: 36px;
            height: 36px;
            border-radius: var(--r);
            background: var(--t500);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            flex-shrink: 0;
        }

        .wc_component .wc-panel-header-title {
            font-size: 14px;
            font-weight: 800;
            color: var(--g800);
            margin: 0;
        }

        .wc_component .wc-panel-header-sub {
            font-size: 11px;
            color: var(--g400);
            margin: 1px 0 0;
        }

        .wc_component .wc-panel-body {
            padding: 18px 20px;
        }

        /* Section divider inside a panel */
        .wc_component .wc-section-divider {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 18px 0 14px;
        }

        .wc_component .wc-section-divider::before,
        .wc_component .wc-section-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--g100);
        }

        .wc_component .wc-section-divider span {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .7px;
            color: var(--g400);
            white-space: nowrap;
        }

        /* ── FORM ELEMENTS ─────────────────────────────── */
        .wc_component .wc-fg {
            display: flex;
            flex-direction: column;
            gap: 5px;
            margin-bottom: 14px;
        }

        .wc_component .wc-fg:last-child {
            margin-bottom: 0;
        }

        .wc_component .wc-label {
            font-size: 11px;
            font-weight: 800;
            color: var(--g500);
            text-transform: uppercase;
            letter-spacing: .55px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .wc_component .wc-label i {
            color: var(--t400);
            font-size: 11px;
        }

        .wc_component .wc-input,
        .wc_component .wc-select {
            width: 100%;
            padding: 8px 11px;
            border: 1.5px solid var(--g200);
            border-radius: var(--r);
            font-size: 13px;
            color: var(--g800);
            background: #fff;
            transition: border-color .15s, box-shadow .15s;
            outline: none;
            font-family: inherit;
        }

        .wc_component .wc-input:focus,
        .wc_component .wc-select:focus {
            border-color: var(--t400);
            box-shadow: 0 0 0 3px rgba(20, 184, 166, .12);
        }

        .wc_component .wc-input::placeholder {
            color: var(--g300);
            font-size: 12px;
        }

        /* Toggle select: enable/disable pill style */
        .wc_component .wc-select option {
            font-weight: 600;
        }

        /* ── SUBMIT BUTTON ─────────────────────────────── */
        .wc_component .wc-submit {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 9px 20px;
            border-radius: var(--r);
            background: var(--t600);
            border: none;
            color: #fff;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: all .15s;
            font-family: inherit;
            box-shadow: 0 2px 6px rgba(13, 148, 136, .3);
            margin-top: 4px;
        }

        .wc_component .wc-submit:hover {
            background: var(--t700);
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(13, 148, 136, .35);
        }

        .wc_component .wc-submit:active {
            transform: translateY(0);
        }

        /* ── FEED COPY BOX ─────────────────────────────── */
        .wc_component .wc-feed-box {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 12px;
            background: var(--g50);
            border: 1.5px solid var(--g200);
            border-radius: var(--r);
            margin-bottom: 14px;
        }

        .wc_component .wc-feed-label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            font-weight: 800;
            color: var(--t700);
            text-transform: uppercase;
            letter-spacing: .5px;
            white-space: nowrap;
        }

        .wc_component .wc-feed-label i {
            font-size: 13px;
        }

        .wc_component .wc-feed-url {
            flex: 1;
            font-size: 12px;
            color: var(--g500);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-family: 'Courier New', monospace;
        }

        .wc_component .wc-copy-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 11px;
            border-radius: var(--r);
            font-size: 11px;
            font-weight: 700;
            border: 1.5px solid var(--t200);
            background: var(--t50);
            color: var(--t700);
            cursor: pointer;
            transition: all .12s;
            white-space: nowrap;
            font-family: inherit;
            flex-shrink: 0;
        }

        .wc_component .wc-copy-btn:hover {
            background: var(--t100);
            border-color: var(--t400);
        }

        .wc_component .wc-copy-btn.copied {
            background: var(--ok);
            border-color: var(--ok);
            color: #fff;
        }

        /* ── STATUS INDICATOR ──────────────────────────── */
        .wc_component .wc-status-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 12px;
            background: var(--g50);
            border-radius: var(--r);
            border: 1.5px solid var(--g200);
            margin-bottom: 14px;
            flex-wrap: wrap;
            gap: 6px;
        }

        .wc_component .wc-status-label {
            font-size: 12px;
            font-weight: 700;
            color: var(--g600);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .wc_component .wc-status-pill {
            font-size: 11px;
            font-weight: 800;
            padding: 3px 10px;
            border-radius: 20px;
        }

        .wc_component .wc-status-pill.on {
            background: #d1fae5;
            color: #065f46;
        }

        .wc_component .wc-status-pill.off {
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
@endsection

@section('page_title')
    Website Config
@endsection
@section('page_heading')
    API & Chat Scripts
@endsection

@section('content')
    <div class="wc_component">

        <!-- Page Header -->
        <div class="wc-page-header">
            <div class="wc-page-icon"><i class="fas fa-cog"></i></div>
            <div>
                <h4>API & Chat Scripts</h4>
                <p>Manage analytics, social login, and third-party chat integrations</p>
            </div>
        </div>

        <div class="wc-shell">

            <!-- ── SIDEBAR NAV ── -->
            <div class="wc-nav">
                <div class="wc-nav-header d-none d-md-block">Integrations</div>
                <div class="wc-nav-list">

                    <button type="button" class="wc-nav-item active" data-target="panel-ga">
                        <span class="wc-nav-icon"><i class="fab fa-google"></i></span>
                        <span class="d-none d-md-inline">Google Analytics</span>
                        <span class="d-md-none">Analytics</span>
                        <span class="wc-nav-badge {{ $generalInfo->google_analytic_status ? '' : 'off' }}">
                            {{ $generalInfo->google_analytic_status ? 'ON' : 'OFF' }}
                        </span>
                    </button>

                    <button type="button" class="wc-nav-item" data-target="panel-gt">
                        <span class="wc-nav-icon"><i class="fas fa-tags"></i></span>
                        <span class="d-none d-md-inline">Tag Manager</span>
                        <span class="d-md-none">GTM</span>
                        <span class="wc-nav-badge {{ $generalInfo->google_tag_manager_status ? '' : 'off' }}">
                            {{ $generalInfo->google_tag_manager_status ? 'ON' : 'OFF' }}
                        </span>
                    </button>

                    <button type="button" class="wc-nav-item" data-target="panel-fp">
                        <span class="wc-nav-icon"><i class="fab fa-facebook-f"></i></span>
                        <span class="d-none d-md-inline">Facebook Pixel</span>
                        <span class="d-md-none">FB Pixel</span>
                        <span class="wc-nav-badge {{ $generalInfo->fb_pixel_status ? '' : 'off' }}">
                            {{ $generalInfo->fb_pixel_status ? 'ON' : 'OFF' }}
                        </span>
                    </button>

                    <button type="button" class="wc-nav-item" data-target="panel-tp">
                        <span class="wc-nav-icon"><i class="fab fa-tiktok"></i></span>
                        <span class="d-none d-md-inline">TikTok Pixel</span>
                        <span class="d-md-none">TT Pixel</span>
                        <span class="wc-nav-badge {{ ($generalInfo->tiktok_pixel_status ?? 0) ? '' : 'off' }}">
                            {{ ($generalInfo->tiktok_pixel_status ?? 0) ? 'ON' : 'OFF' }}
                        </span>
                    </button>

                    <button type="button" class="wc-nav-item" data-target="panel-gr">
                        <span class="wc-nav-icon"><i class="fas fa-shield-alt"></i></span>
                        <span class="d-none d-md-inline">reCAPTCHA</span>
                        <span class="d-md-none">reCAPTCHA</span>
                        <span class="wc-nav-badge {{ $googleRecaptcha->status ? '' : 'off' }}">
                            {{ $googleRecaptcha->status ? 'ON' : 'OFF' }}
                        </span>
                    </button>

                    <button type="button" class="wc-nav-item" data-target="panel-sl">
                        <span class="wc-nav-icon"><i class="fas fa-sign-in-alt"></i></span>
                        <span class="d-none d-md-inline">Social Login</span>
                        <span class="d-md-none">Login</span>
                        <span
                            class="wc-nav-badge {{ $socialLoginInfo->fb_login_status || $socialLoginInfo->gmail_login_status ? '' : 'off' }}">
                            {{ $socialLoginInfo->fb_login_status || $socialLoginInfo->gmail_login_status ? 'ON' : 'OFF' }}
                        </span>
                    </button>

                    <button type="button" class="wc-nav-item" data-target="panel-mc">
                        <span class="wc-nav-icon"><i class="fab fa-facebook-messenger"></i></span>
                        <span class="d-none d-md-inline">Messenger Chat</span>
                        <span class="d-md-none">Messenger</span>
                        <span class="wc-nav-badge {{ $generalInfo->messenger_chat_status ? '' : 'off' }}">
                            {{ $generalInfo->messenger_chat_status ? 'ON' : 'OFF' }}
                        </span>
                    </button>

                    <button type="button" class="wc-nav-item" data-target="panel-tw">
                        <span class="wc-nav-icon"><i class="fas fa-headset"></i></span>
                        <span class="d-none d-md-inline">Tawk.to Chat</span>
                        <span class="d-md-none">Tawk</span>
                        <span class="wc-nav-badge {{ $generalInfo->tawk_chat_status ? '' : 'off' }}">
                            {{ $generalInfo->tawk_chat_status ? 'ON' : 'OFF' }}
                        </span>
                    </button>

                    <button type="button" class="wc-nav-item" data-target="panel-cr">
                        <span class="wc-nav-icon"><i class="fas fa-comments"></i></span>
                        <span class="d-none d-md-inline">Crisp Chat</span>
                        <span class="d-md-none">Crisp</span>
                        <span class="wc-nav-badge {{ $generalInfo->crisp_chat_status ? '' : 'off' }}">
                            {{ $generalInfo->crisp_chat_status ? 'ON' : 'OFF' }}
                        </span>
                    </button>

                </div>
            </div>

            <!-- ── CONTENT PANELS ── -->
            <div class="wc-panels">

                <!-- Google Analytics -->
                <div class="wc-panel active" id="panel-ga">
                    <div class="wc-panel-card">
                        <div class="wc-panel-header">
                            <div class="wc-panel-header-icon" style="background:#e37400;">
                                <i class="fab fa-google"></i>
                            </div>
                            <div>
                                <p class="wc-panel-header-title">Google Analytics</p>
                                <p class="wc-panel-header-sub">Track website visitors and behavior</p>
                            </div>
                        </div>
                        <div class="wc-panel-body">
                            <form action="{{ url('update/google/analytic') }}" method="POST"
                                enctype="multipart/form-data">
                                @csrf
                                <div class="wc-fg">
                                    <label class="wc-label"><i class="fas fa-toggle-on"></i> Status</label>
                                    <select name="google_analytic_status" class="wc-select" required>
                                        <option value="1" @if ($generalInfo->google_analytic_status == 1) selected @endif>
                                            ✓ Enable Google Analytics
                                        </option>
                                        <option value="0" @if ($generalInfo->google_analytic_status == 0) selected @endif>
                                            ✕ Disable Google Analytics
                                        </option>
                                    </select>
                                </div>
                                <div class="wc-fg">
                                    <label class="wc-label"><i class="fas fa-key"></i> Tracking ID</label>
                                    <input type="text" class="wc-input" name="google_analytic_tracking_id"
                                        value="{{ $generalInfo->google_analytic_tracking_id }}"
                                        placeholder="e.g. UA-842191520-6 or G-XXXXXXXX">
                                </div>
                                <button type="submit" class="wc-submit">
                                    <i class="fas fa-save"></i> Save Changes
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Google Tag Manager -->
                <div class="wc-panel" id="panel-gt">
                    <div class="wc-panel-card">
                        <div class="wc-panel-header">
                            <div class="wc-panel-header-icon" style="background:#4285F4;">
                                <i class="fas fa-tags"></i>
                            </div>
                            <div>
                                <p class="wc-panel-header-title">Google Tag Manager</p>
                                <p class="wc-panel-header-sub">Manage marketing and analytics tags</p>
                            </div>
                        </div>
                        <div class="wc-panel-body">
                            <form action="{{ url('update/google/tag/manager') }}" method="POST">
                                @csrf
                                <div class="wc-fg">
                                    <label class="wc-label"><i class="fas fa-toggle-on"></i> Status</label>
                                    <select name="google_tag_manager_status" class="wc-select" required>
                                        <option value="1" @if ($generalInfo->google_tag_manager_status == 1) selected @endif>
                                            ✓ Enable Tag Manager
                                        </option>
                                        <option value="0" @if ($generalInfo->google_tag_manager_status == 0) selected @endif>
                                            ✕ Disable Tag Manager
                                        </option>
                                    </select>
                                </div>
                                <div class="wc-fg">
                                    <label class="wc-label"><i class="fas fa-key"></i> Container ID</label>
                                    <input type="text" class="wc-input" name="google_tag_manager_id"
                                        value="{{ $generalInfo->google_tag_manager_id }}"
                                        placeholder="e.g. GTM-546FMKZS">
                                </div>
                                <button type="submit" class="wc-submit">
                                    <i class="fas fa-save"></i> Save Changes
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Facebook Pixel -->
                <div class="wc-panel" id="panel-fp">
                    <div class="wc-panel-card">
                        <div class="wc-panel-header">
                            <div class="wc-panel-header-icon" style="background:#1877F2;">
                                <i class="fab fa-facebook-f"></i>
                            </div>
                            <div>
                                <p class="wc-panel-header-title">Facebook Pixel</p>
                                <p class="wc-panel-header-sub">Track conversions and build audiences</p>
                            </div>
                        </div>
                        <div class="wc-panel-body">
                            <form action="{{ url('update/facebook/pixel') }}" method="POST"
                                enctype="multipart/form-data">
                                @csrf

                                <!-- Data Feed Copy Box -->
                                <div class="wc-feed-box">
                                    <div class="wc-feed-label">
                                        <i class="fas fa-rss"></i> Data Feed
                                    </div>
                                    <div class="wc-feed-url" id="feedUrl">{{ url('api/facebook-product-feed.xml') }}
                                    </div>
                                    <button class="wc-copy-btn" type="button" id="copyFeedBtn" onclick="copyFeedUrl()">
                                        <i class="fas fa-copy"></i> Copy
                                    </button>
                                </div>

                                <div class="wc-fg">
                                    <label class="wc-label"><i class="fas fa-toggle-on"></i> Status</label>
                                    <select name="fb_pixel_status" class="wc-select" required>
                                        <option value="1" @if ($generalInfo->fb_pixel_status == 1) selected @endif>
                                            ✓ Enable Facebook Pixel
                                        </option>
                                        <option value="0" @if ($generalInfo->fb_pixel_status == 0) selected @endif>
                                            ✕ Disable Facebook Pixel
                                        </option>
                                    </select>
                                </div>
                                <div class="wc-fg">
                                    <label class="wc-label"><i class="fas fa-hashtag"></i> Pixel App ID</label>
                                    <input type="text" class="wc-input" name="fb_pixel_app_id"
                                        value="{{ $generalInfo->fb_pixel_app_id }}" placeholder="e.g. 97291160691059">
                                </div>
                                <div class="wc-fg">
                                    <label class="wc-label"><i class="fas fa-key"></i> Pixel API Key</label>
                                    <input type="password" class="wc-input" name="fb_pixel_api_key" value=""
                                        autocomplete="new-password" placeholder="Leave blank to keep the configured API key">
                                </div>
                                <div class="wc-fg">
                                    <label class="wc-label"><i class="fas fa-code"></i> Facebook Test Event Code</label>
                                    <input type="password" class="wc-input" name="fb_test_event_code" value=""
                                        autocomplete="new-password" placeholder="Leave blank to keep the configured test code">
                                </div>
                                <button type="submit" class="wc-submit">
                                    <i class="fas fa-save"></i> Save Changes
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- TikTok Pixel -->
                <div class="wc-panel" id="panel-tp">
                    <div class="wc-panel-card">
                        <div class="wc-panel-header">
                            <div class="wc-panel-header-icon" style="background:#000000;">
                                <i class="fab fa-tiktok"></i>
                            </div>
                            <div>
                                <p class="wc-panel-header-title">TikTok Pixel</p>
                                <p class="wc-panel-header-sub">Measure ad performance and optimize campaigns</p>
                            </div>
                        </div>
                        <div class="wc-panel-body">
                            <form action="{{ url('update/tiktok/pixel') }}" method="POST"
                                enctype="multipart/form-data">
                                @csrf

                                <div class="wc-fg">
                                    <label class="wc-label"><i class="fas fa-toggle-on"></i> Status</label>
                                    <select name="tiktok_pixel_status" class="wc-select" required>
                                        <option value="1" @if (($generalInfo->tiktok_pixel_status ?? 0) == 1) selected @endif>
                                            ✓ Enable TikTok Pixel
                                        </option>
                                        <option value="0" @if (($generalInfo->tiktok_pixel_status ?? 0) == 0) selected @endif>
                                            ✕ Disable TikTok Pixel
                                        </option>
                                    </select>
                                </div>
                                <div class="wc-fg">
                                    <label class="wc-label"><i class="fas fa-hashtag"></i> Pixel ID</label>
                                    <input type="text" class="wc-input" name="tiktok_pixel_id"
                                        value="{{ $generalInfo->tiktok_pixel_id ?? '' }}"
                                        placeholder="e.g. CXXXXXXXXXXXXXXXX">
                                </div>
                                <div class="wc-fg">
                                    <label class="wc-label"><i class="fas fa-key"></i> Access token</label>
                                    <input type="password" class="wc-input" name="tiktok_pixel_token" value=""
                                        autocomplete="new-password" placeholder="Leave blank to keep the configured access token">
                                </div>
                                <div class="wc-fg">
                                    <label class="wc-label"><i class="fas fa-lock"></i> App secret</label>
                                    <input type="password" class="wc-input" name="tiktok_pixel_secret" value=""
                                        autocomplete="new-password" placeholder="Leave blank to keep the configured app secret">
                                </div>
                                <button type="submit" class="wc-submit">
                                    <i class="fas fa-save"></i> Save Changes
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Google reCAPTCHA -->
                <div class="wc-panel" id="panel-gr">
                    <div class="wc-panel-card">
                        <div class="wc-panel-header">
                            <div class="wc-panel-header-icon" style="background:#4285F4;">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div>
                                <p class="wc-panel-header-title">Google reCAPTCHA</p>
                                <p class="wc-panel-header-sub">Protect forms from spam and abuse</p>
                            </div>
                        </div>
                        <div class="wc-panel-body">
                            <form action="{{ url('update/google/recaptcha') }}" method="POST"
                                enctype="multipart/form-data">
                                @csrf
                                <div class="wc-fg">
                                    <label class="wc-label"><i class="fas fa-toggle-on"></i> Status</label>
                                    <select name="captcha_status" class="wc-select" required>
                                        <option value="1" @if ($googleRecaptcha->status == 1) selected @endif>
                                            ✓ Enable reCAPTCHA
                                        </option>
                                        <option value="0" @if ($googleRecaptcha->status == 0) selected @endif>
                                            ✕ Disable reCAPTCHA
                                        </option>
                                    </select>
                                </div>
                                <div class="wc-fg">
                                    <label class="wc-label"><i class="fas fa-globe"></i> Site Key</label>
                                    <input type="text" class="wc-input" name="captcha_site_key"
                                        value="{{ $googleRecaptcha->captcha_site_key }}"
                                        placeholder="e.g. 6LcVO6cbAAAAOzIEwPlU66nL1rxD4VAS38tjpBX">
                                </div>
                                <div class="wc-fg">
                                    <label class="wc-label"><i class="fas fa-lock"></i> Secret Key</label>
                                    <input type="password" class="wc-input" name="captcha_secret_key" value=""
                                        autocomplete="new-password" placeholder="Leave blank to keep the configured secret key">
                                </div>
                                <button type="submit" class="wc-submit">
                                    <i class="fas fa-save"></i> Save Changes
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Social Login -->
                <div class="wc-panel" id="panel-sl">
                    <div class="wc-panel-card">
                        <div class="wc-panel-header">
                            <div class="wc-panel-header-icon">
                                <i class="fas fa-sign-in-alt"></i>
                            </div>
                            <div>
                                <p class="wc-panel-header-title">Social Login</p>
                                <p class="wc-panel-header-sub">Allow users to sign in with social accounts</p>
                            </div>
                        </div>
                        <div class="wc-panel-body">
                            <form action="{{ url('update/social/login/info') }}" method="POST"
                                enctype="multipart/form-data">
                                @csrf

                                <!-- Facebook Login -->
                                <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
                                    <div
                                        style="width:28px;height:28px;border-radius:6px;background:#1877F2;display:flex;align-items:center;justify-content:center;color:#fff;font-size:12px;flex-shrink:0;">
                                        <i class="fab fa-facebook-f"></i>
                                    </div>
                                    <div style="font-size:13px;font-weight:800;color:var(--g700);">Facebook Login</div>
                                </div>

                                <div class="wc-fg">
                                    <label class="wc-label"><i class="fas fa-toggle-on"></i> Status</label>
                                    <select name="fb_login_status" class="wc-select" required>
                                        <option value="1" @if ($socialLoginInfo->fb_login_status == 1) selected @endif>
                                            ✓ Enable Facebook Login
                                        </option>
                                        <option value="0" @if ($socialLoginInfo->fb_login_status == 0) selected @endif>
                                            ✕ Disable Facebook Login
                                        </option>
                                    </select>
                                </div>
                                <div class="wc-fg">
                                    <label class="wc-label"><i class="fas fa-hashtag"></i> App ID</label>
                                    <input type="text" class="wc-input" name="fb_app_id"
                                        value="{{ $socialLoginInfo->fb_app_id }}" placeholder="e.g. 1844188565781706">
                                </div>
                                <div class="wc-fg">
                                    <label class="wc-label"><i class="fas fa-key"></i> App Secret</label>
                                    <input type="password" class="wc-input" name="fb_app_secret" value=""
                                        autocomplete="new-password" placeholder="Leave blank to keep the configured app secret">
                                </div>
                                <div class="wc-fg">
                                    <label class="wc-label"><i class="fas fa-link"></i> Redirect URL</label>
                                    <input type="text" class="wc-input" name="fb_redirect_url"
                                        value="{{ $socialLoginInfo->fb_redirect_url }}"
                                        placeholder="e.g. https://yourdomain.com/callback/facebook">
                                </div>

                                <div class="wc-section-divider">
                                    <span><i class="fab fa-google" style="margin-right:4px;"></i> Gmail Login</span>
                                </div>

                                <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
                                    <div
                                        style="width:28px;height:28px;border-radius:6px;background:#EA4335;display:flex;align-items:center;justify-content:center;color:#fff;font-size:12px;flex-shrink:0;">
                                        <i class="fab fa-google"></i>
                                    </div>
                                    <div style="font-size:13px;font-weight:800;color:var(--g700);">Gmail Login</div>
                                </div>

                                <div class="wc-fg">
                                    <label class="wc-label"><i class="fas fa-toggle-on"></i> Status</label>
                                    <select name="gmail_login_status" class="wc-select" required>
                                        <option value="1" @if ($socialLoginInfo->gmail_login_status == 1) selected @endif>
                                            ✓ Enable Gmail Login
                                        </option>
                                        <option value="0" @if ($socialLoginInfo->gmail_login_status == 0) selected @endif>
                                            ✕ Disable Gmail Login
                                        </option>
                                    </select>
                                </div>
                                <div class="wc-fg">
                                    <label class="wc-label"><i class="fas fa-hashtag"></i> Client ID</label>
                                    <input type="text" class="wc-input" name="gmail_client_id"
                                        value="{{ $socialLoginInfo->gmail_client_id }}"
                                        placeholder="e.g. 123456789-xxxxxxxx.apps.googleusercontent.com">
                                </div>
                                <div class="wc-fg">
                                    <label class="wc-label"><i class="fas fa-key"></i> Secret ID</label>
                                    <input type="password" class="wc-input" name="gmail_secret_id" value=""
                                        autocomplete="new-password" placeholder="Leave blank to keep the configured secret ID">
                                </div>
                                <div class="wc-fg">
                                    <label class="wc-label"><i class="fas fa-link"></i> Redirect URL</label>
                                    <input type="text" class="wc-input" name="gmail_redirect_url"
                                        value="{{ $socialLoginInfo->gmail_redirect_url }}"
                                        placeholder="e.g. https://yourdomain.com/callback/google">
                                </div>

                                <button type="submit" class="wc-submit">
                                    <i class="fas fa-save"></i> Save Changes
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Messenger Chat -->
                <div class="wc-panel" id="panel-mc">
                    <div class="wc-panel-card">
                        <div class="wc-panel-header">
                            <div class="wc-panel-header-icon" style="background:linear-gradient(135deg,#0099FF,#A033FF);">
                                <i class="fab fa-facebook-messenger"></i>
                            </div>
                            <div>
                                <p class="wc-panel-header-title">Messenger Chat Plugin</p>
                                <p class="wc-panel-header-sub">Add Facebook Messenger chat to your site</p>
                            </div>
                        </div>
                        <div class="wc-panel-body">
                            <form action="{{ url('update/messenger/chat/info') }}" method="POST"
                                enctype="multipart/form-data">
                                @csrf
                                <div class="wc-fg">
                                    <label class="wc-label"><i class="fas fa-toggle-on"></i> Status</label>
                                    <select name="messenger_chat_status" class="wc-select" required>
                                        <option value="1" @if ($generalInfo->messenger_chat_status == 1) selected @endif>
                                            ✓ Enable Messenger Chat
                                        </option>
                                        <option value="0" @if ($generalInfo->messenger_chat_status == 0) selected @endif>
                                            ✕ Disable Messenger Chat
                                        </option>
                                    </select>
                                </div>
                                <div class="wc-fg">
                                    <label class="wc-label"><i class="fab fa-facebook"></i> Facebook Page ID</label>
                                    <input type="text" class="wc-input" name="fb_page_id"
                                        value="{{ $generalInfo->fb_page_id }}" placeholder="e.g. 65498765432165">
                                </div>
                                <button type="submit" class="wc-submit">
                                    <i class="fas fa-save"></i> Save Changes
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Tawk.to -->
                <div class="wc-panel" id="panel-tw">
                    <div class="wc-panel-card">
                        <div class="wc-panel-header">
                            <div class="wc-panel-header-icon" style="background:#03A84E;">
                                <i class="fas fa-headset"></i>
                            </div>
                            <div>
                                <p class="wc-panel-header-title">Tawk.to Live Chat</p>
                                <p class="wc-panel-header-sub">Real-time customer support chat widget</p>
                            </div>
                        </div>
                        <div class="wc-panel-body">
                            <form action="{{ url('update/tawk/chat/info') }}" method="POST"
                                enctype="multipart/form-data">
                                @csrf
                                <div class="wc-fg">
                                    <label class="wc-label"><i class="fas fa-toggle-on"></i> Status</label>
                                    <select name="tawk_chat_status" class="wc-select" required>
                                        <option value="1" @if ($generalInfo->tawk_chat_status == 1) selected @endif>
                                            ✓ Enable Tawk.to Chat
                                        </option>
                                        <option value="0" @if ($generalInfo->tawk_chat_status == 0) selected @endif>
                                            ✕ Disable Tawk.to Chat
                                        </option>
                                    </select>
                                </div>
                                <div class="wc-fg">
                                    <label class="wc-label"><i class="fas fa-link"></i> Direct Chat Link</label>
                                    <input type="text" class="wc-input" name="tawk_chat_link"
                                        value="{{ $generalInfo->tawk_chat_link }}"
                                        placeholder="e.g. https://embed.tawk.to/5a7c31ed7591465c7077c48/default">
                                </div>
                                <button type="submit" class="wc-submit">
                                    <i class="fas fa-save"></i> Save Changes
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Crisp Chat -->
                <div class="wc-panel" id="panel-cr">
                    <div class="wc-panel-card">
                        <div class="wc-panel-header">
                            <div class="wc-panel-header-icon" style="background:#1C2440;">
                                <i class="fas fa-comments"></i>
                            </div>
                            <div>
                                <p class="wc-panel-header-title">Crisp Live Chat</p>
                                <p class="wc-panel-header-sub">Unified messaging platform for customer support</p>
                            </div>
                        </div>
                        <div class="wc-panel-body">
                            <form action="{{ url('update/crisp/chat/info') }}" method="POST"
                                enctype="multipart/form-data">
                                @csrf
                                <div class="wc-fg">
                                    <label class="wc-label"><i class="fas fa-toggle-on"></i> Status</label>
                                    <select name="crisp_chat_status" class="wc-select" required>
                                        <option value="1" @if ($generalInfo->crisp_chat_status == 1) selected @endif>
                                            ✓ Enable Crisp Chat
                                        </option>
                                        <option value="0" @if ($generalInfo->crisp_chat_status == 0) selected @endif>
                                            ✕ Disable Crisp Chat
                                        </option>
                                    </select>
                                </div>
                                <div class="wc-fg">
                                    <label class="wc-label"><i class="fas fa-globe"></i> Website ID</label>
                                    <input type="text" class="wc-input" name="crisp_website_id"
                                        value="{{ $generalInfo->crisp_website_id }}"
                                        placeholder="e.g. 7b6ec17d-256a-41e8-9732-17ff58bd515t">
                                </div>
                                <button type="submit" class="wc-submit">
                                    <i class="fas fa-save"></i> Save Changes
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

            </div><!-- end wc-panels -->
        </div><!-- end wc-shell -->

    </div><!-- end wc_component -->

    <script>
        (function() {
            const navItems = document.querySelectorAll('.wc_component .wc-nav-item');
            const panels = document.querySelectorAll('.wc_component .wc-panel');

            navItems.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const target = this.getAttribute('data-target');

                    navItems.forEach(function(b) {
                        b.classList.remove('active');
                    });
                    panels.forEach(function(p) {
                        p.classList.remove('active');
                    });

                    this.classList.add('active');
                    const panel = document.getElementById(target);
                    if (panel) panel.classList.add('active');
                });
            });
        })();

        function copyFeedUrl() {
            const url = document.getElementById('feedUrl')?.textContent.trim();
            const btn = document.getElementById('copyFeedBtn');

            if (!url) return;

            const copy = navigator.clipboard && window.isSecureContext
                ? navigator.clipboard.writeText(url)
                : new Promise(res => {
                    const t = document.createElement('textarea');
                    t.value = url;
                    document.body.appendChild(t);
                    t.select();
                    document.execCommand('copy');
                    t.remove();
                    res();
                });

            copy.then(() => {
                btn.innerHTML = '✔ Copied';
                setTimeout(() => btn.innerHTML = 'Copy', 2000);
            });
        }
    </script>
@endsection
