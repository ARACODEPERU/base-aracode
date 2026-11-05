<?php

namespace Modules\Treasury\Services;

use Illuminate\Support\Carbon;
use Modules\Treasury\Entities\TreasuryTransaction;

/**
 * Descripción de una operación financiera reportada por un módulo de origen.
 *
 * Es un objeto de transporte: no consulta ni escribe. El servicio de
 * integración decide qué hacer con él (resolver cuenta, convertir moneda,
 * validar y registrar en el libro).
 */
final class FinancialMovement
{
    public function __construct(
        public readonly string $module,
        public readonly string $sourceType,
        public readonly int $sourceId,
        public readonly string $operation,
        public readonly string $type,
        public readonly float $amount,
        public readonly string|Carbon $date,
        public readonly ?int $sourceLine = null,
        public readonly ?int $paymentMethodId = null,
        public readonly ?string $currency = null,
        public readonly ?float $exchangeRate = null,
        public readonly ?string $reference = null,
        public readonly ?string $description = null,
        public readonly ?int $treasuryAccountId = null,
        public readonly ?int $userId = null,
        public readonly array $meta = [],
        public readonly string $source = TreasuryTransaction::SOURCE_AUTO,
        public readonly ?int $reversalOfId = null,
    ) {
    }

    /**
     * Devuelve una copia forzando el tipo (útil para registrar devoluciones
     * reales de dinero como egreso sin duplicar la lógica de armado).
     */
    public function asType(string $type, ?string $operation = null): self
    {
        return new self(
            module: $this->module,
            sourceType: $this->sourceType,
            sourceId: $this->sourceId,
            operation: $operation ?? $this->operation,
            type: $type,
            amount: $this->amount,
            date: $this->date,
            sourceLine: $this->sourceLine,
            paymentMethodId: $this->paymentMethodId,
            currency: $this->currency,
            exchangeRate: $this->exchangeRate,
            reference: $this->reference,
            description: $this->description,
            treasuryAccountId: $this->treasuryAccountId,
            userId: $this->userId,
            meta: $this->meta,
            source: $this->source,
            reversalOfId: $this->reversalOfId,
        );
    }
}
