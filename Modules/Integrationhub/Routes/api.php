<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Integrationhub\Http\Controllers\Api\BirthdayBenefitsController;

/*
    |--------------------------------------------------------------------------
    | API Routes
    |--------------------------------------------------------------------------
    |
    | Here is where you can register API routes for your application. These
    | routes are loaded by the RouteServiceProvider within a group which
    | is assigned the "api" middleware group. Enjoy building your API!
    |
*/

Route::middleware(['auth:sanctum'])->prefix('v1')->name('api.')->group(function () {
    Route::get('integrationhub', fn (Request $request) => $request->user())->name('integrationhub');
});

Route::middleware(['localhost.only'])->prefix('integrationhub')->name('integrationhub.')->group(function () {
    Route::post('birthday_benefits', BirthdayBenefitsController::class)->name('birthday_benefits');
});
