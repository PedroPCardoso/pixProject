<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\SwooleTableService;

class Processtransactions implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    protected $table;
    protected $stats;
    protected $service;

    public function __construct()
    {      
    }

    /**
     * Execute the job.
     */
    public function handle(SwooleTableService $service): void
    {
        $this->table = $service->getTable('swoole.transactions');
        $this->stats = $service->getTable('swoole.stats');
        var_dump($this->table->count());
        var_dump($this->stats->count());
    }
}
