@extends('backend.master')

@section('header_css')
    <style>
        .crm-manual-page {
            --manual-primary: #0f766e;
            --manual-primary-dark: #115e59;
            --manual-blue: #0284c7;
            --manual-border: #dbeafe;
            --manual-soft: #f8fafc;
            --manual-soft-green: #ecfdf5;
            --manual-soft-blue: #eff6ff;
            --manual-text: #0f172a;
            --manual-muted: #64748b;
        }
        html { scroll-behavior: smooth; }
        .crm-manual-hero {
            border-radius: 20px;
            color: #fff;
            padding: 24px;
            margin-bottom: 18px;
            background: linear-gradient(135deg, #0f766e 0%, #0284c7 100%);
            box-shadow: 0 16px 35px rgba(15, 118, 110, .18);
        }
        .crm-manual-hero h3 { color: #fff; font-weight: 800; letter-spacing: -.02em; }
        .crm-manual-hero p { color: rgba(255,255,255,.9); }
        .crm-manual-hero-note {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 7px 11px;
            margin-top: 14px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            background: rgba(255,255,255,.16);
            border: 1px solid rgba(255,255,255,.28);
        }
        .crm-manual-hero-stats { display: flex; flex-wrap: wrap; gap: 9px; margin-top: 15px; }
        .crm-manual-stat {
            min-width: 120px;
            border-radius: 12px;
            padding: 10px 12px;
            background: rgba(255,255,255,.14);
            border: 1px solid rgba(255,255,255,.23);
        }
        .crm-manual-stat strong, .crm-manual-stat span { display: block; color: #fff; }
        .crm-manual-stat strong { font-size: 17px; }
        .crm-manual-stat span { font-size: 11px; opacity: .88; text-transform: uppercase; letter-spacing: .04em; }
        .crm-manual-quick-card {
            display: block;
            height: 100%;
            padding: 14px;
            border: 1px solid #dbeafe;
            border-radius: 14px;
            background: #fff;
            color: var(--manual-text);
            text-decoration: none !important;
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        }
        .crm-manual-quick-card:hover {
            color: var(--manual-text);
            transform: translateY(-3px);
            border-color: #99f6e4;
            box-shadow: 0 10px 22px rgba(15,118,110,.1);
        }
        .crm-manual-quick-icon {
            display: inline-flex;
            width: 34px;
            height: 34px;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
            border-radius: 10px;
            color: var(--manual-primary);
            background: var(--manual-soft-green);
        }
        .crm-manual-quick-card h6 { margin-bottom: 5px; color: var(--manual-text); font-weight: 800; }
        .crm-manual-quick-card p { margin-bottom: 0; color: var(--manual-muted); font-size: 12px; line-height: 1.55; }
        .crm-manual-shell {
            display: grid;
            grid-template-columns: 292px minmax(0, 1fr);
            gap: 18px;
            align-items: start;
            max-height: calc(100vh - 145px);
            overflow-y: scroll;
            scroll-behavior: smooth;
        }
        .crm-manual-sidebar { position: sticky; top: 10px; }
        .crm-manual-index {
            max-height: calc(100vh - 112px);
            overflow: auto;
            border: 1px solid var(--manual-border);
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 8px 22px rgba(15,118,110,.05);
        }
        .crm-manual-index-header {
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
            background: var(--manual-soft);
            border-radius: 16px 16px 0 0;
        }
        .crm-manual-index-header h5 { font-weight: 800; color: var(--manual-text); }
        .crm-manual-index-header small { display: block; color: var(--manual-muted); line-height: 1.45; }
        .crm-manual-search {
            width: 100%;
            padding: 9px 11px;
            margin-top: 10px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            outline: 0;
            font-size: 13px;
        }
        .crm-manual-search:focus { border-color: #14b8a6; box-shadow: 0 0 0 3px rgba(20,184,166,.12); }
        .crm-manual-index-links { padding: 7px 0; }
        .crm-manual-index-link {
            display: flex;
            gap: 9px;
            align-items: flex-start;
            padding: 9px 13px;
            color: #334155;
            text-decoration: none !important;
            border-left: 3px solid transparent;
            transition: all .16s ease;
        }
        .crm-manual-index-link:hover,
        .crm-manual-index-link.is-active { color: var(--manual-primary); background: #f0fdfa; border-left-color: var(--manual-primary); }
        .crm-manual-index-number {
            display: inline-flex;
            flex: 0 0 auto;
            width: 23px;
            height: 23px;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            color: #fff;
            background: var(--manual-primary);
            font-size: 11px;
            font-weight: 800;
        }
        .crm-manual-index-link span:last-child { font-size: 12px; font-weight: 700; line-height: 1.45; }
        .crm-manual-checklist {
            margin-top: 14px;
            padding: 14px;
            border: 1px solid #bbf7d0;
            border-radius: 14px;
            background: #f0fdf4;
        }
        .crm-manual-checklist h6 { color: #166534; font-weight: 800; }
        .crm-manual-checklist ul { padding-left: 18px; margin-bottom: 0; }
        .crm-manual-checklist li { margin-bottom: 7px; color: #166534; font-size: 12px; line-height: 1.5; }
        .crm-manual-checklist li:last-child { margin-bottom: 0; }
        .crm-manual-progress-wrap { height: 4px; overflow: hidden; background: #e2e8f0; }
        .crm-manual-progress { width: 0; height: 100%; background: linear-gradient(90deg, #0f766e, #0284c7); transition: width .18s ease; }
        .crm-manual-card {
            overflow: hidden;
            margin-bottom: 18px;
            border: 1px solid var(--manual-border);
            border-radius: 17px;
            background: #fff;
            box-shadow: 0 8px 22px rgba(15,118,110,.045);
            scroll-margin-top: 84px;
        }
        .crm-manual-card-header {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            padding: 17px;
            border-bottom: 1px solid #e2e8f0;
            background: linear-gradient(90deg, #ecfdf5, #eff6ff);
        }
        .crm-manual-card-number {
            display: inline-flex;
            width: 36px;
            height: 36px;
            flex: 0 0 auto;
            align-items: center;
            justify-content: center;
            border-radius: 11px;
            color: #fff;
            background: var(--manual-primary);
            font-weight: 800;
        }
        .crm-manual-card-header h4 { margin-bottom: 5px; color: var(--manual-text); font-size: 19px; font-weight: 800; }
        .crm-manual-menu-path {
            display: inline-flex;
            flex-wrap: wrap;
            gap: 6px;
            align-items: center;
            padding: 5px 9px;
            border: 1px solid #bfdbfe;
            border-radius: 999px;
            color: #075985;
            background: rgba(255,255,255,.72);
            font-size: 11px;
            font-weight: 700;
        }
        .crm-manual-card-body { padding: 17px; }
        .crm-manual-block-title {
            display: flex;
            gap: 7px;
            align-items: center;
            margin-bottom: 9px;
            color: #475569;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .045em;
            text-transform: uppercase;
        }
        .crm-manual-purpose {
            padding: 13px;
            margin-bottom: 15px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            color: #334155;
            background: var(--manual-soft);
            line-height: 1.65;
        }
        .crm-manual-step {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            padding: 11px 12px;
            margin-bottom: 8px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            color: #334155;
            background: #fff;
            line-height: 1.58;
        }
        .crm-manual-step-number {
            display: inline-flex;
            width: 25px;
            height: 25px;
            flex: 0 0 auto;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            color: #fff;
            background: var(--manual-primary);
            font-size: 11px;
            font-weight: 800;
        }
        .crm-manual-callout { padding: 13px; margin-bottom: 10px; border-radius: 12px; line-height: 1.65; }
        .crm-manual-example { border: 1px solid #bae6fd; color: #075985; background: #f0f9ff; }
        .crm-manual-result { border: 1px solid #bbf7d0; color: #166534; background: #f0fdf4; }
        .crm-manual-warning { border: 1px solid #fde68a; color: #92400e; background: #fffbeb; }
        .crm-manual-tip-list { padding: 12px 14px 4px; border: 1px solid #ddd6fe; border-radius: 12px; color: #5b21b6; background: #f5f3ff; }
        .crm-manual-tip-list ul { padding-left: 18px; }
        .crm-manual-tip-list li { margin-bottom: 7px; line-height: 1.5; }
        .crm-manual-table thead th { color: #334155; background: #f8fafc; font-size: 12px; }
        .crm-manual-table td { color: #475569; font-size: 12px; vertical-align: top; }
        .crm-manual-back-top { display: inline-flex; gap: 6px; align-items: center; font-size: 12px; font-weight: 700; }
        .crm-manual-no-match { display: none; padding: 18px; border: 1px solid #fde68a; border-radius: 13px; color: #92400e; background: #fffbeb; }
        @media (max-width: 991.98px) {
            .crm-manual-shell { grid-template-columns: 1fr; }
            .crm-manual-sidebar { position: relative; top: auto; }
            .crm-manual-index { max-height: 360px; }
        }
        @media (max-width: 575.98px) {
            .crm-manual-hero { padding: 18px; border-radius: 15px; }
            .crm-manual-card-header { padding: 14px; }
            .crm-manual-card-body { padding: 14px; }
            .crm-manual-card-header h4 { font-size: 17px; }
        }
    </style>
@endsection

@section('page_title') {{ $page_title }} @endsection
@section('page_heading') {{ $page_heading }} @endsection

@section('content')
    <div class="crm-manual-page" id="crmManualTop">
        <div class="crm-manual-hero">
            <div class="d-flex flex-wrap justify-content-between align-items-start">
                <div class="pr-lg-4">
                    <h3 class="mb-2"><i class="feather-book-open mr-1"></i> {{ $hero_title }}</h3>
                    <p class="mb-0">{{ $hero_subtitle }}</p>
                    <div class="crm-manual-hero-note"><i class="feather-shield"></i> {{ $hero_note }}</div>
                    <div class="crm-manual-hero-stats">
                        <div class="crm-manual-stat"><strong>{{ count($sections) }}</strong><span>{{ $labels['section_count'] }}</span></div>
                        <div class="crm-manual-stat"><strong>{{ strtoupper($locale) }}</strong><span>{{ $labels['language'] }}</span></div>
                    </div>
                </div>
                <div class="mt-3 mt-lg-0">
                    <a href="{{ $switch_url }}" class="btn btn-light btn-sm"><i class="feather-globe"></i> {{ $switch_text }}</a>
                </div>
            </div>
        </div>

        <div class="card border-0 mb-3">
            <div class="card-body px-0 pt-0 pb-1">
                <h5 class="mb-3" style="font-weight:800;color:#0f172a;"><i class="feather-zap mr-1" style="color:#0f766e;"></i> {{ $labels['quick_start_title'] }}</h5>
                <div class="row">
                    @foreach ($quick_start as $item)
                        <div class="col-xl-3 col-md-6 mb-3">
                            <a class="crm-manual-quick-card" href="#{{ $item['anchor'] }}">
                                <span class="crm-manual-quick-icon"><i class="{{ $item['icon'] }}"></i></span>
                                <h6>{{ $item['title'] }}</h6>
                                <p>{{ $item['text'] }}</p>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="crm-manual-shell">
            <aside class="crm-manual-sidebar" aria-label="CRM manual index">
                <div class="crm-manual-index">
                    <div class="crm-manual-index-header">
                        <h5 class="mb-1"><i class="feather-list mr-1"></i> {{ $labels['index_title'] }}</h5>
                        <small>{{ $labels['index_help'] }}</small>
                        <input type="search" class="crm-manual-search" id="crmManualSearch" placeholder="{{ $labels['search_placeholder'] }}" autocomplete="off">
                    </div>
                    <div class="crm-manual-progress-wrap" aria-hidden="true"><div class="crm-manual-progress" id="crmManualProgress"></div></div>
                    <nav class="crm-manual-index-links" id="crmManualIndexLinks">
                        @foreach ($sections as $section)
                            <a class="crm-manual-index-link" href="#{{ $section['id'] }}" data-target="{{ $section['id'] }}">
                                <span class="crm-manual-index-number">{{ $loop->iteration }}</span>
                                <span>{{ $section['title'] }}</span>
                            </a>
                        @endforeach
                    </nav>
                </div>

                <div class="crm-manual-checklist">
                    <h6 class="mb-2"><i class="feather-check-circle mr-1"></i> {{ $labels['daily_checklist_title'] }}</h6>
                    <ul>
                        @foreach ($daily_checklist as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </div>
            </aside>

            <main>
                <div class="crm-manual-no-match mb-3" id="crmManualNoMatch"><i class="feather-alert-circle mr-1"></i> {{ $labels['no_match'] }}</div>

                @foreach ($sections as $section)
                    <section class="crm-manual-card crm-manual-section" id="{{ $section['id'] }}">
                        <div class="crm-manual-card-header">
                            <div class="crm-manual-card-number">{{ $loop->iteration }}</div>
                            <div>
                                <h4><i class="{{ $section['icon'] }} mr-1"></i> {{ $section['title'] }}</h4>
                                <span class="crm-manual-menu-path"><i class="feather-navigation"></i> {{ $labels['menu_path'] }}: {{ $section['menu'] }}</span>
                            </div>
                        </div>
                        <div class="crm-manual-card-body">
                            <div class="crm-manual-block-title"><i class="feather-info"></i> {{ $labels['why'] }}</div>
                            <div class="crm-manual-purpose">{{ $section['summary'] }}</div>

                            <div class="crm-manual-block-title"><i class="feather-check-square"></i> {{ $labels['steps'] }}</div>
                            @foreach ($section['steps'] as $step)
                                <div class="crm-manual-step">
                                    <span class="crm-manual-step-number">{{ $loop->iteration }}</span>
                                    <span>{{ $step }}</span>
                                </div>
                            @endforeach

                            <div class="row mt-3">
                                <div class="col-lg-6">
                                    <div class="crm-manual-callout crm-manual-example">
                                        <strong><i class="feather-edit-3 mr-1"></i> {{ $labels['example'] }}:</strong><br>
                                        {{ $section['example'] }}
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="crm-manual-callout crm-manual-result">
                                        <strong><i class="feather-check-circle mr-1"></i> {{ $labels['expected_result'] }}:</strong><br>
                                        {{ $section['expected_result'] }}
                                    </div>
                                </div>
                            </div>

                            @if (!empty($section['tips']))
                                <div class="crm-manual-tip-list mb-3">
                                    <strong><i class="feather-thumbs-up mr-1"></i> {{ $labels['tips'] }}:</strong>
                                    <ul class="mt-2 mb-0">
                                        @foreach ($section['tips'] as $tip)
                                            <li>{{ $tip }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if (!empty($section['warning']))
                                <div class="crm-manual-callout crm-manual-warning">
                                    <strong><i class="feather-alert-triangle mr-1"></i> {{ $labels['warning'] }}:</strong>
                                    {{ $section['warning'] }}
                                </div>
                            @endif

                            @if (!empty($section['troubleshooting']))
                                <div class="table-responsive mt-3">
                                    <table class="table table-bordered crm-manual-table mb-0">
                                        <thead>
                                            <tr>
                                                <th>{{ $labels['table_problem'] }}</th>
                                                <th>{{ $labels['table_reason'] }}</th>
                                                <th>{{ $labels['table_solution'] }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($section['troubleshooting'] as $row)
                                                <tr>
                                                    <td>{{ $row[0] }}</td>
                                                    <td>{{ $row[1] }}</td>
                                                    <td>{{ $row[2] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif

                            <div class="text-right mt-3">
                                <a href="#crmManualTop" class="crm-manual-back-top"><i class="feather-arrow-up"></i> {{ $labels['back_to_top'] }}</a>
                            </div>
                        </div>
                    </section>
                @endforeach
            </main>
        </div>
    </div>
@endsection

@section('footer_js')
    <script>
        (function () {
            'use strict';

            var search = document.getElementById('crmManualSearch');
            var noMatch = document.getElementById('crmManualNoMatch');
            var progress = document.getElementById('crmManualProgress');
            var shell = document.querySelector('.crm-manual-shell');
            var sections = Array.prototype.slice.call(document.querySelectorAll('.crm-manual-section'));
            var links = Array.prototype.slice.call(document.querySelectorAll('.crm-manual-index-link'));

            function normalize(value) {
                return String(value || '').toLowerCase().trim();
            }

            function filterManual() {
                var query = normalize(search ? search.value : '');
                var visibleCount = 0;

                sections.forEach(function (section) {
                    var isVisible = !query || normalize(section.textContent).indexOf(query) !== -1;
                    section.style.display = isVisible ? '' : 'none';
                    visibleCount += isVisible ? 1 : 0;
                });

                links.forEach(function (link) {
                    var target = document.getElementById(link.getAttribute('data-target'));
                    link.style.display = target && target.style.display !== 'none' ? '' : 'none';
                });

                if (noMatch) {
                    noMatch.style.display = visibleCount ? 'none' : 'block';
                }

                updateProgress();
            }

            function setActiveLink(id) {
                links.forEach(function (link) {
                    link.classList.toggle('is-active', link.getAttribute('data-target') === id);
                });
            }

            function updateProgress() {
                if (!progress || !shell) return;
                var height = Math.max(shell.scrollHeight - shell.clientHeight, 1);
                progress.style.width = Math.min(100, Math.max(0, (shell.scrollTop / height) * 100)) + '%';
            }

            if (search) {
                search.addEventListener('input', filterManual);
            }

            if ('IntersectionObserver' in window) {
                var observer = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting && entry.target.style.display !== 'none') {
                            setActiveLink(entry.target.id);
                        }
                    });
                }, { root: shell, rootMargin: '-20% 0px -68% 0px', threshold: 0 });

                sections.forEach(function (section) { observer.observe(section); });
            }

            if (shell) {
                shell.addEventListener('scroll', updateProgress, { passive: true });
            }
            updateProgress();
            filterManual();
        })();
    </script>
@endsection
