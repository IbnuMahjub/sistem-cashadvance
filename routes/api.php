<?php

use App\Http\Controllers\CashAdvanceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OCRController;

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


// Route::get('/ca', [CashAdvanceController::class, 'index']);
// Route::get('/ca/{kode_ca}', [CashAdvanceController::class, 'showByKode']);

// Route::post('/ca/{kode}/transaksi', [CashAdvanceController::class, 'postTransaksi']);
// Route::delete('/ca/{kode_ca}', [CashAdvanceController::class, 'delete_ca']);

Route::post('/post_ca', [CashAdvanceController::class, 'post_ca']);
Route::post('/close-ca/{kode_ca}', [CashAdvanceController::class, 'close_ca']);
// Route::get('/ca-pl', [CashAdvanceController::class, 'caPL']);
Route::get('/ca/{kode_ca}', [CashAdvanceController::class, 'showByKode']);


Route::delete('/ca/{kode_ca}', [CashAdvanceController::class, 'delete_ca']);

Route::delete('/ca/transaksi/{id}', [CashAdvanceController::class, 'deleteTransaksiCaPl']);
Route::post('/ocr/scan-struk', [OCRController::class, 'scan']);

Route::get('/wallet-pl', [CashAdvanceController::class, 'walletPL']);
Route::get('/table-wallet-pl', [CashAdvanceController::class, 'tableWalletPL']);
// Route::get('/wallet-pl-riwayat', [CashAdvanceController::class, 'walletPLRiwayat']);

// Route::get('/wallet-pl/{kode_ca}', [CashAdvanceController::class, 'walletPLshowByKode']);
Route::post('/wallet-pl/{kode_ca}/transaksi', [CashAdvanceController::class, 'walletPLPostTransaksi']);


Route::post('/topup-wallet-pl', [CashAdvanceController::class, 'topupWalletPL']);
Route::post('/esekusi-topup-wallet-pl', [CashAdvanceController::class, 'esekusiTopupWalletPL']);

// route table transaksi wallet pl
Route::get('/transaksi-wallet-pl/{kode_ca}', [CashAdvanceController::class, 'showTransaksiByKode']);
// route  transaksi wallet pl
Route::post('/ca/{kode_ca}/transaksi', [CashAdvanceController::class, 'postTransaksiCaPl']);
Route::put('/ca/{kode_ca}/transaksi/{id}', [CashAdvanceController::class, 'updateTransaksiCaPl']);
Route::delete('/ca/{kode_ca}/transaksi/{id}', [CashAdvanceController::class, 'deleteTransaksiCaPl']);

Route::get('/cashadvance', [CashAdvanceController::class, 'cashadvance']);


Route::get('/riwayat-wallet-kegiatan', [CashAdvanceController::class, 'walletPLRiwayatKegiatan']);
Route::post('/topup-wallet-kegiatan', [CashAdvanceController::class, 'topupWalletKegiatan']);
Route::post('/kegiatan/{kode_ca}/transaksi', [CashAdvanceController::class, 'postTransaksiKegiatan']);
Route::post('/close-kegiatan', [CashAdvanceController::class, 'closeKegiatan']);
Route::put('/kegiatan/{kode_ca}/transaksi/{id}', [CashAdvanceController::class, 'updateTransaksiKegiatan']);
Route::delete('/kegiatan/{kode_ca}/transaksi/{id}', [CashAdvanceController::class, 'deleteTransaksiKegiatan']);
Route::get('/transaksi-wallet-kegiatan/{kode_ca}', [CashAdvanceController::class, 'showTransaksiKegiatanByKode']);