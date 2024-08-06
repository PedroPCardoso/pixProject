<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Services\SwooleTableService;
use Laravel\Octane\Facades\Octane;
use Illuminate\Support\Facades\Cache;



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
    protected $service;

    public function __construct(SwooleTableService $swooleTableService)
    {
        parent::__construct();
        $this->service = $swooleTableService;
    }   

    /**
     * Execute the console command.
     */
    public function handle(SwooleTableService $swooleTableService)
    {
        $this->stats = $this->service->getTable('swoole.stats');
        $this->table = $this->service->getTable('swoole.transactions');
        // Cache::store('octane')->get();

        $sum = 0;
        $count = 0;
        $max = 0;
        $min = 0;


        foreach ($this->table as $key => $row) {
            $timestamp = Carbon::parse($row['timestamp']);
            $now = Carbon::now();

            if ($timestamp->diffInSeconds($now) > 60) {
                // $this->table->del($key);
                break;
            }

            if ($timestamp->isFuture()) {
                // $this->table->del($key);
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
        // $this->saveStatsToJson($sum, $avg, $max, $min, $count);
        // $this->saveTableToJson($sum, $avg, $max, $min, $count);

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
        file_put_contents($jsonFilePath, json_encode($this->table));
    }
}
