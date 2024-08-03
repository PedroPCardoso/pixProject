<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Swoole\Table;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('swoole.transations', function () {
            $table = new Table(1024);
            $table->column('amount', Table::TYPE_FLOAT);
            $table->column('timestamp', Table::TYPE_STRING, 64);
            $table->create();

            return $table;
        });

        $this->app->singleton('swoole.stats', function () {
            $table = new Table(1);
            $table->column('sum', Table::TYPE_FLOAT);
            $table->column('count', Table::TYPE_INT);
            $table->column('max', Table::TYPE_FLOAT);
            $table->column('min', Table::TYPE_FLOAT);
            $table->create();
            $table->set(0, ['sum' => 0, 'count' => 0, 'max' => 0, 'min' => PHP_FLOAT_MAX]);

            return $table;
        });

        $this->loadDataFromJson();
    }

    public function boot()
    {
        app('swoole.stats');
        app('swoole.transations');
    }

    protected function loadDataFromJson()
    {
        $transactionsFilePath = storage_path('app/transactions.json');
        $statsFilePath = storage_path('app/stats.json');

        // Carregar dados das transações
        if (file_exists($transactionsFilePath)) {
            $data = json_decode(file_get_contents($transactionsFilePath), true);

            foreach ($data as $transaction) {
                $timestamp = Carbon::createFromFormat('Y-m-d\TH:i:s.u\Z', $transaction['timestamp']);
                $key = $timestamp->timestamp;

                $this->app->make('swoole.table')->set($key, [
                    'amount' => $transaction['amount'],
                    'timestamp' => $timestamp->toIso8601String()
                ]);

                // Atualizar estatísticas
                $this->updateStatsOnInsert($transaction['amount']);
            }
        }

        // Carregar dados das estatísticas
        if (file_exists($statsFilePath)) {
            $statsData = json_decode(file_get_contents($statsFilePath), true);

            $this->app->make('swoole.stats')->set(0, [
                'sum' => $statsData['sum'],
                'count' => $statsData['count'],
                'max' => $statsData['max'],
                'min' => $statsData['min'],
            ]);
        }
    }
}
