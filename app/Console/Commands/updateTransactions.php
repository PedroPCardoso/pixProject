<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;

class UpdateTransactions extends Command
{
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

    protected $table;
    protected $stats;

    public function __construct()
    {
        parent::__construct();
        $this->table = app('swoole.transations'); // Acesso à tabela de transações
        $this->stats = app('swoole.stats'); // Acesso 
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


        foreach ($this->table as $key => $row) {
            $timestamp = Carbon::parse($row['timestamp']);
            $now = Carbon::now();

            if ($timestamp->diffInSeconds($now) > 60) {
                $this->table->del($key);
                break;
            }

            if ($timestamp->isFuture()) {
                $this->table->del($key);
                break;
            }
            $amount = $row['amount'];
            $sum += $amount;
            $count++;
            $max = max($max, $amount);
            $min = min($min, $amount);
        }

        // Calcule a média
        $avg = $count > 0 ? $sum / $count : 0;

        // Atualize as estatísticas
        $this->saveStatsToJson($sum, $avg, $max, $min, $count);
        $this->saveTableToJson($sum, $avg, $max, $min, $count);

        $this->info('Transactions and statistics updated successfully.');
    }

    /**
     * Atualiza a tabela de estatísticas com os novos valores.
     *
     * @param float $sum
     * @param float $avg
     * @param float $max
     * @param float $min
     * @param int $count
     */
    protected function saveStatsToJson($sum, $avg, $max, $min, $count)
    {
        $this->stats->set(0, [
            'sum' => number_format($sum, 2, '.', ''),
            'avg' => number_format($avg, 2, '.', ''),
            'max' => number_format($max, 2, '.', ''),
            'min' => number_format($min, 2, '.', ''),
            'count' => $count
        ]);

        // Salvar no arquivo JSON
        $statsFilePath = storage_path('app/stats.json');
        file_put_contents($statsFilePath, json_encode([
            'sum' => number_format($sum, 2, '.', ''),
            'avg' => number_format($avg, 2, '.', ''),
            'max' => number_format($max, 2, '.', ''),
            'min' => number_format($min, 2, '.', ''),
            'count' => $count
        ]));
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
}
