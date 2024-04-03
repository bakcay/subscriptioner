<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ZotloServiceProvider extends ServiceProvider {
    /**
     * Register services.
     */
    public function register(): void {
        $this->app->singleton(ZotloService::class, function ($app) {
            return new ZotloService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void {
        //
    }
}
