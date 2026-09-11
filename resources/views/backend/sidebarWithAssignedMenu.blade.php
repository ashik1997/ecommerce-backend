@php
    $sidebarModules = function_exists('assigned_sidebar_modules')
        ? assigned_sidebar_modules()
        : [];
@endphp

@include('backend.partials.sidebar-renderer', ['sidebarModules' => $sidebarModules])
