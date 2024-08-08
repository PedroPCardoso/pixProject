<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class UpdateTransactions extends Command
{
    const MAX_CACHE_TIME_IN_SECONDS = 60; 
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-transactions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update transactions and statistics based on the last 60 seconds';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();
        $sum = 0;
        $count = 0;
        $max = PHP_FLOAT_MIN;
        $min = PHP_FLOAT_MAX;

        // Recupera o índice de transações
        $transactionIds = Cache::store('file')->get('transaction_ids', []);

        if (empty($transactionIds)) {
           
            $this->info('No transactions found.');
            return;
        }

        $validTransactions = [];
        $transaction = Cache::store('file')->get('transactions_' . $transactionIds[0]);

        foreach ($transactionIds as $transactionId) {
            $transaction = Cache::store('file')->get('transactions_' . $transactionId);
          
            // Remover transações com valor null ou timestamp ausente
            if (is_null($transaction) || !isset($transaction['timestamp'], $transaction['amount'])) {
                Cache::store('file')->forget('transactions_' . $transactionId);
                continue;
            }
            
            $timestamp = Carbon::parse($transaction['timestamp'])->setTimezone(env('APP_TIMEZONE', 'UTC'));
            var_dump($timestamp->toDateTimeString());
            var_dump($now->toDateTimeString());
            var_dump($timestamp->diffInSeconds($now));

            // Remove transações mais antigas que 60 segundos ou com timestamp futuro
            if ($timestamp->diffInSeconds($now) > self::MAX_CACHE_TIME_IN_SECONDS || $timestamp->isFuture()) {
                Cache::store('file')->forget('transactions_' . $transactionId);
                continue;
            }

            $amount = $transaction['amount'];
            $sum += $amount;
            $count++;
            $max = max($max, $amount);
            $min = min($min, $amount);

            $validTransactions[$transactionId] = $transaction;

        }

        // // Calcula a média
        $avg = $count > 0 ? $sum / $count : 0;

        // // Atualiza as estatísticas no cache
        $this->saveStatsToJson($sum, $avg, $max, $min, $count);

        $this->info('Transactions and statistics updated successfully.');
    }

    /**
     * Atualiza as estatísticas no cache e salva em um arquivo JSON.
     *
     * @param float $sum
     * @param float $avg
     * @param float $max
     * @param float $min
     * @param int $count
     */
    protected function saveStatsToJson($sum, $avg, $max, $min, $count)
    {
        $stats = [
            'sum' => number_format($sum, 2, '.', ''),
            'avg' => number_format($avg, 2, '.', ''),
            'max' => number_format($max, 2, '.', ''),
            'min' => number_format($min, 2, '.', ''),
            'count' => $count
        ];

        Cache::store('file')->put('stats', $stats);
    }

    /**
     * Atualiza o cache e salva as transações válidas em um arquivo JSON.
     *
     * @param array $transactions
     */
    protected function saveTableToJson($transactions)
    {
        // Atualizar o cache com as transações válidas
        foreach ($transactions as $transactionId => $transaction) {
            Cache::store('file')->put('transactions_' . $transactionId, $transaction);
        }

        // Atualizar o índice de transações com os IDs das transações válidas
        $validTransactionIds = array_keys($transactions);
        Cache::store('file')->put('transaction_ids', $validTransactionIds);
    }

}
