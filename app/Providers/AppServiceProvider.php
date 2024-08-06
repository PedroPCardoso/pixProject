<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\SwooleTableService;
use Swoole\Table;
use Carbon\Carbon;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Registra o serviço SwooleTableService
        $this->app->singleton(SwooleTableService::class, function ($app) {
            return new SwooleTableService();
        });


    }

    public function boot()
    {
        app(SwooleTableService::class);
    }


}
