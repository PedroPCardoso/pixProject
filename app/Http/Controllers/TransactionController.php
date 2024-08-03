<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransactionRequest;
use Carbon\Carbon;

class TransactionController extends Controller
{
    protected $table;
    protected $stats;

    public function __construct()
    {
        $this->table = app('swoole.transations');
        $this->stats = app('swoole.stats');
    }

    public function store(TransactionRequest $request)
    {
        $amount = (float) $request->input('amount');
        $timestamp = Carbon::createFromFormat('Y-m-d\TH:i:s.u\Z', $request->input('timestamp'));
        $now = Carbon::now();

        if ($timestamp->diffInSeconds($now) > 60) {
            return response()->noContent(204);
        }

        if ($timestamp->isFuture()) {
            return response()->json(['error' => 'Timestamp is in the future'], 422);
        }

        $key = $timestamp->timestamp + random_int(0, 1000) . $amount;

        $this->table->set($key, [
            'amount' => $amount,
            'timestamp' => $timestamp->toIso8601String()
        ]);

        $this->updateStatsOnInsert($amount);
        // Save to JSON file
        $this->saveTableToJson();
        $this->saveStatsToJson();

        return response()->noContent(201);
    }

    public function getAllTransactions()
    {
        $transactions = [];
        foreach ($this->table as $key => $row) {
            $transactions[] = [
                'amount' => $row['amount'],
                'timestamp' => $row['timestamp'],
            ];
        }

        return response()->json($transactions);
    }


    public function statistics()
    {
        $stats = $this->stats->get(0);
        $count = $stats['count'];
        $sum = $stats['sum'];
        $avg = $count > 0 ? $sum / $count : 0;
        $max = $count > 0 ? $stats['max'] : 0;
        $min = $count > 0 ? $stats['min'] : 0;

        return response()->json([
            'sum' => number_format($sum, 2, '.', ''),
            'avg' => number_format($avg, 2, '.', ''),
            'max' => number_format($max, 2, '.', ''),
            'min' => number_format($min, 2, '.', ''),
            'count' => $count,
        ]);
    }

    public function deleteAll()
    {
        foreach ($this->table as $key => $row) {
            $this->table->del($key);
        }

        $this->resetStats();
        // Save to JSON file
        $this->saveTableToJson();
        $this->saveStatsToJson();

        return response()->noContent(204);
    }

    protected function updateStatsOnInsert($amount)
    {
        $stats = $this->stats->get(0);

        $newSum = $stats['sum'] + $amount;
        $newCount = $stats['count'] + 1;
        $newMax = max($stats['max'], $amount);
        $newMin = $newCount == 1 ? $amount : min($stats['min'], $amount);

        $this->stats->set(0, [
            'sum' => $newSum,
            'count' => $newCount,
            'max' => $newMax,
            'min' => $newMin,
        ]);
    }

    protected function resetStats()
    {
        $this->stats->set(0, [
            'sum' => 0,
            'count' => 0,
            'max' => 0,
            'min' => PHP_FLOAT_MAX,
        ]);
    }

    protected function saveTableToJson()
    {
        $transactions = [];
        foreach ($this->table as $key => $row) {
            $transactions[] = [
                'amount' => $row['amount'],
                'timestamp' => $row['timestamp'],
            ];
        }

        $jsonFilePath = storage_path('app/transactions.json');
        file_put_contents($jsonFilePath, json_encode($transactions));
    }

    protected function saveStatsToJson()
    {
        $stats = $this->stats->get(0);

        $statsData = [
            'sum' => $stats['sum'],
            'count' => $stats['count'],
            'max' => $stats['max'],
            'min' => $stats['min'],
        ];

        $jsonFilePath = storage_path('app/stats.json');
        file_put_contents($jsonFilePath, json_encode($statsData));
    }

}
