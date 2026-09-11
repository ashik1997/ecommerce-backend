<!DOCTYPE html>
<html lang="en">

@php
    $generalInfo = DB::table('general_infos')
        ->where('id', 1)
        ->select('logo', 'company_name', 'fav_icon', 'guest_checkout', 'website')
        ->first();
    $fallbackLogoUrl = asset('uploads/settings/nexgenit.jpg');
@endphp

<head>
    <meta charset="utf-8" />
    <title>Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#4f46e5">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">
    <link rel="manifest" href="{{ url('manifest.json') }}">

    @if ($generalInfo->fav_icon != '' && $generalInfo->fav_icon != null && file_exists(public_path($generalInfo->fav_icon)))
        <link rel="shortcut icon" href="{{ versioned_url($generalInfo->fav_icon) }}">
    @else
        <link rel="shortcut icon" href="{{ asset('assets/images/favicon.ico') }}">
    @endif

    <!-- App css -->
    <link href="{{ versioned_url('assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ versioned_url('assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ versioned_url('assets/css/theme.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ versioned_url('assets/css/toastr.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ versioned_url('assets/css/media-manager.css') }}" rel="stylesheet" type="text/css" />
    <script src="{{ versioned_asset('assets/plugins/axios/axios.js') }}"></script>
    <script src="{{ versioned_asset('assets/js/app-axios.js') }}"></script>

    @yield('header_css')
    @yield('header_js')

    @stack('header_css')
    @stack('css')

    <link href="{{ versioned_url('assets/css/custom.css') }}&v2={{ time() }}" rel="stylesheet"
        type="text/css" />

</head>

<body>
    <!-- Begin page -->
    <div id="layout-wrapper">

        <!-- ========== Left Sidebar Start ========== -->
        <div class="vertical-menu">
            <div data-simplebar class="h-100">

                <!-- LOGO -->
                <div class="navbar-brand-box">
                    <a href="{{ url('/home') }}" class="logo mt-2" style="display: inline-block;">
                        @if ($generalInfo->logo != '' && $generalInfo->logo != null && file_exists(public_path($generalInfo->logo)))
                            <span>
                                <img src="{{ get_file_url() }}/{{ $generalInfo->logo }}" alt=""
                                    class="img-fluid" style="max-height: 100px; max-width: 150px;"
                                    onerror="this.onerror=null;this.src='{{ $fallbackLogoUrl }}';">
                            </span>
                        @else
                            <img src="{{ $fallbackLogoUrl }}" alt="NexGen IT" class="img-fluid"
                                style="max-height: 100px; max-width: 150px;">
                        @endif
                    </a>
                </div>

                <!--- Sidemenu -->
                <div id="sidebar-menu">

                    @if (Auth::user()->user_type == 1)
                        @include('backend.sidebar')
                    @else
                        @include('backend.sidebarWithAssignedMenu')
                    @endif

                </div>
                <!-- Sidebar -->
            </div>
        </div>
        <!-- Left Sidebar End -->

        <!-- ============================================================== -->
        <!-- Start right Content here -->
        <!-- ============================================================== -->
        <style>
            @media (max-width: 767px) {
                .mobile-hide {
                    display: none !important;
                }
            }
        </style>
        {{-- if domain is control-panel.youngerlifestyle.shop --}}
        @if (request()->getHost() === 'control-panel.youngerlifestyle.shop' ||
                request()->getHost() === 'control.imaginex.com.bd' ||
                request()->getHost() === 'connecting.getprotouch.com' ||
                request()->getHost() === 'control.gadgetgallery.live' ||
                request()->getHost() === 'control.kintecay.store')


            {{-- optional for due payment --}}
            <style>
                .billing-notice {
                    margin-top: 25px;
                    font-size: 25px;
                    font-weight: bold;
                    border: none;
                    border-left: 4px solid #ffc107;
                    animation: noticeSlide 0.4s ease;
                }

                @keyframes noticeSlide {
                    from {
                        opacity: 0;
                        transform: translateY(-8px);
                    }

                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }
            </style>
            <div class="main-content">
                <div
                    class="billing-notice alert alert-warning d-flex align-items-center justify-content-between rounded-4 px-3 py-2 shadow-sm">
                    <div>
                        <div class="fw-semibold text-dark">
                            Your Monthly Billing Notice
                        </div>
                        <small class="text-muted">
                            Your {{ env('APP_COMPANY_NAME') }} subscription payment must be paid today.
                        </small>
                    </div>
                    <a href="https://wa.me/8801301884400?text=Hello%2C%20I%20would%20like%20to%20clear%20my%20monthly%20subscription%20payment.%20Please%20share%20the%20payment%20details%20and%20next%20steps.%20Thank%20you."
                        target="_blank" class="btn btn-sm btn-dark">
                        Pay Now
                    </a>
                </div>
                <style>
                    .invoice-wrapper {
                        max-width: 700px;
                        margin: 30px auto;
                        background: #ffffff;
                        border: 1px solid #d9e8c2;
                        border-radius: 28px;
                        overflow: hidden;
                        font-family: Arial, sans-serif;
                        color: #2f3b2f;
                    }

                    .invoice-header {
                        background: #80d847;
                        padding: 35px 30px 45px;
                        text-align: center;
                        color: #fff;
                    }

                    .invoice-logo {
                        margin-bottom: 20px;
                    }

                    .invoice-logo img,
                    .invoice-logo span {
                        display: inline-flex;
                        align-items: center;
                        justify-content: center;
                        min-width: 70px;
                        height: 70px;
                        padding: 0 16px;
                        font-size: 18px;
                        font-weight: 700;
                        color: #fff;
                        object-fit: contain;
                        background: rgba(255, 255, 255, 0.08);
                        border: 2px solid rgba(255, 255, 255, 0.25);
                        border-radius: 50%;
                    }

                    .invoice-header h1 {
                        margin: 0;
                        font-size: 38px;
                        font-weight: 700;
                    }

                    .invoice-header p {
                        margin-top: 12px;
                        font-size: 18px;
                        color: #d6e8c7;
                    }

                    .invoice-body {
                        padding: 45px 40px;
                    }

                    .invoice-body .desc {
                        text-align: center;
                        font-size: 20px;
                        line-height: 1.8;
                        color: #444;
                        margin-bottom: 35px;
                    }

                    .invoice-body .desc strong {
                        color: #2f6f05;
                    }

                    .invoice-box {
                        background: #f4f8ec;
                        border: 1px solid #d8e7c2;
                        border-radius: 18px;
                        padding: 28px;
                        margin-bottom: 40px;
                    }

                    .invoice-box h3 {
                        margin: 0 0 15px;
                        color: #2f6f05;
                        font-size: 24px;
                    }

                    .invoice-box p {
                        margin: 0;
                        font-size: 18px;
                        line-height: 1.8;
                        color: #536153;
                    }

                    .divider {
                        height: 1px;
                        background: #d8e7c2;
                        margin: 40px 0;
                    }

                    .steps {
                        display: flex;
                        gap: 20px;
                        justify-content: space-between;
                        flex-wrap: wrap;
                    }

                    .step-card {
                        flex: 1;
                        min-width: 180px;
                        border: 1px solid #d8e7c2;
                        border-radius: 18px;
                        padding: 25px 20px;
                        text-align: center;
                        background: #fff;
                    }

                    .step-number {
                        width: 45px;
                        height: 45px;
                        line-height: 45px;
                        margin: 0 auto 18px;
                        border-radius: 50%;
                        background: #2f6f05;
                        color: #fff;
                        font-size: 20px;
                        font-weight: bold;
                    }

                    .step-card h4 {
                        margin: 0 0 10px;
                        color: #2f6f05;
                        font-size: 22px;
                    }

                    .step-card p {
                        margin: 0;
                        color: #777;
                        font-size: 16px;
                    }

                    .invoice-footer {
                        text-align: center;
                        padding-top: 35px;
                        font-size: 20px;
                        color: #666;
                    }

                    .invoice-footer strong {
                        color: #2f6f05;
                    }

                    @media(max-width: 768px) {
                        .invoice-header h1 {
                            font-size: 30px;
                        }

                        .invoice-body {
                            padding: 30px 20px;
                        }

                        .steps {
                            flex-direction: column;
                        }
                    }
                </style>

                <div class="invoice-wrapper">

                    <div class="invoice-header">

                        <div class="invoice-logo">
                            <span>NexGen IT</span>
                        </div>

                        <h1>Invoice payment reminder</h1>
                        <p>A gentle note from your business portal</p>

                    </div>

                    <div class="invoice-body">

                        <div class="desc">
                            We kindly remind you to <strong>settle your due invoice</strong>
                            to ensure your business continues to run smoothly and
                            without any interruption.
                        </div>

                        <div class="invoice-box">
                            <h3>📍 Where to find your invoice</h3>

                            <p>
                                Your invoice is ready and waiting in your dedicated group.
                                Simply head over there to review and complete your payment.
                            </p>
                        </div>

                        <div class="divider"></div>

                        <div class="steps">

                            <div class="step-card">
                                <div class="step-number">1</div>
                                <h4>Open your group</h4>
                                <p>Go to the portal</p>
                            </div>
                            <div class="step-card">
                                <div class="step-number">2</div>
                                <h4>Find the invoice</h4>
                                <p>Pinned at the top</p>
                            </div>
                            <div class="step-card">
                                <div class="step-number">3</div>
                                <h4>Complete payment</h4>
                                <p>Quick & secure</p>
                            </div>

                        </div>

                        <div class="invoice-footer">
                            Thank you for your continued
                            <strong>trust & partnership.</strong>
                        </div>

                        {{-- support --}}
                        <div class="invoice-footer mt-4">
                            Need help? Contact our support team at<br>
                            <a class="text-decoration-underline btn btn-lg btn-primary"
                                href="https://wa.me/8801301884400?text=Hello%2C%20I%20would%20like%20to%20clear%20my%20monthly%20subscription%20payment.%20Please%20share%20the%20payment%20details%20and%20next%20steps.%20Thank%20you."
                                target="_blank">Support</a>
                        </div>

                    </div>

                </div>
            </div>
        @else
            <div class="main-content">

                <header id="page-topbar">
                    <div class="navbar-header">
                        <div class="d-flex align-items-center">
                            <button type="button" class="btn btn-sm mr-2 d-lg-none header-item" id="vertical-menu-btn">
                                <i class="fa fa-fw fa-bars"></i>
                            </button>

                            <button type="button" class="btn btn-sm mr-2 d-none d-lg-block header-item"
                                onclick="$('body').toggleClass('lg_hide_menu')" id="lg_menu_toggler">
                                <i class="fa fa-fw fa-bars"></i>
                            </button>

                            <div class="header-breadcumb">
                                <h6 class="header-pretitle d-none d-md-block">Pages <i
                                        class="dripicons-arrow-thin-right"></i> @yield('page_title')</h6>
                                <h2 class="header-title">@yield('page_heading')</h2>
                            </div>
                            <div class="dropdown d-inline-block ml-2">
                                <a href="{{ url('/pos/desktop') }}"
                                    data-active-paths="{{ url('/pos/desktop') }}, {{ url('/pos/desktop/create') }}"
                                    data-active-paths="{{ url('/pos/desktop') }}, {{ url('/pos/desktop/create') }}"
                                    class="btn text-white rounded" style = "background-color: teal;">

                                    <i class="fas fa-store"></i>
                                    POS
                                </a>
                            </div>
                            <div class="dropdown ml-2 d-none d-sm-inline-block">
                                <a href="{{ url('/add/new/expense') }}" class="btn text-white"
                                    style = "background-color: #931920;">
                                    <i class="fas fa-wallet"></i>
                                    Expense
                                </a>
                            </div>
                            <div class="dropdown ml-2 d-none d-sm-inline-block">
                                <form method="POST" action="{{ url('/clear/cache') }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn text-white" style="background-color: #17b311;">
                                        <i class="fas fa-repeat" aria-hidden="true"></i>
                                        Clear Cache
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div class="d-flex align-items-center">
                            <!-- Notification Bell -->
                            @php
                                $totalEcommercePending = DB::table('product_orders')
                                    ->where('order_source', 'ecommerce')
                                    ->where('order_status', 'pending')
                                    ->count();
                            @endphp

                            @if ($totalEcommercePending > 0)
                                <div class="position-relative mr-3">
                                    <a href="{{ url('/product-order/list?order_source=ecommerce') }}">
                                        <i class="fas fa-bell fa-lg"></i>

                                        <span class="text-danger position-absolute"
                                            style="top:-8px; right:-10px; font-size:11px; font-weight:bold;">
                                            {{ $totalEcommercePending }}
                                        </span>
                                    </a>
                                </div>
                            @endif

                            <!-- Visit Website Button -->
                            <div class="position-relative mr-3 ml-2 d-none d-sm-inline-block">
                                <a href="{{ preg_match('/^https?:\/\//', $generalInfo->website) ? $generalInfo->website : 'https://' . $generalInfo->website }}"
                                    target="_blank">
                                    <i class="fas fa-globe fa-lg"></i>
                                </a>
                            </div>

                            <div class="dropdown d-inline-block ml-2">
                                <button type="button" class="btn header-item" id="page-header-user-dropdown"
                                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <img class="rounded-circle header-profile-user"
                                        src="{{ versioned_url('assets/images/users/avatar-1.jpg') }}"
                                        alt="Header Avatar">
                                    <span class="d-none d-sm-inline-block ml-1">@auth {{ Auth::user()->name }}
                                        @endauth
                                    </span>
                                    <i class="mdi mdi-chevron-down d-none d-sm-inline-block"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-right">
                                    {{-- <a class="dropdown-item d-flex align-items-center justify-content-between"
                                    href="javascript:void(0)">
                                    Profile
                                </a> --}}
                                    <a class="dropdown-item d-flex align-items-center justify-content-between"
                                        href="{{ url('/change/password/page') }}">
                                        <span class=""><i class="fas fa-key"></i>
                                            Change Password
                                        </span>
                                    </a>
                                    <a href="{{ route('logout') }}"
                                        class="dropdown-item d-flex align-items-center justify-content-between logout"
                                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                        <span class="">
                                            <i class="fas fa-sign-out-alt"></i>
                                            Logout
                                        </span>
                                    </a>

                                    <form id="logout-form" action="{{ route('logout') }}" method="POST"
                                        class="d-none">
                                        @csrf
                                    </form>
                                </div>
                            </div>

                        </div>
                    </div>
                </header>

                <div class="page-content">
                    <div class="container-fluid">

                        @yield('content')

                    </div> <!-- container-fluid -->
                </div>
                <!-- End Page-content -->
            </div>
        @endif
        <!-- end main content-->
    </div>
    <!-- END layout-wrapper -->

    <!-- Overlay-->
    <div class="menu-overlay"></div>

    @include('backend.components.media-manager.modal')

    <!-- jQuery  -->
    <script src="{{ versioned_url('assets/js/jquery.min.js') }}"></script>
    <script src="{{ versioned_url('assets/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ versioned_url('assets/js/metismenu.min.js') }}"></script>
    <script src="{{ versioned_url('assets/js/waves.js') }}"></script>
    <script src="{{ versioned_url('assets/js/simplebar.min.js') }}"></script>
    <script src="{{ versioned_url('assets/plugins/jquery-sparkline/jquery.sparkline.min.js') }}"></script>
    <script src="{{ versioned_url('assets/plugins/morris-js/morris.min.js') }}"></script>
    <script src="{{ versioned_url('assets/plugins/raphael/raphael.min.js') }}"></script>
    <script src="{{ versioned_url('assets/pages/dashboard-demo.js') }}"></script>
    <script src="{{ versioned_url('assets/js/theme.js') }}"></script>
    <script src="{{ versioned_url('assets/js/ajax.js') }}"></script>
    <script src="{{ versioned_url('assets/js/ajax_two.js') }}"></script>
    <script src="{{ versioned_url('assets/js/search_product_ajax.js') }}"></script>
    <script src="{{ versioned_asset('assets/js/media-manager/media-manager.js') }}"></script>
    <script src="{{ versioned_asset('assets/js/media-manager/media-picker-field.js') }}"></script>

    <script>
        const handleScroll = () => {
            var Sidebar = document.querySelector('.simplebar-content-wrapper')
            var scrollPosition = Sidebar.scrollTop;
            localStorage.setItem('scroll_nav', scrollPosition);
        }
        document.addEventListener('DOMContentLoaded', function() {
            var Sidebar = document.querySelector('.simplebar-content-wrapper');
            const Location = window.location.pathname;
            Sidebar.onscroll = handleScroll;

            let scroll_nav = localStorage.getItem('scroll_nav');
            if (scroll_nav && Location != '/dashboard') {
                Sidebar.scrollTop = scroll_nav;
            } else {
                Sidebar.scrollTop = 0;
                localStorage.setItem('scroll_nav', 0);
            }
        });
    </script>

    @yield('footer_js')
    @stack('js')
    @stack('footer_js')

    <script src="{{ versioned_url('assets/js/toastr.min.js') }}"></script>
    {!! Toastr::message() !!}

    <!-- PWA Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('{{ url('/sw.js') }}')
                    .then(function(registration) {
                        console.log('ServiceWorker registration successful with scope: ', registration.scope);
                    }, function(err) {
                        console.log('ServiceWorker registration failed: ', err);
                    });
            });
        }
    </script>

</body>

</html>
