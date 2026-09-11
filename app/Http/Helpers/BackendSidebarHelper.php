<?php

use Illuminate\Support\Facades\DB;

if (!function_exists('backend_sidebar_modules')) {
    function backend_sidebar_modules(): array
    {
        $count = fn($table, $callback = null) => function () use ($table, $callback) {
            try {
                $query = DB::table($table);

                if (is_callable($callback)) {
                    $callback($query);
                }

                return '(' . $query->count() . ')';
            } catch (\Throwable $exception) {
                return '(0)';
            }
        };

        $url = fn($path) => fn() => url($path);
        $route = fn($name, $params = []) => fn() => route($name, $params);
        $active = fn(...$items) => fn() => implode(', ', array_map(fn($item) => is_callable($item) ? $item() : $item, $items));

        return [
            [
                'module' => 'DASHBOARDS',
                'icon' => 'feather-pie-chart',
                'show_on_nav' => true,
                'submodules' => [
                    ['submodule' => 'Dashboard', 'icon' => 'feather-shopping-bag', 'url' => $url('/home'), 'active_paths' => $url('/home'), 'show_on_nav' => true, 'childmodule' => []],
                    ['submodule' => 'Analytics', 'icon' => 'feather-trending-up', 'url' => $route('analytics.dashboard'), 'active_paths' => $route('analytics.dashboard'), 'show_on_nav' => true, 'childmodule' => []],
                    ['submodule' => 'CRM', 'permission_key' => 'crm.dashboard', 'icon' => 'feather-users', 'url' => $url('/crm-home'), 'active_paths' => $url('/crm-home'), 'show_on_nav' => true, 'childmodule' => []],
                    ['submodule' => 'Accounts', 'icon' => 'feather-pie-chart', 'url' => $url('/accounts-home'), 'active_paths' => $url('/accounts-home'), 'show_on_nav' => true, 'childmodule' => []],
                    ['submodule' => 'Inventory', 'icon' => 'feather-package', 'url' => $url('/inventory-home'), 'active_paths' => $url('/inventory-home'), 'show_on_nav' => true, 'childmodule' => []],
                    ['submodule' => 'Reports', 'icon' => 'feather-file-text', 'url' => $url('/app-report'), 'active_paths' => $url('/app-report'), 'show_on_nav' => true, 'childmodule' => []],
                ],
            ],
            [
                'module' => 'PRODUCT MANAGEMENT',
                'icon' => 'feather-box',
                'show_on_nav' => true,
                'submodules' => [
                    [
                        'submodule' => 'Products',
                        'icon' => 'feather-box',
                        'show_on_nav' => true,
                        'childmodule' => [
                            ['childmodule' => 'All Products', 'icon' => 'feather-list', 'url' => $route('product-management.index'), 'active_paths' => $route('product-management.index'), 'show_on_nav' => true],
                            ['childmodule' => 'Create New Product', 'icon' => 'feather-plus-circle', 'url' => $route('product-management.create'), 'active_paths' => $route('product-management.create'), 'show_on_nav' => true],
                            ['childmodule' => 'Package Products', 'icon' => 'feather-package', 'url' => $url('/package-products'), 'active_paths' => $active($url('/package-products'), $url('/package-products/create'), $url('/package-products/*/edit'), $url('/package-products/*/manage-items')), 'badge' => $count('package_products'), 'show_on_nav' => true],
                            ['childmodule' => 'Barcode Generator', 'icon' => 'feather-maximize', 'url' => $url('/barcode_gen'), 'active_paths' => $url('/barcode_gen'), 'show_on_nav' => false],
                        ],
                    ],
                    [
                        'submodule' => 'Categories',
                        'icon' => 'feather-grid',
                        'show_on_nav' => true,
                        'childmodule' => [
                            ['childmodule' => 'Categories', 'icon' => 'feather-sliders', 'url' => $url('/view/all/category'), 'active_paths' => $active($url('/view/all/category'), $url('/add/new/category'), $url('/edit/category/*'), $url('/rearrange/category')), 'badge' => $count('categories', fn($q) => $q->where('status', 1)), 'show_on_nav' => true],
                            ['childmodule' => 'Subcategories', 'icon' => 'feather-command', 'url' => $url('/view/all/subcategory'), 'active_paths' => $active($url('/view/all/subcategory'), $url('/add/new/subcategory'), $url('/edit/subcategory/*'), $url('/rearrange/subcategory')), 'badge' => $count('subcategories', fn($q) => $q->where('status', 1)), 'show_on_nav' => true],
                            ['childmodule' => 'Child Categories', 'icon' => 'feather-git-pull-request', 'url' => $url('/view/all/childcategory'), 'active_paths' => $active($url('/view/all/childcategory'), $url('/add/new/childcategory'), $url('/edit/childcategory/*'), $url('/rearrange/childcategory')), 'badge' => $count('child_categories', fn($q) => $q->where('status', 1)), 'show_on_nav' => true],
                        ],
                    ],
                    [
                        'submodule' => 'Featured',
                        'icon' => 'feather-star',
                        'url' => $url('/featured/category/products'),
                        'show_on_nav' => true,
                        'childmodule' => [
                            ['childmodule' => 'Featured Category', 'icon' => 'feather-tag', 'url' => $url('/featured/category/products'), 'active_paths' => $url('/featured/category/products'), 'show_on_nav' => true],
                            ['childmodule' => 'Swap Featured Category', 'icon' => 'feather-repeat', 'url' => $url('/featured/category/order'), 'active_paths' => $url('/featured/category/order'), 'show_on_nav' => true],
                        ]
                    ],
                    ['submodule' => 'Product Offers', 'icon' => 'feather-percent', 'url' => $route('product-management.product-offers.index'), 'active_paths' => $active($route('product-management.product-offers.index'), $url('/product-management/product-offers/create'), fn() => rtrim(url('/product-management/product-offers'), '/') . '/*'), 'show_on_nav' => true, 'childmodule' => []],
                    [
                        'submodule' => 'Attributes',
                        'icon' => 'feather-settings',
                        'show_on_nav' => true,
                        'childmodule' => [
                            ['childmodule' => 'Measurement Units', 'icon' => 'feather-activity', 'url' => $url('/view/all/units'), 'active_paths' => $url('/view/all/units'), 'show_on_nav' => true],
                            ['childmodule' => 'Brands', 'icon' => 'feather-award', 'url' => $url('/view/all/brands'), 'active_paths' => $active($url('/view/all/brands'), $url('/add/new/brand'), $url('/rearrange/brands'), $url('edit/brand/*')), 'show_on_nav' => true],
                            ['childmodule' => 'Models', 'icon' => 'feather-layers', 'url' => $url('/view/all/models'), 'active_paths' => $active($url('/view/all/models'), $url('add/new/model'), $url('edit/model/*')), 'show_on_nav' => true],
                            ['childmodule' => 'Flags', 'icon' => 'feather-flag', 'url' => $url('/view/all/flags'), 'active_paths' => $url('/view/all/flags'), 'show_on_nav' => true],
                            ['childmodule' => 'Warranties', 'icon' => 'feather-shield', 'url' => $url('/view/all/warrenties'), 'active_paths' => $url('/view/all/warrenties'), 'show_on_nav' => true],
                            ['childmodule' => 'Variant Management', 'icon' => 'feather-layers', 'url' => $route('variant-management.index'), 'active_paths' => $route('variant-management.index'), 'badge' => $count('product_stock_variant_groups'), 'badge_title' => 'Total Variant Groups', 'show_on_nav' => true],
                            ['childmodule' => 'Product Websites', 'icon' => 'feather-globe', 'url' => $route('ViewAllProductWebsites'), 'active_paths' => $active($route('ViewAllProductWebsites'), $route('AddNewProductWebsite')), 'show_on_nav' => true],
                        ],
                    ],
                    [
                        'submodule' => 'Reviews & Q/A',
                        'icon' => 'feather-message-circle',
                        'show_on_nav' => true,
                        'childmodule' => [
                            ['childmodule' => 'Product Reviews', 'icon' => 'feather-star', 'url' => $url('/view/product/reviews'), 'active_paths' => $url('/view/product/reviews'), 'badge' => $count('product_reviews', fn($q) => $q->where('status', 0)), 'badge_style' => 'color:goldenrod', 'show_on_nav' => true],
                            ['childmodule' => 'Questions/Answers', 'icon' => 'feather-help-circle', 'url' => $url('/view/product/question/answer'), 'active_paths' => $url('/view/product/question/answer'), 'badge' => $count('product_question_answers', fn($q) => $q->whereNull('answer')->orWhere('answer', '')), 'badge_style' => 'color:goldenrod', 'show_on_nav' => true],
                        ],
                    ],
                ],
            ],
            [
                'module' => 'SERVICE MANAGEMENT',
                'icon' => 'feather-briefcase',
                'show_on_nav' => true,
                'submodules' => [
                    ['submodule' => 'Dashboard', 'icon' => 'feather-grid', 'url' => $route('service-management.dashboard'), 'active_paths' => $route('service-management.dashboard'), 'show_on_nav' => true, 'childmodule' => []],
                    [
                        'submodule' => 'Services',
                        'icon' => 'feather-briefcase',
                        'show_on_nav' => true,
                        'childmodule' => [
                            ['childmodule' => 'All Services', 'icon' => 'feather-list', 'url' => $route('service-management.services.index'), 'active_paths' => $active($route('service-management.services.index'), $url('/service-management/services/*/edit')), 'show_on_nav' => true],
                            ['childmodule' => 'Create Service', 'icon' => 'feather-plus-circle', 'url' => $route('service-management.services.create'), 'active_paths' => $route('service-management.services.create'), 'show_on_nav' => true],
                        ],
                    ],
                    [
                        'submodule' => 'Service Instances',
                        'icon' => 'feather-clipboard',
                        'show_on_nav' => true,
                        'childmodule' => [
                            ['childmodule' => 'All Instances', 'icon' => 'feather-list', 'url' => $route('service-management.instances.index'), 'active_paths' => $route('service-management.instances.index'), 'show_on_nav' => true],
                            ['childmodule' => 'Create Instance', 'icon' => 'feather-plus-circle', 'url' => $route('service-management.instances.create'), 'active_paths' => $route('service-management.instances.create'), 'show_on_nav' => true],
                        ],
                    ],
                    ['submodule' => 'Billing', 'icon' => 'feather-file-text', 'url' => $route('service-management.billing.index'), 'active_paths' => $route('service-management.billing.index'), 'show_on_nav' => true, 'childmodule' => []],
                    ['submodule' => 'Payments', 'icon' => 'feather-dollar-sign', 'url' => $route('service-management.payments.index'), 'active_paths' => $route('service-management.payments.index'), 'show_on_nav' => true, 'childmodule' => []],
                    ['submodule' => 'Reports', 'icon' => 'feather-bar-chart-2', 'url' => $route('service-management.reports.index'), 'active_paths' => $route('service-management.reports.index'), 'show_on_nav' => true, 'childmodule' => []],
                ],
            ],
            [
                'module' => 'DELIVERY MANAGEMENT',
                'icon' => 'feather-truck',
                'show_on_nav' => true,
                'submodules' => [
                    ['submodule' => 'Dashboard', 'icon' => 'feather-grid', 'url' => $route('delivery-management.dashboard'), 'active_paths' => $route('delivery-management.dashboard'), 'show_on_nav' => true, 'childmodule' => []],
                    [
                        'submodule' => 'Delivery Providers',
                        'icon' => 'feather-navigation',
                        'show_on_nav' => true,
                        'childmodule' => [
                            ['childmodule' => 'All Providers', 'icon' => 'feather-list', 'url' => $route('delivery-management.providers.index'), 'active_paths' => $active($route('delivery-management.providers.index'), $url('/delivery-management/providers/*/edit')), 'show_on_nav' => true],
                            ['childmodule' => 'Create Provider', 'icon' => 'feather-plus-circle', 'url' => $route('delivery-management.providers.create'), 'active_paths' => $route('delivery-management.providers.create'), 'show_on_nav' => true],
                        ],
                    ],
                    [
                        'submodule' => 'Delivery Employees',
                        'icon' => 'feather-users',
                        'show_on_nav' => true,
                        'childmodule' => [
                            ['childmodule' => 'All Employees', 'icon' => 'feather-list', 'url' => $route('delivery-management.employees.index'), 'active_paths' => $active($route('delivery-management.employees.index'), $url('/delivery-management/employees/*/edit')), 'show_on_nav' => true],
                            ['childmodule' => 'Create Employee', 'icon' => 'feather-user-plus', 'url' => $route('delivery-management.employees.create'), 'active_paths' => $route('delivery-management.employees.create'), 'show_on_nav' => true],
                        ],
                    ],
                    [
                        'submodule' => 'Zones & Rate Cards',
                        'icon' => 'feather-map',
                        'show_on_nav' => true,
                        'childmodule' => [
                            ['childmodule' => 'Delivery Zones', 'icon' => 'feather-map-pin', 'url' => $route('delivery-management.zones.index'), 'active_paths' => $active($route('delivery-management.zones.index'), $url('/delivery-management/zones/*/edit')), 'show_on_nav' => true],
                            ['childmodule' => 'Rate Cards', 'icon' => 'feather-credit-card', 'url' => $route('delivery-management.rate-cards.index'), 'active_paths' => $active($route('delivery-management.rate-cards.index'), $url('/delivery-management/rate-cards/*/edit')), 'show_on_nav' => true],
                        ],
                    ],
                    ['submodule' => 'Shipments', 'icon' => 'feather-package', 'url' => $route('delivery-management.shipments.index'), 'active_paths' => $active($route('delivery-management.shipments.index'), $url('/delivery-management/shipments/*')), 'show_on_nav' => true, 'childmodule' => []],
                    [
                        'submodule' => 'API Courier Operations',
                        'icon' => 'feather-truck',
                        'show_on_nav' => true,
                        'childmodule' => function () {
                            $items = [
                                ['childmodule' => 'Courier Config', 'icon' => 'feather-settings', 'url' => fn() => route('delivery-management.courier-config.index'), 'active_paths' => fn() => route('delivery-management.courier-config.index'), 'show_on_nav' => true],
                                ['childmodule' => 'Courier Settlement', 'icon' => 'feather-dollar-sign', 'url' => fn() => route('delivery-management.courier-settlements.index'), 'active_paths' => fn() => route('delivery-management.courier-settlements.index'), 'show_on_nav' => true],
                            ];

                            foreach (App\Models\ProductOrderCourierMethod::orderBy('title')->get() as $courier) {
                                $items[] = [
                                    'childmodule' => $courier->title . ' Orders',
                                    'icon' => 'feather-truck',
                                    'url' => fn() => route('delivery-management.courier-wise-orders', ['courier' => $courier->title]),
                                    'active_paths' => fn() => route('delivery-management.courier-wise-orders', ['courier' => $courier->title]),
                                    'show_on_nav' => true,
                                ];
                            }

                            return $items;
                        },
                    ],
                    ['submodule' => 'COD Collections', 'icon' => 'feather-dollar-sign', 'url' => $route('delivery-management.cod-collections.index'), 'active_paths' => $active($route('delivery-management.cod-collections.index'), $url('/delivery-management/cod-collections/*')), 'show_on_nav' => true, 'childmodule' => []],
                    ['submodule' => 'Settlements', 'icon' => 'feather-check-square', 'url' => $route('delivery-management.settlements.index'), 'active_paths' => $active($route('delivery-management.settlements.index'), $url('/delivery-management/settlements/*')), 'show_on_nav' => true, 'childmodule' => []],
                    ['submodule' => 'Reports', 'icon' => 'feather-bar-chart-2', 'url' => $route('delivery-management.reports.index'), 'active_paths' => $route('delivery-management.reports.index'), 'show_on_nav' => true, 'childmodule' => []],
                    ['submodule' => 'User Manual', 'icon' => 'feather-book-open', 'url' => $route('delivery-management.user-manual.index'), 'active_paths' => $active($route('delivery-management.user-manual.index'), $url('/delivery-management/user-manual*')), 'show_on_nav' => true, 'childmodule' => []],
                ],
            ],
            [
                'module' => 'SALES & ORDERS',
                'icon' => 'feather-shopping-cart',
                'show_on_nav' => true,
                'submodules' => [
                    [
                        'submodule' => 'Orders',
                        'icon' => 'feather-truck',
                        'show_on_nav' => true,
                        'childmodule' => function () use ($url, $active, $count) {
                            $items = [
                                ['childmodule' => 'Pos Orders', 'icon' => 'feather-list', 'url' => fn() => url('/product-order/list') . '?order_source=pos', 'active_paths' => $active(fn() => url('/product-order/list') . '?order_source=pos', $url('/add/new/product-order/manage')), 'badge' => $count('product_orders', fn($q) => $q->where('order_source', 'pos')->where('order_status', 'pending')), 'show_on_nav' => true],
                                ['childmodule' => 'eCom Orders', 'icon' => 'feather-list', 'url' => fn() => url('/product-order/list') . '?order_source=ecommerce', 'active_paths' => $active(fn() => url('/product-order/list') . '?order_source=ecommerce', $url('/add/new/product-order/manage')), 'badge' => $count('product_orders', fn($q) => $q->where('order_source', 'ecommerce')->where('order_status', 'pending')), 'show_on_nav' => true],
                            ];

                            if (function_exists('is_multiple_domain') && is_multiple_domain()) {
                                foreach (get_all_websites() as $website) {
                                    $items[] = [
                                        'childmodule' => $website->title . ' Orders',
                                        'icon' => 'feather-shopping-cart',
                                        'url' => fn() => url('/product-order/list') . '?order_source=ecommerce&product_website_id=' . $website->id,
                                        'active_paths' => fn() => url('/product-order/list') . '?order_source=ecommerce&product_website_id=' . $website->id . ', ' . url('/product-order/list?order_source=ecommerce&product_website_id=' . $website->id . '/create'),
                                        'badge' => $count('product_orders', fn($q) => $q->where('order_source', 'ecommerce')->where('order_status', 'pending')->where('product_website_id', $website->id)),
                                        'show_on_nav' => true,
                                    ];
                                }
                            }

                            $items[] = ['childmodule' => 'Incomplete Orders', 'icon' => 'feather-rotate-ccw', 'url' => fn() => url('/product-order/list') . '?order_source=ecommerce&is_completed=0', 'active_paths' => fn() => url('/product-order/list') . '?order_source=ecommerce&is_completed=0', 'badge' => $count('product_orders', fn($q) => $q->where('order_source', 'ecommerce')->where('is_completed', '0')), 'show_on_nav' => true];

                            return $items;
                        },
                    ],
                    ['submodule' => 'Create Order', 'icon' => 'feather-shopping-cart', 'url' => $url('/pos/desktop'), 'active_paths' => $active($url('/pos/desktop'), $url('/pos/desktop/create')), 'show_on_nav' => true, 'childmodule' => []],
                    ['submodule' => 'Create Order (v3)', 'icon' => 'feather-shopping-cart', 'url' => $url('/pos/desktop/v3'), 'active_paths' => $active($url('/pos/desktop/v3')), 'show_on_nav' => true, 'childmodule' => []],
                    ['submodule' => 'All Quotations', 'icon' => 'feather-file-text', 'url' => $route('ViewAllProductOrderQuotations'), 'active_paths' => $active($route('ViewAllProductOrderQuotations'), $route('CreateProductOrderQuotation')), 'show_on_nav' => true, 'childmodule' => []],
                    [
                        'submodule' => 'Return Refund',
                        'icon' => 'feather-corner-up-left',
                        'show_on_nav' => true,
                        'childmodule' => [
                            ['childmodule' => 'Dashboard', 'icon' => 'feather-grid', 'url' => $route('ReturnRefundDashboard'), 'active_paths' => $route('ReturnRefundDashboard'), 'badge' => $count('product_order_returns', fn($q) => $q->where('status', 'active')->whereRaw('COALESCE(total, 0) > COALESCE(refunded_amount, 0)')), 'show_on_nav' => true],
                            ['childmodule' => 'Order Returns', 'icon' => 'feather-list', 'url' => $route('ViewAllProductOrderReturns'), 'active_paths' => $active($route('ViewAllProductOrderReturns'), $url('/show/product-order-return/*'), $url('/show/product-order-refund/*')), 'show_on_nav' => true],
                            ['childmodule' => 'All Refunds', 'icon' => 'feather-credit-card', 'url' => $route('ViewAllProductOrderRefunds'), 'active_paths' => $route('ViewAllProductOrderRefunds'), 'show_on_nav' => true],
                            ['childmodule' => 'Create Manual Return', 'icon' => 'feather-plus-circle', 'url' => $route('CreateManualProductReturn'), 'active_paths' => $route('CreateManualProductReturn'), 'show_on_nav' => true],
                            ['childmodule' => 'All Manual Returns', 'icon' => 'feather-list', 'url' => $route('ViewAllManualProductReturns'), 'active_paths' => $route('ViewAllManualProductReturns'), 'show_on_nav' => true],
                        ],
                    ],
                    [
                        'submodule' => 'Courier-wise Orders',
                        'icon' => 'feather-truck',
                        'show_on_nav' => true,
                        'childmodule' => function () {
                            $items = [
                                ['childmodule' => 'Courier Settlement', 'icon' => 'feather-dollar-sign', 'url' => fn() => route('courier-settlements.index'), 'active_paths' => fn() => route('courier-settlements.index'), 'show_on_nav' => true],
                            ];

                            foreach (App\Models\ProductOrderCourierMethod::get() as $courier) {
                                $items[] = [
                                    'childmodule' => $courier->title,
                                    'icon' => 'feather-truck',
                                    'url' => fn() => url('/orders/courier-wise-orders', ['courier' => $courier->title]),
                                    'active_paths' => fn() => url('/orders/courier-wise-orders', ['courier' => $courier->title]),
                                    'show_on_nav' => true,
                                ];
                            }

                            return $items;
                        },
                    ],
                    [
                        'submodule' => 'Customer Payments',
                        'icon' => 'feather-dollar-sign',
                        'show_on_nav' => true,
                        'childmodule' => [
                            ['childmodule' => 'Dashboard', 'icon' => 'feather-pie-chart', 'url' => $route('CustomerPaymentDashboard'), 'active_paths' => $route('CustomerPaymentDashboard'), 'show_on_nav' => true],
                            ['childmodule' => 'Customer Billing', 'icon' => 'feather-file-text', 'url' => $route('CustomerBillingIndex'), 'active_paths' => $route('CustomerBillingIndex'), 'show_on_nav' => true],
                            ['childmodule' => 'Transaction Report', 'icon' => 'feather-bar-chart-2', 'url' => $route('CustomerTransactionReport'), 'active_paths' => $route('CustomerTransactionReport'), 'show_on_nav' => true],
                            ['childmodule' => 'New Payment / Advance', 'icon' => 'feather-plus-circle', 'url' => $route('CreateCustomerPayment'), 'active_paths' => $route('CreateCustomerPayment'), 'show_on_nav' => true],
                            ['childmodule' => 'Due Payment', 'icon' => 'feather-credit-card', 'url' => $route('CreateCustomerDuePayment'), 'active_paths' => $route('CreateCustomerDuePayment'), 'show_on_nav' => true],
                            ['childmodule' => 'Old Due / Opening Balance', 'icon' => 'feather-send', 'url' => $route('CreateCustomerOpeningBalance'), 'active_paths' => $route('CreateCustomerOpeningBalance'), 'show_on_nav' => true],
                            ['childmodule' => 'Due Customer SMS Send', 'icon' => 'feather-mail', 'url' => $route('DueCustomerSmsCreate'), 'active_paths' => $route('DueCustomerSmsCreate'), 'show_on_nav' => true],
                            ['childmodule' => 'Payment Refund', 'icon' => 'feather-corner-up-left', 'url' => $route('CreateCustomerPaymentReturn'), 'active_paths' => $route('CreateCustomerPaymentReturn'), 'show_on_nav' => true],
                            ['childmodule' => 'All Transactions', 'icon' => 'feather-list', 'url' => $route('ViewAllCustomerPayments'), 'active_paths' => $route('ViewAllCustomerPayments'), 'show_on_nav' => true],
                        ],
                    ],
                    ['submodule' => 'Promo Codes', 'icon' => 'feather-gift', 'url' => $url('/view/all/promo/codes'), 'active_paths' => $active($url('/view/all/promo/codes'), $url('/add/new/code'), $url('/edit/promo/code/*')), 'badge' => $count('promo_codes', fn($q) => $q->where('status', 1)), 'show_on_nav' => true, 'childmodule' => []],
                    ['submodule' => 'Customer Wishlist', 'icon' => 'feather-heart', 'url' => $url('/view/customers/wishlist'), 'active_paths' => $url('/view/customers/wishlist'), 'show_on_nav' => true, 'childmodule' => []],
                    ['submodule' => 'Delivery Charges', 'icon' => 'feather-truck', 'url' => $url('/view/delivery/charges'), 'active_paths' => $url('/view/delivery/charges'), 'show_on_nav' => false, 'childmodule' => []],
                    ['submodule' => 'Upazila & Thana', 'icon' => 'feather-map-pin', 'url' => $url('/view/upazila/thana'), 'active_paths' => $url('/view/upazila/thana'), 'show_on_nav' => true, 'childmodule' => []],
                ],
            ],
            [
                'module' => 'INVENTORY & STOCK',
                'icon' => 'feather-package',
                'show_on_nav' => true,
                'submodules' => [
                    [
                        'submodule' => 'Warehouse',
                        'icon' => 'feather-home',
                        'show_on_nav' => true,
                        'childmodule' => [
                            ['childmodule' => 'Warehouses', 'icon' => 'feather-archive', 'url' => $url('/view/all/product-warehouse'), 'active_paths' => $active($url('/view/all/product-warehouse'), $url('/add/new/product-warehouse'), $url('/edit/product-warehouse/*')), 'badge' => $count('product_warehouses', fn($q) => $q->where('status', 'active')), 'show_on_nav' => true],
                            ['childmodule' => 'Warehouse Rooms', 'icon' => 'feather-grid', 'url' => $url('/view/all/product-warehouse-room'), 'active_paths' => $active($url('/view/all/product-warehouse-room'), $url('/add/new/product-warehouse-room'), $url('/edit/product-warehouse-room/*')), 'badge' => $count('product_warehouse_rooms'), 'show_on_nav' => true],
                            ['childmodule' => 'Room Cartons', 'icon' => 'feather-box', 'url' => $url('/view/all/product-warehouse-room-cartoon'), 'active_paths' => $active($url('/view/all/product-warehouse-room-cartoon'), $url('/add/new/product-warehouse-room-cartoon'), $url('/edit/product-warehouse-room-cartoon/*')), 'badge' => $count('product_warehouse_room_cartoons'), 'show_on_nav' => true],
                        ],
                    ],
                    [
                        'submodule' => 'Stock Adjustment',
                        'icon' => 'feather-trending-up',
                        'show_on_nav' => true,
                        'childmodule' => [
                            ['childmodule' => 'Adjustment Logs', 'icon' => 'feather-list', 'url' => $route('stock-adjustment.index'), 'active_paths' => $route('stock-adjustment.index'), 'show_on_nav' => true],
                            ['childmodule' => 'New Adjustment', 'icon' => 'feather-plus-circle', 'url' => $route('stock-adjustment.create'), 'active_paths' => $route('stock-adjustment.create'), 'show_on_nav' => true],
                        ],
                    ],
                    [
                        'submodule' => 'Suppliers',
                        'icon' => 'feather-users',
                        'show_on_nav' => true,
                        'childmodule' => [
                            ['childmodule' => 'Supplier Types', 'icon' => 'feather-tag', 'url' => $url('/view/all/supplier-source'), 'active_paths' => $active($url('/view/all/supplier-source'), $url('/add/new/supplier-source'), $url('/edit/supplier-source/*')), 'badge' => $count('supplier_source_types', fn($q) => $q->where('status', 'active')), 'show_on_nav' => true],
                            ['childmodule' => 'All Suppliers', 'icon' => 'feather-users', 'url' => $url('/view/all/product-supplier'), 'active_paths' => $active($url('/view/all/product-supplier'), $url('/add/new/product-supplier'), $url('/edit/product-supplier/*')), 'badge' => $count('product_suppliers', fn($q) => $q->where('status', 'active')), 'show_on_nav' => true],
                        ],
                    ],
                    [
                        'submodule' => 'Purchase',
                        'icon' => 'feather-shopping-bag',
                        'show_on_nav' => true,
                        'childmodule' => [
                            ['childmodule' => 'Charge Types', 'icon' => 'feather-tag', 'url' => $url('/view/all/purchase-product/charge'), 'active_paths' => $active($url('/view/all/purchase-product/charge'), $url('/add/new/purchase-product/charge'), $url('/edit/purchase-product/charge/*')), 'show_on_nav' => true],
                            ['childmodule' => 'Quotations', 'icon' => 'feather-file-text', 'url' => $url('/view/all/purchase-product/quotation'), 'active_paths' => $active($url('/view/all/purchase-product/quotation'), $url('/add/new/purchase-product/quotation'), $url('/edit/purchase-product/quotation/*'), $url('edit/purchase-product/sales/quotation/*')), 'badge' => $count('product_purchase_quotations'), 'show_on_nav' => false],
                            ['childmodule' => 'Purchase Orders', 'icon' => 'feather-shopping-cart', 'url' => $url('/view/all/purchase-product/order'), 'active_paths' => $active($url('/view/all/purchase-product/order'), $url('/add/new/purchase-product/order'), $url('/edit/purchase-product/order/*'), $url('edit/purchase-product/sales/order/*')), 'badge' => $count('product_purchase_orders'), 'show_on_nav' => true],
                            ['childmodule' => 'Purchase Returns', 'icon' => 'feather-corner-up-left', 'url' => $url('/view/all/purchase-return/order'), 'active_paths' => $active($url('/view/all/purchase-return/order'), $url('/add/new/purchase-return/order'), $url('/edit/purchase-return/order/*'), $url('edit/purchase-return/sales/order/*')), 'badge' => $count('product_purchase_returns'), 'show_on_nav' => true],
                        ],
                    ],
                ],
            ],
            [
                'module' => 'ACCOUNTS & FINANCE',
                'icon' => 'feather-dollar-sign',
                'show_on_nav' => true,
                'submodules' => [
                    ['submodule' => 'Accounts', 'icon' => 'feather-credit-card', 'url' => $url('/view/all/ac-account'), 'active_paths' => $active($url('/view/all/ac-account'), $url('/add/new/ac-account'), $url('/edit/ac-account/*')), 'badge' => $count('ac_accounts', fn($q) => $q->where('status', 'active')), 'show_on_nav' => true, 'childmodule' => []],
                    ['submodule' => 'Investor Management', 'icon' => 'feather-users', 'url' => $route('ViewAllInvestor'), 'active_paths' => $active($route('ViewAllInvestor'), $route('CreateInvestor')), 'show_on_nav' => true, 'childmodule' => []],
                    ['submodule' => 'Payment Methods', 'icon' => 'feather-credit-card', 'url' => $url('/view/all/payment-type'), 'active_paths' => $active($url('/view/all/payment-type'), $url('/add/new/payment-type'), $url('/edit/payment-type/*')), 'badge' => $count('db_paymenttypes'), 'show_on_nav' => true, 'childmodule' => []],
                    [
                        'submodule' => 'Supplier Payments',
                        'icon' => 'feather-credit-card',
                        'show_on_nav' => true,
                        'childmodule' => [
                            ['childmodule' => 'Dashboard', 'icon' => 'feather-grid', 'url' => $route('ViewAllSupplierPayments'), 'active_paths' => $route('ViewAllSupplierPayments'), 'show_on_nav' => true],
                            ['childmodule' => 'Purchase Due Payment', 'icon' => 'feather-credit-card', 'url' => $route('CreateSupplierPaymentDue'), 'active_paths' => $route('CreateSupplierPaymentDue'), 'show_on_nav' => true],
                            ['childmodule' => 'Old Due / Opening Balance', 'icon' => 'feather-file-plus', 'url' => $route('CreateSupplierOpeningBalance'), 'active_paths' => $route('CreateSupplierOpeningBalance'), 'show_on_nav' => true],
                            ['childmodule' => 'Advance Payment', 'icon' => 'feather-send', 'url' => $route('CreateSupplierPaymentAdvance'), 'active_paths' => $route('CreateSupplierPaymentAdvance'), 'show_on_nav' => true],
                            ['childmodule' => 'Advance Refund', 'icon' => 'feather-download', 'url' => $route('CreateSupplierAdvanceRefund'), 'active_paths' => $route('CreateSupplierAdvanceRefund'), 'show_on_nav' => true],
                            ['childmodule' => 'All Transactions', 'icon' => 'feather-list', 'url' => $route('ViewSupplierPayments'), 'active_paths' => $route('ViewSupplierPayments'), 'show_on_nav' => true],
                        ],
                    ],
                    ['submodule' => 'Supplier Cheque Payments', 'icon' => 'feather-file-text', 'url' => $route('supplier-cheque-payments.index'), 'active_paths' => $active($route('supplier-cheque-payments.index'), $route('supplier-cheque-payments.create')), 'show_on_nav' => true, 'childmodule' => []],
                    [
                        'submodule' => 'Incomes',
                        'icon' => 'feather-trending-up',
                        'show_on_nav' => true,
                        'childmodule' => [
                            ['childmodule' => 'Income Categories', 'icon' => 'fas fa-list', 'url' => $route('ViewAllIncomeCategory'), 'active_paths' => $active($route('ViewAllIncomeCategory'), $route('AddNewIncomeCategory'), $route('EditIncomeCategory', ['slug' => 'dummy'])), 'show_on_nav' => true],
                            ['childmodule' => 'All Incomes', 'icon' => 'fas fa-money-bill-wave', 'url' => $route('ViewAllIncome'), 'active_paths' => $active($route('ViewAllIncome'), $route('AddNewIncome'), $route('ViewIncomeDetails', ['id' => 'dummy'])), 'show_on_nav' => true],
                        ],
                    ],
                    [
                        'submodule' => 'Expenses',
                        'icon' => 'feather-trending-down',
                        'show_on_nav' => true,
                        'childmodule' => [
                            ['childmodule' => 'Expense Categories', 'icon' => 'feather-tag', 'url' => $url('/view/all/expense-category'), 'active_paths' => $active($url('/view/all/expense-category'), $url('/add/new/expense-category'), $url('/edit/expense-category/*')), 'badge' => $count('db_expense_categories', fn($q) => $q->where('status', 'active')), 'show_on_nav' => true],
                            ['childmodule' => 'All Expenses', 'icon' => 'feather-list', 'url' => $route('ViewAllExpense'), 'active_paths' => $active($route('ViewAllExpense'), $url('/add/new/expense'), $url('/edit/expense/*')), 'badge' => $count('db_expenses', fn($q) => $q->where('status', 'active')), 'show_on_nav' => true],
                        ],
                    ],
                    ['submodule' => 'Deposits', 'icon' => 'feather-trending-up', 'url' => $route('ViewAllDeposit'), 'active_paths' => $active($route('ViewAllDeposit'), $url('/add/new/deposit'), $url('/edit/deposit/*')), 'show_on_nav' => true, 'childmodule' => []],
                    ['submodule' => 'Withdraws', 'icon' => 'feather-trending-down', 'url' => $route('ViewAllWithdraw'), 'active_paths' => $active($route('ViewAllWithdraw'), $route('AddNewWithdraw')), 'show_on_nav' => true, 'childmodule' => []],
                    [
                        'submodule' => 'Fund Transfer',
                        'icon' => 'feather-repeat',
                        'show_on_nav' => true,
                        'childmodule' => [
                            ['childmodule' => 'All Transfers', 'icon' => 'feather-list', 'url' => $route('ViewAllFundTransfer'), 'active_paths' => $active($route('ViewAllFundTransfer'), $route('CreateFundTransfer')), 'show_on_nav' => true],
                            ['childmodule' => 'Transfer Types', 'icon' => 'feather-tag', 'url' => $route('ViewAllFundTransferType'), 'active_paths' => $route('ViewAllFundTransferType'), 'show_on_nav' => true],
                        ],
                    ],
                    ['submodule' => 'Account Adjustment', 'icon' => 'feather-refresh-cw', 'url' => $route('ViewAllAdjustment'), 'active_paths' => $active($route('ViewAllAdjustment'), $route('CreateAdjustment')), 'show_on_nav' => true, 'childmodule' => []],
                    [
                        'submodule' => 'Financial Reports',
                        'icon' => 'feather-file-text',
                        'show_on_nav' => true,
                        'childmodule' => [
                            ['childmodule' => 'Journal', 'icon' => 'feather-book-open', 'url' => $route('journal.index'), 'active_paths' => $route('journal.index'), 'show_on_nav' => true],
                            ['childmodule' => 'Ledger', 'icon' => 'feather-book', 'url' => $route('ledger.index'), 'active_paths' => $route('ledger.index'), 'show_on_nav' => true],
                            ['childmodule' => 'Trial Balance', 'icon' => 'feather-check-square', 'url' => $route('ledger.trial_balance'), 'active_paths' => $route('ledger.trial_balance'), 'show_on_nav' => true],
                            ['childmodule' => 'Profit & Loss', 'icon' => 'feather-bar-chart', 'url' => $route('ledger.income_statement'), 'active_paths' => $route('ledger.income_statement'), 'show_on_nav' => true],
                            ['childmodule' => 'Balance Sheet', 'icon' => 'feather-pie-chart', 'url' => $route('ledger.balance_sheet'), 'active_paths' => $route('ledger.balance_sheet'), 'show_on_nav' => true],
                            ['childmodule' => 'Cash / Bank Book', 'icon' => 'feather-credit-card', 'url' => $route('ledger.cash_bank_book'), 'active_paths' => $route('ledger.cash_bank_book'), 'show_on_nav' => true],
                            ['childmodule' => 'Supplier Ledger', 'icon' => 'feather-truck', 'url' => $route('ledger.supplier_ledger'), 'active_paths' => $route('ledger.supplier_ledger'), 'show_on_nav' => true],
                            ['childmodule' => 'Supplier Due', 'icon' => 'feather-alert-circle', 'url' => $route('ledger.supplier_due'), 'active_paths' => $route('ledger.supplier_due'), 'show_on_nav' => true],
                            ['childmodule' => 'Customer Ledger', 'icon' => 'feather-users', 'url' => $route('ledger.customer_ledger'), 'active_paths' => $route('ledger.customer_ledger'), 'show_on_nav' => true],
                            ['childmodule' => 'Customer Due', 'icon' => 'feather-user-check', 'url' => $route('ledger.customer_due'), 'active_paths' => $route('ledger.customer_due'), 'show_on_nav' => true],
                            ['childmodule' => 'Expense Report', 'icon' => 'feather-clipboard', 'url' => $route('ledger.expense_report'), 'active_paths' => $route('ledger.expense_report'), 'show_on_nav' => true],
                            ['childmodule' => 'Day Book', 'icon' => 'feather-calendar', 'url' => $route('ledger.day_book'), 'active_paths' => $route('ledger.day_book'), 'show_on_nav' => true],
                            ['childmodule' => 'Purchase Report', 'icon' => 'feather-shopping-bag', 'url' => $url('/product/purchase/report'), 'active_paths' => $url('/product/purchase/report'), 'show_on_nav' => true],
                        ],
                    ],
                ],
            ],
            [
                'module' => 'CRM & CUSTOMERS',
                'permission_key' => 'crm',
                'icon' => 'feather-users',
                'show_on_nav' => true,
                'submodules' => [
                    [
                        'submodule' => 'Customers',
                        'permission_key' => 'crm.customers',
                        'icon' => 'feather-users',
                        'show_on_nav' => true,
                        'childmodule' => [
                            ['childmodule' => 'All Customers', 'permission_key' => 'crm.customers.list', 'icon' => 'feather-user', 'url' => $url('/view/all/customer'), 'active_paths' => $active($url('/view/all/customer'), $url('/add/new/customers'), $url('/edit/customers/*')), 'badge' => $count('customers', fn($q) => $q->where('status', 'active')), 'show_on_nav' => true],
                            ['childmodule' => 'E-commerce Customers', 'permission_key' => 'crm.customers.ecommerce', 'icon' => 'feather-shopping-bag', 'url' => $route('ViewAllCustomerEcommerce'), 'active_paths' => $active($route('ViewAllCustomerEcommerce'), $url('/add/new/customer-ecommerce'), $url('/edit/customer-ecommerce/*')), 'badge' => $count('users', fn($q) => $q->where('user_type', 3)), 'show_on_nav' => true],
                            ['childmodule' => 'Customer Categories', 'permission_key' => 'crm.settings.customer-categories', 'icon' => 'feather-grid', 'url' => $url('/view/all/customer-category'), 'active_paths' => $active($url('/view/all/customer-category'), $url('/add/new/customer-category'), $url('/edit/customer-category/*')), 'badge' => $count('customer_categories', fn($q) => $q->where('status', 'active')), 'show_on_nav' => true],
                            ['childmodule' => 'Customer Source Types', 'permission_key' => 'crm.settings.customer-source-types', 'icon' => 'feather-compass', 'url' => $url('/view/all/customer-source'), 'active_paths' => $active($url('/view/all/customer-source'), $url('/add/new/customer-source'), $url('/edit/customer-source/*')), 'badge' => $count('customer_source_types', fn($q) => $q->where('status', 'active')), 'show_on_nav' => true],
                            ['childmodule' => 'Customer Tags', 'permission_key' => 'crm.settings.tags', 'icon' => 'feather-tag', 'url' => $route('crm.settings.customer-tags.index'), 'active_paths' => $route('crm.settings.customer-tags.index'), 'show_on_nav' => true],
                            ['childmodule' => 'Customer Health', 'permission_key' => 'crm.customer-health.list', 'icon' => 'feather-heart', 'url' => $route('crm.customer-health.index'), 'active_paths' => $active($route('crm.customer-health.index'), $url('/crm/customer-health*')), 'show_on_nav' => true],
                            ['childmodule' => 'Portfolio Segments', 'permission_key' => 'crm.customer-segments.list', 'icon' => 'feather-filter', 'url' => $route('crm.customer-segments.index'), 'active_paths' => $active($route('crm.customer-segments.index'), $url('/crm/customer-segments*')), 'show_on_nav' => true],
                            ['childmodule' => 'Saved Segments', 'permission_key' => 'crm.saved-customer-segments.list', 'icon' => 'feather-bookmark', 'url' => $route('crm.saved-customer-segments.index'), 'active_paths' => $active($route('crm.saved-customer-segments.index'), $url('/crm/saved-customer-segments*')), 'show_on_nav' => true],
                            ['childmodule' => 'Duplicate Review', 'permission_key' => 'crm.duplicate-customers.list', 'icon' => 'feather-copy', 'url' => $route('crm.duplicate-customers.index'), 'active_paths' => $active($route('crm.duplicate-customers.index'), $url('/crm/duplicate-customers*')), 'show_on_nav' => true],
                        ],
                    ],
                    [
                        'submodule' => 'CRM Leads',
                        'permission_key' => 'crm.leads',
                        'icon' => 'feather-target',
                        'show_on_nav' => true,
                        'childmodule' => [
                            ['childmodule' => 'Lead Worklist', 'permission_key' => 'crm.leads.list', 'icon' => 'feather-list', 'url' => $route('crm.leads.index'), 'active_paths' => $route('crm.leads.index'), 'show_on_nav' => true],
                            ['childmodule' => 'Lead Pipeline', 'permission_key' => 'crm.leads.list', 'icon' => 'feather-columns', 'url' => $route('crm.leads.pipeline'), 'active_paths' => $active($route('crm.leads.pipeline'), $url('/crm/leads/pipeline*')), 'show_on_nav' => true],
                        ],
                    ],
                    [
                        'submodule' => 'CRM Tasks',
                        'permission_key' => 'crm.tasks',
                        'icon' => 'feather-check-square',
                        'show_on_nav' => true,
                        'childmodule' => [
                            ['childmodule' => 'Task Worklist', 'permission_key' => 'crm.tasks.list', 'icon' => 'feather-list', 'url' => $route('crm.tasks.index'), 'active_paths' => $route('crm.tasks.index'), 'show_on_nav' => true],
                            ['childmodule' => 'Task Calendar', 'permission_key' => 'crm.tasks.calendar', 'icon' => 'feather-calendar', 'url' => $route('crm.tasks.calendar'), 'active_paths' => $active($route('crm.tasks.calendar'), $url('/crm/tasks/calendar*')), 'show_on_nav' => true],
                        ],
                    ],
                    [
                        'submodule' => 'CRM Communications',
                        'permission_key' => 'crm.communications',
                        'icon' => 'feather-message-circle',
                        'show_on_nav' => true,
                        'childmodule' => [
                            ['childmodule' => 'Communication History', 'permission_key' => 'crm.communications.list', 'icon' => 'feather-list', 'url' => $route('crm.communications.index'), 'active_paths' => $active($route('crm.communications.index'), $url('/crm/communications*')), 'show_on_nav' => true],
                            ['childmodule' => 'Campaign Drafts', 'permission_key' => 'crm.campaign-drafts.list', 'icon' => 'feather-file-text', 'url' => $route('crm.campaign-drafts.index'), 'active_paths' => $active($route('crm.campaign-drafts.index'), $url('/crm/campaign-drafts*')), 'show_on_nav' => true],
                        ],
                    ],
                    [
                        'submodule' => 'CRM Activities',
                        'permission_key' => 'crm.activities',
                        'icon' => 'feather-activity',
                        'show_on_nav' => true,
                        'childmodule' => [
                            ['childmodule' => 'Activity History', 'permission_key' => 'crm.activities.list', 'icon' => 'feather-list', 'url' => $route('crm.activities.index'), 'active_paths' => $active($route('crm.activities.index'), $url('/crm/activities*')), 'show_on_nav' => true],
                        ],
                    ],
                    [
                        'submodule' => 'Contact Management',
                        'permission_key' => 'crm.contacts',
                        'icon' => 'feather-phone',
                        'show_on_nav' => true,
                        'childmodule' => [
                            ['childmodule' => 'Contact History', 'permission_key' => 'crm.contacts.history', 'icon' => 'feather-clock', 'url' => $route('ViewAllCustomerContactHistories'), 'active_paths' => $active($route('ViewAllCustomerContactHistories'), $url('/add/new/customer-contact-history'), $url('/edit/customer-contact-history/*')), 'badge' => $count('customer_contact_histories'), 'show_on_nav' => true],
                            ['childmodule' => 'Scheduled Contacts', 'permission_key' => 'crm.contacts.scheduled', 'icon' => 'feather-calendar', 'url' => $url('/view/all/customer-next-contact-date'), 'active_paths' => $active($url('/view/all/customer-next-contact-date'), $url('/add/new/customer-next-contact-date'), $url('/edit/customer-next-contact-date/*')), 'badge' => $count('customer_next_contact_dates'), 'show_on_nav' => true],
                            ['childmodule' => 'Contact Requests', 'permission_key' => 'crm.contact-requests.list', 'icon' => 'feather-phone-incoming', 'url' => $url('/view/all/contact/requests'), 'active_paths' => $url('/view/all/contact/requests'), 'show_on_nav' => true],
                        ],
                    ],
                    ['submodule' => 'Newsletter Subscribers', 'permission_key' => 'crm.newsletter.subscribers', 'icon' => 'feather-mail', 'url' => $url('/view/all/subscribed/users'), 'active_paths' => $url('/view/all/subscribed/users'), 'show_on_nav' => true, 'childmodule' => []],
                    ['submodule' => 'SMS', 'permission_key' => 'crm.communications.sms', 'icon' => 'feather-message-square', 'url' => $route('bulk-sms-bd.index'), 'active_paths' => $route('bulk-sms-bd.index'), 'show_on_nav' => true, 'childmodule' => []],
                    ['submodule' => 'User Manual', 'permission_key' => 'crm.user-manual', 'icon' => 'feather-book-open', 'url' => $route('crm.user-manual.index'), 'active_paths' => $active($route('crm.user-manual.index'), $url('/crm/user-manual*')), 'show_on_nav' => true, 'childmodule' => []],
                ],
            ],
            [
                'module' => 'USER MANAGEMENT',
                'icon' => 'feather-lock',
                'show_on_nav' => true,
                'submodules' => [
                    ['submodule' => 'System Users', 'icon' => 'feather-user-check', 'url' => $url('/view/system/users'), 'active_paths' => $active($url('/view/system/users'), $url('add/new/system/user'), $url('edit/system/user/*')), 'show_on_nav' => true, 'childmodule' => []],
                    [
                        'submodule' => 'Roles & Permissions',
                        'icon' => 'feather-shield',
                        'show_on_nav' => true,
                        'childmodule' => [
                            ['childmodule' => 'User Roles', 'icon' => 'feather-users', 'url' => $url('/view/user/roles'), 'active_paths' => $active($url('/view/user/roles'), $url('/new/user/role'), $url('/edit/user/role/*')), 'show_on_nav' => true],
                            // ['childmodule' => 'Assign Permissions', 'icon' => 'feather-key', 'url' => $url('/view/user/role/permission'), 'active_paths' => $active($url('/view/user/role/permission'), $url('/assign/role/permission/*')), 'show_on_nav' => true],
                            // ['childmodule' => 'Permission Routes', 'icon' => 'feather-git-merge', 'url' => $url('/view/permission/routes'), 'active_paths' => $url('/view/permission/routes'), 'show_on_nav' => true],
                            ['childmodule' => 'Role Sidebar Permissions', 'icon' => 'feather-git-merge', 'url' => $url('/role-sidebar-permissions'), 'active_paths' => $url('/role-sidebar-permissions'), 'show_on_nav' => true],
                        ],
                    ],
                ],
            ],
            [
                'module' => 'SR MANAGEMENT',
                'icon' => 'feather-target',
                'show_on_nav' => true,
                'submodules' => [
                    ['submodule' => 'Sales Targets', 'icon' => 'feather-target', 'url' => $url('/sales-targets'), 'active_paths' => $active($url('/sales-targets'), $url('sales-targets/*')), 'show_on_nav' => true, 'childmodule' => []],
                    ['submodule' => 'Commission Rules', 'icon' => 'feather-percent', 'url' => $url('/sr-management/commission-rules'), 'active_paths' => $active($url('/sr-management/commission-rules'), $url('/sr-management/commission-rules/*')), 'show_on_nav' => true, 'childmodule' => []],
                    ['submodule' => 'Commission Entries', 'icon' => 'feather-list', 'url' => $url('/sr-management/commission-entries'), 'active_paths' => $active($url('/sr-management/commission-entries'), $url('/sr-management/commission-entries/*')), 'show_on_nav' => true, 'childmodule' => []],
                    ['submodule' => 'Commission Settlement', 'icon' => 'feather-credit-card', 'url' => $url('/sr-management/commission-settlements'), 'active_paths' => $active($url('/sr-management/commission-settlements'), $url('/sr-management/commission-settlements/*')), 'show_on_nav' => true, 'childmodule' => []],
                    ['submodule' => 'Commission Ledger', 'icon' => 'feather-book-open', 'url' => $url('/sr-management/commission-ledger'), 'active_paths' => $active($url('/sr-management/commission-ledger'), $url('/sr-management/commission-ledger/*')), 'show_on_nav' => true, 'childmodule' => []],
                    ['submodule' => 'Affiliate Partners', 'icon' => 'feather-share-2', 'url' => $url('/sr-management/affiliates'), 'active_paths' => $active($url('/sr-management/affiliates'), $url('/sr-management/affiliates/*')), 'show_on_nav' => true, 'childmodule' => []],
                    ['submodule' => 'Commission Report', 'icon' => 'feather-bar-chart-2', 'url' => $url('/sr-management/commission-reports'), 'active_paths' => $active($url('/sr-management/commission-reports'), $url('/sr-management/commission-reports/*')), 'show_on_nav' => true, 'childmodule' => []],
                    ['submodule' => 'Manual', 'icon' => 'feather-help-circle', 'url' => $url('/sr-management/commission-manual'), 'active_paths' => $active($url('/sr-management/commission-manual'), $url('/sr-management/commission-manual/*')), 'show_on_nav' => true, 'childmodule' => []],
                ],
            ],
            [
                'module' => 'HRM MANAGEMENT',
                'icon' => 'feather-users',
                'show_on_nav' => true,
                'submodules' => [
                    [
                        'submodule' => 'Attendance',
                        'icon' => 'feather-users',
                        'show_on_nav' => true,
                        'childmodule' => [
                            ['childmodule' => 'Dashboard', 'url' => $route('hrat.dashboard'), 'active_paths' => $route('hrat.dashboard'), 'show_on_nav' => true],
                            ['childmodule' => 'Departments', 'url' => $route('hrat.departments.index'), 'active_paths' => $route('hrat.departments.index'), 'show_on_nav' => true],
                            ['childmodule' => 'Designations', 'url' => $route('hrat.designations.index'), 'active_paths' => $route('hrat.designations.index'), 'show_on_nav' => true],
                            ['childmodule' => 'Branches', 'url' => $route('hrat.branches.index'), 'active_paths' => $route('hrat.branches.index'), 'show_on_nav' => true],
                            ['childmodule' => 'Shifts', 'url' => $route('hrat.shifts.index'), 'active_paths' => $route('hrat.shifts.index'), 'show_on_nav' => true],
                            ['childmodule' => 'Holiday Calendar', 'url' => $route('hrat.holidays.index'), 'active_paths' => $route('hrat.holidays.index'), 'show_on_nav' => true],
                            ['childmodule' => 'Leave Types', 'url' => $route('hrat.leave-types.index'), 'active_paths' => $route('hrat.leave-types.index'), 'show_on_nav' => true],
                            ['childmodule' => 'Leave Applications', 'url' => $route('hrat.leave-applications.index'), 'active_paths' => $route('hrat.leave-applications.index'), 'show_on_nav' => true],
                            ['childmodule' => 'Employee Profiles', 'url' => $route('hrat.employee-profiles.index'), 'active_paths' => $route('hrat.employee-profiles.index'), 'show_on_nav' => true],
                            ['childmodule' => 'Configuration', 'url' => $route('hrat.configs.index'), 'active_paths' => $route('hrat.configs.index'), 'show_on_nav' => true],
                            ['childmodule' => 'Employee Schedules', 'url' => $route('hrat.employee-schedules.index'), 'active_paths' => $route('hrat.employee-schedules.index'), 'show_on_nav' => true],
                            ['childmodule' => 'Manual Entry', 'url' => $route('hrat.manual-entry.index'), 'active_paths' => $route('hrat.manual-entry.index'), 'show_on_nav' => true],
                            ['childmodule' => 'CSV Import', 'url' => $route('hrat.csv-import.index'), 'active_paths' => $active($route('hrat.csv-import.index'), $route('hrat.import-batches.index')), 'show_on_nav' => true],
                            ['childmodule' => 'Daily Report', 'url' => $route('hrat.reports.daily'), 'active_paths' => $route('hrat.reports.daily'), 'show_on_nav' => true],
                            ['childmodule' => 'Monthly Report', 'url' => $route('hrat.reports.monthly'), 'active_paths' => $route('hrat.reports.monthly'), 'show_on_nav' => true],
                            ['childmodule' => 'Absent Report', 'url' => $route('hrat.reports.absent'), 'active_paths' => $route('hrat.reports.absent'), 'show_on_nav' => true],
                            ['childmodule' => 'Late Report', 'url' => $route('hrat.reports.late'), 'active_paths' => $route('hrat.reports.late'), 'show_on_nav' => true],
                            ['childmodule' => 'Overtime Report', 'url' => $route('hrat.reports.overtime'), 'active_paths' => $route('hrat.reports.overtime'), 'show_on_nav' => true],
                            ['childmodule' => 'Leave Report', 'url' => $route('hrat.reports.leave'), 'active_paths' => $route('hrat.reports.leave'), 'show_on_nav' => true],
                            ['childmodule' => 'Employee Master Report', 'url' => $route('hrat.reports.employee-master'), 'active_paths' => $route('hrat.reports.employee-master'), 'show_on_nav' => true],
                            ['childmodule' => 'Department/Branch Report', 'url' => $route('hrat.reports.department-branch'), 'active_paths' => $route('hrat.reports.department-branch'), 'show_on_nav' => true],
                            ['childmodule' => 'Adjustment History', 'url' => $route('hrat.adjustments.index'), 'active_paths' => $route('hrat.adjustments.index'), 'show_on_nav' => true],
                            ['childmodule' => 'Manual', 'url' => $route('hrat.manual.index'), 'active_paths' => $active($route('hrat.manual.index'), $route('hrat.manual.bn'), $route('hrat.manual.en')), 'show_on_nav' => true],
                        ],
                    ],
                    [
                        'submodule' => 'HR Payroll Setup',
                        'icon' => 'feather-dollar-sign',
                        'show_on_nav' => true,
                        'childmodule' => [
                            ['childmodule' => 'Salary Grades', 'url' => $route('hrat.salary-grades.index'), 'active_paths' => $route('hrat.salary-grades.index'), 'show_on_nav' => true],
                            ['childmodule' => 'Salary Components', 'url' => $route('hrat.salary-components.index'), 'active_paths' => $route('hrat.salary-components.index'), 'show_on_nav' => true],
                            ['childmodule' => 'Salary Assignments', 'url' => $route('hrat.employee-salary-assignments.index'), 'active_paths' => $route('hrat.employee-salary-assignments.index'), 'show_on_nav' => true],
                            ['childmodule' => 'Payrolls', 'url' => $route('hrat.payrolls.index'), 'active_paths' => $route('hrat.payrolls.index'), 'show_on_nav' => true],
                            ['childmodule' => 'Payroll Report', 'url' => $route('hrat.payroll-reports.index'), 'active_paths' => $active($route('hrat.payroll-reports.index'), $route('hrat.payroll-reports.export.csv')), 'show_on_nav' => true],
                            ['childmodule' => 'Payroll Manual', 'url' => $route('hrat.payroll-manual.index'), 'active_paths' => $active($route('hrat.payroll-manual.index'), $route('hrat.payroll-manual.bn'), $route('hrat.payroll-manual.en')), 'show_on_nav' => true],
                        ],
                    ],
                ],
            ],
            [
                'module' => 'WEBSITE & CONTENT',
                'icon' => 'feather-globe',
                'show_on_nav' => true,
                'submodules' => [
                    ['submodule' => 'Breaking News', 'icon' => 'feather-radio', 'url' => $route('ViewAllBreakingNews'), 'active_paths' => $active($route('ViewAllBreakingNews'), $url('/add/new/breaking-news'), $url('/edit/breaking-news/*')), 'show_on_nav' => true, 'childmodule' => []],
                    [
                        'submodule' => 'Blog Management',
                        'icon' => 'feather-edit',
                        'show_on_nav' => true,
                        'childmodule' => [
                            ['childmodule' => 'Write New Blog', 'icon' => 'feather-plus-circle', 'url' => $url('/add/new/blog'), 'active_paths' => $url('/add/new/blog'), 'show_on_nav' => true],
                            ['childmodule' => 'All Blogs', 'icon' => 'feather-list', 'url' => $url('/view/all/blogs'), 'active_paths' => $active($url('/view/all/blogs'), $url('/edit/blog/*')), 'show_on_nav' => true],
                            ['childmodule' => 'Blog Categories', 'icon' => 'feather-grid', 'url' => $url('/blog/categories'), 'active_paths' => $active($url('/blog/categories'), $url('/rearrange/blog/category')), 'show_on_nav' => true],
                        ],
                    ],
                    [
                        'submodule' => 'Pages & Policies',
                        'icon' => 'feather-file-text',
                        'show_on_nav' => true,
                        'childmodule' => [
                            ['childmodule' => 'Custom Pages', 'icon' => 'feather-file-plus', 'url' => $url('/view/all/pages'), 'active_paths' => $active($url('/view/all/pages'), $url('/create/new/page'), $url('edit/custom/page/*')), 'badge' => $count('custom_pages'), 'show_on_nav' => true],
                            ['childmodule' => 'About Us', 'icon' => 'feather-info', 'url' => $route('AboutUsPage'), 'active_paths' => $route('AboutUsPage'), 'show_on_nav' => true],
                            ['childmodule' => 'Terms & Conditions', 'icon' => 'feather-file', 'url' => $url('/terms/and/condition'), 'active_paths' => $url('/terms/and/condition'), 'show_on_nav' => true],
                            ['childmodule' => 'Privacy Policy', 'icon' => 'feather-lock', 'url' => $url('/view/privacy/policy'), 'active_paths' => $url('/view/privacy/policy'), 'show_on_nav' => true],
                            ['childmodule' => 'Shipping Policy', 'icon' => 'feather-truck', 'url' => $url('/view/shipping/policy'), 'active_paths' => $url('/view/shipping/policy'), 'show_on_nav' => true],
                            ['childmodule' => 'Return Policy', 'icon' => 'feather-rotate-ccw', 'url' => $url('/view/return/policy'), 'active_paths' => $url('/view/return/policy'), 'show_on_nav' => true],
                        ],
                    ],
                    [
                        'submodule' => 'Banner Management',
                        'icon' => 'feather-image',
                        'show_on_nav' => true,
                        'childmodule' => [
                            ['childmodule' => 'Sliders', 'icon' => 'feather-sliders', 'url' => $route('ViewAllSliders'), 'active_paths' => $active($route('ViewAllSliders'), $route('AddNewSlider'), $url('/edit/slider/*'), $route('RearrangeSlider')), 'badge' => $count('banners', fn($q) => $q->where('type', 1)->where('status', 1)), 'show_on_nav' => true],
                            ['childmodule' => 'Banners', 'icon' => 'feather-image', 'url' => $route('ViewAllBanners'), 'active_paths' => $active($route('ViewAllBanners'), $route('AddNewBanner'), $url('/edit/banner/*'), $route('RearrangeBanners')), 'badge' => $count('banners', fn($q) => $q->where('type', 2)->where('status', 1)), 'show_on_nav' => true],
                            ['childmodule' => 'Promotional Banners', 'icon' => 'feather-award', 'url' => $route('ViewAllPromotionalBanners'), 'active_paths' => $active($url('/view/all/promotional/banners'), $url('/add/new/promotional/banner'), $url('/edit/promotional/banner/*'), $url('/rearrange/promotional/banners')), 'badge' => $count('promotional_banners', fn($q) => $q->where('status', 1)), 'show_on_nav' => true],
                            ['childmodule' => 'Side Banners', 'icon' => 'feather-sidebar', 'url' => $route('ViewAllSideBanner'), 'active_paths' => $active($route('ViewAllSideBanner'), $route('AddNewSideBanner'), $url('/edit/side-banner/*')), 'badge' => $count('side_banners', fn($q) => $q->where('status', 1)), 'show_on_nav' => false],
                        ],
                    ],
                ],
            ],
            [
                'module' => 'SETTINGS',
                'icon' => 'feather-settings',
                'show_on_nav' => true,
                'submodules' => [
                    ['submodule' => 'General Information', 'icon' => 'feather-info', 'url' => $url('/general/info'), 'active_paths' => $url('/general/info'), 'show_on_nav' => true, 'childmodule' => []],
                    ['submodule' => 'Outlets', 'icon' => 'feather-box', 'url' => $url('/view/all/outlet'), 'active_paths' => $active($url('/view/all/outlet'), $url('/add/new/outlet'), $url('/edit/outlet/*')), 'badge' => $count('outlets'), 'badge_style' => 'color:lightgreen', 'badge_title' => 'Total Outlets', 'show_on_nav' => true, 'childmodule' => []],
                    ['submodule' => 'Checkout Page Config', 'icon' => 'feather-shopping-cart', 'url' => $url('/checkout-config'), 'active_paths' => $url('/checkout-config'), 'show_on_nav' => true, 'childmodule' => []],
                    ['submodule' => 'Template Choice', 'icon' => 'mdi mdi-palette', 'url' => $url('/template-choice'), 'active_paths' => $url('/template-choice'), 'show_on_nav' => true, 'childmodule' => []],
                    ['submodule' => 'Custom CSS & JS', 'icon' => 'feather-code', 'url' => $url('/custom/css/js'), 'active_paths' => $url('/custom/css/js'), 'show_on_nav' => true, 'childmodule' => []],
                    ['submodule' => 'Website Theme Color', 'icon' => 'mdi mdi-format-color-fill', 'icon_style' => 'font-size: 18px', 'url' => $url('/website/theme/page'), 'active_paths' => $url('/website/theme/page'), 'show_on_nav' => true, 'childmodule' => []],
                    ['submodule' => 'Breaking News', 'icon' => 'feather-radio', 'url' => $route('ViewAllBreakingNews'), 'active_paths' => $active($route('ViewAllBreakingNews'), $url('/add/new/breaking-news'), $url('/edit/breaking-news/*')), 'show_on_nav' => true, 'childmodule' => []],
                    ['submodule' => 'Social Media Links', 'icon' => 'feather-share-2', 'url' => $url('/social/media/page'), 'active_paths' => $url('/social/media/page'), 'show_on_nav' => true, 'childmodule' => []],
                    ['submodule' => 'SEO Settings', 'icon' => 'feather-search', 'url' => $url('/seo/homepage'), 'active_paths' => $url('/seo/homepage'), 'show_on_nav' => true, 'childmodule' => []],
                    ['submodule' => 'API & Scripts', 'icon' => 'feather-message-square', 'url' => $url('/social/api-scripts/page'), 'active_paths' => $url('/social/api-scripts/page'), 'show_on_nav' => true, 'childmodule' => []],
                    ['submodule' => 'Email Configure (SMTP)', 'icon' => 'feather-mail', 'url' => $url('/view/email/credential'), 'active_paths' => $url('/view/email/credential'), 'show_on_nav' => true, 'childmodule' => []],
                    ['submodule' => 'SMS Gateways', 'icon' => 'feather-message-square', 'url' => $url('/setup/sms/gateways'), 'active_paths' => $url('/setup/sms/gateways'), 'show_on_nav' => true, 'childmodule' => []],
                    ['submodule' => 'Courier Management', 'icon' => 'feather-truck', 'url' => $url('/courier-management'), 'active_paths' => $url('/courier-management'), 'show_on_nav' => true, 'childmodule' => []],
                    [
                        'submodule' => 'Area Base Courier',
                        'icon' => 'feather-map',
                        'show_on_nav' => true,
                        'childmodule' => [
                            ['childmodule' => 'Area Base Courier Names', 'icon' => 'feather-file-plus', 'url' => $url('area-base-courier-names'), 'active_paths' => $active($url('area-base-courier-names'), $url('area-base-courier-names/*')), 'show_on_nav' => true],
                            ['childmodule' => 'Area Base Courier Charges', 'icon' => 'feather-file-plus', 'url' => $url('area-base-courier-charges'), 'active_paths' => $active($url('area-base-courier-charges'), $url('area-base-courier-charges/create'), $url('area-base-courier-charges/*')), 'show_on_nav' => true],
                        ],
                    ],
                ],
            ],
            [
                'module' => 'FB MARKETING',
                'permission_key' => 'fb_marketing_access',
                'assignable_permission' => true,
                'enforce_permission_read' => true,
                'icon' => 'feather-facebook',
                'show_on_nav' => true,
                'submodules' => [
                    ['submodule' => 'Dashboard', 'permission_key' => 'fb_marketing_dashboard_view', 'icon' => 'feather-pie-chart', 'url' => $route('fbMarketing.dashboard'), 'active_paths' => $active($route('fbMarketing.index'), $route('fbMarketing.dashboard')), 'show_on_nav' => true, 'childmodule' => []],
                    ['submodule' => 'Performance', 'permission_key' => 'fb_marketing_performance_view', 'icon' => 'feather-bar-chart-2', 'url' => $route('fbMarketing.performance.index'), 'active_paths' => $active($route('fbMarketing.performance.index'), $url('/fb-marketing/performance/*')), 'show_on_nav' => true, 'childmodule' => []],
                    ['submodule' => 'Products & Catalog', 'permission_key' => 'fb_marketing_catalog_view', 'icon' => 'feather-shopping-bag', 'url' => $route('fbMarketing.products-catalog.index'), 'active_paths' => $active($route('fbMarketing.products-catalog.index'), $url('/fb-marketing/products-catalog*')), 'show_on_nav' => true, 'childmodule' => []],
                    ['submodule' => 'Product Performance', 'permission_key' => 'fb_marketing_product_performance_view', 'icon' => 'feather-package', 'url' => $route('fbMarketing.product-performance.index'), 'active_paths' => $active($route('fbMarketing.product-performance.index'), $url('/fb-marketing/product-performance*')), 'show_on_nav' => true, 'childmodule' => []],
                    ['submodule' => 'Profitability', 'permission_key' => 'fb_marketing_profitability_view', 'icon' => 'feather-dollar-sign', 'url' => $route('fbMarketing.profitability.index'), 'active_paths' => $active($route('fbMarketing.profitability.index'), $url('/fb-marketing/profitability*')), 'show_on_nav' => true, 'childmodule' => []],
                    ['submodule' => 'Boosting Jobs', 'permission_key' => 'fb_marketing_boosting_jobs_view', 'icon' => 'feather-briefcase', 'url' => $route('fbMarketing.boosting-jobs.index'), 'active_paths' => $active($route('fbMarketing.boosting-jobs.index'), $url('/fb-marketing/boosting-jobs*')), 'show_on_nav' => true, 'childmodule' => []],
                    ['submodule' => 'Audiences', 'permission_key' => 'fb_marketing_audiences_view', 'icon' => 'feather-users', 'url' => $route('fbMarketing.audiences.index'), 'active_paths' => $active($route('fbMarketing.audiences.index'), $url('/fb-marketing/audiences*')), 'show_on_nav' => true, 'childmodule' => []],
                    ['submodule' => 'Campaign Drafts', 'permission_key' => 'fb_marketing_campaign_drafts_view', 'icon' => 'feather-edit-3', 'url' => $route('fbMarketing.campaign-drafts.index'), 'active_paths' => $active($route('fbMarketing.campaign-drafts.index'), $url('/fb-marketing/campaign-drafts*')), 'show_on_nav' => true, 'childmodule' => []],
                    ['submodule' => 'Lead Ads', 'permission_key' => 'fb_marketing_lead_ads_view', 'icon' => 'feather-target', 'url' => $route('fbMarketing.lead-ads.index'), 'active_paths' => $active($route('fbMarketing.lead-ads.index'), $url('/fb-marketing/lead-ads*')), 'show_on_nav' => true, 'childmodule' => []],
                    ['submodule' => 'Health & Alerts', 'permission_key' => 'fb_marketing_alerts_view', 'icon' => 'feather-alert-triangle', 'url' => $route('fbMarketing.alerts.index'), 'active_paths' => $active($route('fbMarketing.alerts.index'), $url('/fb-marketing/alerts*')), 'show_on_nav' => true, 'childmodule' => []],
                    ['submodule' => 'Rules & Recommendations', 'permission_key' => 'fb_marketing_recommendations_view', 'icon' => 'feather-sliders', 'url' => $route('fbMarketing.recommendations.index'), 'active_paths' => $active($route('fbMarketing.recommendations.index'), $url('/fb-marketing/rules-recommendations*')), 'show_on_nav' => true, 'childmodule' => []],
                    ['submodule' => 'Reports', 'permission_key' => 'fb_marketing_reports_view', 'icon' => 'feather-download', 'url' => $route('fbMarketing.reports.index'), 'active_paths' => $active($route('fbMarketing.reports.index'), $url('/fb-marketing/reports*')), 'show_on_nav' => true, 'childmodule' => []],
                    ['submodule' => 'Creative Library', 'permission_key' => 'fb_marketing_creative_library_view', 'icon' => 'feather-image', 'url' => $route('fbMarketing.creative-library.index'), 'active_paths' => $active($route('fbMarketing.creative-library.index'), $url('/fb-marketing/creative-library*')), 'show_on_nav' => true, 'childmodule' => []],
                    ['submodule' => 'Tracking & Attribution', 'permission_key' => 'fb_marketing_tracking_view', 'icon' => 'feather-crosshair', 'url' => $route('fbMarketing.tracking-attribution.index'), 'active_paths' => $active($route('fbMarketing.tracking-attribution.index'), $url('/fb-marketing/tracking-attribution*')), 'show_on_nav' => true, 'childmodule' => []],
                    ['submodule' => 'Attribution Reports', 'permission_key' => 'fb_marketing_attribution_reports_view', 'icon' => 'feather-trending-up', 'url' => $route('fbMarketing.attribution-reports.index'), 'active_paths' => $active($route('fbMarketing.attribution-reports.index'), $url('/fb-marketing/attribution-reports*')), 'show_on_nav' => true, 'childmodule' => []],
                    ['submodule' => 'Setup Wizard', 'permission_key' => 'fb_marketing_setup_wizard_view', 'icon' => 'feather-check-square', 'url' => $route('fbMarketing.configuration.setup-wizard'), 'active_paths' => $route('fbMarketing.configuration.setup-wizard'), 'show_on_nav' => true, 'childmodule' => []],
                    ['submodule' => 'User Manual', 'permission_key' => 'fb_marketing_user_manual_view', 'icon' => 'feather-book-open', 'url' => $route('fbMarketing.user-manual.index'), 'active_paths' => $active($route('fbMarketing.user-manual.index'), $url('/fb-marketing/user-manual*')), 'show_on_nav' => true, 'childmodule' => []],
                    ['submodule' => 'Configuration', 'permission_key' => 'fb_marketing_configuration_view', 'icon' => 'feather-settings', 'url' => $route('fbMarketing.configuration.index'), 'active_paths' => $route('fbMarketing.configuration.index'), 'show_on_nav' => true, 'childmodule' => []],
                ],
            ],
            [
                'module' => 'LOGOUT',
                'icon' => 'feather-log-out',
                'show_on_nav' => true,
                'submodules' => [
                    ['submodule' => 'Logout', 'icon' => 'feather-log-out', 'url' => $route('logout'), 'onclick' => "event.preventDefault(); document.getElementById('logout-form').submit();", 'show_on_nav' => true, 'childmodule' => []],
                ],
            ],
        ];
    }
}

if (!function_exists('backend_sidebar_value')) {
    function backend_sidebar_value($value, $default = '')
    {
        if (is_callable($value)) {
            try {
                return $value();
            } catch (\Throwable $exception) {
                return $default;
            }
        }

        return $value ?? $default;
    }
}

if (!function_exists('backend_sidebar_visible')) {
    function backend_sidebar_visible(array $item): bool
    {
        return ($item['show_on_nav'] ?? true) === true;
    }
}

if (!function_exists('backend_sidebar_children')) {
    function backend_sidebar_children(array $item): array
    {
        $children = $item['childmodule'] ?? [];

        if (is_callable($children)) {
            try {
                $children = $children();
            } catch (\Throwable $exception) {
                return [];
            }
        }

        if ($children instanceof \Illuminate\Support\Collection) {
            $children = $children->toArray();
        }

        if (!is_array($children)) {
            return [];
        }

        return array_values(array_filter($children ?? [], fn($child) => is_array($child) && backend_sidebar_visible($child)));
    }
}

if (!function_exists('assigned_sidebar_modules')) {
    /**
     * Resolve a permission-filtered sidebar for non-admin users.
     *
     * Admin users keep the complete mother sidebar. If role sidebar storage is
     * unavailable for a non-admin request, return an empty sidebar rather than
     * exposing unrestricted navigation or causing a fatal render error.
     */
    function assigned_sidebar_modules(): array
    {
        $user = auth()->user();

        if (!$user) {
            return [];
        }

        try {
            return app(\App\Services\RoleSidebarPermissionService::class)->sidebarForUser($user);
        } catch (\Throwable $exception) {
            \Illuminate\Support\Facades\Log::error('Unable to resolve assigned sidebar modules.', [
                'user_id' => $user->id ?? null,
                'exception' => $exception,
            ]);

            if ((int) ($user->user_type ?? 0) === 1 && function_exists('backend_sidebar_modules')) {
                return backend_sidebar_modules();
            }

            return [];
        }
    }
}

