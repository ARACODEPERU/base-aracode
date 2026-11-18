<?php

namespace Modules\Sales\Http\Controllers;

use Modules\Sales\Services\Sire\SireRvieVentasService;

/**
 * Registro de Ventas e Ingresos Electrónico (RVIE) del SIRE.
 *
 * Solo lee comprobantes del período y arma el archivo plano: no modifica
 * documentos ni envía nada a SUNAT.
 */
class SireRvieVentasController extends SireRegistroController
{
    public function __construct(private SireRvieVentasService $rvie)
    {
    }

    protected function build(string $period, ?int $previewLimit = null): array
    {
        return $this->rvie->build($period, $previewLimit);
    }

    protected function tipo(): string
    {
        return 'ventas';
    }

    protected function indexRoute(): string
    {
        return 'sire_rvie_ventas_index';
    }
}
