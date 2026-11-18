<?php

namespace Modules\Sales\Services\Sire;

use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\Purchases\Entities\PurcDocument;

/**
 * Registro de Compras Electrónico (RCE) del SIRE.
 *
 * Construye el archivo plano que reemplaza la propuesta de SUNAT (Anexo N.° 11
 * de la R.S. N.° 040-2022): 37 campos separados por "|" en el orden del anexo
 * (38 a 41 los completa SUNAT).
 *
 * En el RCE los comprobantes anulados no se anotan (a diferencia del RVIE, que
 * los anota en cero); las notas de crédito llevan sus importes en negativo.
 */
class SireRceComprasService
{
    /** Notas que modifican otro comprobante. */
    private const TIPOS_NOTA = ['07', '08'];

    /** Estado de anulación que graba la pantalla de compras. */
    private const ESTADO_ANULADO = 'A';

    /** Etiquetas para la previsualización. */
    private const ETIQUETAS = [
        '01' => 'Factura',
        '03' => 'Boleta',
        '07' => 'Nota de crédito',
        '08' => 'Nota de débito',
        '14' => 'Servicios públicos',
        '50' => 'DAM',
        '52' => 'DSI',
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
            'gravada' => 0.0,
            'igv' => 0.0,
            'no_gravada' => 0.0,
            'total' => 0.0,
        ];

        if (strlen($ruc) !== 11) {
            $observations[] = 'La empresa no tiene un RUC de 11 dígitos configurado: el registro no se puede presentar.';
        }

        foreach ($documents as $document) {
            $notes = [];
            $provider = $document->provider;
            $tipo = (string) ($document->type?->sunat_id);
            $reference = $references->get((int) $document->document_id);

            $fecha = SireArchivoPlano::date($document->date_of_issue ?: $document->created_at);

            if ($fecha === '') {
                $notes[] = 'Sin fecha de emisión registrada.';
            }

            $proveedorRuc = trim((string) ($provider?->number ?? ''));

            if (! $provider) {
                $notes[] = 'Sin proveedor asociado.';
            } elseif (strlen($proveedorRuc) !== 11) {
                $notes[] = 'El proveedor no tiene RUC de 11 dígitos.';
            }

            if (in_array($tipo, self::TIPOS_NOTA, true) && ! $reference) {
                $notes[] = 'La nota no tiene comprobante de referencia.';
            }

            $currency = strtoupper((string) ($document->currency_type_id ?: 'PEN'));
            $rate = $document->exchange_rate !== null ? (float) $document->exchange_rate : null;

            if ($currency !== 'PEN' && ! $rate) {
                $notes[] = 'Comprobante en moneda extranjera sin tipo de cambio registrado.';
            }

            if ($tipo === '') {
                $notes[] = 'Sin tipo de comprobante SUNAT.';
            }

            $importes = $this->amounts($document, $tipo);
            $numero = trim((string) $document->serie . '-' . (string) $document->number);

            $lines[] = implode('|', $this->fields(
                $period,
                $ruc,
                $razonSocial,
                $tipo,
                $proveedorRuc,
                (string) ($provider?->full_name ?: $provider?->short_name),
                $document,
                $reference,
                $fecha,
                $currency,
                $rate,
                $importes
            ));

            $rows[] = [
                'id' => $document->id,
                'fecha' => $fecha,
                'tipo' => $tipo,
                'tipo_etiqueta' => self::ETIQUETAS[$tipo] ?? 'Documento',
                'numero' => $numero,
                'cliente' => (string) ($provider?->full_name ?: $provider?->short_name),
                'cliente_documento' => trim('6 ' . $proveedorRuc),
                'moneda' => $currency,
                'total' => round($importes['total'], 2),
                'observaciones' => $notes,
            ];

            $totals['count']++;
            $totals['gravada'] += $importes['gravada'];
            $totals['igv'] += $importes['igv'];
            $totals['no_gravada'] += $importes['no_gravada'];
            $totals['total'] += $importes['total'];
        }

        foreach ($rows as $row) {
            foreach ($row['observaciones'] as $note) {
                $observations[] = $row['numero'] . ': ' . $note;
            }
        }

        $hasOperations = $totals['count'] > 0;
        $content = SireArchivoPlano::content($lines);

        // Errores que SUNAT rechazaría: bloquean la descarga del TXT/ZIP. Las
        // observaciones de arriba siguen siendo advertencias.
        $errors = app(SireValidador::class)->validar('compras', $content, $ruc);

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
            'fileName' => SireArchivoPlano::fileName(SireArchivoPlano::LIBRO_COMPRAS, $ruc, $period, $hasOperations),
            // El TXT completo solo se arma para descargar.
            'content' => $previewLimit === null ? $content : null,
        ];
    }

    /**
     * Comprobantes del período, sin los anulados: `status = 'A'` es el estado
     * que graba la anulación en la pantalla de compras.
     *
     * @return Collection<int, PurcDocument>
     */
    private function documentsOfPeriod(Carbon $inicio, Carbon $fin): Collection
    {
        return PurcDocument::query()
            ->with(['provider', 'type'])
            ->whereBetween('date_of_issue', [$inicio->toDateString(), $fin->toDateString()])
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhere('status', '<>', self::ESTADO_ANULADO);
            })
            ->orderBy('date_of_issue')
            ->orderBy('id')
            ->get();
    }

    /**
     * Comprobantes referenciados por las notas del período.
     *
     * @param  Collection<int, PurcDocument>  $documents
     * @return Collection<int, PurcDocument>
     */
    private function references(Collection $documents): Collection
    {
        $ids = $documents
            ->filter(fn (PurcDocument $document) => in_array((string) ($document->type?->sunat_id), self::TIPOS_NOTA, true))
            ->pluck('document_id')
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return PurcDocument::query()->with('type')->whereIn('id', $ids)->get()->keyBy('id');
    }

    /**
     * Importes del RCE: base e IGV con derecho a crédito, adquisiciones no
     * gravadas (exoneradas + inafectas + exportación), ISC y otros tributos.
     * Las notas de crédito van en negativo.
     *
     * @return array<string, float>
     */
    private function amounts(PurcDocument $document, string $tipo): array
    {
        $sign = $tipo === '07' ? -1 : 1;
        $value = fn ($field) => round($sign * (float) $document->{$field}, 2);

        return [
            'gravada' => $value('total_taxed'),
            'igv' => $value('total_igv'),
            'no_gravada' => round($sign * (
                (float) $document->total_exonerated
                + (float) $document->total_unaffected
                + (float) $document->total_exportation
                + (float) $document->total_free
            ), 2),
            'isc' => $value('total_isc'),
            'otros' => $value('total_other_taxes'),
            'total' => round($sign * (float) $document->total, 2),
        ];
    }

    /**
     * Los 37 campos del Anexo N.° 11 (38 a 41 los completa SUNAT).
     *
     * @param  array<string, float>  $importes
     * @return array<int, string>
     */
    private function fields(
        string $period,
        string $ruc,
        string $razonSocial,
        string $tipo,
        string $proveedorRuc,
        string $proveedorNombre,
        PurcDocument $document,
        ?PurcDocument $reference,
        string $fecha,
        string $currency,
        ?float $rate,
        array $importes
    ): array {
        return [
            $ruc,                                                     // 1 RUC
            $razonSocial,                                             // 2 Razón social
            str_replace('-', '', $period),                            // 3 Periodo
            '',                                                       // 4 CAR
            $fecha,                                                   // 5 Fecha emisión
            SireArchivoPlano::date($document->date_of_due),           // 6 Vencimiento o pago
            $tipo,                                                    // 7 Tipo de comprobante
            (string) $document->serie,                                // 8 Serie
            '',                                                       // 9 Año DAM/DSI
            (string) $document->number,                               // 10 Número inicial
            '',                                                       // 11 Número final
            '6',                                                      // 12 Tipo doc. proveedor
            $proveedorRuc,                                            // 13 Nro doc. proveedor
            SireArchivoPlano::text($proveedorNombre),                 // 14 Proveedor
            SireArchivoPlano::money($importes['gravada']),            // 15 BI gravada con crédito
            SireArchivoPlano::money($importes['igv']),                // 16 IGV de la anterior
            SireArchivoPlano::money(0),                               // 17 BI gravada DGNG
            SireArchivoPlano::money(0),                               // 18 IGV DGNG
            SireArchivoPlano::money(0),                               // 19 BI gravada sin crédito
            SireArchivoPlano::money(0),                               // 20 IGV sin crédito
            SireArchivoPlano::money($importes['no_gravada']),         // 21 No gravadas
            SireArchivoPlano::money($importes['isc']),                // 22 ISC
            SireArchivoPlano::money(0),                               // 23 ICBPER
            SireArchivoPlano::money($importes['otros']),              // 24 Otros tributos
            SireArchivoPlano::money($importes['total']),              // 25 Total
            $currency,                                                // 26 Moneda
            SireArchivoPlano::exchangeRate($currency, $rate),         // 27 Tipo de cambio
            $reference ? SireArchivoPlano::date($reference->date_of_issue ?: $reference->created_at) : '', // 28
            $reference ? (string) ($reference->type?->sunat_id) : '', // 29
            $reference ? (string) $reference->serie : '',             // 30
            '',                                                       // 31 Código DAM/DSI
            $reference ? (string) $reference->number : '',            // 32
            '',                                                       // 33 Clasificación bienes/servicios
            '',                                                       // 34 Contrato o proyecto
            '',                                                       // 35 Porcentaje de participación
            SireArchivoPlano::money(0),                               // 36 IMB
            '',                                                       // 37 CAR del comprobante modificado
        ];
    }
}
