<?php

use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::post('bank/account/store', [BankAccountController::class, 'storeOrUpdate'])
        ->name('bank-account-store');
    Route::delete('bank/account/destroy/{id}', [BankAccountController::class, 'destroy'])
        ->name('bank-account-destroy');

    ///users///
    Route::get('datatables/users', [UserController::class, 'getUsers'])->name('users-tables-list');
});
