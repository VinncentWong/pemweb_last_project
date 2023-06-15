<?php

use App\Http\Controllers\MapController;
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

Route::get('/map', [MapController::class, 'map']);
Route::post("/create/history", [MapController::class, 'saveHistoryData']);
Route::get("/history/{id}", [MapController::class, 'getHistoryData']);