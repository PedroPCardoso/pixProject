<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;
use Carbon\Carbon;
use Illuminate\Support\Str;

class TransactionStressTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Configura o driver de cache para array durante os testes
        Cache::store('file')->flush();
    }

    /**
     * Teste de transações simultâneas.
     */
    public function testSimultaneousTransactions()
    {
        $transactionData = [];

        for ($i = 0; $i < 1000; $i++) { // Simula 1000 transações simultâneas
            $transactionData[] = [
                'amount' => rand(100, 10000) / 100,
                'timestamp' => Carbon::now()->subSeconds(rand(1, 30))->format('Y-m-d\TH:i:s.v\Z')
            ];
        }

        // Dispatch multiple transactions simultaneously
        $responses = [];
        foreach ($transactionData as $data) {
            $responses[] = $this->postJson('/api/transactions', $data);
        }
        // Assegura que todas as transações foram processadas corretamente
        foreach ($responses as $response) {
            $response->assertStatus(200);
            $response->assertJsonStructure(['message', 'transaction_id']);
        }

        // Verifica se todas as transações estão armazenadas corretamente no cache
        $transactionIds = Cache::store('file')->get('transaction_ids', []);
        $this->assertCount(1000, $transactionIds);
    }

    /**
     * Teste de validação de estatísticas geradas por um comando em background.
     */
    public function testStatisticsValidation()
    {
        // Insere algumas transações para serem analisadas pelo comando
        $transactionAmounts = [];
        
        for ($i = 0; $i < 10; $i++) {
            $amount = rand(100, 10000) / 100;
            $transactionAmounts[] = $amount;
            
            $data = [
                'amount' => $amount,
                'timestamp' => Carbon::now()->subSeconds(rand(1, 60))->format('Y-m-d\TH:i:s.v\Z')
            ];

            $response = $this->postJson('/api/transactions', $data);
            $response->assertStatus(200);
        }

        // Simula a execução do comando de estatísticas
        Bus::fake(); // Garante que o job não seja executado imediatamente

        $this->artisan('app:update-transactions')
            ->assertExitCode(0);

        // Valida as estatísticas geradas
        $stats = Cache::store('file')->get('stats', []);

        // Calcula os valores esperados
        $expectedSum = array_sum($transactionAmounts);
        $expectedAvg = $expectedSum / count($transactionAmounts);
        $expectedMax = max($transactionAmounts);
        $expectedMin = min($transactionAmounts);
        $expectedCount = count($transactionAmounts);

        // Verifica as estatísticas geradas
        $this->assertNotEmpty($stats);
        $this->assertEquals(number_format($expectedSum, 2, '.', ''), $stats['sum']);
        $this->assertEquals(number_format($expectedAvg, 2, '.', ''), $stats['avg']);
        $this->assertEquals(number_format($expectedMax, 2, '.', ''), $stats['max']);
        $this->assertEquals(number_format($expectedMin, 2, '.', ''), $stats['min']);
        $this->assertEquals($expectedCount, $stats['count']);
    }
}
