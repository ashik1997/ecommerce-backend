@extends('backend.master')

@section('header_css')
    <link href="{{ url('assets') }}/css/spectrum.min.css" rel="stylesheet" type="text/css" />
    <style>
        /* ═══════════════════════════════════════════
           WEBSITE THEME PAGE
           Prefix: .wt_
        ═══════════════════════════════════════════ */
        .wt_wrap {
            --s50:  #f8fafc; --s100: #f1f5f9; --s200: #e2e8f0;
            --s300: #cbd5e1; --s400: #94a3b8; --s500: #64748b;
            --s600: #475569; --s700: #334155; --s800: #1e293b;

            --t50:  #f0fdfa; --t100: #ccfbf1;
            --t400: #2dd4bf; --t500: #14b8a6;
            --t600: #0d9488; --t700: #0f766e;

            --r: 8px; --rl: 12px;
            --sh: 0 1px 3px rgba(0,0,0,.07), 0 1px 2px rgba(0,0,0,.04);
            --shm: 0 4px 12px rgba(0,0,0,.08);

            font-family: 'Nunito', 'Segoe UI', sans-serif;
            font-size: 13px;
            color: var(--s800);
        }

        /* ── PAGE HEADER ── */
        .wt_header {
            display: flex; align-items: center;
            justify-content: space-between;
            margin-bottom: 18px; flex-wrap: wrap; gap: 10px;
        }
        .wt_header-left { display: flex; align-items: center; gap: 10px; }
        .wt_header-icon {
            width: 42px; height: 42px; background: var(--t600);
            border-radius: var(--rl);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 17px; flex-shrink: 0;
        }
        .wt_header-left h4 {
            font-size: 17px; font-weight: 700;
            margin: 0; color: var(--s800); letter-spacing: -.3px;
        }
        .wt_header-left span { font-size: 12px; color: var(--s400); display: block; }

        /* ── LAYOUT ── */
        .wt_layout {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 16px;
            align-items: start;
        }
        @media(max-width: 900px) {
            .wt_layout { grid-template-columns: 1fr; }
        }

        /* ── CARD ── */
        .wt_card {
            background: #fff;
            border-radius: var(--rl);
            border: 1.5px solid var(--s200);
            box-shadow: var(--sh);
            overflow: hidden;
        }
        .wt_card + .wt_card { margin-top: 14px; }

        .wt_card-header {
            display: flex; align-items: center; gap: 8px;
            padding: 12px 18px;
            border-bottom: 1.5px solid var(--s100);
            background: var(--s50);
            font-size: 13px; font-weight: 700; color: var(--s700);
        }
        .wt_card-header i { color: var(--t500); }

        .wt_card-body { padding: 18px; }

        /* ── COLOR PICKER GRID ── */
        .wt_color-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
        }
        @media(max-width: 640px) {
            .wt_color-grid { grid-template-columns: 1fr 1fr; }
        }

        /* ── SINGLE COLOR FIELD ── */
        .wt_color-field {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .wt_color-label {
            font-size: 11px; font-weight: 700; color: var(--s500);
            text-transform: uppercase; letter-spacing: .5px;
            display: flex; align-items: center; gap: 5px;
        }
        .wt_color-label i { color: var(--t400); font-size: 10px; }

        /* ── Spectrum overrides ── */
        .sp-replacer {
            border-radius: var(--r) !important;
            border: 1.5px solid var(--s200) !important;
            background: #fff !important;
            box-shadow: none !important;
            padding: 5px !important;
            width: 100% !important;
            height: 38px !important;
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
            transition: border-color .15s, box-shadow .15s !important;
            cursor: pointer !important;
        }
        .sp-replacer:hover {
            border-color: var(--t400) !important;
        }
        .sp-replacer.sp-active {
            border-color: var(--t400) !important;
            box-shadow: 0 0 0 3px rgba(20,184,166,.12) !important;
        }
        .sp-preview {
            border: 1.5px solid var(--s200) !important;
            border-radius: 5px !important;
            width: 26px !important;
            height: 26px !important;
            flex-shrink: 0 !important;
            margin: 0 !important;
        }
        .sp-dd {
            font-size: 11px !important;
            color: var(--s500) !important;
            flex: 1 !important;
            font-family: 'Courier New', monospace !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            white-space: nowrap !important;
        }
        /* Dropdown container */
        .sp-container {
            border-radius: var(--rl) !important;
            border: 1.5px solid var(--s200) !important;
            box-shadow: var(--shm) !important;
        }
        /* Input inside the picker popup */
        .sp-input-container .sp-input {
            border-radius: var(--r) !important;
            border: 1.5px solid var(--s200) !important;
            font-size: 12px !important;
            font-family: 'Courier New', monospace !important;
        }

        .wt_error {
            font-size: 11px; color: #ef4444;
            display: flex; align-items: center; gap: 4px;
        }

        /* ── PREVIEW PANEL ── */
        .wt_preview-title {
            font-size: 11px; font-weight: 700; color: var(--s500);
            text-transform: uppercase; letter-spacing: .5px;
            margin-bottom: 12px;
        }
        .wt_swatch-list { display: flex; flex-direction: column; gap: 8px; }

        .wt_swatch-row {
            display: flex; align-items: center; gap: 10px;
        }
        .wt_swatch-box {
            width: 36px; height: 36px;
            border-radius: var(--r);
            border: 1.5px solid var(--s200);
            flex-shrink: 0;
            transition: background .2s;
        }
        .wt_swatch-info { flex: 1; min-width: 0; }
        .wt_swatch-name {
            font-size: 12px; font-weight: 700; color: var(--s700);
        }
        .wt_swatch-hex {
            font-size: 11px; color: var(--s400);
            font-family: 'Courier New', monospace;
        }

        /* ── WEBSITE MOCKUP STRIP ── */
        .wt_mockup {
            border: 1.5px solid var(--s200);
            border-radius: var(--r);
            overflow: hidden;
            margin-top: 14px;
        }
        .wt_mockup-nav {
            height: 36px;
            display: flex; align-items: center;
            padding: 0 12px; gap: 8px;
            transition: background .2s;
        }
        .wt_mockup-nav-dot {
            width: 8px; height: 8px; border-radius: 50%;
            background: rgba(255,255,255,.45);
        }
        .wt_mockup-nav-bar {
            flex: 1; height: 8px; border-radius: 4px;
            background: rgba(255,255,255,.25);
        }
        .wt_mockup-body { padding: 12px; background: #fff; }
        .wt_mockup-heading {
            font-size: 13px; font-weight: 700; margin-bottom: 4px;
            transition: color .2s;
        }
        .wt_mockup-text {
            font-size: 11px; line-height: 1.5; margin-bottom: 10px;
            transition: color .2s;
        }
        .wt_mockup-btn {
            display: inline-block;
            font-size: 11px; font-weight: 700;
            padding: 5px 12px; border-radius: 5px;
            color: #fff; transition: background .2s;
        }
        .wt_mockup-divider {
            height: 1px; margin-top: 10px;
            transition: background .2s;
        }

        /* ── FOOTER ACTIONS ── */
        .wt_footer {
            padding: 14px 18px;
            border-top: 1.5px solid var(--s100);
            background: var(--s50);
            display: flex; justify-content: flex-end;
            align-items: center; gap: 10px;
        }
        .wt_btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 20px; border-radius: var(--r);
            font-size: 13px; font-weight: 700;
            border: 1.5px solid transparent;
            cursor: pointer; font-family: inherit;
            transition: all .15s; text-decoration: none; line-height: 1;
        }
        .wt_btn-primary {
            background: var(--t600); border-color: var(--t600); color: #fff;
            box-shadow: 0 2px 6px rgba(13,148,136,.3);
        }
        .wt_btn-primary:hover {
            background: var(--t700); border-color: var(--t700);
            transform: translateY(-1px); color: #fff; text-decoration: none;
        }
        .wt_btn-ghost {
            background: #fff; border-color: var(--s200); color: var(--s600);
        }
        .wt_btn-ghost:hover {
            background: var(--s100); border-color: var(--s300);
            color: var(--s700); text-decoration: none;
        }
    </style>
@endsection

@section('page_title')
    Website Config
@endsection
@section('page_heading')
    Website Theme Color
@endsection

@section('content')
<div class="wt_wrap">

    <!-- Page Header -->
    <div class="wt_header">
        <div class="wt_header-left">
            <div class="wt_header-icon"><i class="fas fa-palette"></i></div>
            <div>
                <h4>Website Theme Colors</h4>
                <span>Customize storefront brand and content colors</span>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ url('update/website/theme/color') }}" enctype="multipart/form-data">
        @csrf

        <div class="wt_layout">

            <!-- ── Left: Color editors ── -->
            <div>

                <!-- Brand Colors -->
                <div class="wt_card">
                    <div class="wt_card-header">
                        <i class="fas fa-swatchbook"></i> Brand Colors
                    </div>
                    <div class="wt_card-body">
                        <div class="wt_color-grid">

                            <div class="wt_color-field">
                                <label class="wt_color-label" for="primary_color">
                                    <i class="fas fa-circle"></i> Primary
                                </label>
                                <input type="text" name="primary_color" id="primary_color"
                                    value="{{ $data->primary_color }}">
                                @error('primary_color')
                                    <span class="wt_error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="wt_color-field">
                                <label class="wt_color-label" for="secondary_color">
                                    <i class="fas fa-circle"></i> Secondary
                                </label>
                                <input type="text" name="secondary_color" id="secondary_color"
                                    value="{{ $data->secondary_color }}">
                                @error('secondary_color')
                                    <span class="wt_error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="wt_color-field">
                                <label class="wt_color-label" for="tertiary_color">
                                    <i class="fas fa-circle"></i> Tertiary
                                </label>
                                <input type="text" name="tertiary_color" id="tertiary_color"
                                    value="{{ $data->tertiary_color }}">
                                @error('tertiary_color')
                                    <span class="wt_error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</span>
                                @enderror
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Content Colors -->
                <div class="wt_card">
                    <div class="wt_card-header">
                        <i class="fas fa-font"></i> Content Colors
                    </div>
                    <div class="wt_card-body">
                        <div class="wt_color-grid">

                            <div class="wt_color-field">
                                <label class="wt_color-label" for="title_color">
                                    <i class="fas fa-heading"></i> Title
                                </label>
                                <input type="text" name="title_color" id="title_color"
                                    value="{{ $data->title_color }}">
                                @error('title_color')
                                    <span class="wt_error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="wt_color-field">
                                <label class="wt_color-label" for="paragraph_color">
                                    <i class="fas fa-align-left"></i> Paragraph
                                </label>
                                <input type="text" name="paragraph_color" id="paragraph_color"
                                    value="{{ $data->paragraph_color }}">
                                @error('paragraph_color')
                                    <span class="wt_error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="wt_color-field">
                                <label class="wt_color-label" for="border_color">
                                    <i class="fas fa-border-style"></i> Border
                                </label>
                                <input type="text" name="border_color" id="border_color"
                                    value="{{ $data->border_color }}">
                                @error('border_color')
                                    <span class="wt_error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</span>
                                @enderror
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Save Bar -->
                <div class="wt_card">
                    <div class="wt_footer">
                        <a href="{{ url('/home') }}" class="wt_btn wt_btn-ghost">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <button type="submit" class="wt_btn wt_btn-primary">
                            <i class="fas fa-save"></i> Save Theme
                        </button>
                    </div>
                </div>

            </div>

            <!-- ── Right: Live Preview ── -->
            <div class="wt_card">
                <div class="wt_card-header">
                    <i class="fas fa-eye"></i> Live Preview
                </div>
                <div class="wt_card-body">

                    <!-- Swatch list -->
                    <p class="wt_preview-title">Color Palette</p>
                    <div class="wt_swatch-list">
                        <div class="wt_swatch-row">
                            <div class="wt_swatch-box" id="swatch-primary" style="background:{{ $data->primary_color }};"></div>
                            <div class="wt_swatch-info">
                                <div class="wt_swatch-name">Primary</div>
                                <div class="wt_swatch-hex" id="hex-primary">{{ $data->primary_color }}</div>
                            </div>
                        </div>
                        <div class="wt_swatch-row">
                            <div class="wt_swatch-box" id="swatch-secondary" style="background:{{ $data->secondary_color }};"></div>
                            <div class="wt_swatch-info">
                                <div class="wt_swatch-name">Secondary</div>
                                <div class="wt_swatch-hex" id="hex-secondary">{{ $data->secondary_color }}</div>
                            </div>
                        </div>
                        <div class="wt_swatch-row">
                            <div class="wt_swatch-box" id="swatch-tertiary" style="background:{{ $data->tertiary_color }};"></div>
                            <div class="wt_swatch-info">
                                <div class="wt_swatch-name">Tertiary</div>
                                <div class="wt_swatch-hex" id="hex-tertiary">{{ $data->tertiary_color }}</div>
                            </div>
                        </div>
                        <div class="wt_swatch-row">
                            <div class="wt_swatch-box" id="swatch-title" style="background:{{ $data->title_color }};"></div>
                            <div class="wt_swatch-info">
                                <div class="wt_swatch-name">Title</div>
                                <div class="wt_swatch-hex" id="hex-title">{{ $data->title_color }}</div>
                            </div>
                        </div>
                        <div class="wt_swatch-row">
                            <div class="wt_swatch-box" id="swatch-paragraph" style="background:{{ $data->paragraph_color }};"></div>
                            <div class="wt_swatch-info">
                                <div class="wt_swatch-name">Paragraph</div>
                                <div class="wt_swatch-hex" id="hex-paragraph">{{ $data->paragraph_color }}</div>
                            </div>
                        </div>
                        <div class="wt_swatch-row">
                            <div class="wt_swatch-box" id="swatch-border" style="background:{{ $data->border_color }}; border-color:{{ $data->border_color }};"></div>
                            <div class="wt_swatch-info">
                                <div class="wt_swatch-name">Border</div>
                                <div class="wt_swatch-hex" id="hex-border">{{ $data->border_color }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Mini mockup -->
                    <div class="wt_mockup" style="margin-top:18px;">
                        <div class="wt_mockup-nav" id="mock-nav" style="background:{{ $data->primary_color }};">
                            <div class="wt_mockup-nav-dot"></div>
                            <div class="wt_mockup-nav-dot"></div>
                            <div class="wt_mockup-nav-dot"></div>
                            <div class="wt_mockup-nav-bar"></div>
                        </div>
                        <div class="wt_mockup-body">
                            <div class="wt_mockup-heading" id="mock-heading" style="color:{{ $data->title_color }};">
                                Page Heading
                            </div>
                            <div class="wt_mockup-text" id="mock-text" style="color:{{ $data->paragraph_color }};">
                                Sample paragraph text to preview how your content will look on the storefront.
                            </div>
                            <div class="wt_mockup-btn" id="mock-btn" style="background:{{ $data->primary_color }};">
                                Shop Now
                            </div>
                            <div class="wt_mockup-divider" id="mock-divider" style="background:{{ $data->border_color }};"></div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </form>
</div>
@endsection


@section('footer_js')
    <script src="{{ url('assets') }}/js/spectrum.min.js"></script>
    <script>
    (function () {
        /* map: input id → { swatch, hex display, optional mock elements } */
        var fields = [
            { id: 'primary_color',   swatchId: 'swatch-primary',   hexId: 'hex-primary',   mocks: ['mock-nav','mock-btn'] },
            { id: 'secondary_color', swatchId: 'swatch-secondary',  hexId: 'hex-secondary',  mocks: [] },
            { id: 'tertiary_color',  swatchId: 'swatch-tertiary',   hexId: 'hex-tertiary',   mocks: [] },
            { id: 'title_color',     swatchId: 'swatch-title',      hexId: 'hex-title',      mocks: ['mock-heading'] },
            { id: 'paragraph_color', swatchId: 'swatch-paragraph',  hexId: 'hex-paragraph',  mocks: ['mock-text'] },
            { id: 'border_color',    swatchId: 'swatch-border',     hexId: 'hex-border',     mocks: ['mock-divider'] },
        ];

        fields.forEach(function (f) {
            var $input = $('#' + f.id);

            $input.spectrum({
                preferredFormat: 'hex',
                showAlpha: false,
                change: function (color) {
                    if (!color) return;
                    updatePreview(f, color.toHexString());
                },
                move: function (color) {
                    if (!color) return;
                    updatePreview(f, color.toHexString());
                },
            });
        });

        function updatePreview(f, hex) {
            /* swatch box */
            var swatch = document.getElementById(f.swatchId);
            if (swatch) swatch.style.background = hex;

            /* hex label */
            var hexEl = document.getElementById(f.hexId);
            if (hexEl) hexEl.textContent = hex;

            /* mockup elements */
            (f.mocks || []).forEach(function (mockId) {
                var el = document.getElementById(mockId);
                if (!el) return;
                if (mockId === 'mock-divider') {
                    el.style.background = hex;
                } else if (mockId === 'mock-heading' || mockId === 'mock-text') {
                    el.style.color = hex;
                } else {
                    el.style.background = hex;
                }
            });
        }
    }());
    </script>
@endsection
