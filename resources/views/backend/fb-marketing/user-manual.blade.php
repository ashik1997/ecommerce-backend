@extends('backend.master')

@section('title')
    {{ $page_title }}
@endsection

@section('header_css')
    <style>
        .fbm-manual-page {
            --fbm-primary: #0f766e;
            --fbm-blue: #2563eb;
            --fbm-border: #dbeafe;
            --fbm-soft: #f8fafc;
            --fbm-green: #ecfdf5;
            --fbm-blue-soft: #eff6ff;
            --fbm-text: #0f172a;
            --fbm-muted: #64748b;
        }
        html { scroll-behavior: smooth; }
        .fbm-manual-hero {
            border-radius: 18px;
            padding: 24px;
            margin-bottom: 18px;
            color: #fff;
            background: linear-gradient(135deg, #0f766e 0%, #2563eb 100%);
            box-shadow: 0 16px 34px rgba(15, 118, 110, .18);
        }
        .fbm-manual-hero h3 { color: #fff; font-weight: 800; }
        .fbm-manual-hero p { color: rgba(255,255,255,.9); }
        .fbm-manual-note {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 7px 11px;
            margin-top: 12px;
            border: 1px solid rgba(255,255,255,.28);
            border-radius: 999px;
            background: rgba(255,255,255,.16);
            color: #fff;
            font-size: 12px;
            font-weight: 700;
        }
        .fbm-manual-stats { display: flex; flex-wrap: wrap; gap: 9px; margin-top: 15px; }
        .fbm-manual-stat { min-width: 130px; padding: 10px 12px; border: 1px solid rgba(255,255,255,.23); border-radius: 12px; background: rgba(255,255,255,.14); }
        .fbm-manual-stat strong, .fbm-manual-stat span { display: block; color: #fff; }
        .fbm-manual-stat strong { font-size: 17px; }
        .fbm-manual-stat span { font-size: 11px; opacity: .88; text-transform: uppercase; letter-spacing: .04em; }
        .fbm-manual-quick-card {
            display: block;
            height: 100%;
            padding: 14px;
            border: 1px solid var(--fbm-border);
            border-radius: 14px;
            color: var(--fbm-text);
            background: #fff;
            text-decoration: none !important;
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        }
        .fbm-manual-quick-card:hover { color: var(--fbm-text); transform: translateY(-2px); border-color: #99f6e4; box-shadow: 0 10px 22px rgba(15,118,110,.1); }
        .fbm-manual-quick-icon { display: inline-flex; width: 34px; height: 34px; align-items: center; justify-content: center; margin-bottom: 10px; border-radius: 10px; color: var(--fbm-primary); background: var(--fbm-green); }
        .fbm-manual-quick-card h6 { margin-bottom: 5px; color: var(--fbm-text); font-weight: 800; }
        .fbm-manual-quick-card p { margin-bottom: 0; color: var(--fbm-muted); font-size: 12px; line-height: 1.55; }
        .fbm-manual-shell { display: grid; grid-template-columns: 292px minmax(0, 1fr); gap: 18px; align-items: start; max-height: calc(100vh - 108px); overflow-y: auto; }
        .fbm-manual-sidebar { position: sticky; top: 10px; }
        .fbm-manual-index { max-height: calc(100vh - 112px); overflow: auto; border: 1px solid var(--fbm-border); border-radius: 16px; background: #fff; box-shadow: 0 8px 22px rgba(15,118,110,.05); }
        .fbm-manual-index-header { padding: 15px; border-bottom: 1px solid #e2e8f0; border-radius: 16px 16px 0 0; background: var(--fbm-soft); }
        .fbm-manual-index-header h5 { color: var(--fbm-text); font-weight: 800; }
        .fbm-manual-index-header small { display: block; color: var(--fbm-muted); line-height: 1.45; }
        .fbm-manual-search { width: 100%; padding: 9px 11px; margin-top: 10px; border: 1px solid #cbd5e1; border-radius: 10px; outline: 0; font-size: 13px; }
        .fbm-manual-search:focus { border-color: #14b8a6; box-shadow: 0 0 0 3px rgba(20,184,166,.12); }
        .fbm-manual-index-links { padding: 7px 0; }
        .fbm-manual-index-link { display: flex; gap: 9px; align-items: flex-start; padding: 9px 13px; color: #334155; text-decoration: none !important; border-left: 3px solid transparent; transition: all .16s ease; }
        .fbm-manual-index-link:hover, .fbm-manual-index-link.is-active { color: var(--fbm-primary); background: #f0fdfa; border-left-color: var(--fbm-primary); }
        .fbm-manual-index-number { display: inline-flex; flex: 0 0 auto; width: 23px; height: 23px; align-items: center; justify-content: center; border-radius: 999px; color: #fff; background: var(--fbm-primary); font-size: 11px; font-weight: 800; }
        .fbm-manual-index-link span:last-child { font-size: 12px; font-weight: 700; line-height: 1.45; }
        .fbm-manual-checklist { margin-top: 14px; padding: 14px; border: 1px solid #bbf7d0; border-radius: 14px; background: #f0fdf4; }
        .fbm-manual-checklist h6 { color: #166534; font-weight: 800; }
        .fbm-manual-checklist ul { padding-left: 18px; margin-bottom: 0; }
        .fbm-manual-checklist li { margin-bottom: 7px; color: #166534; font-size: 12px; line-height: 1.5; }
        .fbm-manual-card { overflow: hidden; margin-bottom: 18px; border: 1px solid var(--fbm-border); border-radius: 17px; background: #fff; box-shadow: 0 8px 22px rgba(15,118,110,.045); scroll-margin-top: 84px; }
        .fbm-manual-card-header { display: flex; gap: 12px; align-items: flex-start; padding: 17px; border-bottom: 1px solid #e2e8f0; background: linear-gradient(90deg, #ecfdf5, #eff6ff); }
        .fbm-manual-card-number { display: inline-flex; width: 36px; height: 36px; flex: 0 0 auto; align-items: center; justify-content: center; border-radius: 11px; color: #fff; background: var(--fbm-primary); font-weight: 800; }
        .fbm-manual-card-header h4 { margin-bottom: 5px; color: var(--fbm-text); font-size: 19px; font-weight: 800; }
        .fbm-manual-menu-path { display: inline-flex; flex-wrap: wrap; gap: 6px; align-items: center; padding: 5px 9px; border: 1px solid #bfdbfe; border-radius: 999px; color: #075985; background: rgba(255,255,255,.72); font-size: 11px; font-weight: 700; }
        .fbm-manual-card-body { padding: 17px; }
        .fbm-manual-block-title { display: flex; gap: 7px; align-items: center; margin-bottom: 9px; color: #475569; font-size: 12px; font-weight: 800; letter-spacing: .045em; text-transform: uppercase; }
        .fbm-manual-purpose { padding: 13px; margin-bottom: 15px; border: 1px solid #e2e8f0; border-radius: 12px; color: #334155; background: var(--fbm-soft); line-height: 1.65; }
        .fbm-manual-step { display: flex; gap: 10px; align-items: flex-start; padding: 11px 12px; margin-bottom: 8px; border: 1px solid #e2e8f0; border-radius: 12px; color: #334155; background: #fff; line-height: 1.58; }
        .fbm-manual-step-number { display: inline-flex; width: 25px; height: 25px; flex: 0 0 auto; align-items: center; justify-content: center; border-radius: 999px; color: #fff; background: var(--fbm-primary); font-size: 11px; font-weight: 800; }
        .fbm-manual-callout { padding: 13px; margin-bottom: 10px; border-radius: 12px; line-height: 1.65; }
        .fbm-manual-example { border: 1px solid #bae6fd; color: #075985; background: #f0f9ff; }
        .fbm-manual-result { border: 1px solid #bbf7d0; color: #166534; background: #f0fdf4; }
        .fbm-manual-warning { border: 1px solid #fde68a; color: #92400e; background: #fffbeb; }
        .fbm-manual-tip-list { padding: 12px 14px 4px; border: 1px solid #ddd6fe; border-radius: 12px; color: #5b21b6; background: #f5f3ff; }
        .fbm-manual-tip-list ul { padding-left: 18px; }
        .fbm-manual-tip-list li { margin-bottom: 7px; line-height: 1.5; }
        .fbm-manual-table thead th { color: #334155; background: #f8fafc; font-size: 12px; }
        .fbm-manual-table td { color: #475569; font-size: 12px; vertical-align: top; }
        .fbm-manual-no-match { display: none; padding: 18px; border: 1px solid #fde68a; border-radius: 13px; color: #92400e; background: #fffbeb; }
        @media (max-width: 991.98px) {
            .fbm-manual-shell { grid-template-columns: 1fr; max-height: none; overflow-y: visible; }
            .fbm-manual-sidebar { position: relative; top: auto; }
            .fbm-manual-index { max-height: 360px; }
        }
    </style>
@endsection

@section('content')
    <div class="page-wrapper fbm-manual-page" id="fbmManualTop">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title mb-1">{{ $page_title }}</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('fbMarketing.dashboard') }}">FB Marketing</a></li>
                            <li class="breadcrumb-item active">User Manual</li>
                        </ul>
                    </div>
                    <div class="col-auto">
                        <a class="btn btn-outline-primary btn-sm" href="{{ route('fbMarketing.user-manual.locale', $switch_locale) }}">{{ $labels['switch_text'] }}</a>
                    </div>
                </div>
            </div>

            @include('backend.fb-marketing._status-card')

            <div class="fbm-manual-hero">
                <h3 class="mb-2">{{ $hero_title }}</h3>
                <p class="mb-0">{{ $hero_subtitle }}</p>
                <div class="fbm-manual-note"><i class="feather-book-open"></i> {{ $hero_note }}</div>
                <div class="fbm-manual-stats">
                    <div class="fbm-manual-stat"><strong>{{ count($sections) }}</strong><span>{{ $labels['section_count'] }}</span></div>
                    <div class="fbm-manual-stat"><strong>{{ strtoupper($locale) }}</strong><span>{{ $labels['language'] }}</span></div>
                    <div class="fbm-manual-stat"><strong>{{ $schema_ready ? 'Ready' : 'Fallback' }}</strong><span>Manual source</span></div>
                </div>
            </div>

            @if (!$schema_ready)
                <div class="alert alert-warning">Manual table migration is pending. The page is showing the built-in FB Marketing guide.</div>
            @endif

            <div class="row mb-3">
                @foreach ($quick_start as $item)
                    <div class="col-xl-2 col-lg-4 col-md-6 mb-2">
                        <a class="fbm-manual-quick-card" href="#{{ $item['anchor'] }}">
                            <span class="fbm-manual-quick-icon"><i class="{{ $item['icon'] }}"></i></span>
                            <h6>{{ $item['title'] }}</h6>
                            <p>{{ $item['text'] }}</p>
                        </a>
                    </div>
                @endforeach
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-1">Quick links</h5>
                            <p class="text-muted mb-0">{{ $locale === 'bn' ? 'প্রয়োজনীয় FB Marketing screen দ্রুত খুলুন।' : 'Open common FB Marketing screens quickly.' }}</p>
                        </div>
                        <div>
                            @foreach ($quick_links as $link)
                                @if (Route::has($link['route']))
                                    <a class="btn btn-outline-secondary btn-sm mt-2 mr-1" href="{{ route($link['route']) }}">{{ $link['label'] }}</a>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="fbm-manual-shell">
                <aside class="fbm-manual-sidebar">
                    <div class="fbm-manual-index">
                        <div class="fbm-manual-index-header">
                            <h5 class="mb-1">{{ $labels['index_title'] }}</h5>
                            <small>{{ $labels['index_help'] }}</small>
                            <input class="fbm-manual-search" id="fbmManualSearch" type="search" placeholder="{{ $labels['search_placeholder'] }}">
                        </div>
                        <div class="fbm-manual-index-links" id="fbmManualIndex">
                            @foreach ($sections as $index => $section)
                                <a class="fbm-manual-index-link" href="#{{ $section['key'] }}" data-target="{{ $section['key'] }}">
                                    <span class="fbm-manual-index-number">{{ $index + 1 }}</span>
                                    <span>{{ $section['title'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <div class="fbm-manual-checklist">
                        <h6 class="mb-2">{{ $labels['daily_checklist_title'] }}</h6>
                        <ul>
                            @foreach ($daily_checklist as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                </aside>

                <main>
                    <div class="fbm-manual-no-match" id="fbmManualNoMatch">{{ $labels['no_match'] }}</div>

                    @foreach ($sections as $index => $section)
                        <article class="fbm-manual-card" id="{{ $section['key'] }}" data-manual-card data-search="{{ strtolower($section['title'] . ' ' . $section['menuPath'] . ' ' . $section['why'] . ' ' . implode(' ', $section['steps']) . ' ' . $section['example']) }}">
                            <div class="fbm-manual-card-header">
                                <span class="fbm-manual-card-number">{{ $index + 1 }}</span>
                                <div>
                                    <h4><i class="{{ $section['icon'] }} mr-1"></i> {{ $section['title'] }}</h4>
                                    <span class="fbm-manual-menu-path"><i class="feather-navigation"></i> {{ $labels['menu_path'] }}: {{ $section['menuPath'] }}</span>
                                </div>
                            </div>
                            <div class="fbm-manual-card-body">
                                <div class="fbm-manual-block-title"><i class="feather-help-circle"></i> {{ $labels['why'] }}</div>
                                <div class="fbm-manual-purpose">{{ $section['why'] }}</div>

                                <div class="fbm-manual-block-title"><i class="feather-list"></i> {{ $labels['steps'] }}</div>
                                @foreach ($section['steps'] as $stepIndex => $step)
                                    <div class="fbm-manual-step">
                                        <span class="fbm-manual-step-number">{{ $stepIndex + 1 }}</span>
                                        <span>{{ $step }}</span>
                                    </div>
                                @endforeach

                                <div class="row mt-3">
                                    <div class="col-lg-6 mb-2">
                                        <div class="fbm-manual-callout fbm-manual-example">
                                            <strong><i class="feather-play-circle"></i> {{ $labels['example'] }}</strong><br>{{ $section['example'] }}
                                        </div>
                                    </div>
                                    <div class="col-lg-6 mb-2">
                                        <div class="fbm-manual-callout fbm-manual-result">
                                            <strong><i class="feather-check-circle"></i> {{ $labels['expected_result'] }}</strong><br>{{ $section['result'] }}
                                        </div>
                                    </div>
                                </div>

                                <div class="fbm-manual-tip-list mb-2">
                                    <strong><i class="feather-star"></i> {{ $labels['tips'] }}</strong>
                                    <ul class="mb-0 mt-2">
                                        @foreach ($section['tips'] as $tip)
                                            <li>{{ $tip }}</li>
                                        @endforeach
                                    </ul>
                                </div>

                                <div class="fbm-manual-callout fbm-manual-warning">
                                    <strong><i class="feather-alert-triangle"></i> {{ $labels['warning'] }}</strong><br>{{ $section['warning'] }}
                                </div>

                                @if (!empty($section['troubleshooting']))
                                    <div class="fbm-manual-block-title mt-3"><i class="feather-tool"></i> {{ $labels['troubleshooting'] }}</div>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered fbm-manual-table mb-0">
                                            <thead><tr><th>{{ $labels['problem'] }}</th><th>{{ $labels['reason'] }}</th><th>{{ $labels['solution'] }}</th></tr></thead>
                                            <tbody>
                                                @foreach ($section['troubleshooting'] as $row)
                                                    <tr><td>{{ $row[0] }}</td><td>{{ $row[1] }}</td><td>{{ $row[2] }}</td></tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif

                                <a class="fbm-manual-back-top mt-3 d-inline-flex" href="#fbmManualTop"><i class="feather-arrow-up"></i> {{ $labels['back_to_top'] }}</a>
                            </div>
                        </article>
                    @endforeach

                    @if (!empty($custom_sections))
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">{{ $labels['custom_notes'] }}</h5>
                                @foreach ($custom_sections as $section)
                                    <div class="border rounded p-3 mb-2">
                                        <h6 class="mb-1">{{ $section['title'] }}</h6>
                                        <p class="text-muted mb-0">{!! nl2br(e($section['body'])) !!}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </main>
            </div>
        </div>
    </div>
@endsection

@section('footer_js')
    <script>
        (function () {
            var search = document.getElementById('fbmManualSearch');
            var cards = Array.prototype.slice.call(document.querySelectorAll('[data-manual-card]'));
            var links = Array.prototype.slice.call(document.querySelectorAll('.fbm-manual-index-link'));
            var noMatch = document.getElementById('fbmManualNoMatch');

            function normalize(value) {
                return (value || '').toString().toLowerCase().trim();
            }

            function filter() {
                var term = normalize(search ? search.value : '');
                var visible = 0;
                cards.forEach(function (card) {
                    var show = term === '' || normalize(card.getAttribute('data-search')).indexOf(term) !== -1;
                    card.style.display = show ? '' : 'none';
                    if (show) visible += 1;
                });
                links.forEach(function (link) {
                    var target = document.getElementById(link.getAttribute('data-target'));
                    link.style.display = target && target.style.display !== 'none' ? '' : 'none';
                });
                if (noMatch) noMatch.style.display = visible === 0 ? 'block' : 'none';
            }

            if (search) search.addEventListener('input', filter);
            links.forEach(function (link) {
                link.addEventListener('click', function () {
                    links.forEach(function (item) { item.classList.remove('is-active'); });
                    link.classList.add('is-active');
                });
            });
        })();
    </script>
@endsection
