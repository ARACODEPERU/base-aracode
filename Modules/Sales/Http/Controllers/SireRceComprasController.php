<?php

namespace Modules\Sales\Http\Controllers;

use Modules\Sales\Services\Sire\SireRceComprasService;

/**
 * Registro de Compras Electrónico (RCE) del SIRE.
 *
 * Solo lee comprobantes de compra del período y arma el archivo plano: no
 * modifica documentos ni envía nada a SUNAT.
 */
class SireRceComprasController extends SireRegistroController
{
    public function __construct(private SireRceComprasService $rce)
    {
    }

    protected function build(string $period, ?int $previewLimit = null): array
    {
        return $this->rce->build($period, $previewLimit);
    }

    protected function tipo(): string
    {
        return 'compras';
    }

    protected function indexRoute(): string
    {
        return 'sire_rce_compras_index';
    }
}
