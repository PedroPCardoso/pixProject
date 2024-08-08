<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeleteAllTransactionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Cria uma nova instância do job.
     */
    public function __construct()
    {
    }

    /**
     * Executa o job.
     */
    public function handle()
    {
        // Recupera o índice de transações
        $transactionIds = Cache::store('file')->get('transaction_ids', []);

        // Remove todas as transações com base no índice
        foreach ($transactionIds as $transactionId) {
            Cache::store('file')->forget('transactions_' . $transactionId);
        }

        // Remove o índice de transações
        Cache::store('file')->forget('transaction_ids');
    }
}
