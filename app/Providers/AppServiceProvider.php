<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Swoole\Table;
use Carbon\Carbon;
use App\Services\SwooleTableService;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Registra o serviço SwooleTableService
        $this->app->singleton(SwooleTableService::class, function ($app) {
            return new SwooleTableService();
        });

        // Registra as tabelas Swoole diretamente no container para o acesso via app()
        $this->app->singleton('swoole.transactions', function () {
            return app(SwooleTableService::class)->getTable('swoole.transactions');
        });

        $this->app->singleton('swoole.stats', function () {
            return app(SwooleTableService::class)->getTable('swoole.stats');
        });

    }

    public function boot()
    {
        app('swoole.stats');
        app('swoole.transactions');
    }


}
