<?php

return [
    'name' => 'Treasury',

    /*
    | Enganche automático de ingresos: cuando una venta o abono se registra,
    | se crea el movimiento en el libro de tesorería (si hay cuenta mapeada).
    | Se puede desactivar con TREASURY_AUTO_INCOME=false.
    */
    'auto_income' => env('TREASURY_AUTO_INCOME', true),

    /*
    | Métodos de pago que se registran en la cuenta de Efectivo/Caja (tipo cash)
    | en lugar de una cuenta bancaria o billetera.
    */
    'excluded_payment_methods' => ['efectivo'],

    /*
    | Días hacia atrás para el backfill inicial de ingresos por venta.
    */
    'backfill_since' => env('TREASURY_BACKFILL_SINCE', '2020-01-01'),

    /*
    | Moneda del sistema. Todo registro sin moneda explícita se interpreta en
    | soles (decisión del proyecto: lo histórico sin moneda es PEN).
    */
    'base_currency' => 'PEN',

    /*
    | Diferencia máxima admitida al cerrar una conciliación bancaria.
    */
    'reconciliation_tolerance' => (float) env('TREASURY_RECONCILIATION_TOLERANCE', 0.01),

    /*
    | Pagos desde este monto requieren aprobación de otro usuario.
    | 0 desactiva la aprobación por umbral.
    */
    'approval_threshold' => (float) env('TREASURY_APPROVAL_THRESHOLD', 2000),

    /*
    | Si es true, quien aprobó una orden no puede ser quien la creó.
    */
    'require_different_approver' => (bool) env('TREASURY_REQUIRE_DIFFERENT_APPROVER', true),

    /*
    | Bloquea egresos que dejen saldo negativo en bancos y billeteras.
    | La caja de efectivo se controla aparte y no aplica.
    */
    'block_negative_balance' => (bool) env('TREASURY_BLOCK_NEGATIVE_BALANCE', true),

    /*
    | No se permiten movimientos manuales con fecha futura.
    */
    'allow_future_dates' => (bool) env('TREASURY_ALLOW_FUTURE_DATES', false),

    /*
    | Antigüedad máxima del tipo de cambio para operaciones en moneda extranjera.
    */
    'max_rate_age_days' => (int) env('TREASURY_MAX_RATE_AGE_DAYS', 3),

    /*
    | Previsión de caja: porcentaje de cobranza esperada en el escenario base.
    */
    'forecast_collection_ratio' => (float) env('TREASURY_FORECAST_COLLECTION_RATIO', 1.0),

    /*
    | Saldo mínimo de referencia para avisar en la previsión (en la moneda vista).
    */
    'forecast_min_balance' => (float) env('TREASURY_FORECAST_MIN_BALANCE', 0),

    /*
    | La previsión incluye las compras registradas con saldo pendiente del
    | módulo Compras (cuentas por pagar reales). Se descarta la compra cuyo
    | importe ya está comprometido en una orden de pago abierta.
    */
    'forecast_include_purchases' => (bool) env('TREASURY_FORECAST_INCLUDE_PURCHASES', true),

    /*
    | IGV de ventas: la previsión incluye el IGV de las boletas y facturas
    | emitidas que todavía no se pagó. El vencimiento es este día del mes
    | siguiente al periodo (SUNAT usa el día 15 en el régimen general).
    */
    'tax_igv_enabled' => (bool) env('TREASURY_TAX_IGV_ENABLED', true),
    'igv_due_day' => (int) env('TREASURY_IGV_DUE_DAY', 15),

    /*
    | Detracciones del IGV: las facturas desde este importe total están
    | sujetas. El porcentaje por defecto es 12 %; por factura puede guardarse
    | 10 % o 4 % según el servicio. El importe detraído se deposita en la
    | cuenta «Detracción» y rebaja el IGV por pagar del periodo.
    */
    'detraction_threshold' => (float) env('TREASURY_DETRACTION_THRESHOLD', 700),
    'detraction_percent' => (float) env('TREASURY_DETRACTION_PERCENT', 12),
];
