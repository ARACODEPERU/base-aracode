<?php

namespace Modules\Sales\Services;

use App\Models\Expense;
use App\Models\Sale;
use App\Models\SaleDocument;
use Illuminate\Support\Facades\DB;

class PettyCashReportService
{
    /**
     * Etiquetas de respaldo por codigo de comprobante electronico.
     */
    private const TYPE_LABELS = [
        '01' => 'Factura Electrónica',
        '03' => 'Boleta Electrónica',
        '80' => 'Ticket',
    ];

    /**
     * Documentos de una caja chica con montos NETOS:
     * bases (facturas, boletas, tickets y fisicos) - notas de credito + notas de debito.
     *
     * Reglas:
     *  - Solo cuentan las ventas activas (sales.status = 1) con documento valido
     *    (no rechazado por SUNAT ni anulado).
     *  - Las notas de credito y debito Aceptadas ajustan la caja. Las notas que
     *    anulan la operacion completa (documento afectado en "Por anular") no se
     *    descuentan porque la venta base ya queda excluida: son el mecanismo de
     *    la anulacion, no un ajuste adicional.
     *  - Las notas emitidas en USD se convierten a soles con el tipo de cambio
     *    registrado en la nota (o en el documento afectado).
     *
     * Es la unica fuente de verdad para el reporte y para el cierre de caja.
     */
    public function build(int $pettyCashId): array
    {
        $sales = Sale::with(['establishment', 'document.serie.documentType', 'physicalDocument.saleDocumentType'])
            ->where('sales.petty_cash_id', $pettyCashId)
            ->orderBy('sales.id', 'desc')
            ->get();

        $electronic = [];
        $tickets = [];
        $physicals = [];
        $excluded = [];
        $gross = 0.0;

        foreach ($sales as $sale) {
            $doc = $sale->document;
            $pdoc = $sale->physicalDocument;
            $local = $sale->establishment->description ?? '-';

            if ($sale->physical == 3) {
                $row = [
                    'sale_id' => $sale->id,
                    'fecha' => (string) $sale->sale_date,
                    'local' => $local,
                    'numero' => $pdoc ? trim($pdoc->serie . ' - ' . $pdoc->correlative) : '-',
                    'tipo' => $pdoc?->saleDocumentType?->description ?? 'Documento Físico',
                    'estado' => $pdoc?->status,
                    'monto' => (float) $sale->total,
                ];

                $valido = $sale->status == 1
                    && $pdoc
                    && in_array((string) $pdoc->document_type, ['1', '2'], true)
                    && $pdoc->status != 'A';

                if ($valido) {
                    $physicals[] = $row;
                    $gross += $row['monto'];
                } else {
                    $excluded[] = $row + ['motivo_exclusion' => $this->exclusionReason($sale, $pdoc?->status)];
                }

                continue;
            }

            $typeDoc = (string) ($doc->invoice_type_doc ?? '');
            $row = [
                'sale_id' => $sale->id,
                'fecha' => (string) $sale->sale_date,
                'local' => $local,
                'numero' => $doc ? ($doc->invoice_serie . '-' . $doc->invoice_correlative) : '-',
                'tipo' => $doc?->serie?->documentType?->description
                    ?? self::TYPE_LABELS[$typeDoc]
                    ?? 'Comprobante',
                'estado' => $doc->invoice_status ?? null,
                'monto' => (float) $sale->total,
            ];

            $esTicket = $sale->physical == 1 && $typeDoc === '80';
            $esElectronico = $sale->physical == 2 && in_array($typeDoc, ['01', '03'], true);

            $valido = $sale->status == 1
                && $doc
                && $doc->status == 1
                && ($esTicket || $esElectronico)
                && $doc->invoice_status !== 'Rechazada';

            if ($valido) {
                if ($esTicket) {
                    $tickets[] = $row;
                } else {
                    $electronic[] = $row;
                }
                $gross += $row['monto'];
            } else {
                $excluded[] = $row + ['motivo_exclusion' => $this->exclusionReason($sale, null)];
            }
        }

        [$notes, $credits, $debits] = $this->buildNotes($pettyCashId);

        $expenses = Expense::where('petty_cash_id', $pettyCashId)->orderBy('id')->get();
        $expensesTotal = round((float) $expenses->sum('amount'), 2);

        $netSales = round($gross - $credits + $debits, 2);

        return [
            'electronic' => $electronic,
            'tickets' => $tickets,
            'physicals' => $physicals,
            'notes' => $notes,
            'excluded' => $excluded,
            'expenses' => $expenses->map(fn ($e) => [
                'id' => $e->id,
                'document' => $e->document,
                'description' => $e->description,
                'amount' => (float) $e->amount,
            ])->all(),
            'totals' => [
                'electronic' => round(collect($electronic)->sum('monto'), 2),
                'tickets' => round(collect($tickets)->sum('monto'), 2),
                'physicals' => round(collect($physicals)->sum('monto'), 2),
                'gross' => round($gross, 2),
                'credits' => $credits,
                'debits' => $debits,
                'net_sales' => $netSales,
                'expenses' => $expensesTotal,
                'counts' => [
                    'electronic' => count($electronic),
                    'tickets' => count($tickets),
                    'physicals' => count($physicals),
                    'credits' => collect($notes)->where('tipo', 'Nota de Crédito')->where('afecta_caja', true)->count(),
                    'debits' => collect($notes)->where('tipo', 'Nota de Débito')->where('afecta_caja', true)->count(),
                    'excluded' => count($excluded),
                ],
            ],
        ];
    }

    /**
     * Notas de credito y debito de la caja con su impacto en soles.
     *
     * @return array [filas, totalCreditos, totalDebitos]
     */
    private function buildNotes(int $pettyCashId): array
    {
        $notes = SaleDocument::with(['sale.establishment'])
            ->whereHas('sale', fn ($q) => $q->where('sales.petty_cash_id', $pettyCashId))
            ->whereIn('invoice_type_doc', ['07', '08'])
            ->orderBy('id')
            ->get();

        if ($notes->isEmpty()) {
            return [[], 0.0, 0.0];
        }

        $affected = SaleDocument::whereIn('id', $notes->pluck('document_id')->filter()->unique())
            ->get()
            ->keyBy('id');

        $creditTypes = DB::table('sunat_note_credit_types')->pluck('description', 'id');
        $debitTypes = DB::table('sunat_note_debit_types')->pluck('description', 'id');

        $rows = [];
        $totalCredits = 0.0;
        $totalDebits = 0.0;

        foreach ($notes as $note) {
            $isCredit = $note->invoice_type_doc === '07';
            $afectada = $affected->get($note->document_id);
            $esAnulacionTotal = $afectada && $afectada->invoice_status === 'Por anular';
            $aceptada = $note->invoice_status === 'Aceptada';

            $monto = (float) $note->invoice_mto_imp_sale;
            $moneda = (string) $note->invoice_type_currency;

            $montoSoles = $monto;
            if ($moneda === 'USD') {
                $rate = (float) ($note->exchange_rate ?: ($afectada->exchange_rate ?? null) ?: 1);
                $montoSoles = $rate > 0 ? $monto / $rate : $monto;
            }

            $afectaCaja = $aceptada && !$esAnulacionTotal;

            if ($afectaCaja) {
                if ($isCredit) {
                    $totalCredits += $montoSoles;
                } else {
                    $totalDebits += $montoSoles;
                }
            }

            $catalogo = $isCredit ? $creditTypes : $debitTypes;
            $motivoDescripcion = $catalogo[$note->note_type_operation_id] ?? null;

            $rows[] = [
                'id' => $note->id,
                'fecha' => (string) $note->invoice_broadcast_date,
                'local' => $note->sale?->establishment?->description ?? '-',
                'numero' => $note->invoice_serie . '-' . $note->invoice_correlative,
                'tipo' => $isCredit ? 'Nota de Crédito' : 'Nota de Débito',
                'motivo' => trim(($note->note_type_operation_id ?? '') . ' ' . ($motivoDescripcion ?? '')),
                'descripcion' => $note->reason_cancellation,
                'doc_afectado' => $afectada
                    ? $afectada->invoice_serie . '-' . $afectada->invoice_correlative
                    : '-',
                'estado' => $note->invoice_status,
                'moneda' => $moneda,
                'monto' => $monto,
                'monto_soles' => round($montoSoles, 2),
                'afecta_caja' => $afectaCaja,
                'sin_impacto' => $esAnulacionTotal
                    ? 'Anulación total (venta ya excluida)'
                    : (!$aceptada ? 'No aceptada por SUNAT' : null),
            ];
        }

        return [$rows, round($totalCredits, 2), round($totalDebits, 2)];
    }

    /**
     * Motivo por el cual un documento queda excluido del calculo.
     */
    private function exclusionReason(Sale $sale, ?string $physicalStatus): string
    {
        if ($sale->status != 1) {
            return 'Venta anulada';
        }

        $doc = $sale->document;

        if ($physicalStatus === 'A') {
            return 'Documento físico anulado';
        }

        if ($doc && $doc->invoice_status === 'Rechazada') {
            return 'Rechazada por SUNAT';
        }

        if ($doc && $doc->status != 1) {
            return 'Documento anulado';
        }

        return 'No válido';
    }
}
