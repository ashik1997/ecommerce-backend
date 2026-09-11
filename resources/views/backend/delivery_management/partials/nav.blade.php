<div class="card mb-3">
    <div class="card-body py-2">
        <div class="d-flex flex-wrap align-items-center">
            <a class="btn btn-sm {{ request()->routeIs('delivery-management.dashboard') ? 'btn-primary' : 'btn-outline-primary' }} mr-2 mb-1" href="{{ route('delivery-management.dashboard') }}">
                <i class="feather-grid"></i> Dashboard
            </a>
            <a class="btn btn-sm {{ request()->routeIs('delivery-management.providers.*') ? 'btn-primary' : 'btn-outline-primary' }} mr-2 mb-1" href="{{ route('delivery-management.providers.index') }}">
                <i class="feather-navigation"></i> Providers
            </a>
            <a class="btn btn-sm {{ request()->routeIs('delivery-management.employees.*') ? 'btn-primary' : 'btn-outline-primary' }} mr-2 mb-1" href="{{ route('delivery-management.employees.index') }}">
                <i class="feather-users"></i> Employees
            </a>
            <a class="btn btn-sm {{ request()->routeIs('delivery-management.zones.*') ? 'btn-primary' : 'btn-outline-primary' }} mr-2 mb-1" href="{{ route('delivery-management.zones.index') }}">
                <i class="feather-map"></i> Zones
            </a>
            <a class="btn btn-sm {{ request()->routeIs('delivery-management.rate-cards.*') ? 'btn-primary' : 'btn-outline-primary' }} mr-2 mb-1" href="{{ route('delivery-management.rate-cards.index') }}">
                <i class="feather-credit-card"></i> Rate Cards
            </a>
            <a class="btn btn-sm {{ request()->routeIs('delivery-management.shipments.*') ? 'btn-primary' : 'btn-outline-primary' }} mr-2 mb-1" href="{{ route('delivery-management.shipments.index') }}">
                <i class="feather-package"></i> Shipments
            </a>
            <a class="btn btn-sm {{ request()->routeIs('delivery-management.cod-collections.*') ? 'btn-primary' : 'btn-outline-primary' }} mr-2 mb-1" href="{{ route('delivery-management.cod-collections.index') }}">
                <i class="feather-dollar-sign"></i> COD
            </a>
            <a class="btn btn-sm {{ request()->routeIs('delivery-management.settlements.*') ? 'btn-primary' : 'btn-outline-primary' }} mr-2 mb-1" href="{{ route('delivery-management.settlements.index') }}">
                <i class="feather-check-square"></i> Settlements
            </a>
            <a class="btn btn-sm {{ request()->routeIs('delivery-management.courier-config.*') ? 'btn-primary' : 'btn-outline-primary' }} mr-2 mb-1" href="{{ route('delivery-management.courier-config.index') }}">
                <i class="feather-settings"></i> Courier Config
            </a>
            <a class="btn btn-sm {{ request()->routeIs('delivery-management.courier-settlements.*') ? 'btn-primary' : 'btn-outline-primary' }} mr-2 mb-1" href="{{ route('delivery-management.courier-settlements.index') }}">
                <i class="feather-dollar-sign"></i> API Courier Settlement
            </a>
            <a class="btn btn-sm {{ request()->routeIs('delivery-management.reports.*') ? 'btn-primary' : 'btn-outline-primary' }} mr-2 mb-1" href="{{ route('delivery-management.reports.index') }}">
                <i class="feather-bar-chart-2"></i> Reports
            </a>
            <a class="btn btn-sm {{ request()->routeIs('delivery-management.user-manual.*') ? 'btn-primary' : 'btn-outline-primary' }} mr-2 mb-1" href="{{ route('delivery-management.user-manual.index') }}">
                <i class="feather-book-open"></i> User Manual
            </a>
        </div>
    </div>
</div>
