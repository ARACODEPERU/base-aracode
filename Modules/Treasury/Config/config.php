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
    | Métodos de pago excluidos del libro de tesorería por descripción.
    | El efectivo se maneja en caja chica (PettyCash), no en bancos.
    */
    'excluded_payment_methods' => ['efectivo'],

    /*
    | Días hacia atrás para el backfill inicial de ingresos por venta.
    */
    'backfill_since' => env('TREASURY_BACKFILL_SINCE', '2020-01-01'),
];
