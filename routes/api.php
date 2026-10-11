<?php

use App\Http\Controllers\AuthController;
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

// Mismos datos pero con roles: lo usa la app móvil para saber si el usuario es administrador.
Route::middleware('auth:sanctum')->get('/user/roles', [AuthController::class, 'user']);


Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
