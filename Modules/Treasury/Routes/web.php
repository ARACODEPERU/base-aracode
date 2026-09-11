<?php

/*
|--------------------------------------------------------------------------
| Web Routes del módulo Treasury
|--------------------------------------------------------------------------
*/

use Illuminate\Support\Facades\Route;
use Modules\Treasury\Http\Controllers\TreasuryAccountController;
use Modules\Treasury\Http\Controllers\TreasuryCategoryController;
use Modules\Treasury\Http\Controllers\TreasuryDashboardController;
use Modules\Treasury\Http\Controllers\TreasuryReconciliationController;
use Modules\Treasury\Http\Controllers\TreasuryTransactionController;

Route::middleware(['auth', 'verified', 'user_activity_log'])->prefix('treasury')->group(function () {
    Route::middleware(['middleware' => 'permission:treasury_dashboard'])
        ->get('dashboard', [TreasuryDashboardController::class, 'index'])
        ->name('treasury_dashboard');

    // Cuentas (bancos y billeteras) + asignación de métodos de pago
    Route::middleware(['middleware' => 'permission:treasury_cuentas'])
        ->get('accounts', [TreasuryAccountController::class, 'index'])->name('treasury_accounts_index');
    Route::middleware(['middleware' => 'permission:treasury_cuentas_nuevo'])
        ->post('accounts/store', [TreasuryAccountController::class, 'store'])->name('treasury_accounts_store');
    Route::middleware(['middleware' => 'permission:treasury_cuentas_editar'])
        ->post('accounts/update/{id}', [TreasuryAccountController::class, 'update'])->name('treasury_accounts_update');
    Route::middleware(['middleware' => 'permission:treasury_cuentas_eliminar'])
        ->delete('accounts/destroy/{id}', [TreasuryAccountController::class, 'destroy'])->name('treasury_accounts_destroy');
    Route::middleware(['middleware' => 'permission:treasury_cuentas_editar'])
        ->post('accounts/assign-payment-method', [TreasuryAccountController::class, 'assignPaymentMethod'])
        ->name('treasury_accounts_assign_payment_method');

    // Libro de bancos (movimientos)
    Route::middleware(['middleware' => 'permission:treasury_movimientos'])
        ->get('transactions', [TreasuryTransactionController::class, 'index'])->name('treasury_transactions_index');
    Route::middleware(['middleware' => 'permission:treasury_movimientos_nuevo'])
        ->post('transactions/store', [TreasuryTransactionController::class, 'store'])->name('treasury_transactions_store');
    Route::middleware(['middleware' => 'permission:treasury_movimientos_editar'])
        ->post('transactions/update/{id}', [TreasuryTransactionController::class, 'update'])->name('treasury_transactions_update');
    Route::middleware(['middleware' => 'permission:treasury_movimientos_eliminar'])
        ->delete('transactions/destroy/{id}', [TreasuryTransactionController::class, 'destroy'])->name('treasury_transactions_destroy');

    // Conciliación manual
    Route::middleware(['middleware' => 'permission:treasury_conciliacion'])
        ->get('reconciliation', [TreasuryReconciliationController::class, 'index'])->name('treasury_reconciliation_index');
    Route::middleware(['middleware' => 'permission:treasury_conciliacion'])
        ->post('reconciliation/reconcile', [TreasuryReconciliationController::class, 'reconcile'])
        ->name('treasury_reconciliation_reconcile');

    // Categorías
    Route::middleware(['middleware' => 'permission:treasury_categorias'])
        ->get('categories', [TreasuryCategoryController::class, 'index'])->name('treasury_categories_index');
    Route::middleware(['middleware' => 'permission:treasury_categorias'])
        ->post('categories/store', [TreasuryCategoryController::class, 'store'])->name('treasury_categories_store');
    Route::middleware(['middleware' => 'permission:treasury_categorias'])
        ->post('categories/update/{id}', [TreasuryCategoryController::class, 'update'])->name('treasury_categories_update');
    Route::middleware(['middleware' => 'permission:treasury_categorias'])
        ->delete('categories/destroy/{id}', [TreasuryCategoryController::class, 'destroy'])->name('treasury_categories_destroy');
});
