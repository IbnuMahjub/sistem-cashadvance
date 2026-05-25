<?php

use App\Http\Controllers\CAviewController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});


Route::get('/ca', function () {
    return view('ca');
});
// Route::get('/ca-pl', function () {
//     return view('capl');
// });

Route::get('/wallet', [CAviewController::class, 'index']);