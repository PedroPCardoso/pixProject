<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TransactionController;

Route::post('/transactions', [TransactionController::class, 'store']);
Route::get('/statistics', [TransactionController::class, 'statistics']);
Route::delete('/transactions', [TransactionController::class, 'deleteAll']);
Route::get('/transactions', [TransactionController::class, 'getAllTransactions']);
Route::get('/produce', ProducerController::class);
Route::get('/consume', ConsumerController::class);