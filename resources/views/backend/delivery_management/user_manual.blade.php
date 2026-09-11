@extends('backend.master')

@section('page_title', $page_title)
@section('page_heading', $page_title)

@section('header_css')
    <style>
        .dm-manual-page {
            --dm-primary: #0f766e;
            --dm-blue: #2563eb;
            --dm-border: #dbeafe;
            --dm-soft: #f8fafc;
            --dm-green: #ecfdf5;
            --dm-text: #0f172a;
            --dm-muted: #64748b;
        }
        html { scroll-behavior: smooth; }
        .dm-manual-hero {
            padding: 24px;
            margin-bottom: 18px;
            border-radius: 8px;
            color: #fff;
            background: linear-gradient(135deg, #0f766e 0%, #2563eb 100%);
            box-shadow: 0 16px 34px rgba(15, 118, 110, .16);
        }
        .dm-manual-hero h3, .dm-manual-hero p { color: #fff; }
        .dm-manual-note {
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
        .dm-manual-stats { display: flex; flex-wrap: wrap; gap: 9px; margin-top: 15px; }
        .dm-manual-stat { min-width: 130px; padding: 10px 12px; border: 1px solid rgba(255,255,255,.23); border-radius: 8px; background: rgba(255,255,255,.14); }
        .dm-manual-stat strong, .dm-manual-stat span { display: block; color: #fff; }
        .dm-manual-stat span { font-size: 11px; opacity: .88; text-transform: uppercase; }
        .dm-manual-quick-card {
            display: block;
            height: 100%;
            padding: 14px;
            border: 1px solid var(--dm-border);
            border-radius: 8px;
            color: var(--dm-text);
            background: #fff;
            text-decoration: none !important;
        }
        .dm-manual-quick-card:hover { color: var(--dm-text); border-color: #99f6e4; box-shadow: 0 10px 22px rgba(15,118,110,.1); }
        .dm-manual-quick-icon { display: inline-flex; width: 34px; height: 34px; align-items: center; justify-content: center; margin-bottom: 10px; border-radius: 8px; color: var(--dm-primary); background: var(--dm-green); }
        .dm-manual-quick-card h6 { margin-bottom: 5px; color: var(--dm-text); font-weight: 800; }
        .dm-manual-quick-card p { margin-bottom: 0; color: var(--dm-muted); font-size: 12px; line-height: 1.55; }
        .dm-manual-shell { display: grid; grid-template-columns: 292px minmax(0, 1fr); gap: 18px; align-items: start; }
        .dm-manual-sidebar { position: sticky; top: 10px; }
        .dm-manual-index { max-height: calc(100vh - 112px); overflow: auto; border: 1px solid var(--dm-border); border-radius: 8px; background: #fff; }
        .dm-manual-index-header { padding: 15px; border-bottom: 1px solid #e2e8f0; background: var(--dm-soft); }
        .dm-manual-search { width: 100%; padding: 9px 11px; margin-top: 10px; border: 1px solid #cbd5e1; border-radius: 8px; outline: 0; font-size: 13px; }
        .dm-manual-index-link { display: flex; gap: 9px; align-items: flex-start; padding: 9px 13px; color: #334155; text-decoration: none !important; border-left: 3px solid transparent; }
        .dm-manual-index-link:hover, .dm-manual-index-link.is-active { color: var(--dm-primary); background: #f0fdfa; border-left-color: var(--dm-primary); }
        .dm-manual-index-number { display: inline-flex; flex: 0 0 auto; width: 23px; height: 23px; align-items: center; justify-content: center; border-radius: 999px; color: #fff; background: var(--dm-primary); font-size: 11px; font-weight: 800; }
        .dm-manual-checklist { margin-top: 14px; padding: 14px; border: 1px solid #bbf7d0; border-radius: 8px; background: #f0fdf4; }
        .dm-manual-checklist h6 { color: #166534; font-weight: 800; }
        .dm-manual-checklist li { margin-bottom: 7px; color: #166534; font-size: 12px; line-height: 1.5; }
        .dm-manual-card { overflow: hidden; margin-bottom: 18px; border: 1px solid var(--dm-border); border-radius: 8px; background: #fff; scroll-margin-top: 84px; }
        .dm-manual-card-header { display: flex; gap: 12px; align-items: flex-start; padding: 17px; border-bottom: 1px solid #e2e8f0; background: linear-gradient(90deg, #ecfdf5, #eff6ff); }
        .dm-manual-card-number { display: inline-flex; width: 36px; height: 36px; flex: 0 0 auto; align-items: center; justify-content: center; border-radius: 8px; color: #fff; background: var(--dm-primary); font-weight: 800; }
        .dm-manual-card-header h4 { margin-bottom: 5px; color: var(--dm-text); font-size: 19px; font-weight: 800; }
        .dm-manual-menu-path { display: inline-flex; flex-wrap: wrap; gap: 6px; align-items: center; padding: 5px 9px; border: 1px solid #bfdbfe; border-radius: 999px; color: #075985; background: rgba(255,255,255,.72); font-size: 11px; font-weight: 700; }
        .dm-manual-card-body { padding: 17px; }
        .dm-manual-block-title { display: flex; gap: 7px; align-items: center; margin-bottom: 9px; color: #475569; font-size: 12px; font-weight: 800; letter-spacing: .045em; text-transform: uppercase; }
        .dm-manual-purpose { padding: 13px; margin-bottom: 15px; border: 1px solid #e2e8f0; border-radius: 8px; color: #334155; background: var(--dm-soft); line-height: 1.65; }
        .dm-manual-step { display: flex; gap: 10px; align-items: flex-start; padding: 11px 12px; margin-bottom: 8px; border: 1px solid #e2e8f0; border-radius: 8px; color: #334155; background: #fff; line-height: 1.58; }
        .dm-manual-step-number { display: inline-flex; width: 25px; height: 25px; flex: 0 0 auto; align-items: center; justify-content: center; border-radius: 999px; color: #fff; background: var(--dm-primary); font-size: 11px; font-weight: 800; }
        .dm-manual-callout { padding: 13px; margin-bottom: 10px; border-radius: 8px; line-height: 1.65; }
        .dm-manual-example { border: 1px solid #bae6fd; color: #075985; background: #f0f9ff; }
        .dm-manual-result { border: 1px solid #bbf7d0; color: #166534; background: #f0fdf4; }
        .dm-manual-warning { border: 1px solid #fde68a; color: #92400e; background: #fffbeb; }
        .dm-manual-tip-list { padding: 12px 14px 4px; border: 1px solid #ddd6fe; border-radius: 8px; color: #5b21b6; background: #f5f3ff; }
        .dm-manual-no-match { display: none; padding: 18px; border: 1px solid #fde68a; border-radius: 8px; color: #92400e; background: #fffbeb; }
        @media (max-width: 991.98px) {
            .dm-manual-shell { grid-template-columns: 1fr; }
            .dm-manual-sidebar { position: relative; top: auto; }
            .dm-manual-index { max-height: 360px; }
        }
    </style>
@endsection

@section('content')
    @include('backend.delivery_management.partials.nav')

    <div class="dm-manual-page" id="dmManualTop">
        <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
            <div>
                <h4 class="mb-1">{{ $page_title }}</h4>
                <small class="text-muted">Delivery Management / User Manual</small>
            </div>
            <a class="btn btn-outline-primary btn-sm" href="{{ route('delivery-management.user-manual.locale', $switch_locale) }}">{{ $labels['switch_text'] }}</a>
        </div>

        <div class="dm-manual-hero">
            <h3 class="mb-2">{{ $hero_title }}</h3>
            <p class="mb-0">{{ $hero_subtitle }}</p>
            <div class="dm-manual-note"><i class="feather-book-open"></i> {{ $hero_note }}</div>
            <div class="dm-manual-stats">
                <div class="dm-manual-stat"><strong>{{ count($sections) }}</strong><span>{{ $labels['section_count'] }}</span></div>
                <div class="dm-manual-stat"><strong>{{ strtoupper($locale) }}</strong><span>{{ $labels['language'] }}</span></div>
                <div class="dm-manual-stat"><strong>Read-only</strong><span>Manual source</span></div>
            </div>
        </div>

        <div class="row mb-3">
            @foreach ($quick_start as $item)
                <div class="col-xl-2 col-lg-4 col-md-6 mb-2">
                    <a class="dm-manual-quick-card" href="#{{ $item['anchor'] }}">
                        <span class="dm-manual-quick-icon"><i class="{{ $item['icon'] }}"></i></span>
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
                        <p class="text-muted mb-0">{{ $locale === 'bn' ? 'প্রয়োজনীয় Delivery Management screen দ্রুত খুলুন।' : 'Open common Delivery Management screens quickly.' }}</p>
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

        <div class="dm-manual-shell">
            <aside class="dm-manual-sidebar">
                <div class="dm-manual-index">
                    <div class="dm-manual-index-header">
                        <h5 class="mb-1">{{ $labels['index_title'] }}</h5>
                        <small>{{ $labels['index_help'] }}</small>
                        <input class="dm-manual-search" id="dmManualSearch" type="search" placeholder="{{ $labels['search_placeholder'] }}">
                    </div>
                    <div id="dmManualIndex">
                        @foreach ($sections as $index => $section)
                            <a class="dm-manual-index-link" href="#{{ $section['key'] }}" data-target="{{ $section['key'] }}">
                                <span class="dm-manual-index-number">{{ $index + 1 }}</span>
                                <span>{{ $section['title'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>

                <div class="dm-manual-checklist">
                    <h6 class="mb-2">{{ $labels['daily_checklist_title'] }}</h6>
                    <ul class="mb-0 pl-3">
                        @foreach ($daily_checklist as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </div>
            </aside>

            <main>
                <div class="dm-manual-no-match" id="dmManualNoMatch">{{ $labels['no_match'] }}</div>

                @foreach ($sections as $index => $section)
                    <article class="dm-manual-card" id="{{ $section['key'] }}" data-manual-card data-search="{{ strtolower($section['title'] . ' ' . $section['menuPath'] . ' ' . $section['why'] . ' ' . implode(' ', $section['steps']) . ' ' . $section['example']) }}">
                        <div class="dm-manual-card-header">
                            <span class="dm-manual-card-number">{{ $index + 1 }}</span>
                            <div>
                                <h4><i class="{{ $section['icon'] }} mr-1"></i> {{ $section['title'] }}</h4>
                                <span class="dm-manual-menu-path"><i class="feather-navigation"></i> {{ $labels['menu_path'] }}: {{ $section['menuPath'] }}</span>
                            </div>
                        </div>
                        <div class="dm-manual-card-body">
                            <div class="dm-manual-block-title"><i class="feather-help-circle"></i> {{ $labels['why'] }}</div>
                            <div class="dm-manual-purpose">{{ $section['why'] }}</div>

                            <div class="dm-manual-block-title"><i class="feather-list"></i> {{ $labels['steps'] }}</div>
                            @foreach ($section['steps'] as $stepIndex => $step)
                                <div class="dm-manual-step">
                                    <span class="dm-manual-step-number">{{ $stepIndex + 1 }}</span>
                                    <span>{{ $step }}</span>
                                </div>
                            @endforeach

                            <div class="row mt-3">
                                <div class="col-lg-6 mb-2">
                                    <div class="dm-manual-callout dm-manual-example">
                                        <strong><i class="feather-play-circle"></i> {{ $labels['example'] }}</strong><br>{{ $section['example'] }}
                                    </div>
                                </div>
                                <div class="col-lg-6 mb-2">
                                    <div class="dm-manual-callout dm-manual-result">
                                        <strong><i class="feather-check-circle"></i> {{ $labels['expected_result'] }}</strong><br>{{ $section['result'] }}
                                    </div>
                                </div>
                            </div>

                            <div class="dm-manual-tip-list mb-2">
                                <strong><i class="feather-star"></i> {{ $labels['tips'] }}</strong>
                                <ul class="mb-0 mt-2 pl-3">
                                    @foreach ($section['tips'] as $tip)
                                        <li>{{ $tip }}</li>
                                    @endforeach
                                </ul>
                            </div>

                            <div class="dm-manual-callout dm-manual-warning">
                                <strong><i class="feather-alert-triangle"></i> {{ $labels['warning'] }}</strong><br>{{ $section['warning'] }}
                            </div>

                            <div class="dm-manual-block-title mt-3"><i class="feather-tool"></i> {{ $labels['troubleshooting'] }}</div>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead><tr><th>{{ $labels['problem'] }}</th><th>{{ $labels['reason'] }}</th><th>{{ $labels['solution'] }}</th></tr></thead>
                                    <tbody>
                                        @foreach ($section['troubleshooting'] as $row)
                                            <tr><td>{{ $row[0] }}</td><td>{{ $row[1] }}</td><td>{{ $row[2] }}</td></tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </article>
                @endforeach
            </main>
        </div>
    </div>
@endsection

@section('footer_js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var search = document.getElementById('dmManualSearch');
            var cards = Array.prototype.slice.call(document.querySelectorAll('[data-manual-card]'));
            var noMatch = document.getElementById('dmManualNoMatch');
            var links = Array.prototype.slice.call(document.querySelectorAll('.dm-manual-index-link'));

            function filterManual() {
                var term = (search.value || '').toLowerCase().trim();
                var visible = 0;
                cards.forEach(function (card) {
                    var matched = !term || (card.getAttribute('data-search') || '').indexOf(term) !== -1;
                    card.style.display = matched ? '' : 'none';
                    if (matched) visible++;
                });
                noMatch.style.display = visible ? 'none' : 'block';
            }

            search.addEventListener('input', filterManual);
            links.forEach(function (link) {
                link.addEventListener('click', function () {
                    links.forEach(function (item) { item.classList.remove('is-active'); });
                    link.classList.add('is-active');
                });
            });
        });
    </script>
@endsection
