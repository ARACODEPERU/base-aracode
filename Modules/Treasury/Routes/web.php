<?php

/*
|--------------------------------------------------------------------------
| Web Routes del módulo Treasury
|--------------------------------------------------------------------------
*/

use Illuminate\Support\Facades\Route;
use Modules\Treasury\Http\Controllers\TreasuryAccountController;
use Modules\Treasury\Http\Controllers\TreasuryAuditController;
use Modules\Treasury\Http\Controllers\TreasuryCashController;
use Modules\Treasury\Http\Controllers\TreasuryCategoryController;
use Modules\Treasury\Http\Controllers\TreasuryCollectionLinkController;
use Modules\Treasury\Http\Controllers\TreasuryConfigurationController;
use Modules\Treasury\Http\Controllers\TreasuryDashboardController;
use Modules\Treasury\Http\Controllers\TreasuryDetractionController;
use Modules\Treasury\Http\Controllers\TreasuryDocumentController;
use Modules\Treasury\Http\Controllers\TreasuryForecastController;
use Modules\Treasury\Http\Controllers\TreasuryIntegrationController;
use Modules\Treasury\Http\Controllers\TreasuryPaymentOrderController;
use Modules\Treasury\Http\Controllers\TreasuryPeriodController;
use Modules\Treasury\Http\Controllers\TreasuryRecurringExpenseController;
use Modules\Treasury\Http\Controllers\TreasuryReconciliationController;
use Modules\Treasury\Http\Controllers\TreasuryReportController;
use Modules\Treasury\Http\Controllers\TreasuryStatementImportController;
use Modules\Treasury\Http\Controllers\TreasurySupplierController;
use Modules\Treasury\Http\Controllers\TreasuryTaxController;
use Modules\Treasury\Http\Controllers\TreasuryTransactionController;
use Modules\Treasury\Http\Controllers\TreasuryTransferController;
use Modules\Treasury\Http\Middleware\EnsureTreasuryEnabled;

// Las pantallas y operaciones del módulo solo responden si Tesorería está activa.
Route::middleware(['auth', 'verified', 'user_activity_log', EnsureTreasuryEnabled::class])->prefix('treasury')->group(function () {
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
    Route::middleware(['middleware' => 'permission:treasury_cuentas_nuevo'])
        ->post('accounts/sync', [TreasuryAccountController::class, 'sync'])->name('treasury_accounts_sync');
    Route::middleware(['middleware' => 'permission:treasury_cuentas_eliminar'])
        ->delete('accounts/destroy/{id}', [TreasuryAccountController::class, 'destroy'])->name('treasury_accounts_destroy');
    Route::middleware(['middleware' => 'permission:treasury_cuentas_editar'])
        ->post('accounts/assign-payment-method', [TreasuryAccountController::class, 'assignPaymentMethod'])
        ->name('treasury_accounts_assign_payment_method');

    // Transferencias entre cuentas (con comisiones, ITF y tipo de cambio)
    Route::middleware(['middleware' => 'permission:treasury_cuentas'])
        ->get('transfers', [TreasuryTransferController::class, 'index'])->name('treasury_transfers_index');
    Route::middleware(['middleware' => 'permission:treasury_cuentas'])
        ->get('transfers/profile', [TreasuryTransferController::class, 'profile'])->name('treasury_transfers_profile');
    Route::middleware(['middleware' => 'permission:treasury_cuentas_nuevo'])
        ->post('transfers/store', [TreasuryTransferController::class, 'store'])->name('treasury_transfers_store');
    Route::middleware(['middleware' => 'permission:treasury_cuentas_editar'])
        ->post('transfers/void/{id}', [TreasuryTransferController::class, 'destroy'])->name('treasury_transfers_void');

    // Libro de bancos (movimientos)
    Route::middleware(['middleware' => 'permission:treasury_movimientos'])
        ->get('transactions', [TreasuryTransactionController::class, 'index'])->name('treasury_transactions_index');
    Route::middleware(['middleware' => 'permission:treasury_movimientos_nuevo'])
        ->post('transactions/store', [TreasuryTransactionController::class, 'store'])->name('treasury_transactions_store');
    Route::middleware(['middleware' => 'permission:treasury_movimientos_editar'])
        ->post('transactions/update/{id}', [TreasuryTransactionController::class, 'update'])->name('treasury_transactions_update');
    Route::middleware(['middleware' => 'permission:treasury_movimientos_eliminar'])
        ->delete('transactions/destroy/{id}', [TreasuryTransactionController::class, 'destroy'])->name('treasury_transactions_destroy');
    // Auto-detracción de una factura desde el cobro registrado.
    Route::middleware(['middleware' => 'permission:treasury_movimientos_nuevo', 'throttle:20,1'])
        ->post('transactions/auto-detraction/{id}', [TreasuryTransactionController::class, 'autoDetraction'])
        ->name('treasury_transactions_auto_detraction');

    // Pagos, compras, pagos a proveedores y gastos
    Route::middleware(['middleware' => 'permission:treasury_pagos'])
        ->get('payments', [TreasuryPaymentOrderController::class, 'index'])->name('treasury_payments_index');
    Route::middleware(['middleware' => 'permission:treasury_pagos_nuevo'])
        ->post('payments/store', [TreasuryPaymentOrderController::class, 'store'])->name('treasury_payments_store');
    Route::middleware(['middleware' => 'permission:treasury_pagos_aprobar'])
        ->post('payments/approve/{id}', [TreasuryPaymentOrderController::class, 'approve'])->name('treasury_payments_approve');
    Route::middleware(['middleware' => 'permission:treasury_pagos_aprobar'])
        ->post('payments/reject/{id}', [TreasuryPaymentOrderController::class, 'reject'])->name('treasury_payments_reject');
    Route::middleware(['middleware' => 'permission:treasury_pagos_ejecutar'])
        ->post('payments/pay/{id}', [TreasuryPaymentOrderController::class, 'pay'])->name('treasury_payments_pay');
    Route::middleware(['middleware' => 'permission:treasury_pagos_anular'])
        ->post('payments/void/{id}', [TreasuryPaymentOrderController::class, 'destroy'])->name('treasury_payments_void');
    // Archivo del lote para el banco: no ejecuta pagos, deja el lote registrado.
    Route::middleware(['middleware' => 'permission:treasury_pagos_masivos', 'throttle:20,1'])
        ->post('payments/bulk-file', [TreasuryPaymentOrderController::class, 'exportBulk'])
        ->name('treasury_payments_bulk_file');

    // Proveedores
    Route::middleware(['middleware' => 'permission:treasury_proveedores'])
        ->post('suppliers/store', [TreasurySupplierController::class, 'store'])->name('treasury_suppliers_store');
    Route::middleware(['middleware' => 'permission:treasury_proveedores'])
        ->post('suppliers/update/{id}', [TreasurySupplierController::class, 'update'])->name('treasury_suppliers_update');
    Route::middleware(['middleware' => 'permission:treasury_proveedores'])
        ->delete('suppliers/destroy/{id}', [TreasurySupplierController::class, 'destroy'])->name('treasury_suppliers_destroy');

    // Gastos recurrentes (cola database)
    Route::middleware(['middleware' => 'permission:treasury_recurrentes'])
        ->post('recurring/store', [TreasuryRecurringExpenseController::class, 'store'])->name('treasury_recurring_store');
    Route::middleware(['middleware' => 'permission:treasury_recurrentes'])
        ->post('recurring/update/{id}', [TreasuryRecurringExpenseController::class, 'update'])->name('treasury_recurring_update');
    Route::middleware(['middleware' => 'permission:treasury_recurrentes'])
        ->post('recurring/run/{id}', [TreasuryRecurringExpenseController::class, 'runNow'])->name('treasury_recurring_run');
    Route::middleware(['middleware' => 'permission:treasury_recurrentes'])
        ->delete('recurring/destroy/{id}', [TreasuryRecurringExpenseController::class, 'destroy'])->name('treasury_recurring_destroy');

    // Previsión de flujo de caja
    Route::middleware(['middleware' => 'permission:treasury_prevision'])
        ->get('forecast', [TreasuryForecastController::class, 'index'])->name('treasury_forecast_index');
    Route::middleware(['middleware' => 'permission:treasury_prevision'])
        ->post('forecast/adjustments/store', [TreasuryForecastController::class, 'storeAdjustment'])->name('treasury_forecast_adjustments_store');
    Route::middleware(['middleware' => 'permission:treasury_prevision'])
        ->delete('forecast/adjustments/destroy/{id}', [TreasuryForecastController::class, 'destroyAdjustment'])->name('treasury_forecast_adjustments_destroy');

    // Efectivo: panel de caja chica y arqueo con cierre de sesión
    Route::middleware(['middleware' => 'permission:treasury_efectivo'])
        ->get('cash', [TreasuryCashController::class, 'index'])->name('treasury_cash_index');
    Route::middleware(['middleware' => 'permission:treasury_efectivo_arqueo'])
        ->post('cash/count', [TreasuryCashController::class, 'storeCount'])->name('treasury_cash_count');

    // Cartera de cheques y letras (por cobrar y por pagar)
    Route::middleware(['middleware' => 'permission:treasury_documentos'])
        ->get('documents', [TreasuryDocumentController::class, 'index'])->name('treasury_documents_index');
    Route::middleware(['middleware' => 'permission:treasury_documentos_gestionar'])
        ->post('documents/store', [TreasuryDocumentController::class, 'store'])->name('treasury_documents_store');
    Route::middleware(['middleware' => 'permission:treasury_documentos_gestionar'])
        ->post('documents/status/{id}', [TreasuryDocumentController::class, 'changeStatus'])->name('treasury_documents_status');
    // Consulta RENIEC/SUNAT del emisor: se limita por minuto para cuidar el token.
    Route::middleware(['middleware' => 'permission:treasury_documentos_gestionar', 'throttle:30,1'])
        ->post('documents/consult-issuer', [TreasuryDocumentController::class, 'consultIssuer'])->name('treasury_documents_consult_issuer');

    // Cobros sin cuenta: vinculación manual de comprobantes
    Route::middleware(['middleware' => 'permission:treasury_cobros_vincular'])
        ->get('collections', [TreasuryCollectionLinkController::class, 'index'])
        ->name('treasury_collections_index');
    Route::middleware(['middleware' => 'permission:treasury_cobros_vincular'])
        ->post('collections/link', [TreasuryCollectionLinkController::class, 'store'])
        ->name('treasury_collections_link');

    // Conciliación bancaria con acta inmutable
    Route::middleware(['middleware' => 'permission:treasury_conciliacion'])
        ->get('reconciliation', [TreasuryReconciliationController::class, 'index'])->name('treasury_reconciliation_index');
    Route::middleware(['middleware' => 'permission:treasury_conciliacion_cerrar'])
        ->post('reconciliation/close', [TreasuryReconciliationController::class, 'close'])->name('treasury_reconciliation_close');
    Route::middleware(['middleware' => 'permission:treasury_conciliacion'])
        ->post('reconciliation/items/store', [TreasuryReconciliationController::class, 'storeItem'])->name('treasury_reconciliation_items_store');
    Route::middleware(['middleware' => 'permission:treasury_conciliacion'])
        ->delete('reconciliation/items/destroy/{id}', [TreasuryReconciliationController::class, 'destroyItem'])->name('treasury_reconciliation_items_destroy');

    // Importación de extractos (CSV, Excel u OFX) con mapeo revisado
    Route::middleware(['middleware' => 'permission:treasury_conciliacion_importar', 'throttle:20,1'])
        ->post('reconciliation/import/preview', [TreasuryStatementImportController::class, 'preview'])
        ->name('treasury_reconciliation_import_preview');
    Route::middleware(['middleware' => 'permission:treasury_conciliacion_importar', 'throttle:20,1'])
        ->post('reconciliation/import/confirm', [TreasuryStatementImportController::class, 'confirm'])
        ->name('treasury_reconciliation_import_confirm');
    // Registrar como movimiento una línea del extracto que no tenía par.
    Route::middleware(['middleware' => 'permission:treasury_conciliacion_importar', 'throttle:60,1'])
        ->post('reconciliation/statement/{import}/movement', [TreasuryReconciliationController::class, 'createStatementMovement'])
        ->name('treasury_reconciliation_statement_movement');
    // Registrar varias líneas del extracto seleccionadas de una sola vez.
    Route::middleware(['middleware' => 'permission:treasury_conciliacion_importar', 'throttle:60,1'])
        ->post('reconciliation/statement/{import}/movements', [TreasuryReconciliationController::class, 'createStatementMovements'])
        ->name('treasury_reconciliation_statement_movements');
    // Descartar el par recomendado: la línea del banco es otro movimiento.
    Route::middleware(['middleware' => 'permission:treasury_conciliacion_importar', 'throttle:60,1'])
        ->post('reconciliation/statement/{import}/lines/{line}/unlink', [TreasuryReconciliationController::class, 'dismissStatementMatch'])
        ->name('treasury_reconciliation_statement_unlink');

    // Cierre de periodos
    Route::middleware(['middleware' => 'permission:treasury_periodos'])
        ->get('periods', [TreasuryPeriodController::class, 'index'])->name('treasury_periods_index');
    Route::middleware(['middleware' => 'permission:treasury_periodos_cerrar'])
        ->post('periods/close', [TreasuryPeriodController::class, 'close'])->name('treasury_periods_close');
    Route::middleware(['middleware' => 'permission:treasury_periodos_reabrir'])
        ->post('periods/reopen', [TreasuryPeriodController::class, 'reopen'])->name('treasury_periods_reopen');

    // Reportes con moneda y centro de costo
    Route::middleware(['middleware' => 'permission:treasury_reportes'])
        ->get('reports', [TreasuryReportController::class, 'index'])->name('treasury_reports_index');
    Route::middleware(['middleware' => 'permission:treasury_reportes'])
        ->post('reports/cost-centers/store', [TreasuryReportController::class, 'storeCostCenter'])->name('treasury_cost_centers_store');
    Route::middleware(['middleware' => 'permission:treasury_reportes'])
        ->post('reports/cost-centers/update/{id}', [TreasuryReportController::class, 'updateCostCenter'])->name('treasury_cost_centers_update');
    Route::middleware(['middleware' => 'permission:treasury_reportes'])
        ->delete('reports/cost-centers/destroy/{id}', [TreasuryReportController::class, 'destroyCostCenter'])->name('treasury_cost_centers_destroy');
    Route::middleware(['middleware' => 'permission:treasury_presupuesto'])
        ->post('reports/budgets/store', [TreasuryReportController::class, 'storeBudget'])->name('treasury_budgets_store');
    Route::middleware(['middleware' => 'permission:treasury_presupuesto'])
        ->delete('reports/budgets/destroy/{id}', [TreasuryReportController::class, 'destroyBudget'])->name('treasury_budgets_destroy');

    // Cuenta de detracciones: cobros, liberación y ajuste crítico
    Route::middleware(['middleware' => 'permission:treasury_detraccion'])
        ->get('detraction', [TreasuryDetractionController::class, 'index'])->name('treasury_detraction_index');
    // Ajuste y liberación piden contraseña: se limitan los intentos por minuto.
    Route::middleware(['middleware' => 'permission:treasury_detraccion', 'throttle:6,1'])
        ->post('detraction/adjust', [TreasuryDetractionController::class, 'adjust'])->name('treasury_detraction_adjust');
    Route::middleware(['middleware' => 'permission:treasury_detraccion', 'throttle:6,1'])
        ->post('detraction/release', [TreasuryDetractionController::class, 'release'])->name('treasury_detraction_release');
    // Banco y número de cuenta donde los clientes depositan la detracción.
    Route::middleware(['middleware' => 'permission:treasury_detraccion'])
        ->post('detraction/account', [TreasuryDetractionController::class, 'updateAccount'])->name('treasury_detraction_account_update');

    // IGV de boletas y facturas: obligación del periodo y su pago
    Route::middleware(['middleware' => 'permission:treasury_impuestos'])
        ->get('taxes', [TreasuryTaxController::class, 'index'])->name('treasury_taxes_index');
    Route::middleware(['middleware' => 'permission:treasury_impuestos'])
        ->post('taxes/pay', [TreasuryTaxController::class, 'pay'])->name('treasury_taxes_pay');

    // Auditoría (solo lectura, inmutable)
    Route::middleware(['middleware' => 'permission:treasury_auditoria'])
        ->get('audit', [TreasuryAuditController::class, 'index'])->name('treasury_audit_index');

    // Bandeja de reintentos de la integración
    Route::middleware(['middleware' => 'permission:treasury_reintentos'])
        ->get('integration', [TreasuryIntegrationController::class, 'index'])->name('treasury_integration_index');
    Route::middleware(['middleware' => 'permission:treasury_reintentos'])
        ->post('integration/retry', [TreasuryIntegrationController::class, 'retry'])->name('treasury_integration_retry');

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

// Configuración del módulo: sigue disponible aunque Tesorería esté apagada,
// porque desde aquí se vuelve a activar.
Route::middleware(['auth', 'verified', 'user_activity_log'])->prefix('treasury')->group(function () {
    Route::middleware(['middleware' => 'permission:treasury_configuracion'])
        ->get('configuration', [TreasuryConfigurationController::class, 'index'])
        ->name('treasury_configuration_index');
    Route::middleware(['middleware' => 'permission:treasury_configuracion', 'throttle:6,1'])
        ->post('configuration/update', [TreasuryConfigurationController::class, 'update'])
        ->name('treasury_configuration_update');
});
