<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\SwooleTableService;

class SwooleTableServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(SwooleTableService::class, function ($app) {
                    return new SwooleTableService();
                });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        app('swoole.transactions');
        app('swoole.stats');
    }
}
