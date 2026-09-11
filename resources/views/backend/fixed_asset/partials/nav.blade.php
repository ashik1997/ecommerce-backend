@php
    $faNavItems = [
        ['route' => 'fixed-assets.dashboard', 'label' => 'Dashboard'],
        ['route' => 'fixed-assets.assets.index', 'label' => 'Assets'],
        ['route' => 'fixed-assets.assignments.index', 'label' => 'Assignments'],
        ['route' => 'fixed-assets.transfers.index', 'label' => 'Transfers'],
        ['route' => 'fixed-assets.maintenance.index', 'label' => 'Maintenance'],
        ['route' => 'fixed-assets.depreciation.index', 'label' => 'Depreciation'],
        ['route' => 'fixed-assets.disposals.index', 'label' => 'Disposals'],
        ['route' => 'fixed-assets.verification.index', 'label' => 'Verification'],
        ['route' => 'fixed-assets.reports.index', 'label' => 'Reports'],
        ['route' => 'fixed-assets.settings.index', 'label' => 'Settings'],
    ];
@endphp
<div class="fa-module-nav mb-3">
    @foreach($faNavItems as $item)
        @php
            $routeName = $item['route'];
            $prefix = str_replace('.index', '', $routeName);
            $active = request()->routeIs($routeName) || request()->routeIs($prefix.'.*');
        @endphp
        @if(Route::has($routeName))
            <a href="{{ route($routeName) }}" class="{{ $active ? 'active' : '' }}">{{ $item['label'] }}</a>
        @endif
    @endforeach
</div>
