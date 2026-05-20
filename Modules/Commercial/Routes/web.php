<?php

use Illuminate\Support\Facades\Route;
use Modules\Commercial\Http\Controllers\CommercialClientController;
use Modules\Commercial\Http\Controllers\CommercialContractController;
use Modules\Commercial\Http\Controllers\CommercialContractPaymentController;
use Modules\Commercial\Http\Controllers\CommercialController;

Route::middleware(['auth', 'verified'])->prefix('commercial')->group(function () {
    Route::get('dashboard', [CommercialController::class, 'index'])
        ->name('comm_dashboard');

    Route::middleware(['middleware' => 'permission:comm_clientes_listado'])
        ->get('clients', [CommercialClientController::class, 'index'])
        ->name('comm_clients');

    Route::middleware(['middleware' => 'permission:comm_clientes_nuevo'])
        ->get('clients/create', [CommercialClientController::class, 'create'])
        ->name('comm_clients_create');

    Route::middleware(['middleware' => 'permission:comm_clientes_nuevo'])
        ->post('clients/store', [CommercialClientController::class, 'store'])
        ->name('comm_clients_store');

    Route::middleware(['middleware' => 'permission:comm_clientes_editar'])
        ->get('clients/edit/{id}', [CommercialClientController::class, 'edit'])
        ->name('comm_clients_edit');

    Route::middleware(['middleware' => 'permission:comm_clientes_editar'])
        ->put('clients/update/{id}', [CommercialClientController::class, 'update'])
        ->name('comm_clients_update');

    Route::middleware(['middleware' => 'permission:comm_clientes_eliminar'])
        ->delete('clients/destroy/{id}', [CommercialClientController::class, 'destroy'])
        ->name('comm_clients_destroy');

    Route::post('clients/search/internal', [CommercialClientController::class, 'searchInternal'])
        ->name('comm_clients_search_internal');

    Route::post('clients/search/external', [CommercialClientController::class, 'searchExternal'])
        ->name('comm_clients_search_external');

    Route::middleware(['middleware' => 'permission:comm_contratos_listado'])
        ->get('contracts', [CommercialContractController::class, 'index'])
        ->name('comm_contracts');

    Route::middleware(['middleware' => 'permission:comm_contratos_nuevo'])
        ->get('contracts/create', [CommercialContractController::class, 'create'])
        ->name('comm_contracts_create');

    Route::middleware(['middleware' => 'permission:comm_contratos_nuevo'])
        ->post('contracts/store', [CommercialContractController::class, 'store'])
        ->name('comm_contracts_store');

    Route::middleware(['middleware' => 'permission:comm_contratos_editar'])
        ->get('contracts/edit/{id}', [CommercialContractController::class, 'edit'])
        ->name('comm_contracts_edit');

    Route::middleware(['middleware' => 'permission:comm_contratos_editar'])
        ->post('contracts/update/{id}', [CommercialContractController::class, 'update'])
        ->name('comm_contracts_update');

    Route::middleware(['middleware' => 'permission:comm_contratos_eliminar'])
        ->delete('contracts/destroy/{id}', [CommercialContractController::class, 'destroy'])
        ->name('comm_contracts_destroy');

    Route::post('contracts/responsible/search', [CommercialContractController::class, 'searchResponsible'])
        ->name('comm_contracts_responsible_search');

    Route::middleware(['middleware' => 'permission:comm_contratos_cronograma'])
        ->get('contracts/payments/{payment}/document/create', [CommercialContractPaymentController::class, 'createDocument'])
        ->name('comm_contract_payment_document_create');

    Route::middleware(['middleware' => 'permission:comm_contratos_cronograma'])
        ->post('contracts/payments/document/store', [CommercialContractPaymentController::class, 'storeDocument'])
        ->name('comm_contract_payment_document_store');

    Route::middleware(['middleware' => 'permission:comm_contratos_cronograma'])
        ->get('contracts/{id}/payments', [CommercialContractPaymentController::class, 'index'])
        ->name('comm_contracts_payments');

    Route::middleware(['middleware' => 'permission:comm_contratos_cronograma'])
        ->post('contracts/{id}/payments/store', [CommercialContractPaymentController::class, 'store'])
        ->name('comm_contracts_payments_store');
});
