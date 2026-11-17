<?php

namespace Modules\Treasury\Exceptions;

use RuntimeException;

/**
 * Error de dominio de Tesorería.
 *
 * Se usa para casos que el usuario debe ver tal cual: periodo cerrado,
 * movimiento conciliado, saldo insuficiente, moneda sin tipo de cambio.
 * El mensaje está escrito en español para mostrarse en la interfaz.
 */
class TreasuryOperationException extends RuntimeException
{
    public static function closedPeriod(string $date): self
    {
        return new self("El periodo de {$date} está cerrado. Reabre el periodo o registra el movimiento en una fecha abierta.");
    }

    public static function reconciled(): self
    {
        return new self('El movimiento pertenece a una conciliación cerrada: no se puede editar, anular ni borrar. Registra un ajuste.');
    }

    public static function negativeBalance(string $account, float $balance): self
    {
        return new self("La operación dejaría la cuenta {$account} con saldo negativo (" . number_format($balance, 2, '.', '') . '). Registra un ingreso o desactiva el bloqueo de sobregiro.');
    }

    public static function missingRate(string $from, string $to): self
    {
        return new self("No hay tipo de cambio vigente para convertir {$from} a {$to}.");
    }

    public static function staleRate(string $currency, int $days): self
    {
        return new self("El tipo de cambio de {$currency} tiene más de {$days} días de antigüedad. Actualízalo antes de operar.");
    }

    public static function currencyMismatch(string $expected, string $given): self
    {
        return new self("La moneda de la operación ({$given}) no coincide con la de la cuenta ({$expected}).");
    }

    public static function invalidStatus(string $status, string $action): self
    {
        return new self("La orden está en estado «{$status}»: no se puede {$action}.");
    }
}
