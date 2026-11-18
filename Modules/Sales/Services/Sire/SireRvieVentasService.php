<?php

namespace Modules\Sales\Services\Sire;

use App\Models\Company;
use App\Models\SaleDocument;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Registro de Ventas e Ingresos Electrónico (RVIE) del SIRE.
 *
 * Construye el archivo plano que reemplaza la propuesta de SUNAT (Anexo N.° 3
 * de la R.S. N.° 112-2021): un comprobante por línea, 33 campos separados por
 * "|", fechas DD/MM/AAAA, importes con dos decimales y comprobantes anulados o
 * rechazados anotados en cero.
 *
 * No envía nada a SUNAT: el archivo se sube al portal SOL o al aplicativo
 * cliente SIRE, donde SUNAT valida la estructura y muestra lo que incumpla.
 */
class SireRvieVentasService
{
    /** Tipos de comprobante que forman el registro de ventas. */
    private const TIPOS_INCLUIDOS = ['01', '03', '07', '08'];

    /** Notas que modifican otro comprobante. */
    private const TIPOS_NOTA = ['07', '08'];

    /** Estados en los que el comprobante se anota con importe cero. */
    private const ESTADOS_EN_CERO = ['Anulada', 'Por anular', 'Rechazada'];

    /** Etiquetas para la previsualización. */
    private const ETIQUETAS = [
        '01' => 'Factura',
        '03' => 'Boleta',
        '07' => 'Nota de crédito',
        '08' => 'Nota de débito',
    ];

    /**
     * Arma el registro del período indicado (formato YYYY-MM).
     *
     * @return array<string, mixed>
     */
    public function build(string $period, ?int $previewLimit = null): array
    {
        $inicio = Carbon::createFromFormat('Y-m', $period)->startOfMonth();
        $fin = $inicio->copy()->endOfMonth();

        $company = Company::query()->first();
        $ruc = (string) preg_replace('/\D/', '', (string) $company?->ruc);
        $razonSocial = SireArchivoPlano::text((string) ($company?->business_name ?: $company?->name));

        $documents = $this->documentsOfPeriod($inicio, $fin);
        $references = $this->references($documents);

        $observations = [];
        $rows = [];
        $lines = [];
        $totals = [
            'count' => 0,
            'zeroed' => 0,
            'gravada' => 0.0,
            'igv' => 0.0,
            'exonerada' => 0.0,
            'inafecta' => 0.0,
            'exportacion' => 0.0,
            'total' => 0.0,
        ];

        if ($ruc === '' || strlen($ruc) !== 11) {
            $observations[] = 'La empresa no tiene un RUC de 11 dígitos configurado: el registro no se puede presentar.';
        }

        foreach ($documents as $document) {
            $notes = [];
            $zero = $this->isZeroed($document);
            $reference = $references->get((int) $document->document_id);

            $fecha = SireArchivoPlano::date($document->invoice_broadcast_date ?: $document->created_at);

            if ($fecha === '') {
                $notes[] = 'Sin fecha de emisión registrada.';
            }

            $due = SireArchivoPlano::date($document->invoice_due_date);

            if (in_array((string) $document->invoice_type_doc, self::TIPOS_NOTA, true) && ! $reference) {
                $notes[] = 'La nota no tiene comprobante de referencia: SUNAT exige tipo, serie, número y fecha del documento modificado.';
            }

            $currency = strtoupper((string) ($document->invoice_type_currency ?: 'PEN'));
            $rate = $document->exchange_rate !== null ? (float) $document->exchange_rate : null;

            if ($currency !== 'PEN' && ! $rate) {
                $notes[] = 'Comprobante en moneda extranjera sin tipo de cambio registrado.';
            }

            $total = (float) ($document->overall_total ?: $document->invoice_mto_imp_sale);

            if ((string) $document->invoice_type_doc === '03'
                && $total >= 700
                && trim((string) $document->client_number) === '') {
                $notes[] = 'Boleta de S/ 700.00 o más sin documento de identidad del cliente.';
            }

            if ((string) $document->invoice_type_doc === '01' && strlen(trim((string) $document->client_number)) !== 11) {
                $notes[] = 'Factura sin RUC válido del cliente.';
            }

            $notaAnterior = $this->isNoteOfPreviousPeriod($document, $reference, $period);
            $importes = $this->amounts($document, $zero, $notaAnterior);
            $line = implode('|', $this->fields(
                $period,
                $ruc,
                $razonSocial,
                $document,
                $reference,
                $fecha,
                $due,
                $currency,
                $rate,
                $importes
            ));

            $rows[] = [
                'id' => $document->id,
                'fecha' => $fecha,
                'tipo' => (string) $document->invoice_type_doc,
                'tipo_etiqueta' => self::ETIQUETAS[(string) $document->invoice_type_doc] ?? 'Documento',
                'numero' => trim((string) $document->invoice_serie . '-' . (string) $document->invoice_correlative),
                'cliente' => (string) $document->client_rzn_social,
                'cliente_documento' => trim((string) $document->client_type_doc . ' ' . (string) $document->client_number),
                'moneda' => $currency,
                'total' => round($zero ? 0 : $total, 2),
                'en_cero' => $zero,
                'observaciones' => $notes,
            ];

            $lines[] = $line;
            $totals['count']++;

            if ($zero) {
                $totals['zeroed']++;
            }

            $totals['gravada'] += $importes['gravada'];
            $totals['igv'] += $importes['igv'];
            $totals['exonerada'] += $importes['exonerada'];
            $totals['inafecta'] += $importes['inafecta'];
            $totals['exportacion'] += $importes['exportacion'];
            $totals['total'] += $importes['total'];
        }

        foreach ($rows as $row) {
            foreach ($row['observaciones'] as $note) {
                $observations[] = $row['numero'] . ': ' . $note;
            }
        }

        $hasOperations = $rows !== [];
        $content = SireArchivoPlano::content($lines);

        // Errores que SUNAT rechazaría: bloquean la descarga del TXT/ZIP. Las
        // observaciones de arriba siguen siendo advertencias.
        $errors = app(SireValidador::class)->validar('ventas', $content, $ruc);

        return [
            'rows' => $previewLimit === null ? $rows : array_slice($rows, 0, $previewLimit),
            'rowsTotal' => count($rows),
            'previewLimit' => $previewLimit,
            'totals' => $totals,
            'observations' => $observations,
            'errors' => $errors,
            'canDownload' => $errors === [],
            'companyRuc' => $ruc,
            'hasOperations' => $hasOperations,
            'ready' => strlen($ruc) === 11,
            'fileName' => SireArchivoPlano::fileName(SireArchivoPlano::LIBRO_VENTAS, $ruc, $period, $hasOperations),
            // El TXT completo solo se arma para descargar: la pantalla recibe
            // una vista previa acotada y nada de contenido masivo en los props.
            'content' => $previewLimit === null ? $content : null,
        ];
    }

    /**
     * @return Collection<int, SaleDocument>
     */
    private function documentsOfPeriod(Carbon $inicio, Carbon $fin): Collection
    {
        return SaleDocument::query()
            ->whereIn('invoice_type_doc', self::TIPOS_INCLUIDOS)
            ->where(function ($query) use ($inicio, $fin) {
                $query->whereBetween('invoice_broadcast_date', [$inicio->toDateString(), $fin->toDateString()])
                    ->orWhere(function ($inner) use ($inicio, $fin) {
                        $inner->whereNull('invoice_broadcast_date')
                            ->whereBetween('created_at', [$inicio->copy()->startOfDay(), $fin->copy()->endOfDay()]);
                    });
            })
            ->orderBy('invoice_broadcast_date')
            ->orderBy('id')
            ->get();
    }

    /**
     * Comprobantes referenciados por las notas del período (por `document_id`).
     *
     * @param  Collection<int, SaleDocument>  $documents
     * @return Collection<int, SaleDocument>
     */
    private function references(Collection $documents): Collection
    {
        $ids = $documents
            ->filter(fn (SaleDocument $document) => in_array((string) $document->invoice_type_doc, self::TIPOS_NOTA, true))
            ->pluck('document_id')
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return SaleDocument::query()->whereIn('id', $ids)->get()->keyBy('id');
    }

    /**
     * Un comprobante anulado, por anular o rechazado se anota con importe cero.
     */
    private function isZeroed(SaleDocument $document): bool
    {
        return in_array((string) $document->invoice_status, self::ESTADOS_EN_CERO, true)
            || (string) $document->status === '3';
    }

    /**
     * Importes del comprobante en el orden del Anexo N.° 3.
     *
     * Reglas del anexo (campos 15 a 18 y preguntas frecuentes de SUNAT):
     *  - Comprobante anulado o rechazado: todo en 0.00.
     *  - Nota de crédito del mismo período: la base y el IGV van en negativo en
     *    los campos 15 y 17.
     *  - Nota de crédito que modifica un comprobante de un período anterior: la
     *    base y el IGV van en negativo en los campos 16 (descuento de base) y 18
     *    (descuento de IGV), y los campos 15 y 17 quedan en cero.
     *  - El descuento global del comprobante ya está reflejado en la base: el
     *    campo 16 solo se usa para el caso anterior.
     *
     * @return array<string, float>
     */
    private function amounts(SaleDocument $document, bool $zero, bool $notaAnterior = false): array
    {
        $isNote = (string) $document->invoice_type_doc === '07';
        $sign = $isNote ? -1 : 1;
        $value = fn ($field) => $zero ? 0.0 : round($sign * (float) $document->{$field}, 2);

        $base = $zero ? 0.0 : round(abs((float) $document->invoice_mto_oper_taxed), 2);
        $igv = $zero ? 0.0 : round(abs((float) $document->invoice_mto_igv), 2);

        return [
            'exportacion' => $value('invoice_mto_oper_export'),
            'gravada' => $notaAnterior ? 0.0 : $value('invoice_mto_oper_taxed'),
            'descuento' => $notaAnterior ? -$base : 0.0,
            'igv' => $notaAnterior ? 0.0 : $value('invoice_mto_igv'),
            'descuento_igv' => $notaAnterior ? -$igv : 0.0,
            'exonerada' => $value('invoice_mto_oper_exonerated'),
            'inafecta' => $value('invoice_mto_oper_unaffected'),
            'icbper' => $value('invoice_icbper'),
            'total' => $zero ? 0.0 : round($sign * (float) ($document->overall_total ?: $document->invoice_mto_imp_sale), 2),
        ];
    }

    /**
     * Una nota de crédito que modifica un comprobante emitido en un período
     * anterior se anota en los campos de descuento (16 y 18).
     */
    private function isNoteOfPreviousPeriod(SaleDocument $document, ?SaleDocument $reference, string $period): bool
    {
        if ((string) $document->invoice_type_doc !== '07' || ! $reference) {
            return false;
        }

        $fecha = $reference->invoice_broadcast_date ?: $reference->created_at;

        if (empty($fecha)) {
            return false;
        }

        return Carbon::parse($fecha)->format('Y-m') < $period;
    }

    /**
     * Los 33 campos del Anexo N.° 3 que envía el contribuyente (34 a 40 los
     * completa SUNAT).
     *
     * @param  array<string, float>  $importes
     * @return array<int, string>
     */
    private function fields(
        string $period,
        string $ruc,
        string $razonSocial,
        SaleDocument $document,
        ?SaleDocument $reference,
        string $fecha,
        string $due,
        string $currency,
        ?float $rate,
        array $importes
    ): array {
        return [
            $ruc,                                             // 1 RUC
            $razonSocial,                                     // 2 Razón social
            str_replace('-', '', $period),                    // 3 Periodo
            '',                                               // 4 CAR (lo completa SUNAT)
            $fecha,                                           // 5 Fecha de emisión
            $due,                                             // 6 Vencimiento o pago
            (string) $document->invoice_type_doc,             // 7 Tipo de comprobante
            (string) $document->invoice_serie,                // 8 Serie
            (string) $document->invoice_correlative,          // 9 Número inicial
            '',                                               // 10 Número final
            (string) $document->client_type_doc,              // 11 Tipo doc. identidad
            (string) $document->client_number,                // 12 Nro doc. identidad
            SireArchivoPlano::text((string) $document->client_rzn_social), // 13 Cliente
            SireArchivoPlano::money($importes['exportacion']),           // 14 Valor exportación
            SireArchivoPlano::money($importes['gravada']),               // 15 Base gravada
            SireArchivoPlano::money($importes['descuento']),             // 16 Descuento base
            SireArchivoPlano::money($importes['igv']),                   // 17 IGV/IPM
            SireArchivoPlano::money($importes['descuento_igv']),         // 18 Descuento IGV
            SireArchivoPlano::money($importes['exonerada']),             // 19 Exonerada
            SireArchivoPlano::money($importes['inafecta']),              // 20 Inafecta
            SireArchivoPlano::money(0),                                  // 21 ISC
            SireArchivoPlano::money(0),                                  // 22 Base IVAP
            SireArchivoPlano::money(0),                                  // 23 IVAP
            SireArchivoPlano::money($importes['icbper']),                // 24 ICBPER
            SireArchivoPlano::money(0),                                  // 25 Otros tributos
            SireArchivoPlano::money($importes['total']),                 // 26 Total
            $currency,                                        // 27 Moneda
            SireArchivoPlano::exchangeRate($currency, $rate),            // 28 Tipo de cambio
            $reference ? SireArchivoPlano::date($reference->invoice_broadcast_date ?: $reference->created_at) : '', // 29
            $reference ? (string) $reference->invoice_type_doc : '',  // 30
            $reference ? (string) $reference->invoice_serie : '',     // 31
            $reference ? (string) $reference->invoice_correlative : '', // 32
            '',                                               // 33 Contrato o proyecto
        ];
    }

}
