<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use App\Http\Requests\TransactionRequest;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Jobs\DeleteAllTransactionsJob;
use Illuminate\Http\JsonResponse;

class TransactionController extends Controller
{
    const MAX_CACHE_TIME_IN_SECONDS = 60;

    public function store(TransactionRequest $request): JsonResponse
    {
        // Valida o JSON recebido
        if (!$request->isJson()) {
            return response()->json(['error' => 'Invalid JSON'], 400);
        }

        try {
            $transactionId = Str::uuid()->toString();
            $transactionKey = 'transactions_' . $transactionId;

            $amount = (float) $request->input('amount');
            $date = Carbon::createFromFormat('Y-m-d\TH:i:s.v\Z', $request->input('timestamp'));
            $now = Carbon::now();

            if ($date->diffInSeconds($now) > self::MAX_CACHE_TIME_IN_SECONDS) {
                return response()->noContent(204);
            }

            if ($date->isFuture()) {
                return response()->json(['error' => 'Timestamp is in the future'], 422);
            }

            $transaction = [
                'timestamp' => $date->format('d/m/Y H:i:s'),
                'amount' => $amount,
            ];
            $expiration = $date->addSeconds(self::MAX_CACHE_TIME_IN_SECONDS);

            Cache::store('file')->put($transactionKey, $transaction, $expiration);
            $this->updateTransactionIndex($transactionId);

            return response()->json(['message' => 'Transaction stored successfully.', 'transaction_id' => $transactionId], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid input data'], 422);
        }
    }

    /**
     * Recupera todas as transações do cache usando o índice.
     */
    public function getAllTransactions()
    {
        $transactionIds = Cache::store('file')->get('transaction_ids', []);

        $transactions = [];
        foreach ($transactionIds as $transactionId) {
            $transactions[$transactionId] = Cache::store('file')->get('transactions_' . $transactionId);
        }

        return response()->json($transactions);
    }

    /**
     * Atualiza o índice de transações.
     */
    protected function updateTransactionIndex($transactionId)
    {
        $transactionIds = Cache::store('file')->get('transaction_ids', []);
        if (!in_array($transactionId, $transactionIds)) {
            $transactionIds[] = $transactionId;
            Cache::store('file')->put('transaction_ids', $transactionIds);
        }
    }

    public function stats(){
        return response()->json(Cache::store('file')->get('stats', []));
    }

    /**
     * Remove uma transação do cache usando o driver Octane.
     */
    public function deleteTransaction()
    {
        DeleteAllTransactionsJob::dispatch();

        return response()->json(['message' => 'Deletion of all transactions has been queued.']);
    }

}
