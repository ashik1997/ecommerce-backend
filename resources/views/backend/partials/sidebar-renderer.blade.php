<!-- Left Menu Start -->

{{-- SEARCH BOX --}}
<div class="p-3 border-bottom" style="position: sticky; top: 0; z-index: 1000;background-color: white;">
    <div style="position:relative;">
        <input type="text" id="menuSearch" class="form-control" placeholder="Search menu..."
            style="padding-left:40px;border-radius:8px;">
        <i class="feather-search" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#999;"></i>
    </div>
</div>

<ul class="metismenu list-unstyled" id="side-menu">
    @foreach ($sidebarModules as $module)
            @continue(!backend_sidebar_visible($module))

            @if (!$loop->first)
                <hr style="border-color: #c8c8c836; margin-top: 12px; margin-bottom: 12px;">
            @endif

            <li class="menu-title" style="color: green;">
                @if (!empty($module['icon']))
                    <i class="{{ $module['icon'] }}"></i>
                @endif
                {{ $module['module'] ?? '' }}
            </li>

            @foreach (($module['submodules'] ?? []) as $submodule)
                @continue(!backend_sidebar_visible($submodule))

                @php
                    $children = backend_sidebar_children($submodule);
                    $hasChildren = count($children) > 0;
                @endphp

                <li>
                    @if ($hasChildren)
                        <a href="javascript: void(0);" class="has-arrow">
                            @if (!empty($submodule['icon']))
                                <i class="{{ $submodule['icon'] }}"
                                    @if (!empty($submodule['icon_style'])) style="{{ $submodule['icon_style'] }}" @endif></i>
                            @endif
                            <span>{{ $submodule['submodule'] ?? '' }}</span>
                        </a>
                        <ul class="sub-menu" aria-expanded="false">
                            @foreach ($children as $child)
                                <li>
                                    <a href="{{ backend_sidebar_value($child['url'] ?? 'javascript: void(0);') }}"
                                        @if (!empty($child['active_paths'])) data-active-paths="{{ backend_sidebar_value($child['active_paths']) }}" @endif
                                        @if (!empty($child['onclick'])) onclick="{{ $child['onclick'] }}" @endif>
                                        @if (!empty($child['icon']))
                                            <i class="{{ $child['icon'] }}"
                                                @if (!empty($child['icon_style'])) style="{{ $child['icon_style'] }}" @endif></i>
                                        @endif
                                        <span>{{ $child['childmodule'] ?? '' }}</span>
                                        @if (!empty($child['badge']))
                                            <span class="menu_badge_count"
                                                @if (!empty($child['badge_title'])) title="{{ $child['badge_title'] }}" @endif
                                                @if (!empty($child['badge_style'])) style="{{ $child['badge_style'] }}" @endif>
                                                {{ backend_sidebar_value($child['badge']) }}
                                            </span>
                                        @endif
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <a href="{{ backend_sidebar_value($submodule['url'] ?? 'javascript: void(0);') }}"
                            @if (!empty($submodule['active_paths'])) data-active-paths="{{ backend_sidebar_value($submodule['active_paths']) }}" @endif
                            @if (!empty($submodule['onclick'])) onclick="{{ $submodule['onclick'] }}" @endif>
                            @if (!empty($submodule['icon']))
                                <i class="{{ $submodule['icon'] }}"
                                    @if (!empty($submodule['icon_style'])) style="{{ $submodule['icon_style'] }}" @endif></i>
                            @endif
                            <span>{{ $submodule['submodule'] ?? '' }}</span>
                            @if (!empty($submodule['badge']))
                                <span class="menu_badge_count"
                                    @if (!empty($submodule['badge_title'])) title="{{ $submodule['badge_title'] }}" @endif
                                    @if (!empty($submodule['badge_style'])) style="{{ $submodule['badge_style'] }}" @endif>
                                    {{ backend_sidebar_value($submodule['badge']) }}
                                </span>
                            @endif
                        </a>
                    @endif
                </li>
            @endforeach
    @endforeach
</ul>

<script>
    function getMenuSection(item) {
        let prev = item.previousElementSibling;
        while (prev) {
            if (prev.classList?.contains('menu-title')) {
                return prev.textContent.trim();
            }
            prev = prev.previousElementSibling;
        }
        return '';
    }

    function getNextMenuTitle(children, index) {
        for (let j = index + 1; j < children.length; j++) {
            const next = children[j];
            if (next.classList?.contains('menu-title')) {
                return next;
            }
            if (next.tagName === 'LI') {
                const section = getMenuSection(next);
                if (section) return children.find(el => el.classList?.contains('menu-title') && el.textContent
                    .trim() === section);
            }
        }
        return null;
    }

    document.addEventListener("DOMContentLoaded", function() {
        const searchBox = document.getElementById("menuSearch");
        const menu = document.getElementById("side-menu");

        searchBox.addEventListener("keyup", function() {
            const keyword = this.value.toLowerCase().trim();
            const items = menu.querySelectorAll("li, hr");

            items.forEach(item => {
                if (item.tagName === "HR") {
                    item.style.display = "";
                    return;
                }

                const isTitle = item.classList.contains("menu-title");
                const subMenu = item.querySelector(":scope > ul.sub-menu");
                const text = item.innerText.toLowerCase();

                let matched = text.includes(keyword);
                let childMatched = false;

                if (subMenu) {
                    const subItems = subMenu.querySelectorAll("li");

                    subItems.forEach(sub => {
                        const subText = sub.innerText.toLowerCase();
                        const ok = subText.includes(keyword);

                        sub.style.display = (keyword === "" || ok) ? "" : "none";

                        if (ok) childMatched = true;
                    });

                    if (keyword !== "" && (matched || childMatched)) {
                        subMenu.style.display = "block";
                        item.classList.add("mm-active");
                    } else {
                        subMenu.style.display = "";
                        item.classList.remove("mm-active");
                    }
                }

                if (keyword === "") {
                    item.style.display = "";
                } else {
                    item.style.display = (matched || childMatched || isTitle) ? "" : "none";
                }
            });

            const titles = menu.querySelectorAll(".menu-title");

            titles.forEach(title => {
                let next = title.nextElementSibling;
                let hasVisibleItem = false;

                while (next && !next.classList.contains("menu-title")) {
                    if (
                        next.tagName === "LI" &&
                        !next.classList.contains("menu-title") &&
                        next.style.display !== "none"
                    ) {
                        hasVisibleItem = true;
                        break;
                    }
                    next = next.nextElementSibling;
                }

                title.style.display = hasVisibleItem ? "" : "none";

                let prev = title.previousElementSibling;
                if (prev && prev.tagName === "HR") {
                    prev.style.display = hasVisibleItem ? "" : "none";
                }
            });
        });
    });
</script>
