<?php

use App\Http\Controllers\CashAdvanceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::get('/ca', [CashAdvanceController::class, 'index']);
Route::get('/ca/{kode_ca}', [CashAdvanceController::class, 'showByKode']);
Route::post('/post_ca', [CashAdvanceController::class, 'post_ca']);
Route::post('/ca/{kode}/transaksi', [CashAdvanceController::class, 'postTransaksi']);
Route::delete('/ca/{kode_ca}', [CashAdvanceController::class, 'delete_ca']);
