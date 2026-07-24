<?php

use App\Http\Controllers\CashAdvanceController;
use App\Http\Controllers\DompetKegiatanController;
use App\Http\Controllers\DompetPLController;
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



// Route::post('/wallet-pl/{kode_ca}/transaksi', [CashAdvanceController::class, 'walletPLPostTransaksi']);

// route  dompet pl
Route::post('/topup-wallet-pl', [DompetPLController::class, 'topupWalletPL']);



Route::post('/esekusi-topup-wallet-pl', [DompetPLController::class, 'esekusiTopupWalletPL']);

// route table transaksi wallet pl
Route::get('/transaksi-wallet-pl/{kode_ca}', [DompetPLController::class, 'showTransaksiByKode']);
// route  transaksi wallet pl
Route::post('/ca/{kode_ca}/transaksi', [DompetPLController::class, 'postTransaksiCaPl']);
Route::put('/ca/{kode_ca}/transaksi/{id}', [DompetPLController::class, 'updateTransaksiCaPl']);
Route::delete('/ca/{kode_ca}/transaksi/{id}', [DompetPLController::class, 'deleteTransaksiCaPl']);
Route::get('/riwayat-wallet-pl', [DompetPLController::class, 'riwayatWalletPL']);
Route::get('/laporan-wallet-pl/{id}', [DompetPLController::class, 'laporanWalletPL']);
Route::get('/detail-wallet-pl/{kode_ca}', [DompetPLController::class, 'detailWalletPL']);
Route::get('/list-wallet-pl', [DompetPLController::class, 'listWalletPL']);
Route::get('/wallet-pl/{kode_ca}', [DompetPLController::class, 'showWalletPlByKode']);

Route::get('/cashadvance', [CashAdvanceController::class, 'cashadvance']);


// route dompet kegiatan
Route::get('/riwayat-wallet-kegiatan', [DompetKegiatanController::class, 'walletPLRiwayatKegiatan']);
Route::post('/topup-wallet-kegiatan', [DompetKegiatanController::class, 'topupWalletKegiatan']);
Route::post('/kegiatan/{kode_ca}/transaksi', [DompetKegiatanController::class, 'postTransaksiKegiatan']);
Route::post('/close-kegiatan', [DompetKegiatanController::class, 'closeKegiatan']);
Route::put('/kegiatan/{kode_ca}/transaksi/{id}', [DompetKegiatanController::class, 'updateTransaksiKegiatan']);
Route::delete('/kegiatan/{kode_ca}/transaksi/{id}', [DompetKegiatanController::class, 'deleteTransaksiKegiatan']);
Route::get('/transaksi-wallet-kegiatan/{kode_ca}', [DompetKegiatanController::class, 'showTransaksiKegiatanByKode']);
Route::get('/list-wallet-kegiatan', [DompetKegiatanController::class, 'listWalletKegiatan']);
Route::get('/wallet-kegiatan/{kode_ca}', [DompetKegiatanController::class, 'showWalletKegiatanByKode']);

Route::get('/all-riwayat-wallet', [CashAdvanceController::class, 'allRiwayatWallet']);