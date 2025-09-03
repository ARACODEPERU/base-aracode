<?php

use Illuminate\Support\Facades\Route;
use Modules\Churchcommunity\Http\Controllers\ChurchcommunityController;
use Modules\Churchcommunity\Http\Controllers\CigleMemberController;


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

Route::middleware(['auth', 'verified'])->prefix('cigle')->group(function () {
    Route::get('dashboard', [ChurchcommunityController::class, 'index'])->name('cigle_dashboard');

    Route::prefix('member')->group(function () {
        Route::get('list', [CigleMemberController::class, 'index'])->name('cigle_member_list');
        Route::get('believing/create', [CigleMemberController::class, 'believerCreate'])->name('cigle_member_believing_create');
        Route::post('believing/store', [CigleMemberController::class, 'believerAction'])->name('cigle_member_believing_store');
        Route::post('search', [CigleMemberController::class, 'searchMember'])->name('cigle_member_search');
        Route::post('members/table', [CigleMemberController::class, 'getMembers'])->name('cigle_member_table');

    });
});
