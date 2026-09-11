@extends('backend.master')

@section('header_css')
    <style>
        .rbp-page { --rbp-teal:#0f766e; --rbp-teal-2:#14b8a6; --rbp-soft:#ecfdf5; --rbp-border:#d7eeee; --rbp-dark:#0f172a; --rbp-muted:#64748b; }
        .rbp-hero { border:1px solid var(--rbp-border); border-radius:16px; background:linear-gradient(135deg,#ffffff,#f0fdfa); box-shadow:0 10px 30px rgba(15,118,110,.08); }
        .rbp-title { color:var(--rbp-teal); font-weight:800; }
        .rbp-search { border-radius:12px; border-color:var(--rbp-border); height:42px; }
        .rbp-layout { display:grid; grid-template-columns:300px minmax(0,1fr); gap:16px; align-items:start; }
        .rbp-module-sidebar { position:sticky; top:88px; border:1px solid var(--rbp-border); border-radius:16px; background:#fff; box-shadow:0 8px 24px rgba(15,118,110,.06); overflow:hidden; }
        .rbp-module-list { max-height:calc(100vh - 220px); overflow:auto; padding:8px; }
        .rbp-module-link { width:100%; border:0; border-radius:12px; padding:11px 12px; background:#fff; text-align:left; display:flex; align-items:center; justify-content:space-between; gap:10px; color:#1f2937; transition:.18s ease; }
        .rbp-module-link:hover { background:#f8fffe; color:var(--rbp-teal); }
        .rbp-module-link.active { background:linear-gradient(135deg,var(--rbp-teal),var(--rbp-teal-2)); color:#fff; box-shadow:0 8px 18px rgba(15,118,110,.22); }
        .rbp-module-link .rbp-module-name { font-weight:700; display:block; line-height:1.2; }
        .rbp-module-link .rbp-module-meta { font-size:11px; opacity:.8; display:block; margin-top:2px; }
        .rbp-count-badge { min-width:34px; border-radius:999px; padding:4px 8px; background:#e2f7f4; color:var(--rbp-teal); text-align:center; font-weight:800; font-size:12px; }
        .rbp-module-link.active .rbp-count-badge { background:rgba(255,255,255,.22); color:#fff; }
        .rbp-content-card { border:1px solid var(--rbp-border); border-radius:16px; background:#fff; box-shadow:0 8px 24px rgba(15,118,110,.06); overflow:hidden; }
        .rbp-content-head { background:linear-gradient(135deg,#0f766e,#14b8a6); color:#fff; padding:16px 18px; }
        .rbp-module-panel { display:none; }
        .rbp-module-panel.active { display:block; }
        .rbp-toolbar-btn { border-radius:999px; padding:6px 12px; font-weight:700; }
        .rbp-stat { border:1px solid #e6f7f4; border-radius:14px; padding:10px 12px; background:#fff; }
        .rbp-stat strong { color:var(--rbp-teal); font-size:18px; display:block; line-height:1; }
        .rbp-stat span { color:var(--rbp-muted); font-size:12px; }
        .rbp-group { border:1px solid #e8f4f2; border-radius:14px; margin:14px 16px; overflow:hidden; }
        .rbp-group-head { background:#f8fffe; padding:12px 14px; display:flex; align-items:center; justify-content:space-between; gap:12px; border-bottom:1px solid #e8f4f2; cursor:pointer; }
        .rbp-group-title { font-weight:800; color:#184e49; }
        .rbp-group-body { padding:0; }
        .rbp-item { display:grid; grid-template-columns:minmax(220px,1fr) 420px; gap:12px; align-items:center; padding:13px 14px; border-bottom:1px solid #f1f7f6; }
        .rbp-item:last-child { border-bottom:0; }
        .rbp-item:hover { background:#fbffff; }
        .rbp-item-title strong { color:#111827; }
        .rbp-item-title small { display:block; color:var(--rbp-muted); margin-top:3px; word-break:break-all; }
        .rbp-actions { display:flex; justify-content:flex-end; gap:8px; flex-wrap:wrap; }
        .rbp-action-wrap { display:inline-flex; }
        .rbp-action-pill { margin:0; border:1px solid #d9e9e7; border-radius:999px; padding:7px 11px; min-width:98px; text-align:center; cursor:pointer; background:#fff; font-weight:800; font-size:12px; color:#475569; transition:.15s ease; user-select:none; display:inline-flex; align-items:center; justify-content:center; gap:7px; }
        .rbp-action-pill:hover { transform:translateY(-1px); box-shadow:0 5px 14px rgba(15,118,110,.10); }
        .rbp-action-pill .rbp-check { position:static; opacity:1; pointer-events:auto; width:17px; height:17px; margin:0; cursor:pointer; flex:0 0 auto; appearance:auto; -webkit-appearance:auto; }
        .rbp-action-pill[data-action="read"] .rbp-check { accent-color:#0284c7; }
        .rbp-action-pill[data-action="create"] .rbp-check { accent-color:#16a34a; }
        .rbp-action-pill[data-action="update"] .rbp-check { accent-color:#ea580c; }
        .rbp-action-pill[data-action="delete"] .rbp-check { accent-color:#dc2626; }
        .rbp-action-pill.is-checked[data-action="read"] { background:#e0f2fe; border-color:#38bdf8; color:#0369a1; }
        .rbp-action-pill.is-checked[data-action="create"] { background:#dcfce7; border-color:#22c55e; color:#166534; }
        .rbp-action-pill.is-checked[data-action="update"] { background:#ffedd5; border-color:#fb923c; color:#9a3412; }
        .rbp-action-pill.is-checked[data-action="delete"] { background:#fee2e2; border-color:#ef4444; color:#991b1b; }
        .rbp-help { color:var(--rbp-muted); font-size:12px; }
        .rbp-empty { display:none; border:1px dashed #bfe7e2; border-radius:16px; padding:24px; color:var(--rbp-muted); text-align:center; background:#fbffff; }
        .rbp-savebar { position:sticky; bottom:12px; z-index:30; margin:18px 0 0; border:1px solid rgba(15,118,110,.22); border-radius:16px; background:#ffffff; box-shadow:0 14px 34px rgba(15,118,110,.18); padding:12px 14px; display:none; }
        .rbp-savebar.show { display:flex; align-items:center; justify-content:space-between; gap:12px; }
        .rbp-dirty-dot { width:10px; height:10px; background:#f59e0b; border-radius:50%; display:inline-block; margin-right:7px; }
        .rbp-role-badge { border-radius:999px; background:#e6fffb; color:#0f766e; padding:6px 11px; font-weight:800; display:inline-block; }
        .rbp-module-hidden-by-search { display:none !important; }
        @media(max-width:1199px) { .rbp-layout { grid-template-columns:260px minmax(0,1fr); } .rbp-item { grid-template-columns:1fr; } .rbp-actions { justify-content:flex-start; } }
        @media(max-width:767px) { .rbp-layout { grid-template-columns:1fr; } .rbp-module-sidebar { position:relative; top:auto; } .rbp-module-list { max-height:260px; } .rbp-savebar.show { display:block; } .rbp-savebar .text-right { text-align:left !important; margin-top:10px; } }
    </style>
@endsection

@section('page_title')
    Role Sidebar Permission
@endsection

@section('page_heading')
    Role Sidebar Permission
@endsection

@section('content')
    @php
        $firstModuleKey = null;
        $totalMenus = 0;
        foreach ($permissionTree as $moduleIndex => $module) {
            if ($firstModuleKey === null) {
                $firstModuleKey = 'module_' . $moduleIndex;
            }
            foreach (($module['groups'] ?? []) as $group) {
                $totalMenus += count($group['items'] ?? []);
            }
        }
    @endphp

    <div class="rbp-page">
        <div class="card rbp-hero mb-3">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-lg-5 mb-3 mb-lg-0">
                        <span class="rbp-role-badge mb-2">Role: {{ $role->name }}</span>
                        <h4 class="rbp-title mb-1">Sidebar Permission Manager</h4>
                        <div class="rbp-help">Left side থেকে module select করুন, right side থেকে quick action অথবা specific CRUD permission দিন। Read permission sidebar visibility control করে।</div>
                    </div>
                    <div class="col-lg-4 mb-3 mb-lg-0">
                        <input type="text" id="permissionSearch" class="form-control rbp-search" placeholder="Search module, menu, permission key...">
                        <div class="rbp-help mt-1">Search করলে matching module/menu auto filter হবে।</div>
                    </div>
                    <div class="col-lg-3 text-lg-right">
                        <button type="button" class="btn btn-sm btn-outline-success rbp-toolbar-btn mb-1" id="globalViewOnlyBtn">View Only All</button>
                        <button type="button" class="btn btn-sm btn-outline-primary rbp-toolbar-btn mb-1" id="globalFullBtn">Full All</button>
                        <button type="button" class="btn btn-sm btn-outline-danger rbp-toolbar-btn mb-1" id="globalClearBtn">Clear All</button>
                    </div>
                </div>
                <div class="row mt-3" id="permissionSummaryCards">
                    <div class="col-6 col-md-3 mb-2"><div class="rbp-stat"><strong id="sumReadable">0</strong><span>Visible Menus</span></div></div>
                    <div class="col-6 col-md-3 mb-2"><div class="rbp-stat"><strong id="sumFull">0</strong><span>Full Access</span></div></div>
                    <div class="col-6 col-md-3 mb-2"><div class="rbp-stat"><strong id="sumViewOnly">0</strong><span>View Only</span></div></div>
                    <div class="col-6 col-md-3 mb-2"><div class="rbp-stat"><strong id="sumRestricted">0</strong><span>Restricted / Hidden</span></div></div>
                </div>
            </div>
        </div>

        <div class="rbp-layout">
            <aside class="rbp-module-sidebar">
                <div class="p-3 border-bottom">
                    <strong class="text-muted">Modules</strong>
                    <span class="float-right badge badge-info">{{ count($permissionTree) }}</span>
                </div>
                <div class="rbp-module-list" id="moduleList">
                    @foreach($permissionTree as $moduleIndex => $module)
                        @php
                            $moduleMenuCount = 0;
                            foreach (($module['groups'] ?? []) as $group) {
                                $moduleMenuCount += count($group['items'] ?? []);
                            }
                            $moduleDomKey = 'module_' . $moduleIndex;
                        @endphp
                        <button type="button"
                                class="rbp-module-link {{ $loop->first ? 'active' : '' }}"
                                data-module-target="{{ $moduleDomKey }}"
                                data-search-text="{{ strtolower(($module['title'] ?? '') . ' ' . ($module['key'] ?? '')) }}">
                            <span>
                                <span class="rbp-module-name">
                                    @if(!empty($module['icon'])) <i class="{{ $module['icon'] }} mr-1"></i> @endif
                                    {{ $module['title'] }}
                                </span>
                                <span class="rbp-module-meta"><span class="js-module-readable">0</span>/{{ $moduleMenuCount }} visible</span>
                            </span>
                            <span class="rbp-count-badge">{{ $moduleMenuCount }}</span>
                        </button>
                    @endforeach
                </div>
            </aside>

            <main>
                <div id="emptySearchState" class="rbp-empty mb-3">
                    <h5 class="mb-1">No matching permission found</h5>
                    <div>Search keyword change করুন অথবা clear করুন।</div>
                </div>

                @foreach($permissionTree as $moduleIndex => $module)
                    @php
                        $moduleDomKey = 'module_' . $moduleIndex;
                        $moduleMenuCount = 0;
                        foreach (($module['groups'] ?? []) as $group) {
                            $moduleMenuCount += count($group['items'] ?? []);
                        }
                    @endphp
                    <section class="rbp-content-card rbp-module-panel {{ $loop->first ? 'active' : '' }}"
                             id="{{ $moduleDomKey }}"
                             data-module-key="{{ $module['key'] ?? $moduleDomKey }}"
                             data-search-text="{{ strtolower(($module['title'] ?? '') . ' ' . ($module['key'] ?? '')) }}">
                        <div class="rbp-content-head">
                            <div class="row align-items-center">
                                <div class="col-lg-5 mb-2 mb-lg-0">
                                    <h5 class="mb-1">
                                        @if(!empty($module['icon'])) <i class="{{ $module['icon'] }} mr-1"></i> @endif
                                        {{ $module['title'] }}
                                    </h5>
                                    <small>{{ count($module['groups']) }} groups · {{ $moduleMenuCount }} menus</small>
                                </div>
                                <div class="col-lg-7 text-lg-right">
                                    <button type="button" class="btn btn-sm btn-light rbp-toolbar-btn js-module-view">View Only</button>
                                    <button type="button" class="btn btn-sm btn-warning rbp-toolbar-btn js-module-full">Full Access</button>
                                    <button type="button" class="btn btn-sm btn-outline-light rbp-toolbar-btn js-module-clear">Clear Module</button>
                                    <button type="button" class="btn btn-sm btn-outline-light rbp-toolbar-btn js-expand-module">Expand</button>
                                    <button type="button" class="btn btn-sm btn-outline-light rbp-toolbar-btn js-collapse-module">Collapse</button>
                                </div>
                            </div>
                        </div>

                        @foreach($module['groups'] as $groupIndex => $group)
                            <div class="rbp-group" data-search-text="{{ strtolower(($module['title'] ?? '') . ' ' . ($group['title'] ?? '') . ' ' . ($group['key'] ?? '')) }}">
                                <div class="rbp-group-head">
                                    <div>
                                        <span class="rbp-group-title">
                                            @if(!empty($group['icon'])) <i class="{{ $group['icon'] }} mr-1"></i> @endif
                                            {{ $group['title'] }}
                                        </span>
                                        <span class="badge badge-info ml-2">{{ count($group['items']) }} menus</span>
                                    </div>
                                    <div class="rbp-group-actions">
                                        <button type="button" class="btn btn-xs btn-outline-info js-group-view">View</button>
                                        <button type="button" class="btn btn-xs btn-outline-success js-group-full">Full</button>
                                        <button type="button" class="btn btn-xs btn-outline-danger js-group-clear">Clear</button>
                                    </div>
                                </div>
                                <div class="rbp-group-body">
                                    @foreach($group['items'] as $item)
                                        @php $saved = $savedPermissions[$item['key']] ?? []; @endphp
                                        <div class="rbp-item permission-item"
                                             data-key="{{ $item['key'] }}"
                                             data-search-text="{{ strtolower(($module['title'] ?? '') . ' ' . ($group['title'] ?? '') . ' ' . ($item['title'] ?? '') . ' ' . ($item['key'] ?? '')) }}">
                                            <div class="rbp-item-title">
                                                <strong>{{ $item['title'] }}</strong>
                                                <small>{{ $item['key'] }}</small>
                                            </div>
                                            <div class="rbp-actions">
                                                @foreach(['read' => 'Read', 'create' => 'Create', 'update' => 'Update', 'delete' => 'Delete'] as $action => $label)
                                                    @php $inputId = 'perm_' . md5($item['key'] . '_' . $action); @endphp
                                                    <span class="rbp-action-wrap">
                                                        <label for="{{ $inputId }}" class="rbp-action-pill" data-action="{{ $action }}">
                                                            <input type="checkbox"
                                                                   id="{{ $inputId }}"
                                                                   class="rbp-check perm-check"
                                                                   data-key="{{ $item['key'] }}"
                                                                   data-action="{{ $action }}"
                                                                   @if(!empty($saved[$action])) checked @endif>
                                                            <span>{{ $label }}</span>
                                                        </label>
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </section>
                @endforeach

                <div class="rbp-savebar" id="saveBar">
                    <div>
                        <strong><span class="rbp-dirty-dot"></span>Unsaved changes</strong>
                        <div class="rbp-help">Permission update করলে DB sidebar copy এবং cache দুটোই regenerate হবে।</div>
                    </div>
                    <div class="text-right">
                        <button type="button" class="btn btn-outline-secondary rbp-toolbar-btn" id="resetChangesBtn">Reset</button>
                        <button type="button" class="btn btn-success rbp-toolbar-btn" id="savePermissionBtn"><i class="feather-save"></i> Save Permissions</button>
                    </div>
                </div>
            </main>
        </div>
    </div>
@endsection

@section('footer_js')
    <script>
        (function () {
            const saveUrl = "{{ url('/role-sidebar-permissions/' . $role->id . '/save') }}";
            const initialPermissions = collectPermissions();
            let isDirty = false;

            function swalAvailable() { return typeof window.Swal !== 'undefined'; }

            function notify(title, text, icon) {
                if (swalAvailable()) {
                    Swal.fire({
                        title: title,
                        text: text || '',
                        icon: icon || 'info',
                        timer: icon === 'success' ? 1800 : undefined,
                        confirmButtonColor: '#0f766e'
                    });
                } else {
                    alert(title + (text ? '\n' + text : ''));
                }
            }

            function httpClient() {
                return window.AppAxios || window.axios;
            }

            function collectPermissions() {
                const permissions = {};
                document.querySelectorAll('.perm-check').forEach(function (box) {
                    const key = box.dataset.key;
                    const action = box.dataset.action;
                    if (!permissions[key]) {
                        permissions[key] = { create: false, read: false, update: false, delete: false };
                    }
                    permissions[key][action] = box.checked;
                });
                return permissions;
            }

            function permissionsEqual(a, b) {
                return JSON.stringify(a) === JSON.stringify(b);
            }

            function setDirty(value) {
                isDirty = value;
                const saveBar = document.getElementById('saveBar');
                if (saveBar) saveBar.classList.toggle('show', isDirty);
            }

            function markDirty() {
                setDirty(!permissionsEqual(collectPermissions(), initialPermissions));
            }

            function setScope(scope, mode) {
                const checks = scope.querySelectorAll('.perm-check');
                checks.forEach(function (box) {
                    if (mode === 'full') box.checked = true;
                    if (mode === 'clear') box.checked = false;
                    if (mode === 'view') box.checked = box.dataset.action === 'read';
                });
                updateSummary();
                markDirty();
            }

            function syncPillStates() {
                document.querySelectorAll('.perm-check').forEach(function (box) {
                    const pill = box.closest('.rbp-action-pill');
                    if (pill) pill.classList.toggle('is-checked', !!box.checked);
                });
            }

            function updateSummary() {
                syncPillStates();
                let readable = 0, full = 0, viewOnly = 0, restricted = 0;

                document.querySelectorAll('.permission-item').forEach(function (item) {
                    const read = item.querySelector('.perm-check[data-action="read"]')?.checked || false;
                    const create = item.querySelector('.perm-check[data-action="create"]')?.checked || false;
                    const update = item.querySelector('.perm-check[data-action="update"]')?.checked || false;
                    const del = item.querySelector('.perm-check[data-action="delete"]')?.checked || false;

                    if (read) readable++;
                    if (read && create && update && del) full++;
                    else if (read && !create && !update && !del) viewOnly++;
                    else restricted++;
                });

                document.getElementById('sumReadable').textContent = readable;
                document.getElementById('sumFull').textContent = full;
                document.getElementById('sumViewOnly').textContent = viewOnly;
                document.getElementById('sumRestricted').textContent = restricted;

                document.querySelectorAll('.rbp-module-panel').forEach(function (panel) {
                    const visible = panel.querySelectorAll('.perm-check[data-action="read"]:checked').length;
                    const link = document.querySelector('.rbp-module-link[data-module-target="' + panel.id + '"]');
                    if (link) {
                        const target = link.querySelector('.js-module-readable');
                        if (target) target.textContent = visible;
                    }
                });
            }

            function activateModule(targetId) {
                document.querySelectorAll('.rbp-module-link').forEach(btn => btn.classList.remove('active'));
                document.querySelectorAll('.rbp-module-panel').forEach(panel => panel.classList.remove('active'));

                const link = document.querySelector('.rbp-module-link[data-module-target="' + targetId + '"]');
                const panel = document.getElementById(targetId);
                if (link) link.classList.add('active');
                if (panel) panel.classList.add('active');
            }

            document.getElementById('moduleList')?.addEventListener('click', function (event) {
                const btn = event.target.closest('.rbp-module-link');
                if (!btn) return;
                activateModule(btn.dataset.moduleTarget);
            });

            document.addEventListener('change', function (event) {
                if (event.target.classList.contains('perm-check')) {
                    updateSummary();
                    markDirty();
                }
            });

            document.addEventListener('click', function (event) {
                const groupHead = event.target.closest('.rbp-group-head');
                if (groupHead && !event.target.closest('button')) {
                    const body = groupHead.parentElement.querySelector('.rbp-group-body');
                    if (body) body.style.display = body.style.display === 'none' ? '' : 'none';
                    return;
                }

                const panel = event.target.closest('.rbp-module-panel');
                const group = event.target.closest('.rbp-group');

                if (event.target.closest('.js-module-view') && panel) { event.preventDefault(); setScope(panel, 'view'); return; }
                if (event.target.closest('.js-module-full') && panel) { event.preventDefault(); setScope(panel, 'full'); return; }
                if (event.target.closest('.js-module-clear') && panel) { event.preventDefault(); setScope(panel, 'clear'); return; }
                if (event.target.closest('.js-expand-module') && panel) { event.preventDefault(); panel.querySelectorAll('.rbp-group-body').forEach(el => el.style.display = ''); return; }
                if (event.target.closest('.js-collapse-module') && panel) { event.preventDefault(); panel.querySelectorAll('.rbp-group-body').forEach(el => el.style.display = 'none'); return; }

                if (event.target.closest('.js-group-view') && group) { event.preventDefault(); setScope(group, 'view'); return; }
                if (event.target.closest('.js-group-full') && group) { event.preventDefault(); setScope(group, 'full'); return; }
                if (event.target.closest('.js-group-clear') && group) { event.preventDefault(); setScope(group, 'clear'); return; }
            });

            document.getElementById('globalViewOnlyBtn')?.addEventListener('click', function () { setScope(document, 'view'); });
            document.getElementById('globalFullBtn')?.addEventListener('click', function () { setScope(document, 'full'); });
            document.getElementById('globalClearBtn')?.addEventListener('click', function () {
                const run = function () { setScope(document, 'clear'); };
                if (swalAvailable()) {
                    Swal.fire({
                        title: 'Clear all permissions?',
                        text: 'This will uncheck every permission in the UI. You still need to save to apply it.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, clear',
                        confirmButtonColor: '#dc2626'
                    }).then(res => { if (res.isConfirmed) run(); });
                } else if (confirm('Clear all permissions?')) run();
            });

            document.getElementById('resetChangesBtn')?.addEventListener('click', function () {
                Object.keys(initialPermissions).forEach(function (key) {
                    ['read','create','update','delete'].forEach(function (action) {
                        const box = document.querySelector('.perm-check[data-key="' + CSS.escape(key) + '"][data-action="' + action + '"]');
                        if (box) box.checked = !!initialPermissions[key][action];
                    });
                });
                updateSummary();
                setDirty(false);
            });

            document.getElementById('permissionSearch')?.addEventListener('keyup', function () {
                const q = this.value.toLowerCase().trim();
                let firstVisibleModule = null;
                let visibleModuleCount = 0;

                document.querySelectorAll('.rbp-module-panel').forEach(function (panel) {
                    let moduleMatched = (panel.dataset.searchText || '').includes(q);
                    let panelHasVisible = false;

                    panel.querySelectorAll('.rbp-group').forEach(function (group) {
                        let groupMatched = (group.dataset.searchText || '').includes(q);
                        let itemVisible = false;

                        group.querySelectorAll('.permission-item').forEach(function (item) {
                            const matched = q === '' || moduleMatched || groupMatched || (item.dataset.searchText || '').includes(q);
                            item.style.display = matched ? '' : 'none';
                            if (matched) itemVisible = true;
                        });

                        const showGroup = q === '' || moduleMatched || groupMatched || itemVisible;
                        group.style.display = showGroup ? '' : 'none';
                        if (showGroup) panelHasVisible = true;
                    });

                    const showPanel = q === '' || moduleMatched || panelHasVisible;
                    const link = document.querySelector('.rbp-module-link[data-module-target="' + panel.id + '"]');
                    if (link) link.classList.toggle('rbp-module-hidden-by-search', !showPanel);

                    if (showPanel) {
                        visibleModuleCount++;
                        if (!firstVisibleModule) firstVisibleModule = panel.id;
                    }
                });

                document.getElementById('emptySearchState').style.display = visibleModuleCount === 0 ? 'block' : 'none';
                if (firstVisibleModule && !document.querySelector('.rbp-module-link.active:not(.rbp-module-hidden-by-search)')) {
                    activateModule(firstVisibleModule);
                }
                if (q !== '') {
                    document.querySelectorAll('.rbp-module-panel.active .rbp-group-body').forEach(el => el.style.display = '');
                }
            });

            document.getElementById('savePermissionBtn')?.addEventListener('click', function () {
                const client = httpClient();
                if (!client) {
                    notify('Axios not found', 'Axios/AppAxios is required to save permissions.', 'error');
                    return;
                }

                const permissions = collectPermissions();
                const readCount = document.querySelectorAll('.perm-check[data-action="read"]:checked').length;

                const proceed = swalAvailable()
                    ? Swal.fire({
                        title: 'Save role permissions?',
                        html: 'Visible menu count: <b>' + readCount + '</b><br>DB sidebar copy and file cache will be regenerated.',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, save',
                        confirmButtonColor: '#0f766e'
                    })
                    : Promise.resolve({ isConfirmed: confirm('Save role permissions?') });

                proceed.then(function (result) {
                    if (!result.isConfirmed) return;

                    if (swalAvailable()) {
                        Swal.fire({
                            title: 'Saving...',
                            text: 'Please wait while permissions are being updated.',
                            allowOutsideClick: false,
                            didOpen: () => Swal.showLoading()
                        });
                    }

                    client.post(saveUrl, { permissions: permissions })
                        .then(function (response) {
                            Object.keys(initialPermissions).forEach(key => delete initialPermissions[key]);
                            Object.assign(initialPermissions, collectPermissions());
                            setDirty(false);
                            updateSummary();
                            notify('Saved!', response.data.message || 'Permissions saved successfully.', 'success');
                        })
                        .catch(function (error) {
                            let message = 'Something went wrong while saving permissions.';
                            if (error.response && error.response.data) {
                                message = error.response.data.message || message;
                                if (error.response.data.errors) {
                                    const firstKey = Object.keys(error.response.data.errors)[0];
                                    if (firstKey) message = error.response.data.errors[firstKey][0] || message;
                                }
                            }
                            notify('Save failed', message, 'error');
                        });
                });
            });

            window.addEventListener('beforeunload', function (event) {
                if (!isDirty) return;
                event.preventDefault();
                event.returnValue = '';
            });

            updateSummary();
            setDirty(false);
        })();
    </script>
@endsection
