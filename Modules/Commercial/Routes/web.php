<?php

use Illuminate\Support\Facades\Route;
use Modules\Commercial\Http\Controllers\CommercialClientController;
use Modules\Commercial\Http\Controllers\CommercialContractController;
use Modules\Commercial\Http\Controllers\CommercialContractPaymentController;
use Modules\Commercial\Http\Controllers\CommercialContractServiceController;
use Modules\Commercial\Http\Controllers\CommercialController;
use Modules\Commercial\Http\Controllers\CommercialNegotiationController;
use Modules\Commercial\Http\Controllers\CommercialNegotiationProcessController;
use Modules\Commercial\Http\Controllers\CommercialNegotiationPublicController;

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

    Route::middleware(['middleware' => 'permission:comm_contratos_nuevo|comm_contratos_editar'])
        ->post('contracts/services/search', [CommercialContractServiceController::class, 'search'])
        ->name('comm_contracts_services_search');

    Route::middleware(['middleware' => 'permission:comm_contratos_nuevo|comm_contratos_editar'])
        ->get('contracts/services/{id}', [CommercialContractServiceController::class, 'show'])
        ->whereNumber('id')
        ->name('comm_contracts_services_show');

    Route::middleware(['middleware' => 'permission:comm_contratos_nuevo|comm_contratos_editar'])
        ->post('contracts/services/store', [CommercialContractServiceController::class, 'store'])
        ->name('comm_contracts_services_store');

    Route::middleware(['middleware' => 'permission:comm_contratos_nuevo|comm_contratos_editar'])
        ->put('contracts/services/update/{id}', [CommercialContractServiceController::class, 'update'])
        ->whereNumber('id')
        ->name('comm_contracts_services_update');

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

Route::middleware(['auth', 'verified'])->prefix('commercial')->group(function () {
    Route::middleware(['middleware' => 'permission:comm_negociaciones_listado'])
        ->get('negotiations', [CommercialNegotiationController::class, 'index'])
        ->name('comm_negotiations');

    Route::middleware(['middleware' => 'permission:comm_negociaciones_nuevo'])
        ->get('negotiations/create', [CommercialNegotiationController::class, 'create'])
        ->name('comm_negotiations_create');

    Route::middleware(['middleware' => 'permission:comm_negociaciones_nuevo'])
        ->post('negotiations/store', [CommercialNegotiationController::class, 'store'])
        ->name('comm_negotiations_store');

    Route::middleware(['middleware' => 'permission:comm_negociaciones_editar'])
        ->get('negotiations/edit/{id}', [CommercialNegotiationController::class, 'edit'])
        ->name('comm_negotiations_edit');

    Route::middleware(['middleware' => 'permission:comm_negociaciones_editar'])
        ->post('negotiations/update/{id}', [CommercialNegotiationController::class, 'update'])
        ->name('comm_negotiations_update');

    Route::middleware(['middleware' => 'permission:comm_negociaciones_eliminar'])
        ->delete('negotiations/destroy/{id}', [CommercialNegotiationController::class, 'destroy'])
        ->name('comm_negotiations_destroy');

    Route::middleware(['middleware' => 'permission:comm_negociaciones_listado'])
        ->get('negotiations/show/{id}', [CommercialNegotiationController::class, 'show'])
        ->name('comm_negotiations_show');

    Route::middleware(['middleware' => 'permission:comm_negociaciones_verificar'])
        ->get('negotiations/process/{id}', [CommercialNegotiationProcessController::class, 'index'])
        ->name('comm_negotiations_process');

    Route::middleware(['middleware' => 'permission:comm_negociaciones_verificar'])
        ->post('negotiations/process/{id}/person', [CommercialNegotiationProcessController::class, 'processPerson'])
        ->name('comm_negotiations_process_person');

    Route::middleware(['middleware' => 'permission:comm_negociaciones_verificar'])
        ->post('negotiations/process/{id}/user', [CommercialNegotiationProcessController::class, 'processUser'])
        ->name('comm_negotiations_process_user');

    Route::middleware(['middleware' => 'permission:comm_negociaciones_verificar'])
        ->post('negotiations/process/{id}/student', [CommercialNegotiationProcessController::class, 'processStudent'])
        ->name('comm_negotiations_process_student');

    Route::middleware(['middleware' => 'permission:comm_negociaciones_verificar'])
        ->post('negotiations/process/{id}/registrations', [CommercialNegotiationProcessController::class, 'processRegistrations'])
        ->name('comm_negotiations_process_registrations');

    Route::middleware(['middleware' => 'permission:comm_negociaciones_verificar'])
        ->post('negotiations/process/{id}/subscriptions', [CommercialNegotiationProcessController::class, 'processSubscriptions'])
        ->name('comm_negotiations_process_subscriptions');

    Route::middleware(['middleware' => 'permission:comm_negociaciones_verificar'])
        ->post('negotiations/process/{id}/document', [CommercialNegotiationProcessController::class, 'processDocument'])
        ->name('comm_negotiations_process_document');

    Route::middleware(['middleware' => 'permission:comm_negociaciones_verificar'])
        ->post('negotiations/process/{id}/email', [CommercialNegotiationProcessController::class, 'processEmail'])
        ->name('comm_negotiations_process_email');

    Route::middleware(['middleware' => 'permission:comm_negociaciones_verificar'])
        ->post('negotiations/process/{id}/complete', [CommercialNegotiationProcessController::class, 'complete'])
        ->name('comm_negotiations_process_complete');

    Route::middleware(['middleware' => 'permission:comm_negociaciones_verificar'])
        ->post('negotiations/approve/{id}', [CommercialNegotiationController::class, 'approve'])
        ->name('comm_negotiations_approve');

    Route::middleware(['middleware' => 'permission:comm_negociaciones_verificar'])
        ->post('negotiations/reject/{id}', [CommercialNegotiationController::class, 'reject'])
        ->name('comm_negotiations_reject');

    Route::middleware(['middleware' => 'permission:comm_negociaciones_editar'])
        ->post('negotiations/cancel/{id}', [CommercialNegotiationController::class, 'cancel'])
        ->name('comm_negotiations_cancel');
});

Route::get('negotiations/public/{token}', [CommercialNegotiationPublicController::class, 'show'])
    ->where('token', '[A-Za-z0-9-]+')
    ->name('comm_negotiations_public_show');

Route::post('negotiations/public/{token}', [CommercialNegotiationPublicController::class, 'store'])
    ->where('token', '[A-Za-z0-9-]+')
    ->name('comm_negotiations_public_store');

Route::post('negotiations/public/{token}/search', [CommercialNegotiationPublicController::class, 'searchPerson'])
    ->where('token', '[A-Za-z0-9-]+')
    ->name('comm_negotiations_public_search');

Route::post('negotiations/public/{token}/validate-ruc', [CommercialNegotiationPublicController::class, 'validateRuc'])
    ->where('token', '[A-Za-z0-9-]+')
    ->name('comm_negotiations_public_validate_ruc');
