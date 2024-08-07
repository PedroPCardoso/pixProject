<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransactionRequest;
use Carbon\Carbon;
use App\Services\SwooleTableService;
use Illuminate\Support\Facades\Cache;


class TransactionController extends Controller
{
    protected $table;
    protected $stats;
    protected $service;

    public function __construct(SwooleTableService $service)
    {
        $this->service = $service;
        $this->table = $service->getTable('swoole.transactions');
        $this->stats = $service->getTable('swoole.stats');
    }

    public function store(TransactionRequest $request)
    {
        $amount = (float) $request->input('amount');
        $timestamp = Carbon::createFromFormat('Y-m-d\TH:i:s.u\Z', $request->input('timestamp'));
        $now = Carbon::now();
        Cache::store('octane')->put('framework', 'Laravel', 30);
        if ($timestamp->diffInSeconds($now) > 60) {
            return response()->noContent(204);
        }

        if ($timestamp->isFuture()) {
            return response()->json(['error' => 'Timestamp is in the future'], 422);
        }

        $key = $timestamp->timestamp + random_int(0, 1000) . $amount;

        $this->service->addTransaction($key, $amount, $timestamp->timestamp);

        $this->service->addStats($amount);
        // $this->saveTableToJson();
        return response()->noContent(201);
    }

    public function getAllTransactions()
    {
        $this->table = $this->service->getTable('swoole.transactions');
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
        $this->stats = $this->service->getTable('swoole.stats');
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

        return response()->noContent(204);
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
        $this->table = $this->service->getTable('swoole.transactions');
        // $transactions = [];
        // foreach ($this->table as $key => $row) {
        //     $transactions[] = [
        //         'amount' => $row['amount'],
        //         'timestamp' => $row['timestamp'],
        //     ];
        // }

        $jsonFilePath = storage_path('app/transactions.json');
        file_put_contents($jsonFilePath, json_encode( array_values($this->table)));
    }


}
