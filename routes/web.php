<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TransactionController;

Route::post('/transactions', [TransactionController::class, 'store']);
Route::get('/statistics', [TransactionController::class, 'statistics']);
Route::delete('/transactions', [TransactionController::class, 'deleteTransaction']);
Route::get('/transactions', [TransactionController::class, 'getAllTransactions']);
Route::get('stats', [TransactionController::class, 'stats']);