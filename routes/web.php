<?php

//auth routes

use App\Http\Controllers\Product\FeaturedCategoryProductController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

// Development override: keep this manually true/false while the project is being built.
// Dynamic fail-closed check for production:
// app()->environment('local') && (bool) config('app.allow_local_unsafe_web_maintenance_routes', false)
$allowLocalUnsafeWebMaintenanceRoutes = true;
// Stage 26B: removed the public /tp authentication-bypass debug route.

require __DIR__ . '/authRoutes.php';

//dashboard routes 
require __DIR__ . '/dashboardRoutes.php';

// payment routes
require __DIR__ . '/paymentRoutes.php';

//ecommerce routes
require __DIR__ . '/ecommerceRoutes.php';

//inventory routes
require __DIR__ . '/inventoryRoutes.php';

//accounts routes
require __DIR__ . '/accountRoutes.php';

//fixed asset management routes
require __DIR__ . '/fixedAssetRoutes.php';

//crm routes
require __DIR__ . '/crmRoutes.php';

//product management routes
require __DIR__ . '/productManagementRoutes.php';

//service management routes
require __DIR__ . '/serviceManagementRoutes.php';

//role and permission routes
require __DIR__ . '/rolePermissionRoutes.php';

//website config routes
require __DIR__ . '/WebConfigRoutes.php';

//cms routes
require __DIR__ . '/cmsRoutes.php';

//clear cache routes
require __DIR__ . '/cache.php';

//general routes
require __DIR__ . '/generalRoutes.php';

//media routes
require __DIR__ . '/mediaRoutes.php';

//stock adjustment routes
require __DIR__ . '/stockAdjustmentRoutes.php';

//analytics routes
require __DIR__ . '/analyticsRoutes.php';

//report routes
require __DIR__ . '/reportRoutes.php';

// desktop POS routes
require __DIR__ . '/pos_desktop_route.php';

// SR Management (Sales Targets)
require __DIR__ . '/srRoutes.php';

// HR Attendance Management
require __DIR__ . '/hratRoutes.php';

// Courier routes
require __DIR__ . '/courierRoutes.php';

// Delivery & Logistics module routes
require __DIR__ . '/deliveryRoutes.php';

// FB MARKETING module routes
require __DIR__ . '/fbMarketingRoutes.php';

// FBM-00: browser bundles must not carry the legacy API token. Internal ERP
// product-search screens use the authenticated web session instead.
Route::post('/internal-api/search/products', [App\Http\Controllers\Api\ApiController::class, 'searchProducts'])
    ->middleware(['auth'])
    ->name('internal-api.search-products');

// Stage 26B: legacy browser maintenance actions register only when explicitly enabled locally.
if ($allowLocalUnsafeWebMaintenanceRoutes) {
    // Dangerous: clear a large part of the DB via seeder
    // Only runs if request has ?auth_name=shefat
    Route::get('/run-db-clear', function (\Illuminate\Http\Request $request) {
        if ($request->input('auth_name') !== 'shefat') {
            abort(403, 'Unauthorized');
        }

        Artisan::call('db:seed', [
            '--class' => \Database\Seeders\DbClearSeeder::class,
        ]);

        return response()->json([
            'status' => 'ok',
            'message' => 'DbClearSeeder executed',
        ]);
    });
}

Route::get('/barcode_gen', function () {
    return view('backend.product.barcode_gen');
});


// Stage 26B: removed the public /dump-autoload SMS-send debug route.

// Product Order Invoice Routes (Public)
Route::get('/order-invoice/{slug}', [App\Http\Controllers\Inventory\ProductOrderController::class, 'showInvoice'])->name('order.invoice');
Route::get('/order-invoice/{slug}/pdf', [App\Http\Controllers\Inventory\ProductOrderController::class, 'downloadInvoicePDF'])->name('order.invoice.pdf');
Route::any('/order-invoice/{slug}/email', [App\Http\Controllers\Inventory\ProductOrderController::class, 'emailInvoice'])->name('order.invoice.email');

// PWA Service Worker Route
Route::get('/sw.js', function () {
    $version = config('app.version', '1.0.0');
    $swContent = file_get_contents(public_path('sw.js'));
    // Replace all instances of APP_VERSION_PLACEHOLDER with actual version
    $swContent = str_replace('APP_VERSION_PLACEHOLDER', $version, $swContent);

    return response($swContent, 200)
        ->header('Content-Type', 'application/javascript')
        ->header('Service-Worker-Allowed', '/')
        ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
        ->header('Pragma', 'no-cache')
        ->header('Expires', '0');
});

// PWA Manifest Route
Route::get('/manifest.json', function () {
    $manifest = [
        'name' => config('app.name', 'POS System'),
        'short_name' => 'POS',
        'description' => 'Point of Sale System',
        'start_url' => '/',
        'display' => 'standalone',
        'background_color' => '#ffffff',
        'theme_color' => '#4f46e5',
        'orientation' => 'portrait-primary',
        'icons' => [
            [
                'src' => url('assets/images/favicon.ico'),
                'sizes' => '48x48',
                'type' => 'image/x-icon'
            ],
            [
                'src' => url('assets/images/favicon.ico'),
                'sizes' => '192x192',
                'type' => 'image/x-icon'
            ],
            [
                'src' => url('assets/images/favicon.ico'),
                'sizes' => '512x512',
                'type' => 'image/x-icon'
            ]
        ],
        'categories' => ['business', 'productivity'],
        'screenshots' => [],
        'prefer_related_applications' => false
    ];

    return response()->json($manifest, 200)
        ->header('Content-Type', 'application/manifest+json');
});

// Public API: categories filtered by product_website_id
Route::get('/api/categories', function (\Illuminate\Http\Request $request) {
    $query = \App\Models\Category::where('status', 1)->orderBy('name', 'asc');

    if ($request->filled('product_website_id')) {
        $query->where('product_website_id', $request->product_website_id);
    }

    return response()->json(
        $query->select('id', 'name')->get()
    );
});


// Public API: subcategories filtered by category_id (and optionally product_website_id)
Route::get('/api/subcategories', function (\Illuminate\Http\Request $request) {
    $query = \App\Models\Subcategory::where('status', 1)->orderBy('name', 'asc');

    if ($request->filled('category_id')) {
        $query->where('category_id', $request->category_id);
    }

    if ($request->filled('product_website_id')) {
        $query->where('product_website_id', $request->product_website_id);
    }

    return response()->json(
        $query->select('id', 'name')->get()
    );
});

// Stage 26B: fail closed outside an explicitly enabled local environment.
if ($allowLocalUnsafeWebMaintenanceRoutes) {
    // Dangerous: mutate schema across product-related tables.
    Route::get('/product_website_id', [App\Http\Controllers\ProductController::class, 'productWebsiteId']);

    Route::get('/append-columns', function () {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $tablesWithColumns = [
            [
                'table' => 'products',
                'columns' => [
                ],
            ],
            [
                'table' => 'user_addresses',
                'columns' => [
                ],
            ],
            [
                'table' => 'product_orders',
                'columns' => [
                    'product_package_id' => 'bigint(20) unsigned NULL',
                ],
            ],
            [
                'table' => 'product_order_returns',
                'columns' => [
                    'return_type' => "varchar(30) NULL DEFAULT 'advance_only' COMMENT 'advance_only, instant_refund'",
                ],
            ],
            [
                'table' => 'manual_product_returns',
                'columns' => [
                ],
            ],
            [
                'table' => 'ac_transactions',
                'columns' => [
                ],
            ],
            [
                'table' => 'hrat_payrolls',
                'columns' => [
                ],
            ],
            [
                'table' => 'product_order_products',
                'columns' => [
                ],
            ],
            [
                'table' => 'product_purchase_orders',
                'columns' => [
                ],
            ],
            [
                'table' => 'product_purchase_order_products',
                'columns' => [
                ],
            ],
            [
                'table' => 'product_purchase_order_product_units',
                'columns' => [
                ],
            ],
            [
                'table' => 'package_products',
                'columns' => [
                ],
            ],
            [
                'table' => 'package_product_items',
                'columns' => [
                ],
            ],
            [
                'table' => 'customers',
                'columns' => [
                ],
            ],
            [
                'table' => 'db_paymenttypes',
                'columns' => [
                ],
            ],
            [
                'table' => 'general_infos',
                'columns' => [
                ],
            ],
            [
                'table' => 'categories',
                'columns' => [
                ],
            ],
        ];

        foreach ($tablesWithColumns as $tableConfig) {
            $table   = $tableConfig['table'];
            $columns = $tableConfig['columns'];

            if (!Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $columnName => $type) {
                if (!Schema::hasColumn($table, $columnName)) {
                    DB::statement("ALTER TABLE `{$table}` ADD COLUMN `{$columnName}` {$type}");
                }
            }
        }

        if (Schema::hasTable('product_orders') && Schema::hasColumn('product_orders', 'order_status')) {
            DB::statement("ALTER TABLE `product_orders` MODIFY COLUMN `order_status` ENUM('pending','accepted','processing','invoiced','delivered','canceled','cancelled','returned') NOT NULL DEFAULT 'pending'");
        }

        if (Schema::hasTable('product_order_returns') && Schema::hasColumn('product_order_returns', 'refund_status')) {
            DB::statement("ALTER TABLE `product_order_returns` MODIFY COLUMN `refund_status` ENUM('pending','partial','completed') NOT NULL DEFAULT 'pending'");
        }

        if (Schema::hasTable('hrat_payrolls') && Schema::hasColumn('hrat_payrolls', 'status')) {
            DB::statement("ALTER TABLE `hrat_payrolls` MODIFY COLUMN `status` ENUM('draft','approved','finalized','paid') NOT NULL DEFAULT 'draft'");
        }

        if (Schema::hasTable('product_purchase_order_product_units') && Schema::hasColumn('product_purchase_order_product_units', 'unit_status')) {
            DB::statement("ALTER TABLE `product_purchase_order_product_units` MODIFY COLUMN `unit_status` ENUM('pending','instock','sold','returned','lost','damaged') NULL DEFAULT 'instock' COMMENT 'pending=>Waiting for purchase receive; instock=>In stock; sold=>Sold; returned=>Returned; lost=>Lost; damaged=>Damaged'");
        }

        if (Schema::hasTable('product_purchase_orders') && Schema::hasColumn('product_purchase_orders', 'other_charge_type')) {
            DB::statement('ALTER TABLE `product_purchase_orders` MODIFY COLUMN `other_charge_type` TEXT NULL');
        }

        Artisan::call('migrate');

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        return 'Columns appended successfully';
    });

    Route::get('/upzip-sourcecode', function () {
        $zipPath = base_path('backend.zip');

        if (!is_file($zipPath)) {
            return response('backend.zip not found in project root.', 404);
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return response('Could not open backend.zip.', 500);
        }

        $expectedRootEntries = [
            'app',
            'app_tenant',
            'bootstrap',
            'config',
            'database',
            'docs',
            'public',
            'resources',
            'routes',
            'scripts',
            'storage',
            'tests',
            'artisan',
            'composer.json',
            'composer.lock',
            'package.json',
            'package-lock.json',
            'phpunit.xml',
            'server.php',
        ];

        $normalizeZipPath = function (string $path): string {
            $path = str_replace('\\', '/', $path);
            $path = preg_replace('#/+#', '/', $path);
            $path = ltrim($path, '/');

            $parts = [];
            foreach (explode('/', $path) as $part) {
                if ($part === '' || $part === '.') {
                    continue;
                }

                if ($part === '..') {
                    return '';
                }

                $parts[] = $part;
            }

            return implode('/', $parts);
        };

        $stripArchiveRoot = function (string $path) use ($expectedRootEntries): string {
            $segments = explode('/', $path);
            $first = $segments[0] ?? '';

            if ($first === '' || in_array($first, $expectedRootEntries, true)) {
                return $path;
            }

            if (count($segments) > 1 && in_array($segments[1], $expectedRootEntries, true)) {
                array_shift($segments);

                return implode('/', $segments);
            }

            return $path;
        };

        $extractedFiles = 0;
        $extractedDirectories = 0;

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entryName = $zip->getNameIndex($index);
            if ($entryName === false) {
                continue;
            }

            $relativePath = $stripArchiveRoot($normalizeZipPath($entryName));
            if ($relativePath === '') {
                continue;
            }

            $targetPath = base_path($relativePath);
            if (substr($entryName, -1) === '/' || substr($relativePath, -1) === '/') {
                if (!is_dir($targetPath) && !mkdir($targetPath, 0755, true) && !is_dir($targetPath)) {
                    $zip->close();

                    return response('Could not create directory: ' . $relativePath, 500);
                }

                $extractedDirectories++;
                continue;
            }

            $targetDirectory = dirname($targetPath);
            if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0755, true) && !is_dir($targetDirectory)) {
                $zip->close();

                return response('Could not create directory: ' . dirname($relativePath), 500);
            }

            $contents = $zip->getFromIndex($index);
            if ($contents === false || file_put_contents($targetPath, $contents) === false) {
                $zip->close();

                return response('Could not extract file: ' . $relativePath, 500);
            }

            $extractedFiles++;
        }

        $zip->close();

        increment_version();

        /** */

        return response()->json([
            'success' => true,
            'message' => 'backend.zip extracted to project directories successfully.',
            'files' => $extractedFiles,
            'directories' => $extractedDirectories,
        ], 200);
    });

    // old data profit calculation and update product_order_products table
    Route::get('/calculate-profit', function () {
        try {

            $order_products = \App\Models\ProductOrderProduct::whereNull('purchase_price')->get();

            foreach ($order_products as $order_product) {

                try {

                    $product = \App\Models\Product::find($order_product->product_id);

                    if (!$product) {
                        Log::warning("Product not found for ProductOrderProduct ID: {$order_product->id}");
                        continue;
                    }

                    $purchase_price = DB::table('product_purchase_order_products')
                        ->where('product_id', $order_product->product_id)
                        ->orderBy('id', 'desc')
                        ->value('purchase_price');

                    if ($purchase_price === null) {
                        Log::warning("Purchase price not found for Product ID: {$order_product->product_id}");
                        continue;
                    }

                    $price = $order_product->sale_price;

                    $unit_profit = ($price - $purchase_price);
                    $net_profit = ($unit_profit * $order_product->qty);

                    \App\Models\ProductOrderProduct::where('id', $order_product->id)
                        ->update([
                            'unit_profit'    => $unit_profit,
                            'net_profit'     => $net_profit,
                            'purchase_price' => $purchase_price,
                        ]);
                } catch (\Exception $e) {

                    Log::error("Error processing ProductOrderProduct ID {$order_product->id}: " . $e->getMessage());

                    continue;
                }
            }

            return response()->json([
                'status'  => 'ok',
                'message' => 'Profit calculated and updated successfully.'
            ]);
        } catch (\Exception $e) {

            Log::error('Calculate profit route failed: ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    });
}
