<?php

namespace App\Providers;

use App\Models\Product;
use App\Observers\FbmProductFeedCacheObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        require_once app_path('Http/Helpers/BackendSidebarHelper.php');
        config()->set('backend_sidebar.modules', backend_sidebar_modules());

        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        Product::observe(FbmProductFeedCacheObserver::class);

        Paginator::useBootstrap();
    }
}
