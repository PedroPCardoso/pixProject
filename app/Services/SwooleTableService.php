<?php

namespace App\Services;

use Swoole\Table;

class SwooleTableService
{
    protected $tables = [];

    public function __construct()
    {
        // Inicialize as tabelas aqui ou adicione métodos para adicionar tabelas dinamicamente
        $this->tables['swoole.transactions'] = $this->createTransactionsTable();
        $this->tables['swoole.stats'] = $this->createStatsTable();
    }

    protected function createTransactionsTable()
    {
        $table = new Table(1024);
        $table->column('amount', Table::TYPE_FLOAT);
        $table->column('timestamp', Table::TYPE_STRING, 64);
        $table->create();
        return $table;
    }

    protected function createStatsTable()
    {
        $table = new Table(1);
        $table->column('sum', Table::TYPE_FLOAT);
        $table->column('count', Table::TYPE_INT);
        $table->column('max', Table::TYPE_FLOAT);
        $table->column('min', Table::TYPE_FLOAT);
        $table->create();
        $table->set(0, ['sum' => 0, 'count' => 0, 'max' => 0, 'min' => PHP_FLOAT_MAX]);
        return $table;
    }

    public function getTables()
    {
        return $this->tables;
    }

    public function getTable($name)
    {
        if(!isset($this->tables[$name])) {
            return;
        }
        // if($this->tables[$name]->count() === 0) {
        //     $this->loadDataFromJson();
        // }
        return $this->tables[$name] ?? null;
    }

    public function addTransaction($key, $amount, $timestamp)
    {
        $this->tables['swoole.transactions']->set($key, [
            'amount' => $amount,
            'timestamp' => $timestamp,
        ]);
    }

    public function addStats($amount)
    {
        $stats = $this->tables['swoole.stats']->get(0);
        $newSum = $stats['sum'] + $amount;
        $newCount = $stats['count'] + 1;
        $newMax = max($stats['max'], $amount);
        $newMin = $newCount == 1 ? $amount : min($stats['min'], $amount);

        $this->tables['swoole.stats']->set(0, [
            'sum' => $newSum,
            'count' => $newCount,
            'max' => $newMax,
            'min' => $newMin,
        ]);
    }
    protected function loadDataFromJson()
    {
        $transactionsFilePath = storage_path('app/transactions.json');
        $statsFilePath = storage_path('app/stats.json');
        // Carregar dados das transações
        if (file_exists($transactionsFilePath)) {
            $data = json_decode(file_get_contents($transactionsFilePath), true);

            $table = $this->tables['swoole.transactions'];
            foreach ($data as $transaction) {

                $timestamp = Carbon::parse($transaction['timestamp']);
                $key = $timestamp->timestamp;

                $table->set($key, [
                    'amount' => $transaction['amount'],
                    'timestamp' => $timestamp->toIso8601String()
                ]);
            }
        }

        // Carregar dados das estatísticas
        if (file_exists($statsFilePath)) {
            $statsData = json_decode(file_get_contents($statsFilePath), true);
            $stats = $this->tables['swoole.stats'];

            $stats->set(0, [
                'sum' => $statsData['sum'],
                'count' => $statsData['count'],
                'max' => $statsData['max'],
                'min' => $statsData['min'],
            ]);
        }
    }
}
