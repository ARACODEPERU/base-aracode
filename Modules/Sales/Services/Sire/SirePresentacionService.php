<?php

namespace Modules\Sales\Services\Sire;

use App\Models\Company;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Sales\Entities\SireSubmission;
use Throwable;

/**
 * Presentación del SIRE ante SUNAT: comparar la propuesta con el ERP,
 * reemplazarla y aceptarla.
 *
 * El SIRE no tiene ambiente de pruebas, así que cada acción de escritura se
 * registra en `sire_submissions` con su ticket y en la bitácora de llamadas.
 * El reemplazo nunca se envía a ciegas: si existe una propuesta descargada, se
 * compara antes y las diferencias tienen que aprobarse de forma explícita.
 */
class SirePresentacionService
{
    private const REEMPLAZAR = '/v1/contribuyente/migeigv/libros/rvierce/receptorpropuesta/web/propuesta/upload';

    private const ACEPTAR = [
        'ventas' => '/v1/contribuyente/migeigv/libros/rvie/propuesta/web/registroslibros/%s/aceptarpropuesta',
        'compras' => '/v1/contribuyente/migeigv/libros/rce/propuesta/web/registroslibros/%s/aceptarpropuesta',
    ];

    private const CONSULTA_TICKET = '/v1/contribuyente/migeigv/libros/rvierce/gestionprocesosmasivos/web/masivo/consultaestadotickets';

    private const LIBRO = [
        'ventas' => '140000',
        'compras' => '080000',
    ];

    private const PROCESO = [
        'ventas' => '3',
        'compras' => '61',
    ];

    private const ETIQUETAS_ESTADO = [
        '01' => 'Solicitado',
        '05' => 'En proceso',
        '06' => 'Terminado',
        'EXP' => 'Expirado',
    ];

    public function __construct(
        private SireApiClient $client,
        private SireRvieVentasService $ventas,
        private SireRceComprasService $compras,
        private SireComparadorService $comparador,
    ) {
    }

    /**
     * Compara el registro del ERP contra la propuesta de SUNAT del período.
     * Si no hay propuesta descargada, la descarga primero (solo lectura).
     *
     * @return array<string, mixed>
     */
    public function comparar(Company $company, string $tipo, string $period, ?int $userId = null): array
    {
        $tipo = $this->tipo($tipo);
        $archivo = $this->archivo($tipo, $period);

        if ($archivo['errors'] !== []) {
            return [
                'ok' => false,
                'bloqueado' => true,
                'resumen' => null,
                'errors' => array_slice($archivo['errors'], 0, 50),
                'message' => 'El registro del ERP tiene ' . count($archivo['errors'])
                    . ' error(es) que impiden compararlo o presentarlo. Corrígelos primero.',
            ];
        }

        $propuesta = $this->propuesta($company, $tipo, $period, $userId);

        if ($propuesta === null) {
            return [
                'ok' => false,
                'pendiente' => true,
                'resumen' => null,
                'message' => 'SUNAT todavía está generando la propuesta del período. Vuelve a comparar en unos minutos.',
            ];
        }

        $resultado = $this->comparador->comparar($tipo, (string) $archivo['content'], $propuesta['content']);

        return $resultado + [
            'ok' => $resultado['ok'],
            'tipo' => $tipo,
            'period' => $period,
            'archivo_erp' => $archivo['fileName'],
            'archivo_sunat' => $propuesta['archivo'],
            'submission_id' => $propuesta['submission_id'],
        ];
    }

    /**
     * Reemplaza la propuesta del período con el registro del ERP.
     *
     * @return array<string, mixed>
     */
    public function reemplazar(
        Company $company,
        string $tipo,
        string $period,
        bool $aceptarDiferencias = false,
        ?int $userId = null
    ): array {
        $tipo = $this->tipo($tipo);
        $archivo = $this->archivo($tipo, $period);

        if ($archivo['errors'] !== []) {
            throw new SireApiException(
                'No se puede reemplazar: ' . $archivo['errors'][0]
                . ' El archivo tiene ' . count($archivo['errors']) . ' error(es); corrígelos y vuelve a intentarlo.'
            );
        }

        if (! $this->client->credencialesCompletas($company)) {
            throw new SireApiException(
                'Faltan las credenciales del SIRE. Carga el client_id, el client_secret y el usuario y clave SOL en Configuración → SIRE.'
            );
        }

        // Comparación previa: con una propuesta descargada no se envía a ciegas.
        $propuesta = $this->propuestaExistente($company, $tipo, $period);
        $comparacion = null;

        if ($propuesta !== null) {
            $comparacion = $this->comparador->comparar($tipo, (string) $archivo['content'], $propuesta['content']);

            if (! $comparacion['ok'] && ! $aceptarDiferencias) {
                throw new SireApiException(
                    'Antes de reemplazar revisa la comparación: ' . $comparacion['message']
                    . ' Si quieres enviarlo igual, confirma que aceptas las diferencias.',
                    null,
                    $comparacion
                );
            }
        }

        $periodo = str_replace('-', '', $period);

        $submission = SireSubmission::create([
            'company_id' => $company->getKey(),
            'user_id' => $userId,
            'tipo' => $tipo,
            'period' => $periodo,
            'action' => 'reemplazar',
            'status' => 'solicitado',
            'file_name' => (string) $archivo['fileName'],
            'file_hash' => hash('sha256', (string) $archivo['content']),
            'lines' => (int) $archivo['rowsTotal'],
            'started_at' => now(),
        ]);

        try {
            $zip = SireArchivoPlano::zip((string) $archivo['content'], (string) $archivo['fileName']);
            $contenido = (string) file_get_contents($zip);
            @unlink($zip);

            $respuesta = $this->client->subir($company, self::REEMPLAZAR, $contenido, [
                'filename' => (string) $archivo['fileName'] . '.zip',
                'filetype' => 'application/zip',
                'numRuc' => (string) $archivo['companyRuc'],
                'perTributario' => $periodo,
                'codOrigenEnvio' => '2',
                'codProceso' => self::PROCESO[$tipo],
                'codTipoCorrelativo' => '01',
                'nomArchivoImportacion' => (string) $archivo['fileName'],
                'codLibro' => self::LIBRO[$tipo],
            ], 'reemplazo', $submission);

            $ticket = $this->buscarTicket($respuesta);

            if ($ticket === '') {
                throw new SireApiException(
                    'SUNAT recibió el archivo pero no devolvió el ticket del reemplazo. No lo reenvíes todavía: revisa la bitácora y el estado del período.',
                    null,
                    $respuesta
                );
            }

            $submission->update([
                'ticket' => $ticket,
                'status' => 'en_proceso',
                'response' => [
                    'respuesta' => $respuesta,
                    'comparacion' => $comparacion['resumen'] ?? null,
                ],
            ]);

            [$estado, $registro] = $this->esperar($company, $periodo, $ticket, $submission, 3, 1500);

            $terminado = $estado === '06';

            $submission->update([
                'status' => $terminado ? 'terminado' : 'en_proceso',
                'response' => [
                    'respuesta' => $respuesta,
                    'estado' => $estado,
                    'registro' => $registro,
                    'comparacion' => $comparacion['resumen'] ?? null,
                ],
                'finished_at' => $terminado ? now() : null,
            ]);

            return [
                'ok' => true,
                'ticket' => $ticket,
                'estado' => $estado,
                'estado_etiqueta' => self::ETIQUETAS_ESTADO[$estado] ?? 'En proceso',
                'procesado' => $terminado,
                'submission_id' => $submission->id,
                'comparacion' => $comparacion['resumen'] ?? null,
                'message' => $terminado
                    ? 'SUNAT procesó el reemplazo del período. Ticket ' . $ticket . '.'
                    : 'SUNAT recibió el reemplazo (ticket ' . $ticket . ') y lo sigue procesando. Vuelve a consultar el estado en unos minutos.',
            ];
        } catch (SireApiException $e) {
            $submission->refresh();

            if (! in_array($submission->status, ['terminado', 'expirado'], true)) {
                $submission->update([
                    'status' => 'error',
                    'response' => array_merge((array) $submission->response, [
                        'error' => Str::limit($e->getMessage(), 900),
                    ]),
                    'finished_at' => now(),
                ]);
            }

            throw $e;
        } catch (Throwable $e) {
            $submission->update([
                'status' => 'error',
                'response' => ['error' => Str::limit($e->getMessage(), 900)],
                'finished_at' => now(),
            ]);

            throw new SireApiException('No se pudo reemplazar la propuesta: ' . $e->getMessage());
        }
    }

    /**
     * Acepta la propuesta que SUNAT tiene publicada para el período.
     *
     * @return array<string, mixed>
     */
    public function aceptar(Company $company, string $tipo, string $period, ?int $userId = null): array
    {
        $tipo = $this->tipo($tipo);

        if (! $this->client->credencialesCompletas($company)) {
            throw new SireApiException(
                'Faltan las credenciales del SIRE. Carga el client_id, el client_secret y el usuario y clave SOL en Configuración → SIRE.'
            );
        }

        $periodo = str_replace('-', '', $period);

        $submission = SireSubmission::create([
            'company_id' => $company->getKey(),
            'user_id' => $userId,
            'tipo' => $tipo,
            'period' => $periodo,
            'action' => 'aceptar',
            'status' => 'solicitado',
            'started_at' => now(),
        ]);

        try {
            $respuesta = $this->client->post(
                $company,
                sprintf(self::ACEPTAR[$tipo], $periodo),
                [],
                'aceptar',
                $submission
            );

            $ticket = $this->buscarTicket($respuesta);

            if ($ticket === '') {
                throw new SireApiException(
                    'SUNAT no devolvió el ticket de la aceptación. Revisa la bitácora antes de volver a intentarlo.',
                    null,
                    $respuesta
                );
            }

            $submission->update([
                'ticket' => $ticket,
                'status' => 'en_proceso',
                'response' => ['respuesta' => $respuesta],
            ]);

            [$estado, $registro] = $this->esperar($company, $periodo, $ticket, $submission, 3, 1500);

            $terminado = $estado === '06';

            $submission->update([
                'status' => $terminado ? 'terminado' : 'en_proceso',
                'response' => ['respuesta' => $respuesta, 'estado' => $estado, 'registro' => $registro],
                'finished_at' => $terminado ? now() : null,
            ]);

            return [
                'ok' => true,
                'ticket' => $ticket,
                'estado' => $estado,
                'estado_etiqueta' => self::ETIQUETAS_ESTADO[$estado] ?? 'En proceso',
                'procesado' => $terminado,
                'submission_id' => $submission->id,
                'message' => $terminado
                    ? 'SUNAT aceptó la propuesta del período. Ticket ' . $ticket . '.'
                    : 'SUNAT recibió la aceptación (ticket ' . $ticket . ') y la sigue procesando.',
            ];
        } catch (SireApiException $e) {
            $submission->refresh();

            if (! in_array($submission->status, ['terminado', 'expirado'], true)) {
                $submission->update([
                    'status' => 'error',
                    'response' => array_merge((array) $submission->response, [
                        'error' => Str::limit($e->getMessage(), 900),
                    ]),
                    'finished_at' => now(),
                ]);
            }

            throw $e;
        } catch (Throwable $e) {
            $submission->update([
                'status' => 'error',
                'response' => ['error' => Str::limit($e->getMessage(), 900)],
                'finished_at' => now(),
            ]);

            throw new SireApiException('No se pudo aceptar la propuesta: ' . $e->getMessage());
        }
    }

    /**
     * Consulta el estado de un proceso ya enviado y lo actualiza.
     *
     * @return array<string, mixed>
     */
    public function estado(Company $company, int $submissionId): array
    {
        $submission = SireSubmission::query()
            ->where('company_id', $company->getKey())
            ->find($submissionId);

        if (! $submission) {
            throw new SireApiException('El proceso no existe o pertenece a otra empresa.');
        }

        if (blank($submission->ticket)) {
            throw new SireApiException('El proceso todavía no tiene ticket de SUNAT.');
        }

        [$estado, $registro] = $this->esperar($company, (string) $submission->period, (string) $submission->ticket, $submission, 1, 0);

        $terminado = $estado === '06';

        $submission->update([
            'status' => $terminado ? 'terminado' : ($estado === 'EXP' ? 'expirado' : 'en_proceso'),
            'response' => array_merge((array) $submission->response, [
                'estado' => $estado,
                'registro' => $registro,
            ]),
            'finished_at' => $terminado ? ($submission->finished_at ?? now()) : null,
        ]);

        return [
            'ok' => true,
            'ticket' => $submission->ticket,
            'estado' => $estado,
            'estado_etiqueta' => self::ETIQUETAS_ESTADO[$estado] ?? 'En proceso',
            'procesado' => $terminado,
            'submission_id' => $submission->id,
            'message' => $terminado
                ? 'SUNAT terminó de procesar el ticket ' . $submission->ticket . '.'
                : 'SUNAT todavía está procesando el ticket ' . $submission->ticket . '.',
        ];
    }

    /**
     * Archivo del ERP del período, completo.
     *
     * @return array<string, mixed>
     */
    private function archivo(string $tipo, string $period): array
    {
        return $tipo === 'compras'
            ? $this->compras->build($period)
            : $this->ventas->build($period);
    }

    /**
     * Propuesta de SUNAT: primero la última descarga del período; si no existe,
     * se descarga con el flujo de solo lectura.
     *
     * @return array{content: string, archivo: string, submission_id: int}|null
     */
    private function propuesta(Company $company, string $tipo, string $period, ?int $userId): ?array
    {
        $existente = $this->propuestaExistente($company, $tipo, $period);

        if ($existente !== null) {
            return $existente;
        }

        $resultado = app(SirePropuestaService::class)->descargar($company, $tipo, $period, $userId);

        if (! ($resultado['descargado'] ?? false)) {
            return null;
        }

        $submission = SireSubmission::find($resultado['submission_id']);

        if (! $submission || blank($submission->file_path) || ! Storage::disk('local')->exists($submission->file_path)) {
            return null;
        }

        return [
            'content' => (string) Storage::disk('local')->get($submission->file_path),
            'archivo' => (string) $submission->file_name,
            'submission_id' => (int) $submission->id,
        ];
    }

    /**
     * @return array{content: string, archivo: string, submission_id: int}|null
     */
    private function propuestaExistente(Company $company, string $tipo, string $period): ?array
    {
        $submission = SireSubmission::query()
            ->where('company_id', $company->getKey())
            ->where('tipo', $tipo)
            ->where('period', str_replace('-', '', $period))
            ->where('action', 'exportar')
            ->where('status', 'terminado')
            ->whereNotNull('file_path')
            ->latest('id')
            ->first();

        if (! $submission || ! Storage::disk('local')->exists((string) $submission->file_path)) {
            return null;
        }

        return [
            'content' => (string) Storage::disk('local')->get((string) $submission->file_path),
            'archivo' => (string) $submission->file_name,
            'submission_id' => (int) $submission->id,
        ];
    }

    /**
     * Consulta el ticket hasta que SUNAT lo marca como terminado o se agotan
     * los intentos.
     *
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function esperar(
        Company $company,
        string $periodo,
        string $ticket,
        SireSubmission $submission,
        int $intentos,
        int $esperaMs
    ): array {
        $estado = '';
        $registro = [];
        $intentos = max(1, $intentos);

        for ($i = 1; $i <= $intentos; $i++) {
            $json = $this->client->get($company, self::CONSULTA_TICKET, [
                'perIni' => $periodo,
                'perFin' => $periodo,
                'page' => 1,
                'perPage' => 100,
                'numTicket' => $ticket,
            ], 'ticket', $submission);

            $registro = $this->registroEstado($json);
            $estado = $this->normalizarEstado($registro);

            $submission->update(['attempts' => $i]);

            if ($estado === '06' || $estado === 'EXP') {
                return [$estado, $registro];
            }

            if ($i < $intentos && $esperaMs > 0) {
                usleep($esperaMs * 1000);
            }
        }

        return [$estado, $registro];
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function buscarTicket(array $datos): string
    {
        foreach (['numTicket', 'numeroTicket', 'nroTicket', 'ticket'] as $clave) {
            if (isset($datos[$clave]) && is_scalar($datos[$clave]) && (string) $datos[$clave] !== '') {
                return (string) $datos[$clave];
            }
        }

        foreach ($datos as $valor) {
            if (is_array($valor)) {
                $encontrado = $this->buscarTicket($valor);

                if ($encontrado !== '') {
                    return $encontrado;
                }
            }
        }

        return '';
    }

    /**
     * @param  array<string|int, mixed>  $datos
     * @return array<string, mixed>
     */
    private function registroEstado(array $datos): array
    {
        if (isset($datos['estado'])) {
            return $datos;
        }

        foreach ($datos as $valor) {
            if (is_array($valor)) {
                $registro = $this->registroEstado($valor);

                if (isset($registro['estado'])) {
                    return $registro;
                }
            }
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $registro
     */
    private function normalizarEstado(array $registro): string
    {
        $valor = strtoupper(trim((string) ($registro['estado'] ?? '')));

        $equivalentes = [
            'TERMINADO' => '06',
            'PROCESO' => '05',
            'EN PROCESO' => '05',
            'SOLICITADO' => '01',
            'EXPIRADO' => 'EXP',
        ];

        if (isset($equivalentes[$valor])) {
            return $equivalentes[$valor];
        }

        if ($valor !== '' && ctype_digit($valor)) {
            return str_pad($valor, 2, '0', STR_PAD_LEFT);
        }

        return $valor;
    }

    private function tipo(string $tipo): string
    {
        return $tipo === 'compras' ? 'compras' : 'ventas';
    }
}
