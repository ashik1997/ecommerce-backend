@php
    $sidebarModules = config('backend_sidebar.modules', []);
@endphp

@include('backend.partials.sidebar-renderer', ['sidebarModules' => $sidebarModules])
