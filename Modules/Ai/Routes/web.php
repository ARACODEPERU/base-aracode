<?php

use Illuminate\Support\Facades\Route;
use Modules\Ai\Http\Controllers\AiController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
//Route::prefix('ai')->middleware('auth')->group(function () {
Route::prefix('ai')->group(function () {
    Route::get('/', [AiController::class, 'index'])->name('ai.index');
    Route::post('/consulta', [AiController::class, 'consulta'])->name('ai.consulta');
    Route::post('/censurar', [AiController::class, 'censurar'])->name('ai.censurar');
    Route::post('/mejorar', [AiController::class, 'mejorar'])->name('ai.mejorar');
    Route::post('/recomendar', [AiController::class, 'recomendar'])->name('ai.recomendar');
});
